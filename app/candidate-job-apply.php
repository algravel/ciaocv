<?php
/**
 * Maquette : postuler à une offre (voir détails + lien vers enregistrement vidéo)
 */
session_start();
require_once __DIR__ . '/db.php';

$jobId = (int)($_GET['id'] ?? 0);
$job = null;

if ($jobId && $db) {
    try {
        $hasDeletedAt = $db->query("SHOW COLUMNS FROM jobs LIKE 'deleted_at'")->rowCount() > 0;
        $stmt = $hasDeletedAt
            ? $db->prepare('SELECT * FROM jobs WHERE id = ? AND deleted_at IS NULL')
            : $db->prepare('SELECT * FROM jobs WHERE id = ?');
        $stmt->execute([$jobId]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($job) {
            $stmtQ = $db->prepare('SELECT * FROM job_questions WHERE job_id = ? ORDER BY sort_order');
            $stmtQ->execute([$jobId]);
            $job['questions'] = $stmtQ->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $job = null;
    }
}

if (!$job) {
    header('Location: candidate-jobs.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($job['title']) ?> - CiaoCV</title>
    <link rel="stylesheet" href="assets/css/design-system.css?v=<?= ASSET_VERSION ?>">
</head>
<body>
    <div class="app-shell">
        <?php $sidebarActive = 'jobs'; include __DIR__ . '/includes/candidate-sidebar.php'; ?>
        <main class="app-main">
        <?php include __DIR__ . '/includes/candidate-header.php'; ?>
        <div class="app-main-content layout-app">
        <div class="job-card">
            <h1><?= htmlspecialchars($job['title']) ?></h1>
            <?php
            $jobLat = isset($job['latitude']) && $job['latitude'] !== '' && $job['latitude'] !== null ? (float)$job['latitude'] : null;
            $jobLng = isset($job['longitude']) && $job['longitude'] !== '' && $job['longitude'] !== null ? (float)$job['longitude'] : null;
            $hasLocation = (!empty($job['location_name']) || ($jobLat !== null && $jobLng !== null));
            if ($hasLocation): ?>
            <div class="job-apply-location" style="margin-bottom:1rem;padding:0.75rem 1rem;background:var(--bg-alt, #f3f4f6);border-radius:8px;font-size:0.9rem;">
                <strong>📍 Lieu du poste</strong>
                <?php if (!empty($job['location_name'])): ?>
                    <div style="margin-top:0.25rem;color:var(--text-secondary, #6B7280);"><?= htmlspecialchars($job['location_name']) ?></div>
                <?php endif; ?>
                <?php if ($jobLat !== null && $jobLng !== null): ?>
                    <div style="margin-top:0.25rem;color:var(--text-secondary, #6B7280);font-size:0.85rem;"><?= number_format($jobLat, 5) ?>, <?= number_format($jobLng, 5) ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($job['description'])): ?>
                <div class="desc"><?= nl2br(htmlspecialchars($job['description'])) ?></div>
            <?php endif; ?>
            <?php if (!empty($job['questions'])): ?>
                <div class="questions-title">Questions pour votre pitch vidéo :</div>
                <ol class="questions-list">
                    <?php foreach ($job['questions'] as $q): ?>
                        <li><?= htmlspecialchars($q['question_text']) ?></li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>

        <a href="record.php" class="btn">🎬 Enregistrer mon pitch vidéo</a>

        </div>
        </main>
    </div>
</body>
</html>
