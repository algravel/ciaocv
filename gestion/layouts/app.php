<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if (!empty($isDebugPage)): ?>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <?php endif; ?>
    <script>(function(){var l=localStorage.getItem('language');if(l)document.documentElement.lang=l;})();</script>
    <title><?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?>CIAOCV - GESTION</title>
    <link rel="stylesheet" href="<?= gestion_asset('assets/css/app.css', !empty($isDebugPage)) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

    <div class="app-container">
        <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

        <aside class="sidebar">
            <div class="logo">
                <a href="<?= GESTION_BASE_PATH ?>/tableau-de-bord" style="text-decoration: none;">
                    <span class="logo-text">ciao<span class="cv">cv</span></span>
                </a>
            </div>

            <nav class="nav-links">
                <?php $dashboardBase = GESTION_BASE_PATH ? GESTION_BASE_PATH . '/tableau-de-bord' : '/tableau-de-bord'; ?>
                <a href="<?= $dashboardBase ?>#dashboard" class="nav-item active" data-section="statistiques" data-hash="#dashboard">
                    <i class="fa-solid fa-chart-line"></i>
                    <span data-i18n="nav_dashboard">Tableau de bord</span>
                </a>

                <a href="<?= $dashboardBase ?>#ventes-stripe" class="nav-item" data-section="ventes-stripe" data-hash="#ventes-stripe">
                    <i class="fa-solid fa-credit-card"></i>
                    <span data-i18n="nav_ventes">Ventes</span>
                </a>

                <a href="<?= $dashboardBase ?>#forfaits-crud" class="nav-item" data-section="forfaits-crud" data-hash="#forfaits-crud">
                    <i class="fa-solid fa-gear"></i>
                    <span data-i18n="nav_forfaits">Forfaits</span>
                </a>

                <a href="<?= $dashboardBase ?>#utilisateurs-liste" class="nav-item" data-section="utilisateurs-liste" data-hash="#utilisateurs-liste">
                    <i class="fa-solid fa-users"></i>
                    <span data-i18n="nav_utilisateurs">Utilisateurs</span>
                </a>

                <a href="<?= $dashboardBase ?>#synchronisation" class="nav-item" data-section="synchronisation" data-hash="#synchronisation">
                    <i class="fa-solid fa-arrows-rotate"></i>
                    <span data-i18n="nav_synchronisation">Synchronisation</span>
                </a>

                <a href="<?= $dashboardBase ?>#configuration" class="nav-item" data-section="configuration" data-hash="#configuration">
                    <i class="fa-solid fa-sliders"></i>
                    <span data-i18n="nav_configuration">Configuration</span>
                </a>

                <a href="<?= GESTION_BASE_PATH ?>/debug" class="nav-item <?= !empty($isDebugPage) ? 'active' : '' ?>" style="<?= !empty($isDebugPage) ? 'background-color: #F5E6EA; color: var(--primary-color);' : '' ?>">
                    <i class="fa-solid fa-bug"></i>
                    <span>Debug</span>
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="top-bar">
                <button class="hamburger-btn" onclick="toggleSidebar()">
                    <i class="fa-solid fa-bars"></i>
                </button>

                <div class="top-bar-center">
                    <span class="company-name">ADMINISTRATION</span>
                </div>

                <div class="header-actions">
                    <div class="lang-switcher">
                        <button class="lang-btn active" data-lang="fr">FR</button>
                        <button class="lang-btn" data-lang="en">EN</button>
                    </div>
                    <div class="user-profile" onclick="toggleUserDropdown(event)">
                        <div class="user-avatar-icon">
                            <i class="fa-regular fa-user"></i>
                        </div>
                        <i class="fa-solid fa-caret-down caret-icon"></i>
                        <div class="user-dropdown" id="userDropdown">
                            <div class="user-dropdown-header">
                                <strong><?= e($user['name'] ?? 'Utilisateur') ?></strong>
                                <span><?= e($user['email'] ?? '') ?></span>
                            </div>
                            <a href="#" class="user-dropdown-link" onclick="event.preventDefault(); document.getElementById('userDropdown').classList.remove('open'); openModal('change-password');">
                                <i class="fa-solid fa-key"></i>
                                <span data-i18n="dropdown_change_password">Changer mot de passe</span>
                            </a>
                            <a href="<?= GESTION_BASE_PATH ?>/deconnexion" class="logout-link">
                                <i class="fa-solid fa-right-from-bracket"></i>
                                <span data-i18n="dropdown_logout">Se déconnecter</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <div class="content-area">
                <?php if (!empty($flashSuccess ?? null)): ?>
                <div class="config-flash config-flash--success" style="margin:1rem 1.5rem;"><?= e($flashSuccess ?? '') ?></div>
                <?php endif; ?>
                <?php if (!empty($flashError ?? null)): ?>
                <div class="config-flash config-flash--error" style="margin:1rem 1.5rem;"><?= e($flashError ?? '') ?></div>
                <?php endif; ?>
                <?= $content ?>
            </div>
        </main>
    </div>

    <div class="modal-overlay" id="change-password-modal">
        <div class="modal modal--narrow">
            <div class="modal-header">
                <h2 class="modal-title" data-i18n="change_password_title">Changer mon mot de passe</h2>
                <button class="btn-icon" onclick="closeModal('change-password')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form method="POST" action="<?= GESTION_BASE_PATH ?>/changer-mot-de-passe" id="change-password-form">
                <?= csrf_field() ?>
                <div class="form-group mb-4">
                    <label class="form-label" for="change-pwd-current" data-i18n="change_password_current">Mot de passe actuel</label>
                    <input type="password" id="change-pwd-current" name="current_password" class="form-input" required autocomplete="current-password">
                </div>
                <div class="form-group mb-4">
                    <label class="form-label" for="change-pwd-new" data-i18n="change_password_new">Nouveau mot de passe</label>
                    <input type="password" id="change-pwd-new" name="new_password" class="form-input" required autocomplete="new-password" minlength="8">
                </div>
                <div class="form-group mb-5">
                    <label class="form-label" for="change-pwd-confirm" data-i18n="change_password_confirm">Confirmer le nouveau mot de passe</label>
                    <input type="password" id="change-pwd-confirm" name="new_password_confirm" class="form-input" required autocomplete="new-password" minlength="8">
                    <span id="change-pwd-match-error" class="form-error hidden" data-i18n="change_password_mismatch">Les mots de passe ne correspondent pas.</span>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('change-password')" data-i18n="btn_cancel">Annuler</button>
                    <button type="submit" class="btn btn-primary" data-i18n="change_password_submit">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const APP_DATA = {
            basePath:       <?= json_encode(GESTION_BASE_PATH) ?>,
            appUrl:         <?= json_encode(GESTION_APP_URL) ?>,
            postes:         <?= json_encode($postes ?? [], JSON_UNESCAPED_UNICODE) ?>,
            affichages:     <?= json_encode($affichages ?? [], JSON_UNESCAPED_UNICODE) ?>,
            candidats:      <?= json_encode($candidats ?? [], JSON_UNESCAPED_UNICODE) ?>,
            candidatsByAff: <?= json_encode($candidatsByAff ?? [], JSON_UNESCAPED_UNICODE) ?>,
            emailTemplates: <?= json_encode($emailTemplates ?? [], JSON_UNESCAPED_UNICODE) ?>,
            departments:    <?= json_encode($departments ?? [], JSON_UNESCAPED_UNICODE) ?>,
            teamMembers:    <?= json_encode($teamMembers ?? [], JSON_UNESCAPED_UNICODE) ?>
        };
    </script>
    <script src="<?= gestion_asset('assets/js/i18n.js', !empty($isDebugPage)) ?>"></script>
    <script src="<?= gestion_asset('assets/js/app.js', !empty($isDebugPage)) ?>"></script>
</body>

</html>
