<?php
/**
 * Contrôleur principal de l'espace gestion (administration).
 */
class GestionController
{
    private const SESSION_USER_ID = 'gestion_user_id';
    private const SESSION_USER_EMAIL = 'gestion_user_email';
    private const SESSION_USER_NAME = 'gestion_user_name';
    private const SESSION_OTP_CODE = 'gestion_otp_code';
    private const SESSION_OTP_EMAIL = 'gestion_otp_email';
    private const SESSION_OTP_EXPIRES = 'gestion_otp_expires';
    private const SESSION_OTP_ADMIN_ID = 'gestion_otp_admin_id';
    private const SESSION_OTP_ADMIN_NAME = 'gestion_otp_admin_name';
    private const OTP_VALIDITY_SECONDS = 600;

    public function login(): void
    {
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        if ($this->isAuthenticated()) {
            $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord');
            return;
        }
        $step = $_GET['step'] ?? '';
        $showOtpModal = ($step === 'otp' && isset($_SESSION[self::SESSION_OTP_CODE]));
        $error = '';
        $errorKey = '';
        if (($_GET['error'] ?? '') === 'otp_expired') {
            $this->clearOtpSession();
            $error = 'Le code a expiré. Veuillez vous reconnecter.';
            $errorKey = 'login.error.otp_expired';
        }
        if ($showOtpModal && $error === '') {
            $error = '';
        }
        $this->view('login', [
            'subtitle'      => "Accédez à l'espace d'administration CiaoCV.",
            'error'         => $error,
            'errorKey'      => $errorKey,
            'errorHtml'     => false,
            'showOtpModal'  => $showOtpModal,
            'otpEmail'      => $showOtpModal ? $this->maskEmail($_SESSION[self::SESSION_OTP_EMAIL] ?? '') : '',
        ], 'auth');
    }

    public function authenticate(): void
    {
        $turnstileSecret = $_ENV['TURNSTILE_SECRET_KEY'] ?? '';
        if ($turnstileSecret !== '') {
            $token = trim($_POST['cf-turnstile-response'] ?? '');
            $remoteIp = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
            if (is_string($remoteIp) && strpos($remoteIp, ',') !== false) {
                $remoteIp = trim(explode(',', $remoteIp)[0]);
            }
            $validation = turnstile_verify($token, $remoteIp);
            if (empty($validation['success'])) {
                $this->view('login', [
                    'subtitle'  => "Accédez à l'espace d'administration CiaoCV.",
                    'error'     => 'Vérification de sécurité échouée. Veuillez réessayer.',
                    'errorKey'  => 'login.error.turnstile',
                    'errorHtml' => false,
                ], 'auth');
                return;
            }
        }
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($email === '') {
            $this->view('login', [
                'subtitle'  => "Accédez à l'espace d'administration CiaoCV.",
                'error'     => 'Veuillez entrer votre courriel.',
                'errorKey'  => 'login.error.email_required',
                'errorHtml' => false,
            ], 'auth');
            return;
        }
        if ($password === '') {
            $this->view('login', [
                'subtitle'  => "Accédez à l'espace d'administration CiaoCV.",
                'error'     => 'Veuillez entrer votre mot de passe.',
                'errorKey'  => 'login.error.password_required',
                'errorHtml' => false,
            ], 'auth');
            return;
        }
        try {
            $adminModel = new Admin();
            $admin = $adminModel->findByEmail($email);
        } catch (Throwable $e) {
            $this->view('login', [
                'subtitle'  => "Accédez à l'espace d'administration CiaoCV.",
                'error'     => 'Une erreur est survenue. Veuillez réessayer.',
                'errorKey'  => 'login.error.generic',
                'errorHtml' => false,
            ], 'auth');
            return;
        }
        if (!$admin || !$adminModel->verifyPassword($password, $admin['password_hash'])) {
            $this->view('login', [
                'subtitle'  => "Accédez à l'espace d'administration CiaoCV.",
                'error'     => 'Courriel ou mot de passe incorrect.',
                'errorKey'  => 'login.error.invalid',
                'errorHtml' => false,
            ], 'auth');
            return;
        }
        $otpCode = (string) random_int(100000, 999999);
        $sent = zeptomail_send_otp($admin['email'], $admin['name'] ?? 'Administrateur', $otpCode);
        if (!$sent) {
            $this->view('login', [
                'subtitle'  => "Accédez à l'espace d'administration CiaoCV.",
                'error'     => 'Impossible d\'envoyer le code par courriel. Veuillez réessayer.',
                'errorKey'  => 'login.error.otp_send_failed',
                'errorHtml' => false,
            ], 'auth');
            return;
        }
        $_SESSION[self::SESSION_OTP_CODE] = $otpCode;
        $_SESSION[self::SESSION_OTP_EMAIL] = $admin['email'];
        $_SESSION[self::SESSION_OTP_EXPIRES] = time() + self::OTP_VALIDITY_SECONDS;
        $_SESSION[self::SESSION_OTP_ADMIN_ID] = $admin['id'];
        $_SESSION[self::SESSION_OTP_ADMIN_NAME] = $admin['name'] ?? 'Administrateur';
        $this->redirect(GESTION_BASE_PATH . '/connexion?step=otp');
    }

    public function verifyOtp(): void
    {
        if (!isset($_SESSION[self::SESSION_OTP_CODE])) {
            $this->redirect(GESTION_BASE_PATH . '/connexion');
            return;
        }
        if (time() > ($_SESSION[self::SESSION_OTP_EXPIRES] ?? 0)) {
            $this->clearOtpSession();
            $this->redirect(GESTION_BASE_PATH . '/connexion?error=otp_expired');
            return;
        }
        $userOtp = trim($_POST['otp'] ?? '');
        $storedOtp = $_SESSION[self::SESSION_OTP_CODE] ?? '';
        if ($userOtp === '' || !hash_equals($storedOtp, $userOtp)) {
            $this->view('login', [
                'subtitle'  => "Accédez à l'espace d'administration CiaoCV.",
                'error'     => 'Code invalide ou expiré. Veuillez réessayer.',
                'errorKey'  => 'login.error.otp_invalid',
                'errorHtml' => false,
                'showOtpModal' => true,
                'otpEmail'  => $this->maskEmail($_SESSION[self::SESSION_OTP_EMAIL] ?? ''),
            ], 'auth');
            return;
        }
        $_SESSION[self::SESSION_USER_ID] = $_SESSION[self::SESSION_OTP_ADMIN_ID];
        $_SESSION[self::SESSION_USER_EMAIL] = $_SESSION[self::SESSION_OTP_EMAIL];
        $_SESSION[self::SESSION_USER_NAME] = $_SESSION[self::SESSION_OTP_ADMIN_NAME];
        $this->clearOtpSession();
        $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord');
    }

    private function clearOtpSession(): void
    {
        unset(
            $_SESSION[self::SESSION_OTP_CODE],
            $_SESSION[self::SESSION_OTP_EMAIL],
            $_SESSION[self::SESSION_OTP_EXPIRES],
            $_SESSION[self::SESSION_OTP_ADMIN_ID],
            $_SESSION[self::SESSION_OTP_ADMIN_NAME]
        );
    }

    private function maskEmail(string $email): string
    {
        if (strpos($email, '@') === false) {
            return $email;
        }
        list($local, $domain) = explode('@', $email, 2);
        $len = strlen($local);
        if ($len <= 2) {
            $masked = str_repeat('*', $len);
        } else {
            $masked = $local[0] . str_repeat('*', $len - 2) . $local[$len - 1];
        }
        return $masked . '@' . $domain;
    }

    public function syncPlans(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect(GESTION_BASE_PATH . '/connexion');
            return;
        }
        $plans = [
            ['Découverte', 'Discovery', 5, 0, 0],
            ['À la carte', 'Pay per use', 9999, 79, 79],
            ['Pro', 'Pro', 50, 139, 1188],
            ['Expert', 'Expert', 200, 199, 1788],
        ];
        try {
            $pdo = Database::get();
            $pdo->exec('DELETE FROM gestion_plans');
            $stmt = $pdo->prepare('INSERT INTO gestion_plans (name_fr, name_en, video_limit, price_monthly, price_yearly) VALUES (?, ?, ?, ?, ?)');
            foreach ($plans as $p) {
                $stmt->execute($p);
            }
            $_SESSION['gestion_flash_success'] = 'Forfaits synchronisés avec www.ciaocv.com/tarifs.';
        } catch (Throwable $e) {
            $_SESSION['gestion_flash_error'] = 'Impossible de synchroniser les forfaits.';
        }
        $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord#forfaits-crud');
    }

    public function createPlan(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect(GESTION_BASE_PATH . '/connexion');
            return;
        }
        $nameFr = trim($_POST['name_fr'] ?? '');
        $nameEn = trim($_POST['name_en'] ?? '');
        $videoLimit = (int) ($_POST['video_limit'] ?? 10);
        $priceMonthly = (float) str_replace(',', '.', $_POST['price_monthly'] ?? '0');
        $priceYearly = (float) str_replace(',', '.', $_POST['price_yearly'] ?? '0');
        if ($nameFr === '' || $nameEn === '') {
            $_SESSION['gestion_flash_error'] = 'Le nom en français et en anglais est requis.';
            $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord#forfaits-crud');
            return;
        }
        if ($videoLimit < 1) {
            $_SESSION['gestion_flash_error'] = 'La limite vidéos doit être au moins 1.';
            $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord#forfaits-crud');
            return;
        }
        if (!isset($_POST['_csrf_token']) || !hash_equals($_SESSION['_csrf_token'] ?? '', $_POST['_csrf_token'] ?? '')) {
            $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord');
            return;
        }
        try {
            $planModel = new Plan();
            $planModel->create($nameFr, $nameEn, $videoLimit, $priceMonthly, $priceYearly);
            $_SESSION['gestion_flash_success'] = 'Forfait créé avec succès.';
        } catch (Throwable $e) {
            $_SESSION['gestion_flash_error'] = 'Impossible de créer le forfait.';
        }
        $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord#forfaits-crud');
    }

    public function updatePlan(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect(GESTION_BASE_PATH . '/connexion');
            return;
        }
        $id = (int) ($_POST['id'] ?? 0);
        $nameFr = trim($_POST['name_fr'] ?? '');
        $nameEn = trim($_POST['name_en'] ?? '');
        $videoLimit = (int) ($_POST['video_limit'] ?? 10);
        $priceMonthly = (float) str_replace(',', '.', $_POST['price_monthly'] ?? '0');
        $priceYearly = (float) str_replace(',', '.', $_POST['price_yearly'] ?? '0');
        $active = isset($_POST['active']) && $_POST['active'] === '1';
        if ($id <= 0 || $nameFr === '' || $nameEn === '') {
            $_SESSION['gestion_flash_error'] = 'Données invalides.';
            $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord#forfaits-crud');
            return;
        }
        if ($videoLimit < 1) {
            $_SESSION['gestion_flash_error'] = 'La limite vidéos doit être au moins 1.';
            $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord#forfaits-crud');
            return;
        }
        try {
            $planModel = new Plan();
            if ($planModel->update($id, $nameFr, $nameEn, $videoLimit, $priceMonthly, $priceYearly, $active)) {
                $_SESSION['gestion_flash_success'] = 'Forfait mis à jour.';
            } else {
                $_SESSION['gestion_flash_error'] = 'Impossible de mettre à jour le forfait.';
            }
        } catch (Throwable $e) {
            $_SESSION['gestion_flash_error'] = 'Impossible de mettre à jour le forfait.';
        }
        $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord#forfaits-crud');
    }

    public function createAdmin(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect(GESTION_BASE_PATH . '/connexion');
            return;
        }
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? 'admin');
        if ($name === '' || $email === '') {
            $_SESSION['gestion_flash_error'] = 'Nom et courriel requis.';
            $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord#configuration');
            return;
        }
        if (!isset($_POST['_csrf_token']) || !hash_equals($_SESSION['_csrf_token'] ?? '', $_POST['_csrf_token'] ?? '')) {
            $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord#configuration');
            return;
        }
        $role = in_array($role, ['admin', 'viewer'], true) ? $role : 'admin';
        $adminModel = new Admin();
        if ($adminModel->findByEmail($email)) {
            $_SESSION['gestion_flash_error'] = 'Un administrateur avec ce courriel existe déjà.';
            $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord#configuration');
            return;
        }
        $newPassword = bin2hex(random_bytes(8));
        try {
            $adminModel->create($email, $newPassword, $name, $role);
        } catch (Throwable $e) {
            $_SESSION['gestion_flash_error'] = 'Impossible de créer le compte.';
            $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord#configuration');
            return;
        }
        $sent = zeptomail_send_new_admin_credentials($email, $name, $newPassword);
        if ($sent) {
            $_SESSION['gestion_flash_success'] = 'Administrateur créé. Les identifiants ont été envoyés par courriel à ' . $email . '.';
        } else {
            $_SESSION['gestion_flash_error'] = 'Compte créé mais l\'envoi du courriel a échoué. Contactez l\'administrateur pour lui transmettre ses identifiants.';
        }
        $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord#configuration');
    }

    public function updateAdmin(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect(GESTION_BASE_PATH . '/connexion');
            return;
        }
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? 'admin');
        if ($id <= 0 || $name === '' || $email === '') {
            $_SESSION['gestion_flash_error'] = 'Données invalides.';
            $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord#configuration');
            return;
        }
        if (!isset($_POST['_csrf_token']) || !hash_equals($_SESSION['_csrf_token'] ?? '', $_POST['_csrf_token'] ?? '')) {
            $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord#configuration');
            return;
        }
        $role = in_array($role, ['admin', 'viewer'], true) ? $role : 'admin';
        $adminModel = new Admin();
        if ($adminModel->update($id, $name, $email, $role)) {
            $_SESSION['gestion_flash_success'] = 'Administrateur mis à jour.';
        } else {
            $_SESSION['gestion_flash_error'] = 'Impossible de mettre à jour (courriel peut-être déjà utilisé).';
        }
        $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord#configuration');
    }

    public function changeOwnPassword(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect(GESTION_BASE_PATH . '/connexion');
            return;
        }
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $newPasswordConfirm = $_POST['new_password_confirm'] ?? '';
        if ($currentPassword === '' || $newPassword === '' || $newPasswordConfirm === '') {
            $_SESSION['gestion_flash_error'] = 'Tous les champs sont requis.';
            $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord#configuration');
            return;
        }
        if ($newPassword !== $newPasswordConfirm) {
            $_SESSION['gestion_flash_error'] = 'Les mots de passe ne correspondent pas.';
            $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord#configuration');
            return;
        }
        if (strlen($newPassword) < 8) {
            $_SESSION['gestion_flash_error'] = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
            $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord');
            return;
        }
        if (!isset($_POST['_csrf_token']) || !hash_equals($_SESSION['_csrf_token'] ?? '', $_POST['_csrf_token'] ?? '')) {
            $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord');
            return;
        }
        $adminModel = new Admin();
        $admin = $adminModel->findById((int) ($_SESSION[self::SESSION_USER_ID] ?? 0));
        if (!$admin || !$adminModel->verifyPassword($currentPassword, $admin['password_hash'])) {
            $_SESSION['gestion_flash_error'] = 'Mot de passe actuel incorrect.';
            $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord');
            return;
        }
        if ($adminModel->resetPassword($admin['id'], $newPassword)) {
            $_SESSION['gestion_flash_success'] = 'Votre mot de passe a été mis à jour.';
        } else {
            $_SESSION['gestion_flash_error'] = 'Impossible de mettre à jour le mot de passe.';
        }
        $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord');
    }

    public function resetAdminPassword(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect(GESTION_BASE_PATH . '/connexion');
            return;
        }
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['gestion_flash_error'] = 'Identifiant invalide.';
            $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord#configuration');
            return;
        }
        if (!isset($_POST['_csrf_token']) || !hash_equals($_SESSION['_csrf_token'] ?? '', $_POST['_csrf_token'] ?? '')) {
            $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord#configuration');
            return;
        }
        $adminModel = new Admin();
        $admin = $adminModel->findById($id);
        if (!$admin) {
            $_SESSION['gestion_flash_error'] = 'Administrateur introuvable.';
            $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord#configuration');
            return;
        }
        $newPassword = bin2hex(random_bytes(8));
        if (!$adminModel->resetPassword($id, $newPassword)) {
            $_SESSION['gestion_flash_error'] = 'Impossible de réinitialiser le mot de passe.';
            $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord#configuration');
            return;
        }
        $sent = zeptomail_send_password_reset($admin['email'], $admin['name'], $newPassword);
        if ($sent) {
            $_SESSION['gestion_flash_success'] = 'Un nouveau mot de passe a été envoyé par courriel à ' . $admin['email'] . '.';
        } else {
            $_SESSION['gestion_flash_error'] = 'Mot de passe mis à jour mais l\'envoi du courriel a échoué. Contactez l\'administrateur.';
        }
        $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord#configuration');
    }

    public function deleteAdmin(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect(GESTION_BASE_PATH . '/connexion');
            return;
        }
        $currentId = (int) ($_SESSION[self::SESSION_USER_ID] ?? 0);
        $targetId = (int) ($_POST['id'] ?? 0);
        if ($targetId <= 0) {
            $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord#configuration');
            return;
        }
        if ($targetId === $currentId) {
            $_SESSION['gestion_flash_error'] = 'Vous ne pouvez pas vous supprimer vous-même.';
            $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord#configuration');
            return;
        }
        if (!isset($_POST['_csrf_token']) || !hash_equals($_SESSION['_csrf_token'] ?? '', $_POST['_csrf_token'] ?? '')) {
            $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord#configuration');
            return;
        }
        $adminModel = new Admin();
        if ($adminModel->softDelete($targetId)) {
            $_SESSION['gestion_flash_success'] = 'Administrateur désactivé.';
        } else {
            $_SESSION['gestion_flash_error'] = 'Impossible de désactiver cet administrateur.';
        }
        $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord#configuration');
    }

    public function logout(): void
    {
        unset(
            $_SESSION[self::SESSION_USER_ID],
            $_SESSION[self::SESSION_USER_EMAIL],
            $_SESSION[self::SESSION_USER_NAME]
        );
        $this->redirect(GESTION_BASE_PATH . '/connexion');
    }

    public function index(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect(GESTION_BASE_PATH . '/connexion');
            return;
        }

        $postes = MockData::getPostes();
        $affichages = MockData::getAffichages();
        $candidats = MockData::getCandidats();
        $candidatsByAff = MockData::getCandidatsByAffichage();
        $emailTemplates = MockData::getEmailTemplates();
        $departments = ['Technologie', 'Gestion', 'Design', 'Stratégie', 'Marketing', 'Ressources humaines', 'Finance', 'Opérations'];
        $teamMembers = MockData::getTeamMembers();

        $user = [
            'name'  => $_SESSION[self::SESSION_USER_NAME] ?? 'Administrateur',
            'email' => $_SESSION[self::SESSION_USER_EMAIL] ?? '',
        ];

        $admins = [];
        try {
            $adminModel = new Admin();
            $admins = $adminModel->all();
        } catch (Throwable $e) {
            $admins = [];
        }
        $plans = [];
        try {
            $planModel = new Plan();
            $plans = $planModel->allWithStatus();
        } catch (Throwable $e) {
            $plans = [];
        }
        try {
            $platformUserModel = new PlatformUser();
            $stripeSaleModel = new StripeSale();
            $eventModel = new Event();

            $kpiUsers = $platformUserModel->count();
            $kpiVideos = 0;
            $kpiSalesCents = $stripeSaleModel->totalAmountCentsThisMonth();
            $platformUsers = $platformUserModel->all();
            $sales = $stripeSaleModel->all();
            $events = $eventModel->recent(20);
        } catch (Throwable $e) {
            $kpiUsers = 0;
            $kpiVideos = 0;
            $kpiSalesCents = 0;
            $platformUsers = [];
            $sales = [];
            $events = [];
        }

        $this->view('dashboard/index', [
            'pageTitle'      => 'Tableau de bord',
            'postes'         => $postes,
            'affichages'     => $affichages,
            'candidats'      => $candidats,
            'candidatsByAff' => $candidatsByAff,
            'emailTemplates' => $emailTemplates,
            'departments'    => $departments,
            'teamMembers'    => $teamMembers,
            'user'           => $user,
            'kpiUsers'       => $kpiUsers,
            'kpiVideos'      => $kpiVideos,
            'kpiSalesCents'  => $kpiSalesCents,
            'plans'          => $plans,
            'platformUsers'  => $platformUsers,
            'sales'          => $sales,
            'events'         => $events,
            'admins'           => $admins,
            'currentUserId'    => (int) ($_SESSION[self::SESSION_USER_ID] ?? 0),
            'flashSuccess'     => $_SESSION['gestion_flash_success'] ?? '',
            'flashError'       => $_SESSION['gestion_flash_error'] ?? '',
            'isDebugPage'      => false,
        ], 'app');
        unset($_SESSION['gestion_flash_success'], $_SESSION['gestion_flash_error']);
    }

    public function debug(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect(GESTION_BASE_PATH . '/connexion');
            return;
        }
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        $result = [
            'ok'            => false,
            'error'         => null,
            'tables'        => [],
            'connection'    => null,
            'encryption_key'=> null,
        ];
        try {
            $pdo = Database::get();
            $result['connection'] = 'OK';
            $tables = [
                'gestion_plans'         => 'Forfaits',
                'gestion_admins'        => 'Admins',
                'gestion_platform_users'=> 'Utilisateurs plateforme',
                'gestion_stripe_sales'  => 'Ventes Stripe',
                'gestion_events'        => 'Événements',
                'gestion_sync_logs'     => 'Logs sync',
            ];
            foreach ($tables as $table => $label) {
                $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                $exists = $stmt->rowCount() > 0;
                $count = 0;
                if ($exists) {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
                    $count = (int) $stmt->fetchColumn();
                }
                $result['tables'][$table] = [
                    'label'  => $label,
                    'exists' => $exists,
                    'count'  => $count,
                ];
            }
            $result['ok'] = true;
            $keySource = isset($_ENV['GESTION_ENCRYPTION_KEY']) ? 'GESTION_ENCRYPTION_KEY' : (isset($_ENV['APP_ENCRYPTION_KEY']) ? 'APP_ENCRYPTION_KEY' : null);
            $result['encryption_key'] = $keySource ? $keySource . ' ✓' : 'Aucune clé';
            if ($result['tables']['gestion_admins']['count'] > 0) {
                try {
                    $adminModel = new Admin();
                    $admins = $adminModel->all();
                    $decryptOk = !empty($admins) && strpos($admins[0]['email'] ?? '', 'invalide') === false;
                    $result['encryption_key'] .= $decryptOk ? ' — Déchiffrement OK' : ' — Déchiffrement échoué';
                } catch (Throwable $e) {
                    $result['encryption_key'] .= ' — Erreur : ' . $e->getMessage();
                }
            }
        } catch (Throwable $e) {
            $result['error'] = $e->getMessage();
        }
        $user = [
            'name'  => $_SESSION[self::SESSION_USER_NAME] ?? 'Administrateur',
            'email' => $_SESSION[self::SESSION_USER_EMAIL] ?? '',
        ];
        $this->view('debug', [
            'result'       => $result,
            'user'         => $user,
            'isDebugPage'  => true,
            'pageTitle'    => 'Debug',
            'postes'       => [],
            'affichages'   => [],
            'candidats'    => [],
            'candidatsByAff'=> [],
            'emailTemplates'=> [],
            'departments'  => [],
            'teamMembers'  => [],
        ], 'app');
    }

    private function isAuthenticated(): bool
    {
        return isset($_SESSION[self::SESSION_USER_ID]);
    }

    private function view(string $view, array $data = [], string $layout = 'auth'): void
    {
        extract($data);
        ob_start();
        $viewFile = GESTION_VIEWS . '/' . $view . '.php';
        if (!file_exists($viewFile)) {
            throw new RuntimeException('Vue introuvable : ' . $view);
        }
        require $viewFile;
        $content = ob_get_clean();

        $layoutFile = GESTION_BASE . '/layouts/' . $layout . '.php';
        if (!file_exists($layoutFile)) {
            throw new RuntimeException('Layout introuvable : ' . $layout);
        }
        require $layoutFile;
    }

    private function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }
}
