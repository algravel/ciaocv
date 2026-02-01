<?php
/**
 * Profil employeur (entreprise) - nom, description, vidéo corporative, logo.
 */
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$success = null;
$error = null;

// S'assurer que les colonnes profil employeur existent
if ($db) {
    $cols = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    $employerCols = ['company_name', 'company_description', 'company_description_visible', 'company_video_url', 'company_logo_url', 'company_website_url'];
    foreach ($employerCols as $c) {
        if (!in_array($c, $cols)) {
            if ($c === 'company_name') {
                $db->exec("ALTER TABLE users ADD COLUMN company_name VARCHAR(255) DEFAULT NULL AFTER preferred_language");
            } elseif ($c === 'company_description') {
                $db->exec("ALTER TABLE users ADD COLUMN company_description TEXT DEFAULT NULL AFTER company_name");
            } elseif ($c === 'company_description_visible') {
                $db->exec("ALTER TABLE users ADD COLUMN company_description_visible TINYINT(1) DEFAULT 1 AFTER company_description");
            } elseif ($c === 'company_video_url') {
                $db->exec("ALTER TABLE users ADD COLUMN company_video_url VARCHAR(500) DEFAULT NULL AFTER company_description_visible");
            } elseif ($c === 'company_logo_url') {
                $db->exec("ALTER TABLE users ADD COLUMN company_logo_url VARCHAR(500) DEFAULT NULL AFTER company_video_url");
            } elseif ($c === 'company_website_url') {
                $db->exec("ALTER TABLE users ADD COLUMN company_website_url VARCHAR(500) DEFAULT NULL AFTER company_logo_url");
            }
        }
    }
}

$user = null;
if ($db) {
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$user) {
    header('Location: index.php');
    exit;
}

$companyName = trim($user['company_name'] ?? '');
$companyDescription = trim($user['company_description'] ?? '');
$companyDescriptionVisible = !empty($user['company_description_visible']);
$companyVideoUrl = trim($user['company_video_url'] ?? '');
$companyLogoUrl = trim($user['company_logo_url'] ?? '');
$companyWebsiteUrl = trim($user['company_website_url'] ?? '');
$email = $user['email'] ?? '';

// API upload logo (B2)
$b2KeyId = $_ENV['B2_KEY_ID'] ?? '';
$b2AppKey = $_ENV['B2_APPLICATION_KEY'] ?? '';
$b2BucketId = $_ENV['B2_BUCKET_ID'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'get_upload_url_logo') {
        header('Content-Type: application/json');
        if (!$b2KeyId || !$b2AppKey || !$b2BucketId) {
            echo json_encode(['error' => 'Config B2 manquante']);
            exit;
        }
        $authUrl = 'https://api.backblazeb2.com/b2api/v2/b2_authorize_account';
        $credentials = base64_encode($b2KeyId . ':' . $b2AppKey);
        $ch = curl_init($authUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $authResponse = json_decode(curl_exec($ch), true);
        curl_close($ch);
        if (!isset($authResponse['authorizationToken'])) {
            echo json_encode(['error' => 'Échec auth B2']);
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
        $fileName = 'logo_' . $userId . '_' . date('Y-m-d_H-i-s') . '.jpg';
        echo json_encode([
            'uploadUrl' => $uploadUrlResponse['uploadUrl'],
            'authToken' => $uploadUrlResponse['authorizationToken'],
            'fileName' => $fileName,
            'downloadUrl' => $authResponse['downloadUrl']
        ]);
        exit;
    }
    if ($_POST['action'] === 'save_logo') {
        header('Content-Type: application/json');
        $logoUrl = trim($_POST['logo_url'] ?? '');
        if ($db && $logoUrl !== '') {
            $db->prepare('UPDATE users SET company_logo_url = ? WHERE id = ?')->execute([$logoUrl, $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'URL invalide']);
        }
        exit;
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_employer_profile') {
    $companyName = trim($_POST['company_name'] ?? '');
    $companyDescription = trim($_POST['company_description'] ?? '');
    $companyDescriptionVisible = isset($_POST['company_description_visible']) && $_POST['company_description_visible'] === '1';
    $companyVideoUrl = trim($_POST['company_video_url'] ?? '');
    $companyLogoUrl = trim($_POST['company_logo_url'] ?? '');
    $companyWebsiteUrl = trim($_POST['company_website_url'] ?? '');

    if ($companyVideoUrl !== '' && !filter_var($companyVideoUrl, FILTER_VALIDATE_URL)) {
        $error = 'L’URL de la vidéo corporative n’est pas valide.';
    } elseif ($companyWebsiteUrl !== '' && !filter_var($companyWebsiteUrl, FILTER_VALIDATE_URL)) {
        $error = 'L’URL du site web n’est pas valide.';
    } else {
        try {
            $db->prepare('UPDATE users SET company_name = ?, company_description = ?, company_description_visible = ?, company_video_url = ?, company_logo_url = ?, company_website_url = ? WHERE id = ?')
               ->execute([$companyName ?: null, $companyDescription ?: null, $companyDescriptionVisible ? 1 : 0, $companyVideoUrl ?: null, $companyLogoUrl ?: null, $companyWebsiteUrl ?: null, $userId]);
            $success = 'Profil entreprise mis à jour.';
            $user['company_name'] = $companyName;
            $user['company_description'] = $companyDescription;
            $user['company_description_visible'] = $companyDescriptionVisible ? 1 : 0;
            $user['company_video_url'] = $companyVideoUrl;
            $user['company_logo_url'] = $companyLogoUrl;
            $user['company_website_url'] = $companyWebsiteUrl;
        } catch (PDOException $e) {
            $error = 'Erreur lors de la mise à jour.';
        }
    }
}

// Recharger après POST
$companyName = trim($user['company_name'] ?? '');
$companyDescription = trim($user['company_description'] ?? '');
$companyDescriptionVisible = !empty($user['company_description_visible']);
$companyVideoUrl = trim($user['company_video_url'] ?? '');
$companyLogoUrl = trim($user['company_logo_url'] ?? '');
$companyWebsiteUrl = trim($user['company_website_url'] ?? '');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil entreprise - CiaoCV</title>
    <link rel="stylesheet" href="assets/css/design-system.css?v=<?= ASSET_VERSION ?>">
</head>
<body>
    <div class="app-shell">
        <?php $sidebarActive = 'profile'; include __DIR__ . '/includes/employer-sidebar.php'; ?>
        <main class="app-main">
        <?php include __DIR__ . '/includes/employer-header.php'; ?>
        <div class="app-main-content layout-app layout-app-wide">

        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- En-tête du profil entreprise -->
        <div class="profile-header employer-profile-header">
            <div class="profile-photo employer-logo-wrap">
                <?php if ($companyLogoUrl): ?>
                    <img src="<?= htmlspecialchars($companyLogoUrl) ?>" alt="Logo <?= htmlspecialchars($companyName ?: 'entreprise') ?>" class="employer-logo-img">
                <?php else: ?>
                    <div class="placeholder">🏢</div>
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <h3><?= htmlspecialchars($companyName ?: 'Entreprise') ?></h3>
                <p><?= htmlspecialchars($email) ?></p>
                <?php if ($companyWebsiteUrl): ?>
                    <p><a href="<?= htmlspecialchars($companyWebsiteUrl) ?>" target="_blank" rel="noopener noreferrer">Site web →</a></p>
                <?php endif; ?>
                <?php if ($companyDescription && $companyDescriptionVisible): ?>
                    <p class="profile-description-preview"><?= nl2br(htmlspecialchars(mb_substr($companyDescription, 0, 200) . (mb_strlen($companyDescription) > 200 ? '…' : ''))) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Informations entreprise -->
        <div class="section">
            <h2>Informations <span class="section-edit-hint">Modifier →</span></h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_employer_profile">
                <input type="hidden" name="company_video_url" value="<?= htmlspecialchars($companyVideoUrl) ?>">
                <input type="hidden" name="company_logo_url" value="<?= htmlspecialchars($companyLogoUrl) ?>">
                <div class="form-group">
                    <label for="company_website_url">Site web de l'entreprise</label>
                    <input type="url" id="company_website_url" name="company_website_url" value="<?= htmlspecialchars($companyWebsiteUrl) ?>" placeholder="https://www.votre-entreprise.com">
                </div>
                <div class="form-group">
                    <label for="company_name">Nom de l'entreprise</label>
                    <input type="text" id="company_name" name="company_name" value="<?= htmlspecialchars($companyName) ?>" placeholder="Nom de votre entreprise">
                </div>
                <div class="form-group">
                    <label for="company_website_url">Site web de l'entreprise</label>
                    <input type="url" id="company_website_url" name="company_website_url" value="<?= htmlspecialchars($companyWebsiteUrl) ?>" placeholder="https://…">
                </div>
                <div class="form-group">
                    <label for="company_description">Description de l'entreprise</label>
                    <textarea id="company_description" name="company_description" rows="5" placeholder="Présentez votre entreprise aux candidats…"><?= htmlspecialchars($companyDescription) ?></textarea>
                </div>
                <div class="form-group form-group-checkbox">
                    <label>
                        <input type="checkbox" name="company_description_visible" value="1" <?= $companyDescriptionVisible ? 'checked' : '' ?>>
                        Afficher la description aux candidats (sinon masquée)
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">Sauvegarder</button>
            </form>
        </div>

        <!-- Logo -->
        <div class="section">
            <h2>Logo</h2>
            <div class="employer-logo-block">
                <?php if ($companyLogoUrl): ?>
                    <div class="employer-logo-preview">
                        <img id="employerLogoImg" src="<?= htmlspecialchars($companyLogoUrl) ?>" alt="Logo" style="max-width: 120px; max-height: 120px; object-fit: contain;">
                    </div>
                <?php else: ?>
                    <div class="employer-logo-placeholder" id="employerLogoPlaceholder">Aucun logo</div>
                <?php endif; ?>
                <button type="button" class="btn btn-primary" id="employerLogoBtn">Changer le logo</button>
            </div>
        </div>

        <!-- Vidéo corporative -->
        <div class="section">
            <h2>Vidéo corporative</h2>
            <div class="video-preview">
                <?php if ($companyVideoUrl): ?>
                    <video controls src="<?= htmlspecialchars($companyVideoUrl) ?>"></video>
                <?php else: ?>
                    <div class="no-video">
                        <span>🎬</span>
                        <p>Aucune vidéo corporative</p>
                    </div>
                <?php endif; ?>
            </div>
            <form method="POST" class="form-inline-video">
                <input type="hidden" name="action" value="update_employer_profile">
                <input type="hidden" name="company_name" value="<?= htmlspecialchars($companyName) ?>">
                <input type="hidden" name="company_description" value="<?= htmlspecialchars($companyDescription) ?>">
                <input type="hidden" name="company_description_visible" value="<?= $companyDescriptionVisible ? '1' : '0' ?>">
                <input type="hidden" name="company_logo_url" value="<?= htmlspecialchars($companyLogoUrl) ?>">
                <input type="hidden" name="company_website_url" value="<?= htmlspecialchars($companyWebsiteUrl) ?>">
                <div class="form-group">
                    <label for="company_video_url">URL de la vidéo</label>
                    <input type="url" id="company_video_url" name="company_video_url" value="<?= htmlspecialchars($companyVideoUrl) ?>" placeholder="https://…">
                </div>
                <button type="submit" class="btn btn-primary">Sauvegarder la vidéo</button>
            </form>
        </div>

        </div>
        </main>
    </div>

    <!-- Modal upload logo -->
    <div class="photo-modal" id="logoModal" role="dialog" aria-labelledby="logoModalTitle" aria-modal="true">
        <div class="photo-modal-backdrop" id="logoModalBackdrop"></div>
        <div class="photo-modal-content">
            <div class="photo-modal-header">
                <h2 id="logoModalTitle">Changer le logo</h2>
                <button type="button" class="photo-modal-close" id="logoModalClose" aria-label="Fermer">&times;</button>
            </div>
            <div class="photo-modal-body">
                <div class="photo-modal-preview" id="logoModalContainer">
                    <video id="logoModalCamera" class="hidden" autoplay playsinline muted></video>
                    <img id="logoModalPreview" src="" alt="Aperçu" class="hidden">
                    <div class="photo-modal-placeholder" id="logoModalPlaceholder">
                        <span class="icon">🏢</span>
                        <span>Aperçu</span>
                    </div>
                </div>
                <div class="photo-modal-options" id="logoModalOptions">
                    <label class="photo-option" id="logoModalTake">
                        <span class="icon">📸</span>
                        <span>Prendre une photo</span>
                    </label>
                    <label class="photo-option">
                        <span class="icon">📁</span>
                        <span>Téléverser une image</span>
                        <input type="file" id="logoModalFile" accept="image/*">
                    </label>
                </div>
                <div id="logoModalCameraControls" class="hidden" style="display:flex;gap:0.5rem;margin-top:1rem;">
                    <button type="button" class="btn btn-secondary" id="logoModalCancelCamera" style="flex:1;">Annuler</button>
                    <button type="button" class="btn" id="logoModalCapture" style="flex:1;">Capturer</button>
                </div>
                <div id="logoModalConfirmControls" class="hidden" style="display:flex;gap:0.5rem;margin-top:1rem;">
                    <button type="button" class="btn btn-secondary" id="logoModalRetake" style="flex:1;">Reprendre</button>
                    <button type="button" class="btn" id="logoModalConfirm" style="flex:1;">Remplacer</button>
                </div>
            </div>
        </div>
    </div>
    <canvas id="logoModalCanvas"></canvas>

    <script>
(function() {
    const modal = document.getElementById('logoModal');
    const backdrop = document.getElementById('logoModalBackdrop');
    const closeBtn = document.getElementById('logoModalClose');
    const openBtn = document.getElementById('employerLogoBtn');
    const logoImg = document.getElementById('employerLogoImg');
    const logoPlaceholder = document.getElementById('employerLogoPlaceholder');
    const container = document.getElementById('logoModalContainer');
    const cameraEl = document.getElementById('logoModalCamera');
    const previewEl = document.getElementById('logoModalPreview');
    const placeholderEl = document.getElementById('logoModalPlaceholder');
    const optionsEl = document.getElementById('logoModalOptions');
    const cameraControls = document.getElementById('logoModalCameraControls');
    const confirmControls = document.getElementById('logoModalConfirmControls');
    const takeBtn = document.getElementById('logoModalTake');
    const fileInput = document.getElementById('logoModalFile');
    const cancelCameraBtn = document.getElementById('logoModalCancelCamera');
    const captureBtn = document.getElementById('logoModalCapture');
    const retakeBtn = document.getElementById('logoModalRetake');
    const confirmBtn = document.getElementById('logoModalConfirm');
    const canvas = document.getElementById('logoModalCanvas');
    const headerLogoImg = document.querySelector('.employer-profile-header .employer-logo-img');

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

    if (openBtn) openBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (backdrop) backdrop.addEventListener('click', closeModal);

    if (takeBtn) takeBtn.addEventListener('click', async () => {
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

    if (cancelCameraBtn) cancelCameraBtn.addEventListener('click', () => {
        if (stream) {
            stream.getTracks().forEach(t => t.stop());
            stream = null;
        }
        resetModalState();
    });

    if (captureBtn) captureBtn.addEventListener('click', () => {
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

    if (fileInput) fileInput.addEventListener('change', (e) => {
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

    if (retakeBtn) retakeBtn.addEventListener('click', resetModalState);

    if (confirmBtn) confirmBtn.addEventListener('click', async () => {
        if (!photoBlob) return;
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Envoi...';
        try {
            const formData = new FormData();
            formData.append('action', 'get_upload_url_logo');
            const response = await fetch('employer-profile.php', { method: 'POST', body: formData });
            const uploadInfo = await response.json();
            if (uploadInfo.error) throw new Error(uploadInfo.error);
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
                    const logoUrl = uploadInfo.downloadUrl + '/file/' + bucketName + '/' + result.fileName;
                    const saveData = new FormData();
                    saveData.append('action', 'save_logo');
                    saveData.append('logo_url', logoUrl);
                    const saveRes = await fetch('employer-profile.php', { method: 'POST', body: saveData });
                    const saveJson = await saveRes.json();
                    if (saveJson.success) {
                        if (logoImg) {
                            logoImg.src = logoUrl;
                        } else if (logoPlaceholder) {
                            logoPlaceholder.outerHTML = '<div class="employer-logo-preview"><img id="employerLogoImg" src="' + logoUrl + '" alt="Logo" style="max-width:120px;max-height:120px;object-fit:contain;"></div>';
                        }
                        if (headerLogoImg) headerLogoImg.src = logoUrl;
                        else {
                            const headerWrap = document.querySelector('.employer-profile-header .employer-logo-wrap');
                            if (headerWrap) {
                                const ph = headerWrap.querySelector('.placeholder');
                                if (ph) {
                                    ph.remove();
                                    const img = document.createElement('img');
                                    img.className = 'employer-logo-img';
                                    img.alt = 'Logo';
                                    img.src = logoUrl;
                                    headerWrap.insertBefore(img, headerWrap.firstChild);
                                } else {
                                    const img = headerWrap.querySelector('.employer-logo-img');
                                    if (img) img.src = logoUrl;
                                }
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
