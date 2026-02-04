<?php
/**
 * Confirmation du code email pour l'onboarding
 * Redirige vers l'étape 2 après confirmation
 */
session_start();
require_once __DIR__ . '/../db.php';

$currentStep = 1;
$stepTitle = "Confirmation";
$error = null;
$success = null;
$email = trim($_GET['email'] ?? $_POST['email'] ?? $_SESSION['pending_email'] ?? '');
$code = trim($_GET['code'] ?? $_POST['code'] ?? '');

// Auto-confirmation si code dans l'URL
if (!empty($email) && !empty($code) && $db) {
    try {
        $stmt = $db->prepare('
            SELECT u.id, u.email, u.first_name, ec.id as conf_id
            FROM users u
            JOIN email_confirmations ec ON ec.user_id = u.id
            WHERE u.email = ? AND ec.code = ? AND ec.expires_at > NOW()
            ORDER BY ec.created_at DESC
            LIMIT 1
        ');
        $stmt->execute([strtolower($email), $code]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            // Marquer comme vérifié et supprimer le code
            $db->prepare('UPDATE users SET email_verified = 1, onboarding_step = 2 WHERE id = ?')->execute([$result['id']]);
            $db->prepare('DELETE FROM email_confirmations WHERE id = ?')->execute([$result['conf_id']]);

            // Créer la session
            $_SESSION['user_id'] = $result['id'];
            $_SESSION['user_email'] = $result['email'];
            $_SESSION['user_first_name'] = $result['first_name'];
            unset($_SESSION['pending_email']);

            // Rediriger vers l'étape 2
            header('Location: step2-job-type.php');
            exit;
        } else {
            // Vérifier si le code est expiré
            $stmt2 = $db->prepare('
                SELECT ec.id FROM users u
                JOIN email_confirmations ec ON ec.user_id = u.id
                WHERE u.email = ? AND ec.code = ?
                LIMIT 1
            ');
            $stmt2->execute([strtolower($email), $code]);
            if ($stmt2->fetch()) {
                $error = 'Ce code a expiré. Veuillez vous réinscrire.';
            } else {
                $error = 'Code invalide.';
            }
        }
    } catch (PDOException $e) {
        $error = 'Erreur. Réessayez plus tard.';
    }
}

// Traitement du formulaire POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $code = trim($_POST['code'] ?? '');

    if (empty($email) || empty($code)) {
        $error = 'Veuillez entrer votre courriel et le code reçu.';
    } else {
        try {
            $stmt = $db->prepare('
                SELECT u.id, u.email, u.first_name, ec.id as conf_id
                FROM users u
                JOIN email_confirmations ec ON ec.user_id = u.id
                WHERE u.email = ? AND ec.code = ? AND ec.expires_at > NOW()
                ORDER BY ec.created_at DESC
                LIMIT 1
            ');
            $stmt->execute([$email, $code]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                $db->prepare('UPDATE users SET email_verified = 1, onboarding_step = 2 WHERE id = ?')->execute([$result['id']]);
                $db->prepare('DELETE FROM email_confirmations WHERE id = ?')->execute([$result['conf_id']]);

                $_SESSION['user_id'] = $result['id'];
                $_SESSION['user_email'] = $result['email'];
                $_SESSION['user_first_name'] = $result['first_name'];
                unset($_SESSION['pending_email']);

                header('Location: step2-job-type.php');
                exit;
            } else {
                $stmt2 = $db->prepare('
                    SELECT ec.id FROM users u
                    JOIN email_confirmations ec ON ec.user_id = u.id
                    WHERE u.email = ? AND ec.code = ?
                    LIMIT 1
                ');
                $stmt2->execute([$email, $code]);
                if ($stmt2->fetch()) {
                    $error = 'Ce code a expiré. Veuillez vous réinscrire.';
                } else {
                    $error = 'Code invalide ou courriel incorrect.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Erreur. Réessayez plus tard.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmer mon compte - CiaoCV</title>
    <link rel="icon" type="image/png" href="../../assets/img/favicon.png">
    <link rel="stylesheet" href="../assets/css/design-system.css?v=<?= ASSET_VERSION ?>">
    <style>
        .onboarding-container {
            max-width: 480px;
            margin: 0 auto;
            padding: 2rem;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: radial-gradient(circle at top right, rgba(37, 99, 235, 0.05), transparent 40%),
                radial-gradient(circle at bottom left, rgba(139, 92, 246, 0.05), transparent 40%);
        }
    </style>
</head>

<body>
    <div class="onboarding-container">
        <?php include __DIR__ . '/../includes/onboarding-header.php'; ?>

        <div style="margin: 2rem 0; text-align: center;">
            <h1 class="step-title"
                style="font-size: 2rem; font-weight: 800; letter-spacing: -0.025em; margin-bottom: 0.5rem;">C'est
                presque fini !</h1>
            <p class="step-subtitle" style="color: var(--text-secondary); font-size: 1rem;">On t'a envoyé un code à 6
                chiffres à <br><strong><?= htmlspecialchars($email) ?></strong> 📧</p>
        </div>

        <div class="onboarding-content glass" style="padding: 2rem; border-radius: 24px;">
            <?php if ($error): ?>
                <div class="error"
                    style="background: var(--error-bg); color: var(--error); padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; font-size: 0.9rem;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">

                <div class="form-group" style="margin-bottom: 2rem;">
                    <label for="code" style="text-align: center;">Code de confirmation</label>
                    <input type="text" id="code" name="code"
                        style="text-align:center;letter-spacing:0.5rem;font-size:1.75rem;font-weight:800;border-radius:12px;padding:1rem;background:white;"
                        maxlength="6" pattern="[0-9]{6}" placeholder="000000" required autocomplete="one-time-code"
                        inputmode="numeric">
                </div>

                <button type="submit" class="btn btn-primary"
                    style="width: 100%; padding: 1rem; border-radius: 50px; font-weight: 700;">Confirmer mon
                    compte</button>
            </form>

            <button class="btn-skip" onclick="location.href='signup.php'" style="margin-top: 1.5rem; opacity: 0.7;">←
                Modifier mon courriel</button>
        </div>
    </div>

    <script>
        // Auto-focus sur le champ code
        document.getElementById('code').focus();
    </script>
</body>

</html>