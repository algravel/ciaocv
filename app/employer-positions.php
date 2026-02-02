<?php
/**
 * Mes postes – descriptions de poste réutilisables pour les affichages.
 * L'employeur gère ici les postes (titre, description, type, lieu) puis les choisit
 * lors de la création d'un nouvel affichage.
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
if (isset($_SESSION['flash_success']))
    unset($_SESSION['flash_success']);
if (isset($_SESSION['flash_error']))
    unset($_SESSION['flash_error']);

// S'assurer que la table employer_positions existe
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
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_employer (employer_id)
                )
            ");
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

// API JSON pour les données d'un poste (pour le modal)
if (isset($_GET['api']) && $_GET['api'] === 'get' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $id = (int) $_GET['id'];
    $stmt = $db->prepare('SELECT * FROM employer_positions WHERE id = ? AND employer_id = ?');
    $stmt->execute([$id, $userId]);
    $pos = $stmt->fetch(PDO::FETCH_ASSOC);
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

// Soumission formulaire (ajout ou édition)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $jobType = trim($_POST['job_type'] ?? '');
    $workLocation = trim($_POST['work_location'] ?? '');

    if (!in_array($jobType, array_keys($jobTypes)))
        $jobType = null;
    if (!in_array($workLocation, array_keys($workLocations)))
        $workLocation = null;

    if (strlen($title) < 2) {
        $error = 'Le titre est requis (min. 2 caractères).';
    } else {
        try {
            if ($id) {
                $stmt = $db->prepare('UPDATE employer_positions SET title = ?, description = ?, job_type = ?, work_location = ? WHERE id = ? AND employer_id = ?');
                $stmt->execute([$title, $description ?: null, $jobType, $workLocation, $id, $userId]);
                if ($stmt->rowCount()) {
                    $_SESSION['flash_success'] = 'Poste mis à jour.';
                }
            } else {
                $stmt = $db->prepare('INSERT INTO employer_positions (employer_id, title, description, job_type, work_location) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$userId, $title, $description ?: null, $jobType, $workLocation]);
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
    <link rel="stylesheet"
        href="assets/css/design-system.css?v=<?= defined('ASSET_VERSION') ? ASSET_VERSION : time() ?>">
</head>

<body>
    <div class="app-shell">
        <?php $sidebarActive = 'positions';
        include __DIR__ . '/includes/employer-sidebar.php'; ?>
        <main class="app-main">
            <?php include __DIR__ . '/includes/employer-header.php'; ?>
            <div class="app-main-content layout-app">

                <div class="section-header" style="margin-bottom:1.5rem;">
                    <div>
                        <h1 style="margin-bottom:0.25rem;">Mes postes</h1>
                        <p class="hint">Définissez les postes de votre organisation. Sélectionnez-les lors de la
                            création d'un affichage.</p>
                    </div>
                    <button type="button" class="btn btn-primary" id="addPositionBtn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            style="margin-right:0.25rem;">
                            <line x1="12" y1="5" x2="12" y2="19" />
                            <line x1="5" y1="12" x2="19" y2="12" />
                        </svg>
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
                            <button type="button" class="btn btn-primary" onclick="openPositionModal()">Créer un
                                poste</button>
                        </div>
                    <?php else: ?>
                        <ul class="list-unstyled" style="display:flex;flex-direction:column;gap:0.75rem;">
                            <?php foreach ($positions as $p): ?>
                                <li class="card"
                                    style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
                                    <div style="flex:1;min-width:0;">
                                        <strong><?= htmlspecialchars($p['title']) ?></strong>
                                        <?php if ($p['job_type'] && isset($jobTypes[$p['job_type']])): ?>
                                            <span
                                                style="margin-left:0.5rem;color:var(--text-muted);font-size:0.9rem;"><?= $jobTypes[$p['job_type']]['icon'] ?>
                                                <?= $jobTypes[$p['job_type']]['title'] ?></span>
                                        <?php endif; ?>
                                        <?php if ($p['work_location'] && isset($workLocations[$p['work_location']])): ?>
                                            <span style="margin-left:0.25rem;color:var(--text-muted);font-size:0.9rem;"> ·
                                                <?= $workLocations[$p['work_location']]['icon'] ?>
                                                <?= $workLocations[$p['work_location']]['title'] ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($p['description'])): ?>
                                            <p
                                                style="margin:0.5rem 0 0;font-size:0.9rem;color:var(--text-secondary);max-height:3em;overflow:hidden;text-overflow:ellipsis;">
                                                <?= htmlspecialchars(mb_substr($p['description'], 0, 120)) ?>            <?= mb_strlen($p['description']) > 120 ? '…' : '' ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div style="display:flex;gap:0.5rem;flex-shrink:0;">
                                        <button type="button" class="btn btn-secondary" style="font-size:0.875rem;"
                                            onclick="openPositionModal(<?= (int) $p['id'] ?>)">Modifier</button>
                                        <a href="employer-positions.php?delete=<?= (int) $p['id'] ?>" class="btn btn-danger"
                                            style="font-size:0.875rem;"
                                            onclick="return confirm('Supprimer ce poste ?');">Supprimer</a>
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
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="positionModalTitle">Ajouter un poste</h2>
                <button type="button" class="modal-close" id="positionModalClose" aria-label="Fermer">&times;</button>
            </div>
            <form method="POST" id="positionForm">
                <input type="hidden" name="id" id="positionId" value="">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="pos_title">Titre du poste *</label>
                        <input type="text" id="pos_title" name="title" required
                            placeholder="Ex: Développeur Frontend React">
                    </div>
                    <div class="form-group">
                        <label for="pos_description">Description</label>
                        <textarea id="pos_description" name="description" rows="4"
                            placeholder="Responsabilités, exigences..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Type d'emploi</label>
                        <div class="option-cards"
                            style="display:grid;grid-template-columns:repeat(auto-fill, minmax(120px, 1fr));gap:0.5rem;margin-top:0.5rem;">
                            <?php foreach ($jobTypes as $value => $type): ?>
                                <label class="option-card" data-group="job_type"
                                    style="margin:0;cursor:pointer;padding:0.5rem;text-align:center;">
                                    <input type="radio" name="job_type" value="<?= $value ?>">
                                    <div class="option-icon" style="font-size:1.25rem;"><?= $type['icon'] ?></div>
                                    <div class="option-title" style="font-size:0.8rem;"><?= $type['title'] ?></div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Lieu de travail</label>
                        <div class="option-cards"
                            style="display:grid;grid-template-columns:repeat(3, 1fr);gap:0.5rem;margin-top:0.5rem;">
                            <?php foreach ($workLocations as $value => $loc): ?>
                                <label class="option-card" data-group="work_location"
                                    style="margin:0;cursor:pointer;padding:0.5rem;text-align:center;">
                                    <input type="radio" name="work_location" value="<?= $value ?>">
                                    <div class="option-icon" style="font-size:1.25rem;"><?= $loc['icon'] ?></div>
                                    <div class="option-title" style="font-size:0.8rem;"><?= $loc['title'] ?></div>
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
        (function () {
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

            window.openPositionModal = async function (id) {
                form.reset();
                document.querySelectorAll('.option-card').forEach(c => c.classList.remove('selected'));
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

            document.querySelectorAll('.option-card').forEach(function (card) {
                card.addEventListener('click', function () {
                    var group = this.getAttribute('data-group');
                    document.querySelectorAll('.option-card[data-group="' + group + '"]').forEach(function (c) { c.classList.remove('selected'); });
                    this.classList.add('selected');
                });
            });
        })();
    </script>
</body>

</html>