<?php
/**
 * Étape 8 - Photo de profil
 */
session_start();
require_once __DIR__ . '/../db.php';

$requiredStep = 8;
require_once __DIR__ . '/../includes/onboarding-check.php';

$currentStep = 8;
$stepTitle = "Photo";

// Charger les variables B2
$envFile = dirname(dirname(__DIR__)) . '/.env';
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

// API pour upload
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
        $photoUrl = $_POST['photo_url'] ?? '';
        if ($db) {
            $db->prepare('UPDATE users SET photo_url = ?, onboarding_step = 9, onboarding_completed = 1 WHERE id = ?')
               ->execute([$photoUrl, $userId]);
            echo json_encode(['success' => true]);
        }
        exit;
    }

    if ($_POST['action'] === 'skip') {
        if ($db) {
            $db->prepare('UPDATE users SET onboarding_step = 9, onboarding_completed = 1 WHERE id = ?')
               ->execute([$userId]);
        }
        echo json_encode(['success' => true, 'redirect' => '../index.php']);
        exit;
    }
}

// Charger la photo existante
$existingPhoto = null;
if ($db) {
    $stmt = $db->prepare('SELECT photo_url FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $existingPhoto = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Photo de profil - CiaoCV</title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="../assets/css/design-system.css?v=<?= ASSET_VERSION ?>">
</head>
<body>
    <div class="onboarding-container">
        <?php include __DIR__ . '/../includes/onboarding-header.php'; ?>

        <h1 class="step-title">Photo de profil</h1>
        
        <div class="onboarding-content">
            <div class="photo-stats">
                <div class="stat">3x</div>
                <p>Les profils avec photo reçoivent 3 fois plus de vues</p>
            </div>

            <div class="photo-container <?= $existingPhoto ? 'has-photo' : '' ?>" id="photoContainer">
                <video id="cameraPreview" class="hidden" autoplay playsinline muted></video>
                <img id="photoPreview" src="<?= $existingPhoto ? htmlspecialchars($existingPhoto) : '' ?>" class="<?= $existingPhoto ? '' : 'hidden' ?>" alt="Aperçu">
                <div class="photo-placeholder <?= $existingPhoto ? 'hidden' : '' ?>" id="placeholder">
                    <span class="icon">👤</span>
                    <span>Ajoute ta photo</span>
                </div>
            </div>

            <div class="photo-options" id="photoOptions" <?= $existingPhoto ? 'style="display:none;"' : '' ?>>
                <label class="photo-option" id="takePhotoBtn">
                    <span class="icon">📸</span>
                    <span>Prendre une photo</span>
                </label>
                
                <label class="photo-option">
                    <span class="icon">📁</span>
                    <span>Téléverser une image</span>
                    <input type="file" id="fileInput" accept="image/*">
                </label>
            </div>

            <div id="existingPhotoControls" class="<?= $existingPhoto ? '' : 'hidden' ?>">
                <button class="btn" id="keepPhotoBtn">Garder cette photo</button>
                <button class="btn btn-secondary" id="changePhotoBtn">Changer de photo</button>
            </div>

            <div id="cameraControls" class="hidden" style="display:flex;gap:0.5rem;margin-top:1rem;">
                <button class="btn btn-secondary" id="cancelCamera" style="flex:1;">Annuler</button>
                <button class="btn" id="captureBtn" style="flex:1;">Capturer</button>
            </div>

            <div id="confirmControls" class="hidden" style="display:flex;gap:0.5rem;margin-top:1rem;">
                <button class="btn btn-secondary" id="retakeBtn" style="flex:1;">Reprendre</button>
                <button class="btn" id="confirmBtn" style="flex:1;">Confirmer</button>
            </div>
        </div>

        <div class="onboarding-footer">
            <button class="btn hidden" id="finishBtn">Finaliser mon profil</button>
            <button class="btn-skip" id="skipBtn">Passer pour l'instant</button>
        </div>
    </div>

    <canvas id="captureCanvas"></canvas>

    <script>
        const userId = <?= $userId ?>;
        let stream = null;
        let photoBlob = null;
        let uploadedFileName = null;
        let downloadUrl = null;

        const cameraPreview = document.getElementById('cameraPreview');
        const photoPreview = document.getElementById('photoPreview');
        const placeholder = document.getElementById('placeholder');
        const photoContainer = document.getElementById('photoContainer');
        const photoOptions = document.getElementById('photoOptions');
        const cameraControls = document.getElementById('cameraControls');
        const confirmControls = document.getElementById('confirmControls');
        const takePhotoBtn = document.getElementById('takePhotoBtn');
        const fileInput = document.getElementById('fileInput');
        const captureBtn = document.getElementById('captureBtn');
        const cancelCamera = document.getElementById('cancelCamera');
        const retakeBtn = document.getElementById('retakeBtn');
        const confirmBtn = document.getElementById('confirmBtn');
        const finishBtn = document.getElementById('finishBtn');
        const skipBtn = document.getElementById('skipBtn');
        const captureCanvas = document.getElementById('captureCanvas');
        const existingPhotoControls = document.getElementById('existingPhotoControls');
        const keepPhotoBtn = document.getElementById('keepPhotoBtn');
        const changePhotoBtn = document.getElementById('changePhotoBtn');
        
        const existingPhotoUrl = <?= json_encode($existingPhoto ?: '') ?>;

        // Si photo existante, gérer les boutons
        if (keepPhotoBtn) {
            keepPhotoBtn.addEventListener('click', () => {
                window.location.href = '../index.php';
            });
        }
        
        if (changePhotoBtn) {
            changePhotoBtn.addEventListener('click', () => {
                existingPhotoControls.classList.add('hidden');
                photoOptions.style.display = '';
                photoOptions.classList.remove('hidden');
            });
        }

        // Prendre une photo avec la caméra
        takePhotoBtn.addEventListener('click', async () => {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { facingMode: 'user', width: { ideal: 720 }, height: { ideal: 720 } } 
                });
                cameraPreview.srcObject = stream;
                
                placeholder.classList.add('hidden');
                photoPreview.classList.add('hidden');
                cameraPreview.classList.remove('hidden');
                
                photoOptions.classList.add('hidden');
                cameraControls.classList.remove('hidden');
                cameraControls.style.display = 'flex';
                confirmControls.classList.add('hidden');
                skipBtn.classList.add('hidden');
            } catch (err) {
                alert('Impossible d\'accéder à la caméra');
            }
        });

        // Annuler la caméra
        cancelCamera.addEventListener('click', () => {
            if (stream) {
                stream.getTracks().forEach(t => t.stop());
                stream = null;
            }
            showInitialState();
        });

        // Capturer la photo
        captureBtn.addEventListener('click', () => {
            captureCanvas.width = 720;
            captureCanvas.height = 720;
            const ctx = captureCanvas.getContext('2d');
            
            // Dessiner la vidéo centrée et croppée en carré
            const vw = cameraPreview.videoWidth;
            const vh = cameraPreview.videoHeight;
            const size = Math.min(vw, vh);
            const sx = (vw - size) / 2;
            const sy = (vh - size) / 2;
            
            ctx.drawImage(cameraPreview, sx, sy, size, size, 0, 0, 720, 720);
            
            captureCanvas.toBlob((blob) => {
                photoBlob = blob;
                photoPreview.src = URL.createObjectURL(blob);
                
                if (stream) {
                    stream.getTracks().forEach(t => t.stop());
                    stream = null;
                }
                
                cameraPreview.classList.add('hidden');
                photoPreview.classList.remove('hidden');
                
                cameraControls.classList.add('hidden');
                confirmControls.classList.remove('hidden');
                confirmControls.style.display = 'flex';
            }, 'image/jpeg', 0.9);
        });

        // Téléverser une image
        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;
            
            // Redimensionner l'image
            const img = new Image();
            img.onload = () => {
                captureCanvas.width = 720;
                captureCanvas.height = 720;
                const ctx = captureCanvas.getContext('2d');
                
                const size = Math.min(img.width, img.height);
                const sx = (img.width - size) / 2;
                const sy = (img.height - size) / 2;
                
                ctx.drawImage(img, sx, sy, size, size, 0, 0, 720, 720);
                
                captureCanvas.toBlob((blob) => {
                    photoBlob = blob;
                    photoPreview.src = URL.createObjectURL(blob);
                    
                    placeholder.classList.add('hidden');
                    cameraPreview.classList.add('hidden');
                    photoPreview.classList.remove('hidden');
                    
                    photoOptions.classList.add('hidden');
                    confirmControls.classList.remove('hidden');
                    confirmControls.style.display = 'flex';
                    skipBtn.classList.add('hidden');
                }, 'image/jpeg', 0.9);
            };
            img.src = URL.createObjectURL(file);
        });

        // Reprendre
        retakeBtn.addEventListener('click', () => {
            photoBlob = null;
            showInitialState();
        });

        // Confirmer et uploader
        confirmBtn.addEventListener('click', async () => {
            confirmBtn.disabled = true;
            confirmBtn.textContent = 'Envoi...';
            
            try {
                // Obtenir l'URL d'upload
                const formData = new FormData();
                formData.append('action', 'get_upload_url');
                const response = await fetch('step8-photo.php', { method: 'POST', body: formData });
                const uploadInfo = await response.json();
                
                if (uploadInfo.error) throw new Error(uploadInfo.error);
                downloadUrl = uploadInfo.downloadUrl;

                // Calculer SHA1
                const arrayBuffer = await photoBlob.arrayBuffer();
                const hashBuffer = await crypto.subtle.digest('SHA-1', arrayBuffer);
                const hashArray = Array.from(new Uint8Array(hashBuffer));
                const sha1 = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');

                // Upload
                const xhr = new XMLHttpRequest();
                xhr.open('POST', uploadInfo.uploadUrl, true);
                xhr.setRequestHeader('Authorization', uploadInfo.authToken);
                xhr.setRequestHeader('X-Bz-File-Name', encodeURIComponent(uploadInfo.fileName));
                xhr.setRequestHeader('Content-Type', 'image/jpeg');
                xhr.setRequestHeader('X-Bz-Content-Sha1', sha1);

                xhr.onload = async () => {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        const result = JSON.parse(xhr.responseText);
                        uploadedFileName = result.fileName;
                        
                        // Sauvegarder l'URL
                        const bucketName = 'ciaocv';
                        const photoUrl = downloadUrl + '/file/' + bucketName + '/' + uploadedFileName;
                        
                        const saveData = new FormData();
                        saveData.append('action', 'save_photo');
                        saveData.append('photo_url', photoUrl);
                        await fetch('step8-photo.php', { method: 'POST', body: saveData });
                        
                        photoContainer.classList.add('has-photo');
                        confirmControls.classList.add('hidden');
                        finishBtn.classList.remove('hidden');
                        skipBtn.classList.add('hidden');
                    } else {
                        throw new Error('Échec upload');
                    }
                };

                xhr.onerror = () => { throw new Error('Erreur réseau'); };
                xhr.send(photoBlob);

            } catch (err) {
                alert('Erreur: ' + err.message);
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Confirmer';
            }
        });

        // Finaliser
        finishBtn.addEventListener('click', () => {
            window.location.href = '../index.php';
        });

        // Passer
        skipBtn.addEventListener('click', async () => {
            const formData = new FormData();
            formData.append('action', 'skip');
            await fetch('step8-photo.php', { method: 'POST', body: formData });
            window.location.href = '../index.php';
        });

        function showInitialState() {
            placeholder.classList.remove('hidden');
            cameraPreview.classList.add('hidden');
            photoPreview.classList.add('hidden');
            photoOptions.classList.remove('hidden');
            cameraControls.classList.add('hidden');
            confirmControls.classList.add('hidden');
            finishBtn.classList.add('hidden');
            skipBtn.classList.remove('hidden');
        }
    </script>
</body>
</html>
