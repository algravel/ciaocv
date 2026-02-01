<?php
require_once __DIR__ . '/db.php';

$jobs = [];
$totalCandidates = 0;
$demoMode = false;

if ($db) {
    try {
        // Vérifier si la colonne deleted_at existe
        $hasDeletedAt = $db->query("SHOW COLUMNS FROM jobs LIKE 'deleted_at'")->rowCount() > 0;
        
        if ($hasDeletedAt) {
            // Exclure les postes supprimés (soft delete)
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
        $demo = include __DIR__ . '/demo-data.php';
        $jobs = $demo['jobs'];
        $totalCandidates = array_sum(array_column($jobs, 'nb_candidates'));
        $demoMode = true;
    }
} else {
    $demo = include __DIR__ . '/demo-data.php';
    $jobs = $demo['jobs'];
    $totalCandidates = array_sum(array_column($jobs, 'nb_candidates'));
    $demoMode = true;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Espace Employeur - CiaoCV</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --bg: #f3f4f6;
            --card-bg: #ffffff;
            --text: #1f2937;
            --text-light: #6b7280;
            --border: #e5e7eb;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', system-ui, sans-serif; }
        body { background-color: var(--bg); color: var(--text); min-height: 100vh; }

        .container { max-width: 1000px; margin: 0 auto; padding: 1rem; }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0 2rem;
        }

        .logo { font-size: 1.5rem; font-weight: 800; color: var(--primary); text-decoration: none; }
        .user-menu { display: flex; align-items: center; gap: 1rem; }
        .avatar {
            width: 40px; height: 40px;
            background: #dbeafe;
            color: var(--primary);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            text-align: center;
        }

        .stat-value { font-size: 2rem; font-weight: 800; color: var(--primary); margin-bottom: 0.5rem; }
        .stat-label { color: var(--text-light); font-size: 0.9rem; font-weight: 500; }

        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        h2 { font-size: 1.25rem; color: var(--text); }

        .btn {
            background: var(--primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: background 0.2s;
        }

        .btn:hover { background: var(--primary-dark); }

        .job-list { display: grid; gap: 1rem; }

        .job-card {
            background: var(--card-bg);
            border-radius: 1rem;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s;
        }

        .job-card:hover { transform: translateY(-2px); }

        .job-info h3 { font-size: 1.1rem; margin-bottom: 0.25rem; }
        .job-meta { color: var(--text-light); font-size: 0.9rem; }
        .job-badge {
            background: #dbeafe;
            color: var(--primary);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .job-status {
            font-size: 0.75rem;
            padding: 0.2rem 0.6rem;
            border-radius: 1rem;
        }
        .job-status-draft { background: #fef3c7; color: #92400e; }
        .job-status-active { background: #d1fae5; color: #065f46; }
        .job-status-closed { background: #e5e7eb; color: #6b7280; }

        .filter-bar { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1.5rem; }
        .filter-btn { padding: 0.5rem 1rem; border: 2px solid var(--border); border-radius: 0.5rem; background: white; font-size: 0.875rem; font-weight: 600; cursor: pointer; }
        .filter-btn:hover { border-color: var(--primary); color: var(--primary); }
        .filter-btn.active { border-color: var(--primary); background: var(--primary); color: white; }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            background: var(--card-bg);
            border-radius: 1rem;
            color: var(--text-light);
        }

        .empty-state p { margin-bottom: 1rem; }

        .footer { margin-top: 3rem; text-align: center; font-size: 0.8rem; color: var(--text-light); }
        .footer a { color: var(--text-light); text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <a href="index.php" class="logo">CiaoCV</a>
            <div class="user-menu">
                <span>Employeur</span>
                <div class="avatar">EM</div>
            </div>
        </header>

        <?php if ($demoMode): ?>
            <div style="background:#fef3c7;color:#92400e;padding:0.75rem 1rem;border-radius:8px;margin-bottom:1.5rem;font-size:0.9rem;">
                ⚠️ Mode démo – données d'exemple. <a href="update-schema-jobs.php" style="color:#92400e;font-weight:600;">Initialiser la base de données</a> pour les vraies données.
            </div>
        <?php else: ?>
            <div style="background:#d1fae5;color:#065f46;padding:0.75rem 1rem;border-radius:8px;margin-bottom:1.5rem;font-size:0.9rem;">
                ✓ Connecté à la base de données
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= count($jobs) ?></div>
                <div class="stat-label">Affichages actifs</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $totalCandidates ?></div>
                <div class="stat-label">Candidatures reçues</div>
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
                    <a href="employer-job-view.php?id=<?= $job['id'] ?>" class="job-card" data-status="<?= htmlspecialchars($jStatus) ?>">
                        <div class="job-info">
                            <h3><?= htmlspecialchars($job['title']) ?></h3>
                            <div class="job-meta"><?= $job['nb_candidates'] ?? 0 ?> candidat(s) · <span class="job-status job-status-<?= $jStatus ?>"><?= $statusLabels[$jStatus] ?? $jStatus ?></span></div>
                        </div>
                        <span class="job-badge">Voir les candidats →</span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="footer">
            <a href="https://www.ciaocv.com">Retour au site principal</a>
        </div>
    </div>

    <?php if (!empty($jobs)): ?>
    <script>
        (function(){
            var btns = document.querySelectorAll('.filter-btn');
            var cards = document.querySelectorAll('.job-card[data-status]');
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
