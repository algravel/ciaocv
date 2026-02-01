<?php
/**
 * Réinitialisation du mot de passe via token reçu par courriel
 */
session_start();
require_once __DIR__ . '/db.php';

// Si déjà connecté, rediriger
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = null;
$success = null;
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$validToken = false;
$userId = null;

if (empty($token) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $error = 'Lien invalide ou expiré. Demandez un nouveau lien depuis la page « Mot de passe oublié ».';
} elseif ($db) {
    try {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = trim($_POST['token'] ?? '');
            $password = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';

            if (empty($token)) {
                $error = 'Lien invalide. Demandez un nouveau lien.';
            } elseif (strlen($password) < 8) {
                $error = 'Le mot de passe doit contenir au moins 8 caractères.';
            } elseif ($password !== $passwordConfirm) {
                $error = 'Les deux mots de passe ne correspondent pas.';
            } else {
                $stmt = $db->prepare('
                    SELECT prt.id as token_id, prt.user_id
                    FROM password_reset_tokens prt
                    WHERE prt.token = ? AND prt.expires_at > NOW()
                    LIMIT 1
                ');
                $stmt->execute([$token]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$row) {
                    $error = 'Ce lien a expiré ou a déjà été utilisé. Demandez un nouveau lien.';
                } else {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$passwordHash, $row['user_id']]);
                    $db->prepare('DELETE FROM password_reset_tokens WHERE id = ?')->execute([$row['token_id']]);
                    $success = 'Votre mot de passe a été mis à jour. Vous pouvez vous connecter.';
                    $token = '';
                }
            }
            // Réafficher le formulaire si erreur de saisie (mot de passe) pour que l'utilisateur puisse réessayer
            if ($error && in_array($error, ['Le mot de passe doit contenir au moins 8 caractères.', 'Les deux mots de passe ne correspondent pas.'], true)) {
                $validToken = true;
            }
        } else {
            // GET : vérifier que le token est valide pour afficher le formulaire
            $stmt = $db->prepare('
                SELECT prt.user_id FROM password_reset_tokens prt
                WHERE prt.token = ? AND prt.expires_at > NOW()
                LIMIT 1
            ');
            $stmt->execute([$token]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $validToken = true;
                $userId = $row['user_id'];
            } else {
                $error = 'Ce lien a expiré ou a déjà été utilisé. Demandez un nouveau lien depuis la page « Mot de passe oublié ».';
            }
        }
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "doesn't exist") !== false) {
            $error = 'Réinitialisation non disponible. <a href="update-schema-password-reset.php">Exécuter la migration</a>.';
        } else {
            $error = 'Erreur. Réessayez plus tard.';
        }
    }
}

$showForm = ($validToken && !$success) || ($_SERVER['REQUEST_METHOD'] === 'POST' && $error && !empty($token));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau mot de passe - CiaoCV</title>
    <link rel="stylesheet" href="assets/css/design-system.css?v=<?= ASSET_VERSION ?>">
</head>
<body class="layout-auth-page">
    <div class="container">
        <a href="index.php" class="logo">CiaoCV</a>
        <div class="card">
            <h1 style="font-size:1.25rem;margin-bottom:0.5rem;">Nouveau mot de passe</h1>

            <?php if ($error && !$showForm): ?>
                <div class="error"><?= $error ?></div>
                <a href="forgot-password.php" class="btn" style="display:block;text-align:center;text-decoration:none;margin-top:1rem;">Demander un nouveau lien</a>
            <?php elseif ($success): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
                <a href="index.php" class="btn" style="display:block;text-align:center;text-decoration:none;">Se connecter</a>
            <?php elseif ($showForm): ?>
                <?php if ($error): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <div class="form-group">
                        <label for="password">Nouveau mot de passe</label>
                        <input type="password" id="password" name="password" placeholder="Minimum 8 caractères" required minlength="8">
                    </div>
                    <div class="form-group">
                        <label for="password_confirm">Confirmer le mot de passe</label>
                        <input type="password" id="password_confirm" name="password_confirm" placeholder="••••••••" required minlength="8">
                    </div>
                    <button type="submit" class="btn">Enregistrer le mot de passe</button>
                </form>
            <?php endif; ?>

            <a href="index.php" class="back">← Retour à la connexion</a>
        </div>
        <p class="footer">© 2026 CiaoCV — <a href="https://www.ciaocv.com">Retour au site</a></p>
    </div>
</body>
</html>
