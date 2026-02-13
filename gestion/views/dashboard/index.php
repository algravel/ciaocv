<!-- ═══════════════════════════════════════════════════════════════════════
     VUE : Tableau de bord ADMINISTRATION (gestion)
     Sections : statistiques, ventes, forfaits, utilisateurs, config, bugs.
     Pas de postes/affichages/candidats (réservés à l'app employeur).
     ═══════════════════════════════════════════════════════════════════════ -->

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
                <tr data-plan-id="<?= (int) $p['id'] ?>" data-name-fr="<?= e($p['name_fr']) ?>" data-name-en="<?= e($p['name_en']) ?>" data-video-limit="<?= (int) $p['video_limit'] ?>" data-price-monthly="<?= e((string) $p['price_monthly']) ?>" data-price-yearly="<?= e((string) $p['price_yearly']) ?>" data-active="<?= $isActive ? '1' : '0' ?>" data-features="<?= e(json_encode($p['features'] ?? [])) ?>" data-is-popular="<?= !empty($p['is_popular']) ? '1' : '0' ?>" <?= !$isActive ? 'style="opacity:0.7;"' : '' ?>>
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
            <div class="form-group mb-4">
                <label class="form-label" for="forfait-edit-price-yearly" data-i18n="th_price_yearly">Prix annuel ($)</label>
                <input type="number" id="forfait-edit-price-yearly" name="price_yearly" class="form-input" required min="0" step="0.01">
            </div>
            <div class="form-group mb-4">
                <label class="form-label" for="forfait-edit-features" data-i18n="forfait_features">Fonctionnalités (une par ligne)</label>
                <textarea id="forfait-edit-features" name="features" class="form-input" rows="5" placeholder="Ex: 10 entrevues vidéo"></textarea>
                <div class="form-help" data-i18n="forfait_features_help">Une fonctionnalité par ligne. Vide = affichage par défaut selon le type de forfait.</div>
            </div>
            <div class="form-group mb-4">
                <label class="checkbox-label">
                    <input type="checkbox" id="forfait-edit-is-popular" name="is_popular" value="1">
                    <span data-i18n="forfait_is_popular">Badge POPULAIRE</span>
                </label>
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
            <div class="form-group mb-4">
                <label class="form-label" for="forfait-price-yearly" data-i18n="th_price_yearly">Prix annuel ($)</label>
                <input type="number" id="forfait-price-yearly" name="price_yearly" class="form-input" required min="0" step="0.01" value="0">
            </div>
            <div class="form-group mb-4">
                <label class="form-label" for="forfait-features" data-i18n="forfait_features">Fonctionnalités (une par ligne)</label>
                <textarea id="forfait-features" name="features" class="form-input" rows="5" placeholder="Ex: 10 entrevues vidéo"></textarea>
                <div class="form-help" data-i18n="forfait_features_help">Une fonctionnalité par ligne. Vide = affichage par défaut.</div>
            </div>
            <div class="form-group mb-4">
                <label class="checkbox-label">
                    <input type="checkbox" id="forfait-is-popular" name="is_popular" value="1">
                    <span data-i18n="forfait_is_popular">Badge POPULAIRE</span>
                </label>
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
                        data-user-plan-id="<?= ($u['plan_id'] ?? null) ? (int) $u['plan_id'] : '' ?>"
                        data-user-billable="<?= !empty($u['billable']) ? '1' : '0' ?>"
                        data-user-active="<?= !empty($u['active']) ? '1' : '0' ?>"><i class="fa-solid fa-pen"></i></button>
                    <form method="POST" action="<?= GESTION_BASE_PATH ?>/utilisateurs/supprimer" class="d-inline utilisateur-delete-form" data-user-name="<?= e(trim(($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? $u['name'] ?? '')) ?: $u['email']) ?>" data-user-email="<?= e($u['email']) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                        <button type="button" class="btn-icon btn-icon--danger utilisateur-delete-btn" data-i18n-title="action_delete" title="Supprimer" onclick="event.preventDefault();event.stopPropagation();var f=this.closest('.utilisateur-delete-form');if(f)openUtilisateurDeleteConfirm(f);"><i class="fa-solid fa-trash"></i></button>
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
                            <th data-i18n="feedback_th_status">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $statusLabels = ['new' => 'Nouveau', 'in_progress' => 'En cours', 'resolved' => 'Réglé'];
                        foreach ($feedback as $f):
                            $st = $f['status'] ?? 'new';
                            $statusClass = $st === 'resolved' ? 'status-active' : ($st === 'in_progress' ? 'status-pending' : 'status-paused');
                        ?>
                        <tr data-feedback-id="<?= (int) $f['id'] ?>" data-feedback-data="<?= e(json_encode([
                            'id' => $f['id'],
                            'type' => $f['type'],
                            'message' => $f['message'],
                            'source' => $f['source'],
                            'user_name' => $f['user_name'] ?? null,
                            'user_email' => $f['user_email'] ?? null,
                            'created_at' => $f['created_at'],
                            'status' => $st,
                            'internal_note' => $f['internal_note'] ?? null
                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>">
                            <td class="cell-date cell-clickable" title="Cliquer pour ouvrir"><?= e(date('d M Y, H:i', strtotime($f['created_at']))) ?></td>
                            <td>
                                <span class="status-badge <?= $f['type'] === 'idea' ? 'status-active' : 'status-paused' ?>">
                                    <?= $f['type'] === 'idea' ? 'Idée' : 'Bug' ?>
                                </span>
                            </td>
                            <td><?= e($f['message']) ?></td>
                            <td><?= e($f['source'] === 'gestion' ? 'Gestion' : 'App') ?></td>
                            <td><?= e($f['user_name'] ?? $f['user_email'] ?? '—') ?></td>
                            <td><span class="status-badge <?= $statusClass ?>"><?= e($statusLabels[$st] ?? 'Nouveau') ?></span></td>
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
                <div class="form-group"><label class="form-label" data-i18n="form_industry">Secteur d'activité</label><select class="form-select" name="industry"><option value="">— Sélectionner —</option><option>Technologie</option><option>Finance</option><option>Santé</option><option>Commerce</option><option>Construction</option><option>Éducation</option><option>Restauration et hôtellerie</option><option>Services professionnels</option><option>Manufacturier</option><option>Transport et logistique</option><option>Immobilier</option><option>Assurance</option><option>Médias et communications</option><option>Marketing et publicité</option><option>Secteur public</option><option>Organismes à but non lucratif</option><option>Agroalimentaire</option><option>Énergie</option><option>Automobile</option><option>Conseil et stratégie</option><option>Ressources humaines</option><option>Autre</option></select></div>
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
            <div class="form-group hidden" style="display: none !important;"><!-- Couleur marque masquée temporairement (FR/EN) -->
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
            <h2 class="card-title" data-i18n="settings_team">Évaluateurs</h2>
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
<div class="modal-overlay" id="feedback-detail-modal">
    <div class="modal">
        <div class="modal-header"><h2 class="modal-title" data-i18n="modal_feedback_detail_title">Détail du feedback</h2><button class="btn-icon" onclick="closeModal('feedback-detail')"><i class="fa-solid fa-xmark"></i></button></div>
        <div id="feedback-detail-content" class="mb-4"></div>
        <form id="feedback-detail-form" onsubmit="saveFeedbackDetail(event)">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="feedback-detail-id" value="">
            <div class="form-group">
                <label class="form-label" data-i18n="feedback_th_status">Statut</label>
                <select name="status" id="feedback-detail-status" class="form-select">
                    <option value="new" data-i18n="status_new">Nouveau</option>
                    <option value="in_progress" data-i18n="status_in_progress">En cours</option>
                    <option value="resolved" data-i18n="status_resolved">Réglé</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" data-i18n="feedback_internal_note">Note interne</label>
                <textarea name="internal_note" id="feedback-detail-internal-note" class="form-input" rows="4" style="resize: vertical;" placeholder="Note visible uniquement par les administrateurs..."></textarea>
            </div>
            <div class="modal-actions"><button type="button" class="btn btn-secondary" onclick="closeModal('feedback-detail')" data-i18n="btn_cancel">Annuler</button><button type="submit" class="btn btn-primary" data-i18n="btn_save">Enregistrer</button></div>
        </form>
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
<div class="modal-overlay" id="delete-poste-modal">
    <div class="modal modal--narrow">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fa-solid fa-trash"></i> <span data-i18n="delete_poste_title">Supprimer le poste</span></h2>
            <button class="btn-icon" onclick="closeModal('delete-poste')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <p class="modal-body" id="delete-poste-message">Êtes-vous sûr de vouloir supprimer ce poste ?</p>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeModal('delete-poste')" data-i18n="btn_cancel">Annuler</button>
            <button type="button" class="btn btn-danger" onclick="confirmDeletePoste()">
                <i class="fa-solid fa-trash"></i> <span data-i18n="action_delete">Supprimer</span>
            </button>
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
