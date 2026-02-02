<?php
/**
 * Fiche candidat pour un évaluateur : vidéo, notes (avec nom, courriel, IP, heure).
 */
session_start();
require_once __DIR__ . '/db.php';

$jobId = (int)($_GET['job'] ?? 0);
$idx = (int)($_GET['idx'] ?? 0);
$job = null;
$candidate = null;
$notes = [];
$noteAdded = false;
$noteError = null;

if (!isset($_SESSION['evaluator_token'], $_SESSION['evaluator_email'], $_SESSION['evaluator_job_id']) || $_SESSION['evaluator_job_id'] !== $jobId) {
    header('Location: index.php');
    exit;
}

if ($jobId && $db) {
    $stmt = $db->prepare('SELECT * FROM jobs WHERE id = ?');
    $stmt->execute([$jobId]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($job) {
        $stmtEval = $db->prepare('SELECT 1 FROM job_evaluators WHERE job_id = ? AND email = ?');
        $stmtEval->execute([$jobId, $_SESSION['evaluator_email']]);
        if (!$stmtEval->fetch()) {
            header('Location: index.php');
            exit;
        }
        $stmtA = $db->prepare('SELECT * FROM applications WHERE job_id = ? ORDER BY created_at DESC');
        $stmtA->execute([$jobId]);
        $all = $stmtA->fetchAll(PDO::FETCH_ASSOC);
        $candidate = $all[$idx] ?? null;
    }
}

if (!$job || !$candidate) {
    header('Location: evaluator-job-view.php?job=' . $jobId);
    exit;
}

$applicationId = (int)($candidate['id'] ?? $candidate['ID'] ?? 0);

// S'assurer que evaluation_notes a author_ip
if ($db) {
    $noteCols = $db->query("SHOW COLUMNS FROM evaluation_notes")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('author_ip', $noteCols)) {
        try { $db->exec("ALTER TABLE evaluation_notes ADD COLUMN author_ip VARCHAR(45) DEFAULT NULL AFTER author_name"); } catch (PDOException $e) {}
    }
}

// Ajouter une note (évaluateur : nom, courriel, IP, heure)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_note']) && $db && $applicationId > 0) {
    $noteText = trim($_POST['note_text'] ?? '');
    $authorEmail = $_SESSION['evaluator_email'];
    $authorName = $_SESSION['evaluator_name'] ?? $authorEmail;
    $authorIp = $_SERVER['REMOTE_ADDR'] ?? null;
    if ($noteText !== '') {
        try {
            $noteCols = $db->query("SHOW COLUMNS FROM evaluation_notes")->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('author_ip', $noteCols)) {
                $stmt = $db->prepare('INSERT INTO evaluation_notes (job_id, application_id, author_email, author_name, author_ip, note_text) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$jobId, $applicationId, $authorEmail, $authorName, $authorIp, $noteText]);
            } else {
                $stmt = $db->prepare('INSERT INTO evaluation_notes (job_id, application_id, author_email, author_name, note_text) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$jobId, $applicationId, $authorEmail, $authorName, $noteText]);
            }
            $noteAdded = true;
        } catch (PDOException $e) {
            $noteError = 'Erreur lors de l\'enregistrement.';
        }
    }
}

if ($applicationId > 0 && $db) {
    $stmt = $db->prepare('SELECT * FROM evaluation_notes WHERE job_id = ? AND application_id = ? ORDER BY created_at DESC');
    $stmt->execute([$jobId, $applicationId]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$videoUrl = $candidate['video_url'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($candidate['candidate_name'] ?? 'Candidat') ?> - CiaoCV</title>
    <link rel="stylesheet" href="assets/css/design-system.css?v=<?= defined('ASSET_VERSION') ? ASSET_VERSION : time() ?>">
    <script src="assets/js/local-time.js?v=<?= ASSET_VERSION ?>"></script>
</head>
<body>
    <div class="app-shell">
        <main class="app-main">
            <header class="app-header" style="display:flex;">
                <div class="app-header-left" style="flex:1;">
                    <a href="evaluator-job-view.php?job=<?= $jobId ?>" style="color:var(--text-secondary);text-decoration:none;font-size:0.9rem;">← Liste des candidats</a>
                </div>
                <div class="app-header-right" style="font-size:0.9rem;color:var(--text-secondary);">
                    <?= htmlspecialchars($_SESSION['evaluator_name'] ?? $_SESSION['evaluator_email']) ?>
                    <a href="evaluator-logout.php" style="margin-left:0.75rem;color:var(--text-muted);text-decoration:none;">Déconnexion</a>
                </div>
            </header>
            <div class="app-main-content layout-app layout-app-wide" style="padding-top:1rem;">

                <?php if ($videoUrl !== ''): ?>
                <div class="video-wrapper">
                    <video controls playsinline>
                        <source src="<?= htmlspecialchars($videoUrl) ?>" type="video/mp4">
                        Votre navigateur ne supporte pas la vidéo.
                    </video>
                </div>
                <?php else: ?>
                <div class="video-wrapper" style="background:var(--border);border-radius:var(--radius-xl);padding:2rem;text-align:center;color:var(--text-muted);">
                    Aucune vidéo pour ce candidat.
                </div>
                <?php endif; ?>

                <div class="candidate-info-card">
                    <h1><?= htmlspecialchars($candidate['candidate_name'] ?: 'Sans nom') ?></h1>
                    <div class="email"><?= htmlspecialchars($candidate['candidate_email']) ?></div>
                    <span class="job-badge"><?= htmlspecialchars($job['title']) ?></span>
                </div>

                <div class="notes-section">
                    <h3>Notes</h3>
                    <?php if ($noteAdded): ?>
                        <div style="padding:0.5rem 0.75rem;background:#d1fae5;color:#065f46;border-radius:6px;margin-bottom:0.75rem;font-size:0.9rem;">✓ Note enregistrée</div>
                    <?php endif; ?>
                    <?php if ($noteError): ?>
                        <div style="padding:0.5rem 0.75rem;background:#fee2e2;color:#b91c1c;border-radius:6px;margin-bottom:0.75rem;font-size:0.9rem;"><?= htmlspecialchars($noteError) ?></div>
                    <?php endif; ?>
                    <p class="hint">Vos notes sont enregistrées avec votre nom, courriel, adresse IP et l'heure.</p>
                    <form method="POST" style="margin-bottom:1rem;">
                        <input type="hidden" name="add_note" value="1">
                        <textarea name="note_text" rows="3" required placeholder="Ajouter une note sur ce candidat…" 
                                  style="width:100%;padding:0.75rem;border:1px solid var(--border, #e5e7eb);border-radius:8px;resize:vertical;"></textarea>
                        <button type="submit" class="btn" style="margin-top:0.5rem;">Publier la note</button>
                    </form>
                    <?php if (!empty($notes)): ?>
                    <div style="display:flex;flex-direction:column;gap:0.75rem;">
                        <?php foreach ($notes as $note):
                            $createdAt = trim($note['created_at'] ?? '');
                            $isoUtc = $createdAt !== '' ? preg_replace('/\s/', 'T', $createdAt) . 'Z' : '';
                            $noteDateFallback = $createdAt !== '' ? date('d/m/Y H:i', strtotime($createdAt)) : '';
                            $authorIp = isset($note['author_ip']) && $note['author_ip'] !== '' ? ' · IP ' . htmlspecialchars($note['author_ip']) : '';
                        ?>
                            <div style="padding:1rem;background:var(--bg-alt, #f8fafc);border:1px solid var(--border, #e5e7eb);border-radius:8px;">
                                <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:0.5rem;flex-wrap:wrap;gap:0.25rem;">
                                    <strong style="color:var(--primary);"><?= htmlspecialchars($note['author_name'] ?? $note['author_email']) ?></strong>
                                    <span style="font-size:0.8rem;color:var(--text-secondary);"><?= $noteDateFallback ?><?= $authorIp ?></span>
                                </div>
                                <div style="color:var(--text);white-space:pre-wrap;"><?= htmlspecialchars($note['note_text']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p style="font-size:0.9rem;color:var(--text-secondary);">Aucune note pour le moment.</p>
                    <?php endif; ?>
                </div>

            </div>
        </main>
    </div>
</body>
</html>
