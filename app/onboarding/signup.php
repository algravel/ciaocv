<?php
/**
 * Étape 1 - Création de compte
 * Prénom + Email + Mot de passe + OAuth (grisés)
 */
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/functions.php';

// Si déjà connecté, rediriger vers l'étape actuelle
if (isset($_SESSION['user_id']) && $db) {
    $stmt = $db->prepare('SELECT onboarding_step, onboarding_completed FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userData) {
        $step = (int) ($userData['onboarding_step'] ?? 2);
        $completed = (bool) $userData['onboarding_completed'];

        $redirectUrls = [
            2 => 'step2-job-type.php',
            3 => 'step3-skills.php',
            4 => 'step4-personality.php',
            5 => 'step5-availability.php',
            6 => 'step6-video.php',
            7 => 'step7-tests.php',
            8 => 'step8-photo.php',
            9 => 'complete.php',
        ];

        if ($completed) {
            header('Location: complete.php');
        } elseif (isset($redirectUrls[$step])) {
            header('Location: ' . $redirectUrls[$step]);
        } else {
            header('Location: step2-job-type.php');
        }
        exit;
    }
}

$currentStep = 1;
$stepTitle = "Bienvenue";
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$db) {
        $error = 'Service temporairement indisponible.';
    } else {
        $firstName = trim($_POST['first_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (empty($firstName)) {
            $error = 'Veuillez entrer votre prénom.';
        } elseif (empty($email)) {
            $error = 'Veuillez entrer votre adresse courriel.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Adresse courriel invalide.';
        } elseif (strlen($password) < 8) {
            $error = 'Le mot de passe doit contenir au moins 8 caractères.';
        } else {
            try {
                // Vérifier si l'email existe déjà
                $stmt = $db->prepare('SELECT id, email_verified, onboarding_step FROM users WHERE email = ?');
                $stmt->execute([$email]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existing) {
                    if ($existing['email_verified']) {
                        $error = 'Cet email est déjà inscrit. <a href="../login.php" style="color:#60a5fa;">Se connecter</a>';
                    } else {
                        $error = 'Un compte existe déjà mais n\'est pas confirmé. <a href="../confirm.php?email=' . urlencode($email) . '" style="color:#60a5fa;">Confirmer</a>';
                    }
                } else {
                    // Créer le compte
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $db->prepare('INSERT INTO users (email, password_hash, first_name, onboarding_step, email_verified) VALUES (?, ?, ?, 1, 0)')->execute([$email, $passwordHash, $firstName]);
                    $userId = $db->lastInsertId();

                    // Générer code de confirmation
                    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $stmt = $db->prepare('INSERT INTO email_confirmations (user_id, code, expires_at) VALUES (?, ?, NOW() + INTERVAL 15 MINUTE)');
                    $stmt->execute([$userId, $code]);

                    // Envoyer l'email
                    $confirmUrl = 'https://app.ciaocv.com/onboarding/confirm.php?email=' . urlencode($email) . '&code=' . $code;
                    $html = '
                    <div style="font-family:sans-serif;max-width:500px;margin:0 auto;">
                        <h2 style="color:#2563eb;">CiaoCV</h2>
                        <p>Salut ' . htmlspecialchars($firstName) . ' !</p>
                        <p>Voici ton code de confirmation :</p>
                        <p style="font-size:32px;font-weight:bold;letter-spacing:6px;color:#2563eb;text-align:center;margin:24px 0;">' . $code . '</p>
                        <p style="text-align:center;margin:24px 0;">
                            <a href="' . $confirmUrl . '" style="display:inline-block;background:#2563eb;color:white;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:bold;">Confirmer mon compte</a>
                        </p>
                        <p style="color:#6b7280;font-size:14px;">Ce code expire dans 15 minutes.</p>
                    </div>';

                    $sent = send_zepto($email, 'Confirme ton inscription - CiaoCV', $html);

                    if ($sent) {
                        // Stocker l'email en session pour la page de confirmation
                        $_SESSION['pending_email'] = $email;
                        header('Location: confirm.php?email=' . urlencode($email));
                        exit;
                    } else {
                        $error = 'Erreur lors de l\'envoi de l\'email. Réessayez.';
                        $db->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
                    }
                }
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), "doesn't exist") !== false) {
                    $error = 'Base de données non configurée. <a href="../update-schema-onboarding.php" style="color:#60a5fa;">Exécuter la migration</a>';
                } else {
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
    <title>Inscription - CiaoCV</title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="../assets/css/design-system.css?v=<?= ASSET_VERSION ?>">
</head>

<body>
    <div class="onboarding-container">
        <?php include __DIR__ . '/../includes/onboarding-header.php'; ?>

        <h1 class="step-title">Bienvenue</h1>
        <p class="step-subtitle">On va te trouver un job qui te ressemble.</p>

        <div class="onboarding-content">
            <?php if ($error): ?>
                <div class="error"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="first_name">Prénom</label>
                    <input type="text" id="first_name" name="first_name" placeholder="Ton prénom" required
                        value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="email">Courriel</label>
                    <input type="email" id="email" name="email" placeholder="ton@email.com" required
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" placeholder="Minimum 8 caractères" required
                        minlength="8">
                </div>

                <button type="submit" class="btn">Continuer</button>
            </form>

            <div class="oauth-divider"><span>ou</span></div>

            <a href="../oauth-google.php?action=login" class="oauth-btn oauth-google">
                <svg viewBox="0 0 24 24">
                    <path fill="#4285F4"
                        d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                    <path fill="#34A853"
                        d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                    <path fill="#FBBC05"
                        d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                    <path fill="#EA4335"
                        d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                </svg>
                Continuer avec Google
            </a>

            <a href="../oauth-microsoft.php?action=login" class="oauth-btn oauth-microsoft">
                <svg viewBox="0 0 24 24">
                    <path fill="#F25022" d="M1 1h10v10H1z" />
                    <path fill="#00A4EF" d="M13 1h10v10H13z" />
                    <path fill="#7FBA00" d="M1 13h10v10H1z" />
                    <path fill="#FFB900" d="M13 13h10v10H13z" />
                </svg>
                Continuer avec Microsoft
            </a>
        </div>

        <div class="onboarding-footer">
            <p style="text-align:center;color:var(--text-light);font-size:0.85rem;">
                Déjà inscrit ? <a href="../login.php" style="color:var(--primary);">Se connecter</a>
            </p>
        </div>
    </div>
</body>

</html>