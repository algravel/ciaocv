<?php
/**
 * Page de connexion uniquement.
 * Si connecté : redirection directe vers l'espace candidat (candidate-jobs.php).
 */
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';

// Déconnexion
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Si connecté : afficher directement l'espace candidat (pas de page d'accueil)
$isLoggedIn = isset($_SESSION['user_id']);
if ($isLoggedIn) {
    header('Location: candidate-jobs.php');
    exit;
}

// =====================
// TRAITEMENT CONNEXION (si non connecté)
// =====================
$error = null;
$errorHtml = false;

if (!$isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'connexion') {
        if (!$db) {
            $error = 'Service temporairement indisponible.';
        } else {
            $email = strtolower(trim($_POST['email'] ?? ''));
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                $error = 'Veuillez entrer votre courriel et mot de passe.';
            } else {
                try {
                    $stmt = $db->prepare('SELECT id, email, first_name, password_hash, email_verified, onboarding_step, onboarding_completed FROM users WHERE email = ?');
                    $stmt->execute([$email]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$user) {
                        $error = 'Courriel ou mot de passe incorrect.';
                    } elseif (!password_verify($password, $user['password_hash'])) {
                        $error = 'Courriel ou mot de passe incorrect.';
                    } elseif (!$user['email_verified']) {
                        $error = 'Compte non confirmé. <a href="onboarding/confirm.php?email=' . urlencode($email) . '">Confirmer</a>';
                        $errorHtml = true;
                    } else {
                        // Connexion réussie
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_email'] = $user['email'];
                        $firstName = trim($user['first_name'] ?? '');
                        $_SESSION['user_first_name'] = $firstName !== '' ? $firstName : (strstr($user['email'], '@', true) ?: $user['email']);
                        
                        header('Location: candidate-jobs.php');
                        exit;
                    }
                } catch (PDOException $e) {
                    $error = 'Erreur. Réessayez plus tard.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CiaoCV - Vidéo</title>
    <link rel="stylesheet" href="assets/css/design-system.css?v=<?= ASSET_VERSION ?>">
</head>
<body class="layout-auth-page">
    <div class="container">
        <div class="logo">CiaoCV</div>
        <p class="tagline">L'entrevue réinventée !</p>

        <div class="login-card">
            <?php if ($error): ?>
                <div class="error"><?= $errorHtml ? $error : htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="action" value="connexion">
                <div class="form-group">
                    <label for="email">Courriel</label>
                    <input type="email" id="email" name="email" placeholder="ton@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn">Se connecter</button>
            </form>

            <div class="login-footer">
                <a href="forgot-password.php">Mot de passe oublié ?</a><br>
                Pas encore inscrit ? <a href="onboarding/signup.php">Créer un compte</a>
            </div>
        </div>

        <div class="footer">
            <p>© 2026 CiaoCV — <a href="https://www.ciaocv.com">Retour au site</a></p>
        </div>
    </div>
</body>
</html>
