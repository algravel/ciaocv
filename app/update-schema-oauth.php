<?php
/**
 * Migration: Ajouter les colonnes OAuth à la table users
 * - oauth_provider (google, microsoft, null)
 * - oauth_id (ID unique chez le provider)
 * - password_hash devient nullable pour les utilisateurs OAuth
 */
require_once __DIR__ . '/db.php';

$messages = [];

if (!$db) {
    die('<p style="color:red;">Erreur de connexion à la base de données.</p>');
}

try {
    // Vérifier si les colonnes existent déjà
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'oauth_provider'");
    $hasOAuthProvider = $stmt->fetch();
    
    if (!$hasOAuthProvider) {
        // Ajouter oauth_provider
        $db->exec("ALTER TABLE users ADD COLUMN oauth_provider VARCHAR(50) DEFAULT NULL AFTER email_verified");
        $messages[] = "✅ Colonne 'oauth_provider' ajoutée";
        
        // Ajouter oauth_id
        $db->exec("ALTER TABLE users ADD COLUMN oauth_id VARCHAR(255) DEFAULT NULL AFTER oauth_provider");
        $messages[] = "✅ Colonne 'oauth_id' ajoutée";
        
        // Rendre password_hash nullable (pour les utilisateurs OAuth)
        $db->exec("ALTER TABLE users MODIFY COLUMN password_hash VARCHAR(255) DEFAULT NULL");
        $messages[] = "✅ Colonne 'password_hash' rendue nullable";
        
        // Ajouter un index pour la recherche OAuth
        $db->exec("ALTER TABLE users ADD INDEX idx_oauth (oauth_provider, oauth_id)");
        $messages[] = "✅ Index 'idx_oauth' créé";
    } else {
        $messages[] = "ℹ️ Les colonnes OAuth existent déjà";
    }
} catch (PDOException $e) {
    $messages[] = "❌ Erreur: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Migration OAuth - CiaoCV</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 600px; margin: 40px auto; padding: 20px; background: #0f172a; color: #e2e8f0; }
        h1 { color: #60a5fa; }
        .msg { padding: 10px 15px; margin: 8px 0; border-radius: 8px; background: #1e293b; }
        a { color: #60a5fa; }
    </style>
</head>
<body>
    <h1>Migration OAuth</h1>
    <?php foreach ($messages as $msg): ?>
        <div class="msg"><?= $msg ?></div>
    <?php endforeach; ?>
    <p><a href="index.php">← Retour à la connexion</a></p>
</body>
</html>
