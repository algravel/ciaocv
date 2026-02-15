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
            $this->redirect(GESTION_BASE_PATH . '/dashboard');
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
        $lang = (trim($_POST['lang'] ?? '') === 'en') ? 'en' : 'fr';
        $sent = zeptomail_send_otp($admin['email'], $admin['name'] ?? 'Administrateur', $otpCode, $lang);
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
        $this->redirect(GESTION_BASE_PATH . '/dashboard');
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

    private function logEvent(string $actionType, string $entityType, ?string $entityId, ?string $details): void
    {
        try {
            $adminId = (int) ($_SESSION[self::SESSION_USER_ID] ?? 0) ?: null;
            $eventModel = new Event();
            $eventModel->log($adminId, $actionType, $entityType, $entityId, $details);
        } catch (Throwable $e) {
            // Ne pas faire échouer l'action principale si la journalisation échoue
        }
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
            $this->logEvent('sync', 'plan', null, 'Synchronisation des forfaits avec www.ciaocv.com/tarifs');
            $_SESSION['gestion_flash_success'] = 'Forfaits synchronisés avec www.ciaocv.com/tarifs.';
        } catch (Throwable $e) {
            $_SESSION['gestion_flash_error'] = 'Impossible de synchroniser les forfaits.';
        }
        $this->redirect(GESTION_BASE_PATH . '/forfaits');
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
        $featuresRaw = trim($_POST['features'] ?? '');
        $featuresArr = array_values(array_filter(array_map('trim', explode("\n", str_replace("\r", '', $featuresRaw)))));
        $featuresJson = !empty($featuresArr) ? json_encode($featuresArr) : null;
        $isPopular = isset($_POST['is_popular']) && $_POST['is_popular'] === '1';
        if ($nameFr === '' || $nameEn === '') {
            $_SESSION['gestion_flash_error'] = 'Le nom en français et en anglais est requis.';
            $this->redirect(GESTION_BASE_PATH . '/forfaits');
            return;
        }
        if ($videoLimit < 1) {
            $_SESSION['gestion_flash_error'] = 'La limite vidéos doit être au moins 1.';
            $this->redirect(GESTION_BASE_PATH . '/forfaits');
            return;
        }
        if (!isset($_POST['_csrf_token']) || !hash_equals($_SESSION['_csrf_token'] ?? '', $_POST['_csrf_token'] ?? '')) {
            $this->redirect(GESTION_BASE_PATH . '/dashboard');
            return;
        }
        try {
            $planModel = new Plan();
            $planId = $planModel->create($nameFr, $nameEn, $videoLimit, $priceMonthly, $priceYearly, $featuresJson, $isPopular);
            $this->logEvent('create', 'plan', (string) $planId, "Forfait créé : {$nameFr} — {$videoLimit} vidéos, {$priceMonthly} \$/mois");
            $_SESSION['gestion_flash_success'] = 'Forfait créé avec succès.';
        } catch (Throwable $e) {
            $_SESSION['gestion_flash_error'] = 'Impossible de créer le forfait.';
        }
        $this->redirect(GESTION_BASE_PATH . '/forfaits');
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
        $featuresRaw = trim($_POST['features'] ?? '');
        $featuresArr = array_values(array_filter(array_map('trim', explode("\n", str_replace("\r", '', $featuresRaw)))));
        $featuresJson = !empty($featuresArr) ? json_encode($featuresArr) : null;
        $isPopular = isset($_POST['is_popular']) && $_POST['is_popular'] === '1';
        if ($id <= 0 || $nameFr === '' || $nameEn === '') {
            $_SESSION['gestion_flash_error'] = 'Données invalides.';
            $this->redirect(GESTION_BASE_PATH . '/forfaits');
            return;
        }
        if ($videoLimit < 1) {
            $_SESSION['gestion_flash_error'] = 'La limite vidéos doit être au moins 1.';
            $this->redirect(GESTION_BASE_PATH . '/forfaits');
            return;
        }
        try {
            $planModel = new Plan();
            if ($planModel->update($id, $nameFr, $nameEn, $videoLimit, $priceMonthly, $priceYearly, $active, $featuresJson, $isPopular)) {
                $this->logEvent('update', 'plan', (string) $id, "Forfait modifié : {$nameFr} — {$videoLimit} vidéos, {$priceMonthly} \$/mois" . ($active ? '' : ', désactivé'));
                $_SESSION['gestion_flash_success'] = 'Forfait mis à jour.';
            } else {
                $_SESSION['gestion_flash_error'] = 'Impossible de mettre à jour le forfait.';
            }
        } catch (Throwable $e) {
            $_SESSION['gestion_flash_error'] = 'Impossible de mettre à jour le forfait.';
        }
        $this->redirect(GESTION_BASE_PATH . '/forfaits');
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
            $this->redirect(GESTION_BASE_PATH . '/configuration');
            return;
        }
        if (!isset($_POST['_csrf_token']) || !hash_equals($_SESSION['_csrf_token'] ?? '', $_POST['_csrf_token'] ?? '')) {
            $this->redirect(GESTION_BASE_PATH . '/configuration');
            return;
        }
        $role = in_array($role, ['admin', 'viewer'], true) ? $role : 'admin';
        $adminModel = new Admin();
        if ($adminModel->findByEmail($email)) {
            $_SESSION['gestion_flash_error'] = 'Un administrateur avec ce courriel existe déjà.';
            $this->redirect(GESTION_BASE_PATH . '/configuration');
            return;
        }
        $newPassword = bin2hex(random_bytes(8));
        try {
            $adminId = $adminModel->create($email, $newPassword, $name, $role);
            $this->logEvent('create', 'admin', (string) $adminId, "Administrateur créé : {$name} ({$email}), rôle {$role}");
        } catch (Throwable $e) {
            $_SESSION['gestion_flash_error'] = 'Impossible de créer le compte.';
            $this->redirect(GESTION_BASE_PATH . '/configuration');
            return;
        }
        $sent = zeptomail_send_new_admin_credentials($email, $name, $newPassword);
        if ($sent) {
            $_SESSION['gestion_flash_success'] = 'Administrateur créé. Les identifiants ont été envoyés par courriel à ' . $email . '.';
        } else {
            $_SESSION['gestion_flash_error'] = 'Compte créé mais l\'envoi du courriel a échoué. Contactez l\'administrateur pour lui transmettre ses identifiants.';
        }
        $this->redirect(GESTION_BASE_PATH . '/configuration');
    }

    public function createPlatformUser(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect(GESTION_BASE_PATH . '/connexion');
            return;
        }
        $prenom = trim($_POST['prenom'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? 'client');
        $planId = isset($_POST['plan_id']) && $_POST['plan_id'] !== '' ? (int) $_POST['plan_id'] : null;
        $billable = isset($_POST['billable']) && $_POST['billable'] === '1';
        $active = !isset($_POST['active']) || $_POST['active'] === '1';

        if ($nom === '' || $email === '') {
            $_SESSION['gestion_flash_error'] = 'Nom et courriel requis.';
            $this->redirect(GESTION_BASE_PATH . '/utilisateurs');
            return;
        }
        if (!isset($_POST['_csrf_token']) || !hash_equals($_SESSION['_csrf_token'] ?? '', $_POST['_csrf_token'] ?? '')) {
            $_SESSION['gestion_flash_error'] = 'Session expirée. Veuillez réessayer.';
            $this->redirect(GESTION_BASE_PATH . '/utilisateurs', 303);
            return;
        }
        $role = in_array($role, ['client', 'evaluateur'], true) ? $role : 'client';
        $newPassword = bin2hex(random_bytes(8));
        $fullName = trim($prenom . ' ' . $nom) ?: $nom;

        try {
            $platformUserModel = new PlatformUser();
            $userId = $platformUserModel->create([
                'prenom' => $prenom,
                'nom' => $nom,
                'email' => $email,
                'role' => $role,
                'plan_id' => $planId > 0 ? $planId : null,
                'billable' => $billable,
                'active' => $active,
                'password' => $newPassword,
            ]);
            // Les évaluateurs n'ont pas d'entreprise propre (ils sont invités par un employeur)
            if ($role !== 'evaluateur') {
                $entrepriseModel = new Entreprise();
                $entrepriseModel->createWithDefaults($userId);
            }
            file_put_contents(dirname(__DIR__) . '/.cursor/debug.log', json_encode(['timestamp' => round(microtime(true) * 1000), 'location' => 'GestionController.php:createPlatformUser', 'message' => 'create success, before zeptomail', 'data' => ['userId' => $userId], 'hypothesisId' => 'H4']) . "\n", FILE_APPEND | LOCK_EX);
            $this->logEvent('create', 'platform_user', (string) $userId, "Utilisateur ajouté : {$fullName} ({$email}), rôle {$role}");
            $sent = zeptomail_send_new_platform_user_credentials($email, $fullName, $newPassword);
            file_put_contents(dirname(__DIR__) . '/.cursor/debug.log', json_encode(['timestamp' => round(microtime(true) * 1000), 'location' => 'GestionController.php:createPlatformUser', 'message' => 'after zeptomail', 'data' => ['sent' => $sent], 'hypothesisId' => 'H5']) . "\n", FILE_APPEND | LOCK_EX);
            if ($sent) {
                $_SESSION['gestion_flash_success'] = 'Utilisateur créé. Les identifiants ont été envoyés par courriel à ' . $email . '.';
            } else {
                $_SESSION['gestion_flash_error'] = 'Compte créé mais l\'envoi du courriel a échoué. Contactez l\'utilisateur pour lui transmettre ses identifiants.';
            }
        } catch (Throwable $e) {
            $_SESSION['gestion_flash_error'] = 'Impossible de créer l\'utilisateur.';
            error_log('PlatformUser::create failed: ' . $e->getMessage());
        }
        $this->redirect(GESTION_BASE_PATH . '/utilisateurs', 303);
    }

    public function updatePlatformUser(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect(GESTION_BASE_PATH . '/connexion');
            return;
        }
        $id = (int) ($_POST['id'] ?? 0);
        $prenom = trim($_POST['prenom'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? 'client');
        $planId = isset($_POST['plan_id']) && $_POST['plan_id'] !== '' ? (int) $_POST['plan_id'] : null;
        $billable = isset($_POST['billable']) && $_POST['billable'] === '1';
        $active = !isset($_POST['active']) || $_POST['active'] === '1';

        if ($id <= 0 || $nom === '' || $email === '') {
            $_SESSION['gestion_flash_error'] = 'Données invalides.';
            $this->redirect(GESTION_BASE_PATH . '/utilisateurs');
            return;
        }
        if (!isset($_POST['_csrf_token']) || !hash_equals($_SESSION['_csrf_token'] ?? '', $_POST['_csrf_token'] ?? '')) {
            $_SESSION['gestion_flash_error'] = 'Session expirée. Veuillez réessayer.';
            $this->redirect(GESTION_BASE_PATH . '/utilisateurs', 303);
            return;
        }
        $role = in_array($role, ['client', 'evaluateur'], true) ? $role : 'client';

        try {
            $platformUserModel = new PlatformUser();
            if ($platformUserModel->update($id, [
                'prenom' => $prenom,
                'nom' => $nom,
                'email' => $email,
                'role' => $role,
                'plan_id' => $planId > 0 ? $planId : null,
                'billable' => $billable,
                'active' => $active,
            ])) {
                $fullName = trim($prenom . ' ' . $nom) ?: $nom;
                $this->logEvent('update', 'platform_user', (string) $id, "Utilisateur modifié : {$fullName} ({$email}), rôle {$role}");
                $_SESSION['gestion_flash_success'] = 'Utilisateur mis à jour.';
            } else {
                $_SESSION['gestion_flash_error'] = 'Impossible de mettre à jour l\'utilisateur.';
            }
        } catch (Throwable $e) {
            $_SESSION['gestion_flash_error'] = 'Impossible de mettre à jour l\'utilisateur.';
            error_log('PlatformUser::update failed: ' . $e->getMessage());
        }
        $this->redirect(GESTION_BASE_PATH . '/utilisateurs', 303);
    }

    public function deletePlatformUser(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect(GESTION_BASE_PATH . '/connexion');
            return;
        }
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['gestion_flash_error'] = 'Identifiant invalide.';
            $this->redirect(GESTION_BASE_PATH . '/utilisateurs');
            return;
        }
        if (!isset($_POST['_csrf_token']) || !hash_equals($_SESSION['_csrf_token'] ?? '', $_POST['_csrf_token'] ?? '')) {
            $_SESSION['gestion_flash_error'] = 'Session expirée. Veuillez réessayer.';
            $this->redirect(GESTION_BASE_PATH . '/utilisateurs', 303);
            return;
        }
        try {
            $platformUserModel = new PlatformUser();
            $user = $platformUserModel->findById($id);
            if ($platformUserModel->delete($id)) {
                $displayName = $user ? (($user['name'] ?? $user['nom'] ?? '') . ' (' . ($user['email'] ?? '') . ')') : (string) $id;
                $this->logEvent('delete', 'platform_user', (string) $id, "Utilisateur supprimé : {$displayName}");
                $_SESSION['gestion_flash_success'] = 'Utilisateur supprimé.';
            } else {
                $_SESSION['gestion_flash_error'] = 'Impossible de supprimer l\'utilisateur.';
            }
        } catch (Throwable $e) {
            $_SESSION['gestion_flash_error'] = 'Impossible de supprimer l\'utilisateur.';
            error_log('PlatformUser::delete failed: ' . $e->getMessage());
        }
        $this->redirect(GESTION_BASE_PATH . '/utilisateurs', 303);
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
            $this->redirect(GESTION_BASE_PATH . '/configuration');
            return;
        }
        if (!isset($_POST['_csrf_token']) || !hash_equals($_SESSION['_csrf_token'] ?? '', $_POST['_csrf_token'] ?? '')) {
            $this->redirect(GESTION_BASE_PATH . '/configuration');
            return;
        }
        $role = in_array($role, ['admin', 'viewer'], true) ? $role : 'admin';
        $adminModel = new Admin();
        if ($adminModel->update($id, $name, $email, $role)) {
            $this->logEvent('update', 'admin', (string) $id, "Administrateur modifié : {$name} ({$email}), rôle {$role}");
            $_SESSION['gestion_flash_success'] = 'Administrateur mis à jour.';
        } else {
            $_SESSION['gestion_flash_error'] = 'Impossible de mettre à jour (courriel peut-être déjà utilisé).';
        }
        $this->redirect(GESTION_BASE_PATH . '/configuration');
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
            $this->redirect(GESTION_BASE_PATH . '/configuration');
            return;
        }
        if ($newPassword !== $newPasswordConfirm) {
            $_SESSION['gestion_flash_error'] = 'Les mots de passe ne correspondent pas.';
            $this->redirect(GESTION_BASE_PATH . '/configuration');
            return;
        }
        if (strlen($newPassword) < 8) {
            $_SESSION['gestion_flash_error'] = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
            $this->redirect(GESTION_BASE_PATH . '/dashboard');
            return;
        }
        if (!isset($_POST['_csrf_token']) || !hash_equals($_SESSION['_csrf_token'] ?? '', $_POST['_csrf_token'] ?? '')) {
            $this->redirect(GESTION_BASE_PATH . '/dashboard');
            return;
        }
        $adminModel = new Admin();
        $admin = $adminModel->findById((int) ($_SESSION[self::SESSION_USER_ID] ?? 0));
        if (!$admin || !$adminModel->verifyPassword($currentPassword, $admin['password_hash'])) {
            $_SESSION['gestion_flash_error'] = 'Mot de passe actuel incorrect.';
            $this->redirect(GESTION_BASE_PATH . '/dashboard');
            return;
        }
        if ($adminModel->resetPassword($admin['id'], $newPassword)) {
            $_SESSION['gestion_flash_success'] = 'Votre mot de passe a été mis à jour.';
        } else {
            $_SESSION['gestion_flash_error'] = 'Impossible de mettre à jour le mot de passe.';
        }
        $this->redirect(GESTION_BASE_PATH . '/dashboard');
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
            $this->redirect(GESTION_BASE_PATH . '/configuration');
            return;
        }
        if (!isset($_POST['_csrf_token']) || !hash_equals($_SESSION['_csrf_token'] ?? '', $_POST['_csrf_token'] ?? '')) {
            $this->redirect(GESTION_BASE_PATH . '/configuration');
            return;
        }
        $adminModel = new Admin();
        $admin = $adminModel->findById($id);
        if (!$admin) {
            $_SESSION['gestion_flash_error'] = 'Administrateur introuvable.';
            $this->redirect(GESTION_BASE_PATH . '/configuration');
            return;
        }
        $newPassword = bin2hex(random_bytes(8));
        if (!$adminModel->resetPassword($id, $newPassword)) {
            $_SESSION['gestion_flash_error'] = 'Impossible de réinitialiser le mot de passe.';
            $this->redirect(GESTION_BASE_PATH . '/configuration');
            return;
        }
        $this->logEvent('update', 'admin', (string) $id, "Mot de passe réinitialisé pour {$admin['name']} ({$admin['email']})");
        $sent = zeptomail_send_password_reset($admin['email'], $admin['name'], $newPassword);
        if ($sent) {
            $_SESSION['gestion_flash_success'] = 'Un nouveau mot de passe a été envoyé par courriel à ' . $admin['email'] . '.';
        } else {
            $_SESSION['gestion_flash_error'] = 'Mot de passe mis à jour mais l\'envoi du courriel a échoué. Contactez l\'administrateur.';
        }
        $this->redirect(GESTION_BASE_PATH . '/configuration');
    }

    public function resetPlatformUserPassword(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect(GESTION_BASE_PATH . '/connexion');
            return;
        }
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['gestion_flash_error'] = 'Identifiant invalide.';
            $this->redirect(GESTION_BASE_PATH . '/utilisateurs');
            return;
        }
        if (!isset($_POST['_csrf_token']) || !hash_equals($_SESSION['_csrf_token'] ?? '', $_POST['_csrf_token'] ?? '')) {
            $this->redirect(GESTION_BASE_PATH . '/utilisateurs');
            return;
        }
        $platformUserModel = new PlatformUser();
        $user = $platformUserModel->findById($id);
        if (!$user) {
            $_SESSION['gestion_flash_error'] = 'Utilisateur introuvable.';
            $this->redirect(GESTION_BASE_PATH . '/utilisateurs');
            return;
        }
        $newPassword = bin2hex(random_bytes(8));
        if (!$platformUserModel->resetPassword($id, $newPassword)) {
            $_SESSION['gestion_flash_error'] = 'Impossible de réinitialiser le mot de passe.';
            $this->redirect(GESTION_BASE_PATH . '/utilisateurs');
            return;
        }
        $fullName = trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? $user['name'] ?? '')) ?: ($user['name'] ?? $user['email']);
        $this->logEvent('update', 'platform_user', (string) $id, "Mot de passe réinitialisé pour {$fullName} ({$user['email']})");
        $sent = zeptomail_send_platform_user_password_reset($user['email'], $fullName, $newPassword);
        if ($sent) {
            $_SESSION['gestion_flash_success'] = 'Un nouveau mot de passe a été envoyé par courriel à ' . $user['email'] . '.';
        } else {
            $_SESSION['gestion_flash_error'] = 'Mot de passe mis à jour mais l\'envoi du courriel a échoué. Contactez l\'utilisateur.';
        }
        $this->redirect(GESTION_BASE_PATH . '/utilisateurs', 303);
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
            $this->redirect(GESTION_BASE_PATH . '/configuration');
            return;
        }
        if ($targetId === $currentId) {
            $_SESSION['gestion_flash_error'] = 'Vous ne pouvez pas vous supprimer vous-même.';
            $this->redirect(GESTION_BASE_PATH . '/configuration');
            return;
        }
        if (!isset($_POST['_csrf_token']) || !hash_equals($_SESSION['_csrf_token'] ?? '', $_POST['_csrf_token'] ?? '')) {
            $this->redirect(GESTION_BASE_PATH . '/configuration');
            return;
        }
        $adminModel = new Admin();
        $targetAdmin = $adminModel->findById($targetId);
        if ($adminModel->softDelete($targetId)) {
            $targetName = $targetAdmin ? ($targetAdmin['name'] ?? '') . ' (' . ($targetAdmin['email'] ?? '') . ')' : (string) $targetId;
            $this->logEvent('delete', 'admin', (string) $targetId, "Administrateur désactivé : {$targetName}");
            $_SESSION['gestion_flash_success'] = 'Administrateur désactivé.';
        } else {
            $_SESSION['gestion_flash_error'] = 'Impossible de désactiver cet administrateur.';
        }
        $this->redirect(GESTION_BASE_PATH . '/configuration');
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
        $kpiUsers = 0;
        $kpiVideos = 0;
        $kpiSalesCents = 0;
        $platformUsers = [];
        $sales = [];
        $events = [];

        try {
            $platformUserModel = new PlatformUser();
            $kpiUsers = $platformUserModel->count();
            $platformUsers = $platformUserModel->all();
        } catch (Throwable $e) {
            // Garder 0 et [] par défaut
        }

        try {
            $stripeSaleModel = new StripeSale();
            $kpiSalesCents = $stripeSaleModel->totalAmountCentsThisMonth();
            $sales = $stripeSaleModel->all();
        } catch (Throwable $e) {
            // Garder 0 et [] par défaut
        }

        try {
            $eventModel = new Event();
            $events = $eventModel->recent(20);
        } catch (Throwable $e) {
            // Garder [] par défaut
        }

        $feedback = [];
        try {
            $feedback = Feedback::all();
        } catch (Throwable $e) {
            // Garder [] par défaut
        }

        $devTasks = [];
        try {
            $devTasks = DevTask::all();
        } catch (Throwable $e) {
            // Garder [] par défaut
        }

        $this->view('dashboard/index', [
            'pageTitle'      => 'Tableau de bord',
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
            'feedback'         => $feedback,
            'devTasks'         => $devTasks,
            'isDebugPage'      => false,
        ], 'app');
        unset($_SESSION['gestion_flash_success'], $_SESSION['gestion_flash_error']);
    }

    public function submitFeedback(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $type = trim($_POST['feedback_type'] ?? '');
        $message = trim($_POST['message'] ?? '');
        if (!in_array($type, ['problem', 'idea'], true)) {
            $type = 'problem';
        }
        if ($message === '') {
            echo json_encode(['ok' => false, 'error' => 'Message requis']);
            return;
        }
        $data = ['type' => $type, 'message' => $message, 'source' => 'gestion'];
        if ($type === 'problem' && !empty(trim($_POST['page_url'] ?? ''))) {
            $data['page_url'] = trim($_POST['page_url']);
        }
        if (!empty($_SESSION[self::SESSION_USER_EMAIL])) {
            $data['user_email'] = $_SESSION[self::SESSION_USER_EMAIL];
        }
        if (!empty($_SESSION[self::SESSION_USER_NAME])) {
            $data['user_name'] = $_SESSION[self::SESSION_USER_NAME];
        }
        if (Feedback::create($data)) {
            echo json_encode(['ok' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Erreur lors de l\'enregistrement']);
        }
    }

    public function updateFeedback(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PATCH') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Méthode non autorisée']);
            return;
        }
        if (!csrf_verify()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'CSRF invalide']);
            return;
        }
        $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['ok' => false, 'error' => 'ID invalide']);
            return;
        }
        $data = [];
        if (isset($_POST['status'])) {
            $data['status'] = $_POST['status'];
        }
        if (array_key_exists('internal_note', $_POST)) {
            $data['internal_note'] = $_POST['internal_note'];
        }
        if (empty($data)) {
            echo json_encode(['ok' => false, 'error' => 'Aucune donnée à mettre à jour']);
            return;
        }
        if (Feedback::update($id, $data)) {
            echo json_encode(['ok' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Erreur lors de la mise à jour']);
        }
    }

    public function deleteFeedback(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Méthode non autorisée']);
            return;
        }
        $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['ok' => false, 'error' => 'ID invalide']);
            return;
        }
        if (Feedback::delete($id)) {
            echo json_encode(['ok' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Erreur lors de la suppression']);
        }
    }

    public function createDevTask(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'error' => 'Méthode non autorisée']);
            return;
        }
        if (!$this->isAuthenticated()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Non authentifié']);
            return;
        }
        if (!csrf_verify()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'CSRF invalide']);
            return;
        }
        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            echo json_encode(['ok' => false, 'error' => 'Titre requis']);
            return;
        }
        $data = ['title' => $title, 'description' => trim($_POST['description'] ?? '') ?: null, 'priority' => (int) ($_POST['priority'] ?? 0), 'status' => $_POST['status'] ?? DevTask::STATUS_TODO];
        $id = DevTask::create($data);
        if ($id !== null) {
            $task = ['id' => $id, 'title' => $title, 'description' => $data['description'], 'priority' => $data['priority'], 'status' => $data['status'], 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')];
            echo json_encode(['ok' => true, 'task' => $task]);
        } else {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Erreur lors de la création']);
        }
    }

    public function updateDevTask(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PATCH') {
            echo json_encode(['ok' => false, 'error' => 'Méthode non autorisée']);
            return;
        }
        if (!$this->isAuthenticated()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Non authentifié']);
            return;
        }
        if (!csrf_verify()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'CSRF invalide']);
            return;
        }
        $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['ok' => false, 'error' => 'ID invalide']);
            return;
        }
        $data = [];
        if (array_key_exists('title', $_POST)) {
            $data['title'] = trim($_POST['title'] ?? '') ?: 'Sans titre';
        }
        if (array_key_exists('description', $_POST)) {
            $data['description'] = trim($_POST['description'] ?? '') ?: null;
        }
        if (array_key_exists('priority', $_POST)) {
            $data['priority'] = (int) $_POST['priority'];
        }
        if (array_key_exists('status', $_POST)) {
            $data['status'] = $_POST['status'];
        }
        if (empty($data)) {
            echo json_encode(['ok' => false, 'error' => 'Aucune donnée à mettre à jour']);
            return;
        }
        if (DevTask::update($id, $data)) {
            echo json_encode(['ok' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Erreur lors de la mise à jour']);
        }
    }

    public function deleteDevTask(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'error' => 'Méthode non autorisée']);
            return;
        }
        if (!$this->isAuthenticated()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Non authentifié']);
            return;
        }
        if (!csrf_verify()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'CSRF invalide']);
            return;
        }
        $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['ok' => false, 'error' => 'ID invalide']);
            return;
        }
        if (DevTask::delete($id)) {
            echo json_encode(['ok' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Erreur lors de la suppression']);
        }
    }

    public function migrate(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect(GESTION_BASE_PATH . '/connexion');
            return;
        }
        $user = [
            'name'  => $_SESSION[self::SESSION_USER_NAME] ?? 'Admin',
            'email' => $_SESSION[self::SESSION_USER_EMAIL] ?? '',
        ];
        $this->view('migrate', ['output' => '', 'user' => $user], 'app');
    }

    public function runMigrations(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Méthode non autorisée']);
            return;
        }
        if (!$this->isAuthenticated()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Non authentifié']);
            return;
        }
        if (!csrf_verify()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'CSRF invalide']);
            return;
        }
        ob_start();
        try {
            require GESTION_BASE . '/migrate.php';
            $output = ob_get_clean();
            echo json_encode(['ok' => true, 'output' => $output]);
        } catch (Throwable $e) {
            ob_end_clean();
            echo json_encode(['ok' => false, 'error' => $e->getMessage(), 'output' => '']);
        }
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
                'gestion_feedback'      => 'Bugs et idées',
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
        if ($status === 303) {
            header('Cache-Control: no-store, no-cache, must-revalidate');
        }
        header('Location: ' . $url);
        exit;
    }
}
