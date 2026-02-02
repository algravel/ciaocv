<?php
session_start();
require_once __DIR__ . '/db.php';

$jobs = [];
$totalCandidates = 0;
$employerProfile = null;

if ($db) {
    if (isset($_SESSION['user_id'])) {
        $userId = (int) $_SESSION['user_id'];
        $cols = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        $employerCols = ['company_name', 'company_logo_url'];
        $needCols = array_diff($employerCols, $cols);
        if (!empty($needCols)) {
            if (in_array('company_name', $needCols)) {
                @$db->exec("ALTER TABLE users ADD COLUMN company_name VARCHAR(255) DEFAULT NULL");
                @$db->exec("ALTER TABLE users ADD COLUMN company_description TEXT DEFAULT NULL");
                @$db->exec("ALTER TABLE users ADD COLUMN company_description_visible TINYINT(1) DEFAULT 1");
                @$db->exec("ALTER TABLE users ADD COLUMN company_video_url VARCHAR(500) DEFAULT NULL");
                @$db->exec("ALTER TABLE users ADD COLUMN company_logo_url VARCHAR(500) DEFAULT NULL");
            }
        }
        $stmt = $db->prepare('SELECT company_name, company_logo_url FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $employerProfile = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    try {
        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
        $hasEmployerId = $db->query("SHOW COLUMNS FROM jobs LIKE 'employer_id'")->rowCount() > 0;
        $hasDeletedAt = $db->query("SHOW COLUMNS FROM jobs LIKE 'deleted_at'")->rowCount() > 0;
        $where = $hasEmployerId && $userId ? 'j.employer_id = ' . (int)$userId : '1';
        if ($hasDeletedAt) $where .= ' AND j.deleted_at IS NULL';
        $stmt = $db->query("SELECT j.*, 
            (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) as nb_candidates 
            FROM jobs j 
            WHERE $where 
            ORDER BY j.created_at DESC");
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totalCandidates = 0;
        foreach ($jobs as $j) $totalCandidates += (int)($j['nb_candidates'] ?? 0);
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

        <?php if ($employerProfile): ?>
        <div class="employer-dashboard-profile">
            <a href="employer-profile.php" class="employer-dashboard-profile-link">
                <div class="employer-dashboard-logo">
                    <?php if (!empty($employerProfile['company_logo_url'])): ?>
                        <img src="<?= htmlspecialchars($employerProfile['company_logo_url']) ?>" alt="">
                    <?php else: ?>
                        <span>🏢</span>
                    <?php endif; ?>
                </div>
                <div class="employer-dashboard-info">
                    <strong><?= htmlspecialchars($employerProfile['company_name'] ?: 'Mon entreprise') ?></strong>
                    <span>Modifier le profil entreprise →</span>
                </div>
            </a>
        </div>
        <?php endif; ?>

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
