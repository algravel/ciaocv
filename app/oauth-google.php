<?php
/**
 * OAuth Google - Login / Callback
 * 
 * Usage:
 * - oauth-google.php?action=login  → Redirection vers Google
 * - oauth-google.php (callback)    → Traitement du retour Google
 */
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';

// Configuration OAuth
$clientId = $_ENV['GOOGLE_CLIENT_ID'] ?? '';
$clientSecret = $_ENV['GOOGLE_CLIENT_SECRET'] ?? '';
$redirectUri = 'https://app.ciaocv.com/oauth-google.php';

// Vérifier la configuration
if (empty($clientId) || empty($clientSecret)) {
    die('<p style="color:red;">Configuration OAuth Google manquante dans .env</p>');
}

// =====================
// ÉTAPE 1: Redirection vers Google
// =====================
if (isset($_GET['action']) && $_GET['action'] === 'login') {
    // Générer state CSRF
    $_SESSION['oauth_state'] = bin2hex(random_bytes(16));

    $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id' => $clientId,
        'redirect_uri' => $redirectUri,
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'state' => $_SESSION['oauth_state'],
        'access_type' => 'online',
        'prompt' => 'select_account'
    ]);

    header('Location: ' . $authUrl);
    exit;
}

// =====================
// ÉTAPE 2: Callback - Traitement du retour Google
// =====================

// Vérification du state CSRF
if (isset($_GET['state']) && $_GET['state'] !== ($_SESSION['oauth_state'] ?? '')) {
    die('<p style="color:red;">Erreur de sécurité (state invalide). <a href="index.php">Réessayer</a></p>');
}

// Erreur retournée par Google
if (isset($_GET['error'])) {
    header('Location: index.php?error=oauth_cancelled');
    exit;
}

// Code d'autorisation reçu
if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // Échanger le code contre un access token
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    $tokenData = [
        'code' => $code,
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri' => $redirectUri,
        'grant_type' => 'authorization_code'
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
        error_log('OAuth Google Error: ' . $tokenResponse);
        header('Location: index.php?error=oauth_token_error');
        exit;
    }

    $accessToken = $tokenJson['access_token'];

    // Récupérer les infos utilisateur
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://www.googleapis.com/oauth2/v2/userinfo',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken]
    ]);

    $userResponse = curl_exec($ch);
    curl_close($ch);

    $userInfo = json_decode($userResponse, true);

    if (!isset($userInfo['email'])) {
        error_log('OAuth Google User Error: ' . $userResponse);
        header('Location: index.php?error=oauth_user_error');
        exit;
    }

    $email = strtolower($userInfo['email']);
    $firstName = $userInfo['given_name'] ?? $userInfo['name'] ?? '';
    $googleId = $userInfo['id'];
    $photoUrl = $userInfo['picture'] ?? null;

    // Nettoyer le state
    unset($_SESSION['oauth_state']);

    // =====================
    // ÉTAPE 3: Créer ou connecter l'utilisateur
    // =====================
    try {
        // Chercher un utilisateur existant par OAuth ID ou email
        $stmt = $db->prepare('SELECT id, email, first_name, email_verified, oauth_provider, onboarding_completed FROM users WHERE (oauth_provider = ? AND oauth_id = ?) OR email = ?');
        $stmt->execute(['google', $googleId, $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Utilisateur existant - Mettre à jour les infos OAuth si nécessaire
            if (empty($user['oauth_provider'])) {
                $db->prepare('UPDATE users SET oauth_provider = ?, oauth_id = ?, email_verified = 1 WHERE id = ?')
                    ->execute(['google', $googleId, $user['id']]);
            }
            $userId = $user['id'];
            $userFirstName = $user['first_name'] ?: $firstName;
        } else {
            // Nouvel utilisateur - Créer le compte
            $stmt = $db->prepare('INSERT INTO users (email, first_name, oauth_provider, oauth_id, email_verified, onboarding_step, photo_url) VALUES (?, ?, ?, ?, 1, 2, ?)');
            $stmt->execute([$email, $firstName, 'google', $googleId, $photoUrl]);
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
        error_log('OAuth Google DB Error: ' . $e->getMessage());
        header('Location: index.php?error=oauth_db_error');
        exit;
    }
}

// Si aucun paramètre, rediriger vers login
header('Location: index.php');
exit;
