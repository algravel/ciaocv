<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?>CIAOCV - GESTION</title>
    <link rel="stylesheet" href="<?= gestion_asset('assets/css/app.css') ?>">
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
                <a href="#dashboard" class="nav-item active" data-section="statistiques">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Tableau de bord</span>
                </a>

                <a href="#ventes" class="nav-item has-submenu" data-section="ventes-stripe">
                    <i class="fa-solid fa-credit-card"></i>
                    <span>Ventes</span>
                    <i class="fa-solid fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="nav-submenu" data-parent="ventes-stripe">
                    <div class="nav-submenu-inner">
                        <a href="#ventes-stripe" class="nav-subitem" data-section="ventes-stripe">Liste des ventes Stripe</a>
                    </div>
                </div>

                <a href="#configuration" class="nav-item has-submenu" data-section="forfaits-crud">
                    <i class="fa-solid fa-gear"></i>
                    <span>Configuration</span>
                    <i class="fa-solid fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="nav-submenu" data-parent="forfaits-crud">
                    <div class="nav-submenu-inner">
                        <span class="nav-submenu-label">Forfaits</span>
                        <span class="nav-submenu-label nav-submenu-label--sub">CRUD des forfaits</span>
                        <a href="#forfaits-limite" class="nav-subitem nav-subitem--l3" data-section="forfaits-crud">Limite de vidéo</a>
                        <a href="#forfaits-prix" class="nav-subitem nav-subitem--l3" data-section="forfaits-crud">Prix mensuel / Annuel</a>
                    </div>
                </div>

                <a href="#utilisateurs" class="nav-item has-submenu" data-section="utilisateurs-liste">
                    <i class="fa-solid fa-users"></i>
                    <span>Utilisateurs</span>
                    <i class="fa-solid fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="nav-submenu" data-parent="utilisateurs-liste">
                    <div class="nav-submenu-inner">
                        <a href="#utilisateurs-liste" class="nav-subitem" data-section="utilisateurs-liste">Liste CRUD</a>
                    </div>
                </div>
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
                    <button class="icon-btn" onclick="document.querySelector('a[data-section=\'forfaits-crud\']').click()">
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
                            <a href="<?= GESTION_BASE_PATH ?>/deconnexion" class="logout-link">
                                <i class="fa-solid fa-right-from-bracket"></i>
                                <span data-i18n="dropdown_logout">Se déconnecter</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <div class="content-area">
                <?= $content ?>
            </div>
        </main>
    </div>

    <script>
        const APP_DATA = {
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
    <script src="<?= gestion_asset('assets/js/i18n.js') ?>"></script>
    <script src="<?= gestion_asset('assets/js/app.js') ?>"></script>
</body>

</html>
