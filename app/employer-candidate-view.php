<?php
/**
 * Maquette : voir la réponse vidéo d'un candidat
 */
session_start();
require_once __DIR__ . '/db.php';

$jobId = (int)($_GET['job'] ?? 0);
$idx = (int)($_GET['idx'] ?? 0);
$job = null;
$candidate = null;

if ($jobId && $db) {
    try {
        $stmt = $db->prepare('SELECT * FROM jobs WHERE id = ?');
        $stmt->execute([$jobId]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($job) {
            $stmtA = $db->prepare('SELECT * FROM applications WHERE job_id = ? ORDER BY created_at DESC');
            $stmtA->execute([$jobId]);
            $all = $stmtA->fetchAll(PDO::FETCH_ASSOC);
            $candidate = $all[$idx] ?? null;
        }
    } catch (PDOException $e) {
        $job = null;
    }
}

if (!$job || !$candidate) {
    header('Location: employer-job-view.php?id=' . $jobId);
    exit;
}

$videoUrl = $candidate['video_url'] ?? '';

$currentStatus = $candidate['status'] ?? 'new';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($candidate['candidate_name'] ?? 'Candidat') ?> - CiaoCV</title>
    <link rel="stylesheet" href="assets/css/design-system.css?v=<?= ASSET_VERSION ?>">
</head>
<body>
    <div class="app-shell">
        <?php $sidebarActive = 'list'; include __DIR__ . '/includes/employer-sidebar.php'; ?>
        <main class="app-main">
        <?php include __DIR__ . '/includes/employer-header.php'; ?>
        <div class="app-main-content layout-app layout-app-wide">

        <?php if ($videoUrl !== ''): ?>
        <div class="video-wrapper">
            <video controls playsinline>
                <source src="<?= htmlspecialchars($videoUrl) ?>" type="video/mp4">
                Votre navigateur ne supporte pas la vidéo.
            </video>
        </div>
        <?php else: ?>
        <div class="video-wrapper" style="background:var(--border);border-radius:var(--radius-xl);padding:2rem;text-align:center;color:var(--text-muted);">
            Aucune vidéo pour ce candidat.
        </div>
        <?php endif; ?>

        <div class="candidate-info-card">
            <h1><?= htmlspecialchars($candidate['candidate_name'] ?: 'Sans nom') ?></h1>
            <div class="email"><?= htmlspecialchars($candidate['candidate_email']) ?></div>
            <span class="job-badge"><?= htmlspecialchars($job['title']) ?></span>
        </div>

        <div class="status-section">
            <h3>Statut</h3>
            <div class="status-btns">
                <button type="button" class="status-btn" data-status="new" data-label="NON TRAITÉ">NON TRAITÉ</button>
                <button type="button" class="status-btn" data-status="rejected" data-label="REFUSÉ">REFUSÉ</button>
                <button type="button" class="status-btn" data-status="accepted" data-label="ACCEPTÉ">ACCEPTÉ</button>
                <button type="button" class="status-btn" data-status="pool" data-label="BANQUE">BANQUE</button>
            </div>
        </div>

        <div class="notes-section">
            <h3>Notes internes</h3>
            <textarea id="internalNotes" placeholder="Vos notes privées sur ce candidat (non visibles par le candidat)…"></textarea>
            <p class="hint">Ces notes ne sont visibles que par vous.</p>
            <div class="save-row">
                <button type="button" class="btn-save">Sauvegarder</button>
                <p class="save-feedback" id="saveFeedback" style="display:none;">✓ Enregistré</p>
            </div>
        </div>

        </div>
        </main>
    </div>

    <script>
        (function() {
            const currentStatus = '<?= in_array($currentStatus, ['new','viewed','rejected','accepted']) ? $currentStatus : ($currentStatus === 'pool' ? 'pool' : 'new') ?>';
            const btns = document.querySelectorAll('.status-btn');
            btns.forEach(btn => {
                if (btn.dataset.status === currentStatus || (currentStatus === 'viewed' && btn.dataset.status === 'new')) {
                    btn.classList.add('active');
                }
                btn.addEventListener('click', function() {
                    btns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                });
            });
            document.querySelector('.btn-save').addEventListener('click', function() {
                var f = document.getElementById('saveFeedback');
                f.style.display = '';
                setTimeout(function() { f.style.display = 'none'; }, 2000);
            });
        })();
    </script>
</body>
</html>
