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
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
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

        /* Settings area */
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

        .video-container {
            position: relative;
            width: 100%;
            /* Auto aspect ratio based on stream, but constrained height */
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
            /* Change cover to contain to avoid zooming/cropping */
            object-fit: contain; 
            width: auto;
            height: auto;
        }

        #videoPlayback {
            display: none;
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

        /* Debug Log Styles */
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

        /* iPhone notch safe area */
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
            <a href="index2.php" class="back-link">← Retour</a>
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

        <div class="video-container">
            <video id="videoPreview" autoplay playsinline muted></video>
            <video id="videoPlayback" playsinline controls></video>
            <div class="timer" id="timer">01:00</div>
            <div class="progress-bar" id="progressBar"></div>
        </div>

        <div class="controls">
            <!-- État initial -->
            <button id="btnStart" class="btn btn-primary">
                <span>🎬</span> Démarrer l'enregistrement
            </button>

            <!-- Pendant l'enregistrement -->
            <button id="btnStop" class="btn btn-danger hidden">
                <span>⏹️</span> Arrêter
            </button>

            <!-- Après l'enregistrement -->
            <button id="btnSave" class="btn btn-success hidden">
                <span>💾</span> Sauvegarder la vidéo
            </button>
            <button id="btnRetry" class="btn btn-secondary hidden">
                <span>🔄</span> Recommencer
            </button>
        </div>

        <div id="status" class="status">
            Appuyez sur démarrer pour enregistrer (max 60 secondes)
        </div>

        <!-- Debug Log Area -->
        <div id="debug-log">
            <div class="log-entry">--- DEBUG LOGS ---</div>
        </div>
    </div>

    <script>
        const MAX_DURATION = 60; // secondes
        let mediaRecorder;
        let recordedChunks = [];
        let stream;
        let timerInterval;
        let startTime;
        let recordedBlob;

        // Éléments DOM
        const videoPreview = document.getElementById('videoPreview');
        const videoPlayback = document.getElementById('videoPlayback');
        const timer = document.getElementById('timer');
        const progressBar = document.getElementById('progressBar');
        const btnStart = document.getElementById('btnStart');
        const btnStop = document.getElementById('btnStop');
        const btnSave = document.getElementById('btnSave');
        const btnRetry = document.getElementById('btnRetry');
        const status = document.getElementById('status');
        const debugLog = document.getElementById('debug-log');
        const cameraSelect = document.getElementById('cameraSelect');
        const micSelect = document.getElementById('micSelect');
        const settingsPanel = document.getElementById('settingsPanel');

        function log(msg, data = null) {
            const time = new Date().toISOString().split('T')[1].split('.')[0];
            const entry = document.createElement('div');
            entry.className = 'log-entry';
            
            let logText = `<span class="log-time">[${time}]</span> ${msg}`;
            if (data) {
                try {
                    logText += ' ' + (typeof data === 'object' ? JSON.stringify(data) : data);
                } catch(e) {
                    logText += ' [Circular/Unserializable]';
                }
            }
            
            entry.innerHTML = logText;
            debugLog.appendChild(entry);
            debugLog.scrollTop = debugLog.scrollHeight;
            console.log(`[${time}] ${msg}`, data || '');
        }

        // Remplir les listes de périphériques
        async function getDevices() {
            try {
                // Demander la permission d'abord pour avoir les labels
                if (!stream) {
                    await initCamera();
                }

                const devices = await navigator.mediaDevices.enumerateDevices();
                
                // Vider les listes
                cameraSelect.innerHTML = '';
                micSelect.innerHTML = '';

                const cameras = devices.filter(d => d.kind === 'videoinput');
                const mics = devices.filter(d => d.kind === 'audioinput');

                cameras.forEach(camera => {
                    const option = document.createElement('option');
                    option.value = camera.deviceId;
                    option.text = camera.label || `Caméra ${cameraSelect.length + 1}`;
                    cameraSelect.appendChild(option);
                });

                mics.forEach(mic => {
                    const option = document.createElement('option');
                    option.value = mic.deviceId;
                    option.text = mic.label || `Micro ${micSelect.length + 1}`;
                    micSelect.appendChild(option);
                });

                // Sélectionner le périphérique actuel si possible
                const currentVideoTrack = stream.getVideoTracks()[0];
                const currentAudioTrack = stream.getAudioTracks()[0];

                if (currentVideoTrack) {
                    const settings = currentVideoTrack.getSettings();
                    if (settings.deviceId) cameraSelect.value = settings.deviceId;
                }
                if (currentAudioTrack) {
                    const settings = currentAudioTrack.getSettings();
                    if (settings.deviceId) micSelect.value = settings.deviceId;
                }

                log('Périphériques listés', { cameras: cameras.length, mics: mics.length });

            } catch (err) {
                log('Erreur enumerateDevices', err.message);
            }
        }

        // Changement de périphérique
        cameraSelect.onchange = initCamera;
        micSelect.onchange = initCamera;

        // Initialisation de la caméra
        async function initCamera() {
            // Arrêter le flux existant
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
                        width: { ideal: 1280 }, // Landscape preference usually better for monitors
                        height: { ideal: 720 }
                    },
                    audio: {
                        deviceId: audioDeviceId ? { exact: audioDeviceId } : undefined
                    }
                };

                // Si pas de sélection (premier lancement), on laisse le navigateur choisir (ou facingMode user)
                if (!videoDeviceId) {
                     delete constraints.video.deviceId;
                     constraints.video.facingMode = 'user';
                }

                log('Contraintes demandées:', constraints);

                stream = await navigator.mediaDevices.getUserMedia(constraints);
                log('Flux obtenu', stream.id);
                
                videoPreview.srcObject = stream;
                
                // Log track info
                stream.getVideoTracks().forEach(track => {
                    log('Piste vidéo:', track.label + ' ' + JSON.stringify(track.getSettings()));
                });

                status.textContent = 'Caméra prête. Appuyez sur démarrer.';
                btnStart.disabled = false;

                // Rafraichir la liste des devices une fois qu'on a la permission (si vide)
                if (cameraSelect.options.length === 0) {
                    getDevices();
                }

            } catch (err) {
                log('Erreur initCamera', err.message);
                status.textContent = 'Erreur: Impossible d\'accéder à la caméra';
                status.classList.add('error');
                btnStart.disabled = true;
                console.error('Erreur caméra:', err);
            }
        }

        // Démarrer l'enregistrement
        function startRecording() {
            log('Démarrage enregistrement demandé');
            recordedChunks = [];
            
            // Masquer les réglages pendant l'enregistrement
            settingsPanel.classList.add('hidden');
            
            // Déterminer le format supporté
            let mimeType = 'video/webm;codecs=vp9';
            if (!MediaRecorder.isTypeSupported(mimeType)) {
                log('VP9 non supporté, essai VP8');
                mimeType = 'video/webm;codecs=vp8';
            }
            if (!MediaRecorder.isTypeSupported(mimeType)) {
                log('VP8 non supporté, essai video/webm');
                mimeType = 'video/webm';
            }
            if (!MediaRecorder.isTypeSupported(mimeType)) {
                log('video/webm non supporté, essai mp4');
                mimeType = 'video/mp4';
            }
            log('Format choisi:', mimeType);

            try {
                mediaRecorder = new MediaRecorder(stream, { 
                    mimeType,
                    videoBitsPerSecond: 1000000 // 1 Mbps limit
                });
            } catch (e) {
                log('Erreur création MediaRecorder avec options, essai sans options', e.message);
                mediaRecorder = new MediaRecorder(stream);
            }

            mediaRecorder.ondataavailable = (e) => {
                if (e.data.size > 0) {
                    recordedChunks.push(e.data);
                    log('Chunk reçu, taille:', e.data.size);
                } else {
                    log('Chunk vide reçu');
                }
            };

            mediaRecorder.onstop = () => {
                log('Arrêt MediaRecorder triggered');
                recordedBlob = new Blob(recordedChunks, { type: mediaRecorder.mimeType });
                log('Blob créé, taille totale:', recordedBlob.size);
                
                videoPlayback.src = URL.createObjectURL(recordedBlob);
                showPlaybackMode();
            };

            mediaRecorder.start(100);
            log('MediaRecorder démarré (state: ' + mediaRecorder.state + ')');
            
            startTime = Date.now();
            
            // Timer et progress bar
            timerInterval = setInterval(updateTimer, 100);
            
            // Auto-stop après 60 secondes
            setTimeout(() => {
                if (mediaRecorder && mediaRecorder.state === 'recording') {
                    log('Auto-stop timer atteint');
                    stopRecording();
                }
            }, MAX_DURATION * 1000);

            showRecordingMode();
        }

        // Arrêter l'enregistrement
        function stopRecording() {
            log('Arrêt demandé');
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
                log('MediaRecorder.stop() appelé');
                clearInterval(timerInterval);
            } else {
                log('Stop ignoré: pas en cours d\'enregistrement');
            }
        }

        // Mettre à jour le timer
        function updateTimer() {
            const elapsed = (Date.now() - startTime) / 1000;
            const remaining = Math.max(0, MAX_DURATION - elapsed);
            const mins = Math.floor(remaining / 60);
            const secs = Math.floor(remaining % 60);
            timer.textContent = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
            progressBar.style.width = `${(elapsed / MAX_DURATION) * 100}%`;
        }

        // Upload vers B2
        async function uploadToB2() {
            log('Début procédure upload');
            status.textContent = 'Préparation de l\'upload...';
            btnSave.disabled = true;
            btnRetry.disabled = true;

            try {
                if (!recordedBlob || recordedBlob.size === 0) {
                    throw new Error('Aucune vidéo enregistrée (taille 0)');
                }

                // Obtenir l'URL d'upload
                const formData = new FormData();
                formData.append('action', 'get_upload_url');
                
                log('Appel backend get_upload_url...');
                const response = await fetch('record.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP Error ${response.status} fetching upload URL`);
                }

                const uploadInfo = await response.json();
                
                if (uploadInfo.error) {
                    throw new Error(uploadInfo.error);
                }

                status.textContent = 'Upload en cours...';

                // Calculer le SHA1
                const arrayBuffer = await recordedBlob.arrayBuffer();
                const hashBuffer = await crypto.subtle.digest('SHA-1', arrayBuffer);
                const hashArray = Array.from(new Uint8Array(hashBuffer));
                const sha1 = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');

                // Upload vers B2
                log('Envoi vers B2 URL:', uploadInfo.uploadUrl);
                const uploadResponse = await fetch(uploadInfo.uploadUrl, {
                    method: 'POST',
                    headers: {
                        'Authorization': uploadInfo.authToken,
                        'X-Bz-File-Name': encodeURIComponent(uploadInfo.fileName),
                        'Content-Type': 'video/webm',
                        'X-Bz-Content-Sha1': sha1
                    },
                    body: recordedBlob
                });

                if (!uploadResponse.ok) {
                    throw new Error('Échec upload: ' + uploadResponse.status);
                }

                const result = await uploadResponse.json();
                log('Succès upload B2', result);
                
                status.textContent = '✅ Vidéo sauvegardée avec succès!';
                status.classList.remove('error');
                status.classList.add('success');
                
                // Proposer de voir les vidéos
                setTimeout(() => {
                    btnSave.textContent = '📹 Voir mes vidéos';
                    btnSave.disabled = false;
                    btnSave.onclick = () => window.location.href = 'view.php';
                }, 1500);

            } catch (err) {
                log('EXCEPTION:', err.message);
                status.textContent = '❌ Erreur: ' + err.message;
                status.classList.add('error');
                btnSave.disabled = false;
                btnRetry.disabled = false;
            }
        }

        // Modes d'affichage
        function showRecordingMode() {
            btnStart.classList.add('hidden');
            btnStop.classList.remove('hidden');
            btnSave.classList.add('hidden');
            btnRetry.classList.add('hidden');
            timer.classList.add('recording');
            videoPreview.classList.remove('hidden');
            videoPlayback.classList.add('hidden');
            status.textContent = 'Enregistrement en cours...';
            status.classList.remove('error', 'success');
        }

        function showPlaybackMode() {
            btnStart.classList.add('hidden');
            btnStop.classList.add('hidden');
            btnSave.classList.remove('hidden');
            btnRetry.classList.remove('hidden');
            timer.classList.remove('recording');
            videoPreview.classList.add('hidden');
            videoPlayback.classList.remove('hidden');
            settingsPanel.classList.remove('hidden'); // Réafficher les réglages
            status.textContent = 'Prévisualisez votre vidéo, puis sauvegardez ou recommencez.';
        }

        function showInitialMode() {
            btnStart.classList.remove('hidden');
            btnStop.classList.add('hidden');
            btnSave.classList.add('hidden');
            btnRetry.classList.add('hidden');
            timer.classList.remove('recording');
            timer.textContent = '01:00';
            progressBar.style.width = '0%';
            videoPreview.classList.remove('hidden');
            videoPlayback.classList.add('hidden');
            settingsPanel.classList.remove('hidden'); // Réafficher les réglages
            status.textContent = 'Appuyez sur démarrer pour enregistrer (max 60 secondes)';
            status.classList.remove('error', 'success');
        }

        // Event listeners
        btnStart.addEventListener('click', startRecording);
        btnStop.addEventListener('click', stopRecording);
        btnSave.addEventListener('click', uploadToB2);
        btnRetry.addEventListener('click', () => {
            log('Recommencer cliqué');
            recordedChunks = [];
            recordedBlob = null;
            showInitialMode();
        });

        // Initialisation
        initCamera(); // Lance la cam, demande permissions, puis getDevices() sera appelé à la fin de initCamera
    </script>
</body>
</html>
