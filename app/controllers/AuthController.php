<?php
/**
 * Contrôleur d'authentification.
 * Gère la connexion (email + mot de passe + OTP par courriel), la déconnexion.
 */
class AuthController extends Controller
{
    private const OTP_VALIDITY_SECONDS = 600; // 10 minutes

    /**
     * Afficher la page de connexion.
     */
    public function login(): void
    {
        // Déjà connecté → rediriger vers le tableau de bord
        if ($this->isAuthenticated()) {
            $this->redirect('/tableau-de-bord');
        }

        // Sous-titre dynamique selon le type de visiteur
        $loginType = $_GET['type'] ?? '';
        $subtitleKey = 'login.hero.subtitle';

        if ($loginType === 'candidat') {
            $subtitleKey = 'login.hero.subtitle.candidat';
        } elseif ($loginType === 'entreprise') {
            $subtitleKey = 'login.hero.subtitle.entreprise';
        }

        // Gestion étape OTP
        $step = $_GET['step'] ?? '';
        $showOtpModal = ($step === 'otp' && isset($_SESSION['app_otp_code']));
        $error = '';
        $errorKey = '';

        if (($_GET['error'] ?? '') === 'otp_expired') {
            $this->clearOtpSession();
            $error = 'Le code a expiré. Veuillez vous reconnecter.';
            $errorKey = 'login.error.otp_expired';
        }

        $this->view('auth.login', [
            'subtitleKey' => $subtitleKey,
            'error' => $error,
            'errorKey' => $errorKey,
            'errorHtml' => false,
            'showOtpModal' => $showOtpModal,
            'otpEmail' => $showOtpModal ? $this->maskEmail($_SESSION['app_otp_email'] ?? '') : '',
        ], 'auth');
    }

    /**
     * Traiter le formulaire de connexion (étape 1: email + mot de passe).
     */
    public function authenticate(): void
    {
        // Cloudflare Turnstile — commenté, remplacé par 2FA par courriel
        // $turnstileSecret = $_ENV['TURNSTILE_SECRET_KEY'] ?? '';
        // if ($turnstileSecret !== '') {
        //     $token = trim($_POST['cf-turnstile-response'] ?? '');
        //     $remoteIp = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
        //     if (is_string($remoteIp) && strpos($remoteIp, ',') !== false) {
        //         $remoteIp = trim(explode(',', $remoteIp)[0]);
        //     }
        //     $validation = turnstile_verify($token, $remoteIp);
        //     if (empty($validation['success'])) {
        //         $loginType = $_GET['type'] ?? '';
        //         $subtitleKey = $loginType === 'candidat' ? 'login.hero.subtitle.candidat' : ($loginType === 'entreprise' ? 'login.hero.subtitle.entreprise' : 'login.hero.subtitle');
        //         $this->view('auth.login', [
        //             'subtitleKey' => $subtitleKey,
        //             'error'       => 'Vérification de sécurité échouée. Veuillez réessayer.',
        //             'errorKey'    => 'login.error.turnstile',
        //             'errorHtml'   => false,
        //         ], 'auth');
        //         return;
        //     }
        // }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $loginType = $_GET['type'] ?? '';
        $subtitleKey = $loginType === 'candidat' ? 'login.hero.subtitle.candidat' : ($loginType === 'entreprise' ? 'login.hero.subtitle.entreprise' : 'login.hero.subtitle');

        if ($email === '') {
            $this->view('auth.login', [
                'subtitleKey' => $subtitleKey,
                'error' => 'Veuillez entrer votre courriel.',
                'errorKey' => 'login.error.email_required',
                'errorHtml' => false,
                'showOtpModal' => false,
                'otpEmail' => '',
            ], 'auth');
            return;
        }

        if ($password === '') {
            $this->view('auth.login', [
                'subtitleKey' => $subtitleKey,
                'error' => 'Veuillez entrer votre mot de passe.',
                'errorKey' => 'login.error.password_required',
                'errorHtml' => false,
                'showOtpModal' => false,
                'otpEmail' => '',
            ], 'auth');
            return;
        }

        // Charger gestion/config pour accéder à PlatformUser, Database, Encryption
        require_once dirname(__DIR__, 2) . '/gestion/config.php';

        try {
            $platformUserModel = new PlatformUser();
            $user = $platformUserModel->findByEmail($email);
        } catch (Throwable $e) {
            $this->view('auth.login', [
                'subtitleKey' => $subtitleKey,
                'error' => 'Une erreur est survenue. Veuillez réessayer.',
                'errorKey' => 'login.error.generic',
                'errorHtml' => false,
                'showOtpModal' => false,
                'otpEmail' => '',
            ], 'auth');
            return;
        }

        // Vérifier identifiants
        if (!$user || empty($user['password_hash']) || !$platformUserModel->verifyPassword($password, $user['password_hash'])) {
            $this->view('auth.login', [
                'subtitleKey' => $subtitleKey,
                'error' => 'Courriel ou mot de passe incorrect.',
                'errorKey' => 'login.error.invalid',
                'errorHtml' => false,
                'showOtpModal' => false,
                'otpEmail' => '',
            ], 'auth');
            return;
        }

        // Vérifier que le compte est actif
        if (empty($user['active'])) {
            $this->view('auth.login', [
                'subtitleKey' => $subtitleKey,
                'error' => 'Votre compte est désactivé. Contactez votre administrateur.',
                'errorKey' => 'login.error.inactive',
                'errorHtml' => false,
                'showOtpModal' => false,
                'otpEmail' => '',
            ], 'auth');
            return;
        }

        // 2FA temporairement désactivé — connexion directe après validation du mot de passe
        // TODO: réactiver l'envoi OTP et la redirection /connexion?step=otp
        /*
        $otpCode = (string) random_int(100000, 999999);
        $lang = (trim($_POST['lang'] ?? '') === 'en') ? 'en' : 'fr';
        $sent = zeptomail_send_otp($user['email'], $user['name'] ?? 'Utilisateur', $otpCode, $lang, '#2563EB');

        if (!$sent) {
            $this->view('auth.login', [
                'subtitleKey' => $subtitleKey,
                'error' => "Impossible d'envoyer le code de vérification. Veuillez réessayer.",
                'errorKey' => 'login.error.otp_send_failed',
                'errorHtml' => false,
                'showOtpModal' => false,
                'otpEmail' => '',
            ], 'auth');
            return;
        }

        $_SESSION['app_otp_code'] = $otpCode;
        $_SESSION['app_otp_email'] = $user['email'];
        $_SESSION['app_otp_expires'] = time() + self::OTP_VALIDITY_SECONDS;
        $_SESSION['app_otp_user_id'] = $user['id'];
        $_SESSION['app_otp_user_name'] = $user['name'] ?? 'Utilisateur';
        $_SESSION['app_otp_company'] = '';
        $this->redirect('/connexion?step=otp');
        */

        // Connexion directe (sans OTP)
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'] ?? 'Utilisateur';
        $_SESSION['user_role'] = $user['role'] ?? 'client';
        $companyName = '';
        $companyOwnerId = null;
        try {
            require_once dirname(__DIR__, 2) . '/gestion/config.php';
            $entrepriseModel = new Entreprise();
            $ent = $entrepriseModel->getByPlatformUserId($user['id']);
            if ($ent && !empty($ent['name'])) {
                $companyName = $ent['name'];
            } else {
                // Pas de propre entreprise : vérifier si l'utilisateur est membre d'une entreprise (accès partagé)
                require_once __DIR__ . '/../models/CompanyMember.php';
                $ownerId = CompanyMember::getOwnerForMember($user['id']);
                if ($ownerId !== null) {
                    $companyOwnerId = $ownerId;
                    $ent = $entrepriseModel->getByPlatformUserId($ownerId);
                    if ($ent && !empty($ent['name'])) {
                        $companyName = $ent['name'];
                    }
                }
            }
        } catch (Throwable $e) {
            // table pas encore créée
        }
        $_SESSION['company_name'] = $companyName;
        if ($companyOwnerId !== null) {
            $_SESSION['company_owner_id'] = $companyOwnerId;
        }

        $this->redirect($_SESSION['user_role'] === 'evaluateur' ? '/affichages' : '/tableau-de-bord');
    }

    /**
     * Vérifier le code OTP (étape 2).
     */
    public function verifyOtp(): void
    {
        if (!isset($_SESSION['app_otp_code'])) {
            $this->redirect('/connexion');
            return;
        }

        // Vérifier expiration
        if (time() > ($_SESSION['app_otp_expires'] ?? 0)) {
            $this->clearOtpSession();
            $this->redirect('/connexion?error=otp_expired');
            return;
        }

        $userOtp = trim($_POST['otp'] ?? '');
        $storedOtp = $_SESSION['app_otp_code'] ?? '';

        if ($userOtp === '' || !hash_equals($storedOtp, $userOtp)) {
            $this->view('auth.login', [
                'subtitleKey' => 'login.hero.subtitle',
                'error' => 'Code invalide ou expiré. Veuillez réessayer.',
                'errorKey' => 'login.error.otp_invalid',
                'errorHtml' => false,
                'showOtpModal' => true,
                'otpEmail' => $this->maskEmail($_SESSION['app_otp_email'] ?? ''),
            ], 'auth');
            return;
        }

        // OTP valide — créer la session authentifiée
        $userId = $_SESSION['app_otp_user_id'];
        $userEmail = $_SESSION['app_otp_email'];
        $userName = $_SESSION['app_otp_user_name'];

        $_SESSION['user_id'] = $userId;
        $_SESSION['user_email'] = $userEmail;
        $_SESSION['user_name'] = $userName;
        // Récupérer le rôle utilisateur
        $userRole = 'client';
        try {
            require_once dirname(__DIR__, 2) . '/gestion/config.php';
            $platformUserModel = new PlatformUser();
            $pu = $platformUserModel->findById($userId);
            if ($pu && !empty($pu['role'])) {
                $userRole = $pu['role'];
            }
        } catch (Throwable $e) {
            // ignorer
        }
        $_SESSION['user_role'] = $userRole;
        // Récupérer le nom d'entreprise depuis la DB (pas d'auto-génération)
        $companyName = '';
        $companyOwnerId = null;
        try {
            require_once dirname(__DIR__, 2) . '/gestion/config.php';
            $entrepriseModel = new Entreprise();
            $ent = $entrepriseModel->getByPlatformUserId($userId);
            if ($ent && !empty($ent['name'])) {
                $companyName = $ent['name'];
            } else {
                require_once __DIR__ . '/../models/CompanyMember.php';
                $ownerId = CompanyMember::getOwnerForMember($userId);
                if ($ownerId !== null) {
                    $companyOwnerId = $ownerId;
                    $ent = $entrepriseModel->getByPlatformUserId($ownerId);
                    if ($ent && !empty($ent['name'])) {
                        $companyName = $ent['name'];
                    }
                }
            }
        } catch (Throwable $e) {
            // table pas encore créée
        }
        $_SESSION['company_name'] = $companyName;
        if ($companyOwnerId !== null) {
            $_SESSION['company_owner_id'] = $companyOwnerId;
        }

        $this->clearOtpSession();
        $this->redirect($_SESSION['user_role'] === 'evaluateur' ? '/affichages' : '/tableau-de-bord');
    }

    /**
     * Mot de passe oublié : génère un nouveau mot de passe et l'envoie par courriel.
     * POST /connexion/mot-de-passe-oublie
     */
    public function forgotPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'Méthode invalide'], 405);
            return;
        }
        $email = trim($_POST['email'] ?? '');
        if ($email === '' || strpos($email, '@') === false) {
            $this->json(['success' => false, 'error' => 'Veuillez entrer une adresse courriel valide.']);
            return;
        }
        if (!isset($_POST['_csrf_token']) || !isset($_SESSION['_csrf_token']) || !hash_equals($_SESSION['_csrf_token'], $_POST['_csrf_token'] ?? '')) {
            $this->json(['success' => false, 'error' => 'Erreur de sécurité. Rechargez la page et réessayez.']);
            return;
        }
        try {
            require_once dirname(__DIR__, 2) . '/gestion/config.php';
            $platformUserModel = new PlatformUser();
            $user = $platformUserModel->findByEmail($email);
            if (!$user) {
                $this->json(['success' => false, 'error' => 'Aucun compte associé à cette adresse courriel.']);
                return;
            }
            if (empty($user['active'])) {
                $this->json(['success' => false, 'error' => 'Ce compte est désactivé. Contactez votre administrateur.']);
                return;
            }
            $newPassword = bin2hex(random_bytes(8));
            if (!$platformUserModel->resetPassword($user['id'], $newPassword)) {
                $this->json(['success' => false, 'error' => 'Une erreur est survenue. Veuillez réessayer.']);
                return;
            }
            $fullName = $user['name'] ?? 'Utilisateur';
            $sent = zeptomail_send_platform_user_password_reset($user['email'], $fullName, $newPassword);
            if (!$sent) {
                $this->json(['success' => false, 'error' => 'Impossible d\'envoyer le courriel. Veuillez réessayer plus tard.']);
                return;
            }
            $this->json(['success' => true, 'message' => 'Un nouveau mot de passe a été envoyé à votre adresse courriel.']);
        } catch (Throwable $e) {
            error_log('AuthController::forgotPassword: ' . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Une erreur est survenue. Veuillez réessayer.']);
        }
    }

    /**
     * Déconnecter l'utilisateur.
     */
    public function logout(): void
    {
        session_destroy();
        $this->redirect('/connexion');
    }

    /**
     * Nettoyer les variables OTP de la session.
     */
    private function clearOtpSession(): void
    {
        unset(
            $_SESSION['app_otp_code'],
            $_SESSION['app_otp_email'],
            $_SESSION['app_otp_expires'],
            $_SESSION['app_otp_user_id'],
            $_SESSION['app_otp_user_name'],
            $_SESSION['app_otp_company']
        );
    }

    /**
     * Masquer partiellement un courriel : j***n@example.com
     */
    private function maskEmail(string $email): string
    {
        if (strpos($email, '@') === false) {
            return $email;
        }
        [$local, $domain] = explode('@', $email, 2);
        $len = strlen($local);
        if ($len <= 2) {
            $masked = str_repeat('*', $len);
        } else {
            $masked = $local[0] . str_repeat('*', $len - 2) . $local[$len - 1];
        }
        return $masked . '@' . $domain;
    }
}
