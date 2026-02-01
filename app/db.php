<?php
/**
 * Connexion DB et chargement .env
 */

// Cache buster - désactiver le cache navigateur
if (!headers_sent()) {
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $value = trim($value, '"\'');
            $_ENV[trim($key)] = $value;
        }
    }
}

// Version des assets pour cache busting (CSS, etc.)
// Optionnel : définir ASSET_VERSION dans .env pour forcer une version (ex. après déploiement)
if (!defined('ASSET_VERSION')) {
    $v = $_ENV['ASSET_VERSION'] ?? null;
    if ($v !== null && $v !== '') {
        define('ASSET_VERSION', (string) $v);
    } else {
        $cssPath = __DIR__ . '/assets/css/design-system.css';
        define('ASSET_VERSION', file_exists($cssPath) ? (string) filemtime($cssPath) : (string) time());
    }
}

$db = null;
try {
    $dsn = 'mysql:host=' . ($_ENV['MYSQL_HOST'] ?? 'localhost') .
           ';dbname=' . ($_ENV['MYSQL_DB'] ?? '') . ';charset=utf8mb4';
    $db = new PDO($dsn, $_ENV['MYSQL_USER'] ?? '', $_ENV['MYSQL_PASS'] ?? '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
}
