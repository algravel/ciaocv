<?php
header('Content-Type: application/json');
require_once '../db.php';

$q = $_GET['q'] ?? '';
$location = $_GET['location'] ?? '';
$type = $_GET['type'] ?? '';

$sql = "SELECT * FROM jobs WHERE 1=1";
$params = [];

if ($q) {
    $sql .= " AND (title LIKE ? OR company LIKE ? OR description LIKE ?)";
    $term = "%$q%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if ($location) {
    $sql .= " AND location LIKE ?";
    $params[] = "%$location%";
}

if ($type) {
    $sql .= " AND type = ?";
    $params[] = $type;
}

$sql .= " ORDER BY created_at DESC LIMIT 20";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll();
    echo json_encode($jobs);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
