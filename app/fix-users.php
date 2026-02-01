<?php
/**
 * Crée la table users manquante
 */
require_once __DIR__ . '/db.php';

if (!$db) {
    die('Connexion DB impossible');
}

try {
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "OK - Table users créée<br>";
} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage() . "<br>";
}

// Vérification
try {
    $db->query("SELECT 1 FROM users LIMIT 1");
    echo "Vérification OK - table users accessible<br>";
} catch (PDOException $e) {
    echo "Vérification échouée: " . $e->getMessage() . "<br>";
}

echo "<hr><a href='login.php'>Aller à la connexion</a>";
