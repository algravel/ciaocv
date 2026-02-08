<div class="card mb-6">
    <div class="flex-between mb-5">
        <div>
            <h2 class="card-title mb-0">Modèles de courriels</h2>
            <p class="form-help">Gérez les messages envoyés aux candidats lors de vos processus de recrutement.</p>
        </div>
        <button class="btn btn-primary" onclick="openEmailTemplateEditor()" style="white-space: nowrap;">
            <i class="fa-solid fa-plus"></i> Nouveau modèle
        </button>
    </div>
    <div id="email-templates-list">
        <!-- Rendu par JS via renderEmailTemplates() -->
    </div>
</div>

<!-- Éditeur de modèle -->
<div class="card hidden" id="email-template-editor">
    <div class="flex-between mb-5">
        <h2 class="card-title mb-0" id="email-editor-title">Nouveau modèle</h2>
        <button class="btn-icon btn-back" onclick="closeEmailTemplateEditor()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form onsubmit="saveEmailTemplate(event)" class="form-vertical--tight">
        <?= csrf_field() ?>
        <div class="form-group">
            <label class="form-label fw-semibold">Titre du modèle</label>
            <input type="text" id="email-tpl-title" class="form-input" placeholder="Ex: Confirmation de réception" required>
        </div>
        <div class="form-group">
            <label class="form-label fw-semibold">Contenu du courriel</label>
            <div class="form-help mb-2">
                Variables disponibles :
                <code>{{nom_candidat}}</code>
                <code>{{titre_poste}}</code>
                <code>{{nom_entreprise}}</code>
            </div>
            <textarea id="email-tpl-content" class="form-input" rows="8" style="resize: vertical;" placeholder="Bonjour {{nom_candidat}},&#10;&#10;..." required></textarea>
        </div>
        <div class="form-actions">
            <button type="button" class="btn btn-secondary" onclick="closeEmailTemplateEditor()">Annuler</button>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Enregistrer</button>
        </div>
    </form>
</div>
