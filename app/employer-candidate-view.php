<?php
/**
 * Maquette : voir la réponse vidéo d'un candidat
 */
require_once __DIR__ . '/db.php';

$jobId = (int)($_GET['job'] ?? 0);
$idx = (int)($_GET['idx'] ?? 0);
$job = null;
$candidate = null;
$demoMode = false;

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

if (!$candidate && $jobId) {
    $demo = include __DIR__ . '/demo-data.php';
    foreach ($demo['jobs'] as $j) {
        if ((int)$j['id'] === $jobId) {
            $job = $j;
            $list = $demo['candidates'][$jobId] ?? [];
            $candidate = $list[$idx] ?? null;
            $demoMode = true;
            break;
        }
    }
}

if (!$job || !$candidate) {
    header('Location: employer-job-view.php?id=' . $jobId);
    exit;
}

$videoUrl = $candidate['video_url'] ?? '';
if (empty($videoUrl) && $demoMode) {
    $videoUrl = 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4';
}

$currentStatus = $candidate['status'] ?? 'new';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($candidate['candidate_name'] ?? 'Candidat') ?> - CiaoCV</title>
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
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: system-ui, sans-serif; }
        body { background: var(--bg); color: var(--text); min-height: 100vh; }
        .container { max-width: 700px; margin: 0 auto; padding: 1.5rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .logo { font-size: 1.25rem; font-weight: 800; color: var(--primary); text-decoration: none; }
        .back { color: var(--text-light); text-decoration: none; font-size: 0.9rem; }
        .video-wrapper {
            background: #000;
            border-radius: 1rem;
            overflow: hidden;
            margin-bottom: 1.5rem;
            aspect-ratio: 16/9;
        }
        .video-wrapper video {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .candidate-info {
            background: var(--card-bg);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .candidate-info h1 { font-size: 1.25rem; margin-bottom: 0.5rem; }
        .candidate-info .email { color: var(--text-light); font-size: 0.95rem; }
        .job-badge {
            display: inline-block;
            background: #dbeafe;
            color: var(--primary);
            padding: 0.35rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.85rem;
            margin-top: 1rem;
        }
        .status-section {
            background: var(--card-bg);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-top: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .status-section h3 { font-size: 0.95rem; margin-bottom: 1rem; color: var(--text-light); font-weight: 600; }
        .status-btns {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .status-btn {
            padding: 0.6rem 1rem;
            border: 2px solid var(--border);
            border-radius: 0.5rem;
            background: white;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .status-btn:hover { border-color: var(--primary); color: var(--primary); }
        .status-btn.active {
            border-color: var(--primary);
            background: var(--primary);
            color: white;
        }
        .status-btn[data-status="rejected"].active { border-color: #dc2626; background: #dc2626; }
        .status-btn[data-status="accepted"].active { border-color: #16a34a; background: #16a34a; }
        .status-btn[data-status="pool"].active { border-color: #7c3aed; background: #7c3aed; }
        .notes-section {
            background: var(--card-bg);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-top: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .notes-section h3 { font-size: 0.95rem; margin-bottom: 0.75rem; color: var(--text-light); font-weight: 600; }
        .notes-section textarea {
            width: 100%;
            min-height: 100px;
            padding: 1rem;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            font-size: 0.95rem;
            resize: vertical;
        }
        .notes-section .hint { font-size: 0.8rem; color: var(--text-light); margin-top: 0.5rem; }
        .notes-section .save-row { margin-top: 1rem; }
        .btn-save {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.6rem 1.25rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
        }
        .btn-save:hover { background: var(--primary-dark); }
        .save-feedback { font-size: 0.9rem; color: #16a34a; margin-top: 0.5rem; }
        .footer { margin-top: 2rem; text-align: center; font-size: 0.8rem; color: var(--text-light); }
        .footer a { color: var(--text-light); }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <a href="employer.php" class="logo">CiaoCV</a>
            <a href="employer-job-view.php?id=<?= $jobId ?>" class="back">← Retour au poste</a>
        </header>

        <?php if ($demoMode): ?>
            <div style="background:#dbeafe;color:#1e40af;padding:0.75rem 1rem;border-radius:8px;margin-bottom:1.5rem;font-size:0.9rem;">
                Mode démo – vidéo d'exemple.
            </div>
        <?php endif; ?>

        <div class="video-wrapper">
            <video controls playsinline>
                <source src="<?= htmlspecialchars($videoUrl) ?>" type="video/mp4">
                Votre navigateur ne supporte pas la vidéo.
            </video>
        </div>

        <div class="candidate-info">
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

        <div class="footer">
            <a href="https://www.ciaocv.com">Retour au site principal</a>
        </div>
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
