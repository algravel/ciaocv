<main class="hero">
    <div class="login-container">
        <div class="hero-text">
            <!-- Logo + Sélecteur de langue -->
            <div class="login-header">
                <a href="<?= SITE_URL ?>" class="login-logo-link">
                    ciao<span class="login-logo-cv">cv</span>
                </a>
                <div class="login-lang-switcher">
                    <button onclick="changeLanguage('fr')" id="btn-fr" class="login-lang-btn">FR</button>
                    <button onclick="changeLanguage('en')" id="btn-en" class="login-lang-btn">EN</button>
                </div>
            </div>

            <h1 data-i18n="login.hero.title">Content de vous <br><span class="highlight">revoir !</span></h1>
            <p class="hero-subtitle" data-i18n="<?= e($subtitleKey) ?>">Accédez à votre espace pour gérer vos entrevues vidéo et vos candidatures en toute simplicité.</p>
        </div>

        <div class="hero-form">
            <div class="login-card">
                <h2 class="login-form-title" data-i18n="login.title">Connexion</h2>

                <?php if (!empty($error)): ?>
                    <div class="login-error"<?php if (!empty($errorKey)): ?> data-i18n="<?= e($errorKey) ?>"<?php endif ?>>
                        <?= $errorHtml ? $error : e($error) ?>
                    </div>
                <?php endif; ?>

                <form action="/connexion" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" id="login-lang" name="lang" value="fr">
                    <div class="form-group mb-5">
                        <label for="email" data-i18n="login.email.label">Courriel</label>
                        <input type="email" id="email" name="email" class="form-control"
                            placeholder="votre@courriel.com" data-i18n="login.email.placeholder">
                    </div>
                    <div class="form-group mb-6">
                        <label for="password" data-i18n="login.password.label">Mot de passe</label>
                        <input type="password" id="password" name="password" class="form-control"
                            placeholder="••••••••" data-i18n="login.password.placeholder">
                    </div>
                    <!-- Cloudflare Turnstile désactivé — remplacé par 2FA par courriel -->
                    <!--
                    <?php
                    $turnstileKey = $_ENV['TURNSTILE_SITE_KEY'] ?? '';
                    if ($turnstileKey !== ''):
                    ?>
                    <div class="form-group mb-6 turnstile-wrap">
                        <div class="cf-turnstile" data-sitekey="<?= e($turnstileKey) ?>" data-theme="light" data-size="normal"></div>
                    </div>
                    <?php endif; ?>
                    -->
                    <button type="submit" class="btn-primary login-submit" data-i18n="login.submit">Se connecter</button>
                </form>

                <div class="login-footer">
                    <a href="<?= SITE_URL ?>/tarifs" class="login-forgot-link" data-i18n="login.create_account">Créer un compte</a>
                    <span class="login-footer-sep">·</span>
                    <a href="#" onclick="openForgotModal(); return false;" class="login-forgot-link" data-i18n="login.forgot_password">Mot de passe oublié ?</a>
                </div>
                <div class="login-demo-cta">
                    <a href="<?= SITE_URL ?>/tarifs" class="login-demo-link" data-i18n="login.create_demo">Créer votre compte démo</a>
                </div>

                <!-- OAuth (masqué pour l'instant) -->
                <div class="oauth-divider hidden">
                    <span class="oauth-divider-text" data-i18n="login.oauth.divider">ou</span>
                </div>
                <div class="hidden flex-col gap-3">
                    <a href="#" class="btn-oauth">
                        <svg width="20" height="20" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                        <span data-i18n="login.oauth.google">Continuer avec Google</span>
                    </a>
                    <a href="#" class="btn-oauth">
                        <svg width="20" height="20" viewBox="0 0 23 23"><path fill="#f35325" d="M1 1h10v10H1z"/><path fill="#81bc06" d="M12 1h10v10H12z"/><path fill="#05a6f0" d="M1 12h10v10H1z"/><path fill="#ffba08" d="M12 12h10v10H12z"/></svg>
                        <span data-i18n="login.oauth.microsoft">Continuer avec Microsoft</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal Mot de passe oublié -->
<div class="forgot-overlay" id="forgotOverlay" onclick="closeForgotModal(event)">
    <div class="forgot-modal" onclick="event.stopPropagation()">
        <button class="forgot-modal-close" onclick="closeForgotModal()" aria-label="Fermer">&times;</button>
        <h3 data-i18n="forgot.title">Mot de passe oublié ?</h3>
        <p class="forgot-desc" data-i18n="forgot.desc">Entrez votre adresse courriel et nous vous enverrons un lien pour réinitialiser votre mot de passe.</p>
        <form id="forgotForm" onsubmit="return false;">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="forgot-email" data-i18n="forgot.email.label">Courriel</label>
                <input type="email" id="forgot-email" name="email" class="form-control"
                    placeholder="votre@courriel.com" data-i18n="forgot.email.placeholder" required>
            </div>
            <button type="submit" class="btn-primary login-submit" data-i18n="forgot.submit">Envoyer le lien</button>
        </form>
    </div>
</div>

<!-- Modal OTP (2FA par courriel) — temporairement désactivé
<div class="forgot-overlay<?= !empty($showOtpModal) ? ' active' : '' ?>" id="otpOverlay" onclick="closeOtpModal(event)">
    <div class="forgot-modal" onclick="event.stopPropagation()">
        <button class="forgot-modal-close" onclick="closeOtpModal()" aria-label="Fermer">&times;</button>
        <h3 data-i18n="login.otp.title">Vérification par courriel</h3>
        <p class="forgot-desc">
            <span data-i18n="login.otp.desc">Un code de vérification a été envoyé à</span>
            <strong><?= e($otpEmail ?? '') ?></strong>
        </p>

        <?php if (!empty($error) && !empty($showOtpModal)): ?>
            <div class="login-error"<?php if (!empty($errorKey)): ?> data-i18n="<?= e($errorKey) ?>"<?php endif ?>>
                <?= $errorHtml ? $error : e($error) ?>
            </div>
        <?php endif; ?>

        <form action="/connexion/otp" method="POST">
            <?= csrf_field() ?>
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="otp-code" data-i18n="login.otp.label">Code à 6 chiffres</label>
                <input type="text" id="otp-code" name="otp" class="form-control"
                    placeholder="000000" maxlength="6" pattern="[0-9]{6}"
                    autocomplete="one-time-code" inputmode="numeric"
                    style="text-align: center; font-size: 1.5rem; letter-spacing: 0.5em; font-weight: 700;"
                    autofocus>
            </div>
            <button type="submit" class="btn-primary login-submit" data-i18n="login.otp.submit">Vérifier</button>
        </form>
        <p style="text-align: center; margin-top: 1rem; font-size: 0.85rem; color: var(--text-secondary);">
            <span data-i18n="login.otp.expires">Le code expire dans 10 minutes.</span>
        </p>
    </div>
</div>
-->

<script>
function closeOtpModal(e) {
    const el = document.getElementById('otpOverlay');
    if (!el) return;
    if (e && e.target !== el) return;
    el.classList.remove('active');
    document.body.style.overflow = 'auto';
}

// Sync language field with login form
document.addEventListener('DOMContentLoaded', function() {
    const langField = document.getElementById('login-lang');
    if (langField) {
        langField.value = localStorage.getItem('language') || 'fr';
    }
});

// Override changeLanguage to also update the hidden field
const _origChangeLanguage = typeof changeLanguage === 'function' ? changeLanguage : null;
function changeLanguage(lang) {
    if (_origChangeLanguage) _origChangeLanguage(lang);
    const langField = document.getElementById('login-lang');
    if (langField) langField.value = lang;
}
</script>
