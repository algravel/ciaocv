<?php
/**
 * Confirmation d'inscription par code email
 */
require_once __DIR__ . '/db.php';

$error = null;
$success = null;
$email = trim($_GET['email'] ?? $_POST['email'] ?? '');
$code = trim($_GET['code'] ?? $_POST['code'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $code = trim($_POST['code'] ?? '');

    if (empty($email) || empty($code)) {
        $error = 'Veuillez entrer votre courriel et le code reçu.';
    } else {
        try {
            $stmt = $db->prepare('
                SELECT u.id, u.email, ec.id as conf_id
                FROM users u
                JOIN email_confirmations ec ON ec.user_id = u.id
                WHERE u.email = ? AND ec.code = ? AND ec.expires_at > NOW()
                ORDER BY ec.created_at DESC
                LIMIT 1
            ');
            $stmt->execute([$email, $code]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                // Marquer l'email comme vérifié et supprimer le code
                $db->prepare('UPDATE users SET email_verified = 1 WHERE id = ?')->execute([$result['id']]);
                $db->prepare('DELETE FROM email_confirmations WHERE id = ?')->execute([$result['conf_id']]);
                $success = 'Votre compte est confirmé ! Vous pouvez maintenant vous connecter.';
            } else {
                // Vérifier si code expiré
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

// Auto-confirmation si email et code dans l'URL (lien du courriel)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($email) && !empty($code) && $db) {
    $_POST['email'] = $email;
    $_POST['code'] = $code;
    // Simuler un POST
    try {
        $stmt = $db->prepare('
            SELECT u.id, u.email, ec.id as conf_id
            FROM users u
            JOIN email_confirmations ec ON ec.user_id = u.id
            WHERE u.email = ? AND ec.code = ? AND ec.expires_at > NOW()
            ORDER BY ec.created_at DESC
            LIMIT 1
        ');
        $stmt->execute([strtolower($email), $code]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $db->prepare('UPDATE users SET email_verified = 1 WHERE id = ?')->execute([$result['id']]);
            $db->prepare('DELETE FROM email_confirmations WHERE id = ?')->execute([$result['conf_id']]);
            $success = 'Votre compte est confirmé ! Vous pouvez maintenant vous connecter.';
        } else {
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
                $error = 'Code invalide ou courriel incorrect.';
            }
        }
    } catch (PDOException $e) {
        $error = 'Erreur. Réessayez plus tard.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmer mon compte - CiaoCV</title>
    <style>
        :root { --primary: #2563eb; --primary-dark: #1e40af; --bg: #111827; --card-bg: #1f2937; --text: #f9fafb; --text-light: #9ca3af; --border: #374151; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: system-ui, sans-serif; }
        body { background: var(--bg); color: var(--text); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem; }
        .card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 1rem; padding: 2rem; max-width: 400px; width: 100%; }
        .logo { font-size: 1.5rem; font-weight: 800; color: var(--primary); margin-bottom: 1.5rem; text-decoration: none; display: block; }
        h1 { font-size: 1.25rem; margin-bottom: 1rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-size: 0.9rem; margin-bottom: 0.5rem; color: var(--text-light); }
        .form-group input { width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 0.5rem; background: #111; color: var(--text); font-size: 1rem; }
        .form-group input.code-input { text-align: center; letter-spacing: 0.5rem; font-size: 1.5rem; font-weight: bold; }
        .btn { width: 100%; padding: 0.75rem 1.5rem; background: var(--primary); color: white; border: none; border-radius: 0.5rem; font-weight: 600; font-size: 1rem; cursor: pointer; }
        .btn:hover { background: var(--primary-dark); }
        .error { background: rgba(239,68,68,0.2); color: #f87171; padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.9rem; }
        .success { background: rgba(34,197,94,0.2); color: #4ade80; padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.9rem; }
        .back { display: inline-block; margin-top: 1.5rem; color: var(--text-light); text-decoration: none; font-size: 0.9rem; }
        .back:hover { color: var(--primary); }
    </style>
</head>
<body>
    <div class="card">
        <a href="index.php" class="logo">CiaoCV</a>
        <h1>Confirmer mon compte</h1>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
            <a href="login.php" class="btn" style="display:block;text-align:center;text-decoration:none;margin-top:1rem;">Se connecter</a>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label for="email">Courriel</label>
                    <input type="email" id="email" name="email" placeholder="votre@email.com" required value="<?= htmlspecialchars($email) ?>">
                </div>
                <div class="form-group">
                    <label for="code">Code de confirmation (6 chiffres)</label>
                    <input type="text" id="code" name="code" class="code-input" maxlength="6" pattern="[0-9]{6}" placeholder="000000" required value="<?= htmlspecialchars($code) ?>">
                </div>
                <button type="submit" class="btn">Confirmer</button>
            </form>
        <?php endif; ?>

        <a href="login.php" class="back">← Retour à la connexion</a>
    </div>
</body>
</html>
