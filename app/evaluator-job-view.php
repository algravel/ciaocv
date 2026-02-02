<?php
/**
 * Liste des candidats à évaluer (accès évaluateur après confirmation par code).
 */
session_start();
require_once __DIR__ . '/db.php';

$jobId = (int)($_GET['job'] ?? 0);
$job = null;
$candidates = [];

if (!isset($_SESSION['evaluator_token'], $_SESSION['evaluator_email'], $_SESSION['evaluator_job_id']) || $_SESSION['evaluator_job_id'] !== $jobId) {
    header('Location: index.php');
    exit;
}

if ($jobId && $db) {
    $stmt = $db->prepare('SELECT * FROM jobs WHERE id = ?');
    $stmt->execute([$jobId]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$job) {
        header('Location: index.php');
        exit;
    }
    $stmtEval = $db->prepare('SELECT 1 FROM job_evaluators WHERE job_id = ? AND email = ?');
    $stmtEval->execute([$jobId, $_SESSION['evaluator_email']]);
    if (!$stmtEval->fetch()) {
        header('Location: index.php');
        exit;
    }
    $stmtA = $db->prepare('
        SELECT a.*, u.photo_url, u.video_url AS bio_video_url
        FROM applications a
        LEFT JOIN users u ON u.email = a.candidate_email
        WHERE a.job_id = ?
        ORDER BY a.created_at DESC
    ');
    $stmtA->execute([$jobId]);
    $candidates = $stmtA->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidats – <?= htmlspecialchars($job['title']) ?> - CiaoCV</title>
    <link rel="stylesheet" href="assets/css/design-system.css?v=<?= defined('ASSET_VERSION') ? ASSET_VERSION : time() ?>">
    <script src="assets/js/local-time.js?v=<?= ASSET_VERSION ?>"></script>
</head>
<body>
    <div class="app-shell">
        <main class="app-main">
            <header class="app-header" style="display:flex;">
                <div class="app-header-left" style="flex:1;">
                    <span style="font-weight:600;">Espace évaluateur</span>
                </div>
                <div class="app-header-right" style="font-size:0.9rem;color:var(--text-secondary);">
                    <?= htmlspecialchars($_SESSION['evaluator_name'] ?? $_SESSION['evaluator_email']) ?>
                    <a href="evaluator-logout.php" style="margin-left:0.75rem;color:var(--text-muted);text-decoration:none;">Déconnexion</a>
                </div>
            </header>
            <div class="app-main-content layout-app layout-app-wide" style="padding-top:1rem;">

                <h1 style="margin:0 0 0.5rem 0;"><?= htmlspecialchars($job['title']) ?></h1>
                <p class="hint" style="margin:0 0 1.5rem 0;">Candidats à évaluer</p>

                <?php if (empty($candidates)): ?>
                    <div class="empty-state">
                        <p>Aucun candidat pour le moment.</p>
                    </div>
                <?php else: ?>
                    <div class="candidate-grid" id="candidateGrid">
                        <?php foreach ($candidates as $i => $c):
                            $cStatus = $c['status'] ?? 'new';
                            if ($cStatus === 'viewed') $cStatus = 'new';
                            $statusLabels = ['new' => 'NON TRAITÉ', 'viewed' => 'NON TRAITÉ', 'rejected' => 'REFUSÉ', 'accepted' => 'ACCEPTÉ', 'pool' => 'BANQUE', 'withdrawn' => 'RETIRÉ'];
                            $statusLabel = $statusLabels[$c['status'] ?? 'new'] ?? 'NON TRAITÉ';
                            $photoUrl = !empty($c['photo_url']) ? trim($c['photo_url']) : null;
                            $bioVideoUrl = !empty($c['bio_video_url']) ? trim($c['bio_video_url']) : null;
                            $createdAtRaw = trim($c['created_at'] ?? '');
                            $createdAtIso = $createdAtRaw !== '' ? preg_replace('/\s/', 'T', $createdAtRaw) . 'Z' : '';
                            $createdAtFallback = $createdAtRaw !== '' ? date('d/m/Y H:i', strtotime($createdAtRaw)) : '';
                            $phone = isset($c['phone']) ? trim($c['phone']) : '';
                        ?>
                            <a href="evaluator-candidate-view.php?job=<?= $jobId ?>&idx=<?= $i ?>" class="candidate-grid-card candidate-with-notes" data-status="<?= htmlspecialchars($cStatus) ?>">
                                <div class="candidate-grid-photo">
                                    <?php if ($photoUrl): ?>
                                        <img src="<?= htmlspecialchars($photoUrl) ?>" alt="" class="candidate-grid-img">
                                    <?php else: ?>
                                        <span class="candidate-grid-initial"><?= strtoupper(mb_substr($c['candidate_name'] ?: '?', 0, 1)) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="candidate-grid-body">
                                    <div class="candidate-grid-name"><?= htmlspecialchars($c['candidate_name'] ?: 'Sans nom') ?></div>
                                    <?php if ($bioVideoUrl): ?>
                                        <a href="<?= htmlspecialchars($bioVideoUrl) ?>" class="candidate-grid-video-link" target="_blank" rel="noopener" onclick="event.stopPropagation();">🎬 Vidéo bio</a>
                                    <?php endif; ?>
                                    <span class="candidate-grid-status tag-status tag-<?= htmlspecialchars($c['status'] ?? 'new') ?>"><?= $statusLabel ?></span>
                                    <?php if ($createdAtIso !== ''): ?>
                                        <div class="candidate-grid-date">Reçu le <time datetime="<?= htmlspecialchars($createdAtIso) ?>"><?= $createdAtFallback ?></time></div>
                                    <?php endif; ?>
                                    <?php if ($phone): ?>
                                        <div class="candidate-grid-phone"><?= htmlspecialchars($phone) ?></div>
                                    <?php endif; ?>
                                    <div class="candidate-grid-email"><?= htmlspecialchars($c['candidate_email']) ?></div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>
        </main>
    </div>
</body>
</html>
