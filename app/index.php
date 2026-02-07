<?php
/**
 * Page de connexion uniquement.
 * Si connecté : redirection directe vers l'espace candidat (candidate-jobs.php).
 */
session_start();
// require_once __DIR__ . '/db.php'; // Disabled: file not available, login form redirects directly to entreprise.html
require_once __DIR__ . '/includes/functions.php';

// Initialize error variables
$error = '';
$errorHtml = false;

// Déconnexion
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
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
        href="assets/css/design-system.css?v=<?= defined('ASSET_VERSION') ? ASSET_VERSION : '1.3' ?>">
    <style>
        /* Page de connexion – mise en page et couleurs */
        .page-login .hero {
            min-height: 100vh;
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

        /* ========== MODAL MOT DE PASSE OUBLIÉ ========== */
        .forgot-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(6px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.25s, visibility 0.25s;
        }

        .forgot-overlay.active {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        .forgot-modal {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 2.5rem 2rem 2rem;
            width: 90%;
            max-width: 420px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
            position: relative;
            transform: translateY(20px) scale(0.97);
            transition: transform 0.25s;
        }

        .forgot-overlay.active .forgot-modal {
            transform: translateY(0) scale(1);
        }

        .forgot-modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.5rem;
            cursor: pointer;
            line-height: 1;
            padding: 0.25rem;
            border-radius: 8px;
            transition: background 0.15s, color 0.15s;
        }

        .forgot-modal-close:hover {
            background: var(--bg-alt, rgba(255,255,255,0.05));
            color: var(--text);
        }

        .forgot-modal h3 {
            font-size: 1.35rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            color: var(--text);
            letter-spacing: -0.02em;
        }

        .forgot-modal .forgot-desc {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }

        .forgot-modal .form-group {
            margin-bottom: 1.25rem;
        }

        .forgot-modal .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text);
        }

        .forgot-modal .turnstile-placeholder {
            background: var(--bg);
            border: 1px dashed var(--border);
            border-radius: var(--radius);
            padding: 1.25rem;
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.8rem;
            margin-bottom: 1.5rem;
            min-height: 65px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .forgot-modal .turnstile-placeholder svg {
            opacity: 0.5;
        }
    </style>
</head>

<body class="page-login">
    <!-- HEADER REMOVED -->

    <!-- MOBILE MENU REMOVED -->

    <!-- MAIN CONTENT -->
    <main class="hero">
        <div class="login-container">
            <div class="hero-text">
                <!-- LOGO AND LANGUAGE SWITCHER -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; width: 100%;">
                    <a href="https://www.ciaocv.com/index.html"
                        style="font-size: 2.5rem; font-weight: 800; text-decoration: none; color: var(--primary);">
                        ciao<span style="color: var(--text);">cv</span>
                    </a>
                    
                    <!-- Language Switcher -->
                    <div class="lang-switcher" style="display: flex; gap: 0.5rem; background: var(--card-bg); padding: 0.25rem; border-radius: 9999px; border: 1px solid var(--border);">
                        <button onclick="changeLanguage('fr')" id="btn-fr" style="border: none; background: transparent; padding: 0.25rem 0.75rem; border-radius: 9999px; cursor: pointer; font-weight: 600; font-size: 0.85rem; color: var(--text-secondary);">FR</button>
                        <button onclick="changeLanguage('en')" id="btn-en" style="border: none; background: transparent; padding: 0.25rem 0.75rem; border-radius: 9999px; cursor: pointer; font-weight: 600; font-size: 0.85rem; color: var(--text-secondary);">EN</button>
                    </div>
                </div>

                <script>
                    // Custom Language Logic: Default EN, unless browser is FR
                    function initLoginLanguage() {
                        // Check if language is already stored
                        let storedLang = localStorage.getItem('language');
                        
                        if (!storedLang) {
                            // If not stored, check browser language
                            const browserLang = navigator.language || navigator.userLanguage;
                            if (browserLang.toLowerCase().startsWith('fr')) {
                                storedLang = 'fr';
                            } else {
                                storedLang = 'en'; // Default to EN
                            }
                            localStorage.setItem('language', storedLang);
                        }
                        
                        // Apply language
                        changeLanguage(storedLang);
                    }

                    function changeLanguage(lang) {
                        localStorage.setItem('language', lang);
                        document.documentElement.lang = lang;
                        if (typeof updateContent === 'function') {
                            updateContent();
                        }
                        
                        // Update UI
                        const btnFr = document.getElementById('btn-fr');
                        const btnEn = document.getElementById('btn-en');
                        
                        if (lang === 'fr') {
                            if(btnFr) {
                                btnFr.style.backgroundColor = 'var(--primary)';
                                btnFr.style.color = 'white';
                            }
                            if(btnEn) {
                                btnEn.style.backgroundColor = 'transparent';
                                btnEn.style.color = 'var(--text-secondary)';
                            }
                        } else {
                            if(btnFr) {
                                btnFr.style.backgroundColor = 'transparent';
                                btnFr.style.color = 'var(--text-secondary)';
                            }
                            if(btnEn) {
                                btnEn.style.backgroundColor = 'var(--primary)';
                                btnEn.style.color = 'white';
                            }
                        }
                    }

                    // Run immediately
                    document.addEventListener('DOMContentLoaded', initLoginLanguage);
                </script>

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
                            <input type="email" id="email" name="email" class="form-control" placeholder="votre@courriel.com"
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
                        <a href="#" onclick="openForgotModal(); return false;"
                            style="font-size: 0.85rem; color: var(--text-gray); text-decoration: none;"
                            data-i18n="login.forgot_password">Mot de passe oublié ?</a>
                    </div>

                    <div class="oauth-divider" style="display: none;">
                        <span
                            style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;"
                            data-i18n="login.oauth.divider">ou</span>
                    </div>

                    <div style="display: none; flex-direction: column; gap: 0.75rem;">
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

    <!-- MODAL MOT DE PASSE OUBLIÉ -->
    <div class="forgot-overlay" id="forgotOverlay" onclick="closeForgotModal(event)">
        <div class="forgot-modal" onclick="event.stopPropagation()">
            <button class="forgot-modal-close" onclick="closeForgotModal()" aria-label="Fermer">&times;</button>

            <h3 data-i18n="forgot.title">Mot de passe oublié ?</h3>
            <p class="forgot-desc" data-i18n="forgot.desc">Entrez votre adresse courriel et nous vous enverrons un lien pour réinitialiser votre mot de passe.</p>

            <form id="forgotForm" onsubmit="return false;">
                <div class="form-group">
                    <label for="forgot-email" data-i18n="forgot.email.label">Courriel</label>
                    <input type="email" id="forgot-email" name="email" class="form-control"
                        placeholder="votre@courriel.com" data-i18n="forgot.email.placeholder" required>
                </div>

                <!-- Cloudflare Turnstile -->
                <div class="turnstile-placeholder" id="cf-turnstile-container">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    <span data-i18n="forgot.turnstile">Vérification de sécurité Cloudflare</span>
                </div>

                <button type="submit" class="btn-primary"
                    style="width: 100%; border: none; cursor: pointer; font-size: 1rem; padding: 0.85rem;"
                    data-i18n="forgot.submit">Envoyer le lien</button>
            </form>
        </div>
    </div>

    <!-- FOOTER REMOVED -->

    <script src="assets/js/i18n.js?v=<?= defined('ASSET_VERSION') ? ASSET_VERSION : '1.3' ?>"></script>
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

        // Forgot Password Modal
        function openForgotModal() {
            const overlay = document.getElementById('forgotOverlay');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
            // Re-apply translations to modal content
            if (typeof updateContent === 'function') updateContent();
            // Focus email field after animation
            setTimeout(() => {
                const input = document.getElementById('forgot-email');
                if (input) input.focus();
            }, 300);
        }

        function closeForgotModal(e) {
            // If called from overlay click, only close if clicking the overlay itself
            if (e && e.target !== document.getElementById('forgotOverlay')) return;
            const overlay = document.getElementById('forgotOverlay');
            overlay.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeForgotModal();
        });

        // Modal Logic for Missing Type - DISABLED PER USER REQUEST
        /*
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
        */
    </script>
</body>

</html>