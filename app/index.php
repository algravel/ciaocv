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
$userPhotoUrl = null;
$userInitial = 'U';
if ($isLoggedIn && $db) {
    $stmt = $db->prepare('SELECT first_name, email, photo_url, onboarding_step, onboarding_completed FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userData) {
        $userName = trim($userData['first_name'] ?? '');
        if ($userName === '') {
            $email = $userData['email'] ?? $_SESSION['user_email'] ?? '';
            $userName = $email !== '' ? (strstr($email, '@', true) ?: $email) : 'Utilisateur';
        }
        $userPhotoUrl = !empty($userData['photo_url']) ? trim($userData['photo_url']) : null;
        $userInitial = $userName ? strtoupper(mb_substr($userName, 0, 1)) : 'U';
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
                        $firstName = trim($user['first_name'] ?? '');
                        $_SESSION['user_first_name'] = $firstName !== '' ? $firstName : (strstr($user['email'], '@', true) ?: $user['email']);
                        
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CiaoCV - Vidéo</title>
    <link rel="stylesheet" href="assets/css/design-system.css?v=<?= ASSET_VERSION ?>">
</head>
<body class="layout-auth-page">
    <?php if ($isLoggedIn): ?>
    <!-- UTILISATEUR CONNECTÉ -->
    <div class="auth-header index-header-right">
        <a href="?logout=1" class="app-header-logout" aria-label="Déconnexion" title="Déconnexion">
            <svg class="app-header-logout-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        </a>
        <div class="app-header-avatar-wrap">
            <span class="avatar avatar-status-wrap">
                <?php if ($userPhotoUrl): ?>
                    <img src="<?= htmlspecialchars($userPhotoUrl) ?>" alt="" class="avatar-img">
                <?php else: ?>
                    <?= htmlspecialchars($userInitial) ?>
                <?php endif; ?>
                <span class="avatar-status" aria-hidden="true"></span>
            </span>
        </div>
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
                <a href="forgot-password.php">Mot de passe oublié ?</a><br>
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
