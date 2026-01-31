<?php
/**
 * record.php - Enregistrement vidéo depuis le navigateur (iPhone compatible)
 * Upload direct vers Backblaze B2
 */

// Charger les variables d'environnement
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

// API endpoint pour obtenir l'URL d'upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'get_upload_url') {
        // Étape 1: Autorisation B2
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
        
        // Étape 2: Obtenir l'URL d'upload
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
        
        // Générer un nom de fichier unique
        $fileName = 'video_' . date('Y-m-d_H-i-s') . '_' . bin2hex(random_bytes(4)) . '.webm';
        
        echo json_encode([
            'uploadUrl' => $uploadUrlResponse['uploadUrl'],
            'authToken' => $uploadUrlResponse['authorizationToken'],
            'fileName' => $fileName
        ]);
        exit;
    }

    if ($_POST['action'] === 'delete_file') {
        $fileId = $_POST['fileId'] ?? '';
        $fileName = $_POST['fileName'] ?? '';

        if (!$fileId || !$fileName) {
            echo json_encode(['error' => 'Paramètres manquants']);
            exit;
        }

        // Auth B2
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

        $apiUrl = $authResponse['apiUrl'];
        $authToken = $authResponse['authorizationToken'];

        // Delete file
        $deleteUrl = $apiUrl . '/b2api/v2/b2_delete_file_version';
        $ch = curl_init($deleteUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $authToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'fileName' => $fileName,
            'fileId' => $fileId
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        echo json_encode(['success' => true, 'deleted' => $response]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="icon" href="data:,">
    <title>Enregistrer - CiaoCV</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --danger: #dc2626;
            --success: #16a34a;
            --bg: #111827;
            --white: #ffffff;
            --text: #f9fafb;
            --text-light: #9ca3af;
            --border: #374151;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
            min-height: -webkit-fill-available;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0 1rem;
        }

        .logo {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--primary);
        }

        .back-link {
            color: var(--text-light);
            text-decoration: none;
            font-size: 0.875rem;
        }

        .settings-panel {
            background: rgba(31, 41, 55, 0.5);
            border: 1px solid var(--border);
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
        }
        
        .settings-row {
            margin-bottom: 0.75rem;
        }
        
        .settings-row:last-child {
            margin-bottom: 0;
        }

        .settings-label {
            display: block;
            font-size: 0.875rem;
            color: var(--text-light);
            margin-bottom: 0.25rem;
        }

        .settings-select {
            width: 100%;
            padding: 0.5rem;
            background: #374151;
            border: 1px solid var(--border);
            color: white;
            border-radius: 0.5rem;
            font-size: 0.875rem;
        }

        .questions-panel {
            background: rgba(37, 99, 235, 0.1);
            border: 1px solid var(--primary);
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
        }

        .questions-panel h3 {
            font-size: 1rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .questions-panel ul {
            list-style-type: none;
            padding-left: 0.5rem;
        }

        .questions-panel li {
            font-size: 0.9rem;
            color: var(--text);
            margin-bottom: 0.25rem;
            padding-left: 1rem;
            position: relative;
        }

        .questions-panel li::before {
            content: "•";
            color: var(--primary);
            position: absolute;
            left: 0;
            font-weight: bold;
        }

        .video-container {
            position: relative;
            width: 100%;
            height: 60vh;
            background: #000;
            border-radius: 1rem;
            overflow: hidden;
            margin-bottom: 1rem;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        #videoPreview, #videoPlayback {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain; 
            width: auto;
            height: auto;
        }

        .timer {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(0,0,0,0.7);
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 1.25rem;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
            z-index: 10;
        }

        .timer.recording {
            background: var(--danger);
        }

        .progress-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 4px;
            background: var(--primary);
            transition: width 0.1s linear;
            z-index: 10;
        }

        .controls {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            flex-grow: 1;
        }

        .btn {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-secondary {
            background: #374151;
            color: white;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .status {
            text-align: center;
            padding: 1rem;
            color: var(--text-light);
            font-size: 0.875rem;
        }

        .status.error {
            color: var(--danger);
        }

        .status.success {
            color: var(--success);
        }

        .hidden {
            display: none !important;
        }

        .transfer-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 20;
            color: white;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #374151;
            border-top: 5px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        #debug-log {
            margin-top: 2rem;
            background: #000;
            color: #0f0;
            font-family: monospace;
            font-size: 12px;
            padding: 10px;
            border-radius: 8px;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #333;
            text-align: left;
            word-break: break-all;
        }
        .log-entry {
            border-bottom: 1px solid #222;
            padding: 2px 0;
        }
        .log-time {
            color: #666;
            margin-right: 5px;
        }

        @supports (padding-top: env(safe-area-inset-top)) {
            .container {
                padding-top: calc(1rem + env(safe-area-inset-top));
                padding-bottom: calc(1rem + env(safe-area-inset-bottom));
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">CiaoCV</div>
            <a href="index.php" class="back-link">← Retour</a>
        </div>

        <div class="settings-panel" id="settingsPanel">
            <div class="settings-row">
                <label class="settings-label" for="cameraSelect">Caméra</label>
                <select id="cameraSelect" class="settings-select"></select>
            </div>
            <div class="settings-row">
                <label class="settings-label" for="micSelect">Microphone</label>
                <select id="micSelect" class="settings-select"></select>
            </div>
        </div>

        <div class="questions-panel">
            <h3>💡 Questions guides</h3>
            <ul>
                <li>Qui êtes-vous ? (Parcours, études)</li>
                <li>Quelles sont vos compétences clés ?</li>
                <li>Que recherchez-vous comme opportunité ?</li>
                <li>Pourquoi vous et pas un autre ?</li>
            </ul>
        </div>

        <div class="video-container">
            <video id="videoPreview" autoplay playsinline muted></video>
            <video id="videoPlayback" class="hidden" playsinline controls></video>
            <div class="timer" id="timer">01:00</div>
            <div class="progress-bar" id="progressBar"></div>
            
            <div id="transferOverlay" class="transfer-overlay hidden">
                <div class="spinner"></div>
                <div style="margin-bottom: 10px;">Transfert en cours...</div>
                <div style="width: 80%; background: #374151; height: 10px; border-radius: 5px; overflow: hidden;">
                    <div id="uploadProgressBar" style="width: 0%; height: 100%; background: var(--success); transition: width 0.2s;"></div>
                </div>
                <div id="uploadPercent" style="margin-top: 5px; font-size: 0.9rem;">0%</div>
            </div>
        </div>

        <div class="controls">
            <button id="btnStart" class="btn btn-primary">
                <span>🎬</span> Démarrer l'enregistrement
            </button>

            <button id="btnStop" class="btn btn-danger hidden">
                <span>⏹️</span> Arrêter
            </button>

            <div id="validationControls" class="hidden" style="display: flex; gap: 10px;">
                <button id="btnAccept" class="btn btn-success">
                    <span>✅</span> Accepter
                </button>
                <button id="btnDelete" class="btn btn-danger">
                    <span>🗑️</span> Supprimer
                </button>
            </div>
        </div>

        <div id="status" class="status">
            Appuyez sur démarrer pour enregistrer (max 60 secondes)
        </div>

        <div id="debug-log">
            <div class="log-entry">--- DEBUG LOGS ---</div>
        </div>

        <div style="text-align: center; margin-top: 2rem; font-size: 0.8rem; color: #6b7280;">
            <a href="https://www.ciaocv.com" style="color: #6b7280; text-decoration: none;">Retour au site principal</a>
        </div>
    </div>

    <script>
        const MAX_DURATION = 60;
        let mediaRecorder;
        let recordedChunks = [];
        let stream;
        let timerInterval;
        let startTime;
        let recordedBlob;
        let uploadedFileId = null;
        let uploadedFileName = null;

        const videoPreview = document.getElementById('videoPreview');
        const videoPlayback = document.getElementById('videoPlayback');
        const timer = document.getElementById('timer');
        const progressBar = document.getElementById('progressBar');
        const btnStart = document.getElementById('btnStart');
        const btnStop = document.getElementById('btnStop');
        const btnAccept = document.getElementById('btnAccept');
        const btnDelete = document.getElementById('btnDelete');
        const validationControls = document.getElementById('validationControls');
        const transferOverlay = document.getElementById('transferOverlay');
        const uploadProgressBar = document.getElementById('uploadProgressBar');
        const uploadPercent = document.getElementById('uploadPercent');
        const status = document.getElementById('status');
        const debugLog = document.getElementById('debug-log');
        const cameraSelect = document.getElementById('cameraSelect');
        const micSelect = document.getElementById('micSelect');
        const settingsPanel = document.getElementById('settingsPanel');

        function log(msg, data = null) {
            const time = new Date().toISOString().split('T')[1].split('.')[0];
            const entry = document.createElement('div');
            entry.className = 'log-entry';
            let logText = '<span class="log-time">[' + time + ']</span> ' + msg;
            if (data) {
                try {
                    logText += ' ' + (typeof data === 'object' ? JSON.stringify(data) : data);
                } catch(e) {
                    logText += ' [Unserializable]';
                }
            }
            entry.innerHTML = logText;
            debugLog.appendChild(entry);
            debugLog.scrollTop = debugLog.scrollHeight;
            console.log('[' + time + '] ' + msg, data || '');
        }

        async function getDevices() {
            try {
                const devices = await navigator.mediaDevices.enumerateDevices();
                
                cameraSelect.innerHTML = '';
                micSelect.innerHTML = '';

                const cameras = devices.filter(d => d.kind === 'videoinput');
                const mics = devices.filter(d => d.kind === 'audioinput');

                cameras.forEach((camera, i) => {
                    const option = document.createElement('option');
                    option.value = camera.deviceId;
                    option.text = camera.label || 'Caméra ' + (i + 1);
                    cameraSelect.appendChild(option);
                });

                mics.forEach((mic, i) => {
                    const option = document.createElement('option');
                    option.value = mic.deviceId;
                    option.text = mic.label || 'Micro ' + (i + 1);
                    micSelect.appendChild(option);
                });

                if (stream) {
                    const vt = stream.getVideoTracks()[0];
                    const at = stream.getAudioTracks()[0];
                    if (vt) {
                        const s = vt.getSettings();
                        if (s.deviceId) cameraSelect.value = s.deviceId;
                    }
                    if (at) {
                        const s = at.getSettings();
                        if (s.deviceId) micSelect.value = s.deviceId;
                    }
                }

                log('Périphériques listés', { cameras: cameras.length, mics: mics.length });
            } catch (err) {
                log('Erreur enumerateDevices', err.message);
            }
        }

        cameraSelect.onchange = initCamera;
        micSelect.onchange = initCamera;

        async function initCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }

            log('Initialisation caméra...');
            try {
                const videoDeviceId = cameraSelect.value;
                const audioDeviceId = micSelect.value;

                const constraints = {
                    video: { 
                        deviceId: videoDeviceId ? { exact: videoDeviceId } : undefined,
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    },
                    audio: {
                        deviceId: audioDeviceId ? { exact: audioDeviceId } : undefined
                    }
                };

                if (!videoDeviceId) {
                    delete constraints.video.deviceId;
                    constraints.video.facingMode = 'user';
                }

                log('Contraintes:', constraints);

                stream = await navigator.mediaDevices.getUserMedia(constraints);
                log('Flux obtenu', stream.id);
                
                videoPreview.srcObject = stream;

                stream.getVideoTracks().forEach(track => {
                    log('Piste vidéo:', track.label);
                });

                status.textContent = 'Caméra prête. Appuyez sur démarrer.';
                btnStart.disabled = false;

                if (cameraSelect.options.length === 0) {
                    getDevices();
                }

            } catch (err) {
                log('Erreur initCamera', err.message);
                status.textContent = 'Erreur: Impossible d\'accéder à la caméra';
                status.classList.add('error');
                btnStart.disabled = true;
            }
        }

        function startRecording() {
            log('Démarrage enregistrement');
            recordedChunks = [];
            settingsPanel.classList.add('hidden');
            
            let mimeType = 'video/webm;codecs=vp9';
            if (!MediaRecorder.isTypeSupported(mimeType)) {
                mimeType = 'video/webm;codecs=vp8';
            }
            if (!MediaRecorder.isTypeSupported(mimeType)) {
                mimeType = 'video/webm';
            }
            if (!MediaRecorder.isTypeSupported(mimeType)) {
                mimeType = 'video/mp4';
            }
            log('Format choisi:', mimeType);

            try {
                mediaRecorder = new MediaRecorder(stream, { 
                    mimeType,
                    videoBitsPerSecond: 1000000
                });
            } catch (e) {
                log('Erreur MediaRecorder, essai sans options', e.message);
                mediaRecorder = new MediaRecorder(stream);
            }

            mediaRecorder.ondataavailable = (e) => {
                if (e.data.size > 0) {
                    recordedChunks.push(e.data);
                    log('Chunk reçu, taille:', e.data.size);
                }
            };

            mediaRecorder.onstop = () => {
                log('Arrêt MediaRecorder triggered');
                recordedBlob = new Blob(recordedChunks, { type: mediaRecorder.mimeType });
                log('Blob créé, taille totale:', recordedBlob.size);
                
                videoPlayback.src = URL.createObjectURL(recordedBlob);
                
                uploadToB2();
            };

            mediaRecorder.start(100);
            log('MediaRecorder démarré');
            
            startTime = Date.now();
            timerInterval = setInterval(updateTimer, 100);
            
            setTimeout(() => {
                if (mediaRecorder && mediaRecorder.state === 'recording') {
                    log('Auto-stop 60s atteint');
                    stopRecording();
                }
            }, MAX_DURATION * 1000);

            showRecordingMode();
        }

        function stopRecording() {
            log('Arrêt demandé');
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
                log('MediaRecorder.stop() appelé');
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
            log('Début procédure upload automatique');
            showTransferMode();
            
            try {
                if (!recordedBlob || recordedBlob.size === 0) {
                    throw new Error('Aucune vidéo enregistrée (taille 0)');
                }

                const formData = new FormData();
                formData.append('action', 'get_upload_url');
                
                log('Appel backend get_upload_url...');
                const response = await fetch('record.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) throw new Error('Erreur réseau get_upload_url');
                const uploadInfo = await response.json();
                if (uploadInfo.error) throw new Error(uploadInfo.error);

                const arrayBuffer = await recordedBlob.arrayBuffer();
                const hashBuffer = await crypto.subtle.digest('SHA-1', arrayBuffer);
                const hashArray = Array.from(new Uint8Array(hashBuffer));
                const sha1 = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');

                log('Envoi vers B2...');
                
                await new Promise((resolve, reject) => {
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', uploadInfo.uploadUrl, true);
                    
                    xhr.setRequestHeader('Authorization', uploadInfo.authToken);
                    xhr.setRequestHeader('X-Bz-File-Name', encodeURIComponent(uploadInfo.fileName));
                    xhr.setRequestHeader('Content-Type', 'video/webm');
                    xhr.setRequestHeader('X-Bz-Content-Sha1', sha1);

                    xhr.upload.onprogress = (e) => {
                        if (e.lengthComputable) {
                            const percentComplete = Math.round((e.loaded / e.total) * 100);
                            uploadProgressBar.style.width = percentComplete + '%';
                            uploadPercent.textContent = percentComplete + '%';
                        }
                    };

                    xhr.onload = () => {
                        if (xhr.status >= 200 && xhr.status < 300) {
                            const result = JSON.parse(xhr.responseText);
                            log('Succès upload B2', result);
                            uploadedFileId = result.fileId;
                            uploadedFileName = result.fileName;
                            resolve(result);
                        } else {
                            reject(new Error('Échec upload B2: ' + xhr.status));
                        }
                    };

                    xhr.onerror = () => reject(new Error('Erreur réseau XHR'));

                    xhr.send(recordedBlob);
                });

                showValidationMode();

            } catch (err) {
                log('EXCEPTION:', err.message);
                status.textContent = '❌ Erreur: ' + err.message;
                status.classList.add('error');
                transferOverlay.classList.add('hidden');
                showInitialMode();
            }
        }

        async function deleteVideo() {
            if (!uploadedFileId || !uploadedFileName) {
                showInitialMode();
                return;
            }
             
            if (!confirm('Voulez-vous vraiment supprimer cette vidéo ?')) return;

            log('Suppression demandée...');
            status.textContent = 'Suppression en cours...';
             
            try {
                const formData = new FormData();
                formData.append('action', 'delete_file');
                formData.append('fileId', uploadedFileId);
                formData.append('fileName', uploadedFileName);
                 
                const response = await fetch('record.php', {
                    method: 'POST',
                    body: formData
                });
                 
                const res = await response.json();
                if (res.success) {
                    log('Vidéo supprimée');
                    showInitialMode();
                } else {
                    throw new Error(res.error || 'Erreur suppression');
                }
            } catch(e) {
                log('Erreur suppression', e.message);
                alert('Erreur lors de la suppression: ' + e.message);
            }
        }

        function showRecordingMode() {
            btnStart.classList.add('hidden');
            btnStop.classList.remove('hidden');
            validationControls.classList.add('hidden');
            timer.classList.add('recording');
            videoPreview.classList.remove('hidden');
            videoPlayback.classList.add('hidden');
            settingsPanel.classList.add('hidden');
            transferOverlay.classList.add('hidden');
            status.textContent = 'Enregistrement en cours...';
            status.classList.remove('error', 'success');
        }

        function showTransferMode() {
            btnStart.classList.add('hidden');
            btnStop.classList.add('hidden');
            validationControls.classList.add('hidden');
            timer.classList.remove('recording');
            
            videoPreview.classList.add('hidden');
            videoPlayback.classList.add('hidden'); 
            
            settingsPanel.classList.add('hidden');
            transferOverlay.classList.remove('hidden');
            
            uploadProgressBar.style.width = '0%';
            uploadPercent.textContent = '0%';
            
            status.textContent = 'Envoi vers le serveur...';
        }

        function showValidationMode() {
            btnStart.classList.add('hidden');
            btnStop.classList.add('hidden');
            validationControls.classList.remove('hidden');
            validationControls.style.display = 'flex';
            timer.classList.remove('recording');
            
            videoPreview.classList.add('hidden');
            videoPlayback.classList.remove('hidden');
            
            settingsPanel.classList.add('hidden');
            transferOverlay.classList.add('hidden');
            status.textContent = 'Vérifiez la vidéo. Accepter pour garder, Supprimer pour recommencer.';
        }

        function showInitialMode() {
            btnStart.classList.remove('hidden');
            btnStop.classList.add('hidden');
            validationControls.classList.add('hidden');
            timer.classList.remove('recording');
            timer.textContent = '01:00';
            progressBar.style.width = '0%';
            videoPreview.classList.remove('hidden');
            videoPlayback.classList.add('hidden');
            settingsPanel.classList.remove('hidden');
            transferOverlay.classList.add('hidden');
            status.textContent = 'Appuyez sur démarrer pour enregistrer (max 60 secondes)';
            status.classList.remove('error', 'success');
            
            uploadedFileId = null;
            uploadedFileName = null;
        }

        btnStart.addEventListener('click', startRecording);
        btnStop.addEventListener('click', stopRecording);
        btnAccept.addEventListener('click', () => {
            window.location.href = 'view.php';
        });
        btnDelete.addEventListener('click', deleteVideo);
        
        initCamera();
    </script>
</body>
</html>
