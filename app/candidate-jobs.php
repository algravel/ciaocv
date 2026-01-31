<?php
/**
 * Maquette : liste des offres pour les candidats
 */
require_once __DIR__ . '/db.php';

$jobs = [];
$demoMode = false;

if ($db) {
    try {
        $stmt = $db->query('SELECT j.*, 
            (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) as nb_candidates 
            FROM jobs j ORDER BY j.created_at DESC');
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $demo = include __DIR__ . '/demo-data.php';
        $jobs = $demo['jobs'];
        $demoMode = true;
    }
} else {
    $demo = include __DIR__ . '/demo-data.php';
    $jobs = $demo['jobs'];
    $demoMode = true;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offres d'emploi - CiaoCV</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --bg: #111827;
            --card-bg: #1f2937;
            --text: #f9fafb;
            --text-light: #9ca3af;
            --border: #374151;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: system-ui, sans-serif; }
        body { background: var(--bg); color: var(--text); min-height: 100vh; }
        .container { max-width: 600px; margin: 0 auto; padding: 1.5rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .logo { font-size: 1.25rem; font-weight: 800; color: var(--primary); text-decoration: none; }
        .back { color: var(--text-light); text-decoration: none; font-size: 0.9rem; }
        .back:hover { color: var(--primary); }
        .job-list { display: flex; flex-direction: column; gap: 1rem; }
        .job-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 1.5rem;
            text-decoration: none;
            color: inherit;
            transition: all 0.2s;
        }
        .job-card:hover { border-color: var(--primary); transform: translateY(-2px); }
        .job-card h3 { font-size: 1.1rem; margin-bottom: 0.5rem; }
        .job-card .desc { font-size: 0.9rem; color: var(--text-light); line-height: 1.4; margin-bottom: 1rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .job-meta { font-size: 0.85rem; color: var(--text-light); display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem; }
        .job-status-badge { padding: 0.2rem 0.5rem; border-radius: 1rem; font-size: 0.75rem; }
        .job-status-badge.active { background: rgba(22,163,74,0.2); color: #4ade80; }
        .job-status-badge.closed { background: #374151; color: var(--text-light); }
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

        <?php if ($demoMode): ?>
            <div style="background:rgba(37,99,235,0.2);color:var(--primary);padding:0.75rem 1rem;border-radius:8px;margin-bottom:1.5rem;font-size:0.9rem;">
                Mode démo – offres d'exemple.
            </div>
        <?php endif; ?>

        <h2 style="margin-bottom:1.5rem;font-size:1.25rem;">Offres d'emploi</h2>

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
                            <span><?= $job['nb_candidates'] ?? 0 ?> candidat(s)</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="footer">
            <a href="https://www.ciaocv.com">Retour au site principal</a>
        </div>
    </div>
</body>
</html>
