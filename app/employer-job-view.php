<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';

$jobId = (int)($_GET['id'] ?? 0);
$job = null;
$candidates = [];
$statusUpdated = false;
$emailSent = null;

// Créer les colonnes manquantes si besoin
if ($db) {
    $cols = $db->query("SHOW COLUMNS FROM jobs")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('show_on_esplanade', $cols)) {
        try { $db->exec("ALTER TABLE jobs ADD COLUMN show_on_esplanade TINYINT(1) DEFAULT 1 AFTER status"); } catch (PDOException $e) {}
    }
    if (!in_array('latitude', $cols)) {
        try { $db->exec("ALTER TABLE jobs ADD COLUMN latitude DECIMAL(10,8) DEFAULT NULL AFTER show_on_esplanade"); } catch (PDOException $e) {}
    }
    if (!in_array('longitude', $cols)) {
        try { $db->exec("ALTER TABLE jobs ADD COLUMN longitude DECIMAL(11,8) DEFAULT NULL AFTER latitude"); } catch (PDOException $e) {}
    }
    if (!in_array('location_name', $cols)) {
        try { $db->exec("ALTER TABLE jobs ADD COLUMN location_name VARCHAR(255) DEFAULT NULL AFTER longitude"); } catch (PDOException $e) {}
    }
}

// Traitement : afficher ou non sur l'Esplanade
if ($jobId && $db && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_esplanade'])) {
    $show = (int)($_POST['show_on_esplanade'] ?? 1);
    $show = $show ? 1 : 0;
    try {
        $stmt = $db->prepare('UPDATE jobs SET show_on_esplanade = ? WHERE id = ?');
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

            $stmtA = $db->prepare('SELECT * FROM applications WHERE job_id = ? ORDER BY created_at DESC');
            $stmtA->execute([$jobId]);
            $candidates = $stmtA->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $job = null;
    }
}

if (!$job) {
    header('Location: employer.php');
    exit;
}

$showOnEsplanade = isset($job['show_on_esplanade']) ? (int)$job['show_on_esplanade'] : 1;

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
            <form method="POST" class="job-status-bar">
                <span class="label">Statut de l'affichage :</span>
                <div class="status-btns">
                    <button type="submit" name="new_status" value="draft" class="status-btn <?= $jobStatus === 'draft' ? 'active' : '' ?>">Brouillon</button>
                    <button type="submit" name="new_status" value="active" class="status-btn <?= $jobStatus === 'active' ? 'active' : '' ?>">En cours</button>
                    <button type="submit" name="new_status" value="closed" class="status-btn <?= $jobStatus === 'closed' ? 'active' : '' ?>">Fermé</button>
                </div>
            </form>
            <form method="POST" class="job-esplanade-bar" style="display:flex;align-items:center;gap:0.75rem;margin-top:1rem;flex-wrap:wrap;">
                <input type="hidden" name="toggle_esplanade" value="1">
                <input type="hidden" name="show_on_esplanade" id="esplanadeVal" value="<?= $showOnEsplanade ?>">
                <div class="esplanade-toggle-wrap" style="position:relative;display:inline-flex;align-items:center;gap:0.5rem;">
                    <span style="font-weight:600;color:#111827;">Afficher sur l'Esplanade</span>
                    <button type="button" id="esplanadeToggleBtn" class="esplanade-toggle" role="switch" aria-checked="<?= $showOnEsplanade ? 'true' : 'false' ?>" aria-label="Afficher sur l'Esplanade" title="L'Esplanade est l'espace de diffusion officiel de la plateforme CiaoCV. Les offres y sont visibles par l'ensemble des candidats inscrits." style="position:relative;width:56px;height:28px;border-radius:50px;border:2px solid #e5e7eb;background:<?= $showOnEsplanade ? '#2563EB' : '#e5e7eb' ?>;cursor:pointer;transition:background 0.2s, border-color 0.2s;flex-shrink:0;">
                        <span class="esplanade-toggle-knob" style="position:absolute;top:2px;left:2px;width:20px;height:20px;border-radius:50%;background:#fff;box-shadow:0 2px 4px rgba(0,0,0,0.2);transition:transform 0.2s;transform:translateX(<?= $showOnEsplanade ? '28px' : '0' ?>);"></span>
                    </button>
                    <span class="esplanade-tooltip" role="tooltip" style="visibility:hidden;position:absolute;left:0;top:100%;margin-top:6px;padding:0.5rem 0.75rem;background:#111827;color:#fff;font-size:0.8rem;border-radius:8px;max-width:280px;z-index:10;box-shadow:0 4px 12px rgba(0,0,0,0.15);">L'Esplanade est l'espace de diffusion officiel de la plateforme CiaoCV. Les offres y sont visibles par l'ensemble des candidats inscrits.</span>
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

        // Bouton Esplanade : toggle on/off + tooltip au survol
        var toggleBtn = document.getElementById('esplanadeToggleBtn');
        var esplanadeVal = document.getElementById('esplanadeVal');
        var wrap = toggleBtn && toggleBtn.closest('.esplanade-toggle-wrap');
        var tooltip = wrap && wrap.querySelector('.esplanade-tooltip');
        var knob = toggleBtn && toggleBtn.querySelector('.esplanade-toggle-knob');
        if (toggleBtn && esplanadeVal) {
            toggleBtn.addEventListener('click', function() {
                var on = esplanadeVal.value === '1';
                on = !on;
                esplanadeVal.value = on ? '1' : '0';
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
