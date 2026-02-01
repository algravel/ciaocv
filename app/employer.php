<?php
session_start();
require_once __DIR__ . '/db.php';

$jobs = [];
$totalCandidates = 0;

if ($db) {
    try {
        $hasDeletedAt = $db->query("SHOW COLUMNS FROM jobs LIKE 'deleted_at'")->rowCount() > 0;
        if ($hasDeletedAt) {
            $stmt = $db->query('SELECT j.*, 
                (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) as nb_candidates 
                FROM jobs j 
                WHERE j.deleted_at IS NULL 
                ORDER BY j.created_at DESC');
        } else {
            $stmt = $db->query('SELECT j.*, 
                (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) as nb_candidates 
                FROM jobs j 
                ORDER BY j.created_at DESC');
        }
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $db->query('SELECT COUNT(*) FROM applications');
        $totalCandidates = (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        $jobs = [];
        $totalCandidates = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Employeur - CiaoCV</title>
    <link rel="stylesheet" href="assets/css/design-system.css?v=<?= ASSET_VERSION ?>">
</head>
<body>
    <div class="app-shell">
        <?php $sidebarActive = 'list'; include __DIR__ . '/includes/employer-sidebar.php'; ?>
        <main class="app-main">
        <?php include __DIR__ . '/includes/employer-header.php'; ?>
        <div class="app-main-content layout-app layout-app-wide">

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-icon">📋</div>
                <div class="stat-card-body">
                    <div class="stat-value"><?= count($jobs) ?></div>
                    <div class="stat-label">Affichages actifs</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon stat-card-icon--green">👥</div>
                <div class="stat-card-body">
                    <div class="stat-value"><?= $totalCandidates ?></div>
                    <div class="stat-label">Candidatures reçues</div>
                </div>
            </div>
        </div>

        <div class="action-bar">
            <h2>Mes affichages</h2>
            <a href="employer-job-create.php" class="btn">+ Créer un poste</a>
        </div>

        <?php if (!empty($jobs)): ?>
        <div class="filter-bar">
            <button type="button" class="filter-btn active" data-filter="all">Tous</button>
            <button type="button" class="filter-btn" data-filter="draft">Brouillon</button>
            <button type="button" class="filter-btn" data-filter="active">En cours</button>
            <button type="button" class="filter-btn" data-filter="closed">Fermé</button>
        </div>
        <?php endif; ?>

        <?php if (empty($jobs)): ?>
            <div class="empty-state">
                <p>Aucun poste pour le moment.</p>
                <a href="employer-job-create.php" class="btn">Créer mon premier poste</a>
            </div>
        <?php else: ?>
            <div class="job-list">
                <?php foreach ($jobs as $job): 
                    $jStatus = $job['status'] ?? 'active';
                    $statusLabels = ['draft' => 'Brouillon', 'active' => 'En cours', 'closed' => 'Fermé'];
                ?>
                    <a href="employer-job-view.php?id=<?= $job['id'] ?>" class="job-card-employer" data-status="<?= htmlspecialchars($jStatus) ?>">
                        <div class="job-info">
                            <h3><?= htmlspecialchars($job['title']) ?></h3>
                            <div class="job-meta"><?= $job['nb_candidates'] ?? 0 ?> candidat(s) · <span class="job-status job-status-<?= $jStatus ?>"><?= $statusLabels[$jStatus] ?? $jStatus ?></span></div>
                        </div>
                        <span class="job-badge">Voir les candidats →</span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        </div>
        </main>
    </div>

    <?php if (!empty($jobs)): ?>
    <script>
        (function(){
            var btns = document.querySelectorAll('.filter-btn');
            var cards = document.querySelectorAll('.job-card-employer[data-status]');
            btns.forEach(function(btn){
                btn.addEventListener('click', function(){
                    btns.forEach(function(b){ b.classList.remove('active'); });
                    this.classList.add('active');
                    var f = this.dataset.filter;
                    cards.forEach(function(c){
                        c.style.display = (f === 'all' || c.dataset.status === f) ? '' : 'none';
                    });
                });
            });
        })();
    </script>
    <?php endif; ?>
</body>
</html>
