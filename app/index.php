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
// TRAITEMENT CONNEXION (si non connecté)
// =====================
$error = null;
$errorHtml = false;

if (!$isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'connexion') {
        if (!$db) {
            $error = 'Service temporairement indisponible.';
        } else {
            $email = strtolower(trim($_POST['email'] ?? ''));
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                $error = 'Veuillez entrer votre courriel et mot de passe.';
            } else {
                try {
                    $stmt = $db->prepare('SELECT id, email, first_name, password_hash, email_verified, onboarding_step, onboarding_completed FROM users WHERE email = ?');
                    $stmt->execute([$email]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$user) {
                        $error = 'Courriel ou mot de passe incorrect.';
                    } elseif (!password_verify($password, $user['password_hash'])) {
                        $error = 'Courriel ou mot de passe incorrect.';
                    } elseif (!$user['email_verified']) {
                        $error = 'Compte non confirmé. <a href="onboarding/confirm.php?email=' . urlencode($email) . '">Confirmer</a>';
                        $errorHtml = true;
                    } else {
                        // Connexion réussie
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_email'] = $user['email'];
                        $firstName = trim($user['first_name'] ?? '');
                        $_SESSION['user_first_name'] = $firstName !== '' ? $firstName : (strstr($user['email'], '@', true) ?: $user['email']);

                        header('Location: candidate-jobs.php');
                        exit;
                    }
                } catch (PDOException $e) {
                    $error = 'Erreur. Réessayez plus tard.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - CiaoCV</title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link rel="stylesheet" href="assets/css/design-system.css?v=<?= ASSET_VERSION ?>">
    <style>
        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            letter-spacing: -0.025em;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        @media (max-width: 900px) {
            .hero-title {
                font-size: 2.5rem;
            }
        }
    </style>
</head>

<body>
    <!-- HEADER -->
    <header class="navbar">
        <a href="../index.html" class="logo">ciao<span>cv</span></a>

        <nav class="nav-links">
            <a href="../tarifs.html">Notre service</a>
            <a href="../guide-candidat.html">Préparez votre entrevue</a>
        </nav>

        <div class="nav-actions">
            <a href="onboarding/signup.php" class="btn-header-secondary">Espace Recruteur</a>
            <a href="onboarding/signup.php" class="btn-header-primary">Espace Candidat</a>

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
        <a href="../tarifs.html" onclick="toggleMenu()">Notre service</a>
        <a href="../guide-candidat.html" onclick="toggleMenu()">Préparez votre entrevue</a>
        <div style="margin-top:2rem; display:flex; flex-direction:column; gap:1rem; width:80%; text-align:center;">
            <a href="onboarding/signup.php" class="btn-header-primary" style="font-size:1.1rem; padding:1rem;">Espace
                Candidat</a>
            <a href="onboarding/signup.php" class="btn-header-secondary"
                style="font-size:1.1rem; padding:1rem; color:var(--text-gray);">Espace Recruteur</a>
        </div>
    </div>

    <!-- MAIN CONTENT (HERO) -->
    <main class="hero">
        <div class="hero-text" style="text-align: left;">
            <h1 class="hero-title">Content de vous revoir !</h1>
            <p class="hero-subtitle">Accédez à votre espace pour gérer vos entrevues vidéo et vos candidatures en toute
                simplicité.</p>

            <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; margin-top: 2rem;">
                <div
                    style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.95rem; font-weight: 500; color: var(--text-secondary);">
                    <span style="color: var(--primary); font-size: 1.2rem;">✓</span> Rapide
                </div>
                <div
                    style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.95rem; font-weight: 500; color: var(--text-secondary);">
                    <span style="color: var(--primary); font-size: 1.2rem;">✓</span> Sécurisé
                </div>
                <div
                    style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.95rem; font-weight: 500; color: var(--text-secondary);">
                    <span style="color: var(--primary); font-size: 1.2rem;">✓</span> Humain
                </div>
            </div>
        </div>

        <div class="hero-form">
            <div class="login-card glass"
                style="padding: 2.5rem; border-radius: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.05);">
                <h2
                    style="font-size: 1.75rem; font-weight: 800; margin-bottom: 2rem; text-align: center; letter-spacing: -0.02em;">
                    Connexion</h2>

                <?php if ($error): ?>
                    <div class="error"
                        style="background: var(--error-bg); color: var(--error); padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; font-size: 0.9rem;">
                        <?= $errorHtml ? $error : htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="action" value="connexion">
                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label for="email"
                            style="font-weight: 600; margin-bottom: 0.5rem; display: block;">Courriel</label>
                        <input type="email" id="email" name="email" placeholder="ton@email.com" required
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            style="border-radius: 12px; padding: 0.85rem 1rem; border: 1px solid var(--border);">
                    </div>
                    <div class="form-group" style="margin-bottom: 1.75rem;">
                        <label for="password" style="font-weight: 600; margin-bottom: 0.5rem; display: block;">Mot de
                            passe</label>
                        <input type="password" id="password" name="password" placeholder="••••••••" required
                            style="border-radius: 12px; padding: 0.85rem 1rem; border: 1px solid var(--border);">
                    </div>
                    <button type="submit" class="btn btn-primary"
                        style="width: 100%; padding: 1.1rem; border-radius: 50px; font-weight: 700; font-size: 1.05rem;">Se
                        connecter</button>
                </form>

                <div class="login-footer" style="margin-top: 1.5rem; text-align: center;">
                    <a href="forgot-password.php"
                        style="font-size: 0.85rem; color: var(--text-secondary); text-decoration: none;">Mot de passe
                        oublié ?</a>
                    <p style="margin-top: 1.25rem; font-size: 0.95rem; color: var(--text-secondary);">
                        Pas encore de compte ? <a href="onboarding/signup.php"
                            style="color: var(--primary); font-weight: 700; text-decoration: none;">S'inscrire
                            gratuitement</a>
                    </p>
                </div>

                <div class="oauth-divider"
                    style="margin: 2rem 0; display: flex; align-items: center; gap: 1rem; color: var(--text-muted);">
                    <span style="flex: 1; height: 1px; background: var(--border);"></span>
                    <span
                        style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">ou</span>
                    <span style="flex: 1; height: 1px; background: var(--border);"></span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <a href="oauth-google.php?action=login" class="btn-header-secondary"
                        style="display: flex; align-items: center; justify-content: center; gap: 0.75rem; width: 100%; border-radius: 12px; font-size: 0.95rem; padding: 0.85rem;">
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
                        Continuer avec Google
                    </a>
                </div>
            </div>
        </div>
    </main>

    <footer style="padding: 5rem 5% 3rem; background: #0F172A; color: white; text-align: center;">
        <div class="logo" style="color: white; font-size: 2.25rem; margin-bottom: 2.5rem; display: block;">ciao<span
                style="color:white">cv</span></div>
        <p style="opacity: 0.7; font-size: 0.95rem; margin-bottom: 0.5rem;">© 2026 CiaoCV — Un service de 3W Capital</p>
        <p style="font-size: 1rem;">Fièrement humain ❤️</p>
    </footer>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('mobileMenu');
            const hamburgers = document.querySelectorAll('.hamburger');
            menu.classList.toggle('active');
            hamburgers.forEach(h => h.classList.toggle('active'));
            document.body.style.overflow = menu.classList.contains('active') ? 'hidden' : '';
        }
    </script>
</body>

</html>