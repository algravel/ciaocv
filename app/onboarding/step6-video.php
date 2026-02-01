<?php
/**
 * Étape 6 - LA VIDÉO (coeur de CiaoCV)
 * Enregistrement vidéo 60 secondes
 */
session_start();
require_once __DIR__ . '/../db.php';

$requiredStep = 6;
require_once __DIR__ . '/../includes/onboarding-check.php';

$currentStep = 6;
$stepTitle = "Ta vidéo";
$firstName = $_SESSION['user_first_name'] ?? 'Candidat';

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
            echo json_encode(['error' => 'Échec authentification B2']);
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
        
        if (!isset($uploadUrlResponse['uploadUrl'])) {
            echo json_encode(['error' => 'Échec obtention URL upload']);
            exit;
        }
        
        $fileName = 'cv_' . $userId . '_' . date('Y-m-d_H-i-s') . '_' . bin2hex(random_bytes(4)) . '.webm';
        
        echo json_encode([
            'uploadUrl' => $uploadUrlResponse['uploadUrl'],
            'authToken' => $uploadUrlResponse['authorizationToken'],
            'fileName' => $fileName,
            'downloadUrl' => $authResponse['downloadUrl']
        ]);
        exit;
    }

    if ($_POST['action'] === 'save_video') {
        $videoUrl = $_POST['video_url'] ?? '';
        if ($db) {
            // Permet de passer sans vidéo (URL vide) ou avec vidéo
            if ($videoUrl) {
                $db->prepare('UPDATE users SET video_url = ?, onboarding_step = 7 WHERE id = ?')
                   ->execute([$videoUrl, $userId]);
            } else {
                $db->prepare('UPDATE users SET onboarding_step = 7 WHERE id = ?')
                   ->execute([$userId]);
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Erreur base de données']);
        }
        exit;
    }

    if ($_POST['action'] === 'delete_file') {
        $fileId = $_POST['fileId'] ?? '';
        $fileName = $_POST['fileName'] ?? '';

        if (!$fileId || !$fileName) {
            echo json_encode(['error' => 'Paramètres manquants']);
            exit;
        }

        $authUrl = 'https://api.backblazeb2.com/b2api/v2/b2_authorize_account';
        $credentials = base64_encode($b2KeyId . ':' . $b2AppKey);
        
        $ch = curl_init($authUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $authResponse = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (isset($authResponse['authorizationToken'])) {
            $deleteUrl = $authResponse['apiUrl'] . '/b2api/v2/b2_delete_file_version';
            $ch = curl_init($deleteUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: ' . $authResponse['authorizationToken'],
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'fileName' => $fileName,
                'fileId' => $fileId
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            curl_close($ch);
        }

        echo json_encode(['success' => true]);
        exit;
    }
}

// Vérifier si une vidéo existe déjà
$existingVideo = null;
if ($db) {
    $stmt = $db->prepare('SELECT video_url FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $existingVideo = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ta vidéo - CiaoCV</title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="../assets/css/design-system.css?v=<?= ASSET_VERSION ?>">
</head>
<body>
    <div class="onboarding-container">
        <?php include __DIR__ . '/../includes/onboarding-header.php'; ?>

        <h1 class="step-title">Montre qui tu es</h1>
        <p class="reassurance">Pas besoin d'être parfait. Sois toi-même.</p>

        <div class="onboarding-content">
            <div class="questions-guide">
                <h4>Questions à l'écran (60 secondes)</h4>
                <ol>
                    <li>Présente-toi rapidement</li>
                    <li>Pourquoi tu cherches un emploi en ce moment ?</li>
                    <li>Qu'est-ce que tu apportes à une équipe ?</li>
                </ol>
            </div>

            <div class="video-section">
                <video id="videoPreview" autoplay playsinline muted></video>
                <video id="videoPlayback" class="hidden" playsinline controls></video>
                <div class="timer" id="timer">01:00</div>
                <div class="progress-bar" id="progressBar"></div>
                
                <div id="transferOverlay" class="transfer-overlay hidden">
                    <div class="spinner"></div>
                    <div style="margin-bottom: 10px; color: white;">Transfert en cours...</div>
                    <div style="width: 80%; background: #374151; height: 10px; border-radius: 5px; overflow: hidden;">
                        <div id="uploadProgressBar" style="width: 0%; height: 100%; background: var(--success); transition: width 0.2s;"></div>
                    </div>
                    <div id="uploadPercent" style="margin-top: 5px; font-size: 0.9rem; color: white;">0%</div>
                </div>
            </div>

            <div id="recordControls">
                <button id="btnStart" class="btn">Enregistrer ma vidéo</button>
                <button id="btnStop" class="btn hidden" style="background:#dc2626;">Arrêter</button>
            </div>

            <div id="validationControls" class="video-controls hidden">
                <button id="btnDelete" class="btn btn-secondary">Recommencer</button>
                <button id="btnAccept" class="btn">Garder cette vidéo</button>
            </div>
        </div>

        <div class="onboarding-footer">
            <button id="btnSkip" class="btn-skip">Passer pour l'instant</button>
        </div>
    </div>

    <script>
        const MAX_DURATION = 60;
        const userId = <?= $userId ?>;
        let mediaRecorder, recordedChunks = [], stream, timerInterval, startTime, recordedBlob;
        let uploadedFileId = null, uploadedFileName = null, downloadUrl = null;

        const videoPreview = document.getElementById('videoPreview');
        const videoPlayback = document.getElementById('videoPlayback');
        const timer = document.getElementById('timer');
        const progressBar = document.getElementById('progressBar');
        const btnStart = document.getElementById('btnStart');
        const btnStop = document.getElementById('btnStop');
        const btnAccept = document.getElementById('btnAccept');
        const btnDelete = document.getElementById('btnDelete');
        const btnSkip = document.getElementById('btnSkip');
        const recordControls = document.getElementById('recordControls');
        const validationControls = document.getElementById('validationControls');
        const transferOverlay = document.getElementById('transferOverlay');
        const uploadProgressBar = document.getElementById('uploadProgressBar');
        const uploadPercent = document.getElementById('uploadPercent');

        async function initCamera() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user', width: { ideal: 720 }, height: { ideal: 1280 } },
                    audio: true
                });
                videoPreview.srcObject = stream;
                btnStart.disabled = false;
            } catch (err) {
                alert('Impossible d\'accéder à la caméra. Vérifie les permissions.');
                btnStart.disabled = true;
            }
        }

        function startRecording() {
            recordedChunks = [];
            let mimeType = 'video/webm;codecs=vp9';
            if (!MediaRecorder.isTypeSupported(mimeType)) mimeType = 'video/webm';
            if (!MediaRecorder.isTypeSupported(mimeType)) mimeType = 'video/mp4';

            try {
                mediaRecorder = new MediaRecorder(stream, { mimeType, videoBitsPerSecond: 1000000 });
            } catch (e) {
                mediaRecorder = new MediaRecorder(stream);
            }

            mediaRecorder.ondataavailable = (e) => {
                if (e.data.size > 0) recordedChunks.push(e.data);
            };

            mediaRecorder.onstop = () => {
                recordedBlob = new Blob(recordedChunks, { type: mediaRecorder.mimeType });
                videoPlayback.src = URL.createObjectURL(recordedBlob);
                uploadToB2();
            };

            mediaRecorder.start(100);
            startTime = Date.now();
            timerInterval = setInterval(updateTimer, 100);

            setTimeout(() => {
                if (mediaRecorder && mediaRecorder.state === 'recording') stopRecording();
            }, MAX_DURATION * 1000);

            showRecordingMode();
        }

        function stopRecording() {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
                clearInterval(timerInterval);
            }
        }

        function updateTimer() {
            const elapsed = (Date.now() - startTime) / 1000;
            const remaining = Math.max(0, MAX_DURATION - elapsed);
            const mins = Math.floor(remaining / 60);
            const secs = Math.floor(remaining % 60);
            timer.textContent = String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
            progressBar.style.width = (elapsed / MAX_DURATION) * 100 + '%';
        }

        async function uploadToB2() {
            showTransferMode();
            
            try {
                const formData = new FormData();
                formData.append('action', 'get_upload_url');
                
                const response = await fetch('step6-video.php', { method: 'POST', body: formData });
                const uploadInfo = await response.json();
                if (uploadInfo.error) throw new Error(uploadInfo.error);

                downloadUrl = uploadInfo.downloadUrl;

                const arrayBuffer = await recordedBlob.arrayBuffer();
                const hashBuffer = await crypto.subtle.digest('SHA-1', arrayBuffer);
                const hashArray = Array.from(new Uint8Array(hashBuffer));
                const sha1 = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');

                await new Promise((resolve, reject) => {
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', uploadInfo.uploadUrl, true);
                    xhr.setRequestHeader('Authorization', uploadInfo.authToken);
                    xhr.setRequestHeader('X-Bz-File-Name', encodeURIComponent(uploadInfo.fileName));
                    xhr.setRequestHeader('Content-Type', 'video/webm');
                    xhr.setRequestHeader('X-Bz-Content-Sha1', sha1);

                    xhr.upload.onprogress = (e) => {
                        if (e.lengthComputable) {
                            const pct = Math.round((e.loaded / e.total) * 100);
                            uploadProgressBar.style.width = pct + '%';
                            uploadPercent.textContent = pct + '%';
                        }
                    };

                    xhr.onload = () => {
                        if (xhr.status >= 200 && xhr.status < 300) {
                            const result = JSON.parse(xhr.responseText);
                            uploadedFileId = result.fileId;
                            uploadedFileName = result.fileName;
                            resolve(result);
                        } else {
                            reject(new Error('Échec upload'));
                        }
                    };
                    xhr.onerror = () => reject(new Error('Erreur réseau'));
                    xhr.send(recordedBlob);
                });

                showValidationMode();

            } catch (err) {
                alert('Erreur: ' + err.message);
                showInitialMode();
            }
        }

        async function acceptVideo() {
            const bucketName = 'ciaocv';
            const videoUrl = downloadUrl + '/file/' + bucketName + '/' + uploadedFileName;
            
            const formData = new FormData();
            formData.append('action', 'save_video');
            formData.append('video_url', videoUrl);
            
            await fetch('step6-video.php', { method: 'POST', body: formData });
            window.location.href = 'step7-tests.php';
        }

        async function deleteVideo() {
            if (uploadedFileId && uploadedFileName) {
                const formData = new FormData();
                formData.append('action', 'delete_file');
                formData.append('fileId', uploadedFileId);
                formData.append('fileName', uploadedFileName);
                await fetch('step6-video.php', { method: 'POST', body: formData });
            }
            showInitialMode();
        }

        function showRecordingMode() {
            btnStart.classList.add('hidden');
            btnStop.classList.remove('hidden');
            validationControls.classList.add('hidden');
            timer.classList.add('recording');
            videoPreview.classList.remove('hidden');
            videoPlayback.classList.add('hidden');
            transferOverlay.classList.add('hidden');
            btnSkip.classList.add('hidden');
        }

        function showTransferMode() {
            btnStart.classList.add('hidden');
            btnStop.classList.add('hidden');
            validationControls.classList.add('hidden');
            timer.classList.remove('recording');
            videoPreview.classList.add('hidden');
            videoPlayback.classList.add('hidden');
            transferOverlay.classList.remove('hidden');
            uploadProgressBar.style.width = '0%';
            uploadPercent.textContent = '0%';
            btnSkip.classList.add('hidden');
        }

        function showValidationMode() {
            recordControls.classList.add('hidden');
            validationControls.classList.remove('hidden');
            timer.classList.remove('recording');
            videoPreview.classList.add('hidden');
            videoPlayback.classList.remove('hidden');
            transferOverlay.classList.add('hidden');
            btnSkip.classList.add('hidden');
        }

        function showInitialMode() {
            recordControls.classList.remove('hidden');
            btnStart.classList.remove('hidden');
            btnStop.classList.add('hidden');
            validationControls.classList.add('hidden');
            timer.classList.remove('recording');
            timer.textContent = '01:00';
            progressBar.style.width = '0%';
            videoPreview.classList.remove('hidden');
            videoPlayback.classList.add('hidden');
            transferOverlay.classList.add('hidden');
            btnSkip.classList.remove('hidden');
            uploadedFileId = null;
            uploadedFileName = null;
        }

        btnStart.addEventListener('click', startRecording);
        btnStop.addEventListener('click', stopRecording);
        btnAccept.addEventListener('click', acceptVideo);
        btnDelete.addEventListener('click', deleteVideo);
        btnSkip.addEventListener('click', () => {
            // Mettre à jour l'étape même si pas de vidéo
            fetch('step6-video.php', {
                method: 'POST',
                body: new URLSearchParams({ action: 'save_video', video_url: '' })
            }).then(() => {
                window.location.href = 'step7-tests.php';
            });
        });

        initCamera();
    </script>
</body>
</html>
