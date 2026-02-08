<?php
/**
 * Synchronise les forfaits avec les tarifs de https://www.ciaocv.com/tarifs
 * Usage: php gestion/sql/sync-forfaits.php (depuis la racine du projet)
 *
 * Remplace tous les forfaits existants par les 4 plans du site vitrine.
 */

$projectRoot = dirname(__DIR__, 2);
$envFile = $projectRoot . '/.env';

if (!file_exists($envFile)) {
    fwrite(STDERR, "Fichier .env introuvable.\n");
    exit(1);
}

$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') continue;
    if (strpos($line, '=') === false) continue;
    list($key, $value) = explode('=', $line, 2);
    $key = trim($key);
    $value = trim($value);
    if (strlen($value) >= 2 && (($value[0] === '"' && $value[strlen($value) - 1] === '"') || ($value[0] === "'" && $value[strlen($value) - 1] === "'"))) {
        $value = substr($value, 1, -1);
    }
    $_ENV[$key] = $value;
}

require_once $projectRoot . '/gestion/config.php';

$pdo = Database::get();

$plans = [
    ['Découverte', 'Discovery', 5, 0, 0],
    ['À la carte', 'Pay per use', 9999, 79, 79],
    ['Pro', 'Pro', 50, 139, 1188],
    ['Expert', 'Expert', 200, 199, 1788],
];

$pdo->beginTransaction();
try {
    $pdo->exec('DELETE FROM gestion_plans');
    $stmt = $pdo->prepare('INSERT INTO gestion_plans (name_fr, name_en, video_limit, price_monthly, price_yearly) VALUES (?, ?, ?, ?, ?)');
    foreach ($plans as $p) {
        $stmt->execute($p);
    }
    $pdo->commit();
    echo "Forfaits synchronisés : Découverte, À la carte, Pro, Expert.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "Erreur : " . $e->getMessage() . "\n");
    exit(1);
}
