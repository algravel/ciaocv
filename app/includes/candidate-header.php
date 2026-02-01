<?php
/**
 * Top header candidat : hamburger, recherche, photo profil, déconnexion.
 */
$userPhotoUrl = null;
$userInitial = 'C';
if (isset($_SESSION['user_id'], $db)) {
    $stmt = $db->prepare('SELECT first_name, photo_url FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $userPhotoUrl = !empty($row['photo_url']) ? trim($row['photo_url']) : null;
        $fn = trim($row['first_name'] ?? '');
        if ($fn !== '') $userInitial = strtoupper(mb_substr($fn, 0, 1));
    }
}
?>
<header class="app-header">
    <div class="app-header-left">
        <button type="button" class="app-header-hamburger" id="appSidebarToggle" aria-label="Menu">☰</button>
        <input type="search" class="app-header-search" placeholder="Rechercher…" aria-label="Recherche">
    </div>
    <div class="app-header-right">
        <a href="employer.php" class="app-header-switch" aria-label="Espace employeur" title="Espace employeur">
            <svg class="app-header-switch-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
        </a>
        <a href="index.php?logout=1" class="app-header-logout" aria-label="Déconnexion" title="Déconnexion">
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
</header>
<script>
(function() {
    var btn = document.getElementById('appSidebarToggle');
    var shell = document.querySelector('.app-shell');
    var backdrop = document.getElementById('appSidebarBackdrop');
    function isMobile() { return window.innerWidth < 1024; }
    if (btn && shell) {
        btn.addEventListener('click', function() {
            if (isMobile()) {
                shell.classList.toggle('sidebar-open');
                document.body.style.overflow = shell.classList.contains('sidebar-open') ? 'hidden' : '';
            } else {
                shell.classList.toggle('sidebar-collapsed');
                try { localStorage.setItem('app-sidebar-collapsed', shell.classList.contains('sidebar-collapsed')); } catch (e) {}
            }
        });
        if (backdrop) {
            backdrop.addEventListener('click', function() {
                shell.classList.remove('sidebar-open');
                document.body.style.overflow = '';
            });
        }
        window.addEventListener('resize', function() {
            if (!isMobile()) {
                shell.classList.remove('sidebar-open');
                document.body.style.overflow = '';
            }
        });
        try {
            if (isMobile() === false && localStorage.getItem('app-sidebar-collapsed') === 'true') shell.classList.add('sidebar-collapsed');
        } catch (e) {}
    }
})();
</script>
