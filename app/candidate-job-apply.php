<?php
/**
 * Maquette : postuler à une offre (voir détails + lien vers enregistrement vidéo)
 */
require_once __DIR__ . '/db.php';

$jobId = (int)($_GET['id'] ?? 0);
$job = null;
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
        }
    } catch (PDOException $e) {
        $job = null;
    }
}

if (!$job && $jobId) {
    $demo = include __DIR__ . '/demo-data.php';
    foreach ($demo['jobs'] as $j) {
        if ((int)$j['id'] === $jobId) {
            $job = $j;
            $demoMode = true;
            break;
        }
    }
}

if (!$job) {
    header('Location: candidate-jobs.php');
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
        .job-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 1.5rem;
        }
        .job-card h1 { font-size: 1.35rem; margin-bottom: 1rem; }
        .job-card .desc { color: var(--text-light); line-height: 1.6; margin-bottom: 1.5rem; white-space: pre-wrap; }
        .questions-title { font-weight: 600; margin-bottom: 0.75rem; }
        .questions-list { margin-left: 1rem; }
        .questions-list li { margin-bottom: 0.5rem; color: var(--text-light); }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--primary);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn:hover { background: var(--primary-dark); }
        .footer { margin-top: 2rem; text-align: center; font-size: 0.8rem; color: var(--text-light); }
        .footer a { color: var(--primary); }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <a href="candidate-jobs.php" class="logo">CiaoCV</a>
            <a href="candidate-jobs.php" class="back">← Retour aux offres</a>
        </header>

        <?php if ($demoMode): ?>
            <div style="background:rgba(37,99,235,0.2);color:var(--primary);padding:0.75rem 1rem;border-radius:8px;margin-bottom:1.5rem;font-size:0.9rem;">
                Mode démo.
            </div>
        <?php endif; ?>

        <div class="job-card">
            <h1><?= htmlspecialchars($job['title']) ?></h1>
            <?php if (!empty($job['description'])): ?>
                <div class="desc"><?= nl2br(htmlspecialchars($job['description'])) ?></div>
            <?php endif; ?>
            <?php if (!empty($job['questions'])): ?>
                <div class="questions-title">Questions pour votre pitch vidéo :</div>
                <ol class="questions-list">
                    <?php foreach ($job['questions'] as $q): ?>
                        <li><?= htmlspecialchars($q['question_text']) ?></li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>

        <a href="record.php" class="btn">🎬 Enregistrer mon pitch vidéo</a>

        <div class="footer" style="margin-top: 2rem;">
            <a href="https://www.ciaocv.com">Retour au site principal</a>
        </div>
    </div>
</body>
</html>
