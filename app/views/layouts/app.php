<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?>CIAOCV - Gestionnaire</title>
    <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

    <div class="app-container">
        <!-- Sidebar Overlay (mobile) -->
        <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

        <!-- ─── Sidebar ─── -->
        <aside class="sidebar">
            <div class="logo">
                <a href="<?= SITE_URL ?>" style="text-decoration: none;">
                    <span class="logo-text">ciao<span class="cv">cv</span></span>
                </a>
            </div>

            <nav class="nav-links">
                <?php $def = $defaultSection ?? 'statistiques'; ?>
                <a href="/tableau-de-bord" class="nav-item<?= $def === 'statistiques' ? ' active' : '' ?>"
                    data-section="statistiques" data-i18n="nav_dashboard">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Tableau de bord</span>
                </a>

                <a href="/postes" class="nav-item has-submenu<?= $def === 'postes' ? ' active' : '' ?>"
                    data-section="postes" data-i18n="nav_postes">
                    <i class="fa-solid fa-briefcase"></i>
                    <span>Postes</span>
                    <i class="fa-solid fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="nav-submenu" data-parent="postes">
                    <div class="nav-submenu-inner">
                        <a href="/postes#postes-tous" class="nav-subitem" data-i18n="filter_all">Tous</a>
                        <a href="/postes#postes-actifs" class="nav-subitem" data-i18n="filter_active">Actifs</a>
                        <a href="/postes#postes-inactifs" class="nav-subitem" data-i18n="filter_inactive">Non actifs</a>
                        <a href="/postes#postes-archives" class="nav-subitem" data-i18n="filter_archived">Archivés</a>
                    </div>
                </div>

                <a href="/affichages" class="nav-item has-submenu<?= $def === 'affichages' ? ' active' : '' ?>"
                    data-section="affichages" data-i18n="nav_affichages">
                    <i class="fa-solid fa-bullhorn"></i>
                    <span>Affichages</span>
                    <i class="fa-solid fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="nav-submenu" data-parent="affichages">
                    <div class="nav-submenu-inner">
                        <a href="/affichages#affichages-tous" class="nav-subitem" data-i18n="filter_all">Tous</a>
                        <a href="/affichages#affichages-actifs" class="nav-subitem" data-i18n="filter_active">Actifs</a>
                        <a href="/affichages#affichages-expires" class="nav-subitem"
                            data-i18n="filter_expired">Expirés</a>
                    </div>
                </div>

                <a href="/candidats" class="nav-item has-submenu<?= $def === 'candidats' ? ' active' : '' ?>"
                    data-section="candidats" data-i18n="nav_candidats">
                    <i class="fa-solid fa-user-group"></i>
                    <span>Candidats</span>
                    <i class="fa-solid fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="nav-submenu" data-parent="candidats">
                    <div class="nav-submenu-inner">
                        <a href="/candidats#candidats-tous" class="nav-subitem" data-i18n="filter_all">Tous</a>
                        <a href="/candidats#candidats-nouveaux" class="nav-subitem" data-i18n="filter_new">Nouveaux</a>
                        <a href="/candidats#candidats-evalues" class="nav-subitem"
                            data-i18n="filter_reviewed">Évalués</a>
                        <a href="/candidats#candidats-shortlistes" class="nav-subitem"
                            data-i18n="filter_shortlisted">Shortlistés</a>
                    </div>
                </div>

                <a href="/parametres" class="nav-item has-submenu<?= $def === 'parametres' ? ' active' : '' ?>"
                    data-section="parametres" data-i18n="nav_parametres">
                    <i class="fa-solid fa-gear"></i>
                    <span>Paramètres</span>
                    <i class="fa-solid fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="nav-submenu" data-parent="parametres">
                    <div class="nav-submenu-inner">
                        <a href="/parametres#parametres-company" class="nav-subitem settings-subitem"
                            data-target="settings-company" data-i18n="settings_company">Entreprise</a>
                        <a href="/parametres#parametres-departments" class="nav-subitem settings-subitem"
                            data-target="settings-departments" data-i18n="settings_departments">Départements</a>
                        <a href="/parametres#parametres-team" class="nav-subitem settings-subitem"
                            data-target="settings-team" data-i18n="settings_team">Équipe</a>
                        <a href="/parametres#parametres-billing" class="nav-subitem settings-subitem"
                            data-target="settings-billing" data-i18n="settings_billing">Facturation</a>
                        <a href="/parametres#parametres-communications" class="nav-subitem settings-subitem"
                            data-target="settings-communications">Communication</a>
                    </div>
                </div>
            </nav>
        </aside>

        <!-- ─── Main Content ─── -->
        <main class="main-content">
            <!-- Top Bar -->
            <header class="top-bar">
                <button class="hamburger-btn" onclick="toggleSidebar()">
                    <i class="fa-solid fa-bars"></i>
                </button>

                <div class="top-bar-center">
                    <span class="company-name"><?= e($companyName ?? '') ?></span>
                </div>

                <div class="header-actions">
                    <div class="lang-switcher">
                        <button class="lang-btn active" data-lang="fr">FR</button>
                        <button class="lang-btn" data-lang="en">EN</button>
                    </div>
                    <button class="icon-btn" onclick="document.querySelector('a[data-section=\'parametres\']').click()">
                        <i class="fa-solid fa-gear"></i>
                    </button>
                    <button class="icon-btn">
                        <i class="fa-regular fa-bell"></i>
                    </button>
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
                            <a href="/deconnexion" class="logout-link">
                                <i class="fa-solid fa-right-from-bracket"></i>
                                <span data-i18n="dropdown_logout">Se déconnecter</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <div class="content-area">
                <?= $content ?>
            </div>
        </main>
    </div>

    <!-- Feedback FAB -->
    <button class="feedback-fab" onclick="openModal('feedback')">
        <i class="fa-solid fa-bug"></i>
        <i class="fa-regular fa-lightbulb"></i>
    </button>

    <!-- ─── Données injectées depuis les modèles PHP ─── -->
    <script>
        const APP_DATA = {
            appUrl: <?= json_encode(defined('APP_URL') ? APP_URL : 'https://app.ciaocv.com') ?>,
            currentUser: <?= json_encode(($user ?? [])['name'] ?? 'Utilisateur', JSON_UNESCAPED_UNICODE) ?>,
            userTimezone: <?= json_encode($userTimezone ?? 'America/Montreal', JSON_UNESCAPED_UNICODE) ?>,
            postes: <?= json_encode($postes ?? [], JSON_UNESCAPED_UNICODE) ?>,
            affichages: <?= json_encode($affichages ?? [], JSON_UNESCAPED_UNICODE) ?>,
            candidats: <?= json_encode($candidats ?? [], JSON_UNESCAPED_UNICODE) ?>,
            candidatsByAff: <?= json_encode($candidatsByAff ?? [], JSON_UNESCAPED_UNICODE) ?>,
            emailTemplates: <?= json_encode($emailTemplates ?? [], JSON_UNESCAPED_UNICODE) ?>,
            departments: <?= json_encode($departments ?? [], JSON_UNESCAPED_UNICODE) ?>,
            teamMembers: <?= json_encode($teamMembers ?? [], JSON_UNESCAPED_UNICODE) ?>,
            events: <?= json_encode($events ?? [], JSON_UNESCAPED_UNICODE) ?>
        };
    </script>
    <script src="<?= asset('assets/js/i18n.js') ?>"></script>
    <script src="<?= asset('assets/js/app.js') ?>"></script>
</body>

</html>