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

// ─── Constantes ───────────────────────────────────────────────────────────
define('GESTION_APP_NAME', 'CiaoCV');
// gestion.ciaocv.com → racine du domaine, pas de préfixe /gestion
define('GESTION_BASE_PATH', $_ENV['GESTION_BASE_PATH'] ?? '');
define('GESTION_APP_URL', $_ENV['APP_URL'] ?? 'https://app.ciaocv.com');
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

function gestion_asset(string $path): string
{
    $version = $_ENV['GESTION_ASSET_VERSION'] ?? $_ENV['ASSET_VERSION'] ?? null;
    if ($version === null || $version === '') {
        $filePath = GESTION_BASE . '/' . ltrim($path, '/');
        $version = file_exists($filePath) ? (string) filemtime($filePath) : (string) time();
    }
    return $path . '?v=' . $version;
}
