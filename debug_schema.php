<?php
require_once __DIR__ . '/gestion/config.php';
$pdo = Database::get();

$result = [];

// 1. Get a valid Platform User ID
try {
    $stmt = $pdo->query("SELECT id FROM gestion_users WHERE role='client_admin' LIMIT 1");
    // Or try to find any platform user linked to an affichage
    if (!$stmt->rowCount()) {
        $stmt = $pdo->query("SELECT DISTINCT platform_user_id FROM app_affichages LIMIT 1");
    }
    $platformUserId = $stmt->fetchColumn() ?: 1;
    $result['tested_platform_user_id'] = $platformUserId;
} catch (Throwable $e) {
    $result['user_id_error'] = $e->getMessage();
    $platformUserId = 1;
}

// 2. Test Chart Query
try {
    $sql = "
            SELECT DATE_FORMAT(c.created_at, '%Y-%m') as ym, COUNT(*) as cnt
            FROM app_candidatures c
            JOIN app_affichages a ON a.id = c.affichage_id
            WHERE a.platform_user_id = ? AND c.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY ym
            ORDER BY ym ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$platformUserId]);
    $result['chart_data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $result['chart_error'] = $e->getMessage();
    $result['chart_sql'] = $sql;
}

// 3. Check Events Schema
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM gestion_events");
    $result['events_columns'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    $result['events_schema_error'] = $e->getMessage();
}

// 4. Check if logForPlatformUser is called? -> Source code check done previously (0 results).

header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);
