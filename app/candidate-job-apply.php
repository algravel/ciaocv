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
        $stmt = $db->prepare('SELECT * FROM jobs WHERE id = ?');
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
