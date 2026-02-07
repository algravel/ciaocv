<?php
/**
 * Espace Administration (/gestion).
 * Session distincte de l'app recruteur (gestion_*).
 */
class GestionController extends Controller
{
    private const SESSION_USER_ID = 'gestion_user_id';
    private const SESSION_USER_EMAIL = 'gestion_user_email';

    /**
     * Page de connexion administration.
     */
    public function login(): void
    {
        if ($this->isGestionAuthenticated()) {
            $this->redirect('/gestion/tableau-de-bord');
            return;
        }

        $this->view('gestion.login', [
            'subtitle'  => 'Accédez à l\'espace d\'administration CiaoCV.',
            'error'     => '',
            'errorKey'  => '',
            'errorHtml' => false,
        ], 'gestion');
    }

    /**
     * Traitement du formulaire de connexion.
     */
    public function authenticate(): void
    {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // TODO : validation réelle (admin en base ou config)
        if ($email === '') {
            $this->view('gestion.login', [
                'subtitle'  => 'Accédez à l\'espace d\'administration CiaoCV.',
                'error'     => 'Veuillez entrer votre courriel.',
                'errorKey'  => 'login.error.email_required',
                'errorHtml' => false,
            ], 'gestion');
            return;
        }

        $_SESSION[self::SESSION_USER_ID]    = 1;
        $_SESSION[self::SESSION_USER_EMAIL] = $email;

        $this->redirect('/gestion/tableau-de-bord');
    }

    /**
     * Déconnexion administration.
     */
    public function logout(): void
    {
        unset($_SESSION[self::SESSION_USER_ID], $_SESSION[self::SESSION_USER_EMAIL]);
        $this->redirect('/gestion/connexion');
    }

    /**
     * Tableau de bord administration (placeholder).
     */
    public function index(): void
    {
        $this->requireGestionAuth();
        $this->view('gestion.index', [
            'pageTitle' => 'Tableau de bord',
            'email'     => $_SESSION[self::SESSION_USER_EMAIL] ?? '',
        ], 'gestion');
    }

    protected function isGestionAuthenticated(): bool
    {
        return isset($_SESSION[self::SESSION_USER_ID]);
    }

    protected function requireGestionAuth(): void
    {
        if (!$this->isGestionAuthenticated()) {
            $this->redirect('/gestion/connexion');
        }
    }
}
