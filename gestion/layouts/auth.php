<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>CIAOCV - GESTION</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= gestion_asset('assets/img/favicon.png', true) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= gestion_asset('assets/css/design-system.css', true) ?>">
    <?php if (!empty($_ENV['TURNSTILE_SITE_KEY'])): ?>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <?php endif; ?>
    <style>
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
            background: rgba(128, 0, 32, 0.2);
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
        .page-login .login-footer a { color: var(--primary); }

        @media (max-width: 900px) {
            .page-login .login-container { grid-template-columns: 1fr; gap: 2rem; text-align: center; }
            .page-login .hero-text h1 { font-size: 1.75rem; }
            .page-login .hero-subtitle { font-size: 1rem; }
            .page-login .login-card { max-width: 100%; }
        }
        @media (max-width: 600px) {
            .page-login .hero { padding: 1.5rem 1rem; min-height: auto; }
            .page-login .login-card { padding: 1.5rem; }
        }

        .gestion-badge {
            display: inline-block;
            background: var(--primary);
            color: white;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 0.35rem 0.75rem;
            border-radius: 9999px;
            margin-bottom: 0.75rem;
        }
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
        .login-logo-cv { color: var(--text); }
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
            transition: background 0.2s, color 0.2s;
        }
        .login-lang-btn.active {
            background: var(--primary);
            color: white;
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
        .login-forgot-link:hover { text-decoration: underline; }
        .login-footer-sep { color: var(--text-muted); font-size: 0.85rem; }
        .turnstile-wrap { display: flex; justify-content: center; }
        .mb-4 { margin-bottom: 1rem; }
        .otp-overlay {
            position: fixed; inset: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(6px);
            z-index: 9999;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; visibility: hidden; pointer-events: none;
            transition: opacity 0.25s, visibility 0.25s;
        }
        .otp-overlay.active { opacity: 1; visibility: visible; pointer-events: auto; }
        .otp-modal {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 2rem;
            width: 90%; max-width: 400px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
        }
        .otp-modal-title { font-size: 1.35rem; font-weight: 800; margin-bottom: 0.5rem; color: var(--text); }
        .otp-modal-desc { font-size: 0.95rem; color: var(--text-secondary); margin-bottom: 1.5rem; line-height: 1.5; }
        .otp-input { text-align: center; font-size: 1.5rem; letter-spacing: 0.5em; }
        .otp-modal-help { margin-top: 1rem; text-align: center; font-size: 0.9rem; }
    </style>
</head>

<body class="page-login">
    <?= $content ?>

    <script src="<?= (GESTION_BASE_PATH ? GESTION_BASE_PATH . '/' : '') . gestion_asset('assets/js/i18n.js', true) ?>"></script>
    <script>
        (function() {
            function initLoginLang() {
                var btns = document.querySelectorAll('.login-lang-switcher button');
                if (!btns.length) return;
                var stored = localStorage.getItem('language') || (navigator.language && navigator.language.toLowerCase().startsWith('fr') ? 'fr' : 'en');
                if (!localStorage.getItem('language')) localStorage.setItem('language', stored);
                function setActive(lang) {
                    btns.forEach(function(b) {
                        var isActive = b.getAttribute('data-lang') === lang;
                        b.classList.toggle('active', isActive);
                        b.style.backgroundColor = isActive ? 'var(--primary)' : 'transparent';
                        b.style.color = isActive ? 'white' : 'var(--text-secondary)';
                    });
                }
                setActive(stored);
                btns.forEach(function(btn) {
                    btn.onclick = function() {
                        var lang = this.getAttribute('data-lang');
                        if (!lang) return;
                        localStorage.setItem('language', lang);
                        document.documentElement.lang = lang;
                        setActive(lang);
                        if (typeof updateContent === 'function') updateContent();
                    };
                });
            }
            function initOtpInput() {
                var el = document.getElementById('otp');
                if (el) {
                    el.addEventListener('input', function() { this.value = this.value.replace(/\D/g, '').slice(0, 6); });
                    el.focus();
                }
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() { initLoginLang(); initOtpInput(); });
            } else {
                initLoginLang();
                initOtpInput();
            }
        })();
    </script>
</body>

</html>
