<?php
/**
 * Contrôleur principal de l'espace gestion (administration).
 */
class GestionController
{
    private const SESSION_USER_ID = 'gestion_user_id';
    private const SESSION_USER_EMAIL = 'gestion_user_email';
    private const SESSION_USER_NAME = 'gestion_user_name';

    public function login(): void
    {
        if ($this->isAuthenticated()) {
            $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord');
            return;
        }
        $this->view('login', [
            'subtitle'  => "Accédez à l'espace d'administration CiaoCV.",
            'error'     => '',
            'errorKey'  => '',
            'errorHtml' => false,
        ], 'auth');
    }

    public function authenticate(): void
    {
        $email = trim($_POST['email'] ?? '');
        if ($email === '') {
            $this->view('login', [
                'subtitle'  => "Accédez à l'espace d'administration CiaoCV.",
                'error'     => 'Veuillez entrer votre courriel.',
                'errorKey'  => 'login.error.email_required',
                'errorHtml' => false,
            ], 'auth');
            return;
        }
        $_SESSION[self::SESSION_USER_ID] = 1;
        $_SESSION[self::SESSION_USER_EMAIL] = $email;
        $_SESSION[self::SESSION_USER_NAME] = 'Administrateur';
        $this->redirect(GESTION_BASE_PATH . '/tableau-de-bord');
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
