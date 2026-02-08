<?php
/**
 * Contrôleur d'authentification.
 * Gère la connexion, la déconnexion et le mot de passe oublié.
 */
class AuthController extends Controller
{
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
        $loginType   = $_GET['type'] ?? '';
        $subtitleKey = 'login.hero.subtitle';

        if ($loginType === 'candidat') {
            $subtitleKey = 'login.hero.subtitle.candidat';
        } elseif ($loginType === 'entreprise') {
            $subtitleKey = 'login.hero.subtitle.entreprise';
        }

        $this->view('auth.login', [
            'subtitleKey' => $subtitleKey,
            'error'       => '',
            'errorKey'    => '',
            'errorHtml'   => false,
        ], 'auth');
    }

    /**
     * Traiter le formulaire de connexion.
     */
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
                $loginType = $_GET['type'] ?? '';
                $subtitleKey = $loginType === 'candidat' ? 'login.hero.subtitle.candidat' : ($loginType === 'entreprise' ? 'login.hero.subtitle.entreprise' : 'login.hero.subtitle');
                $this->view('auth.login', [
                    'subtitleKey' => $subtitleKey,
                    'error'       => 'Vérification de sécurité échouée. Veuillez réessayer.',
                    'errorKey'    => 'login.error.turnstile',
                    'errorHtml'   => false,
                ], 'auth');
                return;
            }
        }

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // TODO : Validation réelle avec base de données
        // Pour l'instant on accepte tout et on crée une session mock
        if ($email === '') {
            $this->view('auth.login', [
                'subtitleKey' => 'login.hero.subtitle',
                'error'       => 'Veuillez entrer votre courriel.',
                'errorKey'    => 'login.error.email_required',
                'errorHtml'   => false,
            ], 'auth');
            return;
        }

        // Créer la session (mock)
        $_SESSION['user_id']       = 1;
        $_SESSION['user_email']    = $email;
        $_SESSION['user_name']     = 'Utilisateur';
        $_SESSION['company_name']  = company_name_from_email($email) ?: 'Mon entreprise';

        $this->redirect('/tableau-de-bord');
    }

    /**
     * Déconnecter l'utilisateur.
     */
    public function logout(): void
    {
        session_destroy();
        $this->redirect('/connexion');
    }
}
