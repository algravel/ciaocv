<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Entreprise - <?= APP_NAME ?></title>
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
                <a href="#dashboard" class="nav-item active" data-section="statistiques" data-i18n="nav_dashboard">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Tableau de bord</span>
                </a>

                <a href="#postes" class="nav-item has-submenu" data-section="postes" data-i18n="nav_postes">
                    <i class="fa-solid fa-briefcase"></i>
                    <span>Postes</span>
                    <i class="fa-solid fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="nav-submenu" data-parent="postes">
                    <div class="nav-submenu-inner">
                        <a href="#postes-tous" class="nav-subitem" data-i18n="filter_all">Tous</a>
                        <a href="#postes-actifs" class="nav-subitem" data-i18n="filter_active">Actifs</a>
                        <a href="#postes-pauses" class="nav-subitem" data-i18n="filter_paused">Pausés</a>
                        <a href="#postes-fermes" class="nav-subitem" data-i18n="filter_closed">Fermés</a>
                    </div>
                </div>

                <a href="#affichages" class="nav-item has-submenu" data-section="affichages" data-i18n="nav_affichages">
                    <i class="fa-solid fa-bullhorn"></i>
                    <span>Affichages</span>
                    <i class="fa-solid fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="nav-submenu" data-parent="affichages">
                    <div class="nav-submenu-inner">
                        <a href="#affichages-tous" class="nav-subitem" data-i18n="filter_all">Tous</a>
                        <a href="#affichages-actifs" class="nav-subitem" data-i18n="filter_active">Actifs</a>
                        <a href="#affichages-expires" class="nav-subitem" data-i18n="filter_expired">Expirés</a>
                    </div>
                </div>

                <a href="#candidats" class="nav-item has-submenu" data-section="candidats" data-i18n="nav_candidats">
                    <i class="fa-solid fa-user-group"></i>
                    <span>Candidats</span>
                    <i class="fa-solid fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="nav-submenu" data-parent="candidats">
                    <div class="nav-submenu-inner">
                        <a href="#candidats-tous" class="nav-subitem" data-i18n="filter_all">Tous</a>
                        <a href="#candidats-nouveaux" class="nav-subitem" data-i18n="filter_new">Nouveaux</a>
                        <a href="#candidats-evalues" class="nav-subitem" data-i18n="filter_reviewed">Évalués</a>
                        <a href="#candidats-shortlistes" class="nav-subitem" data-i18n="filter_shortlisted">Shortlistés</a>
                    </div>
                </div>

                <a href="#parametres" class="nav-item" data-section="parametres" data-i18n="nav_parametres">
                    <i class="fa-solid fa-gear"></i>
                    <span>Paramètres</span>
                </a>
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
                    <span class="company-name">OLYMEL</span>
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
                            <a href="/logout" class="logout-link">
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
            postes:         <?= json_encode($postes ?? [], JSON_UNESCAPED_UNICODE) ?>,
            affichages:     <?= json_encode($affichages ?? [], JSON_UNESCAPED_UNICODE) ?>,
            candidats:      <?= json_encode($candidats ?? [], JSON_UNESCAPED_UNICODE) ?>,
            candidatsByAff: <?= json_encode($candidatsByAff ?? [], JSON_UNESCAPED_UNICODE) ?>,
            emailTemplates: <?= json_encode($emailTemplates ?? [], JSON_UNESCAPED_UNICODE) ?>
        };
    </script>
    <script src="<?= asset('assets/js/i18n.js') ?>"></script>
    <script src="<?= asset('assets/js/app.js') ?>"></script>
</body>

</html>
