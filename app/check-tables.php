<?php
/**
 * Diagnostic : vérifier les tables existantes
 */
require_once __DIR__ . '/db.php';

if (!$db) {
    die('Connexion DB impossible');
}

echo "<h2>Tables existantes</h2>";
$stmt = $db->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "<pre>" . implode("\n", $tables) . "</pre>";

echo "<h2>Test table users</h2>";
try {
    $db->query("SELECT 1 FROM users LIMIT 1");
    echo "OK - table users existe<br>";
} catch (PDOException $e) {
    echo "ERREUR - " . $e->getMessage() . "<br>";
}

echo "<h2>Test table email_confirmations</h2>";
try {
    $db->query("SELECT 1 FROM email_confirmations LIMIT 1");
    echo "OK - table email_confirmations existe<br>";
} catch (PDOException $e) {
    echo "ERREUR - " . $e->getMessage() . "<br>";
}

echo "<hr><a href='login.php'>Retour login</a> | <a href='install.php'>Réinstaller</a>";
