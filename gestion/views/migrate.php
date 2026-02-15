<div class="card">
    <h2 class="card-title"><i class="fa-solid fa-database"></i> Migration SQL</h2>
    <p class="subtitle-muted mb-4">Exécution des migrations de la base de données.</p>
    <p class="mb-4">Cliquez sur le bouton ci-dessous pour lancer les migrations. Aucune mise à jour n'est exécutée automatiquement au chargement de la page.</p>
    <button type="button" id="migrate-run-btn" class="btn btn-primary mb-4">
        <i class="fa-solid fa-play"></i> <span data-i18n="migrate_btn_run">Exécuter les migrations</span>
    </button>
    <pre id="migrate-output" class="migrate-output" style="background: #1e293b; color: #e2e8f0; padding: 1rem; border-radius: 8px; overflow-x: auto; font-size: 0.9rem; line-height: 1.5; min-height: 4rem;"><?= e($output ?? '') ?></pre>
    <a href="<?= GESTION_BASE_PATH ?>/dashboard" class="btn btn-primary mt-4">Retour au tableau de bord</a>
</div>
<script>
(function () {
    var btn = document.getElementById('migrate-run-btn');
    var pre = document.getElementById('migrate-output');
    if (!btn || !pre) return;
    btn.addEventListener('click', function () {
        btn.disabled = true;
        var label = btn.querySelector('span');
        var originalText = label ? label.textContent : '';
        if (label) label.textContent = 'Exécution...';
        pre.textContent = 'Exécution en cours...';
        var formData = new FormData();
        var tokenEl = document.querySelector('input[name="_csrf_token"]');
        if (tokenEl) formData.append('_csrf_token', tokenEl.value);
        var basePath = (typeof APP_DATA !== 'undefined' && APP_DATA.basePath) ? APP_DATA.basePath : '';
        fetch(basePath + '/migrate/run', { method: 'POST', body: formData })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.ok && data.output !== undefined) {
                    pre.textContent = data.output || '(Aucune sortie)';
                } else {
                    pre.textContent = 'Erreur: ' + (data.error || 'Inconnue');
                }
            })
            .catch(function (err) {
                pre.textContent = 'Erreur réseau: ' + (err.message || 'Échec de la requête');
            })
            .finally(function () {
                btn.disabled = false;
                if (label && originalText) label.textContent = originalText;
            });
    });
})();
</script>
