<?php
// Charger les variables d'environnement (si pas déjà fait par le serveur, on fait un parseur simple)
function loadEnv($path) {
    if (!file_exists($path)) return false;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && substr($line, 0, 1) !== '#') {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'"); // Enlever les quotes
            $_ENV[$key] = $value;
        }
    }
    return true;
}

// Tenter de charger .env depuis plusieurs emplacements possibles
$paths = [
    __DIR__ . '/../../.env', // Production : public_html/app -> root
    __DIR__ . '/../.env',    // Local ou autre structure
    __DIR__ . '/.env'
];

$envLoaded = false;
foreach ($paths as $path) {
    if (loadEnv($path)) {
        $envLoaded = true;
        break;
    }
}

if (!$envLoaded) {
    // Fallback : si getenv marche (variables serveur)
    if (!getenv('MYSQL_HOST')) {
        // En prod, on ne veut pas afficher le chemin complet par sécurité, mais pour le debug ici c'est utile
        die("Erreur : Fichier .env introuvable. Chemins testés : " . implode(', ', $paths));
    }
}

$host = $_ENV['MYSQL_HOST'] ?? 'localhost';
$user = $_ENV['MYSQL_USER'] ?? 'root';
$pass = $_ENV['MYSQL_PASS'] ?? '';
$dbname = $_ENV['MYSQL_DB'] ?? 'ciaocv';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
