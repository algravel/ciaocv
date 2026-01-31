<?php
/**
 * Installe les tables MySQL (à exécuter une seule fois)
 * Supprimer ou protéger ce fichier en production.
 */
require_once __DIR__ . '/db.php';

if (!$db) {
    die('Impossible de se connecter à la base de données. Vérifiez .env');
}

$sql = file_get_contents(__DIR__ . '/schema.sql');
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $stmt) {
    if (empty($stmt) || strpos($stmt, '--') === 0) continue;
    try {
        $db->exec($stmt);
        echo "OK: " . substr($stmt, 0, 50) . "...<br>";
    } catch (PDOException $e) {
        echo "Erreur: " . $e->getMessage() . "<br>";
    }
}

echo "<br><strong>Installation terminée.</strong> <a href='employer.php'>Aller à l'espace employeur</a>";
