<?php
/**
 * Top header employeur : hamburger, recherche, photo profil, déconnexion.
 */
$employerPhotoUrl = null;
$employerInitial = 'EM';
if (isset($_SESSION['user_id'], $db)) {
    $stmt = $db->prepare('SELECT first_name, photo_url FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $employerPhotoUrl = !empty($row['photo_url']) ? trim($row['photo_url']) : null;
        $fn = trim($row['first_name'] ?? '');
        if ($fn !== '') $employerInitial = strtoupper(mb_substr($fn, 0, 1));
    }
}
?>
<header class="app-header">
    <div class="app-header-left">
        <button type="button" class="app-header-hamburger" id="appSidebarToggle" aria-label="Menu">☰</button>
        <input type="search" class="app-header-search" placeholder="Rechercher…" aria-label="Recherche">
    </div>
    <div class="app-header-right">
        <div class="app-header-avatar-wrap">
            <span class="avatar avatar-status-wrap">
                <?php if ($employerPhotoUrl): ?>
                    <img src="<?= htmlspecialchars($employerPhotoUrl) ?>" alt="" class="avatar-img">
                <?php else: ?>
                    <?= htmlspecialchars($employerInitial) ?>
                <?php endif; ?>
                <span class="avatar-status" aria-hidden="true"></span>
            </span>
        </div>
        <a href="index.php?logout=1" class="app-header-logout" aria-label="Déconnexion" title="Déconnexion">🚪</a>
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
