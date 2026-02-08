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
    <?php
    $kpiUsers = $kpiUsers ?? 0;
    $kpiVideos = $kpiVideos ?? 0;
    $kpiSalesCents = $kpiSalesCents ?? 0;
    $kpiSalesFormatted = number_format($kpiSalesCents / 100, 2, ',', ' ');
    ?>
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="flex-between-start">
                <div>
                    <div class="kpi-label" data-i18n="dashboard_kpi_users">Nombre d'utilisateurs</div>
                    <div class="kpi-value"><?= e((string) $kpiUsers) ?></div>
                </div>
                <div class="kpi-icon kpi-icon--blue"><i class="fa-solid fa-users"></i></div>
            </div>
            <div class="kpi-trend"><span class="kpi-trend--up"><i class="fa-solid fa-arrow-up"></i></span> <span data-i18n="dashboard_this_month">ce mois</span></div>
        </div>
        <div class="kpi-card">
            <div class="flex-between-start">
                <div>
                    <div class="kpi-label" data-i18n="dashboard_kpi_videos">Nombre de vidéos sous gestion</div>
                    <div class="kpi-value"><?= e((string) $kpiVideos) ?></div>
                </div>
                <div class="kpi-icon kpi-icon--gray"><i class="fa-solid fa-video"></i></div>
            </div>
            <div class="kpi-trend"><span data-i18n="dashboard_this_month">ce mois</span></div>
        </div>
        <div class="kpi-card">
            <div class="flex-between-start">
                <div>
                    <div class="kpi-label" data-i18n="dashboard_kpi_sales">Ventes du mois</div>
                    <div class="kpi-value"><?= e($kpiSalesFormatted) ?> $</div>
                </div>
                <div class="kpi-icon kpi-icon--red"><i class="fa-solid fa-credit-card"></i></div>
            </div>
            <div class="kpi-trend"><span data-i18n="dashboard_vs_prev_month">vs mois précédent</span></div>
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
                <?php
                $events = $events ?? [];
                $eventBadgeMap = ['creation' => 'event-badge--creation', 'modification' => 'event-badge--modification', 'suppression' => 'event-badge--suppression', 'sale' => 'event-badge--evaluation'];
                if (empty($events)): ?>
                <tr><td colspan="4" class="cell-muted">Aucun événement enregistré.</td></tr>
                <?php else:
                $moisFr = ['Jan'=>'janv','Feb'=>'fév','Mar'=>'mars','Apr'=>'avr','May'=>'mai','Jun'=>'juin','Jul'=>'juil','Aug'=>'août','Sep'=>'sept','Oct'=>'oct','Nov'=>'nov','Dec'=>'déc'];
                foreach ($events as $ev):
                    $badgeClass = $eventBadgeMap[$ev['action_type']] ?? 'event-badge--modification';
                    $d = date('j M Y, H:i', strtotime($ev['created_at']));
                    $createdFormatted = preg_replace_callback('/\b(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\b/', function ($m) use ($moisFr) { return $moisFr[$m[1]] ?? $m[1]; }, $d);
                ?>
                <tr>
                    <td class="cell-date"><?= e($createdFormatted) ?></td>
                    <td><strong><?= e($ev['admin_name']) ?></strong></td>
                    <td><span class="event-badge <?= e($badgeClass) ?>"><?= e(ucfirst($ev['action_type'])) ?></span></td>
                    <td class="cell-muted"><?= e($ev['details']) ?></td>
                </tr>
                <?php endforeach; endif; ?>
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
                <?php
                $sales = $sales ?? [];
                if (empty($sales)): ?>
                <tr><td colspan="5" class="cell-muted">Aucune vente enregistrée.</td></tr>
                <?php else:
                $moisFr = ['Jan'=>'janv','Feb'=>'fév','Mar'=>'mars','Apr'=>'avr','May'=>'mai','Jun'=>'juin','Jul'=>'juil','Aug'=>'août','Sep'=>'sept','Oct'=>'oct','Nov'=>'nov','Dec'=>'déc'];
                foreach ($sales as $sale):
                    $amountFormatted = number_format($sale['amount_cents'] / 100, 2, ',', ' ');
                    $d = date('j M Y', strtotime($sale['created_at']));
                    $dateFormatted = preg_replace_callback('/\b(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\b/', function ($m) use ($moisFr) { return $moisFr[$m[1]] ?? $m[1]; }, $d);
                ?>
                <tr>
                    <td><?= e($sale['stripe_payment_id']) ?></td>
                    <td><?= e($dateFormatted) ?></td>
                    <td><?= e($sale['customer_email']) ?></td>
                    <td><?= e($amountFormatted) ?> $</td>
                    <td><span class="status-badge status-active"><?= e($sale['status']) ?></span></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ─── FORFAITS CRUD Section ─── -->
<div id="forfaits-crud-section" class="content-section">
    <div class="page-header">
        <h1 class="page-title" data-i18n="nav_forfaits">Forfaits</h1>
        <button type="button" class="btn btn-primary" onclick="openModal('forfait-add')"><i class="fa-solid fa-plus"></i> <span data-i18n="btn_add">Ajouter</span></button>
    </div>
    <div class="card">
        <table class="data-table data-table-forfaits">
            <thead><tr><th data-i18n="th_name">Nom</th><th data-i18n="th_status">Statut</th><th class="forfait-col-detail" data-i18n="th_video_limit">Limite vidéos</th><th class="forfait-col-detail" data-i18n="th_price_monthly">Prix mensuel</th><th class="forfait-col-detail" data-i18n="th_price_yearly">Prix annuel</th><th data-i18n="th_actions">Actions</th></tr></thead>
            <tbody>
                <?php
                $plans = $plans ?? [];
                if (empty($plans)): ?>
                <tr><td colspan="6" class="cell-muted">Aucun forfait configuré.</td></tr>
                <?php else:
                foreach ($plans as $p):
                    $priceMonthly = number_format($p['price_monthly'], 2, ',', ' ');
                    $priceYearly = number_format($p['price_yearly'], 2, ',', ' ');
                    $isActive = $p['active'] ?? true;
                ?>
                <tr data-plan-id="<?= (int) $p['id'] ?>" data-name-fr="<?= e($p['name_fr']) ?>" data-name-en="<?= e($p['name_en']) ?>" data-video-limit="<?= (int) $p['video_limit'] ?>" data-price-monthly="<?= e((string) $p['price_monthly']) ?>" data-price-yearly="<?= e((string) $p['price_yearly']) ?>" data-active="<?= $isActive ? '1' : '0' ?>" <?= !$isActive ? 'style="opacity:0.7;"' : '' ?>>
                    <td><?= e($p['name']) ?></td>
                    <td>
                        <?php if ($isActive): ?>
                        <span class="status-badge status-active" data-i18n="status_active">Actif</span>
                        <?php else: ?>
                        <span class="status-badge" data-i18n="status_inactive">Désactivé</span>
                        <?php endif; ?>
                    </td>
                    <td class="forfait-col-detail"><?= e((string) $p['video_limit']) ?></td>
                    <td class="forfait-col-detail"><?= e($priceMonthly) ?> $</td>
                    <td class="forfait-col-detail"><?= e($priceYearly) ?> $</td>
                    <td>
                        <button type="button" class="btn-icon forfait-edit-btn" data-i18n-title="action_edit" title="Modifier"><i class="fa-solid fa-pen"></i></button>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="forfait-edit-modal">
    <div class="modal modal--narrow" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 class="modal-title" data-i18n="forfait_edit_title">Modifier le forfait</h2>
            <button class="btn-icon" onclick="closeModal('forfait-edit')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="<?= GESTION_BASE_PATH ?>/forfaits/modifier" id="forfait-edit-form">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="forfait-edit-id">
            <div class="form-group mb-4">
                <label class="form-label" for="forfait-edit-status" data-i18n="th_status">Statut</label>
                <select id="forfait-edit-status" name="active" class="form-select">
                    <option value="1" data-i18n="status_active">Actif</option>
                    <option value="0" data-i18n="status_inactive">Désactivé</option>
                </select>
            </div>
            <div class="form-group mb-4">
                <label class="form-label" for="forfait-edit-name-fr" data-i18n="forfait_name_fr">Nom (français)</label>
                <input type="text" id="forfait-edit-name-fr" name="name_fr" class="form-input" required>
            </div>
            <div class="form-group mb-4">
                <label class="form-label" for="forfait-edit-name-en" data-i18n="forfait_name_en">Nom (anglais)</label>
                <input type="text" id="forfait-edit-name-en" name="name_en" class="form-input" required>
            </div>
            <div class="form-group mb-4">
                <label class="form-label" for="forfait-edit-video-limit" data-i18n="th_video_limit">Limite vidéos</label>
                <input type="number" id="forfait-edit-video-limit" name="video_limit" class="form-input" required min="1">
            </div>
            <div class="form-group mb-4">
                <label class="form-label" for="forfait-edit-price-monthly" data-i18n="th_price_monthly">Prix mensuel ($)</label>
                <input type="number" id="forfait-edit-price-monthly" name="price_monthly" class="form-input" required min="0" step="0.01">
            </div>
            <div class="form-group mb-5">
                <label class="form-label" for="forfait-edit-price-yearly" data-i18n="th_price_yearly">Prix annuel ($)</label>
                <input type="number" id="forfait-edit-price-yearly" name="price_yearly" class="form-input" required min="0" step="0.01">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('forfait-edit')" data-i18n="btn_cancel">Annuler</button>
                <button type="submit" class="btn btn-primary" data-i18n="btn_save">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="forfait-add-modal">
    <div class="modal modal--narrow">
        <div class="modal-header">
            <h2 class="modal-title" data-i18n="forfait_add_title">Ajouter un forfait</h2>
            <button class="btn-icon" onclick="closeModal('forfait-add')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="<?= GESTION_BASE_PATH ?>/forfaits/ajouter" id="forfait-add-form">
            <?= csrf_field() ?>
            <div class="form-group mb-4">
                <label class="form-label" for="forfait-name-fr" data-i18n="forfait_name_fr">Nom (français)</label>
                <input type="text" id="forfait-name-fr" name="name_fr" class="form-input" required placeholder="Ex: Starter">
            </div>
            <div class="form-group mb-4">
                <label class="form-label" for="forfait-name-en" data-i18n="forfait_name_en">Nom (anglais)</label>
                <input type="text" id="forfait-name-en" name="name_en" class="form-input" required placeholder="Ex: Starter">
            </div>
            <div class="form-group mb-4">
                <label class="form-label" for="forfait-video-limit" data-i18n="th_video_limit">Limite vidéos</label>
                <input type="number" id="forfait-video-limit" name="video_limit" class="form-input" required min="1" value="10">
            </div>
            <div class="form-group mb-4">
                <label class="form-label" for="forfait-price-monthly" data-i18n="th_price_monthly">Prix mensuel ($)</label>
                <input type="number" id="forfait-price-monthly" name="price_monthly" class="form-input" required min="0" step="0.01" value="0">
            </div>
            <div class="form-group mb-5">
                <label class="form-label" for="forfait-price-yearly" data-i18n="th_price_yearly">Prix annuel ($)</label>
                <input type="number" id="forfait-price-yearly" name="price_yearly" class="form-input" required min="0" step="0.01" value="0">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('forfait-add')" data-i18n="btn_cancel">Annuler</button>
                <button type="submit" class="btn btn-primary" data-i18n="btn_save">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<!-- ─── UTILISATEURS LISTE Section ─── -->
<div id="utilisateurs-liste-section" class="content-section">
    <div class="page-header">
        <h1 class="page-title" data-i18n="nav_utilisateurs">Utilisateurs</h1>
        <button type="button" class="btn btn-primary" onclick="openModal('utilisateur-add')"><i class="fa-solid fa-plus"></i> <span data-i18n="btn_new_user">Nouvel utilisateur</span></button>
    </div>
    <div class="search-row">
        <div class="search-bar search-bar--full"><i class="fa-solid fa-magnifying-glass"></i><input type="text" data-i18n-placeholder="search" placeholder="Rechercher..."></div>
    </div>
    <table class="data-table">
        <thead><tr><th data-i18n="th_prenom">Prénom</th><th data-i18n="th_nom">Nom</th><th>Email</th><th class="text-center" data-i18n="th_role">Rôle</th><th class="text-center" data-i18n="th_status">Statut</th><th data-i18n="th_actions">Actions</th></tr></thead>
        <tbody>
            <?php
            $platformUsers = $platformUsers ?? [];
            if (empty($platformUsers)): ?>
            <tr><td colspan="6" class="cell-muted">Aucun utilisateur.</td></tr>
            <?php else:
            foreach ($platformUsers as $u):
                $roleBadgeClass = strtolower($u['role']) === 'admin' ? 'status-active' : '';
                $roleKey = 'role_' . strtolower($u['role']);
            ?>
            <tr>
                <td><?= e($u['prenom'] ?? '') ?></td>
                <td class="cell-nom"><?= e($u['nom'] ?? $u['name'] ?? '') ?></td>
                <td><?= e($u['email']) ?></td>
                <td class="text-center"><span class="status-badge <?= e($roleBadgeClass) ?>" data-i18n="<?= e($roleKey) ?>"><?= e($u['role']) ?></span></td>
                <td class="text-center"><span class="status-badge <?= !empty($u['active']) ? 'status-active' : 'status-paused' ?>" data-i18n="<?= !empty($u['active']) ? 'status_active' : 'status_inactive' ?>"><?= !empty($u['active']) ? 'Actif' : 'Désactivé' ?></span></td>
                <td>
                    <button type="button" class="btn-icon utilisateur-edit-btn" data-i18n-title="action_edit" title="Modifier"
                        data-user-id="<?= (int) $u['id'] ?>"
                        data-user-prenom="<?= e($u['prenom'] ?? '') ?>"
                        data-user-nom="<?= e($u['nom'] ?? $u['name'] ?? '') ?>"
                        data-user-email="<?= e($u['email']) ?>"
                        data-user-role="<?= e($u['role']) ?>"
                        data-user-plan-id="<?= (int) ($u['plan_id'] ?? 0) ?>"
                        data-user-billable="<?= !empty($u['billable']) ? '1' : '0' ?>"
                        data-user-active="<?= !empty($u['active']) ? '1' : '0' ?>"><i class="fa-solid fa-pen"></i></button>
                    <form method="POST" action="<?= GESTION_BASE_PATH ?>/utilisateurs/supprimer" class="d-inline utilisateur-delete-form" data-user-name="<?= e(trim(($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? $u['name'] ?? '')) ?: $u['email']) ?>" data-user-email="<?= e($u['email']) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                        <button type="button" class="btn-icon btn-icon--danger utilisateur-delete-btn" data-i18n-title="action_delete" title="Supprimer"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Nouvel utilisateur -->
<div class="modal-overlay" id="utilisateur-add-modal">
    <div class="modal modal--narrow">
        <div class="modal-header">
            <h2 class="modal-title" data-i18n="utilisateur_add_title">Nouvel utilisateur</h2>
            <button class="btn-icon" onclick="closeModal('utilisateur-add')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="<?= GESTION_BASE_PATH ?>/utilisateurs/ajouter" id="utilisateur-add-form">
            <?= csrf_field() ?>
            <div class="form-group mb-4">
                <label class="form-label" for="utilisateur-add-prenom" data-i18n="th_prenom">Prénom</label>
                <input type="text" id="utilisateur-add-prenom" name="prenom" class="form-input">
            </div>
            <div class="form-group mb-4">
                <label class="form-label" for="utilisateur-add-nom" data-i18n="th_nom">Nom</label>
                <input type="text" id="utilisateur-add-nom" name="nom" class="form-input" required>
            </div>
            <div class="form-group mb-4">
                <label class="form-label" for="utilisateur-add-email" data-i18n="th_email">Email</label>
                <input type="email" id="utilisateur-add-email" name="email" class="form-input" required>
            </div>
            <div class="form-group mb-4">
                <label class="form-label" for="utilisateur-add-role" data-i18n="th_role">Rôle</label>
                <select id="utilisateur-add-role" name="role" class="form-select">
                    <option value="client" data-i18n="role_client">Client</option>
                    <option value="evaluateur" data-i18n="role_evaluateur">Évaluateur</option>
                </select>
            </div>
            <div class="form-group mb-4">
                <label class="form-label" for="utilisateur-add-plan" data-i18n="utilisateur_add_plan">Forfait</label>
                <select id="utilisateur-add-plan" name="plan_id" class="form-select">
                    <option value="">—</option>
                    <?php
                    $plans = $plans ?? [];
                    $activePlans = array_filter($plans, fn($p) => $p['active'] ?? true);
                    foreach ($activePlans as $p): ?>
                    <option value="<?= (int) $p['id'] ?>"><?= e($p['name'] ?? $p['name_fr'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-4">
                <label class="form-label" for="utilisateur-add-active" data-i18n="th_status">Statut</label>
                <select id="utilisateur-add-active" name="active" class="form-select">
                    <option value="1" data-i18n="status_active" selected>Actif</option>
                    <option value="0" data-i18n="status_inactive">Désactivé</option>
                </select>
            </div>
            <div class="form-group form-group-checkbox mb-5">
                <label>
                    <input type="checkbox" name="billable" value="1" id="utilisateur-add-billable" checked>
                    <span data-i18n="utilisateur_add_billable">Facturable</span>
                </label>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('utilisateur-add')" data-i18n="btn_cancel">Annuler</button>
                <button type="submit" class="btn btn-primary" data-i18n="btn_save">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Modifier utilisateur -->
<div class="modal-overlay" id="utilisateur-edit-modal">
    <div class="modal modal--narrow">
        <div class="modal-header">
            <h2 class="modal-title" data-i18n="utilisateur_edit_title">Modifier l'utilisateur</h2>
            <button class="btn-icon" onclick="closeModal('utilisateur-edit')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="<?= GESTION_BASE_PATH ?>/utilisateurs/modifier" id="utilisateur-edit-form">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="utilisateur-edit-id" value="">
            <div class="form-group mb-4">
                <label class="form-label" for="utilisateur-edit-prenom" data-i18n="th_prenom">Prénom</label>
                <input type="text" id="utilisateur-edit-prenom" name="prenom" class="form-input">
            </div>
            <div class="form-group mb-4">
                <label class="form-label" for="utilisateur-edit-nom" data-i18n="th_nom">Nom</label>
                <input type="text" id="utilisateur-edit-nom" name="nom" class="form-input" required>
            </div>
            <div class="form-group mb-4">
                <label class="form-label" for="utilisateur-edit-email" data-i18n="th_email">Email</label>
                <input type="email" id="utilisateur-edit-email" name="email" class="form-input" required>
            </div>
            <div class="form-group mb-4">
                <label class="form-label" for="utilisateur-edit-role" data-i18n="th_role">Rôle</label>
                <select id="utilisateur-edit-role" name="role" class="form-select">
                    <option value="client" data-i18n="role_client">Client</option>
                    <option value="evaluateur" data-i18n="role_evaluateur">Évaluateur</option>
                </select>
            </div>
            <div class="form-group mb-4">
                <label class="form-label" for="utilisateur-edit-plan" data-i18n="utilisateur_add_plan">Forfait</label>
                <select id="utilisateur-edit-plan" name="plan_id" class="form-select">
                    <option value="">—</option>
                    <?php
                    $plans = $plans ?? [];
                    $activePlans = array_filter($plans, fn($p) => $p['active'] ?? true);
                    foreach ($activePlans as $p): ?>
                    <option value="<?= (int) $p['id'] ?>"><?= e($p['name'] ?? $p['name_fr'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-4">
                <label class="form-label" for="utilisateur-edit-active" data-i18n="th_status">Statut</label>
                <select id="utilisateur-edit-active" name="active" class="form-select">
                    <option value="1" data-i18n="status_active">Actif</option>
                    <option value="0" data-i18n="status_inactive">Désactivé</option>
                </select>
            </div>
            <div class="form-group form-group-checkbox mb-5">
                <label>
                    <input type="checkbox" name="billable" value="1" id="utilisateur-edit-billable">
                    <span data-i18n="utilisateur_add_billable">Facturable</span>
                </label>
            </div>
            <div class="modal-actions" style="flex-wrap: wrap; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('utilisateur-edit')" data-i18n="btn_cancel">Annuler</button>
                <button type="submit" class="btn btn-primary" data-i18n="btn_save">Enregistrer</button>
                <button type="button" class="btn btn-secondary" id="utilisateur-reset-password-btn">
                    <i class="fa-solid fa-key"></i> <span data-i18n="config_reset_password_btn">Réinitialiser le mot de passe</span>
                </button>
                <button type="button" class="btn btn-danger" id="utilisateur-delete-btn" data-i18n="utilisateur_delete_confirm_btn">Supprimer</button>
            </div>
        </form>
    </div>
</div>
<div class="modal-overlay" id="utilisateur-reset-password-confirm-modal">
    <div class="modal modal--narrow">
        <div class="modal-header">
            <h2 class="modal-title" data-i18n="config_reset_password_title">Réinitialiser le mot de passe</h2>
            <button class="btn-icon" onclick="closeModal('utilisateur-reset-password-confirm')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <p class="subtitle-muted mb-4" id="utilisateur-reset-password-message"></p>
        <form method="POST" action="<?= GESTION_BASE_PATH ?>/utilisateurs/reinitialiser-mot-de-passe" id="utilisateur-reset-password-form">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="utilisateur-reset-password-id" value="">
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('utilisateur-reset-password-confirm')" data-i18n="btn_cancel">Annuler</button>
                <button type="submit" class="btn btn-primary" data-i18n="config_reset_password_confirm_btn">Envoyer le nouveau mot de passe par courriel</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Confirmation suppression utilisateur -->
<div class="modal-overlay" id="utilisateur-delete-modal">
    <div class="modal modal--narrow">
        <div class="modal-header">
            <h2 class="modal-title" data-i18n="utilisateur_delete_modal_title">Confirmer la suppression</h2>
            <button class="btn-icon" onclick="closeModal('utilisateur-delete')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <p class="subtitle-muted mb-4" id="utilisateur-delete-message"></p>
        <form method="POST" action="<?= GESTION_BASE_PATH ?>/utilisateurs/supprimer" id="utilisateur-delete-form" style="display:none;">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="utilisateur-delete-id" value="">
        </form>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeModal('utilisateur-delete')" data-i18n="btn_cancel">Annuler</button>
            <button type="button" class="btn btn-danger" id="utilisateur-delete-confirm" data-i18n="utilisateur_delete_confirm_btn">Supprimer</button>
        </div>
    </div>
</div>

<!-- ─── CONFIGURATION Section ─── -->
<div id="configuration-section" class="content-section">
    <div class="page-header">
        <h1 class="page-title" data-i18n="nav_configuration">Configuration</h1>
        <button type="button" class="btn btn-primary" onclick="openModal('config-add-admin')">
            <i class="fa-solid fa-plus"></i> <span data-i18n="config_add_admin_btn">Ajouter un administrateur</span>
        </button>
    </div>
    <div class="card">
        <h3 class="card-subtitle mb-4" data-i18n="config_admins_title">Administrateurs</h3>
        <p class="subtitle-muted mb-4" data-i18n="config_admins_desc">Comptes ayant accès à l'interface d'administration.</p>
        <!-- Version desktop : tableau -->
        <table class="data-table config-admins-table config-admins-desktop">
            <thead><tr><th data-i18n="th_name">Nom</th><th data-i18n="th_email">Email</th><th data-i18n="th_created">Créé le</th><th data-i18n="th_actions">Actions</th></tr></thead>
            <tbody>
                <?php
                $admins = $admins ?? [];
                $currentUserId = (int) ($currentUserId ?? 0);
                if (empty($admins)): ?>
                <tr><td colspan="4" class="cell-muted"><span data-i18n="config_no_admins">Aucun administrateur.</span></td></tr>
                <?php else:
                foreach ($admins as $a):
                    $createdFormatted = date('Y-m-d', strtotime($a['created_at']));
                    $canDelete = ($currentUserId > 0 && $a['id'] !== $currentUserId);
                ?>
                <tr>
                    <td><strong><?= e($a['name']) ?></strong></td>
                    <td><?= e($a['email']) ?></td>
                    <td><?= e($createdFormatted) ?></td>
                    <td>
                        <button type="button" class="btn-icon config-edit-admin-btn" data-i18n-title="action_edit" title="Modifier"
                            data-admin-id="<?= (int) $a['id'] ?>"
                            data-admin-name="<?= e($a['name']) ?>"
                            data-admin-email="<?= e($a['email']) ?>"
                            data-admin-role="<?= e($a['role']) ?>"
                            onclick="openConfigEditAdminModal(this); return false;"><i class="fa-solid fa-pen"></i></button>
                        <?php if ($canDelete): ?>
                        <form method="POST" action="<?= GESTION_BASE_PATH ?>/admin/supprimer" class="d-inline config-delete-form" data-admin-email="<?= e($a['email']) ?>" data-admin-name="<?= e($a['name']) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                            <button type="button" class="btn-icon btn-icon--danger config-delete-btn" data-i18n-title="action_delete" title="Désactiver"><i class="fa-solid fa-trash"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        <!-- Version mobile : cartes -->
        <div class="config-admins-mobile">
            <?php
            $admins = $admins ?? [];
            $currentUserId = (int) ($currentUserId ?? 0);
            if (empty($admins)): ?>
            <p class="cell-muted" data-i18n="config_no_admins">Aucun administrateur.</p>
            <?php else:
            foreach ($admins as $a):
                $createdFormatted = date('Y-m-d', strtotime($a['created_at']));
                $canDelete = ($currentUserId > 0 && $a['id'] !== $currentUserId);
            ?>
            <div class="config-admin-card">
                <div class="config-admin-card-main">
                    <strong><?= e($a['name']) ?></strong>
                    <span class="config-admin-card-email"><?= e($a['email']) ?></span>
                    <span class="config-admin-card-date"><?= e($createdFormatted) ?></span>
                </div>
                <div class="config-admin-card-actions">
                    <button type="button" class="btn-icon config-edit-admin-btn" data-i18n-title="action_edit" title="Modifier"
                        data-admin-id="<?= (int) $a['id'] ?>"
                        data-admin-name="<?= e($a['name']) ?>"
                        data-admin-email="<?= e($a['email']) ?>"
                        data-admin-role="<?= e($a['role']) ?>"
                        onclick="openConfigEditAdminModal(this); return false;"><i class="fa-solid fa-pen"></i></button>
                    <?php if ($canDelete): ?>
                    <form method="POST" action="<?= GESTION_BASE_PATH ?>/admin/supprimer" class="d-inline config-delete-form" data-admin-email="<?= e($a['email']) ?>" data-admin-name="<?= e($a['name']) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                        <button type="button" class="btn-icon btn-icon--danger config-delete-btn" data-i18n-title="action_delete" title="Désactiver"><i class="fa-solid fa-trash"></i></button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
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

<!-- ─── BUGS ET IDÉES Section ─── -->
<div id="bugs-idees-section" class="content-section">
    <div class="page-header"><h1 class="page-title" data-i18n="nav_bugs_idees">Bugs et idées</h1></div>
    <div class="card">
        <p class="subtitle-muted mb-4" data-i18n="page_bugs_idees_desc">Signaler un problème ou proposer une amélioration pour la plateforme.</p>
        <div id="feedback-list-container">
            <?php if (empty($feedback ?? [])): ?>
            <div class="empty-state">
                <i class="fa-regular fa-lightbulb"></i>
                <p data-i18n="feedback_empty">Aucun retour pour le moment.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="data-table" id="feedback-table">
                    <thead>
                        <tr>
                            <th data-i18n="th_date">Date</th>
                            <th data-i18n="feedback_th_type">Type</th>
                            <th data-i18n="label_message">Message</th>
                            <th data-i18n="feedback_th_source">Source</th>
                            <th data-i18n="feedback_th_user">Utilisateur</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feedback as $f): ?>
                        <tr>
                            <td class="cell-date"><?= e(date('d M Y, H:i', strtotime($f['created_at']))) ?></td>
                            <td>
                                <span class="status-badge <?= $f['type'] === 'idea' ? 'status-active' : 'status-paused' ?>">
                                    <?= $f['type'] === 'idea' ? 'Idée' : 'Bug' ?>
                                </span>
                            </td>
                            <td><?= e($f['message']) ?></td>
                            <td><?= e($f['source'] === 'gestion' ? 'Gestion' : 'App') ?></td>
                            <td><?= e($f['user_name'] ?? $f['user_email'] ?? '—') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
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
            <div class="form-group"><label class="form-label" data-i18n="label_message">Votre message</label><textarea name="message" class="form-input" rows="4" style="resize: vertical;" data-i18n-placeholder="feedback_placeholder" placeholder="Dites-nous en plus..." required></textarea></div>
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
<div class="modal-overlay" id="config-add-admin-modal">
    <div class="modal modal--narrow">
        <div class="modal-header">
            <h2 class="modal-title" data-i18n="config_add_admin_title">Ajouter un administrateur</h2>
            <button class="btn-icon" onclick="closeModal('config-add-admin')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="<?= GESTION_BASE_PATH ?>/admin/ajouter" id="config-add-admin-form">
            <?= csrf_field() ?>
            <div class="form-group mb-4">
                <label class="form-label" for="config-add-admin-name" data-i18n="th_name">Nom</label>
                <input type="text" id="config-add-admin-name" name="name" class="form-input" required>
            </div>
            <div class="form-group mb-4">
                <label class="form-label" for="config-add-admin-email" data-i18n="th_email">Email</label>
                <input type="email" id="config-add-admin-email" name="email" class="form-input" required>
            </div>
            <div class="form-group mb-5">
                <label class="form-label" for="config-add-admin-role" data-i18n="th_role">Rôle</label>
                <select id="config-add-admin-role" name="role" class="form-select">
                    <option value="admin" data-i18n="role_admin">admin</option>
                    <option value="viewer" data-i18n="role_viewer">viewer</option>
                </select>
                <p class="form-help" data-i18n="config_add_admin_help">Un mot de passe temporaire sera généré et envoyé par courriel à l'administrateur.</p>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('config-add-admin')" data-i18n="btn_cancel">Annuler</button>
                <button type="submit" class="btn btn-primary" data-i18n="config_add_admin_submit">Créer et envoyer les identifiants</button>
            </div>
        </form>
    </div>
</div>
<div class="modal-overlay" id="config-edit-admin-modal">
    <div class="modal modal--narrow">
        <div class="modal-header">
            <h2 class="modal-title" data-i18n="config_edit_admin_title">Modifier l'administrateur</h2>
            <button class="btn-icon" onclick="closeModal('config-edit-admin')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="<?= GESTION_BASE_PATH ?>/admin/modifier" id="config-edit-admin-form">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="config-edit-admin-id" value="">
            <div class="form-group mb-4">
                <label class="form-label" for="config-edit-admin-name" data-i18n="th_name">Nom</label>
                <input type="text" id="config-edit-admin-name" name="name" class="form-input" required>
            </div>
            <div class="form-group mb-4">
                <label class="form-label" for="config-edit-admin-email" data-i18n="th_email">Email</label>
                <input type="email" id="config-edit-admin-email" name="email" class="form-input" required>
            </div>
            <div class="form-group mb-5">
                <label class="form-label" for="config-edit-admin-role" data-i18n="th_role">Rôle</label>
                <select id="config-edit-admin-role" name="role" class="form-select">
                    <option value="admin" data-i18n="role_admin">admin</option>
                    <option value="viewer" data-i18n="role_viewer">viewer</option>
                </select>
            </div>
            <div class="modal-actions" style="flex-wrap: wrap; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('config-edit-admin')" data-i18n="btn_cancel">Annuler</button>
                <button type="submit" class="btn btn-primary" data-i18n="btn_save">Enregistrer</button>
                <button type="button" class="btn btn-secondary" id="config-reset-password-btn">
                    <i class="fa-solid fa-key"></i> <span data-i18n="config_reset_password_btn">Réinitialiser le mot de passe</span>
                </button>
            </div>
        </form>
    </div>
</div>
<div class="modal-overlay" id="config-reset-password-confirm-modal">
    <div class="modal modal--narrow">
        <div class="modal-header">
            <h2 class="modal-title" data-i18n="config_reset_password_title">Réinitialiser le mot de passe</h2>
            <button class="btn-icon" onclick="closeModal('config-reset-password-confirm')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <p class="subtitle-muted mb-4" id="config-reset-password-message"></p>
        <form method="POST" action="<?= GESTION_BASE_PATH ?>/admin/reinitialiser-mot-de-passe" id="config-reset-password-form">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="config-reset-password-id" value="">
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('config-reset-password-confirm')" data-i18n="btn_cancel">Annuler</button>
                <button type="submit" class="btn btn-primary" data-i18n="config_reset_password_confirm_btn">Envoyer le nouveau mot de passe par courriel</button>
            </div>
        </form>
    </div>
</div>
<div class="modal-overlay" id="config-delete-admin-modal">
    <div class="modal modal--narrow">
        <div class="modal-header">
            <h2 class="modal-title" data-i18n="config_delete_modal_title">Confirmer la désactivation</h2>
            <button class="btn-icon" onclick="closeModal('config-delete-admin')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <p class="subtitle-muted mb-4" id="config-delete-admin-message"></p>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeModal('config-delete-admin')" data-i18n="btn_cancel">Annuler</button>
            <button type="button" class="btn btn-danger" id="config-delete-admin-confirm" data-i18n="config_delete_confirm_btn">Désactiver</button>
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
