<?php
/**
 * Mot de passe oublié - Demande d'envoi d'un lien de réinitialisation par courriel
 */
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';

// Si déjà connecté, rediriger
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Veuillez entrer une adresse courriel valide.';
    } else {
        try {
            $stmt = $db->prepare('SELECT id, first_name FROM users WHERE email = ? AND email_verified = 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Toujours afficher le même message (éviter l'énumération de comptes)
            $success = 'Si un compte existe avec cette adresse, vous recevrez un courriel avec un lien pour réinitialiser votre mot de passe. Le lien expire dans 1 heure.';

            if ($user) {
                // Invalider les anciens tokens pour cet utilisateur
                $db->prepare('DELETE FROM password_reset_tokens WHERE user_id = ?')->execute([$user['id']]);

                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
                $stmt = $db->prepare('INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)');
                $stmt->execute([$user['id'], $token, $expiresAt]);

                $baseUrl = $_ENV['APP_URL'] ?? 'https://app.ciaocv.com';
                $resetUrl = rtrim($baseUrl, '/') . '/reset-password.php?token=' . urlencode($token);

                $firstName = $user['first_name'] ?? 'Bonjour';
                $html = '
                <div style="font-family:sans-serif;max-width:500px;margin:0 auto;">
                    <h2 style="color:#2563eb;">CiaoCV</h2>
                    <p>Salut ' . htmlspecialchars($firstName) . ',</p>
                    <p>Tu as demandé à réinitialiser ton mot de passe.</p>
                    <p style="text-align:center;margin:24px 0;">
                        <a href="' . htmlspecialchars($resetUrl) . '" style="display:inline-block;background:#2563eb;color:white;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:bold;">Réinitialiser mon mot de passe</a>
                    </p>
                    <p style="color:#6b7280;font-size:14px;">Ce lien expire dans 1 heure. Si tu n\'as pas fait cette demande, ignore ce courriel.</p>
                    <p style="color:#6b7280;font-size:12px;">Lien direct : ' . htmlspecialchars($resetUrl) . '</p>
                </div>';

                send_zepto($email, 'Réinitialiser ton mot de passe - CiaoCV', $html);
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                $error = 'Réinitialisation non disponible. <a href="update-schema-password-reset.php">Exécuter la migration</a>.';
            } else {
                $error = 'Erreur. Réessayez plus tard.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Mot de passe oublié - CiaoCV</title>
    <style>
        :root { --primary: #2563eb; --primary-dark: #1e40af; --bg: #111827; --card-bg: #1f2937; --text: #f9fafb; --text-light: #9ca3af; --border: #374151; --success: #22c55e; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', system-ui, sans-serif; }
        body { background: var(--bg); color: var(--text); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem; }
        .container { max-width: 420px; width: 100%; text-align: center; }
        .logo { font-size: 2rem; font-weight: 800; color: var(--primary); margin-bottom: 1.5rem; text-decoration: none; display: block; }
        .card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 1rem; padding: 2rem; text-align: left; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-size: 0.9rem; margin-bottom: 0.5rem; color: var(--text-light); }
        .form-group input { width: 100%; padding: 0.875rem 1rem; border: 1px solid var(--border); border-radius: 0.75rem; background: var(--bg); color: var(--text); font-size: 1rem; }
        .form-group input:focus { outline: none; border-color: var(--primary); }
        .btn { width: 100%; padding: 1rem; background: var(--primary); color: white; border: none; border-radius: 0.75rem; font-weight: 600; font-size: 1rem; cursor: pointer; }
        .btn:hover { background: var(--primary-dark); }
        .error { background: rgba(239,68,68,0.2); color: #f87171; padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.9rem; }
        .error a { color: #60a5fa; }
        .success { background: rgba(34,197,94,0.2); color: #22c55e; padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.9rem; }
        .back { display: inline-block; margin-top: 1.5rem; color: var(--text-light); text-decoration: none; font-size: 0.9rem; }
        .back:hover { color: var(--primary); }
        .footer { margin-top: 2rem; color: var(--text-light); font-size: 0.85rem; }
        .footer a { color: var(--primary); text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="logo">CiaoCV</a>
        <div class="card">
            <h1 style="font-size:1.25rem;margin-bottom:0.5rem;">Mot de passe oublié</h1>
            <p style="color:var(--text-light);font-size:0.9rem;margin-bottom:1.5rem;">Entrez votre courriel pour recevoir un lien de réinitialisation.</p>

            <?php if ($error): ?>
                <div class="error"><?= $error ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
                <a href="index.php" class="btn" style="display:block;text-align:center;text-decoration:none;">Retour à la connexion</a>
            <?php else: ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="email">Courriel</label>
                        <input type="email" id="email" name="email" placeholder="votre@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn">Envoyer le lien</button>
                </form>
            <?php endif; ?>

            <a href="index.php" class="back">← Retour à la connexion</a>
        </div>
        <p class="footer">© 2026 CiaoCV — <a href="https://www.ciaocv.com">Retour au site</a></p>
    </div>
</body>
</html>
