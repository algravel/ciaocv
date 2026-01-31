<?php
/**
 * Espace Candidats - Tableau de bord candidat
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Espace Candidat - CiaoCV</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --bg: #111827;
            --card-bg: #1f2937;
            --white: #ffffff;
            --text: #f9fafb;
            --text-light: #9ca3af;
            --border: #374151;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }

        body {
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        .container {
            max-width: 500px;
            margin: 0 auto;
            padding: 1.5rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0 2rem;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            text-decoration: none;
        }

        .back-link {
            color: var(--text-light);
            text-decoration: none;
            font-size: 0.9rem;
        }

        .back-link:hover { color: var(--primary); }

        .section-title {
            font-size: 0.85rem;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.75rem;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: var(--card-bg);
            padding: 1.25rem 1.5rem;
            border-radius: 1rem;
            text-decoration: none;
            color: var(--text);
            border: 1px solid var(--border);
            transition: all 0.2s;
        }

        .menu-item:hover {
            background: var(--primary);
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        .menu-icon {
            font-size: 1.75rem;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(37, 99, 235, 0.2);
            border-radius: 0.75rem;
        }

        .menu-item:hover .menu-icon { background: rgba(255, 255, 255, 0.2); }

        .menu-text { flex: 1; text-align: left; }

        .menu-title { font-size: 1.1rem; font-weight: 600; margin-bottom: 0.2rem; }

        .menu-desc { font-size: 0.85rem; color: var(--text-light); }

        .menu-item:hover .menu-desc { color: rgba(255, 255, 255, 0.8); }

        .menu-arrow { font-size: 1.2rem; color: var(--text-light); }

        .menu-item:hover .menu-arrow { color: white; }

        .footer {
            margin-top: 3rem;
            text-align: center;
            font-size: 0.8rem;
            color: var(--text-light);
        }

        .footer a { color: var(--primary); text-decoration: none; }

        @supports (padding-top: env(safe-area-inset-top)) {
            .container { padding-top: calc(1.5rem + env(safe-area-inset-top)); }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <a href="index.php" class="logo">CiaoCV</a>
            <a href="index.php" class="back-link">← Accueil</a>
        </header>

        <p class="section-title">Espace candidat</p>

        <nav class="menu">
            <a href="candidate-profile.php" class="menu-item">
                <div class="menu-icon">👤</div>
                <div class="menu-text">
                    <div class="menu-title">Mon profil</div>
                    <div class="menu-desc">Informations et présentations (3 max)</div>
                </div>
                <span class="menu-arrow">→</span>
            </a>

            <a href="candidate-applications.php" class="menu-item">
                <div class="menu-icon">📋</div>
                <div class="menu-text">
                    <div class="menu-title">Mes candidatures</div>
                    <div class="menu-desc">Postes où j'ai postulé</div>
                </div>
                <span class="menu-arrow">→</span>
            </a>

            <a href="candidate-jobs.php" class="menu-item">
                <div class="menu-icon">💼</div>
                <div class="menu-text">
                    <div class="menu-title">Offres disponibles</div>
                    <div class="menu-desc">Parcourir tous les postes</div>
                </div>
                <span class="menu-arrow">→</span>
            </a>
        </nav>

        <div class="footer">
            <p><a href="https://www.ciaocv.com">Retour au site principal</a></p>
        </div>
    </div>
</body>
</html>
