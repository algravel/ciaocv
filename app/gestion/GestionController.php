<?php
class GestionController
{
    private const SESSION_USER_ID = 'gestion_user_id';
    private const SESSION_USER_EMAIL = 'gestion_user_email';

    public function login(): void
    {
        if ($this->isAuthenticated()) {
            $this->redirect('/gestion/tableau-de-bord');
            return;
        }
        $this->view('login', [
            'subtitle' => 'Accédez à l\'espace d\'administration CiaoCV.',
            'error' => '',
            'errorKey' => '',
            'errorHtml' => false,
        ]);
    }

    public function authenticate(): void
    {
        $email = trim($_POST['email'] ?? '');
        if ($email === '') {
            $this->view('login', [
                'subtitle' => 'Accédez à l\'espace d\'administration CiaoCV.',
                'error' => 'Veuillez entrer votre courriel.',
                'errorKey' => 'login.error.email_required',
                'errorHtml' => false,
            ]);
            return;
        }
        $_SESSION[self::SESSION_USER_ID] = 1;
        $_SESSION[self::SESSION_USER_EMAIL] = $email;
        $this->redirect('/gestion/tableau-de-bord');
    }

    public function logout(): void
    {
        unset($_SESSION[self::SESSION_USER_ID], $_SESSION[self::SESSION_USER_EMAIL]);
        $this->redirect('/gestion/connexion');
    }

    public function index(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/gestion/connexion');
            return;
        }
        $this->view('index', ['email' => $_SESSION[self::SESSION_USER_EMAIL] ?? '']);
    }

    private function isAuthenticated(): bool
    {
        return isset($_SESSION[self::SESSION_USER_ID]);
    }

    private function view(string $view, array $data = []): void
    {
        extract($data);
        ob_start();
        $viewFile = GESTION_VIEWS . '/' . $view . '.php';
        if (!file_exists($viewFile)) {
            throw new RuntimeException("Vue introuvable : " . $view);
        }
        require $viewFile;
        $content = ob_get_clean();
        $layoutFile = GESTION_BASE . '/layouts/gestion.php';
        if (!file_exists($layoutFile)) {
            throw new RuntimeException("Layout introuvable");
        }
        require $layoutFile;
    }

    private function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header("Location: {$url}");
        exit;
    }
}
