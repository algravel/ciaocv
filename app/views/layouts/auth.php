<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link rel="stylesheet" href="<?= asset('assets/css/design-system.css') ?>">
    <!-- Cloudflare Turnstile désactivé — remplacé par 2FA par courriel -->
    <!--
    <?php if (!empty($_ENV['TURNSTILE_SITE_KEY'])): ?>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <?php endif; ?>
    -->
    <style>
        /* ─── Page de connexion ─── */
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
        }

        /* ─── Modal Mot de passe oublié ─── */
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
            background: var(--bg-alt, rgba(255, 255, 255, 0.05));
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

        /* ─── Composants login (extraits des inline styles) ─── */
        .login-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            width: 100%;
        }

        .login-logo-link {
            font-size: 2.5rem;
            font-weight: 800;
            text-decoration: none;
            color: var(--primary);
        }

        .login-logo-cv {
            color: var(--text);
        }

        .login-lang-switcher {
            display: flex;
            gap: 0.5rem;
            background: var(--card-bg);
            padding: 0.25rem;
            border-radius: 9999px;
            border: 1px solid var(--border);
        }

        .login-lang-btn {
            border: none;
            background: transparent;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .login-form-title {
            font-size: 1.75rem;
            font-weight: 800;
            margin-bottom: 2rem;
            text-align: center;
            letter-spacing: -0.02em;
        }

        .login-error {
            background: var(--error-bg);
            color: var(--error);
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .login-submit {
            width: 100%;
            border: none;
            cursor: pointer;
            font-size: 1.05rem;
            padding: 1rem;
        }

        .login-footer {
            margin-top: 1.5rem;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .login-forgot-link {
            font-size: 0.85rem;
            color: var(--primary);
            text-decoration: none;
        }

        .login-forgot-link:hover {
            text-decoration: underline;
        }

        .login-footer-sep {
            color: var(--text-gray);
            font-size: 0.85rem;
        }

        .turnstile-wrap {
            display: flex;
            justify-content: center;
        }

        .login-demo-cta {
            margin-top: 1rem;
            text-align: center;
        }

        .login-demo-link {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--primary);
            text-decoration: none;
        }

        .login-demo-link:hover {
            text-decoration: underline;
        }

        .oauth-divider-text {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
    </style>
</head>

<body class="page-login">
    <?= $content ?>

    <script src="<?= asset('assets/js/i18n.js') ?>"></script>
    <script>
        // ─── Menu mobile ───
        function toggleMenu() {
            const menu = document.getElementById('mobileMenu');
            const hamburgers = document.querySelectorAll('.hamburger');
            if (menu && menu.classList.contains('active')) {
                menu.classList.remove('active');
                document.body.style.overflow = 'auto';
            } else if (menu) {
                menu.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
            hamburgers.forEach(h => h.classList.toggle('active'));
        }

        // ─── Modal Mot de passe oublié ───
        function openForgotModal() {
            const overlay = document.getElementById('forgotOverlay');
            if (!overlay) return;
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
            if (typeof updateContent === 'function') updateContent();
            setTimeout(() => {
                const input = document.getElementById('forgot-email');
                if (input) input.focus();
            }, 300);
        }

        function closeForgotModal(e) {
            if (e && e.target !== document.getElementById('forgotOverlay')) return;
            const overlay = document.getElementById('forgotOverlay');
            overlay.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeForgotModal();
        });

        document.addEventListener('DOMContentLoaded', function () {
            var forgotLink = document.getElementById('login-forgot-password-link');
            if (forgotLink) {
                forgotLink.addEventListener('click', function (e) {
                    e.preventDefault();
                    openForgotModal();
                });
            }
        });

        // ─── Sélecteur de langue (page login) ───
        function initLoginLanguage() {
            let storedLang = localStorage.getItem('language');
            if (!storedLang) {
                const browserLang = navigator.language || navigator.userLanguage;
                storedLang = browserLang.toLowerCase().startsWith('fr') ? 'fr' : 'en';
                localStorage.setItem('language', storedLang);
            }
            changeLanguage(storedLang);
        }

        function changeLanguage(lang) {
            localStorage.setItem('language', lang);
            document.documentElement.lang = lang;
            if (typeof updateContent === 'function') updateContent();

            const btnFr = document.getElementById('btn-fr');
            const btnEn = document.getElementById('btn-en');
            if (lang === 'fr') {
                if (btnFr) { btnFr.style.backgroundColor = 'var(--primary)'; btnFr.style.color = 'white'; }
                if (btnEn) { btnEn.style.backgroundColor = 'transparent'; btnEn.style.color = 'var(--text-secondary)'; }
            } else {
                if (btnFr) { btnFr.style.backgroundColor = 'transparent'; btnFr.style.color = 'var(--text-secondary)'; }
                if (btnEn) { btnEn.style.backgroundColor = 'var(--primary)'; btnEn.style.color = 'white'; }
            }
        }

        document.addEventListener('DOMContentLoaded', initLoginLanguage);
    </script>
</body>

</html>