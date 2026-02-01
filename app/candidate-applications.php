<?php
/**
 * Maquette : candidatures du candidat (postes où il a postulé)
 */
session_start();
require_once __DIR__ . '/db.php';
$demo = include __DIR__ . '/demo-data.php';
$applications = $demo['my_applications'] ?? [];
$statusLabels = ['active' => 'En cours', 'draft' => 'Brouillon', 'closed' => 'Terminé'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes candidatures - CiaoCV</title>
    <link rel="stylesheet" href="assets/css/design-system.css?v=<?= ASSET_VERSION ?>">
</head>
<body>
    <div class="app-shell">
        <?php $sidebarActive = 'applications'; include __DIR__ . '/includes/candidate-sidebar.php'; ?>
        <main class="app-main">
        <?php include __DIR__ . '/includes/candidate-header.php'; ?>
        <div class="app-main-content layout-app">

        <h2 style="margin-bottom:1.5rem;font-size:1.25rem;">Mes candidatures</h2>

        <?php if (empty($applications)): ?>
            <div class="empty-state">
                <p>Aucune candidature pour le moment.</p>
                <a href="candidate-jobs.php" style="color:var(--primary);">Voir les offres</a>
            </div>
        <?php else: ?>
            <?php foreach ($applications as $app): ?>
                <?php 
                $jobStatus = $app['job_status'] ?? 'active';
                $jobLabel = $statusLabels[$jobStatus] ?? $jobStatus;
                ?>
                <a href="candidate-job-apply.php?id=<?= $app['job_id'] ?>" class="app-card">
                    <h3><?= htmlspecialchars($app['job_title']) ?></h3>
                    <div class="app-meta">
                        <span class="badge badge-<?= $jobStatus ?>"><?= $jobLabel ?></span>
                        <?php if ($jobStatus === 'active'): ?>
                            <span>— Affichage ouvert</span>
                        <?php elseif ($jobStatus === 'closed'): ?>
                            <span>— Affichage terminé</span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>

        <div style="margin-top:1.5rem;">
            <a href="candidate-jobs.php" class="btn" style="display:inline-block;padding:0.75rem 1.5rem;background:var(--primary);color:white;border-radius:0.5rem;text-decoration:none;font-weight:600;">Voir toutes les offres</a>
        </div>

        </div>
        </main>
    </div>
</body>
</html>
