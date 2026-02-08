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
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key   = trim($key);
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

// ─── Auto-init schéma DB si tables absentes ────────────────────────────────
// ─── Auto-seed si tables vides ─────────────────────────────────────────────
try {
    $pdo = Database::get();
    $stmt = $pdo->query("SHOW TABLES LIKE 'gestion_plans'");
    if ($stmt->rowCount() === 0) {
        $schemaFile = $gestionBase . '/sql/schema.sql';
        if (file_exists($schemaFile)) {
            $sql = file_get_contents($schemaFile);
            $sql = preg_replace('/--[^\n]*\n/', "\n", $sql);
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $st) {
                if ($st !== '') {
                    $pdo->exec($st);
                }
            }
        }
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM gestion_admins LIKE 'deleted_at'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec('ALTER TABLE gestion_admins ADD COLUMN deleted_at DATETIME NULL COMMENT "Soft delete" AFTER created_at, ADD INDEX idx_deleted (deleted_at)');
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM gestion_plans LIKE 'name_fr'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec('ALTER TABLE gestion_plans ADD COLUMN name_fr VARCHAR(100) NOT NULL DEFAULT "" AFTER id, ADD COLUMN name_en VARCHAR(100) NOT NULL DEFAULT "" AFTER name_fr');
        $pdo->exec('UPDATE gestion_plans SET name_fr = name, name_en = name');
        $pdo->exec('ALTER TABLE gestion_plans DROP COLUMN name');
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM gestion_plans LIKE 'active'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec('ALTER TABLE gestion_plans ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1 COMMENT "0=désactivé" AFTER price_yearly');
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM gestion_platform_users LIKE 'billable'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec('ALTER TABLE gestion_platform_users ADD COLUMN billable TINYINT(1) NOT NULL DEFAULT 1 COMMENT "0=non facturable" AFTER plan_id');
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM gestion_platform_users LIKE 'prenom_encrypted'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec('ALTER TABLE gestion_platform_users ADD COLUMN prenom_encrypted TEXT NULL COMMENT "Prénom" AFTER id');
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM gestion_platform_users LIKE 'active'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec('ALTER TABLE gestion_platform_users ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1 COMMENT "0=désactivé" AFTER billable');
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM gestion_platform_users LIKE 'password_hash'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec('ALTER TABLE gestion_platform_users ADD COLUMN password_hash VARCHAR(255) NULL COMMENT "Hash bcrypt" AFTER email_encrypted');
    }
    $stmt = $pdo->query("SHOW TABLES LIKE 'gestion_feedback'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("CREATE TABLE gestion_feedback (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            type ENUM('problem', 'idea') NOT NULL DEFAULT 'problem',
            message TEXT NOT NULL,
            source VARCHAR(50) NOT NULL DEFAULT 'app',
            user_email VARCHAR(255) NULL,
            user_name VARCHAR(255) NULL,
            platform_user_id INT UNSIGNED NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (platform_user_id) REFERENCES gestion_platform_users(id) ON DELETE SET NULL,
            INDEX idx_created (created_at),
            INDEX idx_type (type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    // Seed si gestion_plans vide
    $stmt = $pdo->query('SELECT COUNT(*) FROM gestion_plans');
    if ((int) $stmt->fetchColumn() === 0) {
        $enc = new Encryption();
        // Forfaits alignés sur https://www.ciaocv.com/tarifs
        $pdo->exec("INSERT INTO gestion_plans (name_fr, name_en, video_limit, price_monthly, price_yearly) VALUES
            ('Découverte', 'Discovery', 5, 0, 0),
            ('À la carte', 'Pay per use', 9999, 79, 79),
            ('Pro', 'Pro', 50, 139, 1188),
            ('Expert', 'Expert', 200, 199, 1788)");
        $adminEmail = 'admin@ciaocv.com';
        $hash = hash('sha256', strtolower($adminEmail));
        $pdo->prepare('INSERT INTO gestion_admins (email_search_hash, email_encrypted, password_hash, name_encrypted, role) VALUES (?, ?, ?, ?, ?)')->execute([
            $hash, $enc->encrypt($adminEmail), password_hash('AdminDemo2026!', PASSWORD_DEFAULT), $enc->encrypt('Administrateur'), 'admin'
        ]);
        $planId = (int) $pdo->query("SELECT id FROM gestion_plans WHERE name_fr = 'Pro' LIMIT 1")->fetchColumn();
        $stmt = $pdo->prepare('INSERT INTO gestion_platform_users (prenom_encrypted, name_encrypted, email_encrypted, role, plan_id) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$enc->encrypt('Marie'), $enc->encrypt('Tremblay'), $enc->encrypt('marie@example.com'), 'admin', $planId]);
        $stmt->execute([$enc->encrypt('Pierre'), $enc->encrypt('Roy'), $enc->encrypt('pierre@example.com'), 'user', $planId]);
        $adminId = (int) $pdo->query('SELECT id FROM gestion_admins LIMIT 1')->fetchColumn();
        $events = [
            ["A modifié le forfait Pro — limite vidéos : 50 → 75"],
            ["A créé le forfait Entreprise — 500 vidéos, 199 \$/mois"],
            ["A modifié l'utilisateur Sophie Martin — rôle : Évaluateur → Admin"],
            ["A ajouté l'utilisateur Luc Bergeron (luc@example.com)"],
            ["A supprimé le forfait Starter"],
            ["Nouvelle vente Stripe — Forfait Platine, 99,99 \$ (user@example.com)"],
        ];
        $stmt = $pdo->prepare('INSERT INTO gestion_events (admin_id, action_type, entity_type, entity_id, details_encrypted) VALUES (?, ?, ?, ?, ?)');
        foreach ([['modification','plan','1'], ['creation','plan',null], ['modification','user','2'], ['creation','user',null], ['suppression','plan',null], ['sale','sale',null]] as $i => $e) {
            $stmt->execute([$adminId, $e[0], $e[1], $e[2], $enc->encrypt($events[$i][0])]);
        }
    }
    // Admin supplémentaire : algravel@gmail.com (créé si absent)
    $extraEmail = 'algravel@gmail.com';
    $extraHash = hash('sha256', strtolower($extraEmail));
    $stmt = $pdo->prepare("SELECT id FROM gestion_admins WHERE email_search_hash = ?");
    $stmt->execute([$extraHash]);
    if (!$stmt->fetch()) {
        $enc = new Encryption();
        $pdo->prepare('INSERT INTO gestion_admins (email_search_hash, email_encrypted, password_hash, name_encrypted, role) VALUES (?, ?, ?, ?, ?)')->execute([
            $extraHash, $enc->encrypt($extraEmail), password_hash('!Pomme123!', PASSWORD_DEFAULT), $enc->encrypt('Algravel'), 'admin'
        ]);
    }
} catch (Throwable $e) {
    // Connexion ou init échouée — les requêtes échoueront plus tard
}

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
        'path'     => GESTION_BASE_PATH ?: '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('GESTION_SID');
    session_start();
}

// ─── Helpers ───────────────────────────────────────────────────────────────
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    $token = $_POST['_csrf_token'] ?? '';
    $sessionToken = $_SESSION['_csrf_token'] ?? '';
    return $token !== '' && $sessionToken !== '' && hash_equals($sessionToken, $token);
}

function zeptomail_send_otp(string $toEmail, string $toName, string $otpCode, string $lang = 'fr'): bool
{
    $apiUrl = $_ENV['ZEPTO_API_URL'] ?? 'https://api.zeptomail.com/v1.1/email';
    $token  = $_ENV['ZEPTO_TOKEN'] ?? '';
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
        'to'   => [['email_address' => ['address' => $toEmail, 'name' => $toName]]],
        'subject' => $subject,
        'htmlbody' => '<div style="font-family:sans-serif;max-width:480px;margin:0 auto;">' .
            '<h2 style="color:#800020;">' . $titleH2 . '</h2>' .
            '<p>' . $bodyHello . ' ' . htmlspecialchars($toName) . ',</p>' .
            '<p>' . $bodyIntro . '</p>' .
            '<p style="font-size:28px;font-weight:700;letter-spacing:6px;color:#800020;margin:1.5rem 0;">' . htmlspecialchars($otpCode) . '</p>' .
            '<p style="color:#666;font-size:14px;">' . $bodyExpire . '</p>' .
            '<p style="color:#666;font-size:12px;">' . $bodyTeam . '</p></div>',
    ];
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Accept: application/json\r\nContent-Type: application/json\r\nAuthorization: $auth\r\n",
            'content' => json_encode($payload),
            'timeout' => 15,
        ],
    ]);
    $response = @file_get_contents($apiUrl, false, $ctx);
    if ($response === false) {
        return false;
    }
    $data = json_decode($response, true);
    return isset($data['request_id']) || (isset($data['data']) && !isset($data['error']));
}

function _zeptomail_email_logo(): string
{
    return '<div style="text-align:center;margin-bottom:2rem;">' .
        '<span style="font-family:\'Montserrat\',sans-serif;font-size:2rem;font-weight:800;color:#800020;">ciao</span>' .
        '<span style="font-family:\'Montserrat\',sans-serif;font-size:2rem;font-weight:800;color:#1E293B;">cv</span>' .
        '</div>';
}

function _zeptomail_email_logo_user(): string
{
    return '<div style="text-align:center;margin-bottom:2rem;">' .
        '<span style="font-family:\'Montserrat\',sans-serif;font-size:2rem;font-weight:800;color:#2563EB;">ciao</span>' .
        '<span style="font-family:\'Montserrat\',sans-serif;font-size:2rem;font-weight:800;color:#0F172A;">cv</span>' .
        '</div>';
}

function zeptomail_send_new_admin_credentials(string $toEmail, string $toName, string $newPassword): bool
{
    $apiUrl = $_ENV['ZEPTO_API_URL'] ?? 'https://api.zeptomail.com/v1.1/email';
    $token  = $_ENV['ZEPTO_TOKEN'] ?? '';
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
        'to'   => [['email_address' => ['address' => $toEmail, 'name' => $toName]]],
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
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Accept: application/json\r\nContent-Type: application/json\r\nAuthorization: $auth\r\n",
            'content' => json_encode($payload),
            'timeout' => 15,
        ],
    ]);
    $response = @file_get_contents($apiUrl, false, $ctx);
    if ($response === false) {
        return false;
    }
    $data = json_decode($response, true);
    return isset($data['request_id']) || (isset($data['data']) && !isset($data['error']));
}

function zeptomail_send_new_platform_user_credentials(string $toEmail, string $toName, string $newPassword): bool
{
    $apiUrl = $_ENV['ZEPTO_API_URL'] ?? 'https://api.zeptomail.com/v1.1/email';
    $token  = $_ENV['ZEPTO_TOKEN'] ?? '';
    $fromAddr = $_ENV['ZEPTO_FROM_ADDRESS'] ?? 'noreply@ciaocv.com';
    $fromName = $_ENV['ZEPTO_FROM_NAME'] ?? 'CIAOCV';
    $logPath = dirname(__DIR__) . '/.cursor/debug.log';
    if ($token === '') {
        file_put_contents($logPath, json_encode(['timestamp' => round(microtime(true) * 1000), 'location' => 'config.php:zeptomail_platform_user', 'message' => 'token empty', 'data' => ['hasToken' => false], 'hypothesisId' => 'H1']) . "\n", FILE_APPEND | LOCK_EX);
        return false;
    }
    file_put_contents($logPath, json_encode(['timestamp' => round(microtime(true) * 1000), 'location' => 'config.php:zeptomail_platform_user', 'message' => 'token present, calling API', 'data' => ['hasToken' => true], 'hypothesisId' => 'H1']) . "\n", FILE_APPEND | LOCK_EX);
    $auth = (strpos($token, 'Zoho-enczapikey') === 0) ? $token : 'Zoho-enczapikey ' . $token;
    $appUrl = rtrim($_ENV['APP_URL'] ?? 'https://app.ciaocv.com', '/');
    $loginUrl = $appUrl . '/connexion';
    $payload = [
        'from' => ['address' => $fromAddr, 'name' => $fromName],
        'to'   => [['email_address' => ['address' => $toEmail, 'name' => $toName]]],
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
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Accept: application/json\r\nContent-Type: application/json\r\nAuthorization: $auth\r\n",
            'content' => json_encode($payload),
            'timeout' => 15,
        ],
    ]);
    $response = @file_get_contents($apiUrl, false, $ctx);
    if ($response === false) {
        file_put_contents($logPath, json_encode(['timestamp' => round(microtime(true) * 1000), 'location' => 'config.php:zeptomail_platform_user', 'message' => 'file_get_contents failed', 'data' => ['response' => false], 'hypothesisId' => 'H2']) . "\n", FILE_APPEND | LOCK_EX);
        return false;
    }
    $data = json_decode($response, true);
    $success = isset($data['request_id']) || (isset($data['data']) && !isset($data['error']));
    file_put_contents($logPath, json_encode(['timestamp' => round(microtime(true) * 1000), 'location' => 'config.php:zeptomail_platform_user', 'message' => 'API response', 'data' => ['success' => $success, 'hasRequestId' => isset($data['request_id']), 'hasError' => isset($data['error']), 'responseKeys' => array_keys($data ?? [])], 'hypothesisId' => 'H3']) . "\n", FILE_APPEND | LOCK_EX);
    return $success;
}

function zeptomail_send_platform_user_password_reset(string $toEmail, string $toName, string $newPassword): bool
{
    $apiUrl = $_ENV['ZEPTO_API_URL'] ?? 'https://api.zeptomail.com/v1.1/email';
    $token  = $_ENV['ZEPTO_TOKEN'] ?? '';
    $fromAddr = $_ENV['ZEPTO_FROM_ADDRESS'] ?? 'noreply@ciaocv.com';
    $fromName = $_ENV['ZEPTO_FROM_NAME'] ?? 'CIAOCV';
    if ($token === '') {
        return false;
    }
    $auth = (strpos($token, 'Zoho-enczapikey') === 0) ? $token : 'Zoho-enczapikey ' . $token;
    $appUrl = rtrim($_ENV['APP_URL'] ?? 'https://app.ciaocv.com', '/');
    $loginUrl = $appUrl . '/connexion';
    $payload = [
        'from' => ['address' => $fromAddr, 'name' => $fromName],
        'to'   => [['email_address' => ['address' => $toEmail, 'name' => $toName]]],
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
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Accept: application/json\r\nContent-Type: application/json\r\nAuthorization: $auth\r\n",
            'content' => json_encode($payload),
            'timeout' => 15,
        ],
    ]);
    $response = @file_get_contents($apiUrl, false, $ctx);
    if ($response === false) {
        return false;
    }
    $data = json_decode($response, true);
    return isset($data['request_id']) || (isset($data['data']) && !isset($data['error']));
}

function zeptomail_send_password_reset(string $toEmail, string $toName, string $newPassword): bool
{
    $apiUrl = $_ENV['ZEPTO_API_URL'] ?? 'https://api.zeptomail.com/v1.1/email';
    $token  = $_ENV['ZEPTO_TOKEN'] ?? '';
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
        'to'   => [['email_address' => ['address' => $toEmail, 'name' => $toName]]],
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
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Accept: application/json\r\nContent-Type: application/json\r\nAuthorization: $auth\r\n",
            'content' => json_encode($payload),
            'timeout' => 15,
        ],
    ]);
    $response = @file_get_contents($apiUrl, false, $ctx);
    if ($response === false) {
        return false;
    }
    $data = json_decode($response, true);
    return isset($data['request_id']) || (isset($data['data']) && !isset($data['error']));
}

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
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
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
