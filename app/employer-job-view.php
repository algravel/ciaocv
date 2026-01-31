<?php
require_once __DIR__ . '/db.php';

$jobId = (int)($_GET['id'] ?? 0);
$job = null;
$candidates = [];
$demoMode = false;

if ($jobId && $db) {
    try {
        $stmt = $db->prepare('SELECT * FROM jobs WHERE id = ?');
        $stmt->execute([$jobId]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($job) {
            $stmtQ = $db->prepare('SELECT * FROM job_questions WHERE job_id = ? ORDER BY sort_order');
            $stmtQ->execute([$jobId]);
            $job['questions'] = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

            $stmtA = $db->prepare('SELECT * FROM applications WHERE job_id = ? ORDER BY created_at DESC');
            $stmtA->execute([$jobId]);
            $candidates = $stmtA->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $job = null;
    }
}

if (!$job) {
    $demo = include __DIR__ . '/demo-data.php';
    foreach ($demo['jobs'] as $j) {
        if ((int)$j['id'] === $jobId) {
            $job = $j;
            $candidates = $demo['candidates'][$jobId] ?? [];
            $demoMode = true;
            break;
        }
    }
}

if (!$job) {
    header('Location: employer.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($job['title']) ?> - CiaoCV</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --bg: #f3f4f6;
            --card-bg: #ffffff;
            --text: #1f2937;
            --text-light: #6b7280;
            --border: #e5e7eb;
            --success: #10b981;
            --danger: #ef4444;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: system-ui, sans-serif; }
        body { background: var(--bg); color: var(--text); min-height: 100vh; }
        .container { max-width: 900px; margin: 0 auto; padding: 1.5rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .logo { font-size: 1.25rem; font-weight: 800; color: var(--primary); text-decoration: none; }
        .back { color: var(--text-light); text-decoration: none; font-size: 0.9rem; }
        .job-card {
            background: var(--card-bg);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .job-card h1 { font-size: 1.5rem; margin-bottom: 1rem; }
        .job-status-bar { margin-bottom: 1rem; display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
        .job-status-bar .label { font-size: 0.9rem; color: var(--text-light); }
        .job-status-bar .status-btns { display: flex; gap: 0.5rem; }
        .job-status-bar .status-btn { padding: 0.4rem 0.9rem; border: 2px solid var(--border); border-radius: 0.5rem; background: white; font-size: 0.85rem; cursor: pointer; }
        .job-status-bar .status-btn:hover { border-color: var(--primary); }
        .job-status-bar .status-btn.active { border-color: var(--primary); background: var(--primary); color: white; }
        .job-card .desc { color: var(--text-light); line-height: 1.6; margin-bottom: 1.5rem; white-space: pre-wrap; }
        .questions-title { font-weight: 600; margin-bottom: 0.75rem; }
        .questions-list { margin-left: 1rem; margin-bottom: 1rem; }
        .questions-list li { margin-bottom: 0.5rem; color: var(--text-light); }
        h2 { font-size: 1.25rem; margin-bottom: 1rem; }
        .candidate-list { display: grid; gap: 1rem; }
        .candidate-card {
            background: var(--card-bg);
            border-radius: 1rem;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .candidate-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .candidate-video-preview {
            width: 70px; height: 70px;
            background: #000;
            border-radius: 50%;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        .candidate-info { flex: 1; }
        .candidate-name { font-weight: 700; margin-bottom: 0.25rem; }
        .candidate-email { font-size: 0.9rem; color: var(--text-light); }
        .tag-status { font-size: 0.75rem; padding: 0.2rem 0.6rem; border-radius: 1rem; }
        .tag-new { background: #dbeafe; color: var(--primary); }
        .tag-viewed { background: #dbeafe; color: var(--primary); }
        .tag-accepted { background: #d1fae5; color: var(--success); }
        .tag-rejected { background: #fee2e2; color: var(--danger); }
        .tag-pool { background: #ede9fe; color: #7c3aed; }
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .filter-btn {
            padding: 0.5rem 1rem;
            border: 2px solid var(--border);
            border-radius: 0.5rem;
            background: white;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .filter-btn:hover { border-color: var(--primary); color: var(--primary); }
        .filter-btn.active {
            border-color: var(--primary);
            background: var(--primary);
            color: white;
        }
        .empty-state { text-align: center; padding: 3rem; color: var(--text-light); }
        .candidate-card.filtered-out { display: none !important; }
        .footer { margin-top: 2rem; text-align: center; font-size: 0.8rem; color: var(--text-light); }
        .footer a { color: var(--text-light); }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <a href="employer.php" class="logo">CiaoCV</a>
            <a href="employer.php" class="back">← Retour aux affichages</a>
        </header>

        <?php if ($demoMode): ?>
            <div style="background:#dbeafe;color:#1e40af;padding:0.75rem 1rem;border-radius:8px;margin-bottom:1.5rem;font-size:0.9rem;">
                <?= !empty($_GET['demo']) ? 'Exemple de poste créé (mode démo). ' : '' ?>Données d'exemple.
            </div>
        <?php endif; ?>

        <div class="job-card">
            <h1><?= htmlspecialchars($job['title']) ?></h1>
            <?php $jobStatus = $job['status'] ?? 'active'; ?>
            <div class="job-status-bar">
                <span class="label">Statut de l'affichage :</span>
                <div class="status-btns">
                    <button type="button" class="status-btn <?= $jobStatus === 'draft' ? 'active' : '' ?>" data-status="draft">Brouillon</button>
                    <button type="button" class="status-btn <?= $jobStatus === 'active' ? 'active' : '' ?>" data-status="active">En cours</button>
                    <button type="button" class="status-btn <?= $jobStatus === 'closed' ? 'active' : '' ?>" data-status="closed">Fermé</button>
                </div>
            </div>
            <?php if ($job['description']): ?>
                <div class="desc"><?= nl2br(htmlspecialchars($job['description'])) ?></div>
            <?php endif; ?>
            <?php if (!empty($job['questions'])): ?>
                <div class="questions-title">Questions d'entrevue :</div>
                <ol class="questions-list">
                    <?php foreach ($job['questions'] as $q): ?>
                        <li><?= htmlspecialchars($q['question_text']) ?></li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
            <h2 style="margin: 0;">Candidats (<?= count($candidates) ?>)</h2>
            <?php if (!empty($candidates)): ?>
            <div class="filter-bar">
                <button type="button" class="filter-btn active" data-filter="all">Tous</button>
                <button type="button" class="filter-btn" data-filter="new">NON TRAITÉ</button>
                <button type="button" class="filter-btn" data-filter="rejected">REFUSÉ</button>
                <button type="button" class="filter-btn" data-filter="accepted">ACCEPTÉ</button>
                <button type="button" class="filter-btn" data-filter="pool">BANQUE</button>
            </div>
            <?php endif; ?>
        </div>

        <?php if (empty($candidates)): ?>
            <div class="empty-state">
                <p>Aucun candidat pour le moment.</p>
            </div>
        <?php else: ?>
            <div class="candidate-list">
                <?php foreach ($candidates as $i => $c): 
                    $cStatus = $c['status'] ?? 'new';
                    if ($cStatus === 'viewed') $cStatus = 'new';
                ?>
                    <a href="employer-candidate-view.php?job=<?= $jobId ?>&idx=<?= $i ?>" class="candidate-card" style="text-decoration:none;color:inherit;" data-status="<?= htmlspecialchars($cStatus) ?>">
                        <div class="candidate-video-preview">
                            ▶
                        </div>
                        <div class="candidate-info" style="flex:1;">
                            <div class="candidate-name"><?= htmlspecialchars($c['candidate_name'] ?: 'Sans nom') ?></div>
                            <div class="candidate-email"><?= htmlspecialchars($c['candidate_email']) ?></div>
                        </div>
                        <?php
                        $statusLabels = ['new' => 'NON TRAITÉ', 'viewed' => 'NON TRAITÉ', 'rejected' => 'REFUSÉ', 'accepted' => 'ACCEPTÉ', 'pool' => 'BANQUE'];
                        $statusLabel = $statusLabels[$c['status'] ?? 'new'] ?? 'NON TRAITÉ';
                        ?>
                        <span class="tag-status tag-<?= htmlspecialchars($c['status'] ?? 'new') ?>"><?= $statusLabel ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="footer">
            <a href="https://www.ciaocv.com">Retour au site principal</a>
        </div>
    </div>

    <script>
        (function(){
            var jobStatusBtns = document.querySelectorAll('.job-status-bar .status-btn');
            jobStatusBtns.forEach(function(btn){
                btn.addEventListener('click', function(){
                    jobStatusBtns.forEach(function(b){ b.classList.remove('active'); });
                    this.classList.add('active');
                });
            });
        })();
    </script>

    <?php if (!empty($candidates)): ?>
    <script>
        (function() {
            const btns = document.querySelectorAll('.filter-btn');
            const cards = document.querySelectorAll('.candidate-card[data-status]');
            const countEl = document.querySelector('h2');
            function updateCount(visible) {
                if (countEl) countEl.innerHTML = 'Candidats (' + visible + ')';
            }
            btns.forEach(btn => {
                btn.addEventListener('click', function() {
                    btns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    const filter = this.dataset.filter;
                    let visible = 0;
                    cards.forEach(card => {
                        const match = filter === 'all' || card.dataset.status === filter;
                        card.classList.toggle('filtered-out', !match);
                        if (match) visible++;
                    });
                    updateCount(visible);
                });
            });
        })();
    </script>
    <?php endif; ?>
</body>
</html>
