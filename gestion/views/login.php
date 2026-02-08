<main class="hero">
    <div class="login-container">
        <div class="hero-text">
            <div class="login-header">
                <a href="<?= GESTION_BASE_PATH ?>/" class="login-logo-link">
                    ciao<span class="login-logo-cv">cv</span>
                </a>
                <div class="login-lang-switcher">
                    <button type="button" onclick="changeLanguage('fr')" id="btn-fr" class="login-lang-btn">FR</button>
                    <button type="button" onclick="changeLanguage('en')" id="btn-en" class="login-lang-btn">EN</button>
                </div>
            </div>

            <span class="gestion-badge">Administration</span>
            <h1>Connexion <span class="highlight">administration</span></h1>
            <p class="hero-subtitle"><?= e($subtitle) ?></p>
        </div>

        <div class="hero-form">
            <div class="login-card">
                <h2 class="login-form-title">Connexion</h2>

                <?php if (!empty($error)): ?>
                    <div class="login-error"<?php if (!empty($errorKey)): ?> data-i18n="<?= e($errorKey) ?>"<?php endif ?>>
                        <?= $errorHtml ? $error : e($error) ?>
                    </div>
                <?php endif; ?>

                <form action="<?= GESTION_BASE_PATH ?>/connexion" method="POST">
                    <?= csrf_field() ?>
                    <div class="form-group mb-5">
                        <label for="email">Courriel</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="votre@courriel.com" autocomplete="email">
                    </div>
                    <div class="form-group mb-6">
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" autocomplete="current-password">
                    </div>
                    <button type="submit" class="btn-primary login-submit">Se connecter</button>
                </form>

                <div class="login-footer">
                    <a href="<?= GESTION_APP_URL ?>/connexion" class="login-forgot-link">Espace recruteur</a>
                    <span class="login-footer-sep">·</span>
                    <a href="<?= GESTION_SITE_URL ?>" class="login-forgot-link">Retour au site</a>
                </div>
            </div>
        </div>
    </div>
</main>
