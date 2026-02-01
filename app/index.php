<?php
/**
 * Page d'accueil - Connexion / Inscription
 * Si connecté : affiche le menu principal avec statut profil
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

// Vérifier si connecté
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_first_name'] ?? null;
$onboardingStep = 1;
$onboardingCompleted = false;
$profilePercent = 0;

// Charger les données utilisateur si connecté
if ($isLoggedIn && $db) {
    $stmt = $db->prepare('SELECT first_name, onboarding_step, onboarding_completed FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userData) {
        $userName = $userData['first_name'];
        $onboardingStep = (int)($userData['onboarding_step'] ?? 1);
        $onboardingCompleted = (bool)$userData['onboarding_completed'];
        $_SESSION['user_first_name'] = $userName;
        
        // Calculer le pourcentage (9 étapes au total)
        $profilePercent = $onboardingCompleted ? 100 : round((($onboardingStep - 1) / 9) * 100);
    }
}

// Mapping des étapes vers les URLs (pour utilisateur connecté, jamais signup)
function getOnboardingUrl($step, $isLoggedIn = true) {
    $urls = [
        1 => $isLoggedIn ? 'onboarding/step2-job-type.php' : 'onboarding/signup.php',
        2 => 'onboarding/step2-job-type.php',
        3 => 'onboarding/step3-skills.php',
        4 => 'onboarding/step4-personality.php',
        5 => 'onboarding/step5-availability.php',
        6 => 'onboarding/step6-video.php',
        7 => 'onboarding/step7-tests.php',
        8 => 'onboarding/step8-photo.php',
        9 => 'onboarding/complete.php',
    ];
    return $urls[$step] ?? 'onboarding/step2-job-type.php';
}

// Si profil complété, lien vers step2 pour modifier; sinon vers l'étape actuelle
$continueUrl = $onboardingCompleted 
    ? 'onboarding/step2-job-type.php' 
    : getOnboardingUrl($onboardingStep, $isLoggedIn);

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
                        $_SESSION['user_first_name'] = $user['first_name'];
                        
                        header('Location: index.php');
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>CiaoCV - Vidéo</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --bg: #111827;
            --card-bg: #1f2937;
            --text: #f9fafb;
            --text-light: #9ca3af;
            --border: #374151;
            --success: #22c55e;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }

        body {
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            width: 100%;
            max-width: 420px;
            padding: 2rem;
            text-align: center;
        }

        .logo {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.5rem;
            letter-spacing: -2px;
        }

        .tagline {
            color: var(--text-light);
            font-size: 1rem;
            margin-bottom: 2rem;
        }

        /* Auth header */
        .auth-header {
            position: absolute;
            top: 1rem;
            right: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            color: white;
            font-weight: 600;
        }

        .auth-btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s;
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-light);
        }

        .auth-btn:hover {
            border-color: var(--text-light);
            color: var(--text);
        }

        /* Profile status */
        .profile-status {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            text-decoration: none;
            display: block;
            transition: all 0.2s;
        }

        .profile-status:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        .profile-status-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .profile-status-title {
            color: var(--text);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .profile-status-percent {
            color: var(--primary);
            font-weight: 700;
            font-size: 0.9rem;
        }

        .profile-progress-bar {
            height: 8px;
            background: var(--border);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .profile-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--success));
            border-radius: 4px;
        }

        .profile-status-cta {
            color: var(--text-light);
            font-size: 0.8rem;
        }

        .profile-status.complete {
            border-color: var(--success);
            background: rgba(34, 197, 94, 0.1);
        }

        .profile-status.complete .profile-status-percent { color: var(--success); }
        .profile-status.complete .profile-status-cta { color: var(--success); }

        /* Menu */
        .menu {
            display: flex;
            flex-direction: column;
            gap: 1rem;
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
            font-size: 2rem;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(37, 99, 235, 0.2);
            border-radius: 0.75rem;
        }

        .menu-item:hover .menu-icon { background: rgba(255, 255, 255, 0.2); }

        .menu-text { flex: 1; text-align: left; }
        .menu-title { font-size: 1.125rem; font-weight: 600; margin-bottom: 0.25rem; }
        .menu-desc { font-size: 0.875rem; color: var(--text-light); }
        .menu-item:hover .menu-desc { color: rgba(255, 255, 255, 0.8); }
        .menu-arrow { font-size: 1.25rem; color: var(--text-light); }
        .menu-item:hover .menu-arrow { color: white; }

        /* Login form */
        .login-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 2rem;
            text-align: left;
        }

        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-size: 0.9rem; margin-bottom: 0.5rem; color: var(--text-light); }
        .form-group input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            background: var(--bg);
            color: var(--text);
            font-size: 1rem;
        }
        .form-group input:focus { outline: none; border-color: var(--primary); }

        .btn {
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn:hover { background: var(--primary-dark); }

        .error {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        .error a { color: #60a5fa; }

        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-light);
            font-size: 0.9rem;
        }
        .login-footer a { color: var(--primary); text-decoration: none; }
        .login-footer a:hover { text-decoration: underline; }

        .footer {
            margin-top: 2rem;
            color: var(--text-light);
            font-size: 0.75rem;
            text-align: center;
        }
        .footer a { color: var(--primary); text-decoration: none; }
    </style>
</head>
<body>
    <?php if ($isLoggedIn): ?>
    <!-- UTILISATEUR CONNECTÉ -->
    <div class="auth-header">
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($userName ?? 'U', 0, 1)) ?></div>
            <span><?= htmlspecialchars($userName ?? 'Utilisateur') ?></span>
        </div>
        <a href="?logout=1" class="auth-btn">Déconnexion</a>
    </div>

    <div class="container">
        <div class="logo">CiaoCV</div>
        <p class="tagline">Votre CV vidéo en 60 secondes</p>

        <nav class="menu">
            <a href="candidate.php" class="menu-item">
                <div class="menu-icon">👤</div>
                <div class="menu-text">
                    <div class="menu-title">Espace candidats</div>
                    <div class="menu-desc">Mon CV vidéo, mes candidatures</div>
                </div>
                <span class="menu-arrow">→</span>
            </a>

            <a href="employer.php" class="menu-item">
                <div class="menu-icon">🏢</div>
                <div class="menu-text">
                    <div class="menu-title">Espace employeur</div>
                    <div class="menu-desc">Gérer mes postes et candidats</div>
                </div>
                <span class="menu-arrow">→</span>
            </a>
        </nav>

        <div class="footer">
            <p>© 2026 CiaoCV — <a href="https://www.ciaocv.com">Retour au site</a></p>
        </div>
    </div>

    <?php else: ?>
    <!-- UTILISATEUR NON CONNECTÉ - FORMULAIRE LOGIN -->
    <div class="container">
        <div class="logo">CiaoCV</div>
        <p class="tagline">Votre CV vidéo en 60 secondes</p>

        <div class="login-card">
            <?php if ($error): ?>
                <div class="error"><?= $errorHtml ? $error : htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="action" value="connexion">
                <div class="form-group">
                    <label for="email">Courriel</label>
                    <input type="email" id="email" name="email" placeholder="ton@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn">Se connecter</button>
            </form>

            <div class="login-footer">
                Pas encore inscrit ? <a href="onboarding/signup.php">Créer un compte</a>
            </div>
        </div>

        <div class="footer">
            <p>© 2026 CiaoCV — <a href="https://www.ciaocv.com">Retour au site</a></p>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>
