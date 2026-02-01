<?php
/**
 * Profil candidat - données réelles
 */
session_start();
require_once __DIR__ . '/db.php';

// Vérifier si connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['user_id'];
$success = null;
$error = null;

// Charger B2 pour l'upload photo (modal)
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $value = trim($value, '"\'');
            $_ENV[trim($key)] = $value;
        }
    }
}
$b2KeyId = $_ENV['B2_KEY_ID'] ?? '';
$b2AppKey = $_ENV['B2_APPLICATION_KEY'] ?? '';
$b2BucketId = $_ENV['B2_BUCKET_ID'] ?? '';

// API upload photo (modal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    if ($_POST['action'] === 'get_upload_url') {
        $authUrl = 'https://api.backblazeb2.com/b2api/v2/b2_authorize_account';
        $credentials = base64_encode($b2KeyId . ':' . $b2AppKey);
        $ch = curl_init($authUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $authResponse = json_decode(curl_exec($ch), true);
        curl_close($ch);
        if (!isset($authResponse['authorizationToken'])) {
            echo json_encode(['error' => 'Échec auth']);
            exit;
        }
        $getUploadUrl = $authResponse['apiUrl'] . '/b2api/v2/b2_get_upload_url';
        $ch = curl_init($getUploadUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $authResponse['authorizationToken'],
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['bucketId' => $b2BucketId]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $uploadUrlResponse = json_decode(curl_exec($ch), true);
        curl_close($ch);
        $fileName = 'photo_' . $userId . '_' . date('Y-m-d_H-i-s') . '.jpg';
        echo json_encode([
            'uploadUrl' => $uploadUrlResponse['uploadUrl'],
            'authToken' => $uploadUrlResponse['authorizationToken'],
            'fileName' => $fileName,
            'downloadUrl' => $authResponse['downloadUrl']
        ]);
        exit;
    }
    if ($_POST['action'] === 'save_photo') {
        $photoUrl = trim($_POST['photo_url'] ?? '');
        if ($db && $photoUrl !== '') {
            $db->prepare('UPDATE users SET photo_url = ? WHERE id = ?')->execute([$photoUrl, $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'URL invalide']);
        }
        exit;
    }
}

// Charger les données utilisateur
$user = null;
if ($db) {
    $cols = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('preferred_language', $cols)) {
        try { $db->exec("ALTER TABLE users ADD COLUMN preferred_language VARCHAR(10) DEFAULT 'fr' AFTER onboarding_completed"); } catch (PDOException $e) {}
    }
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$user) {
    header('Location: index.php');
    exit;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_profile') {
        $firstName = trim($_POST['first_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $preferredLanguage = trim($_POST['preferred_language'] ?? 'fr');
        if (!in_array($preferredLanguage, ['fr', 'en'])) $preferredLanguage = 'fr';
        
        if (empty($firstName)) {
            $error = 'Le prénom est requis.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email invalide.';
        } else {
            try {
                // Vérifier si l'email est déjà utilisé par un autre utilisateur
                $stmt = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
                $stmt->execute([$email, $userId]);
                if ($stmt->fetch()) {
                    $error = 'Cet email est déjà utilisé.';
                } else {
                    $db->prepare('UPDATE users SET first_name = ?, email = ?, preferred_language = ? WHERE id = ?')
                       ->execute([$firstName, $email, $preferredLanguage, $userId]);
                    $success = 'Profil mis à jour.';
                    // Recharger les données
                    $user['first_name'] = $firstName;
                    $user['email'] = $email;
                    $user['preferred_language'] = $preferredLanguage;
                    $_SESSION['user_email'] = $email;
                }
            } catch (PDOException $e) {
                $error = 'Erreur lors de la mise à jour.';
            }
        }
    }
}

// Données du profil
$firstName = $user['first_name'] ?? '';
$email = $user['email'] ?? '';
$preferredLanguage = $user['preferred_language'] ?? 'fr';
$videoUrl = $user['video_url'] ?? null;
$photoUrl = $user['photo_url'] ?? null;
$jobType = $user['job_type'] ?? null;
$workLocation = $user['work_location'] ?? null;
$onboardingCompleted = (bool)$user['onboarding_completed'];

// Labels pour affichage
$jobTypeLabels = [
    'full_time' => 'Temps plein',
    'part_time' => 'Temps partiel',
    'shift' => 'Quart de travail',
    'temporary' => 'Temporaire',
    'internship' => 'Stage'
];
$workLocationLabels = [
    'on_site' => 'Sur place',
    'remote' => 'Télétravail',
    'hybrid' => 'Hybride'
];

// Charger les compétences
$skills = [];
if ($db) {
    $stmt = $db->prepare('SELECT skill_name, skill_level FROM user_skills WHERE user_id = ?');
    $stmt->execute([$userId]);
    $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Charger les traits de personnalité
$traits = [];
$traitLabels = [
    'team_player' => 'Travail d\'équipe',
    'result_oriented' => 'Orienté résultats',
    'customer_friendly' => 'Bon avec les clients',
    'fast_efficient' => 'Rapide et efficace',
    'organized' => 'Organisé',
    'fast_learner' => 'Apprends vite'
];
if ($db) {
    $stmt = $db->prepare('SELECT trait_code FROM user_traits WHERE user_id = ?');
    $stmt->execute([$userId]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $traits[] = $traitLabels[$row['trait_code']] ?? $row['trait_code'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon profil - CiaoCV</title>
    <link rel="stylesheet" href="assets/css/design-system.css?v=<?= ASSET_VERSION ?>">
</head>
<body>
    <div class="app-shell">
        <?php $sidebarActive = 'profile'; include __DIR__ . '/includes/candidate-sidebar.php'; ?>
        <main class="app-main">
        <?php include __DIR__ . '/includes/candidate-header.php'; ?>
        <div class="app-main-content layout-app">
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- En-tête du profil -->
        <div class="profile-header">
            <button type="button" class="profile-photo profile-photo-clickable" id="profilePhotoBtn" title="Cliquer pour changer la photo">
                <?php if ($photoUrl): ?>
                    <img src="<?= htmlspecialchars($photoUrl) ?>" alt="Photo de profil" id="profilePhotoImg">
                <?php else: ?>
                    <div class="placeholder" id="profilePhotoPlaceholder">👤</div>
                <?php endif; ?>
                <span class="profile-photo-edit-hint">Modifier</span>
            </button>
            <div class="profile-info">
                <h3><?= htmlspecialchars($firstName ?: 'Candidat') ?></h3>
                <p><?= htmlspecialchars($email) ?></p>
            </div>
        </div>

        <!-- Informations de base -->
        <div class="section">
            <h2>Informations <a href="onboarding/step2-job-type.php">Modifier →</a></h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                <div class="form-group">
                    <label>Prénom</label>
                    <input type="text" name="first_name" value="<?= htmlspecialchars($firstName) ?>" placeholder="Votre prénom" required>
                </div>
                <div class="form-group">
                    <label>Courriel</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="votre@email.com" required>
                </div>
                <div class="form-group">
                    <label for="preferred_language">Langue</label>
                    <select id="preferred_language" name="preferred_language">
                        <option value="fr" <?= $preferredLanguage === 'fr' ? 'selected' : '' ?>>Français</option>
                        <option value="en" <?= $preferredLanguage === 'en' ? 'selected' : '' ?>>English</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Sauvegarder</button>
            </form>
        </div>

        <!-- Préférences d'emploi -->
        <div class="section">
            <h2>Préférences <a href="onboarding/step2-job-type.php">Modifier →</a></h2>
            <div class="info-grid">
                <div class="info-item">
                    <label>Type d'emploi</label>
                    <span><?= $jobTypeLabels[$jobType] ?? 'Non défini' ?></span>
                </div>
                <div class="info-item">
                    <label>Lieu de travail</label>
                    <span><?= $workLocationLabels[$workLocation] ?? 'Non défini' ?></span>
                </div>
            </div>
        </div>

        <!-- Compétences -->
        <?php if (!empty($skills)): ?>
        <div class="section">
            <h2>Compétences <a href="onboarding/step3-skills.php">Modifier →</a></h2>
            <div class="tags">
                <?php foreach ($skills as $skill): ?>
                    <span class="tag"><?= htmlspecialchars($skill['skill_name']) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Personnalité -->
        <?php if (!empty($traits)): ?>
        <div class="section">
            <h2>Personnalité <a href="onboarding/step4-personality.php">Modifier →</a></h2>
            <div class="tags">
                <?php foreach ($traits as $trait): ?>
                    <span class="tag"><?= htmlspecialchars($trait) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Vidéo de présentation -->
        <div class="section">
            <h2>Ma vidéo <a href="onboarding/step6-video.php">Modifier →</a></h2>
            <div class="video-preview">
                <?php if ($videoUrl): ?>
                    <video controls src="<?= htmlspecialchars($videoUrl) ?>"></video>
                <?php else: ?>
                    <div class="no-video">
                        <span>🎬</span>
                        <p>Aucune vidéo</p>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (!$videoUrl): ?>
                <a href="onboarding/step6-video.php" class="btn btn-primary">Enregistrer ma vidéo</a>
            <?php endif; ?>
        </div>

        </div>
        </main>
    </div>

    <!-- Modal remplacement photo -->
    <div class="photo-modal" id="photoModal" role="dialog" aria-labelledby="photoModalTitle" aria-modal="true">
        <div class="photo-modal-backdrop" id="photoModalBackdrop"></div>
        <div class="photo-modal-content">
            <div class="photo-modal-header">
                <h2 id="photoModalTitle">Changer la photo de profil</h2>
                <button type="button" class="photo-modal-close" id="photoModalClose" aria-label="Fermer">&times;</button>
            </div>
            <div class="photo-modal-body">
                <div class="photo-modal-preview" id="photoModalContainer">
                    <video id="photoModalCamera" class="hidden" autoplay playsinline muted></video>
                    <img id="photoModalPreview" src="" alt="Aperçu" class="hidden">
                    <div class="photo-modal-placeholder" id="photoModalPlaceholder">
                        <span class="icon">👤</span>
                        <span>Aperçu</span>
                    </div>
                </div>
                <div class="photo-modal-options" id="photoModalOptions">
                    <label class="photo-option" id="photoModalTake">
                        <span class="icon">📸</span>
                        <span>Prendre une photo</span>
                    </label>
                    <label class="photo-option">
                        <span class="icon">📁</span>
                        <span>Téléverser une image</span>
                        <input type="file" id="photoModalFile" accept="image/*">
                    </label>
                </div>
                <div id="photoModalCameraControls" class="hidden" style="display:flex;gap:0.5rem;margin-top:1rem;">
                    <button type="button" class="btn btn-secondary" id="photoModalCancelCamera" style="flex:1;">Annuler</button>
                    <button type="button" class="btn" id="photoModalCapture" style="flex:1;">Capturer</button>
                </div>
                <div id="photoModalConfirmControls" class="hidden" style="display:flex;gap:0.5rem;margin-top:1rem;">
                    <button type="button" class="btn btn-secondary" id="photoModalRetake" style="flex:1;">Reprendre</button>
                    <button type="button" class="btn" id="photoModalConfirm" style="flex:1;">Remplacer</button>
                </div>
            </div>
        </div>
    </div>
    <canvas id="photoModalCanvas"></canvas>

    <script>
(function() {
    const userId = <?= (int)$userId ?>;
    const modal = document.getElementById('photoModal');
    const backdrop = document.getElementById('photoModalBackdrop');
    const closeBtn = document.getElementById('photoModalClose');
    const openBtn = document.getElementById('profilePhotoBtn');
    const profileImg = document.getElementById('profilePhotoImg');
    const profilePlaceholder = document.getElementById('profilePhotoPlaceholder');

    const container = document.getElementById('photoModalContainer');
    const cameraEl = document.getElementById('photoModalCamera');
    const previewEl = document.getElementById('photoModalPreview');
    const placeholderEl = document.getElementById('photoModalPlaceholder');
    const optionsEl = document.getElementById('photoModalOptions');
    const cameraControls = document.getElementById('photoModalCameraControls');
    const confirmControls = document.getElementById('photoModalConfirmControls');
    const takeBtn = document.getElementById('photoModalTake');
    const fileInput = document.getElementById('photoModalFile');
    const cancelCameraBtn = document.getElementById('photoModalCancelCamera');
    const captureBtn = document.getElementById('photoModalCapture');
    const retakeBtn = document.getElementById('photoModalRetake');
    const confirmBtn = document.getElementById('photoModalConfirm');
    const canvas = document.getElementById('photoModalCanvas');

    let stream = null;
    let photoBlob = null;

    function openModal() {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        resetModalState();
    }
    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        if (stream) {
            stream.getTracks().forEach(t => t.stop());
            stream = null;
        }
    }
    function resetModalState() {
        photoBlob = null;
        placeholderEl.classList.remove('hidden');
        previewEl.classList.add('hidden');
        previewEl.src = '';
        cameraEl.classList.add('hidden');
        optionsEl.style.display = '';
        cameraControls.classList.add('hidden');
        cameraControls.style.display = 'none';
        confirmControls.classList.add('hidden');
        confirmControls.style.display = 'none';
    }

    openBtn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    backdrop.addEventListener('click', closeModal);

    takeBtn.addEventListener('click', async () => {
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: { ideal: 720 }, height: { ideal: 720 } }
            });
            cameraEl.srcObject = stream;
            placeholderEl.classList.add('hidden');
            previewEl.classList.add('hidden');
            cameraEl.classList.remove('hidden');
            optionsEl.style.display = 'none';
            cameraControls.classList.remove('hidden');
            cameraControls.style.display = 'flex';
            confirmControls.classList.add('hidden');
        } catch (err) {
            alert('Impossible d\'accéder à la caméra');
        }
    });

    cancelCameraBtn.addEventListener('click', () => {
        if (stream) {
            stream.getTracks().forEach(t => t.stop());
            stream = null;
        }
        resetModalState();
    });

    captureBtn.addEventListener('click', () => {
        canvas.width = 720;
        canvas.height = 720;
        const ctx = canvas.getContext('2d');
        const vw = cameraEl.videoWidth;
        const vh = cameraEl.videoHeight;
        const size = Math.min(vw, vh);
        const sx = (vw - size) / 2;
        const sy = (vh - size) / 2;
        ctx.drawImage(cameraEl, sx, sy, size, size, 0, 0, 720, 720);
        canvas.toBlob((blob) => {
            photoBlob = blob;
            previewEl.src = URL.createObjectURL(blob);
            if (stream) {
                stream.getTracks().forEach(t => t.stop());
                stream = null;
            }
            cameraEl.classList.add('hidden');
            previewEl.classList.remove('hidden');
            cameraControls.classList.add('hidden');
            confirmControls.classList.remove('hidden');
            confirmControls.style.display = 'flex';
        }, 'image/jpeg', 0.9);
    });

    fileInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;
        const img = new Image();
        img.onload = () => {
            canvas.width = 720;
            canvas.height = 720;
            const ctx = canvas.getContext('2d');
            const size = Math.min(img.width, img.height);
            const sx = (img.width - size) / 2;
            const sy = (img.height - size) / 2;
            ctx.drawImage(img, sx, sy, size, size, 0, 0, 720, 720);
            canvas.toBlob((blob) => {
                photoBlob = blob;
                previewEl.src = URL.createObjectURL(blob);
                placeholderEl.classList.add('hidden');
                cameraEl.classList.add('hidden');
                previewEl.classList.remove('hidden');
                optionsEl.style.display = 'none';
                confirmControls.classList.remove('hidden');
                confirmControls.style.display = 'flex';
            }, 'image/jpeg', 0.9);
        };
        img.src = URL.createObjectURL(file);
        e.target.value = '';
    });

    retakeBtn.addEventListener('click', resetModalState);

    confirmBtn.addEventListener('click', async () => {
        if (!photoBlob) return;
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Envoi...';
        try {
            const formData = new FormData();
            formData.append('action', 'get_upload_url');
            const response = await fetch('candidate-profile.php', { method: 'POST', body: formData });
            const uploadInfo = await response.json();
            if (uploadInfo.error) throw new Error(uploadInfo.error);
            const downloadUrl = uploadInfo.downloadUrl;
            const arrayBuffer = await photoBlob.arrayBuffer();
            const hashBuffer = await crypto.subtle.digest('SHA-1', arrayBuffer);
            const hashArray = Array.from(new Uint8Array(hashBuffer));
            const sha1 = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
            const xhr = new XMLHttpRequest();
            xhr.open('POST', uploadInfo.uploadUrl, true);
            xhr.setRequestHeader('Authorization', uploadInfo.authToken);
            xhr.setRequestHeader('X-Bz-File-Name', encodeURIComponent(uploadInfo.fileName));
            xhr.setRequestHeader('Content-Type', 'image/jpeg');
            xhr.setRequestHeader('X-Bz-Content-Sha1', sha1);
            xhr.onload = async () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    const result = JSON.parse(xhr.responseText);
                    const bucketName = 'ciaocv';
                    const photoUrl = downloadUrl + '/file/' + bucketName + '/' + result.fileName;
                    const saveData = new FormData();
                    saveData.append('action', 'save_photo');
                    saveData.append('photo_url', photoUrl);
                    const saveRes = await fetch('candidate-profile.php', { method: 'POST', body: saveData });
                    const saveJson = await saveRes.json();
                    if (saveJson.success) {
                        if (profileImg) {
                            profileImg.src = photoUrl;
                        } else {
                            const wrap = openBtn;
                            const ph = document.getElementById('profilePhotoPlaceholder');
                            if (ph) {
                                ph.remove();
                                const img = document.createElement('img');
                                img.id = 'profilePhotoImg';
                                img.alt = 'Photo de profil';
                                img.src = photoUrl;
                                wrap.insertBefore(img, wrap.firstChild);
                            }
                        }
                        closeModal();
                    } else {
                        throw new Error(saveJson.error || 'Erreur');
                    }
                } else {
                    throw new Error('Échec upload');
                }
            };
            xhr.onerror = () => { throw new Error('Erreur réseau'); };
            xhr.send(photoBlob);
        } catch (err) {
            alert('Erreur: ' + err.message);
        }
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Remplacer';
    });
})();
    </script>
</body>
</html>
