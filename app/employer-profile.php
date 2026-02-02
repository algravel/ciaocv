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
$success = $_SESSION['flash_success'] ?? null;
$error = $_SESSION['flash_error'] ?? null;
if (isset($_SESSION['flash_success']))
    unset($_SESSION['flash_success']);
if (isset($_SESSION['flash_error']))
    unset($_SESSION['flash_error']);

// S'assurer que les colonnes profil employeur et préférences existent
if ($db) {
    $cols = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('preferred_language', $cols)) {
        try {
            $db->exec("ALTER TABLE users ADD COLUMN preferred_language VARCHAR(10) DEFAULT 'fr'");
        } catch (PDOException $e) {
        }
    }
    if (!in_array('phone', $cols)) {
        try {
            $db->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(50) DEFAULT NULL AFTER email");
        } catch (PDOException $e) {
        }
    }
    $employerCols = ['company_name', 'company_description', 'company_description_visible', 'company_video_url', 'company_logo_url', 'company_website_url'];
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
            } elseif ($c === 'company_website_url') {
                $db->exec("ALTER TABLE users ADD COLUMN company_website_url VARCHAR(500) DEFAULT NULL AFTER company_logo_url");
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
$companyWebsiteUrl = trim($user['company_website_url'] ?? '');
$email = $user['email'] ?? '';
$phone = trim($user['phone'] ?? '');
$firstName = trim($user['first_name'] ?? '');
$preferredLanguage = $user['preferred_language'] ?? 'fr';
$photoUrl = !empty($user['photo_url']) ? trim($user['photo_url']) : null;

// API upload logo (B2)
$b2KeyId = $_ENV['B2_KEY_ID'] ?? '';
$b2AppKey = $_ENV['B2_APPLICATION_KEY'] ?? '';
$b2BucketId = $_ENV['B2_BUCKET_ID'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'get_upload_url_logo') {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['error' => 'Non autorisé']);
            exit;
        }
        if (!$b2KeyId || !$b2AppKey || !$b2BucketId) {
            echo json_encode(['error' => 'Config B2 manquante']);
            exit;
        }
        $authUrl = 'https://api.backblazeb2.com/b2api/v2/b2_authorize_account';
        $credentials = base64_encode($b2KeyId . ':' . $b2AppKey);
        $ch = curl_init($authUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $authResponse = json_decode(curl_exec($ch), true);
        curl_close($ch);
        if (!isset($authResponse['authorizationToken'])) {
            echo json_encode(['error' => 'Échec auth B2']);
            exit;
        }
        $getUploadUrl = $authResponse['apiUrl'] . '/b2api/v2/b2_get_upload_url';
        $ch = curl_init($getUploadUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $authResponse['authorizationToken'],
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['bucketId' => $b2BucketId]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $uploadUrlResponse = json_decode(curl_exec($ch), true);
        curl_close($ch);
        $fileName = 'logo_' . $userId . '_' . date('Y-m-d_H-i-s') . '.jpg';
        echo json_encode([
            'uploadUrl' => $uploadUrlResponse['uploadUrl'],
            'authToken' => $uploadUrlResponse['authorizationToken'],
            'fileName' => $fileName,
            'downloadUrl' => $authResponse['downloadUrl']
        ]);
        exit;
    }
    if ($_POST['action'] === 'save_logo') {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['error' => 'Non autorisé']);
            exit;
        }
        $logoUrl = trim($_POST['logo_url'] ?? '');
        if ($db && $logoUrl !== '') {
            $db->prepare('UPDATE users SET company_logo_url = ? WHERE id = ?')->execute([$logoUrl, $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'URL invalide']);
        }
        exit;
    }
    if ($_POST['action'] === 'get_upload_url_video') {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['error' => 'Non autorisé']);
            exit;
        }
        if (!$b2KeyId || !$b2AppKey || !$b2BucketId) {
            echo json_encode(['error' => 'Config B2 manquante']);
            exit;
        }
        $ext = preg_replace('/[^a-z0-9]/i', '', $_POST['ext'] ?? 'webm');
        if ($ext === '')
            $ext = 'webm';
        $authUrl = 'https://api.backblazeb2.com/b2api/v2/b2_authorize_account';
        $credentials = base64_encode($b2KeyId . ':' . $b2AppKey);
        $ch = curl_init($authUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $authResponse = json_decode(curl_exec($ch), true);
        curl_close($ch);
        if (!isset($authResponse['authorizationToken'])) {
            echo json_encode(['error' => 'Échec auth B2']);
            exit;
        }
        $getUploadUrl = $authResponse['apiUrl'] . '/b2api/v2/b2_get_upload_url';
        $ch = curl_init($getUploadUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $authResponse['authorizationToken'],
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['bucketId' => $b2BucketId]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $uploadUrlResponse = json_decode(curl_exec($ch), true);
        curl_close($ch);
        $fileName = 'video_employer_' . $userId . '_' . date('Y-m-d_H-i-s') . '.' . $ext;
        echo json_encode([
            'uploadUrl' => $uploadUrlResponse['uploadUrl'],
            'authToken' => $uploadUrlResponse['authorizationToken'],
            'fileName' => $fileName,
            'downloadUrl' => $authResponse['downloadUrl']
        ]);
        exit;
    }
    if ($_POST['action'] === 'save_video') {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['error' => 'Non autorisé']);
            exit;
        }
        $videoUrl = trim($_POST['video_url'] ?? '');
        if ($db) {
            $db->prepare('UPDATE users SET company_video_url = ? WHERE id = ?')->execute([$videoUrl ?: null, $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Erreur base de données']);
        }
        exit;
    }
    if ($_POST['action'] === 'get_upload_url_photo') {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id']) || !$b2KeyId || !$b2AppKey || !$b2BucketId) {
            echo json_encode(['error' => 'Non autorisé ou config B2 manquante']);
            exit;
        }
        $authUrl = 'https://api.backblazeb2.com/b2api/v2/b2_authorize_account';
        $credentials = base64_encode($b2KeyId . ':' . $b2AppKey);
        $ch = curl_init($authUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $authResponse = json_decode(curl_exec($ch), true);
        curl_close($ch);
        if (!isset($authResponse['authorizationToken'])) {
            echo json_encode(['error' => 'Échec auth B2']);
            exit;
        }
        $getUploadUrl = $authResponse['apiUrl'] . '/b2api/v2/b2_get_upload_url';
        $ch = curl_init($getUploadUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $authResponse['authorizationToken'],
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['bucketId' => $b2BucketId]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $uploadUrlResponse = json_decode(curl_exec($ch), true);
        curl_close($ch);
        $fileName = 'photo_' . $userId . '_' . date('Y-m-d_H-i-s') . '.jpg';
        echo json_encode([
            'uploadUrl' => $uploadUrlResponse['uploadUrl'],
            'authToken' => $uploadUrlResponse['authorizationToken'],
            'fileName' => $fileName,
            'downloadUrl' => $authResponse['downloadUrl']
        ]);
        exit;
    }
    if ($_POST['action'] === 'save_photo') {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['error' => 'Non autorisé']);
            exit;
        }
        $photoUrlPost = trim($_POST['photo_url'] ?? '');
        if ($db && $photoUrlPost !== '') {
            $db->prepare('UPDATE users SET photo_url = ? WHERE id = ?')->execute([$photoUrlPost, $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'URL invalide']);
        }
        exit;
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_account') {
        $firstNamePost = trim($_POST['first_name'] ?? '');
        $emailNew = strtolower(trim($_POST['email'] ?? ''));
        $phoneNew = trim($_POST['phone'] ?? '');
        $preferredLanguageNew = trim($_POST['preferred_language'] ?? 'fr');
        if (!in_array($preferredLanguageNew, ['fr', 'en']))
            $preferredLanguageNew = 'fr';
        if (empty($firstNamePost)) {
            $error = 'Le prénom est requis.';
        } elseif (!filter_var($emailNew, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email invalide.';
        } else {
            try {
                $stmt = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
                $stmt->execute([$emailNew, $userId]);
                if ($stmt->fetch()) {
                    $error = 'Cet email est déjà utilisé.';
                } else {
                    $db->prepare('UPDATE users SET first_name = ?, email = ?, phone = ?, preferred_language = ? WHERE id = ?')
                        ->execute([$firstNamePost, $emailNew, $phoneNew ?: null, $preferredLanguageNew, $userId]);
                    $_SESSION['user_email'] = $emailNew;
                    $_SESSION['flash_success'] = 'Compte mis à jour.';
                    header('Location: employer-profile.php');
                    exit;
                }
            } catch (PDOException $e) {
                $error = 'Erreur lors de la mise à jour.';
            }
        }
    }
    if ($_POST['action'] === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['new_password_confirm'] ?? '';
        if (empty($current) || empty($new) || empty($confirm)) {
            $error = 'Tous les champs du mot de passe sont requis.';
        } elseif (strlen($new) < 8) {
            $error = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
        } elseif ($new !== $confirm) {
            $error = 'Les deux mots de passe ne correspondent pas.';
        } else {
            $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row || !password_verify($current, $row['password_hash'])) {
                $error = 'Mot de passe actuel incorrect.';
            } else {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $userId]);
                $_SESSION['flash_success'] = 'Mot de passe mis à jour.';
                header('Location: employer-profile.php');
                exit;
            }
        }
    }
    if ($_POST['action'] === 'update_employer_profile') {
        $companyName = trim($_POST['company_name'] ?? '');
        $companyDescription = trim($_POST['company_description'] ?? '');
        $companyDescriptionVisible = isset($_POST['company_description_visible']) && $_POST['company_description_visible'] === '1';
        $companyVideoUrl = trim($_POST['company_video_url'] ?? $user['company_video_url'] ?? '');
        $companyLogoUrl = trim($_POST['company_logo_url'] ?? $user['company_logo_url'] ?? '');
        $companyWebsiteUrl = trim($_POST['company_website_url'] ?? '');

        if ($companyVideoUrl !== '' && !filter_var($companyVideoUrl, FILTER_VALIDATE_URL)) {
            $error = 'L’URL de la vidéo corporative n’est pas valide.';
        } elseif ($companyWebsiteUrl !== '' && !filter_var($companyWebsiteUrl, FILTER_VALIDATE_URL)) {
            $error = 'L’URL du site web n’est pas valide.';
        } else {
            try {
                $db->prepare('UPDATE users SET company_name = ?, company_description = ?, company_description_visible = ?, company_video_url = ?, company_logo_url = ?, company_website_url = ? WHERE id = ?')
                    ->execute([$companyName ?: null, $companyDescription ?: null, $companyDescriptionVisible ? 1 : 0, $companyVideoUrl ?: null, $companyLogoUrl ?: null, $companyWebsiteUrl ?: null, $userId]);
                $_SESSION['flash_success'] = 'Profil entreprise mis à jour.';
                header('Location: employer-profile.php');
                exit;
            } catch (PDOException $e) {
                $error = 'Erreur lors de la mise à jour.';
            }
        }
    }
}

// Recharger après POST
$companyName = trim($user['company_name'] ?? '');
$companyDescription = trim($user['company_description'] ?? '');
$companyDescriptionVisible = !empty($user['company_description_visible']);
$companyVideoUrl = trim($user['company_video_url'] ?? '');
$companyLogoUrl = trim($user['company_logo_url'] ?? '');
$companyWebsiteUrl = trim($user['company_website_url'] ?? '');
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
        <?php $sidebarActive = 'profile';
        include __DIR__ . '/includes/employer-sidebar.php'; ?>
        <main class="app-main">
            <?php include __DIR__ . '/includes/employer-header.php'; ?>
            <div class="app-main-content layout-app layout-app-wide">

                <?php if ($success): ?>
                    <div class="success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- Mon compte (nom, courriel, langue, photo, mot de passe) -->
                <div class="section">
                    <h2>Mon compte</h2>
                    <div class="profile-header" style="margin-bottom:1.5rem;">
                        <button type="button" class="profile-photo profile-photo-clickable" id="userPhotoBtn"
                            title="Changer ma photo">
                            <?php if ($photoUrl): ?>
                                <img src="<?= htmlspecialchars($photoUrl) ?>" alt="" id="userPhotoImg">
                            <?php else: ?>
                                <div class="placeholder" id="userPhotoPlaceholder">
                                    <?= htmlspecialchars($firstName ? mb_substr($firstName, 0, 1) : '?') ?>
                                </div>
                            <?php endif; ?>
                            <span class="profile-photo-edit-hint">Modifier</span>
                        </button>
                        <div class="profile-info">
                            <p style="color:var(--text-secondary);margin:0;">Photo affichée en haut à droite</p>
                        </div>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_account">
                        <div class="form-group">
                            <label for="account_first_name">Prénom / Nom</label>
                            <input type="text" id="account_first_name" name="first_name"
                                value="<?= htmlspecialchars($firstName) ?>" placeholder="Votre prénom">
                        </div>
                        <div class="form-group">
                            <label for="account_email">Courriel</label>
                            <input type="email" id="account_email" name="email" value="<?= htmlspecialchars($email) ?>"
                                placeholder="votre@email.com" required>
                        </div>
                        <div class="form-group">
                            <label for="account_phone">Téléphone</label>
                            <input type="tel" id="account_phone" name="phone"
                                value="<?= htmlspecialchars($phone ?? '') ?>" placeholder="514-555-1234">
                        </div>
                        <div class="form-group">
                            <label for="account_preferred_language">Langue</label>
                            <select id="account_preferred_language" name="preferred_language">
                                <option value="fr" <?= $preferredLanguage === 'fr' ? 'selected' : '' ?>>Français</option>
                                <option value="en" <?= $preferredLanguage === 'en' ? 'selected' : '' ?>>English</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Sauvegarder</button>
                    </form>
                    <div style="margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid var(--border);">
                        <h3 style="font-size:1rem;margin-bottom:0.75rem;">Changer le mot de passe</h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            <div class="form-group">
                                <label for="account_current_password">Mot de passe actuel</label>
                                <input type="password" id="account_current_password" name="current_password"
                                    placeholder="••••••••" autocomplete="current-password">
                            </div>
                            <div class="form-group">
                                <label for="account_new_password">Nouveau mot de passe</label>
                                <input type="password" id="account_new_password" name="new_password"
                                    placeholder="••••••••" autocomplete="new-password" minlength="8">
                            </div>
                            <div class="form-group">
                                <label for="account_new_password_confirm">Confirmer le nouveau mot de passe</label>
                                <input type="password" id="account_new_password_confirm" name="new_password_confirm"
                                    placeholder="••••••••" autocomplete="new-password">
                            </div>
                            <button type="submit" class="btn btn-primary">Changer le mot de passe</button>
                        </form>
                    </div>
                </div>

                <!-- En-tête du profil entreprise -->
                <div class="profile-header employer-profile-header">
                    <div class="profile-photo employer-logo-wrap">
                        <?php if ($companyLogoUrl): ?>
                            <img src="<?= htmlspecialchars($companyLogoUrl) ?>"
                                alt="Logo <?= htmlspecialchars($companyName ?: 'entreprise') ?>" class="employer-logo-img">
                        <?php else: ?>
                            <div class="placeholder">🏢</div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-info">
                        <h3><?= htmlspecialchars($companyName ?: 'Entreprise') ?></h3>
                        <p><?= htmlspecialchars($email) ?></p>
                        <?php if ($companyWebsiteUrl): ?>
                            <p><a href="<?= htmlspecialchars($companyWebsiteUrl) ?>" target="_blank"
                                    rel="noopener noreferrer">Site web →</a></p>
                        <?php endif; ?>
                        <?php if ($companyDescription && $companyDescriptionVisible): ?>
                            <p class="profile-description-preview">
                                <?= nl2br(htmlspecialchars(mb_substr($companyDescription, 0, 200) . (mb_strlen($companyDescription) > 200 ? '…' : ''))) ?>
                            </p>
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
                            <input type="text" id="company_name" name="company_name"
                                value="<?= htmlspecialchars($companyName) ?>" placeholder="Nom de votre entreprise">
                        </div>
                        <div class="form-group">
                            <label for="company_description">Description de l'entreprise</label>
                            <textarea id="company_description" name="company_description" rows="5"
                                placeholder="Présentez votre entreprise aux candidats…"><?= htmlspecialchars($companyDescription) ?></textarea>
                        </div>
                        <div class="form-group form-group-checkbox">
                            <label>
                                <input type="checkbox" name="company_description_visible" value="1"
                                    <?= $companyDescriptionVisible ? 'checked' : '' ?>>
                                Afficher la description aux candidats (sinon masquée)
                            </label>
                        </div>
                        <div class="form-group">
                            <label for="company_website_url">Site web de l'entreprise</label>
                            <input type="url" id="company_website_url" name="company_website_url"
                                value="<?= htmlspecialchars($companyWebsiteUrl) ?>"
                                placeholder="https://www.votre-entreprise.com">
                        </div>
                        <button type="submit" class="btn btn-primary">Sauvegarder</button>
                    </form>
                </div>

                <!-- Logo -->
                <div class="section">
                    <h2>Logo</h2>
                    <div class="employer-logo-block">
                        <?php if ($companyLogoUrl): ?>
                            <div class="employer-logo-preview">
                                <img id="employerLogoImg" src="<?= htmlspecialchars($companyLogoUrl) ?>" alt="Logo"
                                    style="max-width: 120px; max-height: 120px; object-fit: contain;">
                            </div>
                        <?php else: ?>
                            <div class="employer-logo-placeholder" id="employerLogoPlaceholder">Aucun logo</div>
                        <?php endif; ?>
                        <button type="button" class="btn btn-primary" id="employerLogoBtn">Changer le logo</button>
                    </div>
                </div>

                <!-- Vidéo corporative -->
                <div class="section">
                    <h2>Vidéo corporative</h2>
                    <div class="video-preview" id="employerVideoPreview">
                        <?php if ($companyVideoUrl): ?>
                            <video controls src="<?= htmlspecialchars($companyVideoUrl) ?>" id="employerVideoEl"></video>
                        <?php else: ?>
                            <div class="no-video">
                                <span>🎬</span>
                                <p>Aucune vidéo corporative</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-primary"
                        id="employerVideoChangeBtn"><?= $companyVideoUrl ? 'Changer la vidéo' : 'Téléverser une vidéo' ?></button>
                </div>

            </div>
        </main>
    </div>

    <!-- Modal upload logo -->
    <div class="photo-modal" id="logoModal" role="dialog" aria-labelledby="logoModalTitle" aria-modal="true">
        <div class="photo-modal-backdrop" id="logoModalBackdrop"></div>
        <div class="photo-modal-content">
            <div class="photo-modal-header">
                <h2 id="logoModalTitle">Changer le logo</h2>
                <button type="button" class="photo-modal-close" id="logoModalClose" aria-label="Fermer">&times;</button>
            </div>
            <div class="photo-modal-body">
                <div class="photo-modal-preview" id="logoModalContainer">
                    <video id="logoModalCamera" class="hidden" autoplay playsinline muted></video>
                    <img id="logoModalPreview" src="" alt="Aperçu" class="hidden">
                    <div class="photo-modal-placeholder" id="logoModalPlaceholder">
                        <span class="icon">🏢</span>
                        <span>Aperçu</span>
                    </div>
                </div>
                <div class="photo-modal-options" id="logoModalOptions">
                    <label class="photo-option" id="logoModalTake">
                        <span class="icon">📸</span>
                        <span>Prendre une photo</span>
                    </label>
                    <label class="photo-option">
                        <span class="icon">📁</span>
                        <span>Téléverser une image</span>
                        <input type="file" id="logoModalFile" accept="image/*">
                    </label>
                </div>
                <div id="logoModalCameraControls" class="hidden" style="display:flex;gap:0.5rem;margin-top:1rem;">
                    <button type="button" class="btn btn-secondary" id="logoModalCancelCamera"
                        style="flex:1;">Annuler</button>
                    <button type="button" class="btn" id="logoModalCapture" style="flex:1;">Capturer</button>
                </div>
                <div id="logoModalConfirmControls" class="hidden" style="display:flex;gap:0.5rem;margin-top:1rem;">
                    <button type="button" class="btn btn-secondary" id="logoModalRetake"
                        style="flex:1;">Reprendre</button>
                    <button type="button" class="btn" id="logoModalConfirm" style="flex:1;">Remplacer</button>
                </div>
            </div>
        </div>
    </div>
    <canvas id="logoModalCanvas"></canvas>

    <!-- Modal photo utilisateur (avatar header) -->
    <div class="photo-modal" id="userPhotoModal" role="dialog" aria-labelledby="userPhotoModalTitle" aria-modal="true">
        <div class="photo-modal-backdrop" id="userPhotoModalBackdrop"></div>
        <div class="photo-modal-content">
            <div class="photo-modal-header">
                <h2 id="userPhotoModalTitle">Changer ma photo</h2>
                <button type="button" class="photo-modal-close" id="userPhotoModalClose"
                    aria-label="Fermer">&times;</button>
            </div>
            <div class="photo-modal-body">
                <div class="photo-modal-preview" id="userPhotoModalContainer">
                    <video id="userPhotoModalCamera" class="hidden" autoplay playsinline muted></video>
                    <img id="userPhotoModalPreview" src="" alt="Aperçu" class="hidden">
                    <div class="photo-modal-placeholder" id="userPhotoModalPlaceholder">
                        <span class="icon">👤</span>
                        <span>Aperçu</span>
                    </div>
                </div>
                <div class="photo-modal-options" id="userPhotoModalOptions">
                    <label class="photo-option" id="userPhotoModalTake">
                        <span class="icon">📸</span>
                        <span>Prendre une photo</span>
                    </label>
                    <label class="photo-option">
                        <span class="icon">📁</span>
                        <span>Téléverser une image</span>
                        <input type="file" id="userPhotoModalFile" accept="image/*">
                    </label>
                </div>
                <div id="userPhotoModalCameraControls" class="hidden" style="display:flex;gap:0.5rem;margin-top:1rem;">
                    <button type="button" class="btn btn-secondary" id="userPhotoModalCancelCamera"
                        style="flex:1;">Annuler</button>
                    <button type="button" class="btn" id="userPhotoModalCapture" style="flex:1;">Capturer</button>
                </div>
                <div id="userPhotoModalConfirmControls" class="hidden" style="display:flex;gap:0.5rem;margin-top:1rem;">
                    <button type="button" class="btn btn-secondary" id="userPhotoModalRetake"
                        style="flex:1;">Reprendre</button>
                    <button type="button" class="btn" id="userPhotoModalConfirm" style="flex:1;">Remplacer</button>
                </div>
            </div>
        </div>
    </div>
    <canvas id="userPhotoModalCanvas"></canvas>

    <!-- Modal upload vidéo corporative -->
    <div class="photo-modal" id="videoModal" role="dialog" aria-labelledby="videoModalTitle" aria-modal="true">
        <div class="photo-modal-backdrop" id="videoModalBackdrop"></div>
        <div class="photo-modal-content">
            <div class="photo-modal-header">
                <h2 id="videoModalTitle">Téléverser la vidéo corporative</h2>
                <button type="button" class="photo-modal-close" id="videoModalClose"
                    aria-label="Fermer">&times;</button>
            </div>
            <div class="photo-modal-body">
                <div class="form-group">
                    <label class="photo-option"
                        style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;padding:1rem;border:2px dashed var(--border);border-radius:8px;">
                        <span class="icon">🎬</span>
                        <span>Choisir un fichier vidéo (MP4, WebM, etc.)</span>
                        <input type="file" id="videoModalFile" accept="video/*" style="display:none;">
                    </label>
                </div>
                <div id="videoModalPreviewWrap" class="hidden" style="margin-top:1rem;">
                    <video id="videoModalPreview" controls style="max-width:100%;max-height:200px;"></video>
                    <div style="display:flex;gap:0.5rem;margin-top:1rem;">
                        <button type="button" class="btn btn-secondary" id="videoModalCancel">Annuler</button>
                        <button type="button" class="btn" id="videoModalUpload">Téléverser</button>
                    </div>
                </div>
                <div id="videoModalProgress" class="hidden" style="margin-top:1rem;">
                    <p style="font-size:0.9rem;color:var(--text-secondary);">Envoi en cours…</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('logoModal');
            const backdrop = document.getElementById('logoModalBackdrop');
            const closeBtn = document.getElementById('logoModalClose');
            const openBtn = document.getElementById('employerLogoBtn');
            const logoImg = document.getElementById('employerLogoImg');
            const logoPlaceholder = document.getElementById('employerLogoPlaceholder');
            const container = document.getElementById('logoModalContainer');
            const cameraEl = document.getElementById('logoModalCamera');
            const previewEl = document.getElementById('logoModalPreview');
            const placeholderEl = document.getElementById('logoModalPlaceholder');
            const optionsEl = document.getElementById('logoModalOptions');
            const cameraControls = document.getElementById('logoModalCameraControls');
            const confirmControls = document.getElementById('logoModalConfirmControls');
            const takeBtn = document.getElementById('logoModalTake');
            const fileInput = document.getElementById('logoModalFile');
            const cancelCameraBtn = document.getElementById('logoModalCancelCamera');
            const captureBtn = document.getElementById('logoModalCapture');
            const retakeBtn = document.getElementById('logoModalRetake');
            const confirmBtn = document.getElementById('logoModalConfirm');
            const canvas = document.getElementById('logoModalCanvas');
            const headerLogoImg = document.querySelector('.employer-profile-header .employer-logo-img');

            let stream = null;
            let photoBlob = null;

            function openModal() {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
                resetModalState();
            }
            function closeModal() {
                modal.classList.remove('active');
                document.body.style.overflow = '';
                if (stream) {
                    stream.getTracks().forEach(t => t.stop());
                    stream = null;
                }
            }
            function resetModalState() {
                photoBlob = null;
                placeholderEl.classList.remove('hidden');
                previewEl.classList.add('hidden');
                previewEl.src = '';
                cameraEl.classList.add('hidden');
                optionsEl.style.display = '';
                cameraControls.classList.add('hidden');
                cameraControls.style.display = 'none';
                confirmControls.classList.add('hidden');
                confirmControls.style.display = 'none';
            }

            if (openBtn) openBtn.addEventListener('click', openModal);
            if (closeBtn) closeBtn.addEventListener('click', closeModal);
            if (backdrop) backdrop.addEventListener('click', closeModal);

            if (takeBtn) takeBtn.addEventListener('click', async () => {
                try {
                    stream = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: 'user', width: { ideal: 720 }, height: { ideal: 720 } }
                    });
                    cameraEl.srcObject = stream;
                    placeholderEl.classList.add('hidden');
                    previewEl.classList.add('hidden');
                    cameraEl.classList.remove('hidden');
                    optionsEl.style.display = 'none';
                    cameraControls.classList.remove('hidden');
                    cameraControls.style.display = 'flex';
                    confirmControls.classList.add('hidden');
                } catch (err) {
                    alert('Impossible d\'accéder à la caméra');
                }
            });

            if (cancelCameraBtn) cancelCameraBtn.addEventListener('click', () => {
                if (stream) {
                    stream.getTracks().forEach(t => t.stop());
                    stream = null;
                }
                resetModalState();
            });

            if (captureBtn) captureBtn.addEventListener('click', () => {
                canvas.width = 720;
                canvas.height = 720;
                const ctx = canvas.getContext('2d');
                const vw = cameraEl.videoWidth;
                const vh = cameraEl.videoHeight;
                const size = Math.min(vw, vh);
                const sx = (vw - size) / 2;
                const sy = (vh - size) / 2;
                ctx.drawImage(cameraEl, sx, sy, size, size, 0, 0, 720, 720);
                canvas.toBlob((blob) => {
                    photoBlob = blob;
                    previewEl.src = URL.createObjectURL(blob);
                    if (stream) {
                        stream.getTracks().forEach(t => t.stop());
                        stream = null;
                    }
                    cameraEl.classList.add('hidden');
                    previewEl.classList.remove('hidden');
                    cameraControls.classList.add('hidden');
                    confirmControls.classList.remove('hidden');
                    confirmControls.style.display = 'flex';
                }, 'image/jpeg', 0.9);
            });

            if (fileInput) fileInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (!file) return;
                const img = new Image();
                img.onload = () => {
                    canvas.width = 720;
                    canvas.height = 720;
                    const ctx = canvas.getContext('2d');
                    const size = Math.min(img.width, img.height);
                    const sx = (img.width - size) / 2;
                    const sy = (img.height - size) / 2;
                    ctx.drawImage(img, sx, sy, size, size, 0, 0, 720, 720);
                    canvas.toBlob((blob) => {
                        photoBlob = blob;
                        previewEl.src = URL.createObjectURL(blob);
                        placeholderEl.classList.add('hidden');
                        cameraEl.classList.add('hidden');
                        previewEl.classList.remove('hidden');
                        optionsEl.style.display = 'none';
                        confirmControls.classList.remove('hidden');
                        confirmControls.style.display = 'flex';
                    }, 'image/jpeg', 0.9);
                };
                img.src = URL.createObjectURL(file);
                e.target.value = '';
            });

            if (retakeBtn) retakeBtn.addEventListener('click', resetModalState);

            if (confirmBtn) confirmBtn.addEventListener('click', async () => {
                if (!photoBlob) return;
                confirmBtn.disabled = true;
                confirmBtn.textContent = 'Envoi...';
                try {
                    const formData = new FormData();
                    formData.append('action', 'get_upload_url_logo');
                    const response = await fetch('employer-profile.php', { method: 'POST', body: formData });
                    const uploadInfo = await response.json();
                    if (uploadInfo.error) throw new Error(uploadInfo.error);
                    const arrayBuffer = await photoBlob.arrayBuffer();
                    const hashBuffer = await crypto.subtle.digest('SHA-1', arrayBuffer);
                    const hashArray = Array.from(new Uint8Array(hashBuffer));
                    const sha1 = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', uploadInfo.uploadUrl, true);
                    xhr.setRequestHeader('Authorization', uploadInfo.authToken);
                    xhr.setRequestHeader('X-Bz-File-Name', encodeURIComponent(uploadInfo.fileName));
                    xhr.setRequestHeader('Content-Type', 'image/jpeg');
                    xhr.setRequestHeader('X-Bz-Content-Sha1', sha1);
                    xhr.onload = async () => {
                        if (xhr.status >= 200 && xhr.status < 300) {
                            const result = JSON.parse(xhr.responseText);
                            const bucketName = 'ciaocv';
                            const logoUrl = uploadInfo.downloadUrl + '/file/' + bucketName + '/' + result.fileName;
                            const saveData = new FormData();
                            saveData.append('action', 'save_logo');
                            saveData.append('logo_url', logoUrl);
                            const saveRes = await fetch('employer-profile.php', { method: 'POST', body: saveData });
                            const saveJson = await saveRes.json();
                            if (saveJson.success) {
                                if (logoImg) {
                                    logoImg.src = logoUrl;
                                } else if (logoPlaceholder) {
                                    logoPlaceholder.outerHTML = '<div class="employer-logo-preview"><img id="employerLogoImg" src="' + logoUrl + '" alt="Logo" style="max-width:120px;max-height:120px;object-fit:contain;"></div>';
                                }
                                if (headerLogoImg) headerLogoImg.src = logoUrl;
                                else {
                                    const headerWrap = document.querySelector('.employer-profile-header .employer-logo-wrap');
                                    if (headerWrap) {
                                        const ph = headerWrap.querySelector('.placeholder');
                                        if (ph) {
                                            ph.remove();
                                            const img = document.createElement('img');
                                            img.className = 'employer-logo-img';
                                            img.alt = 'Logo';
                                            img.src = logoUrl;
                                            headerWrap.insertBefore(img, headerWrap.firstChild);
                                        } else {
                                            const img = headerWrap.querySelector('.employer-logo-img');
                                            if (img) img.src = logoUrl;
                                        }
                                    }
                                }
                                closeModal();
                            } else {
                                throw new Error(saveJson.error || 'Erreur');
                            }
                        } else {
                            throw new Error('Échec upload');
                        }
                    };
                    xhr.onerror = () => { throw new Error('Erreur réseau'); };
                    xhr.send(photoBlob);
                } catch (err) {
                    alert('Erreur: ' + err.message);
                }
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Remplacer';
            });
        })();

        // Modal photo utilisateur (avatar)
        (function () {
            const userId = <?= (int) $userId ?>;
            const modal = document.getElementById('userPhotoModal');
            const backdrop = document.getElementById('userPhotoModalBackdrop');
            const closeBtn = document.getElementById('userPhotoModalClose');
            const openBtn = document.getElementById('userPhotoBtn');
            const profileImg = document.getElementById('userPhotoImg');
            const profilePlaceholder = document.getElementById('userPhotoPlaceholder');
            const container = document.getElementById('userPhotoModalContainer');
            const cameraEl = document.getElementById('userPhotoModalCamera');
            const previewEl = document.getElementById('userPhotoModalPreview');
            const placeholderEl = document.getElementById('userPhotoModalPlaceholder');
            const optionsEl = document.getElementById('userPhotoModalOptions');
            const cameraControls = document.getElementById('userPhotoModalCameraControls');
            const confirmControls = document.getElementById('userPhotoModalConfirmControls');
            const takeBtn = document.getElementById('userPhotoModalTake');
            const fileInput = document.getElementById('userPhotoModalFile');
            const cancelCameraBtn = document.getElementById('userPhotoModalCancelCamera');
            const captureBtn = document.getElementById('userPhotoModalCapture');
            const retakeBtn = document.getElementById('userPhotoModalRetake');
            const confirmBtn = document.getElementById('userPhotoModalConfirm');
            const canvas = document.getElementById('userPhotoModalCanvas');
            const headerAvatarImg = document.querySelector('.app-header-avatar-link .avatar-img');

            let stream = null;
            let photoBlob = null;

            function openUserPhotoModal() {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
                resetUserPhotoState();
                if (profileImg && profileImg.src) {
                    previewEl.src = profileImg.src;
                    previewEl.classList.remove('hidden');
                    placeholderEl.classList.add('hidden');
                }
            }
            function closeUserPhotoModal() {
                modal.classList.remove('active');
                document.body.style.overflow = '';
                if (stream) {
                    stream.getTracks().forEach(t => t.stop());
                    stream = null;
                }
            }
            function resetUserPhotoState() {
                photoBlob = null;
                placeholderEl.classList.remove('hidden');
                previewEl.classList.add('hidden');
                previewEl.src = '';
                cameraEl.classList.add('hidden');
                optionsEl.style.display = '';
                cameraControls.classList.add('hidden');
                cameraControls.style.display = 'none';
                confirmControls.classList.add('hidden');
                confirmControls.style.display = 'none';
            }

            if (openBtn) openBtn.addEventListener('click', openUserPhotoModal);
            if (closeBtn) closeBtn.addEventListener('click', closeUserPhotoModal);
            if (backdrop) backdrop.addEventListener('click', closeUserPhotoModal);

            if (takeBtn) takeBtn.addEventListener('click', async () => {
                try {
                    stream = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: 'user', width: { ideal: 720 }, height: { ideal: 720 } }
                    });
                    cameraEl.srcObject = stream;
                    placeholderEl.classList.add('hidden');
                    previewEl.classList.add('hidden');
                    cameraEl.classList.remove('hidden');
                    optionsEl.style.display = 'none';
                    cameraControls.classList.remove('hidden');
                    cameraControls.style.display = 'flex';
                    confirmControls.classList.add('hidden');
                } catch (err) {
                    alert('Impossible d\'accéder à la caméra');
                }
            });

            if (cancelCameraBtn) cancelCameraBtn.addEventListener('click', () => {
                if (stream) {
                    stream.getTracks().forEach(t => t.stop());
                    stream = null;
                }
                resetUserPhotoState();
            });

            if (captureBtn) captureBtn.addEventListener('click', () => {
                canvas.width = 720;
                canvas.height = 720;
                const ctx = canvas.getContext('2d');
                const vw = cameraEl.videoWidth;
                const vh = cameraEl.videoHeight;
                const size = Math.min(vw, vh);
                const sx = (vw - size) / 2;
                const sy = (vh - size) / 2;
                ctx.drawImage(cameraEl, sx, sy, size, size, 0, 0, 720, 720);
                canvas.toBlob((blob) => {
                    photoBlob = blob;
                    previewEl.src = URL.createObjectURL(blob);
                    if (stream) {
                        stream.getTracks().forEach(t => t.stop());
                        stream = null;
                    }
                    cameraEl.classList.add('hidden');
                    previewEl.classList.remove('hidden');
                    cameraControls.classList.add('hidden');
                    confirmControls.classList.remove('hidden');
                    confirmControls.style.display = 'flex';
                }, 'image/jpeg', 0.9);
            });

            if (fileInput) fileInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (!file) return;
                const img = new Image();
                img.onload = () => {
                    canvas.width = 720;
                    canvas.height = 720;
                    const ctx = canvas.getContext('2d');
                    const size = Math.min(img.width, img.height);
                    const sx = (img.width - size) / 2;
                    const sy = (img.height - size) / 2;
                    ctx.drawImage(img, sx, sy, size, size, 0, 0, 720, 720);
                    canvas.toBlob((blob) => {
                        photoBlob = blob;
                        previewEl.src = URL.createObjectURL(blob);
                        placeholderEl.classList.add('hidden');
                        cameraEl.classList.add('hidden');
                        previewEl.classList.remove('hidden');
                        optionsEl.style.display = 'none';
                        confirmControls.classList.remove('hidden');
                        confirmControls.style.display = 'flex';
                    }, 'image/jpeg', 0.9);
                };
                img.src = URL.createObjectURL(file);
                e.target.value = '';
            });

            if (retakeBtn) retakeBtn.addEventListener('click', resetUserPhotoState);

            if (confirmBtn) confirmBtn.addEventListener('click', async () => {
                if (!photoBlob) return;
                confirmBtn.disabled = true;
                confirmBtn.textContent = 'Envoi...';
                try {
                    const formData = new FormData();
                    formData.append('action', 'get_upload_url_photo');
                    const response = await fetch('employer-profile.php', { method: 'POST', body: formData });
                    const uploadInfo = await response.json();
                    if (uploadInfo.error) throw new Error(uploadInfo.error);
                    const arrayBuffer = await photoBlob.arrayBuffer();
                    const hashBuffer = await crypto.subtle.digest('SHA-1', arrayBuffer);
                    const hashArray = Array.from(new Uint8Array(hashBuffer));
                    const sha1 = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', uploadInfo.uploadUrl, true);
                    xhr.setRequestHeader('Authorization', uploadInfo.authToken);
                    xhr.setRequestHeader('X-Bz-File-Name', encodeURIComponent(uploadInfo.fileName));
                    xhr.setRequestHeader('Content-Type', 'image/jpeg');
                    xhr.setRequestHeader('X-Bz-Content-Sha1', sha1);
                    xhr.onload = async () => {
                        if (xhr.status >= 200 && xhr.status < 300) {
                            const result = JSON.parse(xhr.responseText);
                            const bucketName = 'ciaocv';
                            const photoUrl = uploadInfo.downloadUrl + '/file/' + bucketName + '/' + result.fileName;
                            const saveData = new FormData();
                            saveData.append('action', 'save_photo');
                            saveData.append('photo_url', photoUrl);
                            const saveRes = await fetch('employer-profile.php', { method: 'POST', body: saveData });
                            const saveJson = await saveRes.json();
                            if (saveJson.success) {
                                if (profileImg) {
                                    profileImg.src = photoUrl;
                                } else if (profilePlaceholder) {
                                    profilePlaceholder.outerHTML = '<img src="' + photoUrl + '" alt="" id="userPhotoImg">';
                                }
                                if (headerAvatarImg) {
                                    headerAvatarImg.src = photoUrl;
                                } else {
                                    const headerAvatar = document.querySelector('.app-header-avatar-link .avatar');
                                    if (headerAvatar) {
                                        const img = document.createElement('img');
                                        img.className = 'avatar-img';
                                        img.alt = '';
                                        img.src = photoUrl;
                                        headerAvatar.insertBefore(img, headerAvatar.firstChild);
                                    }
                                }
                                closeUserPhotoModal();
                            } else {
                                throw new Error(saveJson.error || 'Erreur');
                            }
                        } else {
                            throw new Error('Échec upload');
                        }
                    };
                    xhr.onerror = () => { throw new Error('Erreur réseau'); };
                    xhr.send(photoBlob);
                } catch (err) {
                    alert('Erreur: ' + err.message);
                }
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Remplacer';
            });
        })();

        // Modal vidéo corporative
        (function () {
            const videoModal = document.getElementById('videoModal');
            const videoBackdrop = document.getElementById('videoModalBackdrop');
            const videoCloseBtn = document.getElementById('videoModalClose');
            const videoOpenBtn = document.getElementById('employerVideoChangeBtn');
            const videoFileInput = document.getElementById('videoModalFile');
            const videoPreviewWrap = document.getElementById('videoModalPreviewWrap');
            const videoPreviewEl = document.getElementById('videoModalPreview');
            const videoCancelBtn = document.getElementById('videoModalCancel');
            const videoUploadBtn = document.getElementById('videoModalUpload');
            const videoProgressWrap = document.getElementById('videoModalProgress');
            const employerVideoPreview = document.getElementById('employerVideoPreview');
            const employerVideoEl = document.getElementById('employerVideoEl');

            let videoBlob = null;

            function openVideoModal() {
                videoModal.classList.add('active');
                document.body.style.overflow = 'hidden';
                videoBlob = null;
                videoFileInput.value = '';
                videoPreviewWrap.classList.add('hidden');
                videoProgressWrap.classList.add('hidden');
            }
            function closeVideoModal() {
                videoModal.classList.remove('active');
                document.body.style.overflow = '';
            }

            if (videoOpenBtn) videoOpenBtn.addEventListener('click', openVideoModal);
            if (videoCloseBtn) videoCloseBtn.addEventListener('click', closeVideoModal);
            if (videoBackdrop) videoBackdrop.addEventListener('click', closeVideoModal);

            if (videoFileInput) videoFileInput.addEventListener('change', function (e) {
                const file = e.target.files[0];
                if (!file) return;
                videoBlob = file;
                videoPreviewEl.src = URL.createObjectURL(file);
                videoPreviewWrap.classList.remove('hidden');
            });

            if (videoCancelBtn) videoCancelBtn.addEventListener('click', function () {
                videoBlob = null;
                videoFileInput.value = '';
                videoPreviewWrap.classList.add('hidden');
            });

            if (videoUploadBtn) videoUploadBtn.addEventListener('click', async function () {
                if (!videoBlob) return;
                videoUploadBtn.disabled = true;
                videoProgressWrap.classList.remove('hidden');
                try {
                    const ext = (videoBlob.name.split('.').pop() || 'webm').toLowerCase().replace(/[^a-z0-9]/g, '') || 'webm';
                    const formData = new FormData();
                    formData.append('action', 'get_upload_url_video');
                    formData.append('ext', ext);
                    const response = await fetch('employer-profile.php', { method: 'POST', body: formData });
                    const uploadInfo = await response.json();
                    if (uploadInfo.error) throw new Error(uploadInfo.error);

                    const arrayBuffer = await videoBlob.arrayBuffer();
                    const hashBuffer = await crypto.subtle.digest('SHA-1', arrayBuffer);
                    const hashArray = Array.from(new Uint8Array(hashBuffer));
                    const sha1 = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');

                    const contentType = videoBlob.type || 'video/mp4';
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', uploadInfo.uploadUrl, true);
                    xhr.setRequestHeader('Authorization', uploadInfo.authToken);
                    xhr.setRequestHeader('X-Bz-File-Name', encodeURIComponent(uploadInfo.fileName));
                    xhr.setRequestHeader('Content-Type', contentType);
                    xhr.setRequestHeader('X-Bz-Content-Sha1', sha1);
                    xhr.onload = async function () {
                        if (xhr.status >= 200 && xhr.status < 300) {
                            const result = JSON.parse(xhr.responseText);
                            const bucketName = 'ciaocv';
                            const videoUrl = uploadInfo.downloadUrl + '/file/' + bucketName + '/' + result.fileName;
                            const saveData = new FormData();
                            saveData.append('action', 'save_video');
                            saveData.append('video_url', videoUrl);
                            const saveRes = await fetch('employer-profile.php', { method: 'POST', body: saveData });
                            const saveJson = await saveRes.json();
                            if (saveJson.success) {
                                if (employerVideoPreview.querySelector('.no-video')) {
                                    employerVideoPreview.innerHTML = '<video controls src="' + videoUrl + '" id="employerVideoEl"></video>';
                                } else if (employerVideoEl) {
                                    employerVideoEl.src = videoUrl;
                                }
                                if (videoOpenBtn) videoOpenBtn.textContent = 'Changer la vidéo';
                                closeVideoModal();
                            } else {
                                throw new Error(saveJson.error || 'Erreur');
                            }
                        } else {
                            throw new Error('Échec upload');
                        }
                    };
                    xhr.onerror = function () { throw new Error('Erreur réseau'); };
                    xhr.send(videoBlob);
                } catch (err) {
                    alert('Erreur: ' + err.message);
                }
                videoUploadBtn.disabled = false;
                videoProgressWrap.classList.add('hidden');
            });
        })();
    </script>
</body>

</html>