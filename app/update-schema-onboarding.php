<?php
/**
 * Migration: Ajoute les tables et colonnes pour l'onboarding candidat
 */
require_once __DIR__ . '/db.php';

if (!$db) {
    die('Erreur de connexion à la base de données.');
}

$messages = [];

try {
    // ========================================
    // Colonnes à ajouter à la table users
    // ========================================
    $userColumns = [
        'first_name' => "VARCHAR(100) DEFAULT NULL AFTER password_hash",
        'onboarding_step' => "TINYINT DEFAULT 1 AFTER first_name",
        'job_type' => "ENUM('full_time','part_time','shift','temporary','internship') DEFAULT NULL AFTER onboarding_step",
        'work_location' => "ENUM('on_site','remote','hybrid') DEFAULT NULL AFTER job_type",
        'video_url' => "VARCHAR(500) DEFAULT NULL AFTER work_location",
        'photo_url' => "VARCHAR(500) DEFAULT NULL AFTER video_url",
        'available_immediately' => "TINYINT(1) DEFAULT 0 AFTER photo_url",
        'available_in_weeks' => "TINYINT DEFAULT NULL AFTER available_immediately",
        'onboarding_completed' => "TINYINT(1) DEFAULT 0 AFTER available_in_weeks"
    ];

    foreach ($userColumns as $col => $def) {
        $stmt = $db->query("SHOW COLUMNS FROM users LIKE '$col'");
        if ($stmt->rowCount() === 0) {
            $db->exec("ALTER TABLE users ADD COLUMN $col $def");
            $messages[] = "✓ Colonne users.$col ajoutée";
        } else {
            $messages[] = "• Colonne users.$col existe déjà";
        }
    }

    // ========================================
    // Table user_skills
    // ========================================
    $db->exec("CREATE TABLE IF NOT EXISTS user_skills (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        skill_name VARCHAR(100) NOT NULL,
        skill_level ENUM('beginner','intermediate','advanced') DEFAULT 'intermediate',
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY (user_id, skill_name)
    )");
    $messages[] = "✓ Table user_skills prête";

    // ========================================
    // Table user_traits
    // ========================================
    $db->exec("CREATE TABLE IF NOT EXISTS user_traits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        trait_code VARCHAR(50) NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY (user_id, trait_code)
    )");
    $messages[] = "✓ Table user_traits prête";

    // ========================================
    // Table user_availability
    // ========================================
    $db->exec("CREATE TABLE IF NOT EXISTS user_availability (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        slot ENUM('day','evening','night','weekend') NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY (user_id, slot)
    )");
    $messages[] = "✓ Table user_availability prête";

    // ========================================
    // Table user_tests
    // ========================================
    $db->exec("CREATE TABLE IF NOT EXISTS user_tests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        test_type VARCHAR(50) NOT NULL,
        score INT DEFAULT NULL,
        completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY (user_id, test_type)
    )");
    $messages[] = "✓ Table user_tests prête";

    $messages[] = "";
    $messages[] = "Migration terminée avec succès !";

} catch (PDOException $e) {
    $messages[] = "ERREUR: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migration Onboarding - CiaoCV</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #111827; color: #f9fafb; padding: 2rem; }
        .container { max-width: 600px; margin: 0 auto; }
        h1 { color: #2563eb; margin-bottom: 1.5rem; }
        .log { background: #1f2937; padding: 1rem; border-radius: 0.5rem; font-family: monospace; white-space: pre-wrap; line-height: 1.6; }
        .link { display: inline-block; margin-top: 1.5rem; color: #60a5fa; text-decoration: none; }
        .link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Migration Onboarding</h1>
        <div class="log"><?= implode("\n", $messages) ?></div>
        <a href="onboarding/signup.php" class="link">→ Aller à l'inscription</a>
    </div>
</body>
</html>
