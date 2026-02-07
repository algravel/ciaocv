<?php
/**
 * Configuration autonome de /gestion (administration).
 * Ne dépend pas de l'app principale. Charge le .env du projet.
 */

$gestionBase = __DIR__;
$projectRoot = dirname(dirname($gestionBase));

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
define('GESTION_APP_URL',  $_ENV['APP_URL']  ?? 'https://app.ciaocv.com');
define('GESTION_SITE_URL', $_ENV['SITE_URL'] ?? 'https://www.ciaocv.com');
define('GESTION_BASE',     $gestionBase);
define('GESTION_VIEWS',    $gestionBase . '/views');
define('GESTION_ASSETS',   '../assets');

// ─── Session (isolée : path /gestion, nom distinct) ────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/gestion',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('GESTION_SID');
    session_start();
}

// ─── Helpers ───────────────────────────────────────────────────────────────
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function csrf_token() {
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="_csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify() {
    $token = isset($_POST['_csrf_token']) ? $_POST['_csrf_token'] : '';
    $sessionToken = isset($_SESSION['_csrf_token']) ? $_SESSION['_csrf_token'] : '';
    return $token !== '' && $sessionToken !== '' && hash_equals($sessionToken, $token);
}

function gestion_asset($path) {
    return GESTION_ASSETS . '/' . ltrim($path, '/');
}
