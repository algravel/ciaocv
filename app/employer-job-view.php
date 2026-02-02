<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';

$jobId = (int)($_GET['id'] ?? 0);
$job = null;
$candidates = [];
$statusUpdated = false;
$emailSent = null;
$evaluatorAdded = false;
$noteAdded = false;
$currentUserEmail = $_SESSION['user_email'] ?? null;
$currentUserId = $_SESSION['user_id'] ?? null;

// Créer les tables d'évaluation si nécessaire
if ($db) {
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS job_evaluators (
            id INT AUTO_INCREMENT PRIMARY KEY,
            job_id INT NOT NULL,
            email VARCHAR(255) NOT NULL,
            added_by INT NOT NULL,
            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
            UNIQUE KEY (job_id, email)
        )");
        
        $db->exec("CREATE TABLE IF NOT EXISTS evaluation_notes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            job_id INT NOT NULL,
            application_id INT DEFAULT NULL,
            author_email VARCHAR(255) NOT NULL,
            author_name VARCHAR(255) DEFAULT NULL,
            note_text TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
            INDEX idx_job_created (job_id, created_at DESC)
        )");
    } catch (PDOException $e) {
        // Tables existent déjà
    }
}

// Traitement: Ajouter un évaluateur
if ($jobId && $db && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_evaluator'])) {
    $evaluatorEmail = filter_var(trim($_POST['evaluator_email'] ?? ''), FILTER_VALIDATE_EMAIL);
    if ($evaluatorEmail && $currentUserId) {
        try {
            $stmt = $db->prepare('INSERT INTO job_evaluators (job_id, email, added_by) VALUES (?, ?, ?)');
            $stmt->execute([$jobId, $evaluatorEmail, $currentUserId]);
            $evaluatorAdded = true;
        } catch (PDOException $e) {
            // Évaluateur déjà ajouté
        }
    }
}

// Traitement: Ajouter une note
if ($jobId && $db && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_note'])) {
    $noteText = trim($_POST['note_text'] ?? '');
    $applicationId = isset($_POST['application_id']) && $_POST['application_id'] !== '' ? (int)$_POST['application_id'] : null;
    $authorName = $_SESSION['user_first_name'] ?? $_SESSION['company_name'] ?? 'Anonyme';
    
    if ($noteText && $currentUserEmail) {
        try {
            $stmt = $db->prepare('INSERT INTO evaluation_notes (job_id, application_id, author_email, author_name, note_text) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$jobId, $applicationId, $currentUserEmail, $authorName, $noteText]);
            $noteAdded = true;
        } catch (PDOException $e) {
            // Erreur
        }
    }
}

// Créer les colonnes manquantes si besoin
if ($db) {
    $cols = $db->query("SHOW COLUMNS FROM jobs")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('show_on_esplanade', $cols) && !in_array('show_on_jobmarket', $cols)) {
        try { $db->exec("ALTER TABLE jobs CHANGE COLUMN show_on_esplanade show_on_jobmarket TINYINT(1) DEFAULT 1"); } catch (PDOException $e) {}
    } elseif (!in_array('show_on_jobmarket', $cols)) {
        try { $db->exec("ALTER TABLE jobs ADD COLUMN show_on_jobmarket TINYINT(1) DEFAULT 1 AFTER status"); } catch (PDOException $e) {}
    }
    $cols = $db->query("SHOW COLUMNS FROM jobs")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('latitude', $cols)) {
        try { $db->exec("ALTER TABLE jobs ADD COLUMN latitude DECIMAL(10,8) DEFAULT NULL AFTER show_on_jobmarket"); } catch (PDOException $e) {}
    }
    if (!in_array('longitude', $cols)) {
        try { $db->exec("ALTER TABLE jobs ADD COLUMN longitude DECIMAL(11,8) DEFAULT NULL AFTER latitude"); } catch (PDOException $e) {}
    }
    if (!in_array('location_name', $cols)) {
        try { $db->exec("ALTER TABLE jobs ADD COLUMN location_name VARCHAR(255) DEFAULT NULL AFTER longitude"); } catch (PDOException $e) {}
    }
}

// Traitement : afficher ou non sur le JobMarket
if ($jobId && $db && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_jobmarket'])) {
    $show = (int)($_POST['show_on_jobmarket'] ?? 1);
    $show = $show ? 1 : 0;
    try {
        $stmt = $db->prepare('UPDATE jobs SET show_on_jobmarket = ? WHERE id = ?');
        $stmt->execute([$show, $jobId]);
        $statusUpdated = true;
    } catch (PDOException $e) {
        // Ignorer
    }
}

// Traitement de la suppression (soft delete)
if ($jobId && $db && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_job']) && $_POST['delete_job'] === 'confirm') {
    try {
        // Vérifier/créer la colonne deleted_at si elle n'existe pas
        $cols = $db->query("SHOW COLUMNS FROM jobs LIKE 'deleted_at'")->rowCount();
        if ($cols === 0) {
            $db->exec("ALTER TABLE jobs ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL");
        }
        
        // Soft delete : marquer comme supprimé au lieu de supprimer
        $stmt = $db->prepare('UPDATE jobs SET deleted_at = NOW() WHERE id = ?');
        $stmt->execute([$jobId]);
        
        header('Location: employer.php?deleted=1');
        exit;
    } catch (PDOException $e) {
        // Ignorer l'erreur
    }
}

// Traitement du changement de status
if ($jobId && $db && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_status'])) {
    $newStatus = $_POST['new_status'];
    if (in_array($newStatus, ['draft', 'active', 'closed'])) {
        try {
            $stmt = $db->prepare('UPDATE jobs SET status = ? WHERE id = ?');
            $stmt->execute([$newStatus, $jobId]);
            $statusUpdated = true;
        } catch (PDOException $e) {
            // Ignorer l'erreur
        }
    }
}

if ($jobId && $db) {
    try {
        // Vérifier si la colonne deleted_at existe
        $cols = $db->query("SHOW COLUMNS FROM jobs LIKE 'deleted_at'")->rowCount();
        
        if ($cols > 0) {
            // Exclure les postes supprimés (soft delete)
            $stmt = $db->prepare('SELECT * FROM jobs WHERE id = ? AND deleted_at IS NULL');
        } else {
            $stmt = $db->prepare('SELECT * FROM jobs WHERE id = ?');
        }
        $stmt->execute([$jobId]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($job) {
            $stmtQ = $db->prepare('SELECT * FROM job_questions WHERE job_id = ? ORDER BY sort_order');
            $stmtQ->execute([$jobId]);
            $job['questions'] = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

            $cols = $db->query("SHOW COLUMNS FROM applications")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('phone', $cols)) {
                try { $db->exec("ALTER TABLE applications ADD COLUMN phone VARCHAR(50) DEFAULT NULL AFTER candidate_name"); } catch (PDOException $e) {}
            }
            $stmtA = $db->prepare('
                SELECT a.*, u.photo_url, u.video_url AS bio_video_url
                FROM applications a
                LEFT JOIN users u ON u.email = a.candidate_email
                WHERE a.job_id = ?
                ORDER BY a.created_at DESC
            ');
            $stmtA->execute([$jobId]);
            $candidates = $stmtA->fetchAll(PDO::FETCH_ASSOC);
            
            // Charger les évaluateurs
            $stmtEval = $db->prepare('SELECT * FROM job_evaluators WHERE job_id = ? ORDER BY added_at DESC');
            $stmtEval->execute([$jobId]);
            $evaluators = $stmtEval->fetchAll(PDO::FETCH_ASSOC);
            
        }
    } catch (PDOException $e) {
        $job = null;
    }
}

// Vérifier si l'utilisateur a accès (propriétaire ou évaluateur)
$hasAccess = false;
if ($job && $currentUserEmail) {
    $hasAccess = true; // L'employeur a toujours accès
    // Vérifier si c'est un évaluateur
    if (isset($evaluators)) {
        foreach ($evaluators as $eval) {
            if ($eval['email'] === $currentUserEmail) {
                $hasAccess = true;
                break;
            }
        }
    }
}

if (!$job) {
    header('Location: employer.php');
    exit;
}

$showOnJobMarket = isset($job['show_on_jobmarket']) ? (int)$job['show_on_jobmarket'] : 1;

// Traitement : envoyer le poste par courriel (après chargement de $job)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_job_email'])) {
    $to = trim($_POST['send_email_to'] ?? '');
    $customMessage = trim($_POST['send_email_message'] ?? '');
    if ($to !== '' && filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'app.ciaocv.com');
        $applyUrl = $baseUrl . '/candidate-job-apply.php?id=' . $jobId;
        $jobTitle = htmlspecialchars($job['title']);
        $jobDesc = !empty($job['description']) ? nl2br(htmlspecialchars($job['description'])) : '';
        $html = '<p>Bonjour,</p>';
        if ($customMessage !== '') {
            $html .= '<p>' . nl2br(htmlspecialchars($customMessage)) . '</p>';
        }
        $html .= '<p><strong>Offre : ' . $jobTitle . '</strong></p>';
        if ($jobDesc !== '') {
            $html .= '<div>' . $jobDesc . '</div>';
        }
        $html .= '<p><a href="' . htmlspecialchars($applyUrl) . '" style="display:inline-block;margin-top:1rem;padding:0.75rem 1.5rem;background:#4f46e5;color:white;text-decoration:none;border-radius:0.5rem;font-weight:600;">Voir l\'offre et postuler</a></p>';
        $html .= '<p style="color:#64748b;font-size:0.875rem;">CiaoCV – Votre CV vidéo en 60 secondes</p>';
        $sent = send_zepto($to, 'Offre CiaoCV : ' . $jobTitle, $html);
        $emailSent = $sent ? true : false;
    } else {
        $emailSent = false;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($job['title']) ?> - CiaoCV</title>
    <link rel="stylesheet" href="assets/css/design-system.css?v=<?= ASSET_VERSION ?>">
    <script src="assets/js/local-time.js?v=<?= ASSET_VERSION ?>"></script>
</head>
<body>
    <div class="app-shell">
        <?php $sidebarActive = 'list'; include __DIR__ . '/includes/employer-sidebar.php'; ?>
        <main class="app-main">
        <?php include __DIR__ . '/includes/employer-header.php'; ?>
        <div class="app-main-content layout-app layout-app-wide">

        <?php $jobStatus = $job['status'] ?? 'active'; ?>
        
        <?php if ($jobStatus === 'draft'): ?>
            <div style="background:#fef3c7;color:#92400e;padding:1rem 1.25rem;border-radius:8px;margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
                <div>
                    <strong>📝 Brouillon</strong> — Ce poste n'est pas encore visible par les candidats.
                </div>
                <form method="POST" style="margin:0;">
                    <input type="hidden" name="new_status" value="active">
                    <button type="submit" style="background:#16a34a;color:white;border:none;padding:0.6rem 1.25rem;border-radius:0.5rem;font-weight:600;cursor:pointer;">
                        ✓ Publier le poste
                    </button>
                </form>
            </div>
        <?php elseif ($statusUpdated): ?>
            <div style="background:#d1fae5;color:#065f46;padding:0.75rem 1rem;border-radius:8px;margin-bottom:1.5rem;font-size:0.9rem;">
                ✓ Statut mis à jour avec succès.
            </div>
        <?php endif; ?>
        <?php if ($emailSent === true): ?>
            <div style="background:#d1fae5;color:#065f46;padding:0.75rem 1rem;border-radius:8px;margin-bottom:1.5rem;font-size:0.9rem;">
                ✓ Offre envoyée par courriel.
            </div>
        <?php elseif ($emailSent === false): ?>
            <div style="background:#fee2e2;color:#b91c1c;padding:0.75rem 1rem;border-radius:8px;margin-bottom:1.5rem;font-size:0.9rem;">
                Erreur lors de l'envoi du courriel. Vérifiez l'adresse et réessayez.
            </div>
        <?php endif; ?>

        <div class="job-detail-card">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:1rem;">
                <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                    <h1 style="margin:0;"><?= htmlspecialchars($job['title']) ?></h1>
                    <button type="button" id="sendJobEmailBtn" class="job-title-email-btn" aria-label="Envoyer le poste par courriel" title="Envoyer le poste par courriel">
                        <svg class="job-title-email-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    </button>
                </div>
                <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                    <a href="employer-job-create.php?id=<?= (int)$jobId ?>" class="job-title-edit-btn" title="Modifier le poste" aria-label="Modifier le poste">
                        <svg class="job-title-edit-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        <span>Modifier</span>
                    </a>
                    <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce poste ? Cette action est irréversible.');" style="margin:0;">
                        <input type="hidden" name="delete_job" value="confirm">
                        <button type="submit" style="background:#ef4444;color:white;border:none;padding:0.5rem 1rem;border-radius:0.5rem;font-size:0.85rem;cursor:pointer;display:flex;align-items:center;gap:0.3rem;">
                            🗑 Supprimer
                        </button>
                    </form>
                </div>
            </div>
            <div class="job-details-collapse">
                <button type="button" class="job-details-collapse-trigger" id="jobDetailsTrigger" aria-expanded="false" aria-controls="jobDetailsContent">
                    <span>Statut, JobMarket, lieu, description et questions</span>
                    <svg class="job-details-collapse-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="job-details-collapse-content" id="jobDetailsContent" hidden>
            <form method="POST" class="job-status-bar">
                <span class="label">Statut de l'affichage :</span>
                <div class="status-btns">
                    <button type="submit" name="new_status" value="draft" class="status-btn <?= $jobStatus === 'draft' ? 'active' : '' ?>">Brouillon</button>
                    <button type="submit" name="new_status" value="active" class="status-btn <?= $jobStatus === 'active' ? 'active' : '' ?>">En cours</button>
                    <button type="submit" name="new_status" value="closed" class="status-btn <?= $jobStatus === 'closed' ? 'active' : '' ?>">Fermé</button>
                </div>
            </form>
            <form method="POST" class="job-jobmarket-bar" style="display:flex;align-items:center;gap:0.75rem;margin-top:1rem;flex-wrap:wrap;">
                <input type="hidden" name="toggle_jobmarket" value="1">
                <input type="hidden" name="show_on_jobmarket" id="jobmarketVal" value="<?= $showOnJobMarket ?>">
                <div class="jobmarket-toggle-wrap" style="position:relative;display:inline-flex;align-items:center;gap:0.5rem;">
                    <span style="font-weight:600;color:#111827;">Afficher sur le JobMarket</span>
                    <button type="button" id="jobmarketToggleBtn" class="jobmarket-toggle" role="switch" aria-checked="<?= $showOnJobMarket ? 'true' : 'false' ?>" aria-label="Afficher sur le JobMarket" title="Le JobMarket est l'espace de diffusion officiel de la plateforme CiaoCV. Les offres y sont visibles par l'ensemble des candidats inscrits." style="position:relative;width:56px;height:28px;border-radius:50px;border:2px solid #e5e7eb;background:<?= $showOnJobMarket ? '#2563EB' : '#e5e7eb' ?>;cursor:pointer;transition:background 0.2s, border-color 0.2s;flex-shrink:0;">
                        <span class="jobmarket-toggle-knob" style="position:absolute;top:2px;left:2px;width:20px;height:20px;border-radius:50%;background:#fff;box-shadow:0 2px 4px rgba(0,0,0,0.2);transition:transform 0.2s;transform:translateX(<?= $showOnJobMarket ? '28px' : '0' ?>);"></span>
                    </button>
                    <span class="jobmarket-tooltip" role="tooltip" style="visibility:hidden;position:absolute;left:0;top:100%;margin-top:6px;padding:0.5rem 0.75rem;background:#111827;color:#fff;font-size:0.8rem;border-radius:8px;max-width:280px;z-index:10;box-shadow:0 4px 12px rgba(0,0,0,0.15);">Le JobMarket est l'espace de diffusion officiel de la plateforme CiaoCV. Les offres y sont visibles par l'ensemble des candidats inscrits.</span>
                </div>
            </form>
            <?php
            $jobLat = isset($job['latitude']) && $job['latitude'] !== '' && $job['latitude'] !== null ? (float)$job['latitude'] : null;
            $jobLng = isset($job['longitude']) && $job['longitude'] !== '' && $job['longitude'] !== null ? (float)$job['longitude'] : null;
            if ($jobLat !== null && $jobLng !== null): ?>
            <div style="margin-top:1rem;padding:0.75rem 1rem;background:#f3f4f6;border-radius:8px;font-size:0.9rem;">
                <strong>📍 Lieu du poste</strong>
                <?php if (!empty($job['location_name'])): ?>
                    <div style="margin-top:0.25rem;color:#6B7280;"><?= htmlspecialchars($job['location_name']) ?></div>
                <?php endif; ?>
                <div style="margin-top:0.25rem;color:#6B7280;"><?= number_format($jobLat, 5) ?>, <?= number_format($jobLng, 5) ?></div>
            </div>
            <?php endif; ?>
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
            
            <!-- Évaluateurs (dans la section détails du poste) -->
            <div style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid var(--border, #e5e7eb);">
                <h4 style="margin:0 0 0.75rem 0;font-size:0.95rem;">👥 Évaluateurs</h4>
                <?php if ($evaluatorAdded): ?>
                    <div style="padding:0.5rem 0.75rem;background:#d1fae5;color:#065f46;border-radius:6px;margin-bottom:0.75rem;font-size:0.85rem;">✓ Évaluateur ajouté</div>
                <?php endif; ?>
                <form method="POST" style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:0.75rem;">
                    <input type="hidden" name="add_evaluator" value="1">
                    <input type="email" name="evaluator_email" placeholder="email@exemple.com" required 
                           style="flex:1;min-width:180px;padding:0.5rem 0.75rem;border:1px solid #e5e7eb;border-radius:6px;font-size:0.9rem;">
                    <button type="submit" class="btn" style="padding:0.5rem 1rem;">+ Ajouter</button>
                </form>
                <?php if (!empty($evaluators)): ?>
                <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
                    <?php foreach ($evaluators as $eval): ?>
                        <span style="padding:0.35rem 0.65rem;background:#e0e7ff;color:#4338ca;border-radius:6px;font-size:0.85rem;">
                            <?= htmlspecialchars($eval['email']) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p style="font-size:0.85rem;color:var(--text-secondary);margin:0;">Aucun évaluateur pour le moment</p>
                <?php endif; ?>
            </div>
                </div>
            </div>
        </div>

        <?php if ($noteAdded): ?>
            <div style="padding:0.75rem 1rem;background:#d1fae5;color:#065f46;border-radius:8px;margin-bottom:1rem;font-size:0.9rem;">
                ✓ Note ajoutée
            </div>
        <?php endif; ?>

        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
            <h2 style="margin: 0;">Candidats (<?= count($candidates) ?>)</h2>
            <?php if (!empty($candidates)): ?>
            <div class="filter-bar">
                <button type="button" class="filter-btn active" data-filter="all">Tous</button>
                <button type="button" class="filter-btn" data-filter="new">NON TRAITÉ</button>
                <button type="button" class="filter-btn" data-filter="rejected">REFUSÉ</button>
                <button type="button" class="filter-btn" data-filter="accepted">ACCEPTÉ</button>
                <button type="button" class="filter-btn" data-filter="pool">BANQUE</button>
                <button type="button" class="filter-btn" data-filter="withdrawn">RETIRÉ</button>
            </div>
            <?php endif; ?>
        </div>

        <?php if (empty($candidates)): ?>
            <div class="empty-state">
                <p>Aucun candidat pour le moment.</p>
            </div>
        <?php else: ?>
            <div class="candidate-grid" id="candidateGrid">
                <?php foreach ($candidates as $i => $c): 
                    $cStatus = $c['status'] ?? 'new';
                    if ($cStatus === 'viewed') $cStatus = 'new';
                    $statusLabels = ['new' => 'NON TRAITÉ', 'viewed' => 'NON TRAITÉ', 'rejected' => 'REFUSÉ', 'accepted' => 'ACCEPTÉ', 'pool' => 'BANQUE', 'withdrawn' => 'RETIRÉ'];
                    $statusLabel = $statusLabels[$c['status'] ?? 'new'] ?? 'NON TRAITÉ';
                    $photoUrl = !empty($c['photo_url']) ? trim($c['photo_url']) : null;
                    $bioVideoUrl = !empty($c['bio_video_url']) ? trim($c['bio_video_url']) : null;
                    $createdAtRaw = trim($c['created_at'] ?? '');
                    $createdAtIso = $createdAtRaw !== '' ? preg_replace('/\s/', 'T', $createdAtRaw) . 'Z' : '';
                    $createdAtFallback = $createdAtRaw !== '' ? date('d/m/Y H:i', strtotime($createdAtRaw)) : '';
                    $phone = isset($c['phone']) ? trim($c['phone']) : '';
                ?>
                    <a href="employer-candidate-view.php?job=<?= $jobId ?>&idx=<?= $i ?>" class="candidate-grid-card candidate-with-notes" data-status="<?= htmlspecialchars($cStatus) ?>">
                        <div class="candidate-grid-photo">
                            <?php if ($photoUrl): ?>
                                <img src="<?= htmlspecialchars($photoUrl) ?>" alt="" class="candidate-grid-img">
                            <?php else: ?>
                                <span class="candidate-grid-initial"><?= strtoupper(mb_substr($c['candidate_name'] ?: '?', 0, 1)) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="candidate-grid-body">
                            <div class="candidate-grid-name"><?= htmlspecialchars($c['candidate_name'] ?: 'Sans nom') ?></div>
                            <?php if ($bioVideoUrl): ?>
                                <a href="<?= htmlspecialchars($bioVideoUrl) ?>" class="candidate-grid-video-link" target="_blank" rel="noopener" onclick="event.stopPropagation();">🎬 Vidéo bio</a>
                            <?php endif; ?>
                            <span class="candidate-grid-status tag-status tag-<?= htmlspecialchars($c['status'] ?? 'new') ?>"><?= $statusLabel ?></span>
                            <?php if ($createdAtIso !== ''): ?>
                                <div class="candidate-grid-date">Reçu le <time datetime="<?= htmlspecialchars($createdAtIso) ?>"><?= $createdAtFallback ?></time></div>
                            <?php endif; ?>
                            <?php if ($phone): ?>
                                <div class="candidate-grid-phone"><?= htmlspecialchars($phone) ?></div>
                            <?php endif; ?>
                            <div class="candidate-grid-email"><?= htmlspecialchars($c['candidate_email']) ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        </div>
        </main>
    </div>

    <div class="photo-modal" id="sendEmailModal" role="dialog" aria-labelledby="sendEmailModalTitle" aria-modal="true">
        <div class="photo-modal-backdrop" id="sendEmailModalBackdrop"></div>
        <div class="photo-modal-content">
            <div class="photo-modal-header">
                <h2 id="sendEmailModalTitle">Envoyer le poste par courriel</h2>
                <button type="button" class="photo-modal-close" id="sendEmailModalClose" aria-label="Fermer">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="send_job_email" value="1">
                <div class="form-group">
                    <label for="send_email_to">Courriel du destinataire *</label>
                    <input type="email" id="send_email_to" name="send_email_to" required placeholder="destinataire@exemple.com">
                </div>
                <div class="form-group">
                    <label for="send_email_message">Message personnel (optionnel)</label>
                    <textarea id="send_email_message" name="send_email_message" rows="3" placeholder="Un mot pour le destinataire..."></textarea>
                </div>
                <button type="submit" class="btn">Envoyer</button>
            </form>
        </div>
    </div>
    <script>
    (function() {
        var btn = document.getElementById('sendJobEmailBtn');
        var modal = document.getElementById('sendEmailModal');
        var backdrop = document.getElementById('sendEmailModalBackdrop');
        var closeBtn = document.getElementById('sendEmailModalClose');
        if (btn && modal) {
            btn.addEventListener('click', function() { modal.classList.add('active'); document.body.style.overflow = 'hidden'; });
            function closeModal() { modal.classList.remove('active'); document.body.style.overflow = ''; }
            if (backdrop) backdrop.addEventListener('click', closeModal);
            if (closeBtn) closeBtn.addEventListener('click', closeModal);
        }

        // Section repliable : Statut → Questions
        var detailsTrigger = document.getElementById('jobDetailsTrigger');
        var detailsContent = document.getElementById('jobDetailsContent');
        var detailsIcon = detailsTrigger && detailsTrigger.querySelector('.job-details-collapse-icon');
        if (detailsTrigger && detailsContent) {
            detailsTrigger.addEventListener('click', function() {
                var open = detailsContent.hidden;
                detailsContent.hidden = !open;
                detailsTrigger.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (detailsIcon) detailsIcon.style.transform = open ? 'rotate(180deg)' : 'rotate(0deg)';
            });
        }

        // Bouton JobMarket : toggle on/off + tooltip au survol
        var toggleBtn = document.getElementById('jobmarketToggleBtn');
        var jobmarketVal = document.getElementById('jobmarketVal');
        var wrap = toggleBtn && toggleBtn.closest('.jobmarket-toggle-wrap');
        var tooltip = wrap && wrap.querySelector('.jobmarket-tooltip');
        var knob = toggleBtn && toggleBtn.querySelector('.jobmarket-toggle-knob');
        if (toggleBtn && jobmarketVal) {
            toggleBtn.addEventListener('click', function() {
                var on = jobmarketVal.value === '1';
                on = !on;
                jobmarketVal.value = on ? '1' : '0';
                toggleBtn.setAttribute('aria-checked', on ? 'true' : 'false');
                toggleBtn.style.background = on ? '#2563EB' : '#e5e7eb';
                toggleBtn.style.borderColor = on ? '#2563EB' : '#e5e7eb';
                if (knob) knob.style.transform = on ? 'translateX(28px)' : 'translateX(0)';
                toggleBtn.closest('form').submit();
            });
            if (wrap && tooltip) {
                wrap.addEventListener('mouseenter', function() { tooltip.style.visibility = 'visible'; });
                wrap.addEventListener('mouseleave', function() { tooltip.style.visibility = 'hidden'; });
            }
        }
    })();
    </script>

    <?php if (!empty($candidates)): ?>
    <script>
        (function() {
            const btns = document.querySelectorAll('.filter-btn');
            const cards = document.querySelectorAll('.candidate-with-notes[data-status]');
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
