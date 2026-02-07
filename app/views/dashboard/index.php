<!-- ═══════════════════════════════════════════════════════════════════════
     VUE : Tableau de bord employeur
     Toutes les sections (SPA côté client, affichées/masquées via JS).
     Les données viennent de APP_DATA (injecté par le layout).
     ═══════════════════════════════════════════════════════════════════════ -->

<!-- ─── POSTES Section ─── -->
<div id="postes-section" class="content-section">
    <div class="page-header">
        <h1 class="page-title" data-i18n="postes_title">Postes actifs</h1>
    </div>
    <div class="filters-bar">
        <div class="view-tabs">
            <button class="view-tab active" data-i18n="filter_all">Tous</button>
            <button class="view-tab" data-i18n="filter_active">Actifs</button>
            <button class="view-tab" data-i18n="filter_paused">Pausés</button>
            <button class="view-tab" data-i18n="filter_closed">Fermés</button>
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
                <th data-i18n="th_actions">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($postes as $p): ?>
            <tr onclick="showPosteDetail('<?= e($p['id']) ?>')" class="row-clickable">
                <td><strong><?= e($p['title']) ?></strong></td>
                <td><?= e($p['department']) ?></td>
                <td><?= e($p['location']) ?></td>
                <td><span class="status-badge <?= e($p['statusClass']) ?>"><?= e($p['status']) ?></span></td>
                <td><?= $p['candidates'] ?></td>
                <td><?= e($p['date']) ?></td>
                <td onclick="event.stopPropagation()">
                    <button class="btn-icon" title="Modifier"><i class="fa-solid fa-pen"></i></button>
                    <button class="btn-icon" title="Voir"><i class="fa-solid fa-eye"></i></button>
                    <button class="btn-icon" title="Supprimer"><i class="fa-solid fa-trash"></i></button>
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
        <span class="status-badge ml-auto" id="detail-poste-status">Statut</span>
    </div>
    <div class="grid-detail">
        <div>
            <div class="card mb-8">
                <h3 class="section-heading">Description</h3>
                <div class="text-body" id="detail-poste-description">...</div>
            </div>
            <div class="card">
                <h3 class="section-heading">Questions de présélection</h3>
                <ul class="detail-list" id="detail-poste-questions"></ul>
            </div>
        </div>
        <div>
            <div class="card">
                <h3 class="card-subtitle">Informations</h3>
                <div class="info-row"><div class="info-label">Candidats</div><div class="info-value" id="detail-poste-candidates">0</div></div>
                <div class="info-row"><div class="info-label">Créé le</div><div id="detail-poste-date">2026-01-01</div></div>
                <div class="action-stack">
                    <button class="btn btn-primary btn--center">Modifier le poste</button>
                    <button class="btn btn-secondary btn--center">Voir les candidats</button>
                </div>
            </div>
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
        <span class="status-badge ml-auto" id="affichage-candidats-status">Statut</span>
        <button class="btn btn-danger-light ml-4" onclick="openCloseAffichageModal()">
            <i class="fa-solid fa-xmark"></i>
            <span>Fermer l'affichage</span>
        </button>
    </div>
    <div class="filters-bar">
        <div class="view-tabs">
            <button class="view-tab active">Tous</button>
            <button class="view-tab">Nouveaux</button>
            <button class="view-tab">Évalués</button>
            <button class="view-tab">Favoris</button>
        </div>
    </div>
    <div class="search-row">
        <div class="search-bar search-bar--full"><i class="fa-solid fa-magnifying-glass"></i><input type="text" placeholder="Rechercher..."></div>
    </div>
    <table class="data-table">
        <thead><tr><th>Candidat</th><th>Statut</th><th>Vidéo</th><th>Note</th><th>Postulé le</th><th>Actions</th></tr></thead>
        <tbody id="affichage-candidats-tbody"></tbody>
    </table>
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
                <th data-i18n="th_poste">Poste</th><th data-i18n="th_platform">Plateforme</th>
                <th data-i18n="th_start_date">Date début</th><th data-i18n="th_end_date">Date fin</th>
                <th data-i18n="th_status">Statut</th><th data-i18n="th_views">Vues</th>
                <th data-i18n="th_applications">Candidatures</th><th data-i18n="th_actions">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($affichages as $aId => $a): ?>
            <tr onclick="showAffichageDetail('<?= e($aId) ?>')" class="row-clickable">
                <td><strong><?= e($a['title']) ?></strong></td>
                <td><?php if ($a['platform'] === 'LinkedIn'): ?><i class="fa-brands fa-linkedin platform-icon--linkedin"></i><?php else: ?><i class="fa-solid fa-globe platform-icon--default"></i><?php endif; ?> <?= e($a['platform']) ?></td>
                <td><?= e($a['start']) ?></td>
                <td><?= e($a['end']) ?></td>
                <td><span class="status-badge <?= e($a['statusClass']) ?>"><?= e($a['status']) ?></span></td>
                <td><?= e($a['views']) ?></td>
                <td><?= e($a['apps']) ?></td>
                <td onclick="event.stopPropagation()">
                    <button class="btn-icon" title="Modifier"><i class="fa-solid fa-pen"></i></button>
                    <button class="btn-icon" title="Voir"><i class="fa-solid fa-eye"></i></button>
                    <button class="btn-icon" title="Supprimer"><i class="fa-solid fa-trash"></i></button>
                </td>
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
            <button class="view-tab" data-i18n="filter_shortlisted">Favoris</button>
        </div>
        <div><select class="form-select form-select--auto"><option data-i18n="filter_all_jobs">Tous les postes</option><option>Développeur Frontend</option><option>Chef de projet</option><option>Designer UX/UI</option></select></div>
    </div>
    <div class="search-row">
        <div class="search-bar search-bar--full"><i class="fa-solid fa-magnifying-glass"></i><input type="text" placeholder="Rechercher..." data-i18n-placeholder="search"></div>
    </div>
    <table class="data-table" id="candidats-table">
        <thead><tr><th data-i18n="th_candidate">Candidat</th><th data-i18n="th_poste">Poste</th><th data-i18n="th_status">Statut</th><th data-i18n="th_video">Vidéo</th><th data-i18n="th_rating">Note</th><th data-i18n="th_applied">Postulé le</th><th data-i18n="th_actions">Actions</th></tr></thead>
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
                <td><i class="fa-solid fa-circle-check icon-video-ok"></i></td>
                <td><div class="star-color"><?php for ($i = 1; $i <= 5; $i++): ?><i class="fa-<?= $i <= $c['rating'] ? 'solid' : 'regular' ?> fa-star"></i><?php endfor; ?></div></td>
                <td>2026-02-01</td>
                <td>
                    <button class="btn-icon" title="Voir vidéo" onclick="event.stopPropagation()"><i class="fa-solid fa-play"></i></button>
                    <button class="btn-icon" title="Profil" onclick="event.stopPropagation()"><i class="fa-solid fa-user"></i></button>
                    <button class="btn-icon" title="Mettre en favoris" onclick="event.stopPropagation()"><i class="fa-<?= $c['isFavorite'] ? 'solid' : 'regular' ?> fa-star <?= $c['isFavorite'] ? 'star-color' : '' ?>"></i></button>
                </td>
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
        <div class="action-group">
            <button class="favorite-btn" id="detail-candidate-favorite" onclick="toggleFavorite()"><i class="fa-regular fa-star"></i></button>
            <select class="status-select" id="detail-candidate-status-select" onchange="updateCandidateStatus(this.value)">
                <option value="new">Nouveau</option><option value="reviewed">Évalué</option><option value="shortlisted">Favori</option>
                <option value="interview">Entretien</option><option value="rejected">Refusé</option><option value="hired">Embauché</option>
            </select>
        </div>
    </div>
    <div class="modal-split-view mt-6">
        <div class="modal-left">
            <div class="video-container">
                <video controls class="hidden" id="detail-candidate-video-player"><source src="" type="video/mp4"></video>
                <div id="detail-video-placeholder" class="text-center">
                    <i class="fa-solid fa-play-circle icon-xl"></i>
                    <div class="mt-2" data-i18n="video_preview">Aperçu vidéo</div>
                </div>
            </div>
            <div class="card contact-card">
                <h3 class="contact-heading"><i class="fa-regular fa-envelope"></i> Email</h3>
                <p id="detail-candidate-email" class="text-body mb-4">email@example.com</p>
                <h3 class="contact-heading"><i class="fa-solid fa-phone"></i> <span data-i18n="form_phone">Téléphone</span></h3>
                <p id="detail-candidate-phone" class="text-body">+1 514 555-0199</p>
            </div>
        </div>
        <div class="modal-right">
            <h3 class="section-heading-sm" data-i18n="th_rating">Note</h3>
            <div class="star-rating-input" id="detail-star-rating">
                <?php for ($i = 1; $i <= 5; $i++): ?><i class="fa-solid fa-star" data-value="<?= $i ?>" onclick="setRating(<?= $i ?>)"></i><?php endfor; ?>
            </div>
            <h3 class="section-heading-sm" data-i18n="comments_title">Commentaires</h3>
            <div class="timeline-container" id="detail-timeline-list"></div>
            <div class="flex-center gap-2">
                <textarea class="form-input" id="detail-new-comment-input" rows="1" placeholder="Ajouter une note..." style="resize: none;" data-i18n-placeholder="add_note_placeholder"></textarea>
                <button class="btn btn-primary" onclick="addComment()"><i class="fa-solid fa-paper-plane"></i></button>
            </div>
        </div>
    </div>
</div>

<!-- ─── STATISTIQUES / TABLEAU DE BORD Section ─── -->
<div id="statistiques-section" class="content-section active">
    <div class="page-header"><h1 class="page-title" data-i18n="statistiques_title">Tableau de bord</h1></div>

    <!-- Forfait Banner -->
    <div class="forfait-banner">
        <div class="flex-center gap-5">
            <div class="forfait-icon"><i class="fa-solid fa-gem icon-lg"></i></div>
            <div><div class="plan-name">Forfait Platine</div></div>
        </div>
        <a href="#" onclick="document.querySelector('a[data-section=\'parametres\']').click(); setTimeout(function(){ document.querySelector('.settings-nav-item[data-target=\'settings-billing\']').click(); }, 50); return false;" class="forfait-cta">Gérer mon forfait</a>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="flex-between-start">
                <div>
                    <div class="kpi-label">Mon forfait</div>
                    <div class="kpi-value">20 <span class="kpi-suffix">/ 50</span></div>
                    <div class="kpi-sub">candidats disponibles</div>
                </div>
                <div class="kpi-icon kpi-icon--blue"><i class="fa-solid fa-users"></i></div>
            </div>
            <div class="progress-bar"><div class="progress-fill" style="width: 40%;"></div></div>
        </div>
        <div class="kpi-card">
            <div class="flex-between-start">
                <div>
                    <div class="kpi-label" data-i18n="stat_active_jobs">Postes actifs</div>
                    <div class="kpi-value">3</div>
                </div>
                <div class="kpi-icon kpi-icon--gray"><i class="fa-solid fa-briefcase"></i></div>
            </div>
            <div class="kpi-trend"><span class="kpi-trend--up"><i class="fa-solid fa-arrow-up"></i> 1</span> depuis le mois dernier</div>
        </div>
        <div class="kpi-card">
            <div class="flex-between-start">
                <div>
                    <div class="kpi-label">Candidats en attente</div>
                    <div class="kpi-value">7</div>
                </div>
                <div class="kpi-icon kpi-icon--yellow"><i class="fa-solid fa-clock"></i></div>
            </div>
            <div class="kpi-trend kpi-trend--warning"><i class="fa-solid fa-circle-exclamation"></i> En attente de réponse</div>
        </div>
    </div>

    <!-- Graphique : Candidatures par mois -->
    <div class="chart-card">
        <div class="flex-center gap-3 mb-6">
            <div class="chart-icon"><i class="fa-solid fa-chart-bar"></i></div>
            <h2 class="section-heading mb-0" data-i18n="chart_applications">Candidatures par mois</h2>
        </div>
        <div class="chart-bars">
            <?php $months = [['Sep', 60], ['Oct', 100], ['Nov', 80], ['Déc', 140], ['Jan', 180], ['Fév', 120]]; ?>
            <?php foreach ($months as $i => $m): ?>
            <div class="chart-bar-col">
                <div class="chart-bar <?= $i === 4 ? 'chart-bar--highlight' : '' ?>" style="height: <?= $m[1] ?>px;"></div>
                <span class="chart-bar-label <?= $i === 4 ? 'chart-bar-label--highlight' : '' ?>"><?= $m[0] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Journalisation -->
    <div class="card">
        <div class="card-header"><h2 class="card-title">Journalisation des événements</h2></div>
        <table class="data-table">
            <thead><tr><th>Date</th><th>Utilisateur</th><th>Action</th><th>Détails</th></tr></thead>
            <tbody>
                <tr><td class="cell-date">6 fév 2026, 14:32</td><td><strong>Marie Tremblay</strong></td><td><span class="event-badge event-badge--evaluation">Évaluation</span></td><td class="cell-muted">A noté le candidat <strong>Jean Dupont</strong> — 4/5 étoiles</td></tr>
                <tr><td class="cell-date">6 fév 2026, 11:15</td><td><strong>Pierre Roy</strong></td><td><span class="event-badge event-badge--creation">Création</span></td><td class="cell-muted">A créé un nouvel affichage pour <strong>Développeur Frontend</strong></td></tr>
                <tr><td class="cell-date">5 fév 2026, 16:48</td><td><strong>Marie Tremblay</strong></td><td><span class="event-badge event-badge--modification">Modification</span></td><td class="cell-muted">A modifié les questions du poste <strong>Chef de projet</strong></td></tr>
                <tr><td class="cell-date">5 fév 2026, 09:22</td><td><strong>Pierre Roy</strong></td><td><span class="event-badge event-badge--invitation">Invitation</span></td><td class="cell-muted">A invité <strong>Sophie Martin</strong> à une entrevue vidéo</td></tr>
                <tr><td class="cell-date">4 fév 2026, 15:05</td><td><strong>Marie Tremblay</strong></td><td><span class="event-badge event-badge--suppression">Suppression</span></td><td class="cell-muted">A archivé le poste <strong>Analyste d'affaires</strong></td></tr>
                <tr><td class="cell-date">4 fév 2026, 10:30</td><td><strong>Pierre Roy</strong></td><td><span class="event-badge event-badge--evaluation">Évaluation</span></td><td class="cell-muted">A visionné la vidéo de <strong>Luc Bergeron</strong></td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ─── PARAMÈTRES Section ─── -->
<div id="parametres-section" class="content-section">
    <div class="page-header"><h1 class="page-title" data-i18n="parametres_title">Paramètres</h1></div>

    <!-- Onglets horizontaux -->
    <div class="settings-tabs">
        <a href="#" class="settings-nav-item active" data-target="settings-company"><i class="fa-regular fa-building"></i><span data-i18n="settings_company">Entreprise</span></a>
        <a href="#" class="settings-nav-item" data-target="settings-branding"><i class="fa-solid fa-palette"></i><span data-i18n="settings_branding">Marque employeur</span></a>
        <a href="#" class="settings-nav-item" data-target="settings-team"><i class="fa-solid fa-users"></i><span data-i18n="settings_team">Équipe</span></a>
        <a href="#" class="settings-nav-item" data-target="settings-notifications"><i class="fa-solid fa-bell"></i><span data-i18n="settings_notifications">Notifications</span></a>
        <a href="#" class="settings-nav-item" data-target="settings-billing"><i class="fa-solid fa-credit-card"></i><span data-i18n="settings_billing">Forfaits</span></a>
        <a href="#" class="settings-nav-item" data-target="settings-communications"><i class="fa-solid fa-envelope"></i><span>Communication</span></a>
        <a href="#" class="settings-nav-item" data-target="settings-integrations"><i class="fa-solid fa-plug"></i><span data-i18n="settings_integrations">Intégrations</span></a>
    </div>

    <!-- Entreprise -->
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

    <!-- Marque employeur -->
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
                    <input type="color" class="form-input" value="#3B82F6" style="width: 60px; height: 40px; padding: 0.25rem;">
                    <input type="text" class="form-input" value="#3B82F6" style="width: 120px;">
                </div>
            </div>
            <div class="form-actions"><button type="button" class="btn btn-secondary" data-i18n="btn_cancel">Annuler</button><button type="submit" class="btn btn-primary" data-i18n="btn_save">Enregistrer</button></div>
        </form>
    </div>

    <!-- Placeholders -->
    <div class="card settings-pane hidden" id="settings-team"><h2 class="card-title" data-i18n="settings_team">Équipe</h2><p>Gestion de l'équipe...</p></div>
    <div class="card settings-pane hidden" id="settings-notifications"><h2 class="card-title" data-i18n="settings_notifications">Notifications</h2><p>Préférences de notifications...</p></div>

    <!-- Forfaits -->
    <div class="settings-pane hidden" id="settings-billing">
        <?php require VIEWS_PATH . '/dashboard/_billing.php'; ?>
    </div>

    <!-- Communication -->
    <div class="settings-pane hidden" id="settings-communications">
        <?php require VIEWS_PATH . '/dashboard/_communications.php'; ?>
    </div>

    <div class="card settings-pane hidden" id="settings-integrations"><h2 class="card-title" data-i18n="settings_integrations">Intégrations</h2><p>Connectez vos outils...</p></div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     MODALS
     ═══════════════════════════════════════════════════════════════════════ -->

<!-- Modal Poste -->
<div class="modal-overlay" id="poste-modal">
    <div class="modal">
        <div class="modal-header"><h2 class="modal-title" data-i18n="modal_add_poste">Nouveau poste</h2><button class="btn-icon" onclick="closeModal('poste')"><i class="fa-solid fa-xmark"></i></button></div>
        <form>
            <?= csrf_field() ?>
            <div class="form-group"><label class="form-label" data-i18n="form_title">Titre du poste</label><input type="text" class="form-input" placeholder="Ex: Développeur Frontend"></div>
            <div class="form-group"><label class="form-label" data-i18n="form_department">Département</label><input type="text" class="form-input" placeholder="Ex: Technologie"></div>
            <div class="form-group"><label class="form-label" data-i18n="form_location">Lieu</label><input type="text" class="form-input" placeholder="Ex: Montréal, QC"></div>
            <div class="form-group"><label class="form-label" data-i18n="form_status">Statut</label><select class="form-select"><option value="active" data-i18n="status_active">Actif</option><option value="paused" data-i18n="status_paused">Pausé</option><option value="closed" data-i18n="status_closed">Fermé</option></select></div>
            <div class="modal-actions"><button type="button" class="btn btn-secondary" onclick="closeModal('poste')" data-i18n="btn_cancel">Annuler</button><button type="submit" class="btn btn-primary" data-i18n="btn_save">Enregistrer</button></div>
        </form>
    </div>
</div>

<!-- Modal Affichage -->
<div class="modal-overlay" id="affichage-modal">
    <div class="modal">
        <div class="modal-header"><h2 class="modal-title" data-i18n="modal_add_affichage">Nouvel affichage</h2><button class="btn-icon" onclick="closeModal('affichage')"><i class="fa-solid fa-xmark"></i></button></div>
        <form>
            <?= csrf_field() ?>
            <div class="form-group"><label class="form-label" data-i18n="form_poste">Poste</label><select class="form-select"><option>Développeur Frontend</option><option>Chef de projet</option><option>Designer UX/UI</option></select></div>
            <div class="form-group"><label class="form-label" data-i18n="form_platform">Plateforme</label><select class="form-select"><option>LinkedIn</option><option>Indeed</option><option>Site carrière</option><option>Autre</option></select></div>
            <div class="form-group"><label class="form-label" data-i18n="form_start_date">Date de début</label><input type="date" class="form-input"></div>
            <div class="form-group"><label class="form-label" data-i18n="form_end_date">Date de fin</label><input type="date" class="form-input"></div>
            <div class="modal-actions"><button type="button" class="btn btn-secondary" onclick="closeModal('affichage')" data-i18n="btn_cancel">Annuler</button><button type="submit" class="btn btn-primary" data-i18n="btn_save">Enregistrer</button></div>
        </form>
    </div>
</div>

<!-- Modal Feedback -->
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

<!-- Modal Fermer l'affichage -->
<div class="modal-overlay" id="close-affichage-modal">
    <div class="modal modal--narrow">
        <div class="modal-header"><h2 class="modal-title">Fermer l'affichage</h2><button class="btn-icon" onclick="closeModal('close-affichage')"><i class="fa-solid fa-xmark"></i></button></div>
        <div class="mb-5">
            <p class="subtitle-muted mb-2">Sélectionnez les candidats à notifier par courriel.</p>
            <div class="alert-warning"><i class="fa-solid fa-triangle-exclamation"></i> Cette action est irréversible.</div>
        </div>
        <div class="mb-5">
            <div class="flex-between mb-3">
                <label class="form-label mb-0 fw-semibold">Candidats à notifier</label>
                <label class="select-all-label">
                    <input type="checkbox" id="close-select-all" onchange="toggleSelectAllClose(this)">Tout sélectionner
                </label>
            </div>
            <div id="close-affichage-candidates" class="candidate-list-scroll"></div>
        </div>
        <div class="form-group mb-5">
            <label class="form-label fw-semibold">Message aux candidats</label>
            <div class="flex-center gap-2 mb-3 flex-wrap">
                <button type="button" class="btn btn-secondary btn-sm" onclick="setCloseMessage('polite')">Refus poli</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="setCloseMessage('filled')">Poste comblé</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="setCloseMessage('custom')">Personnalisé</button>
            </div>
            <textarea id="close-affichage-message" class="form-input w-full" rows="4" style="resize: vertical;" placeholder="Rédigez votre message..."></textarea>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeModal('close-affichage')">Annuler</button>
            <button type="button" class="btn btn-danger" onclick="confirmCloseAffichage()"><i class="fa-solid fa-xmark"></i> Fermer l'affichage</button>
        </div>
    </div>
</div>
