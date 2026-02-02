<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$userId = (int) $_SESSION['user_id'];

$jobId = (int) ($_GET['id'] ?? 0);
$job = null;
$error = null;

// Type d'emploi et lieu de travail (alignés sur l'onboarding candidat step2 pour le matching)
$jobTypes = [
    'full_time' => ['icon' => '💼', 'title' => 'Temps plein', 'desc' => '35-40h par semaine'],
    'part_time' => ['icon' => '⏰', 'title' => 'Temps partiel', 'desc' => 'Moins de 35h par semaine'],
    'shift' => ['icon' => '🔄', 'title' => 'Quart de travail', 'desc' => 'Horaires rotatifs'],
    'temporary' => ['icon' => '📅', 'title' => 'Temporaire', 'desc' => 'Contrat à durée déterminée'],
    'internship' => ['icon' => '🎓', 'title' => 'Stage', 'desc' => 'Formation en entreprise'],
];
$workLocations = [
    'on_site' => ['icon' => '🏢', 'title' => 'Sur place', 'desc' => 'Au bureau ou en magasin'],
    'remote' => ['icon' => '🏠', 'title' => 'Télétravail', 'desc' => '100% à distance'],
    'hybrid' => ['icon' => '🔀', 'title' => 'Hybride', 'desc' => 'Mix bureau et maison'],
];

// Liste des compétences populaires
$availableSkills = [
    'communication' => 'Communication',
    'travail_equipe' => 'Travail d\'équipe',
    'service_client' => 'Service client',
    'organisation' => 'Organisation',
    'gestion_temps' => 'Gestion du temps',
    'resolution_problemes' => 'Résolution de problèmes',
    'adaptabilite' => 'Adaptabilité',
    'leadership' => 'Leadership',
    'informatique' => 'Informatique / Bureautique',
    'vente' => 'Vente / Négociation',
    'langues' => 'Bilinguisme (FR/EN)',
    'creativite' => 'Créativité',
    'autonomie' => 'Autonomie',
    'rigueur' => 'Rigueur / Attention aux détails',
    'gestion_stress' => 'Gestion du stress'
];

// Charger le poste en mode édition (uniquement si appartient à l'employeur)
if ($jobId && $db) {
    try {
        $cols = $db->query("SHOW COLUMNS FROM jobs")->fetchAll(PDO::FETCH_COLUMN);
        $hasEmployerId = in_array('employer_id', $cols);
        $hasDeletedAt = in_array('deleted_at', $cols);
        if ($hasEmployerId) {
            if ($hasDeletedAt) {
                $stmt = $db->prepare('SELECT * FROM jobs WHERE id = ? AND employer_id = ? AND deleted_at IS NULL');
            } else {
                $stmt = $db->prepare('SELECT * FROM jobs WHERE id = ? AND employer_id = ?');
            }
            $stmt->execute([$jobId, $userId]);
        } else {
            if ($hasDeletedAt) {
                $stmt = $db->prepare('SELECT * FROM jobs WHERE id = ? AND deleted_at IS NULL');
            } else {
                $stmt = $db->prepare('SELECT * FROM jobs WHERE id = ?');
            }
            $stmt->execute([$jobId]);
        }
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($job) {
            $stmtQ = $db->prepare('SELECT question_text FROM job_questions WHERE job_id = ? ORDER BY sort_order');
            $stmtQ->execute([$jobId]);
            $job['questions'] = $stmtQ->fetchAll(PDO::FETCH_COLUMN);
            // Charger les compétences existantes
            $job['skills'] = $job['skills'] ? json_decode($job['skills'], true) : [];
        } else {
            header('Location: employer.php');
            exit;
        }
    } catch (PDOException $e) {
        header('Location: employer.php');
        exit;
    }
}
$isEdit = (bool) $job;

// Postes réutilisables (pour le sélecteur en création)
$employerPositions = [];
$selectedPosition = null;
if (!$isEdit && $db && isset($_SESSION['user_id'])) {
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'employer_positions'");
        if ($stmt->rowCount() > 0) {
            $stmt = $db->prepare('SELECT id, title FROM employer_positions WHERE employer_id = ? ORDER BY title');
            $stmt->execute([$userId]);
            $employerPositions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
    }
    $positionId = isset($_GET['position_id']) ? (int) $_GET['position_id'] : 0;
    if ($positionId) {
        $stmt = $db->prepare('SELECT * FROM employer_positions WHERE id = ? AND employer_id = ?');
        $stmt->execute([$positionId, $userId]);
        $selectedPosition = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $jobType = trim($_POST['job_type'] ?? '');
    $workLocation = trim($_POST['work_location'] ?? '');
    $questions = array_filter(array_map('trim', $_POST['questions'] ?? []));
    $latitude = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? (float) $_POST['latitude'] : null;
    $longitude = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? (float) $_POST['longitude'] : null;
    $location_name = trim($_POST['location_name'] ?? '') ?: null;
    $showOnJobMarket = isset($_POST['show_on_jobmarket']) && $_POST['show_on_jobmarket'] === '1' ? 1 : 0;
    $postId = (int) ($_POST['id'] ?? 0);
    $selectedSkills = isset($_POST['skills']) ? array_intersect($_POST['skills'], array_keys($availableSkills)) : [];
    $skillsJson = !empty($selectedSkills) ? json_encode(array_values($selectedSkills)) : null;

    if (!in_array($jobType, array_keys($jobTypes)))
        $jobType = null;
    if (!in_array($workLocation, array_keys($workLocations)))
        $workLocation = null;

    if (strlen($title) < 2) {
        $error = 'Le titre du poste est requis (min. 2 caractères).';
    } elseif (empty($jobType)) {
        $error = 'Choisissez un type d\'emploi pour le matching avec les candidats.';
    } elseif (empty($workLocation)) {
        $error = 'Choisissez un lieu de travail pour le matching avec les candidats.';
    } elseif (count($questions) > 5) {
        $error = 'Maximum 5 questions d\'entrevue.';
    } elseif ($db) {
        try {
            $cols = $db->query("SHOW COLUMNS FROM jobs")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('employer_id', $cols)) {
                $db->exec("ALTER TABLE jobs ADD COLUMN employer_id INT DEFAULT 1 AFTER id");
            }
            if (!in_array('status', $cols)) {
                $db->exec("ALTER TABLE jobs ADD COLUMN status ENUM('draft','active','closed') DEFAULT 'active' AFTER description");
            }
            if (in_array('show_on_esplanade', $cols) && !in_array('show_on_jobmarket', $cols)) {
                try {
                    $db->exec("ALTER TABLE jobs CHANGE COLUMN show_on_esplanade show_on_jobmarket TINYINT(1) DEFAULT 1");
                } catch (PDOException $e) {
                }
                $cols = $db->query("SHOW COLUMNS FROM jobs")->fetchAll(PDO::FETCH_COLUMN);
            }
            if (!in_array('show_on_jobmarket', $cols)) {
                try {
                    $db->exec("ALTER TABLE jobs ADD COLUMN show_on_jobmarket TINYINT(1) DEFAULT 1 AFTER status");
                } catch (PDOException $e) {
                }
                $cols = $db->query("SHOW COLUMNS FROM jobs")->fetchAll(PDO::FETCH_COLUMN);
            }
            if (!in_array('latitude', $cols)) {
                $db->exec("ALTER TABLE jobs ADD COLUMN latitude DECIMAL(10,8) DEFAULT NULL AFTER show_on_jobmarket");
            }
            if (!in_array('longitude', $cols)) {
                $db->exec("ALTER TABLE jobs ADD COLUMN longitude DECIMAL(11,8) DEFAULT NULL AFTER latitude");
            }
            if (!in_array('location_name', $cols)) {
                $db->exec("ALTER TABLE jobs ADD COLUMN location_name VARCHAR(255) DEFAULT NULL AFTER longitude");
            }
            if (!in_array('skills', $cols)) {
                $db->exec("ALTER TABLE jobs ADD COLUMN skills JSON DEFAULT NULL AFTER location_name");
            }
            if (!in_array('job_type', $cols)) {
                $db->exec("ALTER TABLE jobs ADD COLUMN job_type ENUM('full_time','part_time','shift','temporary','internship') DEFAULT NULL AFTER title");
                $cols = $db->query("SHOW COLUMNS FROM jobs")->fetchAll(PDO::FETCH_COLUMN);
            }
            if (!in_array('work_location', $cols)) {
                $db->exec("ALTER TABLE jobs ADD COLUMN work_location ENUM('on_site','remote','hybrid') DEFAULT NULL AFTER job_type");
            }

            if ($postId) {
                // Mise à jour
                $stmt = $db->prepare('UPDATE jobs SET title = ?, description = ?, job_type = ?, work_location = ?, show_on_jobmarket = ?, latitude = ?, longitude = ?, location_name = ?, skills = ? WHERE id = ?');
                $stmt->execute([$title, $description, $jobType ?: null, $workLocation ?: null, $showOnJobMarket, $latitude, $longitude, $location_name, $skillsJson, $postId]);
                $jobId = $postId;
                $db->prepare('DELETE FROM job_questions WHERE job_id = ?')->execute([$jobId]);
                foreach ($questions as $i => $q) {
                    if ($q !== '') {
                        try {
                            $stmtQ = $db->prepare('INSERT INTO job_questions (job_id, question_text, sort_order) VALUES (?, ?, ?)');
                            $stmtQ->execute([$jobId, $q, $i + 1]);
                        } catch (PDOException $qe) {
                        }
                    }
                }
            } else {
                // Création
                $stmt = $db->prepare('INSERT INTO jobs (employer_id, title, description, job_type, work_location, status, show_on_jobmarket, latitude, longitude, location_name, skills) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$userId, $title, $description, $jobType ?: null, $workLocation ?: null, 'draft', $showOnJobMarket, $latitude, $longitude, $location_name, $skillsJson]);
                $jobId = $db->lastInsertId();
                foreach ($questions as $i => $q) {
                    if ($q !== '') {
                        try {
                            $stmtQ = $db->prepare('INSERT INTO job_questions (job_id, question_text, sort_order) VALUES (?, ?, ?)');
                            $stmtQ->execute([$jobId, $q, $i + 1]);
                        } catch (PDOException $qe) {
                        }
                    }
                }
            }
            header('Location: employer-job-view.php?id=' . $jobId);
            exit;
        } catch (PDOException $e) {
            $error = 'Erreur DB: ' . $e->getMessage();
        }
    } else {
        $error = 'Base de données non connectée.';
    }
}

// Valeurs pour le formulaire (édition ou reprise après erreur ; POST prioritaire ; sinon poste choisi)
$formTitle = isset($_POST['title']) ? (string) $_POST['title'] : ($job['title'] ?? ($selectedPosition['title'] ?? ''));
$formDescription = isset($_POST['description']) ? (string) $_POST['description'] : ($job['description'] ?? ($selectedPosition['description'] ?? ''));
$formLat = isset($_POST['latitude']) ? (string) $_POST['latitude'] : (isset($job['latitude']) ? (string) $job['latitude'] : '');
$formLng = isset($_POST['longitude']) ? (string) $_POST['longitude'] : (isset($job['longitude']) ? (string) $job['longitude'] : '');
$formLocationName = isset($_POST['location_name']) ? (string) $_POST['location_name'] : ($job['location_name'] ?? '');
$formShowOnJobMarket = isset($_POST['show_on_jobmarket']) ? (int) $_POST['show_on_jobmarket'] : (isset($job['show_on_jobmarket']) ? (int) $job['show_on_jobmarket'] : 1);
$formQuestions = [];
for ($i = 0; $i < 5; $i++) {
    $formQuestions[$i] = isset($_POST['questions'][$i]) ? (string) $_POST['questions'][$i] : (isset($job['questions'][$i]) ? (string) $job['questions'][$i] : '');
}
$formSkills = isset($_POST['skills']) ? $_POST['skills'] : ($job['skills'] ?? []);
$formJobType = isset($_POST['job_type']) ? (string) $_POST['job_type'] : ($job['job_type'] ?? ($selectedPosition['job_type'] ?? ''));
$formWorkLocation = isset($_POST['work_location']) ? (string) $_POST['work_location'] : ($job['work_location'] ?? ($selectedPosition['work_location'] ?? ''));
$formPositionId = $selectedPosition ? (int) $selectedPosition['id'] : 0;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Modifier le poste' : 'Créer un poste' ?> - CiaoCV</title>
    <link rel="stylesheet" href="assets/css/design-system.css?v=<?= ASSET_VERSION ?>">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
</head>

<body>
    <div class="app-shell">
        <?php $sidebarActive = $isEdit ? 'list' : 'create';
        include __DIR__ . '/includes/employer-sidebar.php'; ?>
        <main class="app-main">
            <?php include __DIR__ . '/includes/employer-header.php'; ?>
            <div class="app-main-content layout-app">
                <h1><?= $isEdit ? 'Modifier le poste' : 'Créer un nouveau poste' ?></h1>

                <?php if ($error): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" id="jobCreateForm">
                    <?php if ($jobId): ?><input type="hidden" name="id" value="<?= (int) $jobId ?>"><?php endif; ?>
                    <?php if (!$isEdit && !empty($employerPositions)): ?>
                        <div class="form-group">
                            <label for="position_id">Choisir un poste (optionnel)</label>
                            <p class="hint">Sélectionnez un poste défini dans « Mes postes » pour pré-remplir le titre et la
                                description.</p>
                            <select id="position_id" name="position_id" class="form-control" style="max-width:400px;">
                                <option value="">— Créer sans partir d'un poste —</option>
                                <?php foreach ($employerPositions as $pos): ?>
                                    <option value="<?= (int) $pos['id'] ?>" <?= $formPositionId === (int) $pos['id'] ? 'selected' : '' ?>><?= htmlspecialchars($pos['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <script>
                                (function () {
                                    var sel = document.getElementById('position_id');
                                    if (sel) sel.addEventListener('change', function () {
                                        var v = this.value;
                                        if (v) window.location = 'employer-job-create.php?position_id=' + v;
                                    });
                                })();
                            </script>
                        </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="title">Titre du poste *</label>
                        <input type="text" id="title" name="title" required value="<?= htmlspecialchars($formTitle) ?>"
                            placeholder="Ex: Développeur Frontend React">
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description"
                            placeholder="Décrivez le poste, les responsabilités..."><?= htmlspecialchars($formDescription) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Type d'emploi *</label>
                        <p class="hint">Même critères que les candidats pour le matching.</p>
                        <div class="option-cards"
                            style="display:grid;grid-template-columns:repeat(auto-fill, minmax(140px, 1fr));gap:0.75rem;margin-top:0.5rem;">
                            <?php foreach ($jobTypes as $value => $type): ?>
                                <label class="option-card <?= $formJobType === $value ? 'selected' : '' ?>"
                                    data-group="job_type" style="margin:0;cursor:pointer;padding:0.75rem;">
                                    <input type="radio" name="job_type" value="<?= $value ?>" <?= $formJobType === $value ? 'checked' : '' ?> required>
                                    <div class="option-icon" style="font-size:1.25rem;"><?= $type['icon'] ?></div>
                                    <div class="option-text">
                                        <div class="option-title" style="font-size:0.9rem;"><?= $type['title'] ?></div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Lieu de travail *</label>
                        <p class="hint">Sur place, télétravail ou hybride — pour le matching avec les candidats.</p>
                        <div class="option-cards"
                            style="display:grid;grid-template-columns:repeat(3, 1fr);gap:0.75rem;margin-top:0.5rem;">
                            <?php foreach ($workLocations as $value => $loc): ?>
                                <label class="option-card <?= $formWorkLocation === $value ? 'selected' : '' ?>"
                                    data-group="work_location" style="margin:0;cursor:pointer;padding:0.75rem;">
                                    <input type="radio" name="work_location" value="<?= $value ?>"
                                        <?= $formWorkLocation === $value ? 'checked' : '' ?> required>
                                    <div class="option-icon" style="font-size:1.25rem;"><?= $loc['icon'] ?></div>
                                    <div class="option-text">
                                        <div class="option-title" style="font-size:0.9rem;"><?= $loc['title'] ?></div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Compétences recherchées</label>
                        <p class="hint">Sélectionnez les compétences clés pour ce poste</p>
                        <div class="skills-grid"
                            style="display:grid;grid-template-columns:repeat(auto-fill, minmax(200px, 1fr));gap:0.5rem;margin-top:0.75rem;">
                            <?php foreach ($availableSkills as $skillKey => $skillLabel):
                                $isChecked = in_array($skillKey, $formSkills);
                                ?>
                                <label class="skill-checkbox"
                                    style="display:flex;align-items:center;gap:0.5rem;padding:0.75rem 1rem;background:<?= $isChecked ? 'var(--primary-light, #dbeafe)' : 'var(--bg-alt, #f1f5f9)' ?>;border:2px solid <?= $isChecked ? 'var(--primary)' : 'transparent' ?>;border-radius:var(--radius, 8px);cursor:pointer;transition:all 0.2s;font-size:0.9rem;">
                                    <input type="checkbox" name="skills[]" value="<?= $skillKey ?>" <?= $isChecked ? 'checked' : '' ?> style="width:18px;height:18px;accent-color:var(--primary);">
                                    <span><?= htmlspecialchars($skillLabel) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group"
                        style="padding:1rem 1.25rem;background:var(--bg-alt, #f1f5f9);border-radius:var(--radius);">
                        <div
                            style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
                            <div>
                                <span style="font-weight:600;color:var(--text);">Afficher sur le JobMarket</span>
                                <p style="font-size:0.85rem;color:var(--text-secondary);margin-top:0.25rem;">Visible par
                                    les candidats inscrits</p>
                            </div>
                            <input type="hidden" name="show_on_jobmarket" id="jobmarketHidden"
                                value="<?= $formShowOnJobMarket ?>">
                            <button type="button" id="jobmarketToggleCreate" role="switch"
                                aria-checked="<?= $formShowOnJobMarket ? 'true' : 'false' ?>"
                                aria-label="Afficher sur le JobMarket"
                                style="position:relative;width:52px;height:28px;border-radius:50px;border:2px solid #e5e7eb;background:<?= $formShowOnJobMarket ? 'var(--primary)' : '#e5e7eb' ?>;cursor:pointer;transition:background 0.2s,border-color 0.2s;flex-shrink:0;">
                                <span
                                    style="position:absolute;top:2px;left:2px;width:20px;height:20px;border-radius:50%;background:#fff;box-shadow:0 2px 4px rgba(0,0,0,0.2);transition:transform 0.2s;transform:translateX(<?= $formShowOnJobMarket ? '22px' : '0' ?>);"></span>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Lieu du poste (géolocalisation)</label>
                        <p class="hint">Indiquez l'emplacement pour rapprocher candidats et employeurs. Cliquez sur la
                            carte ou utilisez « Ma position ».</p>
                        <input type="hidden" name="latitude" id="jobLatitude" value="<?= htmlspecialchars($formLat) ?>">
                        <input type="hidden" name="longitude" id="jobLongitude"
                            value="<?= htmlspecialchars($formLng) ?>">
                        <input type="hidden" name="location_name" id="jobLocationName"
                            value="<?= htmlspecialchars($formLocationName) ?>">
                        <div style="display:flex;flex-wrap:wrap;gap:0.5rem;margin-bottom:0.5rem;">
                            <button type="button" id="jobGeoBtn" class="btn btn-secondary" style="font-size:0.9rem;">📍
                                Ma position</button>
                            <span id="jobLocationLabel"
                                style="font-size:0.9rem;color:#6B7280;align-self:center;"></span>
                        </div>
                        <div id="jobMap"
                            style="height:280px;border-radius:12px;border:1px solid #e5e7eb;overflow:hidden;"></div>
                    </div>

                    <div class="form-group">
                        <label>Questions d'entrevue (max. 5)</label>
                        <p class="hint">Ces questions seront affichées aux candidats pour guider leur pitch vidéo.</p>
                        <div class="questions-list">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <div class="form-group">
                                    <label>Question <?= $i ?></label>
                                    <input type="text" name="questions[]"
                                        value="<?= htmlspecialchars($formQuestions[$i - 1] ?? '') ?>"
                                        placeholder="Ex: Parlez-nous de votre expérience avec React">
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="video_url">Vidéo de présentation du poste (optionnel)</label>
                        <p class="hint">Partagez un lien vers une vidéo présentant le poste (YouTube, Vimeo, Loom, etc.)
                        </p>
                        <input type="url" id="video_url" name="video_url"
                            value="<?= htmlspecialchars($formVideoUrl ?? '') ?>"
                            placeholder="https://www.youtube.com/watch?v=... ou https://vimeo.com/...">
                    </div>

                    <button type="submit"
                        class="btn"><?= $isEdit ? 'Enregistrer les modifications' : 'Créer le poste' ?></button>
                </form>

            </div>
        </main>
    </div>

    <script>
        // Sélection visuelle des cartes Type d'emploi / Lieu de travail
        document.querySelectorAll('.option-card').forEach(function (card) {
            card.addEventListener('click', function () {
                var group = this.getAttribute('data-group');
                document.querySelectorAll('.option-card[data-group="' + group + '"]').forEach(function (c) { c.classList.remove('selected'); });
                this.classList.add('selected');
            });
        });
    </script>
    <script>
        // Gestion des checkboxes de compétences
        document.querySelectorAll('.skill-checkbox input[type="checkbox"]').forEach(function (cb) {
            cb.addEventListener('change', function () {
                var label = this.closest('.skill-checkbox');
                if (this.checked) {
                    label.style.background = 'var(--primary-light, #dbeafe)';
                    label.style.borderColor = 'var(--primary)';
                } else {
                    label.style.background = 'var(--bg-alt, #f1f5f9)';
                    label.style.borderColor = 'transparent';
                }
            });
        });
    </script>
    <script>
        (function () {
            var toggle = document.getElementById('jobmarketToggleCreate');
            var hidden = document.getElementById('jobmarketHidden');
            if (toggle && hidden) {
                toggle.addEventListener('click', function () {
                    var on = hidden.value === '1';
                    on = !on;
                    hidden.value = on ? '1' : '0';
                    toggle.setAttribute('aria-checked', on ? 'true' : 'false');
                    toggle.style.background = on ? 'var(--primary)' : '#e5e7eb';
                    toggle.style.borderColor = on ? 'var(--primary)' : '#e5e7eb';
                    var knob = toggle.querySelector('span');
                    if (knob) knob.style.transform = on ? 'translateX(22px)' : 'translateX(0)';
                });
            }
        })();
    </script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        (function () {
            var mapEl = document.getElementById('jobMap');
            if (!mapEl) return;
            var defaultLat = 45.5017, defaultLng = -73.5673;
            var map = L.map('jobMap').setView([defaultLat, defaultLng], 10);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
            var marker = null;
            var latInput = document.getElementById('jobLatitude');
            var lngInput = document.getElementById('jobLongitude');
            var nameInput = document.getElementById('jobLocationName');
            var labelEl = document.getElementById('jobLocationLabel');

            function setMarker(lat, lng) {
                if (marker) map.removeLayer(marker);
                marker = L.marker([lat, lng]).addTo(map);
                latInput.value = lat;
                lngInput.value = lng;
                labelEl.textContent = lat.toFixed(5) + ', ' + lng.toFixed(5);
                fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng + '&zoom=18&addressdetails=1', {
                    headers: { 'Accept': 'application/json', 'Accept-Language': 'fr' }
                })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data && data.display_name) {
                            nameInput.value = data.display_name;
                            labelEl.textContent = data.display_name;
                        }
                    })
                    .catch(function () { });
            }

            map.on('click', function (e) {
                setMarker(e.latlng.lat, e.latlng.lng);
            });

            document.getElementById('jobGeoBtn').addEventListener('click', function () {
                if (!navigator.geolocation) {
                    labelEl.textContent = 'Géolocalisation non supportée.';
                    return;
                }
                labelEl.textContent = 'Localisation…';
                navigator.geolocation.getCurrentPosition(
                    function (pos) {
                        var lat = pos.coords.latitude;
                        var lng = pos.coords.longitude;
                        map.setView([lat, lng], 14);
                        setMarker(lat, lng);
                    },
                    function () {
                        labelEl.textContent = 'Impossible d\'obtenir la position. Cliquez sur la carte.';
                    }
                );
            });

            var savedLat = parseFloat(latInput.value);
            var savedLng = parseFloat(lngInput.value);
            if (!isNaN(savedLat) && !isNaN(savedLng)) {
                map.setView([savedLat, savedLng], 14);
                setMarker(savedLat, savedLng);
            }
        })();
    </script>
</body>

</html>