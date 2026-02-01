<?php
/**
 * Sidebar employeur : logo, navigation, footer.
 * Variable attendue : $sidebarActive ('list' | 'create')
 */
if (!isset($sidebarActive)) {
    $sidebarActive = '';
}
?>
<div class="app-sidebar-backdrop" id="appSidebarBackdrop" aria-hidden="true"></div>
<aside class="app-sidebar">
    <a href="employer.php" class="app-sidebar-logo">CiaoCV</a>
    <nav class="app-sidebar-nav">
        <a href="employer.php" class="app-sidebar-link <?= $sidebarActive === 'list' ? 'active' : '' ?>">
            <span class="app-sidebar-link-icon">📋</span>
            <span>Mes postes</span>
        </a>
        <a href="employer-job-create.php" class="app-sidebar-link <?= $sidebarActive === 'create' ? 'active' : '' ?>">
            <span class="app-sidebar-link-icon">➕</span>
            <span>Créer un poste</span>
        </a>
    </nav>
    <div class="app-sidebar-footer">
        <a href="https://www.ciaocv.com">Retour au site principal</a>
    </div>
</aside>
