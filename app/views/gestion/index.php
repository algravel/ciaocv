<main class="hero">
    <div class="login-container" style="max-width: 560px;">
        <div class="hero-text" style="text-align: center;">
            <span class="gestion-badge">Administration</span>
            <h1 style="margin-bottom: 0.5rem;">Tableau de bord</h1>
            <p class="hero-subtitle">Connecté en tant que <strong><?= e($email) ?></strong></p>
        </div>
        <div class="hero-form">
            <div class="login-card">
                <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">L’espace d’administration est en construction. Vous êtes connecté.</p>
                <a href="/gestion/deconnexion" class="btn-primary login-submit" style="display: block; text-align: center; text-decoration: none; box-sizing: border-box;">Se déconnecter</a>
            </div>
        </div>
    </div>
</main>
