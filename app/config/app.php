<?php
/**
 * Configuration de l'application CiaoCV
 * Charge le fichier .env, définit les constantes et démarre la session.
 */

// ─── Chargement du .env ────────────────────────────────────────────────────
$envFile = dirname(__DIR__, 2) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);
        // Retirer les guillemets entourant la valeur (ex: "valeur" ou 'valeur')
        if (strlen($value) >= 2 && (($value[0] === '"' && $value[strlen($value) - 1] === '"') || ($value[0] === "'" && $value[strlen($value) - 1] === "'"))) {
            $value = substr($value, 1, -1);
        }
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
    }
}

// ─── Constantes ────────────────────────────────────────────────────────────
define('APP_NAME',        'CiaoCV');
define('APP_URL',         $_ENV['APP_URL']       ?? 'https://app.ciaocv.com');
define('SITE_URL',        $_ENV['SITE_URL']      ?? 'https://www.ciaocv.com');
define('ASSET_VERSION_FALLBACK', $_ENV['ASSET_VERSION'] ?? null);
define('BASE_PATH',       dirname(__DIR__));
define('VIEWS_PATH',      BASE_PATH . '/views');
define('CONTROLLERS_PATH', BASE_PATH . '/controllers');
define('MODELS_PATH',     BASE_PATH . '/models');

// ─── Session ───────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ─── Helpers ───────────────────────────────────────────────────────────────

/**
 * Génère une URL d'asset avec cache-busting automatique.
 *
 * Utilise filemtime() pour que la version change uniquement quand le
 * fichier est réellement modifié. Le navigateur peut ainsi mettre en
 * cache tant que le fichier ne bouge pas.
 *
 * Priorité : ASSET_VERSION (.env) → filemtime → timestamp courant.
 */
function asset(string $path): string
{
    $filePath = BASE_PATH . '/' . ltrim($path, '/');
    $version  = $_ENV['ASSET_VERSION'] ?? null;

    if ($version === null || $version === '') {
        $version = file_exists($filePath) ? filemtime($filePath) : (ASSET_VERSION_FALLBACK ?? time());
    }

    return $path . '?v=' . $version;
}

/**
 * Échappe une chaîne pour éviter les failles XSS.
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// ─── CSRF Protection ────────────────────────────────────────────────────────

/**
 * Génère ou récupère le token CSRF de la session.
 */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

/**
 * Génère un champ <input> hidden contenant le token CSRF.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Vérifie le token CSRF envoyé en POST.
 * @return bool true si valide
 */
function csrf_verify(): bool
{
    $token = $_POST['_csrf_token']
        ?? $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? '';
    $sessionToken = $_SESSION['_csrf_token'] ?? '';
    if ($token === '' || $sessionToken === '') {
        return false;
    }
    return hash_equals($sessionToken, $token);
}

// ─── Gestion globale des erreurs ────────────────────────────────────────────

/**
 * Handler d'exceptions non interceptées.
 */
set_exception_handler(function (Throwable $e) {
    error_log("[CiaoCV] Exception: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}");

    if (http_response_code() === 200) {
        http_response_code(500);
    }

    // En production, afficher une page d'erreur propre
    $isDev = ($_ENV['APP_ENV'] ?? 'production') === 'development';

    if ($isDev) {
        echo '<h1>Erreur</h1>';
        echo '<pre>' . e($e->getMessage()) . '</pre>';
        echo '<pre>' . e($e->getTraceAsString()) . '</pre>';
    } else {
        echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">';
        echo '<title>Erreur - ' . APP_NAME . '</title>';
        echo '<style>body{font-family:system-ui;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#F3F4F6;color:#1E293B;margin:0;}';
        echo '.box{text-align:center;padding:3rem;background:#fff;border-radius:1rem;box-shadow:0 4px 20px rgba(0,0,0,.08);max-width:500px;}</style></head>';
        echo '<body><div class="box"><h1>500</h1><p>Une erreur interne est survenue.</p>';
        echo '<a href="/" style="color:#2563EB;">Retour à l\'accueil</a></div></body></html>';
    }
    exit;
});

/**
 * Convertit les erreurs PHP en exceptions.
 */
set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});
