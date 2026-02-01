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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Candidat - CiaoCV</title>
    <link rel="stylesheet" href="assets/css/design-system.css?v=<?= ASSET_VERSION ?>">
</head>
<body>
    <div class="app-shell">
        <?php $sidebarActive = 'dashboard'; include __DIR__ . '/includes/candidate-sidebar.php'; ?>
        <main class="app-main">
        <?php include __DIR__ . '/includes/candidate-header.php'; ?>
        <div class="app-main-content layout-app">
            <p class="section-title">Espace candidat</p>
        </div>
        </main>
    </div>
</body>
</html>
