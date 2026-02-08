<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>(function(){var l=localStorage.getItem('language');if(l)document.documentElement.lang=l;})();</script>
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
                    <span data-i18n="nav_dashboard">Tableau de bord</span>
                </a>

                <a href="#ventes-stripe" class="nav-item" data-section="ventes-stripe">
                    <i class="fa-solid fa-credit-card"></i>
                    <span data-i18n="nav_ventes">Ventes</span>
                </a>

                <a href="#forfaits-crud" class="nav-item" data-section="forfaits-crud">
                    <i class="fa-solid fa-gear"></i>
                    <span data-i18n="nav_forfaits">Forfaits</span>
                </a>

                <a href="#utilisateurs-liste" class="nav-item" data-section="utilisateurs-liste">
                    <i class="fa-solid fa-users"></i>
                    <span data-i18n="nav_utilisateurs">Utilisateurs</span>
                </a>

                <a href="#synchronisation" class="nav-item" data-section="synchronisation">
                    <i class="fa-solid fa-arrows-rotate"></i>
                    <span data-i18n="nav_synchronisation">Synchronisation</span>
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
