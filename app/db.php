<?php
/**
 * Connexion DB et chargement .env
 */
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

$db = null;
try {
    $dsn = 'mysql:host=' . ($_ENV['MYSQL_HOST'] ?? 'localhost') .
           ';dbname=' . ($_ENV['MYSQL_DB'] ?? '') . ';charset=utf8mb4';
    $db = new PDO($dsn, $_ENV['MYSQL_USER'] ?? '', $_ENV['MYSQL_PASS'] ?? '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
}
