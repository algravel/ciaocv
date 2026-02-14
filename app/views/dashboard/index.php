<!-- ═══════════════════════════════════════════════════════════════════════
     VUE : Tableau de bord employeur
     Toutes les sections (SPA côté client, affichées/masquées via JS).
     Les données viennent de APP_DATA (injecté par le layout).
     ═══════════════════════════════════════════════════════════════════════ -->
<?php $def = $defaultSection ?? 'statistiques'; ?>

<!-- ─── CSRF Token for AJAX ─── -->
<?= csrf_field() ?>

<!-- ─── POSTES Section ─── -->
<div id="postes-section" class="content-section<?= $def === 'postes' ? ' active' : '' ?>">
    <div class="page-header">
        <h1 class="page-title" data-i18n="postes_title">Postes actifs</h1>
    </div>
    <div class="filters-bar">
        <div class="view-tabs" id="postes-filter-tabs">
            <button class="view-tab active" data-filter="all" data-i18n="filter_all">Tous</button>
            <button class="view-tab" data-filter="active" data-i18n="filter_active">Actifs</button>
            <button class="view-tab" data-filter="paused" data-i18n="filter_inactive">Non actifs</button>
            <button class="view-tab" data-filter="closed" data-i18n="filter_archived">Archivés</button>
        </div>
        <button class="btn btn-primary" onclick="openModal('poste')">
            <i class="fa-solid fa-plus"></i>
            <span data-i18n="add_poste">Nouveau poste</span>
        </button>
    </div>
    <div class="search-row">
        <div class="search-bar search-bar--full">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" placeholder="Rechercher..." data-i18n-placeholder="search">
        </div>
    </div>
    <table class="data-table" id="postes-table">
        <thead>
            <tr>
                <th data-i18n="th_title">Titre</th>
                <th data-i18n="th_department">Département</th>
                <th data-i18n="th_location">Lieu</th>
                <th data-i18n="th_status">Statut</th>
                <th class="th-candidates" data-i18n="th_candidates">Candidats</th>
                <th class="th-actions" data-i18n="th_actions">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($postes as $p):
                $rawStatus = strtolower(str_replace(['é', 'è', 'ê'], 'e', $p['status'] ?? ''));
                $filterStatus = 'active';
                if (in_array($rawStatus, ['non actif', 'inactif', 'pause', 'paused']))
                    $filterStatus = 'paused';
                elseif (in_array($rawStatus, ['archive', 'archivé', 'closed', 'ferme', 'fermé']))
                    $filterStatus = 'closed';
                ?>
                <tr class="row-clickable" data-poste-id="<?= e($p['id']) ?>" data-status="<?= $filterStatus ?>"
                    role="button" tabindex="0" onclick="showPosteDetail('<?= e($p['id']) ?>')">
                    <td><strong><?= e($p['title']) ?></strong></td>
                    <td><?= e($p['department']) ?></td>
                    <td><?= e($p['location']) ?></td>
                    <td><span class="status-badge <?= e($p['statusClass']) ?>"><?= e($p['status']) ?></span></td>
                    <td class="cell-candidates"><?= (int) ($p['candidates'] ?? 0) ?></td>
                    <td class="cell-actions">
                        <button type="button" class="btn-icon btn-icon-edit"
                            onclick="event.stopPropagation(); showPosteDetail('<?= e($p['id']) ?>')" title="Modifier"
                            data-i18n-title="action_edit"><i class="fa-solid fa-pen"></i></button>
                        <button type="button" class="btn-icon btn-icon-delete"
                            onclick="event.stopPropagation(); deletePoste('<?= e($p['id']) ?>', this.closest('tr'))"
                            title="Supprimer" data-i18n-title="action_delete"><i class="fa-solid fa-trash"></i></button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- ─── POSTE DÉTAIL Section ─── -->
<div id="poste-detail-section" class="content-section section--detail">
    <div class="page-header--detail">
        <button class="btn-icon btn-back" onclick="goBackToPostes()">
            <i class="fa-solid fa-arrow-left"></i>
        </button>
        <div>
            <h1 class="page-title" id="detail-poste-title">Titre du Poste</h1>
            <div class="subtitle-muted" id="detail-poste-dept-loc">Département • Lieu</div>
        </div>
        <select class="status-select ml-auto" id="detail-poste-status-select" onchange="updatePosteStatus(this.value)">
            <option value="actif">Actif</option>
            <option value="inactif">Non actif</option>
            <option value="archive">Archivé</option>
        </select>
    </div>
    <!-- Informations du poste -->
    <div class="card">
        <h3 class="card-subtitle">Informations</h3>
        <div class="info-row">
            <div class="info-label">Candidats</div>
            <div class="info-value" id="detail-poste-candidates">0</div>
        </div>
        <div class="action-stack">
            <button class="btn btn-primary btn--center" onclick="openPosteCandidatsModal()">
                <i class="fa-solid fa-users"></i> Voir les candidats
            </button>
        </div>
        <div id="detail-poste-share-url-wrap" class="mt-4 pt-4" style="border-top: 1px solid var(--border-color);">
            <label class="form-label mb-1" data-i18n="poste_share_url_label">Lien pour les candidats</label>
            <div id="detail-poste-share-url-content" class="search-row-url-wrap flex-center gap-2" style="flex-wrap: wrap;">
                <span class="subtitle-muted" data-i18n="poste_share_url_empty">Créez un affichage pour ce poste pour obtenir le lien.</span>
            </div>
        </div>
    </div>

    <!-- Questions CRUD -->
    <div class="card">
        <div class="flex-between mb-4">
            <h3 class="section-heading mb-0">Questions de présélection</h3>
            <span class="subtitle-muted" id="detail-poste-questions-count">0 questions</span>
        </div>
        <div id="detail-poste-questions-list" class="questions-list"></div>
        <div id="detail-poste-questions-proposed" class="questions-proposed mt-4">
            <label class="form-label" data-i18n="questions_proposed">Questions proposées</label>
            <div class="question-chips" id="detail-poste-question-chips">
                <button type="button" class="question-chip" data-question="Parlez-nous de votre parcours et de vos motivations."><i class="fa-solid fa-plus"></i> Parlez-nous de votre parcours et de vos motivations.</button>
                <button type="button" class="question-chip" data-question="Décrivez un projet complexe que vous avez géré."><i class="fa-solid fa-plus"></i> Décrivez un projet complexe que vous avez géré.</button>
                <button type="button" class="question-chip" data-question="Quelles sont vos compétences clés pour ce poste ?"><i class="fa-solid fa-plus"></i> Quelles sont vos compétences clés pour ce poste ?</button>
                <button type="button" class="question-chip" data-question="Pourquoi souhaitez-vous nous rejoindre ?"><i class="fa-solid fa-plus"></i> Pourquoi souhaitez-vous nous rejoindre ?</button>
                <button type="button" class="question-chip" data-question="Quel est votre style de leadership ?"><i class="fa-solid fa-plus"></i> Quel est votre style de leadership ?</button>
                <button type="button" class="question-chip" data-question="Racontez-nous un défi que vous avez surmonté."><i class="fa-solid fa-plus"></i> Racontez-nous un défi que vous avez surmonté.</button>
                <button type="button" class="question-chip" data-question="Que recherchez-vous comme opportunité ?"><i class="fa-solid fa-plus"></i> Que recherchez-vous comme opportunité ?</button>
                <button type="button" class="question-chip" data-question="Pourquoi vous et pas un autre ?"><i class="fa-solid fa-plus"></i> Pourquoi vous et pas un autre ?</button>
            </div>
        </div>
        <div class="questions-add-row mt-4">
            <input type="text" id="detail-poste-new-question" class="form-input" placeholder="Ajouter une question..."
                onkeydown="if(event.key==='Enter'){addPosteQuestion(); event.preventDefault();}">
            <button class="btn btn-primary" onclick="addPosteQuestion()"><i class="fa-solid fa-plus"></i></button>
        </div>
        <div class="mt-4 flex-between flex-wrap gap-2">
            <div class="flex align-center gap-2">
                <button type="button" class="btn btn-primary" id="btn-save-questions">
                    <i class="fa-solid fa-check"></i> <span data-i18n="btn_save_questions">Sauvegarder les questions</span>
                </button>
                <span id="questions-save-status" class="subtitle-muted" style="display:none;" aria-live="polite"></span>
            </div>
        </div>
        <!-- Durée d'enregistrement -->
        <div class="flex-between mt-6" style="border-top: 1px solid var(--border-color); padding-top: 1rem;">
            <div>
                <label class="form-label mb-0 fw-semibold"><i class="fa-solid fa-video"></i> Durée
                    d'enregistrement</label>
                <div class="form-help" data-i18n="record_duration_help">Temps maximum pour l'ensemble des questions pour le candidat.</div>
            </div>
            <select class="form-select form-select--auto" id="detail-poste-record-duration"
                onchange="updatePosteRecordDuration(this.value)">
                <option value="1">1 minute</option>
                <option value="2">2 minutes</option>
                <option value="3" selected>3 minutes</option>
                <option value="4">4 minutes</option>
                <option value="5">5 minutes</option>
            </select>
        </div>
    </div>
</div>

<!-- Modal Candidats du poste -->
<div class="modal-overlay" id="poste-candidats-modal">
    <div class="modal modal--narrow">
        <div class="modal-header">
            <h2 class="modal-title" id="poste-candidats-modal-title">Candidats</h2>
            <button class="btn-icon" onclick="closeModal('poste-candidats')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div id="poste-candidats-modal-list" class="poste-candidats-list"></div>
        <div id="poste-candidats-modal-empty" class="text-center hidden" style="padding: 2rem 0;">
            <i class="fa-regular fa-user icon-xl" style="opacity: 0.3;"></i>
            <p class="subtitle-muted mt-2">Aucun candidat pour ce poste.</p>
        </div>
    </div>
</div>

<!-- ─── AFFICHAGE DÉTAIL Section ─── -->
<div id="affichage-detail-section" class="content-section section--detail">
    <div class="page-header--detail">
        <button class="btn-icon btn-back" onclick="goBackToAffichages()"><i class="fa-solid fa-arrow-left"></i></button>
        <div>
            <h1 class="page-title" id="detail-affichage-title">Titre de l'Affichage</h1>
            <div class="subtitle-muted" id="detail-affichage-platform">Plateforme</div>
        </div>
        <span class="status-badge ml-auto" id="detail-affichage-status">Statut</span>
    </div>
    <div class="grid-detail">
        <div class="card preview-placeholder">
            <div class="text-center text-body">
                <i class="fa-regular fa-image icon-xl"></i>
                <p>Aperçu de la publicité</p>
            </div>
        </div>
        <div>
            <div class="card">
                <h3 class="card-subtitle">Performances</h3>
                <div class="grid-2col mb-6">
                    <div>
                        <div class="info-label">Vues</div>
                        <div class="info-value" id="detail-affichage-views">0</div>
                    </div>
                    <div>
                        <div class="info-label">Candidatures</div>
                        <div class="info-value" id="detail-affichage-apps">0</div>
                    </div>
                </div>
                <h3 class="card-subtitle mt-6">Détails</h3>
                <div class="detail-row"><span class="info-label">Début</span><span class="info-value-sm"
                        id="detail-affichage-start">—</span></div>
                <div class="detail-row"><span class="info-label">Fin</span><span class="info-value-sm"
                        id="detail-affichage-end">—</span></div>
                <div class="mt-8">
                    <button class="btn btn-primary btn--full">Modifier l'affichage</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ─── AFFICHAGE CANDIDATS Section ─── -->
<div id="affichage-candidats-section" class="content-section section--detail">
    <div class="page-header--detail">
        <button class="btn-icon btn-back" onclick="goBackToAffichages()"><i class="fa-solid fa-arrow-left"></i></button>
        <div>
            <h1 class="page-title" id="affichage-candidats-title">Candidats</h1>
            <div class="subtitle-muted" id="affichage-candidats-subtitle">Plateforme</div>
        </div>
        <?php if (empty($isEvaluateur)): ?>
        <div class="action-group">
            <select class="status-select" id="affichage-status-select" onchange="updateAffichageStatus(this.value)">
                <option value="actif" data-i18n="status_active">Actif</option>
                <option value="termine" data-i18n="status_termine">Terminé</option>
                <option value="archive" data-i18n="status_archived">Archivé</option>
            </select>
            <button class="btn-icon" title="Notifier les candidats" data-i18n-title="notify_candidats_title" onclick="openNotifyCandidatsModal()"><i
                    class="fa-solid fa-envelope"></i></button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Alerte Terminé (15 jours) -->
    <div class="alert-warning mb-4 hidden" id="affichage-termine-alert">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span>Cet affichage est terminé. Les fichiers associés seront supprimés dans <strong>15 jours</strong> si le
            statut ne change pas.</span>
    </div>

    <div class="filters-bar">
        <div class="view-tabs" id="affichage-candidats-filter-tabs">
            <button class="view-tab active" data-filter="all" data-i18n="filter_all">Tous</button>
            <button class="view-tab" data-filter="new" data-i18n="filter_new">Nouveaux</button>
            <button class="view-tab" data-filter="reviewed" data-i18n="filter_reviewed">Évalués</button>
            <button class="view-tab" data-filter="rejected" data-i18n="filter_rejected">Refusés</button>
        </div>
    </div>
    <?php if (empty($isEvaluateur)): ?>
    <div class="search-row search-row--with-label">
        <span class="search-row-label" data-i18n="share_link_label">Lien à partager</span>
        <span class="search-row-url-wrap">
            <?php
            $firstAff = $affichages ? reset($affichages) : null;
            $shareLongId = $firstAff['shareLongId'] ?? '0cb075d860fa55c4';
            ?>
            <a class="search-row-url" id="affichage-share-url" href="<?= APP_URL ?>/entrevue/<?= e($shareLongId) ?>"
                target="_blank" rel="noopener"><?= APP_URL ?>/entrevue/<?= e($shareLongId) ?></a>
            <button type="button" class="btn-icon btn-icon--copy" title="Copier le lien" onclick="copyShareUrl()"><i
                    class="fa-regular fa-copy"></i></button>
        </span>
    </div>
    <?php endif; ?>
    <div class="affichage-candidats-table-wrap">
        <table class="data-table" id="affichage-candidats-table">
            <thead>
                <tr>
                    <th>Candidat</th>
                    <th>Statut</th>
                    <th data-i18n="th_rating">Note</th>
                    <th>Favori</th>
                    <th>Postulé le</th>
                    <th>Communication</th>
                </tr>
            </thead>
            <tbody id="affichage-candidats-tbody"></tbody>
        </table>
        <div class="affichage-candidats-empty-msg hidden" id="affichage-candidats-empty-msg" aria-live="polite"></div>
    </div>

    <!-- Évaluateurs CRUD (caché pour les évaluateurs) -->
    <div class="card mt-6" id="affichage-evaluateurs-card">
        <div class="flex-between mb-4">
            <h3 class="section-heading mb-0"><i class="fa-solid fa-user-check"></i> <span data-i18n="settings_team">Évaluateurs</span></h3>
            <span class="subtitle-muted" id="affichage-evaluateurs-count" data-i18n="evaluators_count_zero">0 évaluateurs</span>
        </div>
        <div id="affichage-evaluateurs-list" class="evaluateurs-list"></div>
        <?php if (empty($isEvaluateur)): ?>
        <div class="evaluateurs-add-row mt-4" id="evaluateurs-add-row">
            <input type="text" id="eval-new-prenom" class="form-input" placeholder="Prénom" data-i18n-placeholder="evaluator_placeholder_prenom">
            <input type="text" id="eval-new-nom" class="form-input" placeholder="Nom" data-i18n-placeholder="evaluator_placeholder_nom">
            <input type="email" id="eval-new-email" class="form-input" placeholder="Courriel" data-i18n-placeholder="evaluator_placeholder_email"
                onkeydown="if(event.key==='Enter'){addEvaluateur(); event.preventDefault();}">
            <button type="button" class="btn btn-primary" onclick="addEvaluateur()" style="flex-shrink:0;min-width:44px;min-height:44px;" title="Ajouter un évaluateur" data-i18n-title="add_evaluateur_title"><i class="fa-solid fa-plus"></i></button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ─── AFFICHAGES Section ─── -->
<div id="affichages-section" class="content-section<?= $def === 'affichages' ? ' active' : '' ?>">
    <div class="page-header">
        <h1 class="page-title" data-i18n="affichages_title">Affichages en cours</h1>
    </div>
    <div class="filters-bar">
        <div class="view-tabs" id="affichages-filter-tabs">
            <button class="view-tab active" data-filter="all" data-i18n="filter_all">Tous</button>
            <button class="view-tab" data-filter="active" data-i18n="filter_active">Actifs</button>
            <button class="view-tab" data-filter="expired" data-i18n="filter_expired">Expirés</button>
        </div>
        <?php if (empty($isEvaluateur)): ?>
        <button class="btn btn-primary" onclick="openModal('affichage')"><i class="fa-solid fa-plus"></i><span
                data-i18n="add_affichage">Nouvel affichage</span></button>
        <?php endif; ?>
    </div>
    <div class="search-row">
        <div class="search-bar search-bar--full"><i class="fa-solid fa-magnifying-glass"></i><input type="text"
                placeholder="Rechercher..." data-i18n-placeholder="search"></div>
    </div>
    <table class="data-table" id="affichages-table">
        <thead>
            <tr>
                <th data-i18n="th_poste">Poste</th>
                <th data-i18n="th_department">Département</th>
                <th data-i18n="th_status">Statut</th>
                <th data-i18n="th_new_candidates">Non évalué</th>
                <th class="th-actions" data-i18n="th_actions">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($affichages as $aId => $a):
                $affRawStatus = 'active';
                if (($a['statusClass'] ?? '') === 'status-expired')
                    $affRawStatus = 'expired';
                elseif (($a['statusClass'] ?? '') === 'status-paused')
                    $affRawStatus = 'paused';
                elseif (($a['statusClass'] ?? '') === 'status-closed')
                    $affRawStatus = 'closed';
                $statusI18n = 'status_active';
                if (($a['statusClass'] ?? '') === 'status-closed') $statusI18n = 'status_archived';
                elseif (($a['statusClass'] ?? '') === 'status-paused' || ($a['statusClass'] ?? '') === 'status-expired') $statusI18n = 'status_termine';

                $cands = $candidatsByAff[$aId] ?? [];
                $cnt = count($cands);
                $newCnt = 0;
                foreach ($cands as $c) {
                    $st = strtolower($c['status'] ?? '');
                    if ($st === 'new') $newCnt++;
                }
                ?>
                <tr data-affichage-id="<?= e($aId) ?>" data-status="<?= $affRawStatus ?>"
                    onclick="showAffichageDetail('<?= e($aId) ?>')" class="row-clickable">
                    <td><strong><?= e($a['title']) ?></strong></td>
                    <td><?= e($a['department'] ?? '') ?></td>
                    <td><span class="status-badge <?= e($a['statusClass']) ?>" data-i18n="<?= e($statusI18n) ?>"><?= e($a['status']) ?></span></td>
                    <td><span class="badge-count"><?= $newCnt ?>/<?= $cnt ?></span></td>
                    <td class="cell-actions">
                        <button type="button" class="btn-icon btn-icon-edit"
                            onclick="event.stopPropagation(); showAffichageDetail('<?= e($aId) ?>')" title="Modifier"
                            data-i18n-title="action_edit">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <?php if (empty($isEvaluateur)): ?>
                        <button type="button" class="btn-icon btn-icon-delete"
                            onclick="event.stopPropagation(); deleteAffichage('<?= e($aId) ?>', this.closest('tr'))"
                            title="Supprimer" data-i18n-title="action_delete">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- ─── CANDIDATS Section ─── -->
<div id="candidats-section" class="content-section<?= $def === 'candidats' ? ' active' : '' ?>">
    <div class="page-header">
        <h1 class="page-title" data-i18n="candidats_title">Candidats</h1>
    </div>
    <div class="filters-bar">
        <div class="view-tabs" id="candidats-filter-tabs">
            <button class="view-tab active" data-filter="all" data-i18n="filter_all">Tous</button>
            <button class="view-tab" data-filter="new" data-i18n="filter_new">Nouveaux</button>
            <button class="view-tab" data-filter="reviewed" data-i18n="filter_reviewed">Évalués</button>
            <button class="view-tab" data-filter="rejected" data-i18n="filter_rejected">Refusés</button>
        </div>
        <div><select class="form-select form-select--auto">
                <option data-i18n="filter_all_jobs">Tous les postes</option>
                <option>Développeur Frontend</option>
                <option>Chef de projet</option>
                <option>Designer UX/UI</option>
            </select></div>
    </div>
    <div class="search-row">
        <div class="search-bar search-bar--full"><i class="fa-solid fa-magnifying-glass"></i><input type="text"
                placeholder="Rechercher..." data-i18n-placeholder="search"></div>
    </div>
    <table class="data-table" id="candidats-table">
        <thead>
            <tr>
                <th data-i18n="th_candidate">Candidat</th>
                <th data-i18n="th_poste">Poste</th>
                <th data-i18n="th_status">Statut</th>
                <th data-i18n="th_rating">Note</th>
                <th data-i18n="th_applied">Postulé le</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($candidats as $cId => $c): ?>
                <tr onclick="showCandidateDetail('<?= e($cId) ?>')" class="row-clickable"
                    data-status="<?= e($c['status']) ?>">
                    <td>
                        <div class="flex-center gap-3">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($c['name']) ?>&background=<?= e($c['color']) ?>&color=fff"
                                class="avatar" alt="">
                            <div><strong><?= e($c['name']) ?></strong>
                                <div class="subtitle-muted"><?= e($c['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= e($c['role']) ?></td>
                    <td><?php
                    $statusMap = [
                        'new' => ['label' => 'Nouveau', 'class' => 'status-new'],
                        'reviewed' => ['label' => 'Évalué', 'class' => 'status-active'],
                        'rejected' => ['label' => 'Refusé', 'class' => 'status-rejected'],
                        'shortlisted' => ['label' => 'Banque', 'class' => 'status-shortlisted'],
                    ];
                    $st = $c['status'];
                    $badge = $statusMap[$st] ?? ['label' => $st, 'class' => ''];
                    ?><span class="status-badge <?= $badge['class'] ?>"><?= e($badge['label']) ?></span></td>
                    <td>
                        <div class="star-color"><?php for ($i = 1; $i <= 5; $i++): ?><i
                                    class="fa-<?= $i <= $c['rating'] ? 'solid' : 'regular' ?> fa-star"></i><?php endfor; ?>
                        </div>
                    </td>
                    <td>2026-02-01</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- ─── CANDIDAT DÉTAIL Section ─── -->
<div id="candidate-detail-section" class="content-section section--detail">
    <div class="page-header--detail page-header--candidate">
        <button class="btn-icon btn-back" onclick="goBackToCandidates()"><i class="fa-solid fa-arrow-left"></i></button>
        <div class="page-header--candidate-info">
            <h1 class="page-title" id="detail-candidate-name">Nom du Candidat</h1>
            <div class="subtitle-muted" id="detail-candidate-role-source">Poste</div>
        </div>
        <div class="page-header--candidate-actions">
            <!-- Appréciation du candidat (sur la personne, pas l'enregistrement) -->
            <div class="candidate-rating-block">
                <span class="text-muted text-sm" data-i18n="rating_label">Appréciation</span>
                <div class="star-rating star-rating--header" id="detail-candidate-rating-stars">
                    <i class="fa-regular fa-star"></i><i class="fa-regular fa-star"></i><i class="fa-regular fa-star"></i><i class="fa-regular fa-star"></i><i class="fa-regular fa-star"></i>
                </div>
            </div>
            <button class="favorite-btn" id="detail-candidate-favorite" onclick="toggleFavorite()"
                title="Mettre en favori"><i class="fa-regular fa-heart"></i></button>
            <select class="status-select status-select--candidate" id="detail-candidate-status-select"
                onchange="updateCandidateStatus(this.value)">
                <option value="shortlisted" data-i18n="status_banque">Banque</option>
                <option value="reviewed" data-i18n="status_accepted">Accepté</option>
                <option value="rejected" data-i18n="status_rejected">Refusé</option>
            </select>
        </div>
    </div>

    <div class="candidate-detail-grid">
        <!-- Colonne gauche : Vidéo + Commentaires -->
        <div class="candidate-detail-main">
            <div class="candidate-video-wrapper">
                <video controls class="candidate-video-player hidden" id="detail-candidate-video-player">
                    <source src="" type="video/mp4">
                </video>
                <div id="detail-video-placeholder" class="candidate-video-placeholder">
                    <i class="fa-solid fa-play-circle icon-xl"></i>
                    <div class="mt-2" data-i18n="video_preview">Aperçu vidéo</div>
                </div>
            </div>
            <!-- Playback Speed Controls -->
            <div class="playback-speed-controls hidden" id="video-speed-controls">
                <button type="button" class="speed-btn active" onclick="setPlaybackSpeed(1, this)">1x</button>
                <button type="button" class="speed-btn" onclick="setPlaybackSpeed(1.5, this)">1.5x</button>
                <button type="button" class="speed-btn" onclick="setPlaybackSpeed(2, this)">2x</button>
            </div>

            <div class="card candidate-comments-card">
                <div class="comments-header">
                    <h3 class="section-heading-sm mb-0" data-i18n="comments_title">Commentaires</h3>
                    <span class="comments-subtitle" data-i18n="comments_subtitle">Échangez avec votre équipe sur ce candidat</span>
                </div>
                <div class="comments-list" id="detail-timeline-list"></div>
                <div class="comment-add">
                    <div class="comment-add-avatar" id="comment-current-user-avatar" title="Vous">?</div>
                    <div class="comment-add-form">
                        <textarea class="form-input" id="detail-new-comment-input" rows="2"
                            placeholder="Écrire un commentaire..." data-i18n-placeholder="add_note_placeholder"></textarea>
                        <button type="button" class="btn btn-primary btn-sm" id="comment-send-btn" data-action="add-comment">
                            <i class="fa-solid fa-paper-plane"></i> <span data-i18n="comment_send">Envoyer</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne droite : Coordonnées + Détails de l'enregistrement -->
        <aside class="candidate-detail-sidebar">
            <div class="card contact-card">
                <h3 class="contact-heading"><i class="fa-regular fa-envelope"></i> <span data-i18n="contact_email">Email</span></h3>
                <p id="detail-candidate-email" class="contact-value">—</p>
                <h3 class="contact-heading"><i class="fa-solid fa-phone"></i> <span data-i18n="form_phone">Téléphone</span></h3>
                <p id="detail-candidate-phone" class="contact-value">—</p>
                <h3 class="contact-heading mt-2"><i class="fa-solid fa-file-lines"></i> <span data-i18n="cv_label">CV</span></h3>
                <div id="detail-candidate-cv-wrap" class="mt-1">
                    <a id="detail-candidate-cv-link" href="#" target="_blank" rel="noopener" class="btn btn-secondary btn-sm hidden">
                        <i class="fa-solid fa-file-pdf"></i> <span data-i18n="action_download_cv">Télécharger CV</span>
                    </a>
                    <span id="detail-candidate-cv-missing" class="text-muted text-sm">
                        <span data-i18n="cv_missing">CV manquant</span>
                    </span>
                </div>
            </div>

            <!-- Détails de l'enregistrement uniquement (date, reprises, temps) -->
            <div class="card">
                <h3 class="section-heading-sm" data-i18n="recording_details_title">Détails de l'enregistrement</h3>
                <div class="recording-detail-row">
                    <label class="text-muted text-sm" data-i18n="date_label">Date</label>
                    <div id="detail-candidate-date" class="text-body font-weight-bold">—</div>
                </div>
                <div class="recording-metrics">
                    <div class="recording-metric">
                        <label class="text-muted text-sm" data-i18n="retakes_label">Reprises</label>
                        <div class="recording-metric-value">
                            <i class="fa-solid fa-rotate-right text-muted-light"></i>
                            <span id="detail-candidate-retakes">0</span>
                        </div>
                    </div>
                    <div class="recording-metric">
                        <label class="text-muted text-sm" data-i18n="duration_label">Temps passé</label>
                        <div class="recording-metric-value">
                            <i class="fa-regular fa-clock text-muted-light"></i>
                            <span id="detail-candidate-time-spent">0m 00s</span>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- ─── STATISTIQUES / TABLEAU DE BORD Section ─── -->
<div id="statistiques-section" class="content-section<?= $def === 'statistiques' ? ' active' : '' ?>">
    <div class="page-header">
        <h1 class="page-title" data-i18n="statistiques_title">Tableau de bord</h1>
    </div>

    <!-- Forfait Banner -->
    <div class="forfait-banner">
        <div class="flex-center gap-5">
            <div class="forfait-icon"><i class="fa-solid fa-gem icon-lg"></i></div>
            <div>
                <div class="plan-name"><?= e($planName ?? 'Découverte') ?></div>
            </div>
        </div>
        <a href="/parametres#parametres-billing" class="forfait-cta" data-i18n="forfait_manage">Gérer mon forfait</a>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="flex-between-start">
                <div>
                    <div class="kpi-label" data-i18n="kpi_my_plan">Mon forfait</div>
                    <div class="kpi-value"><?= (int) ($kpiForfaitUsed ?? 0) ?> <span class="kpi-suffix">/
                            <?= (int) ($kpiForfaitLimit ?? 50) ?></span></div>
                    <div class="kpi-sub">entrevues reçues</div>
                </div>
                <div class="kpi-icon kpi-icon--blue"><i class="fa-solid fa-users"></i></div>
            </div>
            <?php $kpiPct = ($kpiForfaitLimit ?? 50) > 0 ? min(100, (int) ((($kpiForfaitUsed ?? 0) / ($kpiForfaitLimit ?? 50)) * 100)) : 0; ?>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?= $kpiPct ?>%;"></div>
            </div>
        </div>
        <a href="/affichages" class="kpi-card kpi-card--clickable"
            style="text-decoration:none;color:inherit;display:block;"
            onclick="event.preventDefault(); document.querySelector('a.nav-item[href=\'/affichages\']')?.click();">
            <div class="flex-between-start">
                <div>
                    <div class="kpi-label" data-i18n="stat_active_jobs">Affichages actifs</div>
                    <div class="kpi-value"><?= (int) ($kpiAffichagesActifs ?? 0) ?></div>
                </div>
                <div class="kpi-icon kpi-icon--gray"><i class="fa-solid fa-briefcase"></i></div>
            </div>
            <?php $affDiff = ($kpiAffichagesActifs ?? 0) - ($kpiAffichagesActifsPrev ?? 0); ?>
            <div class="kpi-trend"><?php if ($affDiff > 0): ?><span class="kpi-trend--up"><i
                            class="fa-solid fa-arrow-up"></i> <?= $affDiff ?></span><?php elseif ($affDiff < 0): ?><span
                        class="kpi-trend--down"><i class="fa-solid fa-arrow-down"></i>
                        <?= abs($affDiff) ?></span><?php endif; ?> <span data-i18n="kpi_since_last_month">depuis le mois
                    dernier</span></div>
        </a>
        <?php $tachesRestantes = (int) ($kpiTachesRestantes ?? 0);
        $hasTaches = $tachesRestantes > 0; ?>
        <div class="kpi-card kpi-card--clickable<?= $hasTaches ? ' kpi-card--alert' : ' kpi-card--complete' ?>"
            onclick="openModal('completer-profil')" role="button" tabindex="0">
            <div class="flex-between-start">
                <div>
                    <div class="kpi-label"
                        data-i18n="<?= $hasTaches ? 'kpi_complete_profile' : 'kpi_profile_completed' ?>">
                        <?= $hasTaches ? 'Compléter votre profil' : 'Profil complété' ?>
                    </div>
                    <div class="kpi-value"><?= $tachesRestantes ?></div>
                </div>
                <?php if ($hasTaches): ?>
                    <div class="kpi-icon kpi-icon--red"><i class="fa-solid fa-clipboard-check"></i></div>
                <?php else: ?>
                    <div class="kpi-icon kpi-icon--blue"><i class="fa-solid fa-circle-check"></i></div>
                <?php endif; ?>
            </div>
            <div class="kpi-sub"
                data-i18n="<?= $hasTaches ? ($tachesRestantes === 1 ? 'kpi_task_remaining' : 'kpi_tasks_remaining') : 'kpi_all_done' ?>">
                <?= $hasTaches ? ($tachesRestantes === 1 ? 'tâche restante' : 'tâches restantes') : 'Tout est en ordre ✓' ?>
            </div>
        </div>
    </div>

    <!-- Graphique : Candidatures par mois -->
    <div class="chart-card">
        <div class="flex-center gap-3 mb-6">
            <div class="chart-icon"><i class="fa-solid fa-chart-bar"></i></div>
            <h2 class="section-heading mb-0" data-i18n="chart_applications">Candidatures par mois</h2>
        </div>
        <?php $chartData = $chartMonths ?? []; ?>
        <?php if (empty($chartData)): ?>
            <div class="text-center" style="padding: 2rem 0; color: var(--text-secondary);">
                <i class="fa-solid fa-chart-bar"
                    style="font-size: 2rem; opacity: 0.3; margin-bottom: 0.5rem; display: block;"></i>
                <p data-i18n="chart_no_data">Aucune candidature pour le moment</p>
            </div>
        <?php else: ?>
            <div class="chart-bars">
                <?php
                $maxVal = max(array_column($chartData, 'count')) ?: 1;
                $currentMonth = date('Y-m');
                foreach ($chartData as $i => $m):
                    $cnt = (int) ($m['count'] ?? 0);
                    $h = $maxVal > 0 ? (int) (($cnt / $maxVal) * 150) : 0;
                    $label = $m['label'] ?? '';
                    $isCurrent = isset($m['month']) && $m['month'] === $currentMonth;
                    ?>
                    <div class="chart-bar-col">
                        <div class="chart-bar <?= $isCurrent ? 'chart-bar--highlight' : '' ?>" style="height: <?= $h ?>px;">
                        </div>
                        <span
                            class="chart-bar-label <?= $isCurrent ? 'chart-bar-label--highlight' : '' ?>"><?= e($label) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Journalisation -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title" data-i18n="events_title">Journalisation des événements</h2>
        </div>
        <table class="data-table" id="events-table">
            <thead>
                <tr>
                    <th data-i18n="th_date">Date</th>
                    <th data-i18n="th_user">Utilisateur</th>
                    <th data-i18n="th_action">Action</th>
                    <th data-i18n="th_details">Détails</th>
                </tr>
            </thead>
            <tbody id="events-tbody">
                <!-- Rempli par JS (pagination) -->
            </tbody>
        </table>
        <div class="card-footer events-pagination" id="events-pagination" style="display: flex; align-items: center; justify-content: center; gap: 1rem; padding: 1rem;">
            <button type="button" class="btn btn-sm btn-secondary" id="events-prev" disabled title="Page précédente">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <span class="events-page-info" id="events-page-info">—</span>
            <button type="button" class="btn btn-sm btn-secondary" id="events-next" disabled title="Page suivante">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    </div>
</div>

<!-- ─── PARAMÈTRES Section ─── -->
<div id="parametres-section" class="content-section<?= $def === 'parametres' ? ' active' : '' ?>">
    <div class="page-header">
        <h1 class="page-title" data-i18n="parametres_title">Paramètres</h1>
    </div>

    <?php
    $isNewOrg = $isNewOrg ?? false;
    $ent = $entreprise ?? null;
    $settingsCompanyName = $companyName ?? '';
    $companyIndustry = $ent['industry'] ?? '';
    $companyEmail = $ent['email'] ?? ($user['email'] ?? '');
    $companyPhone = $ent['phone'] ?? '';
    $companyAddress = $ent['address'] ?? '';
    $companyDescription = $ent['description'] ?? '';
    $companyTimezone = $ent['timezone'] ?? 'America/Montreal';
    ?>
    <!-- Entreprise -->
    <div class="card settings-pane" id="settings-company">
        <div class="card-header card-header--bordered">
            <h2 class="card-title" data-i18n="settings_company_info">Informations de l'entreprise</h2>
        </div>
        <form class="form-vertical" id="form-settings-company" onsubmit="return saveCompanySettings(event)">
            <?= csrf_field() ?>
            <div class="grid-2col">
                <div class="form-group"><label class="form-label" data-i18n="form_company_name">Nom de
                        l'entreprise</label><input type="text" class="form-input" id="settings-company-name"
                        name="company_name" value="<?= e($settingsCompanyName) ?>"
                        placeholder="<?= $isNewOrg ? 'Ex: Mon entreprise' : '' ?>"></div>
                <div class="form-group"><label class="form-label" data-i18n="form_industry">Secteur
                        d'activité</label><select class="form-select" name="industry">
                        <option value="" <?= $companyIndustry === '' ? ' selected' : '' ?>>— Sélectionner —</option>
                        <?php
                        $industries = ['Technologie', 'Finance', 'Santé', 'Commerce', 'Construction', 'Éducation', 'Restauration et hôtellerie', 'Services professionnels', 'Manufacturier', 'Transport et logistique', 'Immobilier', 'Assurance', 'Médias et communications', 'Marketing et publicité', 'Secteur public', 'Organismes à but non lucratif', 'Agroalimentaire', 'Énergie', 'Automobile', 'Conseil et stratégie', 'Ressources humaines', 'Autre'];
                        foreach ($industries as $ind):
                        ?><option value="<?= e($ind) ?>" <?= $companyIndustry === $ind ? ' selected' : '' ?>><?= e($ind) ?></option><?php endforeach; ?>
                    </select></div>
            </div>
            <div class="grid-2col">
                <div class="form-group"><label class="form-label" data-i18n="form_email">Email de contact</label><input
                        type="email" class="form-input" name="email" value="<?= e($companyEmail) ?>"
                        placeholder="<?= $isNewOrg ? 'contact@entreprise.com' : '' ?>"></div>
                <div class="form-group"><label class="form-label" data-i18n="form_phone">Téléphone</label><input
                        type="tel" class="form-input" name="phone" value="<?= e($companyPhone) ?>"
                        placeholder="<?= $isNewOrg ? '+1 (514) 555-0000' : '' ?>"></div>
            </div>
            <div class="form-group"><label class="form-label" data-i18n="form_address">Adresse</label><input type="text"
                    class="form-input" name="address" value="<?= e($companyAddress) ?>"
                    placeholder="<?= $isNewOrg ? 'Adresse complète' : '' ?>"></div>
            <div class="form-group"><label class="form-label" data-i18n="form_description">Description de
                    l'entreprise</label><textarea class="form-input" name="description" rows="4"
                    style="resize: vertical;"
                    placeholder="<?= $isNewOrg ? 'Décrivez votre entreprise...' : '' ?>"><?= e($companyDescription) ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label" data-i18n="form_timezone">Fuseau horaire</label>
                <select class="form-select" name="timezone" id="settings-company-timezone">
                    <option value="America/Montreal" <?= $companyTimezone === 'America/Montreal' ? ' selected' : '' ?>>Montréal (America/Montreal)</option>
                    <option value="America/Toronto" <?= $companyTimezone === 'America/Toronto' ? ' selected' : '' ?>>Toronto (America/Toronto)</option>
                    <option value="America/New_York" <?= $companyTimezone === 'America/New_York' ? ' selected' : '' ?>>New York (America/New_York)</option>
                    <option value="America/Chicago" <?= $companyTimezone === 'America/Chicago' ? ' selected' : '' ?>>Chicago (America/Chicago)</option>
                    <option value="America/Los_Angeles" <?= $companyTimezone === 'America/Los_Angeles' ? ' selected' : '' ?>>Los Angeles (America/Los_Angeles)</option>
                    <option value="America/Vancouver" <?= $companyTimezone === 'America/Vancouver' ? ' selected' : '' ?>>Vancouver (America/Vancouver)</option>
                    <option value="Europe/Paris" <?= $companyTimezone === 'Europe/Paris' ? ' selected' : '' ?>>Paris (Europe/Paris)</option>
                    <option value="Europe/London" <?= $companyTimezone === 'Europe/London' ? ' selected' : '' ?>>Londres (Europe/London)</option>
                    <option value="UTC" <?= $companyTimezone === 'UTC' ? ' selected' : '' ?>>UTC</option>
                </select>
                <p class="form-help" data-i18n="timezone_help">Les dates sont enregistrées en UTC et affichées selon ce fuseau.</p>
            </div>
            <div class="form-group hidden" style="display: none !important;"><!-- Couleur marque masquée temporairement (FR/EN) -->
                <label class="form-label" data-i18n="form_brand_color">Couleur de la marque</label>
                <div class="flex-center gap-4">
                    <input type="color" class="form-input" name="brand_color" id="settings-brand-color" value="#3B82F6"
                        style="width: 60px; height: 40px; padding: 0.25rem;">
                    <input type="text" class="form-input" name="brand_color_hex" id="settings-brand-color-hex" value="#3B82F6" style="width: 120px;">
                </div>
            </div>
            <div class="form-actions"><button type="button" class="btn btn-secondary"
                    data-i18n="btn_cancel">Annuler</button><button type="submit" class="btn btn-primary"
                    data-i18n="btn_save">Enregistrer</button></div>
        </form>
    </div>

    <!-- Départements -->
    <div class="card settings-pane hidden" id="settings-departments">
        <div class="card-header card-header--bordered">
            <h2 class="card-title" data-i18n="settings_departments">Départements</h2>
            <span class="subtitle-muted" id="settings-departments-count">0 départements</span>
        </div>
        <div id="settings-departments-list" class="departments-list"></div>
        <div class="departments-add-row mt-4">
            <input type="text" id="dept-new-name" class="form-input" placeholder="Nom du département"
                onkeydown="if(event.key==='Enter'){addDepartment(); event.preventDefault();}">
            <button class="btn btn-primary" onclick="addDepartment()" style="flex-shrink:0;"><i
                    class="fa-solid fa-plus"></i></button>
        </div>
    </div>

    <!-- Accès entreprise (membres avec même accès que le propriétaire) -->
    <div class="card settings-pane hidden" id="settings-team">
        <div class="card-header card-header--bordered">
            <h2 class="card-title" data-i18n="settings_company_access">Accès entreprise</h2>
            <span class="subtitle-muted" id="settings-team-count">0 utilisateurs</span>
        </div>
        <p class="form-help mb-3" data-i18n="settings_company_access_help">Les personnes ajoutées ici auront le même accès que vous : postes, affichages, candidats et paramètres.</p>
        <div id="settings-team-list" class="team-members-list"></div>
        <div class="team-members-add-row mt-4" id="settings-team-add-row">
            <input type="text" id="team-new-prenom" class="form-input" placeholder="Prénom">
            <input type="text" id="team-new-nom" class="form-input" placeholder="Nom">
            <input type="email" id="team-new-email" class="form-input" placeholder="Courriel" required>
            <button class="btn btn-primary" onclick="addTeamMember()" style="flex-shrink:0;" title="Ajouter"><i class="fa-solid fa-plus"></i></button>
        </div>
    </div>

    <!-- Forfaits -->
    <div class="settings-pane hidden" id="settings-billing">
        <?php require VIEWS_PATH . '/dashboard/_billing.php'; ?>
    </div>

    <!-- Communication -->
    <div class="settings-pane hidden" id="settings-communications">
        <?php require VIEWS_PATH . '/dashboard/_communications.php'; ?>
    </div>

</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     MODALS
     ═══════════════════════════════════════════════════════════════════════ -->

<!-- Modal Poste -->
<div class="modal-overlay" id="poste-modal">
    <div class="modal" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 class="modal-title" data-i18n="modal_add_poste">Nouveau poste</h2><button class="btn-icon"
                onclick="closeModal('poste')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="form-poste-create" onsubmit="return savePosteFromModal(event)">
            <?= csrf_field() ?>
            <div class="form-group"><label class="form-label" data-i18n="form_department">Département</label><select
                    class="form-select" name="department">
                    <option value="" data-i18n="option_select">— Sélectionner —</option>
                    <?php
                    $deptKeys = ['Technologie' => 'dept_technologie', 'Gestion' => 'dept_gestion', 'Design' => 'dept_design', 'Stratégie' => 'dept_strategie', 'Marketing' => 'dept_marketing', 'Ressources humaines' => 'dept_ressources_humaines', 'Finance' => 'dept_finance', 'Opérations' => 'dept_operations'];
                    foreach (($departments ?? []) as $d):
                        $key = $deptKeys[$d] ?? null;
                    ?><option value="<?= e($d) ?>"<?= $key ? ' data-i18n="' . e($key) . '"' : '' ?>><?= e($d) ?></option><?php endforeach; ?>
                </select></div>
            <div class="form-group"><label class="form-label" data-i18n="form_title">Titre du poste</label><input
                    type="text" class="form-input" name="title" required placeholder="Ex: Développeur Frontend"></div>
            <div class="form-group"><label class="form-label" data-i18n="form_location">Lieu</label><input type="text"
                    class="form-input" name="location" placeholder="Ex: Montréal, QC"></div>
            <div class="form-group"><label class="form-label" data-i18n="form_status">Statut</label><select
                    class="form-select" name="status">
                    <option value="active" data-i18n="status_active">Actif</option>
                    <option value="paused" data-i18n="status_paused">Pausé</option>
                    <option value="closed" data-i18n="status_closed">Fermé</option>
                </select></div>
            <div class="modal-actions"><button type="button" class="btn btn-secondary" onclick="closeModal('poste')"
                    data-i18n="btn_cancel">Annuler</button><button type="submit" class="btn btn-primary"
                    data-i18n="btn_save">Enregistrer</button></div>
        </form>
    </div>
</div>

<!-- Modal Affichage -->
<div class="modal-overlay" id="affichage-modal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title" data-i18n="modal_add_affichage">Nouvel affichage</h2><button class="btn-icon"
                onclick="closeModal('affichage')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="form-affichage-create" onsubmit="return saveAffichageFromModal(event)">
            <?= csrf_field() ?>
            <div class="form-group"><label class="form-label" data-i18n="form_poste">Poste</label><select
                    class="form-select" name="poste_id" id="affichage-poste_id">
                    <option value="">— Sélectionner —</option><?php foreach ($postes ?? [] as $p): ?>
                        <option value="<?= e($p['id']) ?>" data-department="<?= e($p['department'] ?? '') ?>">
                            <?= e($p['title']) ?>
                        </option><?php endforeach; ?>
                </select></div>
            <div class="modal-actions"><button type="button" class="btn btn-secondary" onclick="closeModal('affichage')"
                    data-i18n="btn_cancel">Annuler</button><button type="submit" class="btn btn-primary"
                    data-i18n="btn_save">Enregistrer</button></div>
        </form>
    </div>
</div>

<!-- Modal Feedback -->
<div class="modal-overlay" id="feedback-modal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title" data-i18n="modal_feedback_title">Feedback</h2><button class="btn-icon"
                onclick="closeModal('feedback')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form onsubmit="sendFeedback(event)">
            <?= csrf_field() ?>
            <div class="form-group">
                <label class="form-label" data-i18n="label_type">Type de retour</label>
                <div class="flex-center gap-4">
                    <label class="radio-option"><input type="radio" name="feedback_type" value="problem" checked><span
                            data-i18n="option_problem">Signaler un problème</span></label>
                    <label class="radio-option"><input type="radio" name="feedback_type" value="idea"><span
                            data-i18n="option_idea">Soumettre une idée</span></label>
                </div>
            </div>
            <div class="form-group"><label class="form-label" data-i18n="label_message">Votre message</label><textarea
                    name="message" class="form-input" rows="4" style="resize: vertical;"
                    data-i18n-placeholder="feedback_placeholder" placeholder="Dites-nous en plus..."
                    required></textarea></div>
            <div class="modal-actions"><button type="button" class="btn btn-secondary" onclick="closeModal('feedback')"
                    data-i18n="btn_cancel">Annuler</button><button type="submit" class="btn btn-primary"
                    data-i18n="btn_send">Envoyer</button></div>
        </form>
    </div>
</div>

<!-- Modal Notifier les candidats -->
<div class="modal-overlay" id="notify-candidats-modal">
    <div class="modal modal--narrow">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fa-solid fa-envelope"></i> <span data-i18n="notify_candidats_title">Notifier les candidats</span></h2><button
                class="btn-icon" onclick="closeModal('notify-candidats')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="mb-5">
            <p class="subtitle-muted mb-2" data-i18n="notify_candidats_subtitle">Sélectionnez les candidats à notifier par courriel.</p>
        </div>
        <div class="mb-5">
            <div class="flex-between mb-3">
                <label class="form-label mb-0 fw-semibold" data-i18n="notify_candidats_list_label">Candidats à notifier</label>
                <label class="select-all-label">
                    <input type="checkbox" id="notify-select-all" onchange="toggleSelectAllNotify(this)"><span data-i18n="notify_select_all">Tout sélectionner</span>
                </label>
            </div>
            <div id="notify-candidats-list" class="candidate-list-scroll"></div>
        </div>
        <div class="form-group mb-5">
            <label class="form-label fw-semibold" data-i18n="notify_message_label">Message aux candidats</label>
            <div id="notify-template-buttons" class="flex-center gap-2 mb-3 flex-wrap">
                <!-- Boutons générés depuis les modèles Paramètres > Communication -->
            </div>
            <textarea id="notify-candidats-message" class="form-input w-full" rows="4" style="resize: vertical;"
                placeholder="Rédigez votre message..." data-i18n-placeholder="notify_message_placeholder"></textarea>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeModal('notify-candidats')" data-i18n="btn_cancel">Annuler</button>
            <button type="button" class="btn btn-primary" onclick="confirmNotifyCandidats()"><i
                    class="fa-solid fa-paper-plane"></i> <span data-i18n="btn_send">Envoyer</span></button>
        </div>
    </div>
</div>

<!-- Modal Détail communication envoyée -->
<div class="modal-overlay" id="communication-detail-modal">
    <div class="modal modal--narrow">
        <div class="modal-header">
            <h2 class="modal-title" id="communication-detail-title"><i class="fa-solid fa-envelope-open"></i> Message envoyé</h2>
            <button class="btn-icon" onclick="closeModal('communication-detail')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <p class="subtitle-muted mb-4" id="communication-detail-date"></p>
        <div id="communication-detail-content" class="form-input" style="white-space: pre-wrap; min-height: 8rem; background: var(--bg-secondary);"></div>
        <div class="modal-actions mt-5">
            <button type="button" class="btn btn-secondary" onclick="closeModal('communication-detail')">Fermer</button>
        </div>
    </div>
</div>

<!-- Modal Ajouter un candidat -->
<div class="modal-overlay" id="add-candidat-modal">
    <div class="modal modal--narrow">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fa-solid fa-user-plus"></i> Ajouter un candidat</h2><button
                class="btn-icon" onclick="closeModal('add-candidat')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form onsubmit="submitAddCandidat(event)">
            <?= csrf_field() ?>
            <div class="form-group mb-4">
                <label class="form-label" for="add-candidat-prenom">Prénom</label>
                <input type="text" id="add-candidat-prenom" class="form-input" required>
            </div>
            <div class="form-group mb-4">
                <label class="form-label" for="add-candidat-nom">Nom</label>
                <input type="text" id="add-candidat-nom" class="form-input" required>
            </div>
            <div class="form-group mb-4">
                <label class="form-label" for="add-candidat-email">Courriel</label>
                <input type="email" id="add-candidat-email" class="form-input" required>
            </div>
            <div class="form-group mb-5">
                <label class="form-label" for="add-candidat-phone">Téléphone</label>
                <input type="tel" id="add-candidat-phone" class="form-input">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('add-candidat')">Annuler</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> <span
                        data-i18n="btn_add">Ajouter</span></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Compléter votre profil -->
<div class="modal-overlay" id="completer-profil-modal">
    <div class="modal modal--narrow">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fa-solid fa-clipboard-check"></i> <span
                    data-i18n="modal_complete_profile">Compléter votre profil</span></h2>
            <button class="btn-icon" onclick="closeModal('completer-profil')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="completer-profil-list">
            <a href="#parametres" class="completer-profil-item" onclick="closeModal('completer-profil')">
                <span class="completer-profil-num"
                    style="<?= $hasCompanyName ? 'background-color:var(--success-color);color:#fff;border-color:var(--success-color);' : '' ?>">
                    <?php if ($hasCompanyName): ?><i class="fa-solid fa-check"></i><?php else: ?>1<?php endif; ?>
                </span>
                <div>
                    <strong data-i18n="profile_step1_title">Détail de votre organisation</strong>
                    <span class="subtitle-muted" data-i18n="profile_step1_sub">Paramètres de l'entreprise</span>
                </div>
                <?php if ($hasCompanyName): ?>
                    <span class="status-badge status-active ml-auto"><i class="fa-solid fa-check"></i> <span
                            data-i18n="badge_done">Fait</span></span>
                <?php else: ?>
                    <i class="fa-solid fa-chevron-right ml-auto"></i>
                <?php endif; ?>
            </a>
            <a href="#postes" class="completer-profil-item" onclick="closeModal('completer-profil')">
                <span class="completer-profil-num"
                    style="<?= $hasPoste ? 'background-color:var(--success-color);color:#fff;border-color:var(--success-color);' : '' ?>">
                    <?php if ($hasPoste): ?><i class="fa-solid fa-check"></i><?php else: ?>2<?php endif; ?>
                </span>
                <div>
                    <strong data-i18n="profile_step2_title">Créer un poste</strong>
                    <span class="subtitle-muted" data-i18n="profile_step2_sub">Définir vos postes à pourvoir</span>
                </div>
                <?php if ($hasPoste): ?>
                    <span class="status-badge status-active ml-auto"><i class="fa-solid fa-check"></i> <span
                            data-i18n="badge_done">Fait</span></span>
                <?php else: ?>
                    <i class="fa-solid fa-chevron-right ml-auto"></i>
                <?php endif; ?>
            </a>
            <a href="#affichages" class="completer-profil-item" onclick="closeModal('completer-profil')">
                <span class="completer-profil-num"
                    style="<?= $hasAffichage ? 'background-color:var(--success-color);color:#fff;border-color:var(--success-color);' : '' ?>">
                    <?php if ($hasAffichage): ?><i class="fa-solid fa-check"></i><?php else: ?>3<?php endif; ?>
                </span>
                <div>
                    <strong data-i18n="profile_step3_title">Créer un affichage</strong>
                    <span class="subtitle-muted" data-i18n="profile_step3_sub">Publier votre poste</span>
                </div>
                <?php if ($hasAffichage): ?>
                    <span class="status-badge status-active ml-auto"><i class="fa-solid fa-check"></i> <span
                            data-i18n="badge_done">Fait</span></span>
                <?php else: ?>
                    <i class="fa-solid fa-chevron-right ml-auto"></i>
                <?php endif; ?>
            </a>
        </div>
    </div>
</div>

<!-- Modal Confirmation suppression affichage -->
<div class="modal-overlay" id="delete-affichage-modal">
    <div class="modal modal--narrow" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fa-solid fa-trash"></i> <span data-i18n="modal_delete_affichage">Supprimer
                    l'affichage</span></h2>
            <button class="btn-icon" onclick="closeModal('delete-affichage')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <p class="modal-body" id="delete-affichage-message" data-i18n="modal_delete_affichage_msg">Êtes-vous sûr de
            vouloir supprimer cet affichage ?</p>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeModal('delete-affichage')"
                data-i18n="btn_cancel">Annuler</button>
            <button type="button" class="btn btn-danger" id="delete-affichage-confirm-btn"
                onclick="confirmDeleteAffichage()">
                <i class="fa-solid fa-trash"></i> <span data-i18n="action_delete">Supprimer</span>
            </button>
        </div>
    </div>
</div>

<!-- Modal Confirmation suppression poste (soft delete) -->
<div class="modal-overlay" id="delete-poste-modal">
    <div class="modal modal--narrow" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fa-solid fa-trash"></i> <span data-i18n="delete_poste_title">Supprimer le
                    poste</span></h2>
            <button class="btn-icon" onclick="closeModal('delete-poste')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <p class="modal-body" id="delete-poste-message" data-i18n="modal_delete_poste_msg">Êtes-vous sûr de vouloir
            supprimer ce poste ?</p>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeModal('delete-poste')"
                data-i18n="btn_cancel">Annuler</button>
            <button type="button" class="btn btn-danger" onclick="confirmDeletePoste()">
                <i class="fa-solid fa-trash"></i> <span data-i18n="action_delete">Supprimer</span>
            </button>
        </div>
    </div>
</div>
