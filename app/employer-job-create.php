<?php
require_once __DIR__ . '/db.php';

$saved = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $questions = array_filter(array_map('trim', $_POST['questions'] ?? []));

    if (strlen($title) < 2) {
        $error = 'Le titre du poste est requis (min. 2 caractères).';
    } elseif (count($questions) > 5) {
        $error = 'Maximum 5 questions d\'entrevue.';
    } elseif ($db) {
        try {
            $stmt = $db->prepare('INSERT INTO jobs (employer_id, title, description) VALUES (1, ?, ?)');
            $stmt->execute([$title, $description]);
            $jobId = $db->lastInsertId();

            $stmtQ = $db->prepare('INSERT INTO job_questions (job_id, question_text, sort_order) VALUES (?, ?, ?)');
            foreach ($questions as $i => $q) {
                if ($q !== '') {
                    $stmtQ->execute([$jobId, $q, $i + 1]);
                }
            }
            $saved = true;
            header('Location: employer-job-view.php?id=' . $jobId);
            exit;
        } catch (PDOException $e) {
            header('Location: employer-job-view.php?id=1&demo=1');
            exit;
        }
    } else {
        header('Location: employer-job-view.php?id=1&demo=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un poste - CiaoCV</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --bg: #f3f4f6;
            --card-bg: #ffffff;
            --text: #1f2937;
            --text-light: #6b7280;
            --border: #e5e7eb;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: system-ui, sans-serif; }
        body { background: var(--bg); color: var(--text); min-height: 100vh; }
        .container { max-width: 600px; margin: 0 auto; padding: 1.5rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .logo { font-size: 1.25rem; font-weight: 800; color: var(--primary); text-decoration: none; }
        .back { color: var(--text-light); text-decoration: none; font-size: 0.9rem; }
        h1 { font-size: 1.5rem; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1.25rem; }
        label { display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem; }
        input, textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
        }
        textarea { min-height: 100px; resize: vertical; }
        .questions-list { margin-top: 1rem; }
        .questions-list .form-group { margin-bottom: 1rem; }
        .questions-list label { font-weight: 500; }
        .hint { font-size: 0.8rem; color: var(--text-light); margin-top: 0.25rem; }
        .btn {
            background: var(--primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn:hover { background: var(--primary-dark); }
        .btn-secondary { background: #6b7280; }
        .error { background: #fef2f2; color: #dc2626; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .footer { margin-top: 2rem; text-align: center; font-size: 0.8rem; color: var(--text-light); }
        .footer a { color: var(--text-light); }

        /* Vidéo maquette */
        .video-section {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .video-section h3 { font-size: 1rem; margin-bottom: 0.75rem; }
        .video-section .hint { margin-bottom: 1rem; }
        .video-container {
            position: relative;
            width: 100%;
            height: 40vh;
            min-height: 200px;
            background: #000;
            border-radius: 0.75rem;
            overflow: hidden;
            margin-bottom: 1rem;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .video-container video {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .video-timer {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 0.35rem 0.75rem;
            border-radius: 2rem;
            font-size: 1rem;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
            z-index: 5;
        }
        .video-timer.recording { background: #dc2626; }
        .video-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 4px;
            background: var(--primary);
            z-index: 5;
        }
        .video-controls { display: flex; gap: 0.75rem; flex-wrap: wrap; }
        .video-controls .btn { flex: 1; min-width: 120px; }
        .btn-danger { background: #dc2626; }
        .btn-success { background: #16a34a; }
        .btn-success:hover { background: #15803d; }
        .transfer-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.9);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            z-index: 10;
        }
        .transfer-overlay.hidden { display: none !important; }
        .spinner {
            width: 40px; height: 40px;
            border: 4px solid #374151;
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1rem;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .video-ready-badge {
            background: #d1fae5;
            color: #065f46;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .video-settings { margin-bottom: 1rem; }
        .video-settings select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            font-size: 0.875rem;
        }
        .video-settings label { font-size: 0.8rem; }
        .hidden { display: none !important; }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <a href="employer.php" class="logo">CiaoCV</a>
            <a href="employer.php" class="back">← Retour aux affichages</a>
        </header>

        <h1>Créer un nouveau poste</h1>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="title">Titre du poste *</label>
                <input type="text" id="title" name="title" required
                       value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                       placeholder="Ex: Développeur Frontend React">
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" placeholder="Décrivez le poste, les responsabilités..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label>Questions d'entrevue (max. 5)</label>
                <p class="hint">Ces questions seront affichées aux candidats pour guider leur pitch vidéo.</p>
                <div class="questions-list">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <div class="form-group">
                            <label>Question <?= $i ?></label>
                            <input type="text" name="questions[]"
                                   value="<?= htmlspecialchars($_POST['questions'][$i-1] ?? '') ?>"
                                   placeholder="Ex: Parlez-nous de votre expérience avec React">
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="form-group video-section">
                <h3>Vidéo de présentation du poste (60 sec)</h3>
                <p class="hint">Enregistrez une courte vidéo pour présenter le poste aux candidats. Maquette : enregistrement réel, sauvegarde simulée.</p>
                <div id="videoReadyBadge" class="video-ready-badge hidden">✓ Vidéo prête</div>
                <div id="videoRecordBlock">
                    <div class="video-settings">
                        <label for="jobCameraSelect">Caméra</label>
                        <select id="jobCameraSelect"></select>
                    </div>
                    <div class="video-settings">
                        <label for="jobMicSelect">Microphone</label>
                        <select id="jobMicSelect"></select>
                    </div>
                    <div class="video-container">
                        <video id="jobVideoPreview" autoplay playsinline muted></video>
                        <video id="jobVideoPlayback" class="hidden" playsinline controls></video>
                        <div class="video-timer" id="jobTimer">01:00</div>
                        <div class="video-progress" id="jobProgressBar" style="width:0%"></div>
                        <div id="jobTransferOverlay" class="transfer-overlay hidden">
                            <div class="spinner"></div>
                            <div>Transfert en cours...</div>
                            <div style="width:80%;height:8px;background:#374151;border-radius:4px;overflow:hidden;margin-top:1rem;">
                                <div id="jobUploadBar" style="height:100%;width:0%;background:#16a34a;transition:width 0.2s"></div>
                            </div>
                            <div id="jobUploadPercent" style="margin-top:0.5rem;font-size:0.9rem">0%</div>
                        </div>
                    </div>
                    <div class="video-controls">
                        <button type="button" id="jobBtnStart" class="btn btn-primary">🎬 Démarrer</button>
                        <button type="button" id="jobBtnStop" class="btn btn-danger hidden">⏹ Arrêter</button>
                        <div id="jobValidationBtns" class="hidden" style="display:flex;gap:0.5rem;flex:1">
                            <button type="button" id="jobBtnAccept" class="btn btn-success">✓ Accepter</button>
                            <button type="button" id="jobBtnDelete" class="btn btn-danger">🗑 Supprimer</button>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn">Créer le poste</button>
        </form>

        <div class="footer">
            <a href="https://www.ciaocv.com">Retour au site principal</a>
        </div>
    </div>

    <script>
        (function() {
            const MAX_DURATION = 60;
            let stream, mediaRecorder, recordedChunks = [], startTime, timerInterval;

            const preview = document.getElementById('jobVideoPreview');
            const playback = document.getElementById('jobVideoPlayback');
            const timer = document.getElementById('jobTimer');
            const progressBar = document.getElementById('jobProgressBar');
            const btnStart = document.getElementById('jobBtnStart');
            const btnStop = document.getElementById('jobBtnStop');
            const btnAccept = document.getElementById('jobBtnAccept');
            const btnDelete = document.getElementById('jobBtnDelete');
            const validationBtns = document.getElementById('jobValidationBtns');
            const transferOverlay = document.getElementById('jobTransferOverlay');
            const uploadBar = document.getElementById('jobUploadBar');
            const uploadPercent = document.getElementById('jobUploadPercent');
            const recordBlock = document.getElementById('videoRecordBlock');
            const readyBadge = document.getElementById('videoReadyBadge');
            const cameraSelect = document.getElementById('jobCameraSelect');
            const micSelect = document.getElementById('jobMicSelect');

            async function getDevices() {
                const devices = await navigator.mediaDevices.enumerateDevices();
                cameraSelect.innerHTML = '';
                micSelect.innerHTML = '';
                devices.filter(d => d.kind === 'videoinput').forEach((c, i) => {
                    const o = document.createElement('option');
                    o.value = c.deviceId;
                    o.text = c.label || 'Caméra ' + (i + 1);
                    cameraSelect.appendChild(o);
                });
                devices.filter(d => d.kind === 'audioinput').forEach((m, i) => {
                    const o = document.createElement('option');
                    o.value = m.deviceId;
                    o.text = m.label || 'Micro ' + (i + 1);
                    micSelect.appendChild(o);
                });
            }

            async function initCamera() {
                if (stream) stream.getTracks().forEach(t => t.stop());
                try {
                    stream = await navigator.mediaDevices.getUserMedia({
                        video: { deviceId: cameraSelect.value ? { exact: cameraSelect.value } : undefined },
                        audio: { deviceId: micSelect.value ? { exact: micSelect.value } : undefined }
                    });
                    preview.srcObject = stream;
                    preview.classList.remove('hidden');
                    playback.classList.add('hidden');
                    btnStart.disabled = false;
                    if (cameraSelect.options.length === 0) await getDevices();
                } catch (e) {
                    btnStart.disabled = true;
                }
            }

            cameraSelect.onchange = micSelect.onchange = initCamera;

            function startRecording() {
                recordedChunks = [];
                let mime = 'video/webm;codecs=vp9';
                if (!MediaRecorder.isTypeSupported(mime)) mime = 'video/webm';
                mediaRecorder = new MediaRecorder(stream, { mimeType: mime, videoBitsPerSecond: 1000000 });
                mediaRecorder.ondataavailable = e => { if (e.data.size) recordedChunks.push(e.data); };
                mediaRecorder.onstop = () => {
                    const blob = new Blob(recordedChunks, { type: mediaRecorder.mimeType });
                    playback.src = URL.createObjectURL(blob);
                    simulateUpload();
                };
                mediaRecorder.start(100);
                startTime = Date.now();
                timerInterval = setInterval(updateTimer, 100);
                setTimeout(() => { if (mediaRecorder?.state === 'recording') stopRecording(); }, MAX_DURATION * 1000);
                btnStart.classList.add('hidden');
                btnStop.classList.remove('hidden');
                timer.classList.add('recording');
            }

            function stopRecording() {
                if (mediaRecorder?.state === 'recording') {
                    mediaRecorder.stop();
                    clearInterval(timerInterval);
                }
                btnStart.classList.add('hidden');
                btnStop.classList.add('hidden');
            }

            function updateTimer() {
                const elapsed = (Date.now() - startTime) / 1000;
                const rem = Math.max(0, MAX_DURATION - elapsed);
                timer.textContent = String(Math.floor(rem / 60)).padStart(2, '0') + ':' + String(Math.floor(rem % 60)).padStart(2, '0');
                progressBar.style.width = (elapsed / MAX_DURATION) * 100 + '%';
            }

            function simulateUpload() {
                preview.classList.add('hidden');
                playback.classList.add('hidden');
                transferOverlay.classList.remove('hidden');
                uploadBar.style.width = '0%';
                uploadPercent.textContent = '0%';
                let p = 0;
                const iv = setInterval(() => {
                    p += Math.random() * 15 + 10;
                    if (p >= 100) {
                        p = 100;
                        clearInterval(iv);
                        uploadBar.style.width = '100%';
                        uploadPercent.textContent = '100%';
                        setTimeout(showPreview, 300);
                    } else {
                        uploadBar.style.width = p + '%';
                        uploadPercent.textContent = Math.round(p) + '%';
                    }
                }, 200);
            }

            function showPreview() {
                transferOverlay.classList.add('hidden');
                playback.classList.remove('hidden');
                validationBtns.classList.remove('hidden');
                validationBtns.style.display = 'flex';
                timer.classList.remove('recording');
            }

            btnAccept.addEventListener('click', () => {
                if (stream) stream.getTracks().forEach(t => t.stop());
                recordBlock.classList.add('hidden');
                readyBadge.classList.remove('hidden');
            });

            btnDelete.addEventListener('click', () => {
                playback.src = '';
                preview.classList.remove('hidden');
                playback.classList.add('hidden');
                validationBtns.classList.add('hidden');
                timer.textContent = '01:00';
                progressBar.style.width = '0%';
                btnStart.classList.remove('hidden');
                btnStop.classList.add('hidden');
                initCamera();
            });

            btnStart.addEventListener('click', startRecording);
            btnStop.addEventListener('click', stopRecording);

            getDevices().then(() => initCamera());
        })();
    </script>
</body>
</html>
