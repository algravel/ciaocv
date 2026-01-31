<?php
/**
 * Maquette : candidatures du candidat (postes où il a postulé)
 */
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
    <style>
        :root { --primary: #2563eb; --primary-dark: #1e40af; --bg: #111827; --card-bg: #1f2937; --text: #f9fafb; --text-light: #9ca3af; --border: #374151; --success: #16a34a; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: system-ui, sans-serif; }
        body { background: var(--bg); color: var(--text); min-height: 100vh; }
        .container { max-width: 600px; margin: 0 auto; padding: 1.5rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .logo { font-size: 1.25rem; font-weight: 800; color: var(--primary); text-decoration: none; }
        .back { color: var(--text-light); text-decoration: none; }
        .back:hover { color: var(--primary); }
        .app-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 1rem; padding: 1.5rem; margin-bottom: 1rem; text-decoration: none; color: inherit; display: block; transition: all 0.2s; }
        .app-card:hover { border-color: var(--primary); }
        .app-card h3 { font-size: 1.1rem; margin-bottom: 0.5rem; }
        .app-meta { display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: var(--text-light); }
        .badge { padding: 0.2rem 0.6rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 600; }
        .badge-active { background: rgba(22,163,74,0.2); color: #4ade80; }
        .badge-closed { background: #374151; color: var(--text-light); }
        .badge-draft { background: rgba(251,191,36,0.2); color: #fbbf24; }
        .empty-state { text-align: center; padding: 3rem 2rem; color: var(--text-light); }
        .empty-state p { margin-bottom: 1rem; }
        .footer { margin-top: 2rem; text-align: center; font-size: 0.8rem; color: var(--text-light); }
        .footer a { color: var(--primary); }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <a href="candidate.php" class="logo">CiaoCV</a>
            <a href="candidate.php" class="back">← Espace candidat</a>
        </header>

        <div style="background:rgba(37,99,235,0.2);color:var(--primary);padding:0.75rem 1rem;border-radius:8px;margin-bottom:1.5rem;font-size:0.9rem;">Mode démo.</div>

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

        <div class="footer" style="margin-top:2rem;">
            <a href="https://www.ciaocv.com">Retour au site principal</a>
        </div>
    </div>
</body>
</html>
