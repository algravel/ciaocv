<?php
/**
 * Header réutilisable pour l'onboarding avec barre de progression
 * 
 * Variables attendues:
 * - $currentStep (int) : étape actuelle (1-9)
 * - $stepTitle (string) : titre de l'étape
 */

$totalSteps = 9;
$progress = ($currentStep / $totalSteps) * 100;
?>

<header class="onboarding-header">
    <div class="header-top">
        <a href="../index.php" class="logo">ciao<span>cv</span></a>
        <span class="step-indicator"><?= $currentStep ?> / <?= $totalSteps ?></span>
    </div>
    <div class="progress-bar">
        <div class="progress-fill" style="width: <?= $progress ?>%"></div>
    </div>
</header>