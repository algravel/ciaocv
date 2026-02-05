<?php
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

// Mapping des étapes vers les URLs
function getOnboardingUrl($step) {
    $urls = [
        1 => 'onboarding/signup.php',
        2 => 'onboarding/step2-job-type.php',
        3 => 'onboarding/step3-skills.php',
        4 => 'onboarding/step4-personality.php',
        5 => 'onboarding/step5-availability.php',
        6 => 'onboarding/step6-video.php',
        7 => 'onboarding/step7-tests.php',
        8 => 'onboarding/step8-photo.php',
        9 => 'onboarding/complete.php',
    ];
    return $urls[$step] ?? 'onboarding/signup.php';
}

$continueUrl = getOnboardingUrl($onboardingStep);
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
            --white: #ffffff;
            --text: #f9fafb;
            --text-light: #9ca3af;
            --border: #374151;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', system-ui, -apple-system, sans-serif;
        }

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
            max-width: 400px;
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
            margin-bottom: 3rem;
        }

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

        .menu-item:active {
            transform: translateY(0);
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

        .menu-item:hover .menu-icon {
            background: rgba(255, 255, 255, 0.2);
        }

        .menu-text {
            flex: 1;
            text-align: left;
        }

        .menu-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .menu-desc {
            font-size: 0.875rem;
            color: var(--text-light);
        }

        .menu-item:hover .menu-desc {
            color: rgba(255, 255, 255, 0.8);
        }

        .menu-arrow {
            font-size: 1.25rem;
            color: var(--text-light);
        }

        .menu-item:hover .menu-arrow {
            color: white;
        }

        .footer {
            margin-top: 3rem;
            color: var(--text-light);
            font-size: 0.75rem;
        }

        .footer a {
            color: var(--primary);
            text-decoration: none;
        }

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
        }

        .auth-btn.login {
            background: var(--primary);
            color: white;
        }

        .auth-btn.login:hover {
            background: var(--primary-dark);
        }

        .auth-btn.logout {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-light);
        }

        .auth-btn.logout:hover {
            border-color: var(--text-light);
            color: var(--text);
        }

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
            background: linear-gradient(90deg, var(--primary), #22c55e);
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        .profile-status-cta {
            color: var(--text-light);
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .profile-status.complete {
            border-color: #22c55e;
            background: rgba(34, 197, 94, 0.1);
        }

        .profile-status.complete .profile-status-percent {
            color: #22c55e;
        }

        .profile-status.complete .profile-status-cta {
            color: #22c55e;
        }

        /* iPhone safe area */
        @supports (padding-top: env(safe-area-inset-top)) {
            .container {
                padding-top: calc(2rem + env(safe-area-inset-top));
                padding-bottom: calc(2rem + env(safe-area-inset-bottom));
            }
        }
    </style>
</head>
<body>
    <div class="auth-header">
        <?php if ($isLoggedIn): ?>
            <div class="user-info">
                <div class="user-avatar"><?= strtoupper(substr($userName ?? 'U', 0, 1)) ?></div>
                <span><?= htmlspecialchars($userName ?? 'Utilisateur') ?></span>
            </div>
            <a href="?logout=1" class="auth-btn logout">Déconnexion</a>
        <?php else: ?>
            <a href="onboarding/signup.php" class="auth-btn login">Inscription</a>
            <a href="login.php" class="auth-btn logout">Connexion</a>
        <?php endif; ?>
    </div>

    <div class="container">
        <div class="logo">CiaoCV</div>
        <p class="tagline">Votre CV vidéo en 60 secondes</p>

        <?php if ($isLoggedIn): ?>
        <a href="<?= $continueUrl ?>" class="profile-status <?= $onboardingCompleted ? 'complete' : '' ?>">
            <div class="profile-status-header">
                <span class="profile-status-title">
                    <?= $onboardingCompleted ? 'Profil complété' : 'Compléter mon profil' ?>
                </span>
                <span class="profile-status-percent"><?= $profilePercent ?>%</span>
            </div>
            <div class="profile-progress-bar">
                <div class="profile-progress-fill" style="width: <?= $profilePercent ?>%"></div>
            </div>
            <div class="profile-status-cta">
                <?php if ($onboardingCompleted): ?>
                    Voir mon profil →
                <?php else: ?>
                    Étape <?= $onboardingStep ?>/9 — Continuer →
                <?php endif; ?>
            </div>
        </a>
        <?php endif; ?>

        <nav class="menu">
            <a href="candidate.php" class="menu-item">
                <div class="menu-icon">👤</div>
                <div class="menu-text">
                    <div class="menu-title">Espace candidats</div>
                    <div class="menu-desc">Enregistrer mon CV vidéo, mes candidatures</div>
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
</body>
</html>
