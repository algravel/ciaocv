<?php
/**
 * Accès évaluateur : lien reçu par courriel.
 * 1. Affiche la page de confirmation d'identité (code envoyé par courriel).
 * 2. Envoie le code à l'email de l'évaluateur.
 * 3. Vérifie le code et crée la session, puis redirige vers la liste des candidats.
 */
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$evaluator = null;
$error = null;
$codeSent = isset($_GET['sent']) || isset($_GET['send']);
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'app.ciaocv.com');

// Créer table evaluator_access_codes si besoin
if ($db) {
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS evaluator_access_codes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            token VARCHAR(64) NOT NULL,
            code CHAR(6) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_token_expires (token, expires_at)
        )");
    } catch (PDOException $e) {}
}

if ($token && $db) {
    $stmt = $db->prepare('SELECT * FROM job_evaluators WHERE access_token = ?');
    $stmt->execute([$token]);
    $evaluator = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($evaluator) {
        $expires = $evaluator['token_expires_at'] ?? null;
        if ($expires && strtotime($expires) < time()) {
            $evaluator = null;
            $error = 'Ce lien a expiré. Demandez une nouvelle invitation à l\'employeur.';
        }
    } else {
        $error = 'Lien invalide ou expiré.';
    }
}

// Envoyer le code par courriel
$tokenForSend = $token ?: trim($_POST['token'] ?? '');
if ($tokenForSend && $db && ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_code']) || isset($_GET['send']) && $_GET['send'] === '1')) {
    $stmt = $db->prepare('SELECT * FROM job_evaluators WHERE access_token = ?');
    $stmt->execute([$tokenForSend]);
    $evalRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($evalRow) {
        $expires = $evalRow['token_expires_at'] ?? null;
        if (!$expires || strtotime($expires) >= time()) {
            $code = sprintf('%06d', random_int(0, 999999));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
            $db->prepare('DELETE FROM evaluator_access_codes WHERE token = ?')->execute([$tokenForSend]);
            $stmtIns = $db->prepare('INSERT INTO evaluator_access_codes (token, code, expires_at) VALUES (?, ?, ?)');
            $stmtIns->execute([$tokenForSend, $code, $expiresAt]);
            $html = '<p>Votre code de confirmation CiaoCV : <strong>' . $code . '</strong></p>';
            $html .= '<p>Ce code expire dans 5 minutes. Si vous n\'avez pas demandé cet accès, ignorez ce message.</p>';
            $html .= '<p style="color:#64748b;font-size:0.875rem;">CiaoCV</p>';
            send_zepto($evalRow['email'], 'Votre code de confirmation – CiaoCV', $html);
            header('Location: evaluator-access.php?token=' . urlencode($tokenForSend) . '&sent=1');
            exit;
        }
    }
}

// Vérifier le code et créer la session
if ($evaluator && $db && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_code'])) {
    $code = preg_replace('/\D/', '', $_POST['code'] ?? '');
    if (strlen($code) === 6) {
        $stmt = $db->prepare('SELECT * FROM evaluator_access_codes WHERE token = ? AND code = ? AND expires_at > NOW()');
        $stmt->execute([$token, $code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $db->prepare('DELETE FROM evaluator_access_codes WHERE token = ?')->execute([$token]);
            $_SESSION['evaluator_token'] = $token;
            $_SESSION['evaluator_email'] = $evaluator['email'];
            $_SESSION['evaluator_name'] = $evaluator['name'] ?? $evaluator['email'];
            $_SESSION['evaluator_job_id'] = (int)$evaluator['job_id'];
            header('Location: evaluator-job-view.php?job=' . (int)$evaluator['job_id']);
            exit;
        }
    }
    $error = 'Code invalide ou expiré. Demandez un nouveau code.';
}

if (!$evaluator && !$error) $error = 'Lien invalide.';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmer votre accès – CiaoCV</title>
    <link rel="stylesheet" href="assets/css/design-system.css?v=<?= defined('ASSET_VERSION') ? ASSET_VERSION : time() ?>">
</head>
<body>
    <div class="app-shell">
        <main class="app-main" style="max-width:480px;margin:0 auto;padding:2rem;">
            <div class="card" style="padding:1.5rem;">
                <h1 style="margin:0 0 1rem 0;">Accès évaluateur</h1>
                <?php if ($error && !$evaluator): ?>
                    <p class="error"><?= htmlspecialchars($error) ?></p>
                    <p><a href="<?= htmlspecialchars($baseUrl) ?>" class="btn">Retour à l'accueil</a></p>
                <?php elseif ($evaluator): ?>
                    <?php if ($error): ?>
                        <p class="error"><?= htmlspecialchars($error) ?></p>
                    <?php endif; ?>
                    <p>Pour accéder aux candidats à évaluer, confirmez que vous êtes bien <strong><?= htmlspecialchars($evaluator['email']) ?></strong>.</p>
                    <?php if (!$codeSent): ?>
                        <p class="hint">Un code à 6 chiffres sera envoyé à cette adresse courriel.</p>
                        <form method="POST" style="margin-top:1rem;">
                            <input type="hidden" name="send_code" value="1">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                            <button type="submit" class="btn">Envoyer le code</button>
                        </form>
                    <?php else: ?>
                        <p class="success" style="margin-top:0.5rem;">Un code a été envoyé à <?= htmlspecialchars($evaluator['email']) ?>.</p>
                        <form method="POST" style="margin-top:1rem;">
                            <input type="hidden" name="verify_code" value="1">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                            <div class="form-group">
                                <label for="code">Code à 6 chiffres *</label>
                                <input type="text" id="code" name="code" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" placeholder="000000" required
                                       style="width:8rem;padding:0.75rem;font-size:1.25rem;letter-spacing:0.25em;text-align:center;">
                            </div>
                            <button type="submit" class="btn">Confirmer et accéder</button>
                        </form>
                        <p class="hint" style="margin-top:0.75rem;"><a href="evaluator-access.php?token=<?= urlencode($token) ?>&send=1">Renvoyer un code</a></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
