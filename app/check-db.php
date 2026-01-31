<?php
/**
 * Diagnostic DB – à supprimer après résolution
 */
header('Content-Type: text/html; charset=utf-8');

$envPath = dirname(__DIR__) . '/.env';
$envExists = file_exists($envPath);
$envReadable = $envExists && is_readable($envPath);

$_ENV = [];
if ($envReadable) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, '"\'');
        }
    }
}

$db = null;
$dbError = null;
try {
    $dsn = 'mysql:host=' . ($_ENV['MYSQL_HOST'] ?? 'localhost') .
           ';dbname=' . ($_ENV['MYSQL_DB'] ?? '') . ';charset=utf8mb4';
    $db = new PDO($dsn, $_ENV['MYSQL_USER'] ?? '', $_ENV['MYSQL_PASS'] ?? '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $dbError = $e->getMessage();
}

$tables = [];
if ($db) {
    try {
        $stmt = $db->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $dbError = $dbError ?? $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Diagnostic DB - CiaoCV</title>
    <style>
        body { font-family: sans-serif; max-width: 600px; margin: 2rem auto; padding: 1rem; }
        .ok { color: green; }
        .err { color: red; }
        pre { background: #f5f5f5; padding: 1rem; overflow-x: auto; }
        h2 { margin-top: 1.5rem; }
    </style>
</head>
<body>
    <h1>Diagnostic base de données</h1>

    <h2>Fichier .env</h2>
    <p>Chemin recherché : <code><?= htmlspecialchars($envPath) ?></code></p>
    <p>Existe : <?= $envExists ? '<span class="ok">Oui</span>' : '<span class="err">Non</span>' ?></p>
    <p>Lisible : <?= $envReadable ? '<span class="ok">Oui</span>' : '<span class="err">Non</span>' ?></p>
    <?php if ($envReadable): ?>
    <p>MYSQL_DB = <?= htmlspecialchars($_ENV['MYSQL_DB'] ?? '(vide)') ?></p>
    <?php endif; ?>

    <h2>Connexion MySQL</h2>
    <?php if ($db): ?>
        <p class="ok">Connexion réussie.</p>
    <?php else: ?>
        <p class="err">Échec : <?= htmlspecialchars($dbError ?? 'inconnu') ?></p>
    <?php endif; ?>

    <h2>Tables</h2>
    <?php if ($db): ?>
        <p>Tables trouvées : <?= count($tables) ?></p>
        <?php if (!empty($tables)): ?>
            <pre><?= htmlspecialchars(implode("\n", $tables)) ?></pre>
        <?php endif; ?>
        <?php $required = ['jobs', 'job_questions', 'applications']; ?>
        <?php $missing = array_diff($required, $tables); ?>
        <?php if (!empty($missing)): ?>
            <p class="err">Tables manquantes : <?= implode(', ', $missing) ?></p>
        <?php endif; ?>
    <?php endif; ?>

    <p style="margin-top: 2rem;"><a href="employer.php">Retour employeur</a> | <a href="install.php">Réinstaller</a></p>
</body>
</html>
