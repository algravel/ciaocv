<?php
/**
 * Postuler à une offre - Enregistrement vidéo obligatoire
 */
session_start();
require_once __DIR__ . '/db.php';

// Vérifier si l'utilisateur est connecté
$userId = $_SESSION['user_id'] ?? null;
$userEmail = $_SESSION['user_email'] ?? null;
$userName = $_SESSION['user_first_name'] ?? null;

if (!$userId) {
    header('Location: login.php');
    exit;
}

$jobId = (int)($_GET['id'] ?? 0);
$job = null;
$alreadyApplied = false;

if ($jobId && $db) {
    try {
        $hasDeletedAt = $db->query("SHOW COLUMNS FROM jobs LIKE 'deleted_at'")->rowCount() > 0;
        $stmt = $hasDeletedAt
            ? $db->prepare('SELECT * FROM jobs WHERE id = ? AND deleted_at IS NULL')
            : $db->prepare('SELECT * FROM jobs WHERE id = ?');
        $stmt->execute([$jobId]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($job) {
            $stmtQ = $db->prepare('SELECT * FROM job_questions WHERE job_id = ? ORDER BY sort_order');
            $stmtQ->execute([$jobId]);
            $job['questions'] = $stmtQ->fetchAll(PDO::FETCH_ASSOC);
            
            // Vérifier si l'utilisateur a déjà postulé
            $stmtCheck = $db->prepare('SELECT COUNT(*) FROM applications WHERE job_id = ? AND candidate_email = ?');
            $stmtCheck->execute([$jobId, $userEmail]);
            $alreadyApplied = $stmtCheck->fetchColumn() > 0;
        }
    } catch (PDOException $e) {
        $job = null;
    }
}

if (!$job) {
    header('Location: candidate-jobs.php');
    exit;
}

// Charger les variables B2
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

// API pour upload et soumission
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
        
        $fileName = 'application_' . $userId . '_job' . $jobId . '_' . date('Y-m-d_H-i-s') . '_' . bin2hex(random_bytes(4)) . '.webm';
        
        echo json_encode([
            'uploadUrl' => $uploadUrlResponse['uploadUrl'],
            'authToken' => $uploadUrlResponse['authorizationToken'],
            'fileName' => $fileName,
            'downloadUrl' => $authResponse['downloadUrl']
        ]);
        exit;
    }

    if ($_POST['action'] === 'submit_application') {
        $videoUrl = $_POST['video_url'] ?? '';
        
        if (empty($videoUrl)) {
            echo json_encode(['error' => 'La vidéo est obligatoire pour postuler']);
            exit;
        }
        
        if ($alreadyApplied) {
            echo json_encode(['error' => 'Vous avez déjà postulé à cette offre']);
            exit;
        }
        
        if ($db) {
            try {
                // Vérifier que la table applications a une colonne user_id
                $cols = $db->query("SHOW COLUMNS FROM applications")->fetchAll(PDO::FETCH_COLUMN);
                if (!in_array('user_id', $cols)) {
                    $db->exec("ALTER TABLE applications ADD COLUMN user_id INT DEFAULT NULL AFTER id");
                }
                
                // Insérer la candidature avec la vidéo
                $stmtInsert = $db->prepare('INSERT INTO applications (user_id, job_id, candidate_email, candidate_name, video_url, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
                $stmtInsert->execute([$userId, $jobId, $userEmail, $userName, $videoUrl, 'new']);
                
                echo json_encode(['success' => true, 'redirect' => 'candidate-applications.php?success=1']);
            } catch (PDOException $e) {
                echo json_encode(['error' => 'Erreur: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['error' => 'Erreur de connexion à la base de données']);
        }
        exit;
    }

    if ($_POST['action'] === 'delete_file') {
        $fileId = $_POST['fileId'] ?? '';
        $fileName = $_POST['fileName'] ?? '';

        if ($fileId && $fileName) {
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
        }

        echo json_encode(['success' => true]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($job['title']) ?> - Postuler - CiaoCV</title>
    <link rel="stylesheet" href="assets/css/design-system.css?v=<?= ASSET_VERSION ?>">
    <style>
        .video-record-section {
            background: var(--bg-alt, #f8fafc);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }
        .video-record-section h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
        }
        .video-container {
            position: relative;
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            aspect-ratio: 9/16;
            max-height: 400px;
            margin: 1rem auto;
        }
        .video-container video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .video-timer {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(0,0,0,0.7);
            color: #fff;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.2rem;
        }
        .video-timer.recording {
            background: #dc2626;
            animation: pulse 1s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        .video-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 4px;
            background: var(--primary);
            transition: width 0.1s linear;
        }
        .transfer-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.85);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        .spinner {
            width: 48px;
            height: 48px;
            border: 4px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1rem;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .video-controls {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }
        .video-controls button {
            flex: 1;
            min-width: 120px;
        }
        .btn-danger { background: #dc2626; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-success { background: #16a34a; }
        .btn-success:hover { background: #15803d; }
        .video-ready-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #dcfce7;
            color: #166534;
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .questions-guide {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .questions-guide h4 {
            margin: 0 0 0.75rem 0;
            font-size: 0.95rem;
            color: var(--text);
        }
        .questions-guide ol {
            margin: 0;
            padding-left: 1.25rem;
        }
        .questions-guide li {
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <div class="app-shell">
        <?php $sidebarActive = 'jobs'; include __DIR__ . '/includes/candidate-sidebar.php'; ?>
        <main class="app-main">
        <?php include __DIR__ . '/includes/candidate-header.php'; ?>
        <div class="app-main-content layout-app">
        
        <?php if ($alreadyApplied): ?>
            <div class="success" style="padding:1rem;background:#efe;border:1px solid #cfc;border-radius:8px;margin-bottom:1rem;color:#060;">
                ✓ Vous avez déjà postulé à cette offre. <a href="candidate-applications.php" style="color:var(--primary);">Voir mes candidatures</a>
            </div>
        <?php endif; ?>
        
        <div class="job-card">
            <h1><?= htmlspecialchars($job['title']) ?></h1>
            <?php
            $jobLat = isset($job['latitude']) && $job['latitude'] !== '' && $job['latitude'] !== null ? (float)$job['latitude'] : null;
            $jobLng = isset($job['longitude']) && $job['longitude'] !== '' && $job['longitude'] !== null ? (float)$job['longitude'] : null;
            $hasLocation = (!empty($job['location_name']) || ($jobLat !== null && $jobLng !== null));
            if ($hasLocation): ?>
            <div class="job-apply-location" style="margin-bottom:1rem;padding:0.75rem 1rem;background:var(--bg-alt, #f3f4f6);border-radius:8px;font-size:0.9rem;">
                <strong>📍 Lieu du poste</strong>
                <?php if (!empty($job['location_name'])): ?>
                    <div style="margin-top:0.25rem;color:var(--text-secondary, #6B7280);"><?= htmlspecialchars($job['location_name']) ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($job['description'])): ?>
                <div class="desc" style="margin-bottom:1rem;"><?= nl2br(htmlspecialchars($job['description'])) ?></div>
            <?php endif; ?>
        </div>

        <?php if ($alreadyApplied): ?>
            <div style="padding:1.5rem;background:var(--bg-alt, #f3f4f6);border-radius:12px;text-align:center;">
                <p style="margin:0 0 1rem 0;">Votre candidature a été envoyée pour cette offre.</p>
                <a href="candidate-applications.php" class="btn btn-secondary">Voir mes candidatures</a>
            </div>
        <?php else: ?>
            <!-- Section enregistrement vidéo -->
            <div class="video-record-section">
                <h3>🎬 Enregistre ta vidéo de candidature</h3>
                <p style="color:var(--text-secondary);margin-bottom:1rem;">
                    Réponds aux questions de l'employeur en vidéo (90 secondes max.)
                </p>

                <?php if (!empty($job['questions'])): ?>
                <div class="questions-guide">
                    <h4>Questions à répondre :</h4>
                    <ol>
                        <?php foreach ($job['questions'] as $q): ?>
                            <li><?= htmlspecialchars($q['question_text']) ?></li>
                        <?php endforeach; ?>
                    </ol>
                </div>
                <?php endif; ?>

                <div id="videoReadyBadge" class="video-ready-badge hidden">
                    ✓ Vidéo prête à envoyer
                </div>

                <div id="videoRecordBlock">
                    <div class="video-container">
                        <video id="videoPreview" autoplay playsinline muted></video>
                        <video id="videoPlayback" class="hidden" playsinline controls></video>
                        <div class="video-timer" id="timer">01:30</div>
                        <div class="video-progress" id="progressBar"></div>
                        
                        <div id="transferOverlay" class="transfer-overlay hidden">
                            <div class="spinner"></div>
                            <div style="margin-bottom:10px;">Transfert en cours...</div>
                            <div style="width:80%;background:#374151;height:10px;border-radius:5px;overflow:hidden;">
                                <div id="uploadProgressBar" style="width:0%;height:100%;background:var(--success, #16a34a);transition:width 0.2s;"></div>
                            </div>
                            <div id="uploadPercent" style="margin-top:5px;font-size:0.9rem;">0%</div>
                        </div>
                    </div>

                    <div class="video-controls" id="recordControls">
                        <button id="btnStart" class="btn">🎬 Commencer l'enregistrement</button>
                        <button id="btnStop" class="btn btn-danger hidden">⏹ Arrêter</button>
                    </div>

                    <div class="video-controls hidden" id="validationControls">
                        <button id="btnDelete" class="btn btn-secondary">🔄 Recommencer</button>
                        <button id="btnAccept" class="btn btn-success">✓ Garder cette vidéo</button>
                    </div>
                </div>

                <div id="submitSection" class="hidden" style="margin-top:1.5rem;text-align:center;">
                    <button id="btnSubmit" class="btn" style="font-size:1.1rem;padding:1rem 2rem;">
                        📤 Envoyer ma candidature
                    </button>
                    <p style="margin-top:0.5rem;font-size:0.9rem;color:var(--text-secondary);">
                        Ta vidéo sera envoyée à l'employeur
                    </p>
                </div>
            </div>
        <?php endif; ?>

        </div>
        </main>
    </div>

    <?php if (!$alreadyApplied): ?>
    <script>
        const MAX_DURATION = 90; // 90 secondes
        const jobId = <?= $jobId ?>;
        let mediaRecorder, recordedChunks = [], stream, timerInterval, startTime, recordedBlob;
        let uploadedFileId = null, uploadedFileName = null, downloadUrl = null, finalVideoUrl = null;

        const videoPreview = document.getElementById('videoPreview');
        const videoPlayback = document.getElementById('videoPlayback');
        const timer = document.getElementById('timer');
        const progressBar = document.getElementById('progressBar');
        const btnStart = document.getElementById('btnStart');
        const btnStop = document.getElementById('btnStop');
        const btnAccept = document.getElementById('btnAccept');
        const btnDelete = document.getElementById('btnDelete');
        const btnSubmit = document.getElementById('btnSubmit');
        const recordControls = document.getElementById('recordControls');
        const validationControls = document.getElementById('validationControls');
        const transferOverlay = document.getElementById('transferOverlay');
        const uploadProgressBar = document.getElementById('uploadProgressBar');
        const uploadPercent = document.getElementById('uploadPercent');
        const videoRecordBlock = document.getElementById('videoRecordBlock');
        const videoReadyBadge = document.getElementById('videoReadyBadge');
        const submitSection = document.getElementById('submitSection');

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
                
                const response = await fetch('candidate-job-apply.php?id=' + jobId, { method: 'POST', body: formData });
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

        function acceptVideo() {
            const bucketName = 'ciaocv';
            finalVideoUrl = downloadUrl + '/file/' + bucketName + '/' + uploadedFileName;
            
            // Arrêter la caméra
            if (stream) {
                stream.getTracks().forEach(t => t.stop());
            }
            
            // Afficher la section de soumission
            videoRecordBlock.classList.add('hidden');
            videoReadyBadge.classList.remove('hidden');
            submitSection.classList.remove('hidden');
        }

        async function deleteVideo() {
            if (uploadedFileId && uploadedFileName) {
                const formData = new FormData();
                formData.append('action', 'delete_file');
                formData.append('fileId', uploadedFileId);
                formData.append('fileName', uploadedFileName);
                await fetch('candidate-job-apply.php?id=' + jobId, { method: 'POST', body: formData });
            }
            uploadedFileId = null;
            uploadedFileName = null;
            finalVideoUrl = null;
            showInitialMode();
        }

        async function submitApplication() {
            if (!finalVideoUrl) {
                alert('Tu dois d\'abord enregistrer une vidéo.');
                return;
            }
            
            btnSubmit.disabled = true;
            btnSubmit.textContent = 'Envoi en cours...';
            
            const formData = new FormData();
            formData.append('action', 'submit_application');
            formData.append('video_url', finalVideoUrl);
            
            try {
                const response = await fetch('candidate-job-apply.php?id=' + jobId, { method: 'POST', body: formData });
                const result = await response.json();
                
                if (result.success) {
                    window.location.href = result.redirect;
                } else {
                    alert(result.error || 'Erreur lors de la soumission');
                    btnSubmit.disabled = false;
                    btnSubmit.textContent = '📤 Envoyer ma candidature';
                }
            } catch (err) {
                alert('Erreur: ' + err.message);
                btnSubmit.disabled = false;
                btnSubmit.textContent = '📤 Envoyer ma candidature';
            }
        }

        function showRecordingMode() {
            btnStart.classList.add('hidden');
            btnStop.classList.remove('hidden');
            validationControls.classList.add('hidden');
            timer.classList.add('recording');
            videoPreview.classList.remove('hidden');
            videoPlayback.classList.add('hidden');
            transferOverlay.classList.add('hidden');
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
        }

        function showValidationMode() {
            recordControls.classList.add('hidden');
            validationControls.classList.remove('hidden');
            timer.classList.remove('recording');
            videoPreview.classList.add('hidden');
            videoPlayback.classList.remove('hidden');
            transferOverlay.classList.add('hidden');
        }

        function showInitialMode() {
            recordControls.classList.remove('hidden');
            btnStart.classList.remove('hidden');
            btnStop.classList.add('hidden');
            validationControls.classList.add('hidden');
            timer.classList.remove('recording');
            timer.textContent = '01:30';
            progressBar.style.width = '0%';
            videoPreview.classList.remove('hidden');
            videoPlayback.classList.add('hidden');
            transferOverlay.classList.add('hidden');
            videoRecordBlock.classList.remove('hidden');
            videoReadyBadge.classList.add('hidden');
            submitSection.classList.add('hidden');
            initCamera();
        }

        btnStart.addEventListener('click', startRecording);
        btnStop.addEventListener('click', stopRecording);
        btnAccept.addEventListener('click', acceptVideo);
        btnDelete.addEventListener('click', deleteVideo);
        btnSubmit.addEventListener('click', submitApplication);

        initCamera();
    </script>
    <?php endif; ?>
</body>
</html>
