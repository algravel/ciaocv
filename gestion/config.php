<?php
/**
 * Configuration autonome de /gestion (administration).
 * Ne dépend pas de l'app principale. Charge le .env du projet.
 */

$gestionBase = __DIR__;
$projectRoot = dirname($gestionBase);

// ─── Chargement .env (racine du projet) ───────────────────────────────────
$envFile = $projectRoot . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#')
            continue;
        if (strpos($line, '=') === false)
            continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (strlen($value) >= 2 && (($value[0] === '"' && $value[strlen($value) - 1] === '"') || ($value[0] === "'" && $value[strlen($value) - 1] === "'"))) {
            $value = substr($value, 1, -1);
        }
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
    }
}

// ─── Includes (Database, Encryption, Modèles) ──────────────────────────────
require_once $gestionBase . '/includes/Database.php';
require_once $gestionBase . '/includes/Encryption.php';
require_once $gestionBase . '/models/Admin.php';
require_once $gestionBase . '/models/Plan.php';
require_once $gestionBase . '/models/PlatformUser.php';
require_once $gestionBase . '/models/StripeSale.php';
require_once $gestionBase . '/models/Event.php';
require_once $gestionBase . '/models/Feedback.php';
require_once $gestionBase . '/models/DevTask.php';
require_once $gestionBase . '/models/Entrevue.php';
require_once $gestionBase . '/models/Entreprise.php';

// ─── Auto-init schéma DB ───────────────────────────────────────────────────
// Le schéma et les seeds sont désormais gérés par /gestion/migrate.php
// pour éviter des requêtes SHOW TABLES/COLUMNS à chaque chargement.


// ─── Constantes ───────────────────────────────────────────────────────────
define('GESTION_APP_NAME', 'CiaoCV');
// gestion.ciaocv.com → racine du domaine, pas de préfixe /gestion
define('GESTION_BASE_PATH', $_ENV['GESTION_BASE_PATH'] ?? '');
define('GESTION_APP_URL', $_ENV['APP_URL'] ?? 'https://app.ciaocv.com');
define('GESTION_URL', $_ENV['GESTION_URL'] ?? 'https://gestion.ciaocv.com');
define('GESTION_SITE_URL', $_ENV['SITE_URL'] ?? 'https://www.ciaocv.com');
define('GESTION_BASE', $gestionBase);
define('GESTION_VIEWS', $gestionBase . '/views');

// ─── Session (isolée : path /gestion, nom distinct) ────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => GESTION_BASE_PATH ?: '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('GESTION_SID');
    session_start();
}

// ─── Helpers (éviter redeclaration si chargé depuis app) ─────────────────────
if (!function_exists('e')) {
    function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('csrf_verify')) {
    function csrf_verify(): bool
    {
        $token = $_POST['_csrf_token'] ?? '';
        $sessionToken = $_SESSION['_csrf_token'] ?? '';
        return $token !== '' && $sessionToken !== '' && hash_equals($sessionToken, $token);
    }
}

/**
 * Envoie une requête POST à l'API ZeptoMail (utilise cURL si disponible).
 * @return array{success: bool, response: string|null}
 */
function _zeptomail_post(string $apiUrl, string $auth, array $payload): array
{
    $body = json_encode($payload);
    if (function_exists('curl_init')) {
        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: ' . $auth,
            ],
        ]);
        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);
        if ($errno !== 0) {
            error_log('[Zepto] curl error ' . $errno . ': ' . ($response ?: 'no response'));
            return ['success' => false, 'response' => $response ?: null];
        }
        $data = json_decode($response, true);
        $ok = isset($data['request_id']) || (isset($data['data']) && !isset($data['error']));
        return ['success' => $ok, 'response' => $response];
    }
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Accept: application/json\r\nContent-Type: application/json\r\nAuthorization: $auth\r\n",
            'content' => $body,
            'timeout' => 15,
        ],
    ]);
    $response = @file_get_contents($apiUrl, false, $ctx);
    if ($response === false) {
        error_log('[Zepto] file_get_contents failed (allow_url_fopen?)');
        return ['success' => false, 'response' => null];
    }
    $data = json_decode($response, true);
    $ok = isset($data['request_id']) || (isset($data['data']) && !isset($data['error']));
    return ['success' => $ok, 'response' => $response];
}

function zeptomail_send_otp(string $toEmail, string $toName, string $otpCode, string $lang = 'fr', string $accentColor = '#800020'): bool
{
    $apiUrl = $_ENV['ZEPTO_API_URL'] ?? 'https://api.zeptomail.com/v1.1/email';
    $token = $_ENV['ZEPTO_TOKEN'] ?? '';
    $fromAddr = $_ENV['ZEPTO_FROM_ADDRESS'] ?? 'noreply@ciaocv.com';
    $fromName = 'CIAOCV';
    if ($token === '') {
        return false;
    }
    $isEn = ($lang === 'en');
    $subject = $isEn ? 'Verification code' : 'Code de vérification';
    $titleH2 = $isEn ? 'Verification code' : 'Code de vérification';
    $bodyHello = $isEn ? 'Hello' : 'Bonjour';
    $bodyIntro = $isEn
        ? 'Use the following code to complete your login to the CiaoCV administration area:'
        : 'Utilisez le code suivant pour finaliser votre connexion à l\'espace administration CiaoCV :';
    $bodyExpire = $isEn ? 'This code expires in 10 minutes. Do not share it with anyone.' : 'Ce code expire dans 10 minutes. Ne le partagez avec personne.';
    $bodyTeam = $isEn ? '— The CiaoCV team' : '— L\'équipe CiaoCV';
    $auth = (strpos($token, 'Zoho-enczapikey') === 0) ? $token : 'Zoho-enczapikey ' . $token;
    $payload = [
        'from' => ['address' => $fromAddr, 'name' => $fromName],
        'to' => [['email_address' => ['address' => $toEmail, 'name' => $toName]]],
        'subject' => $subject,
        'htmlbody' => '<div style="font-family:sans-serif;max-width:480px;margin:0 auto;">' .
            '<h2 style="color:' . $accentColor . ';">' . $titleH2 . '</h2>' .
            '<p>' . $bodyHello . ' ' . htmlspecialchars($toName) . ',</p>' .
            '<p>' . $bodyIntro . '</p>' .
            '<p style="font-size:28px;font-weight:700;letter-spacing:6px;color:' . $accentColor . ';margin:1.5rem 0;">' . htmlspecialchars($otpCode) . '</p>' .
            '<p style="color:#666;font-size:14px;">' . $bodyExpire . '</p>' .
            '<p style="color:#666;font-size:12px;">' . $bodyTeam . '</p></div>',
    ];
    $r = _zeptomail_post($apiUrl, $auth, $payload);
    return $r['success'];
}

function _zeptomail_email_logo(): string
{
    return '<div style="text-align:center;margin-bottom:2rem;">' .
        '<span style="font-family:\'Montserrat\',sans-serif;font-size:2rem;font-weight:800;color:#800020;">ciao</span>' .
        '<span style="font-family:\'Montserrat\',sans-serif;font-size:2rem;font-weight:800;color:#1E293B;">cv</span>' .
        '</div>';
}

function _zeptomail_email_logo_user(string $marginBottom = '2rem'): string
{
    return '<div style="text-align:center;margin-bottom:' . $marginBottom . ';">' .
        '<span style="font-family:\'Montserrat\',sans-serif;font-size:2rem;font-weight:800;color:#2563EB;">ciao</span>' .
        '<span style="font-family:\'Montserrat\',sans-serif;font-size:2rem;font-weight:800;color:#0F172A;">cv</span>' .
        '</div>';
}

function zeptomail_send_new_admin_credentials(string $toEmail, string $toName, string $newPassword): bool
{
    $apiUrl = $_ENV['ZEPTO_API_URL'] ?? 'https://api.zeptomail.com/v1.1/email';
    $token = $_ENV['ZEPTO_TOKEN'] ?? '';
    $fromAddr = $_ENV['ZEPTO_FROM_ADDRESS'] ?? 'noreply@ciaocv.com';
    $fromName = $_ENV['ZEPTO_FROM_NAME'] ?? 'CIAOCV';
    if ($token === '') {
        return false;
    }
    $auth = (strpos($token, 'Zoho-enczapikey') === 0) ? $token : 'Zoho-enczapikey ' . $token;
    $gestionBase = rtrim(defined('GESTION_URL') ? GESTION_URL : ($_ENV['GESTION_URL'] ?? 'https://gestion.ciaocv.com'), '/');
    $basePath = $_ENV['GESTION_BASE_PATH'] ?? '';
    $loginUrl = $gestionBase . ($basePath ? '/' . trim($basePath, '/') : '') . '/connexion';
    $payload = [
        'from' => ['address' => $fromAddr, 'name' => $fromName],
        'to' => [['email_address' => ['address' => $toEmail, 'name' => $toName]]],
        'subject' => 'Vos accès',
        'htmlbody' => '<div style="font-family:\'Montserrat\',sans-serif;max-width:480px;margin:0 auto;padding:2rem 1rem;">' .
            _zeptomail_email_logo() .
            '<h2 style="color:#800020;font-size:1.25rem;margin-bottom:1rem;">Voici vos accès</h2>' .
            '<p>Bonjour ' . htmlspecialchars($toName) . ',</p>' .
            '<p>Votre compte administrateur CiaoCV a été créé. Voici vos identifiants de connexion :</p>' .
            '<p><strong>Courriel :</strong> ' . htmlspecialchars($toEmail) . '</p>' .
            '<p><strong>Mot de passe :</strong> <code style="background:#f3f4f6;padding:0.25rem 0.5rem;border-radius:4px;">' . htmlspecialchars($newPassword) . '</code></p>' .
            '<p><a href="' . htmlspecialchars($loginUrl) . '" style="display:inline-block;background:#800020;color:white;padding:0.75rem 1.5rem;text-decoration:none;border-radius:8px;margin-top:1rem;">Se connecter</a></p>' .
            '<p style="color:#666;font-size:14px;">Pour des raisons de sécurité, nous vous recommandons de modifier ce mot de passe après votre prochaine connexion.</p>' .
            '<p style="color:#666;font-size:12px;">— L\'équipe CiaoCV</p></div>',
    ];
    $r = _zeptomail_post($apiUrl, $auth, $payload);
    return $r['success'];
}

function zeptomail_send_new_platform_user_credentials(string $toEmail, string $toName, string $newPassword): bool
{
    $apiUrl = $_ENV['ZEPTO_API_URL'] ?? 'https://api.zeptomail.com/v1.1/email';
    $token = $_ENV['ZEPTO_TOKEN'] ?? '';
    $fromAddr = $_ENV['ZEPTO_FROM_ADDRESS'] ?? 'noreply@ciaocv.com';
    $fromName = $_ENV['ZEPTO_FROM_NAME'] ?? 'ciaocv';
    if ($token === '') {
        error_log('[Zepto] platform_user_credentials: token empty');
        return false;
    }
    $auth = (strpos($token, 'Zoho-enczapikey') === 0) ? $token : 'Zoho-enczapikey ' . $token;
    $siteUrl = rtrim($_ENV['SITE_URL'] ?? 'https://www.ciaocv.com', '/');
    $loginUrl = $siteUrl . '/connexion';
    $payload = [
        'from' => ['address' => $fromAddr, 'name' => $fromName],
        'to' => [['email_address' => ['address' => $toEmail, 'name' => $toName]]],
        'subject' => 'Votre compte CIAOCV a été créé',
        'htmlbody' => '<div style="font-family:\'Montserrat\',sans-serif;max-width:480px;margin:0 auto;padding:2rem 1rem;">' .
            _zeptomail_email_logo_user() .
            '<h2 style="color:#2563EB;font-size:1.25rem;margin-bottom:1rem;">Voici vos identifiants</h2>' .
            '<p>Bonjour ' . htmlspecialchars($toName) . ',</p>' .
            '<p>Votre compte CIAOCV a été créé. Voici vos identifiants de connexion :</p>' .
            '<p><strong>Courriel :</strong> ' . htmlspecialchars($toEmail) . '</p>' .
            '<p><strong>Mot de passe :</strong> <code style="background:#f3f4f6;padding:0.25rem 0.5rem;border-radius:4px;">' . htmlspecialchars($newPassword) . '</code></p>' .
            '<p><a href="' . htmlspecialchars($loginUrl) . '" style="display:inline-block;background:#2563EB;color:white;padding:0.75rem 1.5rem;text-decoration:none;border-radius:8px;margin-top:1rem;">Se connecter</a></p>' .
            '<p style="color:#666;font-size:14px;">Pour des raisons de sécurité, nous vous recommandons de modifier ce mot de passe après votre prochaine connexion.</p>' .
            '<p style="color:#666;font-size:12px;">— L\'équipe CiaoCV</p></div>',
    ];
    $r = _zeptomail_post($apiUrl, $auth, $payload);
    if (!$r['success'] && $r['response']) {
        error_log('[Zepto] platform_user_credentials failed: ' . substr($r['response'], 0, 500));
    }
    return $r['success'];
}

function zeptomail_send_platform_user_password_reset(string $toEmail, string $toName, string $newPassword): bool
{
    $apiUrl = $_ENV['ZEPTO_API_URL'] ?? 'https://api.zeptomail.com/v1.1/email';
    $token = $_ENV['ZEPTO_TOKEN'] ?? '';
    $fromAddr = $_ENV['ZEPTO_FROM_ADDRESS'] ?? 'noreply@ciaocv.com';
    $fromName = $_ENV['ZEPTO_FROM_NAME'] ?? 'CIAOCV';
    if ($token === '') {
        return false;
    }
    $auth = (strpos($token, 'Zoho-enczapikey') === 0) ? $token : 'Zoho-enczapikey ' . $token;
    $appUrl = rtrim($_ENV['APP_URL'] ?? $_ENV['SITE_URL'] ?? 'https://app.ciaocv.com', '/');
    $loginUrl = $appUrl . '/connexion';
    $payload = [
        'from' => ['address' => $fromAddr, 'name' => $fromName],
        'to' => [['email_address' => ['address' => $toEmail, 'name' => $toName]]],
        'subject' => 'Mot de passe réinitialisé - CiaoCV',
        'htmlbody' => '<div style="font-family:\'Montserrat\',sans-serif;max-width:480px;margin:0 auto;padding:2rem 1rem;">' .
            _zeptomail_email_logo_user() .
            '<h2 style="color:#2563EB;font-size:1.25rem;margin-bottom:1rem;">Nouveau mot de passe</h2>' .
            '<p>Bonjour ' . htmlspecialchars($toName) . ',</p>' .
            '<p>Votre mot de passe a été réinitialisé. Voici vos nouveaux identifiants :</p>' .
            '<p><strong>Courriel :</strong> ' . htmlspecialchars($toEmail) . '</p>' .
            '<p><strong>Nouveau mot de passe :</strong> <code style="background:#f3f4f6;padding:0.25rem 0.5rem;border-radius:4px;">' . htmlspecialchars($newPassword) . '</code></p>' .
            '<p><a href="' . htmlspecialchars($loginUrl) . '" style="display:inline-block;background:#2563EB;color:white;padding:0.75rem 1.5rem;text-decoration:none;border-radius:8px;margin-top:1rem;">Se connecter</a></p>' .
            '<p style="color:#666;font-size:14px;">Pour des raisons de sécurité, nous vous recommandons de modifier ce mot de passe après votre prochaine connexion.</p>' .
            '<p style="color:#666;font-size:12px;">— L\'équipe CiaoCV</p></div>',
    ];
    $r = _zeptomail_post($apiUrl, $auth, $payload);
    return $r['success'];
}

function zeptomail_send_new_candidature_notification(string $toEmail, string $toName, string $posteTitle, string $candidatName, string $viewUrl): bool
{
    $apiUrl = $_ENV['ZEPTO_API_URL'] ?? 'https://api.zeptomail.com/v1.1/email';
    $token = $_ENV['ZEPTO_TOKEN'] ?? '';
    $fromAddr = $_ENV['ZEPTO_FROM_ADDRESS'] ?? 'noreply@ciaocv.com';
    $fromName = $_ENV['ZEPTO_FROM_NAME'] ?? 'ciaocv';
    if ($token === '') {
        return false;
    }
    $auth = (strpos($token, 'Zoho-enczapikey') === 0) ? $token : 'Zoho-enczapikey ' . $token;
    $payload = [
        'from' => ['address' => $fromAddr, 'name' => $fromName],
        'to' => [['email_address' => ['address' => $toEmail, 'name' => $toName]]],
        'subject' => 'Nouvelle candidature reçue – ' . $posteTitle,
        'htmlbody' => '<div style="font-family:\'Montserrat\',sans-serif;max-width:480px;margin:0 auto;padding:2rem 1rem;">' .
            _zeptomail_email_logo_user() .
            '<h2 style="color:#2563EB;font-size:1.25rem;margin-bottom:1rem;">Nouvelle candidature</h2>' .
            '<p>Bonjour ' . htmlspecialchars($toName) . ',</p>' .
            '<p>Une nouvelle candidature vidéo a été reçue pour le poste <strong>' . htmlspecialchars($posteTitle) . '</strong>.</p>' .
            '<p>Candidat : <strong>' . htmlspecialchars($candidatName) . '</strong></p>' .
            '<p><a href="' . htmlspecialchars($viewUrl) . '" style="display:inline-block;background:#2563EB;color:white;padding:0.75rem 1.5rem;text-decoration:none;border-radius:8px;margin-top:1rem;">Voir la candidature</a></p>' .
            '<p style="color:#666;font-size:12px;">— L\'équipe CiaoCV</p></div>',
    ];
    $r = _zeptomail_post($apiUrl, $auth, $payload);
    return $r['success'];
}

/**
 * Notifie un évaluateur existant qu'il a été assigné à un affichage.
 */
function zeptomail_send_evaluateur_assigned(string $toEmail, string $toName, string $posteTitle, string $viewUrl): bool
{
    $apiUrl = $_ENV['ZEPTO_API_URL'] ?? 'https://api.zeptomail.com/v1.1/email';
    $token = $_ENV['ZEPTO_TOKEN'] ?? '';
    $fromAddr = $_ENV['ZEPTO_FROM_ADDRESS'] ?? 'noreply@ciaocv.com';
    $fromName = $_ENV['ZEPTO_FROM_NAME'] ?? 'ciaocv';
    if ($token === '') {
        return false;
    }
    $auth = (strpos($token, 'Zoho-enczapikey') === 0) ? $token : 'Zoho-enczapikey ' . $token;
    $payload = [
        'from' => ['address' => $fromAddr, 'name' => $fromName],
        'to' => [['email_address' => ['address' => $toEmail, 'name' => $toName]]],
        'subject' => 'Vous avez été assigné comme évaluateur – ' . $posteTitle,
        'htmlbody' => '<div style="font-family:\'Montserrat\',sans-serif;max-width:480px;margin:0 auto;padding:2rem 1rem;">' .
            _zeptomail_email_logo_user() .
            '<h2 style="color:#2563EB;font-size:1.25rem;margin-bottom:1rem;">Nouvelle assignation</h2>' .
            '<p>Bonjour ' . htmlspecialchars($toName) . ',</p>' .
            '<p>Vous avez été assigné comme évaluateur pour le poste <strong>' . htmlspecialchars($posteTitle) . '</strong>.</p>' .
            '<p><a href="' . htmlspecialchars($viewUrl) . '" style="display:inline-block;background:#2563EB;color:white;padding:0.75rem 1.5rem;text-decoration:none;border-radius:8px;margin-top:1rem;">Voir l\'affichage</a></p>' .
            '<p style="color:#666;font-size:12px;">— L\'équipe CiaoCV</p></div>',
    ];
    $r = _zeptomail_post($apiUrl, $auth, $payload);
    if (!$r['success'] && $r['response']) {
        error_log('[Zepto] evaluateur_assigned failed: ' . substr($r['response'], 0, 500));
    }
    return $r['success'];
}

function zeptomail_send_password_reset(string $toEmail, string $toName, string $newPassword): bool
{
    $apiUrl = $_ENV['ZEPTO_API_URL'] ?? 'https://api.zeptomail.com/v1.1/email';
    $token = $_ENV['ZEPTO_TOKEN'] ?? '';
    $fromAddr = $_ENV['ZEPTO_FROM_ADDRESS'] ?? 'noreply@ciaocv.com';
    $fromName = $_ENV['ZEPTO_FROM_NAME'] ?? 'CIAOCV';
    if ($token === '') {
        return false;
    }
    $auth = (strpos($token, 'Zoho-enczapikey') === 0) ? $token : 'Zoho-enczapikey ' . $token;
    $gestionBase = rtrim(defined('GESTION_URL') ? GESTION_URL : ($_ENV['GESTION_URL'] ?? 'https://gestion.ciaocv.com'), '/');
    $basePath = $_ENV['GESTION_BASE_PATH'] ?? '';
    $loginUrl = $gestionBase . ($basePath ? '/' . trim($basePath, '/') : '') . '/connexion';
    $payload = [
        'from' => ['address' => $fromAddr, 'name' => $fromName],
        'to' => [['email_address' => ['address' => $toEmail, 'name' => $toName]]],
        'subject' => 'Nouveau mot de passe',
        'htmlbody' => '<div style="font-family:\'Montserrat\',sans-serif;max-width:480px;margin:0 auto;padding:2rem 1rem;">' .
            _zeptomail_email_logo() .
            '<h2 style="color:#800020;font-size:1.25rem;margin-bottom:1rem;">Mot de passe réinitialisé</h2>' .
            '<p>Bonjour ' . htmlspecialchars($toName) . ',</p>' .
            '<p>Votre mot de passe a été réinitialisé. Voici vos nouveaux identifiants :</p>' .
            '<p><strong>Courriel :</strong> ' . htmlspecialchars($toEmail) . '</p>' .
            '<p><strong>Nouveau mot de passe :</strong> <code style="background:#f3f4f6;padding:0.25rem 0.5rem;border-radius:4px;">' . htmlspecialchars($newPassword) . '</code></p>' .
            '<p><a href="' . htmlspecialchars($loginUrl) . '" style="display:inline-block;background:#800020;color:white;padding:0.75rem 1.5rem;text-decoration:none;border-radius:8px;margin-top:1rem;">Se connecter</a></p>' .
            '<p style="color:#666;font-size:14px;">Pour des raisons de sécurité, nous vous recommandons de modifier ce mot de passe après votre prochaine connexion.</p>' .
            '<p style="color:#666;font-size:12px;">— L\'équipe CiaoCV</p></div>',
    ];
    $r = _zeptomail_post($apiUrl, $auth, $payload);
    return $r['success'];
}

/**
 * Envoie un courriel personnalisé à un candidat (refus, poste comblé, etc.).
 * @param string $toEmail Courriel du candidat
 * @param string $toName Nom du candidat
 * @param string $message Corps du message (texte brut, converti en HTML)
 */
function zeptomail_send_candidate_notification(string $toEmail, string $toName, string $message): bool
{
    $apiUrl = $_ENV['ZEPTO_API_URL'] ?? 'https://api.zeptomail.com/v1.1/email';
    $token = $_ENV['ZEPTO_TOKEN'] ?? '';
    $fromAddr = $_ENV['ZEPTO_FROM_ADDRESS'] ?? 'noreply@ciaocv.com';
    $fromName = $_ENV['ZEPTO_FROM_NAME'] ?? 'CiaoCV';
    if ($token === '' || trim($toEmail) === '' || strpos($toEmail, '@') === false) {
        return false;
    }
    $auth = (strpos($token, 'Zoho-enczapikey') === 0) ? $token : 'Zoho-enczapikey ' . $token;
    $subject = 'Votre candidature – message de l\'équipe recrutement';
    // Retirer la salutation du corps pour éviter le doublon (le HTML ajoute déjà "Bonjour [nom],")
    $message = preg_replace('/^\s*Bonjour\s+[^,\n]*,\s*\n?/iu', '', trim($message));
    $message = trim($message);
    // Un seul saut de ligne entre paragraphes pour réduire l'espace
    $message = preg_replace('/\n{2,}/', "\n", $message);
    $messageHtml = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
    $payload = [
        'from' => ['address' => $fromAddr, 'name' => $fromName],
        'to' => [['email_address' => ['address' => $toEmail, 'name' => $toName]]],
        'subject' => $subject,
        'htmlbody' => '<div style="font-family:\'Montserrat\',sans-serif;max-width:480px;margin:0 auto;padding:1rem 1rem;text-align:left;">' .
            _zeptomail_email_logo_user('1rem') .
            '<h2 style="color:#2563EB;font-size:1.25rem;margin:0 0 1.25rem 0;text-align:center;">Message concernant votre candidature</h2>' .
            '<p style="margin:0 0 0.5rem 0;line-height:1.4;">Bonjour ' . htmlspecialchars($toName, ENT_QUOTES, 'UTF-8') . ',</p>' .
            '<div style="white-space:pre-wrap;margin:0;line-height:1.4;">' . $messageHtml . '</div></div>',
    ];
    $r = _zeptomail_post($apiUrl, $auth, $payload);
    if (!$r['success'] && $r['response']) {
        error_log('[Zepto] candidate_notification failed: ' . substr($r['response'], 0, 500));
    }
    return $r['success'];
}

if (!function_exists('turnstile_verify')) {
    function turnstile_verify(string $token, ?string $remoteIp = null): array
    {
        $secret = $_ENV['TURNSTILE_SECRET_KEY'] ?? '';
        if ($secret === '' || $token === '') {
            return ['success' => false, 'error-codes' => ['missing-input-response']];
        }
        $data = ['secret' => $secret, 'response' => $token];
        if ($remoteIp !== null && $remoteIp !== '') {
            $data['remoteip'] = $remoteIp;
        }
        $ctx = stream_context_create([
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
                'timeout' => 10,
            ],
        ]);
        $response = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $ctx);
        if ($response === false) {
            return ['success' => false, 'error-codes' => ['internal-error']];
        }
        $result = json_decode($response, true);
        return is_array($result) ? $result : ['success' => false, 'error-codes' => ['internal-error']];
    }
}

function gestion_asset(string $path, bool $noCache = false): string
{
    if ($noCache) {
        $version = (string) time();
    } else {
        $version = $_ENV['GESTION_ASSET_VERSION'] ?? $_ENV['ASSET_VERSION'] ?? null;
        if ($version === null || $version === '') {
            $filePath = GESTION_BASE . '/' . ltrim($path, '/');
            $version = file_exists($filePath) ? (string) filemtime($filePath) : (string) time();
        }
    }
    return $path . '?v=' . $version;
}
