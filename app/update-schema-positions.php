<?php
/**
 * Crée la table employer_positions si elle n'existe pas.
 */
require_once __DIR__ . '/db.php';

if (!$db) {
    die('Erreur de connexion à la base de données.');
}

$messages = [];
try {
    $stmt = $db->query("SHOW TABLES LIKE 'employer_positions'");
    if ($stmt->rowCount() === 0) {
        $db->exec("
            CREATE TABLE employer_positions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                employer_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT DEFAULT NULL,
                job_type ENUM('full_time','part_time','shift','temporary','internship') DEFAULT NULL,
                work_location ENUM('on_site','remote','hybrid') DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_employer (employer_id)
            )
        ");
        $messages[] = "✓ Table employer_positions créée.";
    } else {
        $messages[] = "• Table employer_positions existe déjà.";
    }
    $messages[] = "";
    $messages[] = "Mise à jour terminée.";
} catch (PDOException $e) {
    $messages[] = "ERREUR: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schéma - Mes postes - CiaoCV</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #111827; color: #f9fafb; padding: 2rem; }
        .container { max-width: 600px; margin: 0 auto; }
        h1 { color: #2563eb; margin-bottom: 1.5rem; }
        .log { background: #1f2937; padding: 1rem; border-radius: 0.5rem; font-family: monospace; white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Schéma – employer_positions</h1>
        <pre class="log"><?= htmlspecialchars(implode("\n", $messages)) ?></pre>
        <p><a href="employer.php" style="color: #60a5fa;">← Retour employeur</a></p>
    </div>
</body>
</html>
