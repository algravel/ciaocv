<?php
/**
 * Migration: Table pour les tokens de réinitialisation de mot de passe
 */
require_once __DIR__ . '/db.php';

if (!$db) {
    die('Erreur de connexion à la base de données.');
}

$messages = [];

try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(64) NOT NULL UNIQUE,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_token_expires (token, expires_at)
        )
    ");
    $messages[] = "✓ Table password_reset_tokens créée ou déjà existante";
} catch (PDOException $e) {
    $messages[] = "Erreur: " . $e->getMessage();
}

header('Content-Type: text/html; charset=utf-8');
echo '<pre>' . implode("\n", $messages) . '</pre>';
echo '<p><a href="index.php">Retour à l\'accueil</a></p>';
