<?php
/**
 * Maquette : liste des offres pour les candidats
 */
session_start();
require_once __DIR__ . '/db.php';

$jobs = [];

if ($db) {
    try {
        $cols = $db->query("SHOW COLUMNS FROM jobs")->fetchAll(PDO::FETCH_COLUMN);
        $hasStatus = in_array('status', $cols);
        $hasDeletedAt = in_array('deleted_at', $cols);
        $hasJobMarket = in_array('show_on_jobmarket', $cols);
        $sql = 'SELECT j.* FROM jobs j WHERE 1=1';
        if ($hasStatus) {
            $sql .= " AND j.status = 'active'";
        }
        if ($hasDeletedAt) {
            $sql .= " AND j.deleted_at IS NULL";
        }
        if ($hasJobMarket) {
            $sql .= " AND j.show_on_jobmarket = 1";
        }
        $sql .= ' ORDER BY j.created_at DESC';
        $stmt = $db->query($sql);
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $jobs = [];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JobMarket - CiaoCV</title>
    <link rel="stylesheet" href="assets/css/design-system.css?v=<?= ASSET_VERSION ?>">
</head>
<body>
    <div class="app-shell">
        <?php $sidebarActive = 'jobs'; include __DIR__ . '/includes/candidate-sidebar.php'; ?>
        <main class="app-main">
        <?php include __DIR__ . '/includes/candidate-header.php'; ?>
        <div class="app-main-content layout-app">

        <h2 style="margin-bottom:1.5rem;font-size:1.25rem;">JobMarket</h2>
        <p style="color:var(--text-secondary);font-size:0.9rem;margin-bottom:1.5rem;">Les offres d'emploi affichées par les employeurs.</p>

        <?php if (empty($jobs)): ?>
            <p style="color:var(--text-light);text-align:center;padding:2rem;">Aucune offre pour le moment.</p>
        <?php else: ?>
            <div class="job-list">
                <?php foreach ($jobs as $job): 
                    $jStatus = $job['status'] ?? 'active';
                    $statusLabel = $jStatus === 'active' ? 'En cours' : ($jStatus === 'closed' ? 'Terminé' : 'Brouillon');
                ?>
                    <a href="candidate-job-apply.php?id=<?= $job['id'] ?>" class="job-card">
                        <h3><?= htmlspecialchars($job['title']) ?></h3>
                        <?php if (!empty($job['description'])): ?>
                            <div class="desc"><?= htmlspecialchars($job['description']) ?></div>
                        <?php endif; ?>
                        <div class="job-meta">
                            <span class="job-status-badge <?= $jStatus ?>"><?= $statusLabel ?></span>
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
