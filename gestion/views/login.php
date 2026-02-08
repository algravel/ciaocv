<main class="hero">
    <div class="login-container">
        <div class="hero-text">
            <div class="login-header">
                <a href="<?= GESTION_BASE_PATH ?>/" class="login-logo-link">
                    ciao<span class="login-logo-cv">cv</span>
                </a>
                <div class="login-lang-switcher">
                    <button type="button" id="btn-fr" class="login-lang-btn lang-btn" data-lang="fr">FR</button>
                    <button type="button" id="btn-en" class="login-lang-btn lang-btn" data-lang="en">EN</button>
                </div>
            </div>

            <span class="gestion-badge" data-i18n="login.admin.badge">Administration</span>
            <h1 data-i18n="login.admin.hero">Connexion <span class="highlight">administration</span></h1>
        </div>

        <div class="hero-form">
            <div class="login-card">
                <h2 class="login-form-title" data-i18n="login.title">Connexion</h2>

                <?php if (!empty($error)): ?>
                    <div class="login-error"<?php if (!empty($errorKey)): ?> data-i18n="<?= e($errorKey) ?>"<?php endif ?>>
                        <?= $errorHtml ? $error : e($error) ?>
                    </div>
                <?php endif; ?>

                <form action="<?= GESTION_BASE_PATH ?>/connexion" method="POST" id="loginForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="lang" id="login-lang-input" value="">
                    <div class="form-group mb-5">
                        <label for="email" data-i18n="login.email.label">Courriel</label>
                        <input type="email" id="email" name="email" class="form-control" data-i18n-placeholder="login.email.placeholder" placeholder="votre@courriel.com" autocomplete="email">
                    </div>
                    <div class="form-group mb-6">
                        <label for="password" data-i18n="login.password.label">Mot de passe</label>
                        <input type="password" id="password" name="password" class="form-control" data-i18n-placeholder="login.password.placeholder" placeholder="••••••••" autocomplete="current-password">
                    </div>
                    <?php
                    $turnstileKey = $_ENV['TURNSTILE_SITE_KEY'] ?? '';
                    if ($turnstileKey !== ''):
                    ?>
                    <div class="form-group mb-6 turnstile-wrap">
                        <div class="cf-turnstile" data-sitekey="<?= e($turnstileKey) ?>" data-theme="light" data-size="normal"></div>
                    </div>
                    <?php endif; ?>
                    <button type="submit" class="btn-primary login-submit" data-i18n="login.submit">Se connecter</button>
                </form>

                <div class="login-footer">
                    <a href="<?= GESTION_APP_URL ?>/connexion" class="login-forgot-link" data-i18n="login.recruiter_link">Espace recruteur</a>
                    <span class="login-footer-sep">·</span>
                    <a href="<?= GESTION_SITE_URL ?>" class="login-forgot-link" data-i18n="login.back_site">Retour au site</a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php if (!empty($showOtpModal)): ?>
<div class="otp-overlay active" id="otpOverlay">
    <div class="otp-modal">
        <h3 class="otp-modal-title" data-i18n="login.otp.title">Vérification en deux étapes</h3>
        <p class="otp-modal-desc"><span data-i18n="login.otp.desc">Un code à 6 chiffres a été envoyé à</span> <strong><?= e($otpEmail ?? '') ?></strong></p>
        <?php if (!empty($error)): ?>
        <div class="login-error mb-4"<?php if (!empty($errorKey)): ?> data-i18n="<?= e($errorKey) ?>"<?php endif ?>><?= $errorHtml ? $error : e($error) ?></div>
        <?php endif; ?>
        <form action="<?= GESTION_BASE_PATH ?>/verifier-otp" method="POST" id="otpForm">
            <?= csrf_field() ?>
            <div class="form-group mb-4">
                <label for="otp" data-i18n="login.otp.label">Code de vérification</label>
                <input type="text" id="otp" name="otp" class="form-control otp-input" placeholder="000000" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="one-time-code" data-i18n-placeholder="login.otp.placeholder">
            </div>
            <button type="submit" class="btn-primary login-submit" data-i18n="login.otp.submit">Vérifier</button>
        </form>
        <p class="otp-modal-help"><a href="<?= GESTION_BASE_PATH ?>/connexion" class="login-forgot-link">Annuler et revenir</a></p>
    </div>
</div>
<?php endif; ?>
