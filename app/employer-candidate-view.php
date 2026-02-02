<?php
/**
 * Voir la fiche candidat : vidéo, statut, notes d'évaluation partagées
 */
session_start();
require_once __DIR__ . '/db.php';

$jobId = (int) ($_GET['job'] ?? 0);
$idx = (int) ($_GET['idx'] ?? 0);
$job = null;
$candidate = null;
$notes = [];
$noteAdded = false;
$noteError = null;
$currentUserEmail = $_SESSION['user_email'] ?? null;
$currentUserId = $_SESSION['user_id'] ?? null;

// S'assurer que la table evaluation_notes existe (même schéma que employer-job-view)
if ($db) {
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS evaluation_notes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            job_id INT NOT NULL,
            application_id INT DEFAULT NULL,
            author_email VARCHAR(255) NOT NULL,
            author_name VARCHAR(255) DEFAULT NULL,
            note_text TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_job_created (job_id, created_at DESC),
            INDEX idx_app (application_id)
        )");
    } catch (PDOException $e) {
    }
}

if ($jobId && $db) {
    try {
        $stmt = $db->prepare('SELECT * FROM jobs WHERE id = ?');
        $stmt->execute([$jobId]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($job) {
            // Vérifier si la colonne phone existe dans users
            $userCols = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
            $hasPhone = in_array('phone', $userCols);
            if ($hasPhone) {
                $stmtA = $db->prepare('SELECT a.*, u.phone FROM applications a LEFT JOIN users u ON u.email = a.candidate_email WHERE a.job_id = ? ORDER BY a.created_at DESC');
            } else {
                $stmtA = $db->prepare('SELECT a.* FROM applications a WHERE a.job_id = ? ORDER BY a.created_at DESC');
            }
            $stmtA->execute([$jobId]);
            $all = $stmtA->fetchAll(PDO::FETCH_ASSOC);
            $candidate = $all[$idx] ?? null;
        }
    } catch (PDOException $e) {
        $job = null;
        error_log('employer-candidate-view error: ' . $e->getMessage());
    }
}

if (!$job || !$candidate) {
    // API: Mise à jour du statut via AJAX (doit être avant redirect pour éviter boucle)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
        header('Content-Type: application/json');
        $newStatus = $_POST['status'] ?? '';
        $appId = (int) ($_POST['application_id'] ?? 0);
        if (in_array($newStatus, ['new', 'rejected', 'accepted', 'pool']) && $appId > 0 && $db) {
            try {
                $stmt = $db->prepare('UPDATE applications SET status = ? WHERE id = ?');
                $stmt->execute([$newStatus, $appId]);
                echo json_encode(['success' => true]);
            } catch (PDOException $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['error' => 'Paramètres invalides']);
        }
        exit;
    }
    header('Location: employer-job-view.php?id=' . $jobId);
    exit;
}

// ID de la candidature (clé peut être 'id' ou 'ID' selon le driver)
$applicationId = (int) ($candidate['id'] ?? $candidate['ID'] ?? 0);

// API: Mise à jour du statut via AJAX (aussi si candidate existe)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    header('Content-Type: application/json');
    $newStatus = $_POST['status'] ?? '';
    $appId = (int) ($_POST['application_id'] ?? 0);
    if (in_array($newStatus, ['new', 'rejected', 'accepted', 'pool']) && $appId > 0 && $db) {
        try {
            $stmt = $db->prepare('UPDATE applications SET status = ? WHERE id = ?');
            $stmt->execute([$newStatus, $appId]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['error' => 'Paramètres invalides']);
    }
    exit;
}

// Traitement : ajouter une note (nom, courriel, IP, heure)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_note']) && $db && $currentUserEmail) {
    $noteText = trim($_POST['note_text'] ?? '');
    $authorName = $_SESSION['user_first_name'] ?? $_SESSION['company_name'] ?? 'Anonyme';
    $authorIp = $_SERVER['REMOTE_ADDR'] ?? null;
    if ($noteText !== '' && $applicationId > 0) {
        try {
            $noteCols = $db->query("SHOW COLUMNS FROM evaluation_notes")->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('author_ip', $noteCols)) {
                $stmt = $db->prepare('INSERT INTO evaluation_notes (job_id, application_id, author_email, author_name, author_ip, note_text) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$jobId, $applicationId, $currentUserEmail, $authorName, $authorIp, $noteText]);
            } else {
                $stmt = $db->prepare('INSERT INTO evaluation_notes (job_id, application_id, author_email, author_name, note_text) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$jobId, $applicationId, $currentUserEmail, $authorName, $noteText]);
            }
            $noteAdded = true;
        } catch (PDOException $e) {
            $noteError = 'Erreur lors de l\'enregistrement : ' . $e->getMessage();
        }
    } elseif ($applicationId <= 0) {
        $noteError = 'Impossible d\'identifier la candidature.';
    }
}

// Charger les notes pour ce candidat (plus récentes en premier)
if ($applicationId > 0 && $db) {
    try {
        $stmt = $db->prepare('SELECT * FROM evaluation_notes WHERE job_id = ? AND application_id = ? ORDER BY created_at DESC');
        $stmt->execute([$jobId, $applicationId]);
        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $notes = [];
    }
}

$videoUrl = $candidate['video_url'] ?? '';
$currentStatus = $candidate['status'] ?? 'new';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($candidate['candidate_name'] ?? 'Candidat') ?> - CiaoCV</title>
    <link rel="stylesheet" href="assets/css/design-system.css?v=<?= ASSET_VERSION ?>">
    <script src="assets/js/local-time.js?v=<?= ASSET_VERSION ?>"></script>
</head>

<body>
    <div class="app-shell">
        <?php $sidebarActive = 'list';
        include __DIR__ . '/includes/employer-sidebar.php'; ?>
        <main class="app-main">
            <?php include __DIR__ . '/includes/employer-header.php'; ?>
            <div class="app-main-content layout-app layout-app-wide">

                <div class="candidate-info-card">
                    <h1><?= htmlspecialchars($candidate['candidate_name'] ?: 'Sans nom') ?></h1>
                    <div class="email"><?= htmlspecialchars($candidate['candidate_email']) ?></div>
                    <?php if (!empty($candidate['phone'])): ?>
                        <div class="phone" style="color:var(--text-secondary);font-size:0.9rem;">
                            <?= htmlspecialchars($candidate['phone']) ?>
                        </div>
                    <?php endif; ?>
                    <span class="job-badge"><?= htmlspecialchars($job['title']) ?></span>
                </div>

                <div class="section">
                    <h2>Vidéo de candidature</h2>
                    <?php if ($videoUrl !== ''): ?>
                        <div class="video-wrapper">
                            <video controls playsinline>
                                <source src="<?= htmlspecialchars($videoUrl) ?>" type="video/mp4">
                                Votre navigateur ne supporte pas la vidéo.
                            </video>
                        </div>
                    <?php else: ?>
                        <p style="color:var(--text-muted);">Aucune vidéo pour ce candidat.</p>
                    <?php endif; ?>
                </div>

                <div class="notes-section">
                    <h3>Notes</h3>
                    <?php if ($noteAdded): ?>
                        <div
                            style="padding:0.5rem 0.75rem;background:#d1fae5;color:#065f46;border-radius:6px;margin-bottom:0.75rem;font-size:0.9rem;">
                            ✓ Note enregistrée</div>
                    <?php endif; ?>
                    <?php if ($noteError): ?>
                        <div
                            style="padding:0.5rem 0.75rem;background:#fee2e2;color:#b91c1c;border-radius:6px;margin-bottom:0.75rem;font-size:0.9rem;">
                            <?= htmlspecialchars($noteError) ?>
                        </div>
                    <?php endif; ?>
                    <p class="hint">Notes partagées entre l'entreprise et les évaluateurs (non visibles par le
                        candidat).</p>
                    <?php if ($currentUserEmail): ?>
                        <form method="POST" style="margin-bottom:1rem;">
                            <input type="hidden" name="add_note" value="1">
                            <textarea name="note_text" rows="3" required placeholder="Ajouter une note sur ce candidat…"
                                style="width:100%;padding:0.75rem;border:1px solid var(--border, #e5e7eb);border-radius:8px;resize:vertical;"></textarea>
                            <button type="submit" class="btn" style="margin-top:0.5rem;">Publier la note</button>
                        </form>
                    <?php else: ?>
                        <p class="hint" style="color:#b91c1c;">Connectez-vous pour ajouter une note.</p>
                    <?php endif; ?>
                    <?php if (!empty($notes)): ?>
                        <div style="display:flex;flex-direction:column;gap:0.75rem;">
                            <?php foreach ($notes as $note):
                                $createdAt = trim($note['created_at'] ?? '');
                                $isoUtc = $createdAt !== '' ? preg_replace('/\s/', 'T', $createdAt) . 'Z' : '';
                                $noteDateFallback = $createdAt !== '' ? date('d/m/Y H:i', strtotime($createdAt)) : '';
                                ?>
                                <div
                                    style="padding:1rem;background:var(--bg-alt, #f8fafc);border:1px solid var(--border, #e5e7eb);border-radius:8px;">
                                    <div
                                        style="display:flex;justify-content:space-between;align-items:start;margin-bottom:0.5rem;flex-wrap:wrap;gap:0.25rem;">
                                        <strong
                                            style="color:var(--primary);"><?= htmlspecialchars($note['author_name'] ?? $note['author_email']) ?></strong>
                                        <span
                                            style="font-size:0.8rem;color:var(--text-secondary);"><?= $noteDateFallback ?><?= isset($note['author_ip']) && $note['author_ip'] !== '' ? ' · IP ' . htmlspecialchars($note['author_ip']) : '' ?></span>
                                    </div>
                                    <div style="color:var(--text);white-space:pre-wrap;">
                                        <?= htmlspecialchars($note['note_text']) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="font-size:0.9rem;color:var(--text-secondary);">Aucune note pour le moment.</p>
                    <?php endif; ?>
                </div>

                <div class="status-section">
                    <h3>Statut</h3>
                    <?php if (($candidate['status'] ?? '') === 'withdrawn'): ?>
                        <span class="status-badge status-withdrawn">Décliné par le candidat</span>
                    <?php else: ?>
                        <div class="status-btns">
                            <button type="button" class="status-btn" data-status="new" data-label="NON TRAITÉ">NON
                                TRAITÉ</button>
                            <button type="button" class="status-btn" data-status="rejected"
                                data-label="REFUSÉ">REFUSÉ</button>
                            <button type="button" class="status-btn" data-status="accepted"
                                data-label="ACCEPTÉ">ACCEPTÉ</button>
                            <button type="button" class="status-btn" data-status="pool" data-label="BANQUE">BANQUE</button>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </main>
    </div>

    <script>
        (function () {
            const currentStatus = '<?= in_array($currentStatus, ['new', 'viewed', 'rejected', 'accepted', 'pool']) ? $currentStatus : 'new' ?>';
            const applicationId = <?= $applicationId ?>;
            const jobId = <?= $jobId ?>;
            const idx = <?= $idx ?>;
            const btns = document.querySelectorAll('.status-btn');
            btns.forEach(btn => {
                if (btn.dataset.status === currentStatus || (currentStatus === 'viewed' && btn.dataset.status === 'new')) {
                    btn.classList.add('active');
                }
                btn.addEventListener('click', async function () {
                    const newStatus = this.dataset.status;
                    btns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');

                    // Auto-save via AJAX
                    const formData = new FormData();
                    formData.append('action', 'update_status');
                    formData.append('status', newStatus);
                    formData.append('application_id', applicationId);
                    try {
                        const res = await fetch('employer-candidate-view.php?job=' + jobId + '&idx=' + idx, { method: 'POST', body: formData });
                        const result = await res.json();
                        if (result.success) {
                            this.style.outline = '2px solid #10b981';
                            setTimeout(() => this.style.outline = '', 500);
                        }
                    } catch (e) {
                        console.error('Status update error:', e);
                    }
                });
            });
        })();
    </script>
</body>

</html>