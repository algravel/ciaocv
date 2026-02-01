<?php
/**
 * Vérification de l'étape d'onboarding
 * Inclure ce fichier au début de chaque étape
 * 
 * Variables requises avant l'inclusion:
 * - $requiredStep (int) : l'étape minimum requise pour accéder à cette page
 */

if (!isset($requiredStep)) {
    $requiredStep = 1;
}

// Vérifier la session
if (!isset($_SESSION['user_id'])) {
    header('Location: signup.php');
    exit;
}

// Charger l'étape actuelle de l'utilisateur
$userId = $_SESSION['user_id'];
$userOnboardingStep = 1;
$onboardingCompleted = false;

if ($db) {
    $stmt = $db->prepare('SELECT onboarding_step, onboarding_completed FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userData) {
        $userOnboardingStep = (int)($userData['onboarding_step'] ?? 1);
        $onboardingCompleted = (bool)$userData['onboarding_completed'];
    }
}

// Si onboarding complété, permettre l'édition (pas de redirection)
// L'utilisateur peut revoir et modifier ses réponses

// Si l'utilisateur n'a pas encore atteint cette étape, rediriger vers la bonne
// Exception: si connecté et à l'étape 1, on permet l'accès à step2 (compte déjà créé)
if ($userOnboardingStep < $requiredStep) {
    // Si l'utilisateur est à l'étape 1 et veut accéder à step2, c'est OK (il est connecté)
    if ($userOnboardingStep == 1 && $requiredStep == 2) {
        // Permettre l'accès, mettre à jour l'étape
        if ($db) {
            $db->prepare('UPDATE users SET onboarding_step = 2 WHERE id = ?')->execute([$userId]);
        }
    } else {
        $redirectUrls = [
            1 => 'step2-job-type.php', // Connecté = étape 1 terminée
            2 => 'step2-job-type.php',
            3 => 'step3-skills.php',
            4 => 'step4-personality.php',
            5 => 'step5-availability.php',
            6 => 'step6-video.php',
            7 => 'step7-tests.php',
            8 => 'step8-photo.php',
            9 => 'complete.php',
        ];
        
        header('Location: ' . ($redirectUrls[$userOnboardingStep] ?? 'step2-job-type.php'));
        exit;
    }
}
