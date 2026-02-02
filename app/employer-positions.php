<?php
/**
 * Mes postes – descriptions de poste réutilisables pour les affichages.
 * Inclut compétences, traits de personnalité et disponibilités recherchées.
 */
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$success = $_SESSION['flash_success'] ?? null;
$error = $_SESSION['flash_error'] ?? null;
if (isset($_SESSION['flash_success'])) unset($_SESSION['flash_success']);
if (isset($_SESSION['flash_error'])) unset($_SESSION['flash_error']);

// S'assurer que les tables existent
if ($db) {
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'employer_positions'");
        if ($stmt->rowCount() === 0) {
            $db->exec("
                CREATE TABLE employer_positions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    employer_id INT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    description TEXT DEFAULT NULL,
                    job_type ENUM('full_time','part_time','shift','temporary','internship') DEFAULT NULL,
                    work_location ENUM('on_site','remote','hybrid') DEFAULT NULL,
                    skills_required TEXT DEFAULT NULL,
                    traits_required TEXT DEFAULT NULL,
                    slots_required TEXT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_employer (employer_id)
                )
            ");
        } else {
            // Ajouter les colonnes si elles n'existent pas
            $cols = $db->query("SHOW COLUMNS FROM employer_positions")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('skills_required', $cols)) {
                $db->exec("ALTER TABLE employer_positions ADD COLUMN skills_required TEXT DEFAULT NULL");
            }
            if (!in_array('traits_required', $cols)) {
                $db->exec("ALTER TABLE employer_positions ADD COLUMN traits_required TEXT DEFAULT NULL");
            }
            if (!in_array('slots_required', $cols)) {
                $db->exec("ALTER TABLE employer_positions ADD COLUMN slots_required TEXT DEFAULT NULL");
            }
        }
    } catch (PDOException $e) {
        $error = $error ?: 'Erreur base de données: ' . $e->getMessage();
    }
}

$jobTypes = [
    'full_time' => ['icon' => '💼', 'title' => 'Temps plein'],
    'part_time' => ['icon' => '⏰', 'title' => 'Temps partiel'],
    'shift' => ['icon' => '🔄', 'title' => 'Quart de travail'],
    'temporary' => ['icon' => '📅', 'title' => 'Temporaire'],
    'internship' => ['icon' => '🎓', 'title' => 'Stage'],
];
$workLocations = [
    'on_site' => ['icon' => '🏢', 'title' => 'Sur place'],
    'remote' => ['icon' => '🏠', 'title' => 'Télétravail'],
    'hybrid' => ['icon' => '🔀', 'title' => 'Hybride'],
];

$defaultSkills = [
    'Service client', 'Vente', 'Administration', 'Manutention', 
    'Cuisine', 'IT / Informatique', 'Santé', 'Construction',
    'Marketing', 'Comptabilité', 'Logistique', 'Mécanique',
    'Design', 'Communication', 'Gestion', 'Ressources humaines'
];

$traits = [
    'team_player' => ['icon' => '👥', 'title' => 'Travail d\'équipe'],
    'result_oriented' => ['icon' => '🎯', 'title' => 'Orienté résultats'],
    'customer_friendly' => ['icon' => '😊', 'title' => 'Bon avec les clients'],
    'fast_efficient' => ['icon' => '⚡', 'title' => 'Rapide et efficace'],
    'organized' => ['icon' => '📋', 'title' => 'Organisé'],
    'fast_learner' => ['icon' => '🧠', 'title' => 'Apprends vite'],
];

$timeSlots = [
    'day' => ['icon' => '☀️', 'title' => 'Jour', 'desc' => '6h - 14h'],
    'evening' => ['icon' => '🌅', 'title' => 'Soir', 'desc' => '14h - 22h'],
    'night' => ['icon' => '🌙', 'title' => 'Nuit', 'desc' => '22h - 6h'],
    'weekend' => ['icon' => '📆', 'title' => 'Fin de semaine', 'desc' => 'Sam-Dim'],
];

// API JSON pour les données d'un poste
if (isset($_GET['api']) && $_GET['api'] === 'get' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $id = (int) $_GET['id'];
    $stmt = $db->prepare('SELECT * FROM employer_positions WHERE id = ? AND employer_id = ?');
    $stmt->execute([$id, $userId]);
    $pos = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($pos) {
        $pos['skills_required'] = $pos['skills_required'] ? json_decode($pos['skills_required'], true) : [];
        $pos['traits_required'] = $pos['traits_required'] ? json_decode($pos['traits_required'], true) : [];
        $pos['slots_required'] = $pos['slots_required'] ? json_decode($pos['slots_required'], true) : [];
    }
    echo json_encode($pos ?: ['error' => 'Not found']);
    exit;
}

// Suppression
$deleteId = isset($_GET['delete']) ? (int) $_GET['delete'] : 0;
if ($deleteId && $db) {
    $stmt = $db->prepare('DELETE FROM employer_positions WHERE id = ? AND employer_id = ?');
    $stmt->execute([$deleteId, $userId]);
    if ($stmt->rowCount()) {
        $_SESSION['flash_success'] = 'Poste supprimé.';
    } else {
        $_SESSION['flash_error'] = 'Impossible de supprimer ce poste.';
    }
    header('Location: employer-positions.php');
    exit;
}

// Soumission formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $jobType = trim($_POST['job_type'] ?? '');
    $workLocation = trim($_POST['work_location'] ?? '');
    $skillsRequired = isset($_POST['skills']) ? json_encode($_POST['skills']) : null;
    $traitsRequired = isset($_POST['traits']) ? json_encode($_POST['traits']) : null;
    $slotsRequired = isset($_POST['slots']) ? json_encode($_POST['slots']) : null;

    if (!in_array($jobType, array_keys($jobTypes))) $jobType = null;
    if (!in_array($workLocation, array_keys($workLocations))) $workLocation = null;

    if (strlen($title) < 2) {
        $error = 'Le titre est requis (min. 2 caractères).';
    } else {
        try {
            if ($id) {
                $stmt = $db->prepare('UPDATE employer_positions SET title = ?, description = ?, job_type = ?, work_location = ?, skills_required = ?, traits_required = ?, slots_required = ? WHERE id = ? AND employer_id = ?');
                $stmt->execute([$title, $description ?: null, $jobType, $workLocation, $skillsRequired, $traitsRequired, $slotsRequired, $id, $userId]);
                $_SESSION['flash_success'] = 'Poste mis à jour.';
            } else {
                $stmt = $db->prepare('INSERT INTO employer_positions (employer_id, title, description, job_type, work_location, skills_required, traits_required, slots_required) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$userId, $title, $description ?: null, $jobType, $workLocation, $skillsRequired, $traitsRequired, $slotsRequired]);
                $_SESSION['flash_success'] = 'Poste ajouté.';
            }
            header('Location: employer-positions.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Erreur: ' . $e->getMessage();
        }
    }
}

// Liste des postes
$positions = [];
if ($db) {
    $stmt = $db->prepare('SELECT * FROM employer_positions WHERE employer_id = ? ORDER BY title');
    $stmt->execute([$userId]);
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes postes - CiaoCV</title>
    <link rel="stylesheet" href="assets/css/design-system.css?v=<?= defined('ASSET_VERSION') ? ASSET_VERSION : time() ?>">
</head>
<body>
    <div class="app-shell">
        <?php $sidebarActive = 'positions'; include __DIR__ . '/includes/employer-sidebar.php'; ?>
        <main class="app-main">
            <?php include __DIR__ . '/includes/employer-header.php'; ?>
            <div class="app-main-content layout-app">

                <div class="section-header" style="margin-bottom:1.5rem;">
                    <div>
                        <h1 style="margin-bottom:0.25rem;">Mes postes</h1>
                        <p class="hint">Définissez les postes de votre organisation avec les compétences et qualités recherchées.</p>
                    </div>
                    <button type="button" class="btn btn-primary" id="addPositionBtn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:0.25rem;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Ajouter un poste
                    </button>
                </div>

                <?php if ($success): ?>
                    <div class="success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <section>
                    <?php if (empty($positions)): ?>
                        <div class="empty-state" style="text-align:center;padding:3rem 1rem;">
                            <div style="font-size:3rem;margin-bottom:1rem;">📋</div>
                            <h3>Aucun poste</h3>
                            <p class="hint">Créez votre premier poste pour commencer.</p>
                            <button type="button" class="btn btn-primary" onclick="openPositionModal()">Créer un poste</button>
                        </div>
                    <?php else: ?>
                        <ul class="list-unstyled" style="display:flex;flex-direction:column;gap:0.75rem;">
                            <?php foreach ($positions as $p): ?>
                                <li class="card" style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
                                    <div style="flex:1;min-width:0;">
                                        <strong><?= htmlspecialchars($p['title']) ?></strong>
                                        <?php if ($p['job_type'] && isset($jobTypes[$p['job_type']])): ?>
                                            <span style="margin-left:0.5rem;color:var(--text-muted);font-size:0.9rem;"><?= $jobTypes[$p['job_type']]['icon'] ?> <?= $jobTypes[$p['job_type']]['title'] ?></span>
                                        <?php endif; ?>
                                        <?php if ($p['work_location'] && isset($workLocations[$p['work_location']])): ?>
                                            <span style="margin-left:0.25rem;color:var(--text-muted);font-size:0.9rem;"> · <?= $workLocations[$p['work_location']]['icon'] ?> <?= $workLocations[$p['work_location']]['title'] ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($p['description'])): ?>
                                            <p style="margin:0.5rem 0 0;font-size:0.9rem;color:var(--text-secondary);max-height:3em;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars(mb_substr($p['description'], 0, 120)) ?><?= mb_strlen($p['description']) > 120 ? '…' : '' ?></p>
                                        <?php endif; ?>
                                        <?php
                                        $skills = $p['skills_required'] ? json_decode($p['skills_required'], true) : [];
                                        $ptraits = $p['traits_required'] ? json_decode($p['traits_required'], true) : [];
                                        if (!empty($skills) || !empty($ptraits)):
                                        ?>
                                        <div style="margin-top:0.5rem;display:flex;flex-wrap:wrap;gap:0.25rem;">
                                            <?php foreach (array_slice($skills, 0, 3) as $sk): ?>
                                                <span class="tag" style="font-size:0.75rem;padding:0.2rem 0.5rem;"><?= htmlspecialchars($sk) ?></span>
                                            <?php endforeach; ?>
                                            <?php foreach (array_slice($ptraits, 0, 2) as $tr): ?>
                                                <?php if (isset($traits[$tr])): ?>
                                                    <span class="tag" style="font-size:0.75rem;padding:0.2rem 0.5rem;"><?= $traits[$tr]['icon'] ?></span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                            <?php if (count($skills) + count($ptraits) > 5): ?>
                                                <span style="font-size:0.75rem;color:var(--text-muted);">+<?= count($skills) + count($ptraits) - 5 ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div style="display:flex;gap:0.5rem;flex-shrink:0;">
                                        <button type="button" class="btn btn-secondary" style="font-size:0.875rem;" onclick="openPositionModal(<?= (int)$p['id'] ?>)">Modifier</button>
                                        <a href="employer-positions.php?delete=<?= (int)$p['id'] ?>" class="btn btn-danger" style="font-size:0.875rem;" onclick="return confirm('Supprimer ce poste ?');">Supprimer</a>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>

            </div>
        </main>
    </div>

    <!-- Modal Ajout/Édition de poste -->
    <div class="modal" id="positionModal" role="dialog" aria-labelledby="positionModalTitle" aria-modal="true">
        <div class="modal-backdrop" id="positionModalBackdrop"></div>
        <div class="modal-content" style="max-width:640px;">
            <div class="modal-header">
                <h2 id="positionModalTitle">Ajouter un poste</h2>
                <button type="button" class="modal-close" id="positionModalClose" aria-label="Fermer">&times;</button>
            </div>
            <form method="POST" id="positionForm">
                <input type="hidden" name="id" id="positionId" value="">
                <div class="modal-body" style="max-height:60vh;overflow-y:auto;">
                    <div class="form-group">
                        <label for="pos_title">Titre du poste *</label>
                        <input type="text" id="pos_title" name="title" required placeholder="Ex: Développeur Frontend React">
                    </div>
                    <div class="form-group">
                        <label for="pos_description">Description</label>
                        <textarea id="pos_description" name="description" rows="3" placeholder="Responsabilités, exigences..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Type d'emploi</label>
                        <div class="option-cards" style="display:grid;grid-template-columns:repeat(auto-fill, minmax(100px, 1fr));gap:0.5rem;margin-top:0.5rem;">
                            <?php foreach ($jobTypes as $value => $type): ?>
                                <label class="option-card" data-group="job_type" style="margin:0;cursor:pointer;padding:0.5rem;text-align:center;">
                                    <input type="radio" name="job_type" value="<?= $value ?>">
                                    <div class="option-icon" style="font-size:1.25rem;"><?= $type['icon'] ?></div>
                                    <div class="option-title" style="font-size:0.75rem;"><?= $type['title'] ?></div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Lieu de travail</label>
                        <div class="option-cards" style="display:grid;grid-template-columns:repeat(3, 1fr);gap:0.5rem;margin-top:0.5rem;">
                            <?php foreach ($workLocations as $value => $loc): ?>
                                <label class="option-card" data-group="work_location" style="margin:0;cursor:pointer;padding:0.5rem;text-align:center;">
                                    <input type="radio" name="work_location" value="<?= $value ?>">
                                    <div class="option-icon" style="font-size:1.25rem;"><?= $loc['icon'] ?></div>
                                    <div class="option-title" style="font-size:0.75rem;"><?= $loc['title'] ?></div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <hr style="margin:1.25rem 0;border:none;border-top:1px solid var(--border);">

                    <div class="form-group">
                        <label>Compétences recherchées</label>
                        <div class="tags-container" style="display:flex;flex-wrap:wrap;gap:0.5rem;margin-top:0.5rem;">
                            <?php foreach ($defaultSkills as $skill): ?>
                            <label class="tag" style="cursor:pointer;">
                                <input type="checkbox" name="skills[]" value="<?= htmlspecialchars($skill) ?>" style="display:none;">
                                <?= htmlspecialchars($skill) ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Traits de personnalité recherchés</label>
                        <div class="trait-cards" style="display:grid;grid-template-columns:repeat(auto-fill, minmax(130px, 1fr));gap:0.5rem;margin-top:0.5rem;">
                            <?php foreach ($traits as $code => $trait): ?>
                            <label class="trait-card" style="cursor:pointer;padding:0.5rem;text-align:center;">
                                <input type="checkbox" name="traits[]" value="<?= $code ?>" style="display:none;">
                                <div class="trait-icon" style="font-size:1.25rem;"><?= $trait['icon'] ?></div>
                                <div class="trait-title" style="font-size:0.75rem;"><?= $trait['title'] ?></div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Disponibilités requises</label>
                        <div class="time-slots" style="display:grid;grid-template-columns:repeat(4, 1fr);gap:0.5rem;margin-top:0.5rem;">
                            <?php foreach ($timeSlots as $code => $slot): ?>
                            <label class="time-slot" style="cursor:pointer;padding:0.5rem;text-align:center;">
                                <input type="checkbox" name="slots[]" value="<?= $code ?>" style="display:none;">
                                <div class="slot-icon" style="font-size:1.25rem;"><?= $slot['icon'] ?></div>
                                <div class="slot-title" style="font-size:0.7rem;"><?= $slot['title'] ?></div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closePositionModal()">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="positionSubmitBtn">Ajouter</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    (function() {
        const modal = document.getElementById('positionModal');
        const backdrop = document.getElementById('positionModalBackdrop');
        const closeBtn = document.getElementById('positionModalClose');
        const addBtn = document.getElementById('addPositionBtn');
        const form = document.getElementById('positionForm');
        const titleEl = document.getElementById('positionModalTitle');
        const submitBtn = document.getElementById('positionSubmitBtn');
        const idInput = document.getElementById('positionId');

        function openModal() {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        function closeModal() {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        window.openPositionModal = async function(id) {
            form.reset();
            document.querySelectorAll('.option-card, .tag, .trait-card, .time-slot').forEach(c => c.classList.remove('selected'));
            idInput.value = '';

            if (id) {
                titleEl.textContent = 'Modifier le poste';
                submitBtn.textContent = 'Enregistrer';
                try {
                    const res = await fetch('employer-positions.php?api=get&id=' + id);
                    const data = await res.json();
                    if (data && !data.error) {
                        idInput.value = data.id;
                        document.getElementById('pos_title').value = data.title || '';
                        document.getElementById('pos_description').value = data.description || '';
                        
                        if (data.job_type) {
                            const jt = form.querySelector('input[name="job_type"][value="' + data.job_type + '"]');
                            if (jt) { jt.checked = true; jt.closest('.option-card').classList.add('selected'); }
                        }
                        if (data.work_location) {
                            const wl = form.querySelector('input[name="work_location"][value="' + data.work_location + '"]');
                            if (wl) { wl.checked = true; wl.closest('.option-card').classList.add('selected'); }
                        }
                        if (data.skills_required && Array.isArray(data.skills_required)) {
                            data.skills_required.forEach(sk => {
                                const cb = form.querySelector('input[name="skills[]"][value="' + sk + '"]');
                                if (cb) { cb.checked = true; cb.closest('.tag').classList.add('selected'); }
                            });
                        }
                        if (data.traits_required && Array.isArray(data.traits_required)) {
                            data.traits_required.forEach(tr => {
                                const cb = form.querySelector('input[name="traits[]"][value="' + tr + '"]');
                                if (cb) { cb.checked = true; cb.closest('.trait-card').classList.add('selected'); }
                            });
                        }
                        if (data.slots_required && Array.isArray(data.slots_required)) {
                            data.slots_required.forEach(sl => {
                                const cb = form.querySelector('input[name="slots[]"][value="' + sl + '"]');
                                if (cb) { cb.checked = true; cb.closest('.time-slot').classList.add('selected'); }
                            });
                        }
                    }
                } catch (e) { console.error(e); }
            } else {
                titleEl.textContent = 'Ajouter un poste';
                submitBtn.textContent = 'Ajouter';
            }
            openModal();
        };
        window.closePositionModal = closeModal;

        if (addBtn) addBtn.addEventListener('click', () => openPositionModal());
        closeBtn.addEventListener('click', closeModal);
        backdrop.addEventListener('click', closeModal);

        // Option cards (radio)
        document.querySelectorAll('.option-card').forEach(function(card) {
            card.addEventListener('click', function() {
                var group = this.getAttribute('data-group');
                document.querySelectorAll('.option-card[data-group="' + group + '"]').forEach(function(c) { c.classList.remove('selected'); });
                this.classList.add('selected');
            });
        });

        // Tags (checkbox)
        document.querySelectorAll('.tags-container .tag').forEach(function(tag) {
            tag.addEventListener('click', function() {
                const cb = this.querySelector('input[type="checkbox"]');
                cb.checked = !cb.checked;
                this.classList.toggle('selected', cb.checked);
            });
        });

        // Trait cards (checkbox)
        document.querySelectorAll('.trait-card').forEach(function(card) {
            card.addEventListener('click', function() {
                const cb = this.querySelector('input[type="checkbox"]');
                cb.checked = !cb.checked;
                this.classList.toggle('selected', cb.checked);
            });
        });

        // Time slots (checkbox)
        document.querySelectorAll('.time-slot').forEach(function(slot) {
            slot.addEventListener('click', function() {
                const cb = this.querySelector('input[type="checkbox"]');
                cb.checked = !cb.checked;
                this.classList.toggle('selected', cb.checked);
            });
        });
    })();
    </script>
</body>
</html>