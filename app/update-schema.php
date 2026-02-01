<?php
/**
 * Mise à jour du schéma de la table users
 * Ajoute password_hash et email_verified
 */
require_once __DIR__ . '/db.php';

if (!$db) {
    die('Erreur de connexion à la base de données.');
}

$messages = [];

try {
    // Vérifier si la colonne password_hash existe
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'password_hash'");
    if ($stmt->rowCount() === 0) {
        $db->exec("ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) NOT NULL DEFAULT '' AFTER email");
        $messages[] = "✓ Colonne password_hash ajoutée";
    } else {
        $messages[] = "• Colonne password_hash existe déjà";
    }

    // Vérifier si la colonne email_verified existe
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'email_verified'");
    if ($stmt->rowCount() === 0) {
        $db->exec("ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 0 AFTER password_hash");
        $messages[] = "✓ Colonne email_verified ajoutée";
    } else {
        $messages[] = "• Colonne email_verified existe déjà";
    }

    $messages[] = "";
    $messages[] = "Mise à jour terminée !";

} catch (PDOException $e) {
    $messages[] = "ERREUR: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mise à jour du schéma - CiaoCV</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #111827; color: #f9fafb; padding: 2rem; }
        .container { max-width: 600px; margin: 0 auto; }
        h1 { color: #2563eb; margin-bottom: 1.5rem; }
        .log { background: #1f2937; padding: 1rem; border-radius: 0.5rem; font-family: monospace; white-space: pre-wrap; }
        .link { display: inline-block; margin-top: 1.5rem; color: #60a5fa; text-decoration: none; }
        .link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Mise à jour du schéma</h1>
        <div class="log"><?= implode("\n", $messages) ?></div>
        <a href="login.php" class="link">← Retour à la connexion</a>
    </div>
</body>
</html>
