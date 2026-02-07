<?php
/** @var string $longId */
/** @var array $poste */
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

    <!-- Étape 0 : Intro + préparation -->
    <div id="rec-step0" class="rec-card">
        <h2><?= e($poste['title'] ?? '') ?></h2>
        <p class="rec-meta"><?= e($poste['department'] ?? '') ?> • <?= e($poste['location'] ?? '') ?></p>
        <p class="rec-desc">Avant de commencer, consultez notre guide pour réussir votre entrevue vidéo.</p>
        <a href="<?= SITE_URL ?>/guide-candidat.html" class="rec-btn rec-btn-secondary" style="margin-bottom:0.75rem;">
            <i class="fa-solid fa-book-open"></i> Préparez votre entrevue
        </a>
        <button type="button" id="rec-btn-continuer" class="rec-btn rec-btn-primary">Continuer vers le formulaire</button>
    </div>

    <!-- Étape 1 : Inscription -->
    <div id="rec-step1" class="rec-card rec-hidden">
        <p class="rec-meta">Remplissez vos coordonnées pour accéder à l'entrevue de présélection.</p>
        <form id="rec-form" class="rec-form">
            <?= csrf_field() ?>
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
</div>

<script>
(function() {
    const MAX_DURATION = <?= $recordDuration * 60 ?>;
    let mediaRecorder, recordedChunks = [], stream, timerInterval, startTime, recordedBlob;

    const step0 = document.getElementById('rec-step0');
    const step1 = document.getElementById('rec-step1');
    const step2 = document.getElementById('rec-step2');
    const form = document.getElementById('rec-form');
    const btnContinuer = document.getElementById('rec-btn-continuer');

    btnContinuer.addEventListener('click', function() {
        step0.classList.add('rec-hidden');
        step1.classList.remove('rec-hidden');
    });
    const videoPreview = document.getElementById('rec-preview');
    const videoPlayback = document.getElementById('rec-playback');
    const timer = document.getElementById('rec-timer');
    const btnStart = document.getElementById('rec-btn-start');
    const btnStop = document.getElementById('rec-btn-stop');
    const btnReprendre = document.getElementById('rec-btn-reprendre');
    const btnAccepter = document.getElementById('rec-btn-accepter');
    const status = document.getElementById('rec-status');

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        step1.classList.add('rec-hidden');
        step2.classList.remove('rec-hidden');
        initCamera();
    });

    async function initCamera() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
            videoPreview.srcObject = stream;
            status.textContent = 'Caméra prête. Cliquez sur Enregistrer pour commencer.';
            btnStart.disabled = false;
        } catch (err) {
            status.textContent = 'Erreur : Impossible d\'accéder à la caméra.';
            status.style.color = '#ef4444';
        }
    }

    function startRecording() {
        recordedChunks = [];
        let mimeType = 'video/webm;codecs=vp9';
        if (!MediaRecorder.isTypeSupported(mimeType)) mimeType = 'video/webm;codecs=vp8';
        if (!MediaRecorder.isTypeSupported(mimeType)) mimeType = 'video/webm';

        try {
            mediaRecorder = new MediaRecorder(stream, { mimeType, videoBitsPerSecond: 1000000 });
        } catch (e) {
            mediaRecorder = new MediaRecorder(stream);
        }

        mediaRecorder.ondataavailable = (e) => { if (e.data.size > 0) recordedChunks.push(e.data); };
        mediaRecorder.onstop = () => {
            recordedBlob = new Blob(recordedChunks, { type: mediaRecorder.mimeType });
            videoPlayback.src = URL.createObjectURL(recordedBlob);
            videoPreview.classList.add('rec-hidden');
            videoPlayback.classList.remove('rec-hidden');
            btnStop.classList.add('rec-hidden');
            btnReprendre.classList.remove('rec-hidden');
            btnAccepter.classList.remove('rec-hidden');
            timer.classList.remove('recording');
            status.textContent = 'Vérifiez votre vidéo. Accepter pour envoyer, Reprendre pour réenregistrer.';
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
        status.textContent = 'Enregistrement en cours...';
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

    function reprendre() {
        videoPlayback.classList.add('rec-hidden');
        videoPreview.classList.remove('rec-hidden');
        videoPlayback.src = '';
        btnReprendre.classList.add('rec-hidden');
        btnAccepter.classList.add('rec-hidden');
        btnStart.classList.remove('rec-hidden');
        timer.textContent = '<?= sprintf('%d:00', $recordDuration) ?>';
        status.textContent = 'Cliquez sur Enregistrer pour reprendre.';
        startRecording();
    }

    function accepter() {
        status.textContent = 'Envoi en cours...';
        btnAccepter.disabled = true;
        // TODO: upload vers B2 ou API
        setTimeout(() => {
            status.textContent = 'Merci ! Votre vidéo a bien été envoyée.';
            status.style.color = '#22c55e';
        }, 1500);
    }

    btnStart.addEventListener('click', startRecording);
    btnStop.addEventListener('click', stopRecording);
    btnReprendre.addEventListener('click', reprendre);
    btnAccepter.addEventListener('click', accepter);
})();
</script>
