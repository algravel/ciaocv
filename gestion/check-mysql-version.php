<?php
/**
 * Vérifie la version MySQL du serveur.
 * Usage: php scripts/check-mysql-version.php
 *
 * MySQL 5.7.8+ requis pour le type JSON natif.
 * Le projet utilise TEXT pour les colonnes JSON (compatibilité 5.5+).
 */
$envFile = dirname(__DIR__) . '/.env';
if (!file_exists($envFile)) {
    echo "Fichier .env introuvable.\n";
    exit(1);
}
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$env = [];
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') continue;
    if (strpos($line, '=') === false) continue;
    [$k, $v] = explode('=', $line, 2);
    $v = trim($v, " \t\n\r\"'");
    $env[trim($k)] = $v;
}
$host = $env['MYSQL_HOST'] ?? 'localhost';
$user = $env['MYSQL_USER'] ?? '';
$pass = $env['MYSQL_PASS'] ?? '';
$db   = $env['MYSQL_DB'] ?? '';

if (!$user || !$db) {
    echo "MYSQL_USER et MYSQL_DB requis dans .env\n";
    exit(1);
}

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $ver = $pdo->query('SELECT VERSION()')->fetchColumn();
    echo "MySQL version: $ver\n";

    $parts = explode('.', preg_replace('/[^0-9.].*$/', '', $ver));
    $major = (int) ($parts[0] ?? 0);
    $minor = (int) ($parts[1] ?? 0);
    $patch = (int) ($parts[2] ?? 0);
    $jsonOk = ($major > 5) || ($major == 5 && $minor > 7) || ($major == 5 && $minor == 7 && $patch >= 8);
    echo "Type JSON natif: " . ($jsonOk ? "OUI (5.7.8+)" : "NON (requis 5.7.8+)") . "\n";
    echo "Le projet utilise TEXT pour compatibilité MySQL 5.5+.\n";
} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
