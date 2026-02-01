<?php
/**
 * Espace Candidats - Tableau de bord candidat
 */
session_start();
require_once __DIR__ . '/db.php';

// Vérifier si connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Charger les données utilisateur
$userName = 'Candidat';
$onboardingStep = 1;
$onboardingCompleted = false;
$profilePercent = 0;

if ($db) {
    $stmt = $db->prepare('SELECT first_name, onboarding_step, onboarding_completed FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userData) {
        $userName = $userData['first_name'] ?: 'Candidat';
        $onboardingStep = (int)($userData['onboarding_step'] ?? 1);
        $onboardingCompleted = (bool)$userData['onboarding_completed'];
        $profilePercent = $onboardingCompleted ? 100 : round((($onboardingStep - 1) / 9) * 100);
    }
}

// URL pour modifier/continuer le profil
$continueUrl = $onboardingCompleted ? 'onboarding/step2-job-type.php' : 'onboarding/step' . max(2, $onboardingStep) . '-job-type.php';
if ($onboardingStep == 3) $continueUrl = 'onboarding/step3-skills.php';
elseif ($onboardingStep == 4) $continueUrl = 'onboarding/step4-personality.php';
elseif ($onboardingStep == 5) $continueUrl = 'onboarding/step5-availability.php';
elseif ($onboardingStep == 6) $continueUrl = 'onboarding/step6-video.php';
elseif ($onboardingStep == 7) $continueUrl = 'onboarding/step7-tests.php';
elseif ($onboardingStep == 8) $continueUrl = 'onboarding/step8-photo.php';
elseif ($onboardingStep >= 9 || $onboardingCompleted) $continueUrl = 'onboarding/step2-job-type.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Espace Candidat - CiaoCV</title>
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

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }

        body {
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        .container {
            max-width: 500px;
            margin: 0 auto;
            padding: 1.5rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0 2rem;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            text-decoration: none;
        }

        .back-link {
            color: var(--text-light);
            text-decoration: none;
            font-size: 0.9rem;
        }

        .back-link:hover { color: var(--primary); }

        .section-title {
            font-size: 0.85rem;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.75rem;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
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
            font-size: 1.75rem;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(37, 99, 235, 0.2);
            border-radius: 0.75rem;
        }

        .menu-item:hover .menu-icon { background: rgba(255, 255, 255, 0.2); }

        .menu-text { flex: 1; text-align: left; }

        .menu-title { font-size: 1.1rem; font-weight: 600; margin-bottom: 0.2rem; }

        .menu-desc { font-size: 0.85rem; color: var(--text-light); }

        .menu-item:hover .menu-desc { color: rgba(255, 255, 255, 0.8); }

        .menu-arrow { font-size: 1.2rem; color: var(--text-light); }

        .menu-item:hover .menu-arrow { color: white; }

        .footer {
            margin-top: 3rem;
            text-align: center;
            font-size: 0.8rem;
            color: var(--text-light);
        }

        .footer a { color: var(--primary); text-decoration: none; }

        /* Profile status */
        .profile-status {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            display: block;
            text-decoration: none;
            color: var(--text);
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
            height: 6px;
            background: var(--border);
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }
        .profile-progress-fill {
            height: 100%;
            background: var(--primary);
            border-radius: 3px;
            transition: width 0.3s ease;
        }
        .profile-status-cta {
            color: var(--text-light);
            font-size: 0.8rem;
        }
        .profile-status.complete {
            border-color: #22c55e;
            background: rgba(34, 197, 94, 0.1);
        }
        .profile-status.complete .profile-status-percent { color: #22c55e; }
        .profile-status.complete .profile-progress-fill { background: #22c55e; }
        .profile-status.complete .profile-status-cta { color: #22c55e; }

        /* Pastille notification */
        .menu-item-wrapper {
            position: relative;
        }
        .notification-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #ef4444;
            color: white;
            font-size: 0.7rem;
            font-weight: 700;
            min-width: 20px;
            height: 20px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        @supports (padding-top: env(safe-area-inset-top)) {
            .container { padding-top: calc(1.5rem + env(safe-area-inset-top)); }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <a href="index.php" class="logo">CiaoCV</a>
            <a href="index.php" class="back-link">← Accueil</a>
        </header>

        <p class="section-title">Espace candidat</p>

        <nav class="menu">
            <div class="menu-item-wrapper">
                <?php if (!$onboardingCompleted): ?>
                    <span class="notification-badge"><?= $profilePercent ?>%</span>
                <?php endif; ?>
                <a href="candidate-profile.php" class="menu-item <?= $onboardingCompleted ? '' : 'profile-incomplete' ?>">
                    <div class="menu-icon">👤</div>
                    <div class="menu-text">
                        <div class="menu-title">Mon profil</div>
                        <div class="menu-desc">
                            <?php if ($onboardingCompleted): ?>
                                Profil complété ✓
                            <?php else: ?>
                                Étape <?= $onboardingStep ?>/9 — À compléter
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="menu-arrow">→</span>
                </a>
            </div>

            <a href="candidate-applications.php" class="menu-item">
                <div class="menu-icon">📋</div>
                <div class="menu-text">
                    <div class="menu-title">Mes candidatures</div>
                    <div class="menu-desc">Postes où j'ai postulé</div>
                </div>
                <span class="menu-arrow">→</span>
            </a>

            <a href="candidate-jobs.php" class="menu-item">
                <div class="menu-icon">💼</div>
                <div class="menu-text">
                    <div class="menu-title">Offres disponibles</div>
                    <div class="menu-desc">Parcourir tous les postes</div>
                </div>
                <span class="menu-arrow">→</span>
            </a>
        </nav>

        <div class="footer">
            <p><a href="https://www.ciaocv.com">Retour au site principal</a></p>
        </div>
    </div>
</body>
</html>
