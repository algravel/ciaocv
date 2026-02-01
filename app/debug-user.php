<?php
/**
 * Debug - Affiche l'état de l'utilisateur connecté
 */
session_start();
require_once __DIR__ . '/db.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== DEBUG UTILISATEUR ===\n\n";

echo "Session:\n";
echo "- user_id: " . ($_SESSION['user_id'] ?? 'NON DÉFINI') . "\n";
echo "- user_email: " . ($_SESSION['user_email'] ?? 'NON DÉFINI') . "\n";
echo "- user_first_name: " . ($_SESSION['user_first_name'] ?? 'NON DÉFINI') . "\n";
echo "\n";

if (isset($_SESSION['user_id']) && $db) {
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "Base de données (users):\n";
        foreach ($user as $key => $value) {
            if ($key === 'password_hash') {
                echo "- $key: [MASQUÉ]\n";
            } else {
                echo "- $key: " . ($value ?? 'NULL') . "\n";
            }
        }
    } else {
        echo "Utilisateur non trouvé en BD!\n";
    }
} else {
    echo "Non connecté ou pas de connexion BD\n";
}
