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
        $onboardingStep = (int) ($row['onboarding_step'] ?? 1);
        $onboardingCompleted = (bool) ($row['onboarding_completed'] ?? false);
        $profilePercent = $onboardingCompleted ? 100 : round((($onboardingStep - 1) / 9) * 100);
    }
}
$onboardingStep = $onboardingStep ?? 1;
$onboardingCompleted = $onboardingCompleted ?? false;
$profilePercent = $profilePercent ?? 0;

// Photo / initial pour le footer (avatar)
$sidebarPhotoUrl = null;
$sidebarInitial = 'C';
if (isset($_SESSION['user_id'], $db)) {
    $stmtU = $db->prepare('SELECT first_name, photo_url FROM users WHERE id = ?');
    $stmtU->execute([$_SESSION['user_id']]);
    $rowU = $stmtU->fetch(PDO::FETCH_ASSOC);
    if ($rowU) {
        $sidebarPhotoUrl = !empty($rowU['photo_url']) ? trim($rowU['photo_url']) : null;
        $fn = trim($rowU['first_name'] ?? '');
        if ($fn !== '')
            $sidebarInitial = strtoupper(mb_substr($fn, 0, 1));
    }
}
?>
<div class="app-sidebar-backdrop" id="appSidebarBackdrop" aria-hidden="true"></div>
<aside class="app-sidebar">
    <a href="candidate-jobs.php" class="logo"
        style="margin-bottom: 2rem; display: block; text-align: center;">ciao<span>cv</span></a>
    <nav class="app-sidebar-nav">
        <div class="app-sidebar-link-wrapper">
            <?php if (!$onboardingCompleted): ?>
                <span class="notification-badge"><?= (int) $profilePercent ?>%</span>
            <?php endif; ?>
            <a href="candidate-profile.php"
                class="app-sidebar-link <?= $sidebarActive === 'profile' ? 'active' : '' ?> <?= !$onboardingCompleted ? 'profile-incomplete' : '' ?>">
                <span class="app-sidebar-link-icon">👤</span>
                <span>Mon profil</span>
            </a>
        </div>
        <a href="candidate-applications.php"
            class="app-sidebar-link <?= $sidebarActive === 'applications' ? 'active' : '' ?>">
            <span class="app-sidebar-link-icon">📋</span>
            <span>Mes candidatures</span>
        </a>
        <a href="candidate-jobs.php" class="app-sidebar-link <?= $sidebarActive === 'jobs' ? 'active' : '' ?>">
            <span class="app-sidebar-link-icon">💼</span>
            <span>JobMarket</span>
        </a>
    </nav>
    <div class="app-sidebar-footer">
        <div class="app-sidebar-footer-actions">
            <!-- Avatar déplacé dans le header à droite du switch -->
        </div>
    </div>
</aside>