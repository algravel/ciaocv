<?php
/**
 * Contrôleur de base.
 * Fournit les méthodes utilitaires pour le rendu des vues,
 * les redirections et les réponses JSON.
 */
class Controller
{
    /**
     * Rendre une vue encapsulée dans un layout.
     *
     * @param string $view   Chemin de la vue (notation pointée : "auth.login")
     * @param array  $data   Variables à injecter dans la vue
     * @param string $layout Nom du layout (dans views/layouts/)
     */
    protected function view(string $view, array $data = [], string $layout = 'app'): void
    {
        // Les données deviennent des variables locales dans la vue
        extract($data);

        // 1. Rendre la vue dans un buffer
        ob_start();
        $viewFile = VIEWS_PATH . '/' . str_replace('.', '/', $view) . '.php';
        if (!file_exists($viewFile)) {
            throw new RuntimeException("Vue introuvable : {$view}");
        }
        require $viewFile;
        $content = ob_get_clean();

        // 2. Injecter $content dans le layout
        $layoutFile = VIEWS_PATH . '/layouts/' . $layout . '.php';
        if (!file_exists($layoutFile)) {
            throw new RuntimeException("Layout introuvable : {$layout}");
        }
        require $layoutFile;
    }

    /**
     * Redirection HTTP.
     */
    protected function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }

    /**
     * Réponse JSON.
     */
    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Exige une session authentifiée, sinon redirige vers /login.
     */
    protected function requireAuth(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
    }

    /**
     * Vérifie si l'utilisateur est connecté.
     */
    protected function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']);
    }
}
