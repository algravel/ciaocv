<?php
/**
 * Supprime tous les utilisateurs (et leurs confirmations via CASCADE)
 */
require_once __DIR__ . '/db.php';

if (!$db) {
    die('Erreur de connexion à la base de données.');
}

try {
    $db->exec('DELETE FROM users');
    $count = $db->query('SELECT ROW_COUNT()')->fetchColumn();
    echo "✓ Tous les utilisateurs supprimés.<br><br>";
    echo "<a href='login.php'>← Retour à la connexion</a>";
} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage();
}
