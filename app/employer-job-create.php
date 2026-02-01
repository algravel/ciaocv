<?php
session_start();
require_once __DIR__ . '/db.php';

$saved = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $questions = array_filter(array_map('trim', $_POST['questions'] ?? []));
    $latitude = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? (float)$_POST['latitude'] : null;
    $longitude = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;
    $location_name = trim($_POST['location_name'] ?? '') ?: null;

    if (strlen($title) < 2) {
        $error = 'Le titre du poste est requis (min. 2 caractères).';
    } elseif (count($questions) > 5) {
        $error = 'Maximum 5 questions d\'entrevue.';
    } elseif ($db) {
        try {
            // Vérifier/créer les colonnes manquantes
            $cols = $db->query("SHOW COLUMNS FROM jobs")->fetchAll(PDO::FETCH_COLUMN);
            
            if (!in_array('employer_id', $cols)) {
                $db->exec("ALTER TABLE jobs ADD COLUMN employer_id INT DEFAULT 1 AFTER id");
            }
            if (!in_array('status', $cols)) {
                $db->exec("ALTER TABLE jobs ADD COLUMN status ENUM('draft','active','closed') DEFAULT 'active' AFTER description");
            }
            if (!in_array('latitude', $cols)) {
                $db->exec("ALTER TABLE jobs ADD COLUMN latitude DECIMAL(10,8) DEFAULT NULL AFTER show_on_esplanade");
            }
            if (!in_array('longitude', $cols)) {
                $db->exec("ALTER TABLE jobs ADD COLUMN longitude DECIMAL(11,8) DEFAULT NULL AFTER latitude");
            }
            if (!in_array('location_name', $cols)) {
                $db->exec("ALTER TABLE jobs ADD COLUMN location_name VARCHAR(255) DEFAULT NULL AFTER longitude");
            }
            
            // Créer en brouillon pour que l'utilisateur puisse vérifier avant publication
            $stmt = $db->prepare('INSERT INTO jobs (employer_id, title, description, status, latitude, longitude, location_name) VALUES (1, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$title, $description, 'draft', $latitude, $longitude, $location_name]);
            $jobId = $db->lastInsertId();

            // Insérer les questions (ignorer les erreurs de clé unique)
            foreach ($questions as $i => $q) {
                if ($q !== '') {
                    try {
                        $stmtQ = $db->prepare('INSERT INTO job_questions (job_id, question_text, sort_order) VALUES (?, ?, ?)');
                        $stmtQ->execute([$jobId, $q, $i + 1]);
                    } catch (PDOException $qe) {
                        // Ignorer les erreurs de questions
                    }
                }
            }
            $saved = true;
            header('Location: employer-job-view.php?id=' . $jobId);
            exit;
        } catch (PDOException $e) {
            $error = 'Erreur DB: ' . $e->getMessage();
        }
    } else {
        $error = 'Base de données non connectée.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un poste - CiaoCV</title>
    <link rel="stylesheet" href="assets/css/design-system.css?v=<?= ASSET_VERSION ?>">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
</head>
<body>
    <div class="app-shell">
        <?php $sidebarActive = 'create'; include __DIR__ . '/includes/employer-sidebar.php'; ?>
        <main class="app-main">
        <?php include __DIR__ . '/includes/employer-header.php'; ?>
        <div class="app-main-content layout-app">
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
                <label>Lieu du poste (géolocalisation)</label>
                <p class="hint">Indiquez l'emplacement pour rapprocher candidats et employeurs. Cliquez sur la carte ou utilisez « Ma position ».</p>
                <input type="hidden" name="latitude" id="jobLatitude" value="<?= htmlspecialchars($_POST['latitude'] ?? '') ?>">
                <input type="hidden" name="longitude" id="jobLongitude" value="<?= htmlspecialchars($_POST['longitude'] ?? '') ?>">
                <input type="hidden" name="location_name" id="jobLocationName" value="<?= htmlspecialchars($_POST['location_name'] ?? '') ?>">
                <div style="display:flex;flex-wrap:wrap;gap:0.5rem;margin-bottom:0.5rem;">
                    <button type="button" id="jobGeoBtn" class="btn btn-secondary" style="font-size:0.9rem;">📍 Ma position</button>
                    <span id="jobLocationLabel" style="font-size:0.9rem;color:#6B7280;align-self:center;"></span>
                </div>
                <div id="jobMap" style="height:280px;border-radius:12px;border:1px solid #e5e7eb;overflow:hidden;"></div>
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

            <div class="form-group video-section-block">
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

        </div>
        </main>
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
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
    (function() {
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
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data && data.display_name) {
                        nameInput.value = data.display_name;
                        labelEl.textContent = data.display_name;
                    }
                })
                .catch(function() {});
        }

        map.on('click', function(e) {
            setMarker(e.latlng.lat, e.latlng.lng);
        });

        document.getElementById('jobGeoBtn').addEventListener('click', function() {
            if (!navigator.geolocation) {
                labelEl.textContent = 'Géolocalisation non supportée.';
                return;
            }
            labelEl.textContent = 'Localisation…';
            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    var lat = pos.coords.latitude;
                    var lng = pos.coords.longitude;
                    map.setView([lat, lng], 14);
                    setMarker(lat, lng);
                },
                function() {
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
