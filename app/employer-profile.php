<?php
/**
 * Profil employeur (entreprise) - nom, description, vidéo corporative, logo.
 */
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$success = null;
$error = null;

// S'assurer que les colonnes profil employeur existent
if ($db) {
    $cols = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    $employerCols = ['company_name', 'company_description', 'company_description_visible', 'company_video_url', 'company_logo_url'];
    foreach ($employerCols as $c) {
        if (!in_array($c, $cols)) {
            if ($c === 'company_name') {
                $db->exec("ALTER TABLE users ADD COLUMN company_name VARCHAR(255) DEFAULT NULL AFTER preferred_language");
            } elseif ($c === 'company_description') {
                $db->exec("ALTER TABLE users ADD COLUMN company_description TEXT DEFAULT NULL AFTER company_name");
            } elseif ($c === 'company_description_visible') {
                $db->exec("ALTER TABLE users ADD COLUMN company_description_visible TINYINT(1) DEFAULT 1 AFTER company_description");
            } elseif ($c === 'company_video_url') {
                $db->exec("ALTER TABLE users ADD COLUMN company_video_url VARCHAR(500) DEFAULT NULL AFTER company_description_visible");
            } elseif ($c === 'company_logo_url') {
                $db->exec("ALTER TABLE users ADD COLUMN company_logo_url VARCHAR(500) DEFAULT NULL AFTER company_video_url");
            }
        }
    }
}

$user = null;
if ($db) {
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$user) {
    header('Location: index.php');
    exit;
}

$companyName = trim($user['company_name'] ?? '');
$companyDescription = trim($user['company_description'] ?? '');
$companyDescriptionVisible = !empty($user['company_description_visible']);
$companyVideoUrl = trim($user['company_video_url'] ?? '');
$companyLogoUrl = trim($user['company_logo_url'] ?? '');
$email = $user['email'] ?? '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_employer_profile') {
    $companyName = trim($_POST['company_name'] ?? '');
    $companyDescription = trim($_POST['company_description'] ?? '');
    $companyDescriptionVisible = isset($_POST['company_description_visible']) && $_POST['company_description_visible'] === '1';
    $companyVideoUrl = trim($_POST['company_video_url'] ?? '');
    $companyLogoUrl = trim($_POST['company_logo_url'] ?? '');

    if ($companyVideoUrl !== '' && !filter_var($companyVideoUrl, FILTER_VALIDATE_URL)) {
        $error = 'L’URL de la vidéo corporative n’est pas valide.';
    } elseif ($companyLogoUrl !== '' && !filter_var($companyLogoUrl, FILTER_VALIDATE_URL)) {
        $error = 'L’URL du logo n’est pas valide.';
    } else {
        try {
            $db->prepare('UPDATE users SET company_name = ?, company_description = ?, company_description_visible = ?, company_video_url = ?, company_logo_url = ? WHERE id = ?')
               ->execute([$companyName ?: null, $companyDescription ?: null, $companyDescriptionVisible ? 1 : 0, $companyVideoUrl ?: null, $companyLogoUrl ?: null, $userId]);
            $success = 'Profil entreprise mis à jour.';
            $user['company_name'] = $companyName;
            $user['company_description'] = $companyDescription;
            $user['company_description_visible'] = $companyDescriptionVisible ? 1 : 0;
            $user['company_video_url'] = $companyVideoUrl;
            $user['company_logo_url'] = $companyLogoUrl;
        } catch (PDOException $e) {
            $error = 'Erreur lors de la mise à jour.';
        }
    }
}

// Recharger après POST
$companyName = trim($user['company_name'] ?? '');
$companyDescription = trim($user['company_description'] ?? '');
$companyDescriptionVisible = !empty($user['company_description_visible']);
$companyVideoUrl = trim($user['company_video_url'] ?? '');
$companyLogoUrl = trim($user['company_logo_url'] ?? '');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil entreprise - CiaoCV</title>
    <link rel="stylesheet" href="assets/css/design-system.css?v=<?= ASSET_VERSION ?>">
</head>
<body>
    <div class="app-shell">
        <?php $sidebarActive = 'profile'; include __DIR__ . '/includes/employer-sidebar.php'; ?>
        <main class="app-main">
        <?php include __DIR__ . '/includes/employer-header.php'; ?>
        <div class="app-main-content layout-app layout-app-wide">

        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- En-tête du profil entreprise -->
        <div class="profile-header employer-profile-header">
            <div class="profile-photo employer-logo-wrap">
                <?php if ($companyLogoUrl): ?>
                    <img src="<?= htmlspecialchars($companyLogoUrl) ?>" alt="Logo <?= htmlspecialchars($companyName ?: 'entreprise') ?>" class="employer-logo-img">
                <?php else: ?>
                    <div class="placeholder">🏢</div>
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <h3><?= htmlspecialchars($companyName ?: 'Entreprise') ?></h3>
                <p><?= htmlspecialchars($email) ?></p>
                <?php if ($companyDescription && $companyDescriptionVisible): ?>
                    <p class="profile-description-preview"><?= nl2br(htmlspecialchars(mb_substr($companyDescription, 0, 200) . (mb_strlen($companyDescription) > 200 ? '…' : ''))) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Informations entreprise -->
        <div class="section">
            <h2>Informations <span class="section-edit-hint">Modifier →</span></h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_employer_profile">
                <input type="hidden" name="company_video_url" value="<?= htmlspecialchars($companyVideoUrl) ?>">
                <input type="hidden" name="company_logo_url" value="<?= htmlspecialchars($companyLogoUrl) ?>">
                <div class="form-group">
                    <label for="company_name">Nom de l'entreprise</label>
                    <input type="text" id="company_name" name="company_name" value="<?= htmlspecialchars($companyName) ?>" placeholder="Nom de votre entreprise">
                </div>
                <div class="form-group">
                    <label for="company_description">Description de l'entreprise</label>
                    <textarea id="company_description" name="company_description" rows="5" placeholder="Présentez votre entreprise aux candidats…"><?= htmlspecialchars($companyDescription) ?></textarea>
                </div>
                <div class="form-group form-group-checkbox">
                    <label>
                        <input type="checkbox" name="company_description_visible" value="1" <?= $companyDescriptionVisible ? 'checked' : '' ?>>
                        Afficher la description aux candidats (sinon masquée)
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">Sauvegarder</button>
            </form>
        </div>

        <!-- Logo -->
        <div class="section">
            <h2>Logo</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_employer_profile">
                <input type="hidden" name="company_name" value="<?= htmlspecialchars($companyName) ?>">
                <input type="hidden" name="company_description" value="<?= htmlspecialchars($companyDescription) ?>">
                <input type="hidden" name="company_description_visible" value="<?= $companyDescriptionVisible ? '1' : '0' ?>">
                <input type="hidden" name="company_video_url" value="<?= htmlspecialchars($companyVideoUrl) ?>">
                <div class="form-group">
                    <label for="company_logo_url">URL du logo</label>
                    <input type="url" id="company_logo_url" name="company_logo_url" value="<?= htmlspecialchars($companyLogoUrl) ?>" placeholder="https://…">
                </div>
                <?php if ($companyLogoUrl): ?>
                    <div class="employer-logo-preview">
                        <img src="<?= htmlspecialchars($companyLogoUrl) ?>" alt="Aperçu logo" style="max-width: 120px; max-height: 120px; object-fit: contain;">
                    </div>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">Sauvegarder le logo</button>
            </form>
        </div>

        <!-- Vidéo corporative -->
        <div class="section">
            <h2>Vidéo corporative</h2>
            <div class="video-preview">
                <?php if ($companyVideoUrl): ?>
                    <video controls src="<?= htmlspecialchars($companyVideoUrl) ?>"></video>
                <?php else: ?>
                    <div class="no-video">
                        <span>🎬</span>
                        <p>Aucune vidéo corporative</p>
                    </div>
                <?php endif; ?>
            </div>
            <form method="POST" class="form-inline-video">
                <input type="hidden" name="action" value="update_employer_profile">
                <input type="hidden" name="company_name" value="<?= htmlspecialchars($companyName) ?>">
                <input type="hidden" name="company_description" value="<?= htmlspecialchars($companyDescription) ?>">
                <input type="hidden" name="company_description_visible" value="<?= $companyDescriptionVisible ? '1' : '0' ?>">
                <input type="hidden" name="company_logo_url" value="<?= htmlspecialchars($companyLogoUrl) ?>">
                <div class="form-group">
                    <label for="company_video_url">URL de la vidéo</label>
                    <input type="url" id="company_video_url" name="company_video_url" value="<?= htmlspecialchars($companyVideoUrl) ?>" placeholder="https://…">
                </div>
                <button type="submit" class="btn btn-primary">Sauvegarder la vidéo</button>
            </form>
        </div>

        </div>
        </main>
    </div>
</body>
</html>
