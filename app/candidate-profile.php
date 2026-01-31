<?php
/**
 * Maquette : profil candidat + gestion des présentations (max 3)
 */
$demo = include __DIR__ . '/demo-data.php';
$profile = $demo['my_profile'] ?? ['name' => '', 'email' => '', 'presentations' => [['title' => '', 'has_video' => false], ['title' => '', 'has_video' => false], ['title' => '', 'has_video' => false]]];
$presentations = $profile['presentations'] ?? [];
while (count($presentations) < 3) $presentations[] = ['title' => '', 'has_video' => false];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon profil - CiaoCV</title>
    <style>
        :root { --primary: #2563eb; --primary-dark: #1e40af; --bg: #111827; --card-bg: #1f2937; --text: #f9fafb; --text-light: #9ca3af; --border: #374151; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: system-ui, sans-serif; }
        body { background: var(--bg); color: var(--text); min-height: 100vh; }
        .container { max-width: 600px; margin: 0 auto; padding: 1.5rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .logo { font-size: 1.25rem; font-weight: 800; color: var(--primary); text-decoration: none; }
        .back { color: var(--text-light); text-decoration: none; }
        .back:hover { color: var(--primary); }
        .section { background: var(--card-bg); border: 1px solid var(--border); border-radius: 1rem; padding: 1.5rem; margin-bottom: 1.5rem; }
        .section h2 { font-size: 1.1rem; margin-bottom: 1rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-size: 0.9rem; margin-bottom: 0.5rem; color: var(--text-light); }
        .form-group input { width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 0.5rem; background: #111; color: var(--text); }
        .pres-slot { display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #111; border-radius: 0.5rem; margin-bottom: 0.75rem; }
        .pres-slot .thumb { width: 50px; height: 50px; background: #374151; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; color: var(--text-light); flex-shrink: 0; }
        .pres-slot .info { flex: 1; }
        .pres-slot .info strong { display: block; margin-bottom: 0.2rem; }
        .pres-slot .info span { font-size: 0.85rem; color: var(--text-light); }
        .btn { display: inline-block; padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.9rem; text-decoration: none; font-weight: 600; border: none; cursor: pointer; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text); }
        .btn-outline:hover { border-color: var(--primary); color: var(--primary); }
        .footer { margin-top: 2rem; text-align: center; font-size: 0.8rem; color: var(--text-light); }
        .footer a { color: var(--primary); }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <a href="candidate.php" class="logo">CiaoCV</a>
            <a href="candidate.php" class="back">← Espace candidat</a>
        </header>

        <div style="background:rgba(37,99,235,0.2);color:var(--primary);padding:0.75rem 1rem;border-radius:8px;margin-bottom:1.5rem;font-size:0.9rem;">Mode démo.</div>

        <div class="section">
            <h2>Mon profil</h2>
            <div class="form-group">
                <label>Nom</label>
                <input type="text" value="<?= htmlspecialchars($profile['name']) ?>" placeholder="Votre nom">
            </div>
            <div class="form-group">
                <label>Courriel</label>
                <input type="email" value="<?= htmlspecialchars($profile['email']) ?>" placeholder="votre@email.com">
            </div>
            <button type="button" class="btn btn-primary">Sauvegarder</button>
        </div>

        <div class="section">
            <h2>Mes présentations (3 max)</h2>
            <p style="font-size:0.9rem;color:var(--text-light);margin-bottom:1rem;">Différentes vidéos que vous pouvez utiliser pour postuler.</p>
            <?php foreach ($presentations as $i => $p): ?>
            <div class="pres-slot">
                <div class="thumb"><?= $p['has_video'] ? '▶' : '+' ?></div>
                <div class="info">
                    <strong><?= htmlspecialchars($p['title'] ?: 'Slot ' . ($i + 1)) ?></strong>
                    <span><?= $p['has_video'] ? 'Vidéo enregistrée' : 'Vide' ?></span>
                </div>
                <?php if ($p['has_video']): ?>
                    <a href="view.php" class="btn btn-outline">Voir</a>
                <?php else: ?>
                    <a href="record.php" class="btn btn-primary">Enregistrer</a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="footer">
            <a href="https://www.ciaocv.com">Retour au site principal</a>
        </div>
    </div>
</body>
</html>
