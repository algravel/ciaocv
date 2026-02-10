<?php
/** @var string $longId */
/** @var array $poste */
/** @var string $csrfToken */
$questions = $poste['questions'] ?? [];
$recordDuration = (int)($poste['recordDuration'] ?? 3);
?>
<div class="rec-container">
    <div class="rec-header">
        <span class="rec-logo">ciao<span class="cv">cv</span></span>
        <div class="rec-poste-emplacement">
            <strong><?= e($poste['title'] ?? '') ?></strong>
            <span class="rec-emplacement"><?= e($poste['location'] ?? '') ?></span>
        </div>
    </div>

    <!-- Step indicator -->
    <div class="rec-steps-indicator">
        <div class="rec-step-dot active" id="dot-0"><span>1</span></div>
        <div class="rec-step-line"></div>
        <div class="rec-step-dot" id="dot-1"><span>2</span></div>
        <div class="rec-step-line"></div>
        <div class="rec-step-dot" id="dot-2"><span>3</span></div>
    </div>

    <!-- Étape 0 : Intro + préparation -->
    <div id="rec-step0" class="rec-card">
        <h2><?= e($poste['title'] ?? '') ?></h2>
        <p class="rec-meta"><?= e($poste['department'] ?? '') ?> • <?= e($poste['location'] ?? '') ?></p>
        <p class="rec-desc">Avant de commencer, consultez notre guide pour réussir votre entrevue vidéo.</p>
        <a href="<?= SITE_URL ?>/guide-candidat.html" class="rec-btn rec-btn-secondary" style="margin-bottom:0.75rem;">
            <i class="fa-solid fa-book-open"></i> Guide candidat
        </a>
        <button type="button" id="rec-btn-continuer" class="rec-btn rec-btn-primary">Continuer vers le formulaire</button>
    </div>

    <!-- Étape 1 : Inscription -->
    <div id="rec-step1" class="rec-card rec-hidden">
        <p class="rec-meta">Remplissez vos coordonnées pour accéder à l'entrevue de présélection.</p>
        <form id="rec-form" class="rec-form" novalidate>
            <div class="form-row">
                <label for="rec-nom">Nom</label>
                <input type="text" id="rec-nom" name="nom" required placeholder="Votre nom">
            </div>
            <div class="form-row">
                <label for="rec-prenom">Prénom</label>
                <input type="text" id="rec-prenom" name="prenom" required placeholder="Votre prénom">
            </div>
            <div class="form-row">
                <label for="rec-tel">Téléphone</label>
                <input type="tel" id="rec-tel" name="tel" placeholder="514 555-0123">
            </div>
            <div class="form-row">
                <label for="rec-email">Courriel</label>
                <input type="email" id="rec-email" name="email" required placeholder="votre@courriel.com">
            </div>
            <button type="submit" class="rec-btn rec-btn-primary">Continuer</button>
        </form>
    </div>

    <!-- Étape 2 : Présentation + Enregistrement -->
    <div id="rec-step2" class="rec-hidden">
        <div class="rec-card">
            <h2><?= e($poste['title']) ?></h2>
            <p class="rec-meta" style="margin-bottom:0;"><?= e($poste['department'] ?? '') ?> • <?= e($poste['location'] ?? '') ?></p>
        </div>

        <div class="rec-card">
            <h3>Questions de présélection</h3>
            <ul class="rec-questions-list">
                <?php foreach ($questions as $i => $q): ?>
                <li><span class="q-num"><?= $i + 1 ?></span><span><?= e($q) ?></span></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="rec-card">
            <h3>Enregistrez votre réponse</h3>
            <p class="rec-meta" style="margin-bottom:1rem;">Durée maximale : <?= $recordDuration ?> minute(s) pour l'ensemble des questions. Répondez de façon naturelle.</p>
            <div class="rec-video-wrap">
                <video id="rec-preview" autoplay playsinline muted></video>
                <video id="rec-playback" class="rec-hidden" playsinline controls></video>
                <div class="rec-timer" id="rec-timer"><?= sprintf('%d:00', $recordDuration) ?></div>
                <!-- Overlay de transfert -->
                <div id="rec-transfer-overlay" class="rec-transfer-overlay rec-hidden">
                    <div class="rec-spinner"></div>
                    <div class="rec-transfer-label">Envoi en cours…</div>
                    <div class="rec-progress-bar-wrap">
                        <div class="rec-progress-bar-fill" id="rec-progress-fill"></div>
                    </div>
                    <div class="rec-progress-percent" id="rec-progress-percent">0 %</div>
                </div>
            </div>
            <div class="rec-controls">
                <button type="button" id="rec-btn-start" class="rec-btn rec-btn-primary"><i class="fa-solid fa-circle"></i> Enregistrer</button>
                <button type="button" id="rec-btn-stop" class="rec-btn rec-btn-danger rec-hidden"><i class="fa-solid fa-stop"></i> Arrêter</button>
                <button type="button" id="rec-btn-reprendre" class="rec-btn rec-btn-secondary rec-hidden"><i class="fa-solid fa-rotate-right"></i> Reprendre</button>
                <button type="button" id="rec-btn-accepter" class="rec-btn rec-btn-success rec-hidden"><i class="fa-solid fa-check"></i> Accepter</button>
            </div>
            <div id="rec-status" class="rec-status">Autorisez l'accès à la caméra puis cliquez sur Enregistrer.</div>
        </div>
    </div>

    <!-- Étape 3 : Confirmation -->
    <div id="rec-step3" class="rec-hidden">
        <div class="rec-card rec-confirmation">
            <div class="rec-confirmation-icon"><i class="fa-solid fa-circle-check"></i></div>
            <h2>Merci !</h2>
            <p class="rec-desc">Votre candidature a bien été envoyée. L'entreprise examinera votre vidéo et vous contactera si votre profil est retenu.</p>
            <a href="<?= SITE_URL ?>" class="rec-btn rec-btn-secondary"><i class="fa-solid fa-arrow-left"></i> Retour au site</a>
        </div>
    </div>
</div>

<script>
(function() {
    const CSRF_TOKEN  = '<?= e($csrfToken) ?>';
    const LONG_ID     = '<?= e($longId) ?>';
    const MAX_DURATION = <?= $recordDuration * 60 ?>;

    let mediaRecorder, recordedChunks = [], stream, timerInterval, startTime, recordedBlob;

    // ─── DOM refs ───────────────────────────────────────────────────────
    const step0 = document.getElementById('rec-step0');
    const step1 = document.getElementById('rec-step1');
    const step2 = document.getElementById('rec-step2');
    const step3 = document.getElementById('rec-step3');
    const form  = document.getElementById('rec-form');
    const btnContinuer  = document.getElementById('rec-btn-continuer');
    const videoPreview  = document.getElementById('rec-preview');
    const videoPlayback = document.getElementById('rec-playback');
    const timer         = document.getElementById('rec-timer');
    const btnStart      = document.getElementById('rec-btn-start');
    const btnStop       = document.getElementById('rec-btn-stop');
    const btnReprendre  = document.getElementById('rec-btn-reprendre');
    const btnAccepter   = document.getElementById('rec-btn-accepter');
    const status        = document.getElementById('rec-status');
    const transferOverlay = document.getElementById('rec-transfer-overlay');
    const progressFill    = document.getElementById('rec-progress-fill');
    const progressPercent = document.getElementById('rec-progress-percent');
    const dots = [document.getElementById('dot-0'), document.getElementById('dot-1'), document.getElementById('dot-2')];

    // Données du formulaire conservées en JS
    let formData = {};

    // ─── Step navigation ────────────────────────────────────────────────
    function goToStep(n) {
        [step0, step1, step2, step3].forEach((s, i) => {
            s.classList.toggle('rec-hidden', i !== n);
        });
        dots.forEach((d, i) => {
            d.classList.toggle('active', i <= n - 1);
        });
    }

    // Step 0 → Step 1
    btnContinuer.addEventListener('click', () => goToStep(1));

    // Step 1 → Step 2 (conserver les données en JS, pas de sauvegarde DB)
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        // Validation basique
        const nom    = document.getElementById('rec-nom').value.trim();
        const prenom = document.getElementById('rec-prenom').value.trim();
        const email  = document.getElementById('rec-email').value.trim();
        const tel    = document.getElementById('rec-tel').value.trim();

        if (!nom || !prenom || !email) {
            status.textContent = 'Veuillez remplir tous les champs obligatoires.';
            status.style.color = '#EF4444';
            return;
        }

        // Stocker localement
        formData = { nom, prenom, email, telephone: tel };

        goToStep(2);
        initCamera();
    });

    // ─── Caméra ─────────────────────────────────────────────────────────
    async function initCamera() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { width: { ideal: 1280 }, height: { ideal: 720 }, facingMode: 'user' },
                audio: true
            });
            videoPreview.srcObject = stream;
            status.textContent = 'Caméra prête. Cliquez sur Enregistrer pour commencer.';
            status.style.color = '';
            btnStart.disabled = false;
        } catch (err) {
            status.textContent = 'Erreur : Impossible d\'accéder à la caméra. Vérifiez les permissions.';
            status.style.color = '#EF4444';
        }
    }

    // ─── Enregistrement ─────────────────────────────────────────────────
    function startRecording() {
        recordedChunks = [];

        // Détection du meilleur format — MP4 prioritaire pour compatibilité universelle
        let mimeType = '';
        const types = [
            'video/mp4;codecs=avc1',
            'video/mp4',
            'video/webm;codecs=h264',
            'video/webm;codecs=vp9',
            'video/webm;codecs=vp8',
            'video/webm'
        ];
        for (const t of types) {
            if (MediaRecorder.isTypeSupported(t)) { mimeType = t; break; }
        }

        try {
            mediaRecorder = new MediaRecorder(stream, {
                mimeType: mimeType || undefined,
                videoBitsPerSecond: 1_000_000 // 1 Mbps
            });
        } catch (e) {
            mediaRecorder = new MediaRecorder(stream);
        }

        mediaRecorder.ondataavailable = (e) => { if (e.data.size > 0) recordedChunks.push(e.data); };
        mediaRecorder.onstop = () => {
            recordedBlob = new Blob(recordedChunks, { type: mediaRecorder.mimeType || 'video/mp4' });
            videoPlayback.src = URL.createObjectURL(recordedBlob);
            videoPreview.classList.add('rec-hidden');
            videoPlayback.classList.remove('rec-hidden');
            btnStop.classList.add('rec-hidden');
            btnReprendre.classList.remove('rec-hidden');
            btnAccepter.classList.remove('rec-hidden');
            timer.classList.remove('recording');
            status.textContent = 'Vérifiez votre vidéo. Accepter pour envoyer, Reprendre pour réenregistrer.';
            status.style.color = '';
        };

        mediaRecorder.start(100);
        startTime = Date.now();
        timerInterval = setInterval(updateTimer, 100);
        setTimeout(() => {
            if (mediaRecorder && mediaRecorder.state === 'recording') stopRecording();
        }, MAX_DURATION * 1000);

        btnStart.classList.add('rec-hidden');
        btnStop.classList.remove('rec-hidden');
        timer.classList.add('recording');
        status.textContent = 'Enregistrement en cours…';
        status.style.color = '';
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
        const m = Math.floor(remaining / 60), s = Math.floor(remaining % 60);
        timer.textContent = m + ':' + String(s).padStart(2, '0');
    }

    // ─── Reprendre ──────────────────────────────────────────────────────
    function reprendre() {
        videoPlayback.classList.add('rec-hidden');
        videoPreview.classList.remove('rec-hidden');
        videoPlayback.src = '';
        btnReprendre.classList.add('rec-hidden');
        btnAccepter.classList.add('rec-hidden');
        btnStart.classList.remove('rec-hidden');
        timer.textContent = '<?= sprintf('%d:00', $recordDuration) ?>';
        status.textContent = 'Cliquez sur Enregistrer pour reprendre.';
        status.style.color = '';
        startRecording();
    }

    // ─── Accepter : upload B2 + save DB ─────────────────────────────────
    async function accepter() {
        if (!recordedBlob || recordedBlob.size === 0) {
            status.textContent = 'Aucune vidéo enregistrée.';
            status.style.color = '#EF4444';
            return;
        }

        btnAccepter.disabled = true;
        btnReprendre.classList.add('rec-hidden');
        transferOverlay.classList.remove('rec-hidden');

        try {
            // 1. Obtenir le presigned URL R2
            const urlFormData = new FormData();
            urlFormData.append('longId', LONG_ID);
            urlFormData.append('_csrf_token', CSRF_TOKEN);

            const urlRes = await fetch('/entrevue/upload-url', {
                method: 'POST',
                body: urlFormData
            });
            const urlData = await urlRes.json();
            if (urlData.error) throw new Error(urlData.error);

            // 2. Upload direct vers R2 (presigned PUT)
            await new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.open('PUT', urlData.uploadUrl, true);
                xhr.setRequestHeader('Content-Type', 'video/mp4');
                xhr.timeout = 300000; // 5 min pour les grosses vidéos

                xhr.upload.onprogress = (e) => {
                    if (e.lengthComputable) {
                        const pct = Math.round((e.loaded / e.total) * 100);
                        progressFill.style.width = pct + '%';
                        progressPercent.textContent = pct + ' %';
                    }
                };

                xhr.onload = () => {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        resolve();
                    } else {
                        reject(new Error('Échec upload (HTTP ' + xhr.status + ')'));
                    }
                };
                xhr.onerror = () => reject(new Error('Erreur réseau (vérifiez CORS ou connectivité)'));
                xhr.ontimeout = () => reject(new Error('Délai d\'envoi dépassé (5 min)'));
                xhr.send(recordedBlob);
            });

            // 3. Sauvegarder la candidature en BDD
            const submitRes = await fetch('/entrevue/submit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': CSRF_TOKEN
                },
                body: JSON.stringify({
                    longId:      LONG_ID,
                    nom:         formData.nom,
                    prenom:      formData.prenom,
                    email:       formData.email,
                    telephone:   formData.telephone,
                    videoPath:   urlData.fileName
                })
            });
            const submitData = await submitRes.json();
            if (submitData.error) throw new Error(submitData.error);

            // 5. Succès → écran de confirmation
            // Arrêter la caméra
            if (stream) stream.getTracks().forEach(t => t.stop());
            goToStep(3);

        } catch (err) {
            transferOverlay.classList.add('rec-hidden');
            btnAccepter.disabled = false;
            btnReprendre.classList.remove('rec-hidden');
            status.textContent = '❌ Erreur : ' + err.message;
            status.style.color = '#EF4444';
        }
    }

    // ─── Event listeners ────────────────────────────────────────────────
    btnStart.addEventListener('click', startRecording);
    btnStop.addEventListener('click', stopRecording);
    btnReprendre.addEventListener('click', reprendre);
    btnAccepter.addEventListener('click', accepter);
})();
</script>
