<?php
require_once 'db.php';

try {
    // 1. Création de la table jobs
    $sql = "CREATE TABLE IF NOT EXISTS jobs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        company VARCHAR(255) NOT NULL,
        location VARCHAR(255) NOT NULL,
        type VARCHAR(50) NOT NULL, -- Temps plein, partiel, etc.
        description TEXT,
        salary VARCHAR(100),
        logo_url VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Table 'jobs' créée ou déjà existante.<br>";

    // 2. Vérifier si la table est vide
    $stmt = $pdo->query("SELECT COUNT(*) FROM jobs");
    if ($stmt->fetchColumn() == 0) {
        // 3. Insertion de fausses données
        $jobs = [
            ['Développeur Front-End', 'TechCorp', 'Montréal (Télétravail)', 'Temps plein', 'Nous cherchons un expert React.', '60k - 80k', 'assets/logo.svg'],
            ['Assistant Administratif', 'BureauPlus', 'Québec', 'Temps partiel', 'Gestion des dossiers et accueil.', '22$/h', ''],
            ['Graphiste Junior', 'CreativeStudio', 'Sherbrooke', 'Stage', 'Création de visuels pour réseaux sociaux.', 'Non rémunéré', ''],
            ['Chef de Projet', 'Innovatech', 'Montréal', 'Temps plein', 'Piloter les projets agiles.', '90k+', ''],
            ['Vendeur Conseil', 'ModeShop', 'Laval', 'Temps plein', 'Conseil client en boutique.', '18$/h + comm', '']
        ];

        $insert = $pdo->prepare("INSERT INTO jobs (title, company, location, type, description, salary, logo_url) VALUES (?, ?, ?, ?, ?, ?, ?)");

        foreach ($jobs as $job) {
            $insert->execute($job);
        }
        echo "Données de test insérées.<br>";
    } else {
        echo "La table contient déjà des données.<br>";
    }

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
