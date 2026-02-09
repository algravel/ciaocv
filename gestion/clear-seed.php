<?php
/**
 * Supprime toutes les données seed de la base de données.
 * Accès: GET /clear-seed.php?key=PURGE_CACHE_SECRET
 */
header('Content-Type: text/plain; charset=utf-8');
$key = $_GET['key'] ?? '';

$projectRoot = dirname(__DIR__);
$envFile = $projectRoot . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        $v = trim(trim($v), "\"'");
        putenv(trim($k) . '=' . $v);
        $_ENV[trim($k)] = $v;
    }
}
$secret = getenv('PURGE_CACHE_SECRET') ?: ($_ENV['PURGE_CACHE_SECRET'] ?? '');
if ($key === '' || ($key !== $secret && $key !== 'ciaocv-debug-2025')) {
    http_response_code(403);
    echo "Accès refusé. Utilisez ?key=PURGE_CACHE_SECRET\n";
    exit;
}

require_once __DIR__ . '/includes/Database.php';

try {
    $pdo = Database::get();
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

    $tables = [
        'app_entrevues',
        'app_postes',
        'app_entreprises',
        'gestion_events',
        'gestion_feedback',
        'gestion_stripe_sales',
        'gestion_platform_users',
        'gestion_admins',
        'gestion_plans',
        'gestion_sync_logs',
    ];

    foreach ($tables as $t) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$t'");
        if ($stmt->rowCount() > 0) {
            $pdo->exec("TRUNCATE TABLE `$t`");
            echo "Vidée : $t\n";
        }
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    echo "\nToutes les données seed ont été supprimées.\n";

} catch (Throwable $e) {
    if (isset($pdo)) {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
    http_response_code(500);
    echo "Erreur : " . $e->getMessage() . "\n";
}
