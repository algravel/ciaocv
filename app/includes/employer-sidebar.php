<?php
/**
 * Sidebar employeur : logo, navigation, footer.
 * Variable attendue : $sidebarActive ('list' | 'create' | 'profile' | 'positions')
 */
if (!isset($sidebarActive)) {
    $sidebarActive = '';
}
$sidebarPhotoUrl = null;
$sidebarInitial = 'E';
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
    <a href="employer.php" class="logo"
        style="margin-bottom: 2rem; display: block; text-align: center;">ciao<span>cv</span></a>
    <nav class="app-sidebar-nav">
        <a href="employer-profile.php" class="app-sidebar-link <?= $sidebarActive === 'profile' ? 'active' : '' ?>">
            <span class="app-sidebar-link-icon">🏢</span>
            <span>Mon entreprise</span>
        </a>
        <a href="employer-positions.php" class="app-sidebar-link <?= $sidebarActive === 'positions' ? 'active' : '' ?>">
            <span class="app-sidebar-link-icon">📄</span>
            <span>Mes postes</span>
        </a>
        <a href="employer.php" class="app-sidebar-link <?= $sidebarActive === 'list' ? 'active' : '' ?>">
            <span class="app-sidebar-link-icon">📋</span>
            <span>Mes affichages</span>
        </a>
        <a href="employer-job-create.php" class="app-sidebar-link <?= $sidebarActive === 'create' ? 'active' : '' ?>">
            <span class="app-sidebar-link-icon">➕</span>
            <span>Créer un poste</span>
        </a>
    </nav>
    <div class="app-sidebar-footer">
        <div class="app-sidebar-footer-actions">
            <!-- Avatar déplacé dans le header à droite du switch -->
        </div>
    </div>
</aside>