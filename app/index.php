<?php
/**
 * Page de connexion uniquement.
 * Si connecté : redirection directe vers l'espace candidat (candidate-jobs.php).
 */
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';

// Déconnexion
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Si connecté : afficher directement l'espace candidat (pas de page d'accueil)
$isLoggedIn = isset($_SESSION['user_id']);
if ($isLoggedIn) {
    header('Location: candidate-jobs.php');
    exit;
}

// =====================
// TRAITEMENT CONNEXION - BYPASS: redirige directement vers employer.php
// =====================
$error = null;
$errorHtml = false;

if (!$isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'connexion') {
        // BYPASS: Redirection directe vers employer.php sans validation
        header('Location: employer.php');
        exit;
    }
}
// =====================
// DYNAMIC SUBTITLE LOGIC
// =====================
$loginType = $_GET['type'] ?? '';
$subtitleKey = 'login.hero.subtitle'; // default

if ($loginType === 'candidat') {
    $subtitleKey = 'login.hero.subtitle.candidat';
} elseif ($loginType === 'entreprise') {
    $subtitleKey = 'login.hero.subtitle.entreprise';
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - CiaoCV</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link rel="stylesheet"
        href="assets/css/design-system.css?v=<?= defined('ASSET_VERSION') ? ASSET_VERSION : '1.2' ?>">
    <style>
        /* Page de connexion – mise en page et couleurs */
        .page-login .hero {
            min-height: calc(100vh - 80px - 180px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 5%;
            box-sizing: border-box;
        }

        .page-login .login-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
            max-width: 1100px;
            width: 100%;
            margin: 0 auto;
        }

        .page-login .hero-text h1 {
            font-size: clamp(1.75rem, 4vw, 3.5rem);
            font-weight: 800;
            line-height: 1.15;
            margin-bottom: 1.25rem;
            letter-spacing: -0.025em;
            color: var(--text);
        }

        .page-login .hero-text .highlight {
            color: var(--primary);
            position: relative;
            z-index: 1;
        }

        .page-login .hero-text .highlight::after {
            content: '';
            position: absolute;
            bottom: 4px;
            left: 0;
            width: 100%;
            height: 12px;
            background: rgba(37, 99, 235, 0.2);
            z-index: -1;
            transform: rotate(-1.5deg);
        }

        .page-login .hero-subtitle {
            font-size: 1.1rem;
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .page-login .login-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 2rem;
            box-shadow: var(--shadow-md);
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
        }

        .page-login .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text);
        }

        .page-login .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            background: var(--bg);
            font-family: inherit;
            font-size: 1rem;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }

        .page-login .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }

        .page-login .oauth-divider {
            margin: 1.5rem 0;
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--text-muted);
        }

        .page-login .oauth-divider::before,
        .page-login .oauth-divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .page-login .btn-oauth {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: var(--radius);
            background: var(--bg);
            border: 1px solid var(--border);
            color: var(--text);
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            transition: background 0.2s, border-color 0.2s;
        }

        .page-login .btn-oauth:hover {
            background: var(--bg-alt);
            border-color: var(--text-muted);
        }

        .page-login .login-footer a {
            color: var(--primary);
        }

        .page-login footer {
            flex-shrink: 0;
        }

        @media (max-width: 900px) {
            .page-login .login-container {
                grid-template-columns: 1fr;
                gap: 2rem;
                text-align: center;
            }

            .page-login .hero-text h1 {
                font-size: 1.75rem;
            }

            .page-login .hero-subtitle {
                font-size: 1rem;
            }

            .page-login .login-card {
                max-width: 100%;
            }
        }

        @media (max-width: 600px) {
            .page-login .hero {
                padding: 1.5rem 1rem;
                min-height: auto;
            }

            .page-login .login-card {
                padding: 1.5rem;
            }

            .page-login footer>div:first-of-type {
                grid-template-columns: 1fr !important;
                gap: 2rem !important;
                text-align: center !important;
            }
        }
    </style>
</head>

<body class="page-login">
    <!-- HEADER -->
    <header class="navbar">
        <a href="https://www.ciaocv.com/index.html" class="logo">ciao<span style="color:var(--text-white)">cv</span></a>

        <nav class="nav-links">
            <a href="https://www.ciaocv.com/tarifs.html" data-i18n="nav.service">Notre service</a>
            <a href="https://www.ciaocv.com/guide-candidat.html" data-i18n="nav.guide">Préparez votre entrevue</a>
        </nav>

        <div class="nav-actions">
            <!-- Language Toggle (Desktop) -->
            <a href="#" class="lang-toggle" id="langToggleDesktop"
                style="font-weight:600; margin-right:1rem; color:var(--text-gray); text-decoration:none;">EN</a>

            <!-- Single Login Button -->
            <a href="index.php" class="btn-header-primary" data-i18n="nav.login">
                Se connecter
            </a>

            <button class="hamburger" aria-label="Menu" onclick="toggleMenu()">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>

    <!-- MOBILE MENU -->
    <div class="mobile-menu" id="mobileMenu">
        <button class="hamburger active" style="position:absolute; top: 1.25rem; right: 5%; display:flex;"
            onclick="toggleMenu()">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <!-- Language Toggle (Mobile) -->
        <a href="#" class="lang-toggle" id="langToggleMobile"
            style="font-size: 1.2rem; margin-bottom: 1rem; color: var(--primary); font-weight: 700; text-decoration: none;">EN</a>

        <a href="https://www.ciaocv.com/tarifs.html" onclick="toggleMenu()" data-i18n="nav.service">Notre service</a>
        <a href="https://www.ciaocv.com/guide-candidat.html" onclick="toggleMenu()" data-i18n="nav.guide">Préparez votre
            entrevue</a>
        <div style="margin-top:2rem; width:80%;">
            <a href="index.php" class="btn-header-primary" style="display:block; text-align:center; padding:1rem;"
                data-i18n="nav.login">Se connecter</a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <main class="hero">
        <div class="login-container">
            <div class="hero-text">
                <h1 data-i18n="login.hero.title">Content de vous <br><span class="highlight">revoir !</span></h1>
                <p class="hero-subtitle" data-i18n="<?= $subtitleKey ?>">Accédez à votre espace pour gérer vos entrevues
                    vidéo et vos candidatures en toute simplicité.</p>
            </div>

            <div class="hero-form">
                <div class="login-card">
                    <h2 style="font-size: 1.75rem; font-weight: 800; margin-bottom: 2rem; text-align: center; letter-spacing: -0.02em;"
                        data-i18n="login.title">Connexion</h2>

                    <?php if ($error): ?>
                        <div class="error"
                            style="background: var(--error-bg); color: var(--error); padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; font-size: 0.9rem;">
                            <?= $errorHtml ? $error : htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Formulaire sans validation - redirige directement vers entreprise.html -->
                    <form action="entreprise.html" method="GET">
                        <div class="form-group" style="margin-bottom: 1.25rem;">
                            <label for="email" data-i18n="login.email.label">Courriel</label>
                            <input type="email" id="email" name="email" class="form-control" placeholder="ton@email.com"
                                data-i18n="login.email.placeholder">
                        </div>
                        <div class="form-group" style="margin-bottom: 1.75rem;">
                            <label for="password" data-i18n="login.password.label">Mot de passe</label>
                            <input type="password" id="password" name="password" class="form-control"
                                placeholder="••••••••" data-i18n="login.password.placeholder">
                        </div>
                        <button type="submit" class="btn-primary"
                            style="width: 100%; border: none; cursor: pointer; font-size: 1.05rem; padding: 1rem;"
                            data-i18n="login.submit">Se connecter</button>
                    </form>

                    <div class="login-footer" style="margin-top: 1.5rem; text-align: center;">
                        <a href="forgot-password.php"
                            style="font-size: 0.85rem; color: var(--text-gray); text-decoration: none;"
                            data-i18n="login.forgot_password">Mot de passe oublié ?</a>
                        <p style="margin-top: 1.25rem; font-size: 0.95rem; color: var(--text-gray);">
                            <span data-i18n="login.signup_prompt">Pas encore de compte ?</span> <a
                                href="onboarding/register-candidate.php"
                                style="color: var(--primary); font-weight: 700; text-decoration: none;"
                                data-i18n="login.signup_link">S'inscrire gratuitement</a>
                        </p>
                    </div>

                    <div class="oauth-divider">
                        <span
                            style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;"
                            data-i18n="login.oauth.divider">ou</span>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <a href="oauth-google.php?action=login" class="btn-oauth">
                            <svg width="20" height="20" viewBox="0 0 24 24">
                                <path fill="#4285F4"
                                    d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                                <path fill="#34A853"
                                    d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                                <path fill="#FBBC05"
                                    d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                                <path fill="#EA4335"
                                    d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                            </svg>
                            <span data-i18n="login.oauth.google">Continuer avec Google</span>
                        </a>
                        <a href="oauth-microsoft.php?action=login" class="btn-oauth">
                            <svg width="20" height="20" viewBox="0 0 23 23">
                                <path fill="#f35325" d="M1 1h10v10H1z" />
                                <path fill="#81bc06" d="M12 1h10v10H12z" />
                                <path fill="#05a6f0" d="M1 12h10v10H1z" />
                                <path fill="#ffba08" d="M12 12h10v10H12z" />
                            </svg>
                            <span data-i18n="login.oauth.microsoft">Continuer avec Microsoft</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- FOOTER -->
    <footer style="margin-top: 0; padding: 5rem 5% 3rem; background: var(--primary); color: white;">
        <div
            style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: repeat(3, 1fr); gap: 3rem; text-align: left;">
            <div>
                <h4
                    style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255, 255, 255, 0.7); margin-bottom: 1rem;">
                    CIAOCV</h4>
                <ul style="list-style: none;">
                    <li><a href="https://www.ciaocv.com/tarifs.html"
                            style="color: white; text-decoration: none; font-size: 0.95rem; display: block; margin-bottom: 0.6rem; opacity: 0.9;"
                            data-i18n="footer.service">Notre service</a></li>
                    <li><a href="https://www.ciaocv.com/guide-candidat.html"
                            style="color: white; text-decoration: none; font-size: 0.95rem; display: block; margin-bottom: 0.6rem; opacity: 0.9;"
                            data-i18n="footer.guide">Préparez votre entrevue</a></li>
                </ul>
            </div>
            <div>
                <h4 style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255, 255, 255, 0.7); margin-bottom: 1rem;"
                    data-i18n="footer.legal">Légal</h4>
                <ul style="list-style: none;">
                    <li><a href="#"
                            style="color: white; text-decoration: none; font-size: 0.95rem; display: block; margin-bottom: 0.6rem; opacity: 0.9;"
                            data-i18n="footer.privacy">Politique de confidentialité</a></li>
                    <li><a href="#"
                            style="color: white; text-decoration: none; font-size: 0.95rem; display: block; margin-bottom: 0.6rem; opacity: 0.9;"
                            data-i18n="footer.terms">Conditions d’utilisation</a></li>
                </ul>
            </div>
            <div>
                <h4 style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255, 255, 255, 0.7); margin-bottom: 1rem;"
                    data-i18n="footer.contact">Contact</h4>
                <ul style="list-style: none;">
                    <li><a href="mailto:bonjour@ciaocv.com"
                            style="color: white; text-decoration: none; font-size: 0.95rem; display: block; margin-bottom: 0.6rem; opacity: 0.9;">bonjour@ciaocv.com</a>
                    </li>
                </ul>
            </div>
        </div>
        <div
            style="max-width: 1200px; margin: 2rem auto 0; padding-top: 2rem; border-top: 1px solid rgba(255, 255, 255, 0.2); text-align: center;">
            <p style="color: rgba(255, 255, 255, 0.8); font-size: 0.85rem;">© 2026 CiaoCV</p>
            <p style="margin-top: 0.5rem; opacity: 0.8; font-size: 0.85rem;" data-i18n="footer.proudly">Fièrement humain
                ❤️</p>
        </div>
    </footer>

    <script src="assets/js/i18n.js?v=<?= defined('ASSET_VERSION') ? ASSET_VERSION : '1.2' ?>"></script>
    <script>
        function toggleMenu() {
            const menu = document.getElementById('mobileMenu');
            const hamburgers = document.querySelectorAll('.hamburger');
            if (menu.classList.contains('active')) {
                menu.classList.remove('active');
                document.body.style.overflow = 'auto';
            } else {
                menu.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
            hamburgers.forEach(h => h.classList.toggle('active'));
        }

        // Modal Logic for Missing Type
        document.addEventListener('DOMContentLoaded', function () {
            const urlParams = new URLSearchParams(window.location.search);
            const type = urlParams.get('type');

            if (!type) {
                // Show modal overlay
                const modal = document.createElement('div');
                modal.id = 'type-selection-modal';
                modal.style.position = 'fixed';
                modal.style.top = '0';
                modal.style.left = '0';
                modal.style.width = '100vw';
                modal.style.height = '100vh';
                modal.style.backgroundColor = 'rgba(0, 0, 0, 0.8)'; // Darker for better focus
                modal.style.backdropFilter = 'blur(8px)';
                modal.style.zIndex = '9999';
                modal.style.display = 'flex';
                modal.style.alignItems = 'center';
                modal.style.justifyContent = 'center';

                // Get current language for initial render logic (though i18n.js handles text)
                const lang = getLanguage();

                modal.innerHTML = `
                    <div style="background: var(--card-bg); padding: 3rem 2rem; border-radius: var(--radius-xl); text-align: center; max-width: 500px; width: 90%; border: 1px solid var(--border); box-shadow: var(--shadow-xl);">
                        <h2 data-i18n="login.modal.title" style="margin-bottom: 2rem; font-size: 1.8rem; font-weight: 800; color: var(--text);">Vous êtes ?</h2>
                        <div style="display: flex; gap: 1.5rem; flex-direction: column;">
                            <a href="?type=candidat" class="btn-primary" style="padding: 1rem 1.5rem; font-size: 1.1rem; text-decoration: none; justify-content: center; display: flex;" data-i18n="login.modal.candidate">
                                Un Candidat
                            </a>
                            <a href="?type=entreprise" class="btn-secondary" style="padding: 1rem 1.5rem; font-size: 1.1rem; text-decoration: none; border: 2px solid var(--border); border-radius: var(--radius); color: var(--text); background: var(--bg); display: flex; justify-content: center; font-weight: 600;" data-i18n="login.modal.recruiter">
                                Un Recruteur
                            </a>
                        </div>
                    </div>
                `;

                document.body.appendChild(modal);
                document.body.style.overflow = 'hidden'; // Prevent scrolling

                // Trigger translation update for the new modal content
                updateContent();
            }
        });
    </script>
</body>

</html>