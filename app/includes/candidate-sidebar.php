<?php
/**
 * Sidebar candidat : logo, navigation, footer.
 * Variables attendues : $sidebarActive ('dashboard' | 'profile' | 'applications' | 'jobs')
 * Optionnel : $onboardingCompleted, $onboardingStep, $profilePercent (sinon chargés depuis la DB)
 */
if (!isset($sidebarActive)) {
    $sidebarActive = '';
}
if ((!isset($onboardingCompleted) || !isset($onboardingStep)) && isset($db, $_SESSION['user_id'])) {
    $stmt = $db->prepare('SELECT onboarding_step, onboarding_completed FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $onboardingStep = (int)($row['onboarding_step'] ?? 1);
        $onboardingCompleted = (bool)($row['onboarding_completed'] ?? false);
        $profilePercent = $onboardingCompleted ? 100 : round((($onboardingStep - 1) / 9) * 100);
    }
}
$onboardingStep = $onboardingStep ?? 1;
$onboardingCompleted = $onboardingCompleted ?? false;
$profilePercent = $profilePercent ?? 0;
?>
<div class="app-sidebar-backdrop" id="appSidebarBackdrop" aria-hidden="true"></div>
<aside class="app-sidebar">
    <a href="candidate-jobs.php" class="app-sidebar-logo">CiaoCV</a>
    <nav class="app-sidebar-nav">
        <div class="app-sidebar-link-wrapper">
            <?php if (!$onboardingCompleted): ?>
                <span class="notification-badge"><?= (int)$profilePercent ?>%</span>
            <?php endif; ?>
            <a href="candidate-profile.php" class="app-sidebar-link <?= $sidebarActive === 'profile' ? 'active' : '' ?> <?= !$onboardingCompleted ? 'profile-incomplete' : '' ?>">
                <span class="app-sidebar-link-icon">👤</span>
                <span>Mon profil</span>
            </a>
        </div>
        <a href="candidate-applications.php" class="app-sidebar-link <?= $sidebarActive === 'applications' ? 'active' : '' ?>">
            <span class="app-sidebar-link-icon">📋</span>
            <span>Mes candidatures</span>
        </a>
        <a href="candidate-jobs.php" class="app-sidebar-link <?= $sidebarActive === 'jobs' ? 'active' : '' ?>">
            <span class="app-sidebar-link-icon">💼</span>
            <span>JobMarket</span>
        </a>
    </nav>
    <div class="app-sidebar-footer">
        <a href="https://www.ciaocv.com">Retour au site principal</a>
    </div>
</aside>
