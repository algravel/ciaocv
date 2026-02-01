<?php
/**
 * Mise à jour du schéma - Ajout colonne status à jobs
 */
require_once __DIR__ . '/db.php';

if (!$db) {
    die('Erreur: Connexion DB impossible');
}

$messages = [];

try {
    // Vérifier si la table jobs existe
    $stmt = $db->query("SHOW TABLES LIKE 'jobs'");
    if ($stmt->rowCount() === 0) {
        // Créer la table jobs
        $db->exec("CREATE TABLE IF NOT EXISTS jobs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employer_id INT DEFAULT 1,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            status ENUM('draft','active','closed') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $messages[] = "Table 'jobs' créée.";
    } else {
        // Vérifier si la colonne status existe
        $stmt = $db->query("SHOW COLUMNS FROM jobs LIKE 'status'");
        if ($stmt->rowCount() === 0) {
            $db->exec("ALTER TABLE jobs ADD COLUMN status ENUM('draft','active','closed') DEFAULT 'active' AFTER description");
            $messages[] = "Colonne 'status' ajoutée à la table 'jobs'.";
        } else {
            $messages[] = "Colonne 'status' existe déjà.";
        }
    }

    // Créer la table job_questions si elle n'existe pas
    $db->exec("CREATE TABLE IF NOT EXISTS job_questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        job_id INT NOT NULL,
        question_text VARCHAR(500) NOT NULL,
        sort_order TINYINT NOT NULL DEFAULT 1,
        FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
        UNIQUE KEY (job_id, sort_order)
    )");
    $messages[] = "Table 'job_questions' vérifiée/créée.";

    // Créer la table applications si elle n'existe pas
    $db->exec("CREATE TABLE IF NOT EXISTS applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        job_id INT NOT NULL,
        candidate_email VARCHAR(255) NOT NULL,
        candidate_name VARCHAR(255),
        video_url VARCHAR(500),
        status ENUM('new','viewed','accepted','rejected','pool') DEFAULT 'new',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
    )");
    $messages[] = "Table 'applications' vérifiée/créée.";

    echo "<h2>Mise à jour terminée</h2>";
    echo "<ul>";
    foreach ($messages as $msg) {
        echo "<li>$msg</li>";
    }
    echo "</ul>";
    echo "<p><a href='employer.php'>Retour à l'espace employeur</a></p>";

} catch (PDOException $e) {
    echo "<h2>Erreur</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
