<!-- ═══════════════════════════════════════════════════════════════════════
     VUE : Tableau de bord employeur (gestion autonome)
     Toutes les sections (SPA côté client, affichées/masquées via JS).
     Les données viennent de APP_DATA (injecté par le layout).
     ═══════════════════════════════════════════════════════════════════════ -->

<?php
$appUrl = defined('GESTION_APP_URL') ? GESTION_APP_URL : 'https://app.ciaocv.com';
?>

<!-- ─── POSTES Section ─── -->
<div id="postes-section" class="content-section">
    <div class="page-header">
        <h1 class="page-title" data-i18n="postes_title">Postes actifs</h1>
    </div>
    <div class="filters-bar">
        <div class="view-tabs">
            <button class="view-tab active" data-i18n="filter_all">Tous</button>
            <button class="view-tab" data-i18n="filter_active">Actifs</button>
            <button class="view-tab" data-i18n="filter_inactive">Non actifs</button>
            <button class="view-tab" data-i18n="filter_archived">Archivés</button>
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
                <th data-i18n="th_candidates">Candidats</th>
                <th data-i18n="th_created">Créé le</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($postes as $p): ?>
            <tr class="row-clickable" data-poste-id="<?= e($p['id']) ?>" role="button" tabindex="0">
                <td><strong><?= e($p['title']) ?></strong></td>
                <td><?= e($p['department']) ?></td>
                <td><?= e($p['location']) ?></td>
                <td><span class="status-badge <?= e($p['statusClass']) ?>"><?= e($p['status']) ?></span></td>
                <td><?= $p['candidates'] ?></td>
                <td><?= e($p['date']) ?></td>
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
    <div class="card">
        <h3 class="card-subtitle">Informations</h3>
        <div class="info-row"><div class="info-label">Candidats</div><div class="info-value" id="detail-poste-candidates">0</div></div>
        <div class="action-stack">
            <button class="btn btn-primary btn--center" onclick="openPosteCandidatsModal()">
                <i class="fa-solid fa-users"></i> Voir les candidats
            </button>
        </div>
    </div>
    <div class="card">
        <div class="flex-between mb-4">
            <h3 class="section-heading mb-0">Questions de présélection</h3>
            <span class="subtitle-muted" id="detail-poste-questions-count">0 questions</span>
        </div>
        <div id="detail-poste-questions-list" class="questions-list"></div>
        <div class="questions-add-row mt-4">
            <input type="text" id="detail-poste-new-question" class="form-input" placeholder="Ajouter une question..." onkeydown="if(event.key==='Enter'){addPosteQuestion(); event.preventDefault();}">
            <button class="btn btn-primary" onclick="addPosteQuestion()"><i class="fa-solid fa-plus"></i></button>
        </div>
        <div class="flex-between mt-6" style="border-top: 1px solid var(--border-color); padding-top: 1rem;">
            <div>
                <label class="form-label mb-0 fw-semibold"><i class="fa-solid fa-video"></i> Durée d'enregistrement</label>
                <div class="form-help">Temps maximum par question pour le candidat.</div>
            </div>
            <select class="form-select form-select--auto" id="detail-poste-record-duration" onchange="updatePosteRecordDuration(this.value)">
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
                    <div><div class="info-label">Vues</div><div class="info-value" id="detail-affichage-views">0</div></div>
                    <div><div class="info-label">Candidatures</div><div class="info-value" id="detail-affichage-apps">0</div></div>
                </div>
                <h3 class="card-subtitle mt-6">Détails</h3>
                <div class="detail-row"><span class="info-label">Début</span><span class="info-value-sm" id="detail-affichage-start">—</span></div>
                <div class="detail-row"><span class="info-label">Fin</span><span class="info-value-sm" id="detail-affichage-end">—</span></div>
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
        <div class="action-group">
            <select class="status-select" id="affichage-status-select" onchange="updateAffichageStatus(this.value)">
                <option value="actif">Actif</option>
                <option value="termine">Terminé</option>
                <option value="archive">Archivé</option>
            </select>
            <button class="btn-icon" title="Notifier les candidats" onclick="openNotifyCandidatsModal()"><i class="fa-solid fa-envelope"></i></button>
        </div>
    </div>
    <div class="alert-warning mb-4 hidden" id="affichage-termine-alert">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span>Cet affichage est terminé. Les fichiers associés seront supprimés dans <strong>15 jours</strong> si le statut ne change pas.</span>
    </div>
    <div class="filters-bar">
        <div class="view-tabs">
            <button class="view-tab active">Tous</button>
            <button class="view-tab">Nouveaux</button>
            <button class="view-tab">Évalués</button>
            <button class="view-tab">Refusés</button>
        </div>
    </div>
    <div class="search-row search-row--with-label">
        <span class="search-row-label">Lien à partager</span>
        <span class="search-row-url-wrap">
            <?php
            $firstAff = $affichages ? reset($affichages) : null;
            $shareLongId = $firstAff['shareLongId'] ?? '0cb075d860fa55c4';
            ?>
            <a class="search-row-url" id="affichage-share-url" href="<?= e($appUrl) ?>/entrevue/<?= e($shareLongId) ?>" target="_blank" rel="noopener"><?= e($appUrl) ?>/entrevue/<?= e($shareLongId) ?></a>
            <button type="button" class="btn-icon btn-icon--copy" title="Copier le lien" onclick="copyShareUrl()"><i class="fa-regular fa-copy"></i></button>
        </span>
    </div>
    <table class="data-table">
        <thead><tr><th>Candidat</th><th>Statut</th><th>Favori</th><th>Postulé le</th></tr></thead>
        <tbody id="affichage-candidats-tbody"></tbody>
    </table>
    <div class="card mt-6">
        <div class="flex-between mb-4">
            <h3 class="section-heading mb-0"><i class="fa-solid fa-user-check"></i> Évaluateurs</h3>
            <span class="subtitle-muted" id="affichage-evaluateurs-count">0 évaluateurs</span>
        </div>
        <div id="affichage-evaluateurs-list" class="evaluateurs-list"></div>
        <div class="evaluateurs-add-row mt-4">
            <input type="text" id="eval-new-prenom" class="form-input" placeholder="Prénom">
            <input type="text" id="eval-new-nom" class="form-input" placeholder="Nom">
            <input type="email" id="eval-new-email" class="form-input" placeholder="Courriel" onkeydown="if(event.key==='Enter'){addEvaluateur(); event.preventDefault();}">
            <button class="btn btn-primary" onclick="addEvaluateur()" style="flex-shrink:0;"><i class="fa-solid fa-plus"></i></button>
        </div>
    </div>
</div>

<!-- ─── AFFICHAGES Section ─── -->
<div id="affichages-section" class="content-section">
    <div class="page-header"><h1 class="page-title" data-i18n="affichages_title">Affichages en cours</h1></div>
    <div class="filters-bar">
        <div class="view-tabs">
            <button class="view-tab active" data-i18n="filter_all">Tous</button>
            <button class="view-tab" data-i18n="filter_active">Actifs</button>
            <button class="view-tab" data-i18n="filter_expired">Expirés</button>
        </div>
        <button class="btn btn-primary" onclick="openModal('affichage')"><i class="fa-solid fa-plus"></i><span data-i18n="add_affichage">Nouvel affichage</span></button>
    </div>
    <div class="search-row">
        <div class="search-bar search-bar--full"><i class="fa-solid fa-magnifying-glass"></i><input type="text" placeholder="Rechercher..." data-i18n-placeholder="search"></div>
    </div>
    <table class="data-table" id="affichages-table">
        <thead>
            <tr>
                <th data-i18n="th_poste">Poste</th>
                <th data-i18n="th_department">Département</th>
                <th data-i18n="th_start_date">Date début</th>
                <th data-i18n="th_status">Statut</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($affichages as $aId => $a): ?>
            <tr onclick="showAffichageDetail('<?= e($aId) ?>')" class="row-clickable">
                <td><strong><?= e($a['title']) ?></strong></td>
                <td><?= e($a['department'] ?? '') ?></td>
                <td><?= e($a['start']) ?></td>
                <td><span class="status-badge <?= e($a['statusClass']) ?>"><?= e($a['status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- ─── CANDIDATS Section ─── -->
<div id="candidats-section" class="content-section">
    <div class="page-header"><h1 class="page-title" data-i18n="candidats_title">Candidats</h1></div>
    <div class="filters-bar">
        <div class="view-tabs">
            <button class="view-tab active" data-i18n="filter_all">Tous</button>
            <button class="view-tab" data-i18n="filter_new">Nouveaux</button>
            <button class="view-tab" data-i18n="filter_reviewed">Évalués</button>
            <button class="view-tab" data-i18n="filter_rejected">Refusés</button>
        </div>
        <div><select class="form-select form-select--auto"><option data-i18n="filter_all_jobs">Tous les postes</option><option>Développeur Frontend</option><option>Chef de projet</option><option>Designer UX/UI</option></select></div>
    </div>
    <div class="search-row">
        <div class="search-bar search-bar--full"><i class="fa-solid fa-magnifying-glass"></i><input type="text" placeholder="Rechercher..." data-i18n-placeholder="search"></div>
    </div>
    <table class="data-table" id="candidats-table">
        <thead><tr><th data-i18n="th_candidate">Candidat</th><th data-i18n="th_poste">Poste</th><th data-i18n="th_status">Statut</th><th data-i18n="th_rating">Note</th><th data-i18n="th_applied">Postulé le</th></tr></thead>
        <tbody>
            <?php foreach ($candidats as $cId => $c): ?>
            <tr onclick="showCandidateDetail('<?= e($cId) ?>')" class="row-clickable">
                <td>
                    <div class="flex-center gap-3">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($c['name']) ?>&background=<?= e($c['color']) ?>&color=fff" class="avatar" alt="">
                        <div><strong><?= e($c['name']) ?></strong><div class="subtitle-muted"><?= e($c['email']) ?></div></div>
                    </div>
                </td>
                <td><?= e($c['role']) ?></td>
                <td><?php
                    $statusMap = [
                        'new'         => ['label' => 'Nouveau',  'class' => 'status-new'],
                        'reviewed'    => ['label' => 'Évalué',   'class' => 'status-active'],
                        'rejected'    => ['label' => 'Refusé',   'class' => 'status-rejected'],
                        'shortlisted' => ['label' => 'Favori',   'class' => 'status-shortlisted'],
                    ];
                    $st = $c['status'];
                    $badge = $statusMap[$st] ?? ['label' => $st, 'class' => ''];
                ?><span class="status-badge <?= $badge['class'] ?>"><?= e($badge['label']) ?></span></td>
                <td><div class="star-color"><?php for ($i = 1; $i <= 5; $i++): ?><i class="fa-<?= $i <= $c['rating'] ? 'solid' : 'regular' ?> fa-star"></i><?php endfor; ?></div></td>
                <td>2026-02-01</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- ─── CANDIDAT DÉTAIL Section ─── -->
<div id="candidate-detail-section" class="content-section section--detail">
    <div class="page-header--detail">
        <button class="btn-icon btn-back" onclick="goBackToCandidates()"><i class="fa-solid fa-arrow-left"></i></button>
        <div>
            <h1 class="page-title" id="detail-candidate-name">Nom du Candidat</h1>
            <div class="subtitle-muted" id="detail-candidate-role-source">Poste • Source</div>
        </div>
        <div class="action-group flex-center gap-3">
            <select class="status-select status-select--candidate" id="detail-candidate-status-select" onchange="updateCandidateStatus(this.value)">
                <option value="new" data-i18n="status_new">Nouveau</option>
                <option value="reviewed" data-i18n="status_accepted">Accepté</option>
                <option value="rejected" data-i18n="status_rejected">Refusé</option>
                <option value="shortlisted" data-i18n="status_shortlisted">Favori</option>
            </select>
            <button class="favorite-btn" id="detail-candidate-favorite" onclick="toggleFavorite()" title="Mettre en favori"><i class="fa-regular fa-star"></i></button>
        </div>
    </div>
    <div class="card contact-card mt-6">
        <h3 class="contact-heading"><i class="fa-regular fa-envelope"></i> Email</h3>
        <p id="detail-candidate-email" class="text-body mb-4">email@example.com</p>
        <h3 class="contact-heading"><i class="fa-solid fa-phone"></i> <span data-i18n="form_phone">Téléphone</span></h3>
        <p id="detail-candidate-phone" class="text-body">+1 514 555-0199</p>
    </div>
    <div class="video-container">
        <video controls class="hidden" id="detail-candidate-video-player"><source src="" type="video/mp4"></video>
        <div id="detail-video-placeholder" class="text-center">
            <i class="fa-solid fa-play-circle icon-xl"></i>
            <div class="mt-2" data-i18n="video_preview">Aperçu vidéo</div>
        </div>
    </div>
    <div class="card">
        <h3 class="section-heading-sm" data-i18n="comments_title">Commentaires</h3>
        <div class="timeline-container" id="detail-timeline-list"></div>
        <div class="flex-center gap-2">
            <textarea class="form-input" id="detail-new-comment-input" rows="1" placeholder="Ajouter une note..." style="resize: none;" data-i18n-placeholder="add_note_placeholder"></textarea>
            <button class="btn btn-primary" onclick="addComment()"><i class="fa-solid fa-paper-plane"></i></button>
        </div>
    </div>
</div>

<!-- ─── STATISTIQUES / TABLEAU DE BORD Section ─── -->
<div id="statistiques-section" class="content-section active">
    <div class="page-header"><h1 class="page-title" data-i18n="statistiques_title">Tableau de bord</h1></div>
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="flex-between-start">
                <div>
                    <div class="kpi-label" data-i18n="dashboard_kpi_users">Nombre d'utilisateurs</div>
                    <div class="kpi-value">247</div>
                </div>
                <div class="kpi-icon kpi-icon--blue"><i class="fa-solid fa-users"></i></div>
            </div>
            <div class="kpi-trend"><span class="kpi-trend--up"><i class="fa-solid fa-arrow-up"></i> 12</span> <span data-i18n="dashboard_this_month">ce mois</span></div>
        </div>
        <div class="kpi-card">
            <div class="flex-between-start">
                <div>
                    <div class="kpi-label" data-i18n="dashboard_kpi_videos">Nombre de vidéos sous gestion</div>
                    <div class="kpi-value">1 842</div>
                </div>
                <div class="kpi-icon kpi-icon--gray"><i class="fa-solid fa-video"></i></div>
            </div>
            <div class="kpi-trend"><span class="kpi-trend--up"><i class="fa-solid fa-arrow-up"></i> 156</span> <span data-i18n="dashboard_this_month">ce mois</span></div>
        </div>
        <div class="kpi-card">
            <div class="flex-between-start">
                <div>
                    <div class="kpi-label" data-i18n="dashboard_kpi_sales">Ventes du mois</div>
                    <div class="kpi-value">8 450 $</div>
                </div>
                <div class="kpi-icon kpi-icon--red"><i class="fa-solid fa-credit-card"></i></div>
            </div>
            <div class="kpi-trend"><span class="kpi-trend--up"><i class="fa-solid fa-arrow-up"></i> 23 %</span> <span data-i18n="dashboard_vs_prev_month">vs mois précédent</span></div>
        </div>
    </div>
    <div class="chart-card">
        <div class="flex-center gap-3 mb-6">
            <div class="chart-icon"><i class="fa-solid fa-chart-bar"></i></div>
            <h2 class="section-heading mb-0" data-i18n="chart_sales_history">Historiques des ventes</h2>
        </div>
        <div class="chart-bars">
            <?php
            $months = [['Sep', 60], ['Oct', 100], ['Nov', 80], ['Déc', 140], ['Jan', 180], ['Fév', 120]];
            $maxVal = max(array_column($months, 1));
            foreach ($months as $i => $m):
                $pct = $maxVal > 0 ? round(100 * $m[1] / $maxVal) : 0;
            ?>
            <div class="chart-bar-col">
                <div class="chart-bar-wrap">
                    <div class="chart-bar <?= $i === 4 ? 'chart-bar--highlight' : '' ?>" style="height: <?= $pct ?>%;"></div>
                </div>
                <span class="chart-bar-label <?= $i === 4 ? 'chart-bar-label--highlight' : '' ?>"><?= $m[0] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h2 class="card-title" data-i18n="dashboard_events_log">Journalisation des événements</h2></div>
        <p class="subtitle-muted mb-4" data-i18n="dashboard_events_desc">Historique des modifications et actions effectuées dans l’administration (Ventes, Configuration, Utilisateurs).</p>
        <table class="data-table">
            <thead><tr><th data-i18n="th_date">Date</th><th data-i18n="th_user">Utilisateur</th><th data-i18n="th_action">Action</th><th data-i18n="th_details">Détails</th></tr></thead>
            <tbody>
                <tr><td class="cell-date">6 fév 2026, 14:32</td><td><strong>Marie Tremblay</strong></td><td><span class="event-badge event-badge--modification" data-i18n="event_modification">Modification</span></td><td class="cell-muted">A modifié le forfait <strong>Pro</strong> — limite vidéos : 50 → 75</td></tr>
                <tr><td class="cell-date">6 fév 2026, 11:15</td><td><strong>Pierre Roy</strong></td><td><span class="event-badge event-badge--creation" data-i18n="event_creation">Création</span></td><td class="cell-muted">A créé le forfait <strong>Entreprise</strong> — 500 vidéos, 199 $/mois</td></tr>
                <tr><td class="cell-date">5 fév 2026, 16:48</td><td><strong>Marie Tremblay</strong></td><td><span class="event-badge event-badge--modification" data-i18n="event_modification">Modification</span></td><td class="cell-muted">A modifié l’utilisateur <strong>Sophie Martin</strong> — rôle : Évaluateur → Admin</td></tr>
                <tr><td class="cell-date">5 fév 2026, 09:22</td><td><strong>Pierre Roy</strong></td><td><span class="event-badge event-badge--creation" data-i18n="event_creation">Création</span></td><td class="cell-muted">A ajouté l’utilisateur <strong>Luc Bergeron</strong> (luc@example.com)</td></tr>
                <tr><td class="cell-date">4 fév 2026, 15:05</td><td><strong>Marie Tremblay</strong></td><td><span class="event-badge event-badge--suppression" data-i18n="event_deletion">Suppression</span></td><td class="cell-muted">A supprimé le forfait <strong>Starter</strong></td></tr>
                <tr><td class="cell-date">4 fév 2026, 10:30</td><td><strong>Pierre Roy</strong></td><td><span class="event-badge event-badge--evaluation" data-i18n="event_sale">Vente</span></td><td class="cell-muted">Nouvelle vente Stripe — Forfait Platine, 99,99 $ (user@example.com)</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ─── VENTES STRIPE Section ─── -->
<div id="ventes-stripe-section" class="content-section">
    <div class="page-header"><h1 class="page-title" data-i18n="page_ventes_title">Liste des ventes Stripe</h1></div>
    <div class="card">
        <p class="subtitle-muted mb-4" data-i18n="page_ventes_desc">Historique des transactions et abonnements Stripe.</p>
        <table class="data-table">
            <thead><tr><th>ID</th><th data-i18n="th_date">Date</th><th data-i18n="th_client">Client</th><th data-i18n="th_amount">Montant</th><th data-i18n="th_status">Statut</th></tr></thead>
            <tbody>
                <tr><td>pi_xxx123</td><td>6 fév 2026</td><td>user@example.com</td><td>29,99 $</td><td><span class="status-badge status-active" data-i18n="status_paid">Payé</span></td></tr>
                <tr><td>pi_xxx124</td><td>5 fév 2026</td><td>autre@example.com</td><td>99,99 $</td><td><span class="status-badge status-active" data-i18n="status_paid">Payé</span></td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ─── FORFAITS CRUD Section ─── -->
<div id="forfaits-crud-section" class="content-section">
    <div class="page-header">
        <h1 class="page-title" data-i18n="nav_forfaits">Forfaits</h1>
        <button class="btn btn-primary"><i class="fa-solid fa-plus"></i> <span data-i18n="btn_add">Ajouter</span></button>
    </div>
    <div class="card">
        <table class="data-table">
            <thead><tr><th data-i18n="th_name">Nom</th><th data-i18n="th_video_limit">Limite vidéos</th><th data-i18n="th_price_monthly">Prix mensuel</th><th data-i18n="th_price_yearly">Prix annuel</th><th data-i18n="th_actions">Actions</th></tr></thead>
            <tbody>
                <tr><td>Starter</td><td>10</td><td>9,99 $</td><td>99 $</td><td><button class="btn-icon" data-i18n-title="action_edit" title="Modifier"><i class="fa-solid fa-pen"></i></button></td></tr>
                <tr><td>Pro</td><td>50</td><td>29,99 $</td><td>299 $</td><td><button class="btn-icon" data-i18n-title="action_edit" title="Modifier"><i class="fa-solid fa-pen"></i></button></td></tr>
                <tr><td>Platine</td><td>200</td><td>99,99 $</td><td>999 $</td><td><button class="btn-icon" data-i18n-title="action_edit" title="Modifier"><i class="fa-solid fa-pen"></i></button></td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ─── UTILISATEURS LISTE Section ─── -->
<div id="utilisateurs-liste-section" class="content-section">
    <div class="page-header">
        <h1 class="page-title" data-i18n="nav_utilisateurs">Utilisateurs</h1>
        <button class="btn btn-primary"><i class="fa-solid fa-plus"></i> <span data-i18n="btn_new_user">Nouvel utilisateur</span></button>
    </div>
    <div class="search-row">
        <div class="search-bar search-bar--full"><i class="fa-solid fa-magnifying-glass"></i><input type="text" data-i18n-placeholder="search" placeholder="Rechercher..."></div>
    </div>
    <table class="data-table">
        <thead><tr><th data-i18n="th_name">Nom</th><th>Email</th><th data-i18n="th_role">Rôle</th><th data-i18n="th_created">Créé le</th><th data-i18n="th_actions">Actions</th></tr></thead>
        <tbody>
            <tr><td><strong>Marie Tremblay</strong></td><td>marie@example.com</td><td><span class="status-badge status-active">Admin</span></td><td>2026-01-15</td><td><button class="btn-icon" data-i18n-title="action_edit" title="Modifier"><i class="fa-solid fa-pen"></i></button></td></tr>
            <tr><td><strong>Pierre Roy</strong></td><td>pierre@example.com</td><td><span class="status-badge">Utilisateur</span></td><td>2026-02-01</td><td><button class="btn-icon" data-i18n-title="action_edit" title="Modifier"><i class="fa-solid fa-pen"></i></button></td></tr>
        </tbody>
    </table>
</div>

<!-- ─── SYNCHRONISATION Section ─── -->
<div id="synchronisation-section" class="content-section">
    <div class="page-header"><h1 class="page-title" data-i18n="nav_synchronisation">Synchronisation</h1></div>
    <div class="card">
        <p class="subtitle-muted mb-4" data-i18n="page_sync_desc">Synchronisation des données avec les services externes.</p>
        <div class="empty-state">
            <i class="fa-solid fa-arrows-rotate"></i>
            <p data-i18n="content_coming">Contenu à venir</p>
        </div>
    </div>
</div>

<!-- ─── PARAMÈTRES Section (ancien, conservé pour compatibilité) ─── -->
<div id="parametres-section" class="content-section">
    <div class="page-header"><h1 class="page-title" data-i18n="parametres_title">Paramètres</h1></div>
    <div class="card settings-pane" id="settings-company">
        <div class="card-header card-header--bordered">
            <h2 class="card-title" data-i18n="settings_company_info">Informations de l'entreprise</h2>
        </div>
        <form class="form-vertical">
            <div class="grid-2col">
                <div class="form-group"><label class="form-label" data-i18n="form_company_name">Nom de l'entreprise</label><input type="text" class="form-input" value="Acme Corporation"></div>
                <div class="form-group"><label class="form-label" data-i18n="form_industry">Secteur d'activité</label><select class="form-select"><option>Technologie</option><option>Finance</option><option>Santé</option><option>Commerce</option></select></div>
            </div>
            <div class="grid-2col">
                <div class="form-group"><label class="form-label" data-i18n="form_email">Email de contact</label><input type="email" class="form-input" value="rh@acme.com"></div>
                <div class="form-group"><label class="form-label" data-i18n="form_phone">Téléphone</label><input type="tel" class="form-input" value="+1 (514) 555-0123"></div>
            </div>
            <div class="form-group"><label class="form-label" data-i18n="form_address">Adresse</label><input type="text" class="form-input" value="1234 Rue Principale, Montréal, QC H2X 1Y6"></div>
            <div class="form-group"><label class="form-label" data-i18n="form_description">Description de l'entreprise</label><textarea class="form-input" rows="4" style="resize: vertical;">Acme Corporation est une entreprise leader dans le domaine de la technologie.</textarea></div>
            <div class="form-actions"><button type="button" class="btn btn-secondary" data-i18n="btn_cancel">Annuler</button><button type="submit" class="btn btn-primary" data-i18n="btn_save">Enregistrer</button></div>
        </form>
    </div>
    <div class="card settings-pane hidden" id="settings-branding">
        <div class="card-header card-header--bordered"><h2 class="card-title" data-i18n="settings_branding_title">Personnalisation de la marque</h2></div>
        <form class="form-vertical">
            <div class="form-group">
                <label class="form-label" data-i18n="form_logo">Logo de l'entreprise</label>
                <p class="form-help" data-i18n="logo_help">Affiché sur votre profil et vos offres.</p>
                <div class="flex-center gap-4">
                    <div class="logo-preview"><img src="https://ui-avatars.com/api/?name=Acme&background=3B82F6&color=fff&size=80" alt="Logo" class="w-full" style="height:100%; object-fit:cover;"></div>
                    <div class="flex-col gap-2"><button type="button" class="btn btn-secondary" data-i18n="btn_upload">Téléverser un logo</button><span class="form-help">JPG, PNG ou SVG. Max 2MB.</span></div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" data-i18n="form_brand_color">Couleur de la marque</label>
                <div class="flex-center gap-4">
                    <input type="color" class="form-input" value="#800020" style="width: 60px; height: 40px; padding: 0.25rem;">
                    <input type="text" class="form-input" value="#800020" style="width: 120px;">
                </div>
            </div>
            <div class="form-actions"><button type="button" class="btn btn-secondary" data-i18n="btn_cancel">Annuler</button><button type="submit" class="btn btn-primary" data-i18n="btn_save">Enregistrer</button></div>
        </form>
    </div>
    <div class="card settings-pane hidden" id="settings-departments">
        <div class="card-header card-header--bordered">
            <h2 class="card-title" data-i18n="settings_departments">Départements</h2>
            <span class="subtitle-muted" id="settings-departments-count">0 départements</span>
        </div>
        <div id="settings-departments-list" class="departments-list"></div>
        <div class="departments-add-row mt-4">
            <input type="text" id="dept-new-name" class="form-input" placeholder="Nom du département" onkeydown="if(event.key==='Enter'){addDepartment(); event.preventDefault();}">
            <button class="btn btn-primary" onclick="addDepartment()" style="flex-shrink:0;"><i class="fa-solid fa-plus"></i></button>
        </div>
    </div>
    <div class="card settings-pane hidden" id="settings-team">
        <div class="card-header card-header--bordered">
            <h2 class="card-title" data-i18n="settings_team">Équipe</h2>
            <span class="subtitle-muted" id="settings-team-count">0 utilisateurs</span>
        </div>
        <div id="settings-team-list" class="team-members-list"></div>
        <div class="team-members-add-row mt-4">
            <input type="text" id="team-new-prenom" class="form-input" placeholder="Prénom">
            <input type="text" id="team-new-nom" class="form-input" placeholder="Nom">
            <input type="email" id="team-new-email" class="form-input" placeholder="Courriel">
            <select class="form-select form-select--role" id="team-new-role">
                <option value="evaluateur">Évaluateur</option>
                <option value="administrateur">Administrateur</option>
            </select>
            <button class="btn btn-primary" onclick="addTeamMember()" style="flex-shrink:0;"><i class="fa-solid fa-plus"></i></button>
        </div>
    </div>
    <div class="settings-pane hidden" id="settings-billing">
        <?php require GESTION_VIEWS . '/dashboard/_billing.php'; ?>
    </div>
    <div class="settings-pane hidden" id="settings-communications">
        <?php require GESTION_VIEWS . '/dashboard/_communications.php'; ?>
    </div>
</div>

<!-- MODALS -->
<div class="modal-overlay" id="poste-modal">
    <div class="modal">
        <div class="modal-header"><h2 class="modal-title" data-i18n="modal_add_poste">Nouveau poste</h2><button class="btn-icon" onclick="closeModal('poste')"><i class="fa-solid fa-xmark"></i></button></div>
        <form>
            <?= csrf_field() ?>
            <div class="form-group"><label class="form-label" data-i18n="form_department">Département</label><select class="form-select"><option value="">— Sélectionner —</option><option value="Technologie">Technologie</option><option value="Gestion">Gestion</option><option value="Design">Design</option><option value="Stratégie">Stratégie</option><option value="Marketing">Marketing</option><option value="Ressources humaines">Ressources humaines</option><option value="Finance">Finance</option><option value="Opérations">Opérations</option></select></div>
            <div class="form-group"><label class="form-label" data-i18n="form_title">Titre du poste</label><input type="text" class="form-input" placeholder="Ex: Développeur Frontend"></div>
            <div class="form-group"><label class="form-label" data-i18n="form_location">Lieu</label><input type="text" class="form-input" placeholder="Ex: Montréal, QC"></div>
            <div class="form-group"><label class="form-label" data-i18n="form_status">Statut</label><select class="form-select"><option value="active" data-i18n="status_active">Actif</option><option value="paused" data-i18n="status_paused">Pausé</option><option value="closed" data-i18n="status_closed">Fermé</option></select></div>
            <div class="modal-actions"><button type="button" class="btn btn-secondary" onclick="closeModal('poste')" data-i18n="btn_cancel">Annuler</button><button type="submit" class="btn btn-primary" data-i18n="btn_save">Enregistrer</button></div>
        </form>
    </div>
</div>
<div class="modal-overlay" id="affichage-modal">
    <div class="modal">
        <div class="modal-header"><h2 class="modal-title" data-i18n="modal_add_affichage">Nouvel affichage</h2><button class="btn-icon" onclick="closeModal('affichage')"><i class="fa-solid fa-xmark"></i></button></div>
        <form>
            <?= csrf_field() ?>
            <div class="form-group"><label class="form-label" data-i18n="form_department">Département</label><select class="form-select"><option value="">— Sélectionner —</option><option value="Technologie">Technologie</option><option value="Gestion">Gestion</option><option value="Design">Design</option><option value="Stratégie">Stratégie</option><option value="Marketing">Marketing</option><option value="Ressources humaines">Ressources humaines</option><option value="Finance">Finance</option><option value="Opérations">Opérations</option></select></div>
            <div class="form-group"><label class="form-label" data-i18n="form_poste">Poste</label><select class="form-select"><option>Développeur Frontend</option><option>Chef de projet</option><option>Designer UX/UI</option></select></div>
            <div class="modal-actions"><button type="button" class="btn btn-secondary" onclick="closeModal('affichage')" data-i18n="btn_cancel">Annuler</button><button type="submit" class="btn btn-primary" data-i18n="btn_save">Enregistrer</button></div>
        </form>
    </div>
</div>
<div class="modal-overlay" id="feedback-modal">
    <div class="modal">
        <div class="modal-header"><h2 class="modal-title" data-i18n="modal_feedback_title">Feedback</h2><button class="btn-icon" onclick="closeModal('feedback')"><i class="fa-solid fa-xmark"></i></button></div>
        <form onsubmit="sendFeedback(event)">
            <?= csrf_field() ?>
            <div class="form-group">
                <label class="form-label" data-i18n="label_type">Type de retour</label>
                <div class="flex-center gap-4">
                    <label class="radio-option"><input type="radio" name="feedback_type" value="problem" checked><span data-i18n="option_problem">Signaler un problème</span></label>
                    <label class="radio-option"><input type="radio" name="feedback_type" value="idea"><span data-i18n="option_idea">Soumettre une idée</span></label>
                </div>
            </div>
            <div class="form-group"><label class="form-label" data-i18n="label_message">Votre message</label><textarea class="form-input" rows="4" style="resize: vertical;" data-i18n-placeholder="feedback_placeholder" placeholder="Dites-nous en plus..." required></textarea></div>
            <div class="modal-actions"><button type="button" class="btn btn-secondary" onclick="closeModal('feedback')" data-i18n="btn_cancel">Annuler</button><button type="submit" class="btn btn-primary" data-i18n="btn_send">Envoyer</button></div>
        </form>
    </div>
</div>
<div class="modal-overlay" id="notify-candidats-modal">
    <div class="modal modal--narrow">
        <div class="modal-header"><h2 class="modal-title"><i class="fa-solid fa-envelope"></i> Notifier les candidats</h2><button class="btn-icon" onclick="closeModal('notify-candidats')"><i class="fa-solid fa-xmark"></i></button></div>
        <div class="mb-5"><p class="subtitle-muted mb-2">Sélectionnez les candidats à notifier par courriel.</p></div>
        <div class="mb-5">
            <div class="flex-between mb-3">
                <label class="form-label mb-0 fw-semibold">Candidats à notifier</label>
                <label class="select-all-label"><input type="checkbox" id="notify-select-all" onchange="toggleSelectAllNotify(this)">Tout sélectionner</label>
            </div>
            <div id="notify-candidats-list" class="candidate-list-scroll"></div>
        </div>
        <div class="form-group mb-5">
            <label class="form-label fw-semibold">Message aux candidats</label>
            <div class="flex-center gap-2 mb-3 flex-wrap">
                <button type="button" class="btn btn-secondary btn-sm" onclick="setNotifyMessage('polite')">Refus poli</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="setNotifyMessage('filled')">Poste comblé</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="setNotifyMessage('custom')">Personnalisé</button>
            </div>
            <textarea id="notify-candidats-message" class="form-input w-full" rows="4" style="resize: vertical;" placeholder="Rédigez votre message..."></textarea>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeModal('notify-candidats')">Annuler</button>
            <button type="button" class="btn btn-primary" onclick="confirmNotifyCandidats()"><i class="fa-solid fa-paper-plane"></i> Envoyer</button>
        </div>
    </div>
</div>
<div class="modal-overlay" id="add-candidat-modal">
    <div class="modal modal--narrow">
        <div class="modal-header"><h2 class="modal-title"><i class="fa-solid fa-user-plus"></i> Ajouter un candidat</h2><button class="btn-icon" onclick="closeModal('add-candidat')"><i class="fa-solid fa-xmark"></i></button></div>
        <form onsubmit="submitAddCandidat(event)">
            <?= csrf_field() ?>
            <div class="form-group mb-4"><label class="form-label" for="add-candidat-prenom">Prénom</label><input type="text" id="add-candidat-prenom" class="form-input" required></div>
            <div class="form-group mb-4"><label class="form-label" for="add-candidat-nom">Nom</label><input type="text" id="add-candidat-nom" class="form-input" required></div>
            <div class="form-group mb-4"><label class="form-label" for="add-candidat-email">Courriel</label><input type="email" id="add-candidat-email" class="form-input" required></div>
            <div class="form-group mb-5"><label class="form-label" for="add-candidat-phone">Téléphone</label><input type="tel" id="add-candidat-phone" class="form-input"></div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('add-candidat')">Annuler</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Ajouter</button>
            </div>
        </form>
    </div>
</div>
<div class="modal-overlay" id="completer-profil-modal">
    <div class="modal modal--narrow">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fa-solid fa-clipboard-check"></i> Compléter votre profil</h2>
            <button class="btn-icon" onclick="closeModal('completer-profil')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="completer-profil-list">
            <a href="#parametres" class="completer-profil-item" onclick="closeModal('completer-profil')">
                <span class="completer-profil-num">1</span>
                <div><strong>Détail de votre organisation</strong><span class="subtitle-muted">Paramètres de l'entreprise</span></div>
                <i class="fa-solid fa-chevron-right"></i>
            </a>
            <a href="#postes" class="completer-profil-item" onclick="closeModal('completer-profil')">
                <span class="completer-profil-num">2</span>
                <div><strong>Créer un poste</strong><span class="subtitle-muted">Définir vos postes à pourvoir</span></div>
                <i class="fa-solid fa-chevron-right"></i>
            </a>
            <a href="#affichages" class="completer-profil-item" onclick="closeModal('completer-profil')">
                <span class="completer-profil-num">3</span>
                <div><strong>Créer un affichage</strong><span class="subtitle-muted">Publier votre poste</span></div>
                <i class="fa-solid fa-chevron-right"></i>
            </a>
        </div>
    </div>
</div>
