<?php
/**
 * OAuth Microsoft 365 - Login / Callback
 * 
 * Usage:
 * - oauth-microsoft.php?action=login  → Redirection vers Microsoft
 * - oauth-microsoft.php (callback)    → Traitement du retour Microsoft
 */
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';

// Configuration OAuth
$clientId = $_ENV['MICROSOFT_CLIENT_ID'] ?? '';
$clientSecret = $_ENV['MICROSOFT_CLIENT_SECRET'] ?? '';
$redirectUri = 'https://app.ciaocv.com/oauth-microsoft.php';
$tenant = 'common'; // Permet comptes personnels et pro

// Vérifier la configuration
if (empty($clientId) || empty($clientSecret)) {
    die('<p style="color:red;">Configuration OAuth Microsoft manquante dans .env</p>');
}

// =====================
// ÉTAPE 1: Redirection vers Microsoft
// =====================
if (isset($_GET['action']) && $_GET['action'] === 'login') {
    // Générer state CSRF
    $_SESSION['oauth_state'] = bin2hex(random_bytes(16));

    $authUrl = "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/authorize?" . http_build_query([
        'client_id' => $clientId,
        'redirect_uri' => $redirectUri,
        'response_type' => 'code',
        'scope' => 'openid email profile User.Read',
        'state' => $_SESSION['oauth_state'],
        'response_mode' => 'query',
        'prompt' => 'select_account'
    ]);

    header('Location: ' . $authUrl);
    exit;
}

// =====================
// ÉTAPE 2: Callback - Traitement du retour Microsoft
// =====================

// Vérification du state CSRF
if (isset($_GET['state']) && $_GET['state'] !== ($_SESSION['oauth_state'] ?? '')) {
    die('<p style="color:red;">Erreur de sécurité (state invalide). <a href="index.php">Réessayer</a></p>');
}

// Erreur retournée par Microsoft
if (isset($_GET['error'])) {
    error_log('OAuth Microsoft Error: ' . ($_GET['error_description'] ?? $_GET['error']));
    header('Location: index.php?error=oauth_cancelled');
    exit;
}

// Code d'autorisation reçu
if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // Échanger le code contre un access token
    $tokenUrl = "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token";
    $tokenData = [
        'code' => $code,
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri' => $redirectUri,
        'grant_type' => 'authorization_code',
        'scope' => 'openid email profile User.Read'
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $tokenUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($tokenData),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
    ]);

    $tokenResponse = curl_exec($ch);
    curl_close($ch);

    $tokenJson = json_decode($tokenResponse, true);

    if (!isset($tokenJson['access_token'])) {
        error_log('OAuth Microsoft Token Error: ' . $tokenResponse);
        header('Location: index.php?error=oauth_token_error');
        exit;
    }

    $accessToken = $tokenJson['access_token'];

    // Récupérer les infos utilisateur via Microsoft Graph API
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://graph.microsoft.com/v1.0/me',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken]
    ]);

    $userResponse = curl_exec($ch);
    curl_close($ch);

    $userInfo = json_decode($userResponse, true);

    // Microsoft peut retourner mail ou userPrincipalName
    $email = strtolower($userInfo['mail'] ?? $userInfo['userPrincipalName'] ?? '');

    if (empty($email)) {
        error_log('OAuth Microsoft User Error: ' . $userResponse);
        header('Location: index.php?error=oauth_user_error');
        exit;
    }

    $firstName = $userInfo['givenName'] ?? $userInfo['displayName'] ?? '';
    $microsoftId = $userInfo['id'];

    // Nettoyer le state
    unset($_SESSION['oauth_state']);

    // =====================
    // ÉTAPE 3: Créer ou connecter l'utilisateur
    // =====================
    try {
        // Chercher un utilisateur existant par OAuth ID ou email
        $stmt = $db->prepare('SELECT id, email, first_name, email_verified, oauth_provider, onboarding_completed FROM users WHERE (oauth_provider = ? AND oauth_id = ?) OR email = ?');
        $stmt->execute(['microsoft', $microsoftId, $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Utilisateur existant - Mettre à jour les infos OAuth si nécessaire
            if (empty($user['oauth_provider'])) {
                $db->prepare('UPDATE users SET oauth_provider = ?, oauth_id = ?, email_verified = 1 WHERE id = ?')
                    ->execute(['microsoft', $microsoftId, $user['id']]);
            }
            $userId = $user['id'];
            $userFirstName = $user['first_name'] ?: $firstName;
        } else {
            // Nouvel utilisateur - Créer le compte
            $stmt = $db->prepare('INSERT INTO users (email, first_name, oauth_provider, oauth_id, email_verified, onboarding_step) VALUES (?, ?, ?, ?, 1, 2)');
            $stmt->execute([$email, $firstName, 'microsoft', $microsoftId]);
            $userId = $db->lastInsertId();
            $userFirstName = $firstName;
        }

        // Connecter l'utilisateur
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_first_name'] = $userFirstName ?: (strstr($email, '@', true) ?: $email);

        // Rediriger vers l'espace candidat
        header('Location: candidate-jobs.php');
        exit;

    } catch (PDOException $e) {
        error_log('OAuth Microsoft DB Error: ' . $e->getMessage());
        header('Location: index.php?error=oauth_db_error');
        exit;
    }
}

// Si aucun paramètre, rediriger vers login
header('Location: index.php');
exit;
