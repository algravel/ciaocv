<?php
/**
 * Routeur simple pour CiaoCV
 * Mappe les URI vers les contrôleurs et leurs méthodes.
 */
class Router
{
    /** @var array<string, array<string, array{controller: string, method: string}>> */
    private array $routes = [];

    /** @var array<string, array<int, array{pattern: string, controller: string, method: string}>> */
    private array $patternRoutes = [];

    /**
     * Enregistre une route GET.
     */
    public function get(string $uri, string $controller, string $method): void
    {
        $this->routes['GET'][$uri] = compact('controller', 'method');
    }

    /**
     * Enregistre une route GET avec motif (ex: #^/rec/([a-f0-9]{16})$#).
     * Les groupes capturés sont passés en arguments à la méthode du contrôleur.
     */
    public function getPattern(string $pattern, string $controller, string $method): void
    {
        $this->patternRoutes['GET'][] = compact('pattern', 'controller', 'method');
    }

    /**
     * Enregistre une route POST.
     */
    public function post(string $uri, string $controller, string $method): void
    {
        $this->routes['POST'][$uri] = compact('controller', 'method');
    }

    /**
     * Résout l'URI courante et appelle le contrôleur approprié.
     */
    public function dispatch(): void
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $uri = $this->parseUri();

        // Vérification CSRF pour toutes les requêtes POST
        if ($requestMethod === 'POST' && !csrf_verify()) {
            http_response_code(403);
            echo '<h1>403 – Forbidden</h1><p>Token CSRF invalide ou expiré. <a href="/">Retour</a></p>';
            return;
        }

        if (isset($this->routes[$requestMethod][$uri])) {
            $route = $this->routes[$requestMethod][$uri];
            $this->callAction($route['controller'], $route['method'], []);
            return;
        }

        // Routes à motif (ex: /rec/{longId})
        $patterns = $this->patternRoutes[$requestMethod] ?? [];
        foreach ($patterns as $route) {
            if (preg_match($route['pattern'], $uri, $m)) {
                $args = array_slice($m, 1);
                $this->callAction($route['controller'], $route['method'], $args);
                return;
            }
        }

        // 404 – Page non trouvée
        http_response_code(404);
        require VIEWS_PATH . '/errors/404.php';
    }

    // ─── Privées ───────────────────────────────────────────────────────────

    /**
     * Extrait l'URI nettoyée (sans query-string ni trailing slash).
     */
    private function parseUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Supprimer la query-string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        // Retirer le slash final (sauf pour "/")
        return rtrim($uri, '/') ?: '/';
    }

    /**
     * Instancie le contrôleur et appelle la méthode (avec arguments optionnels).
     * @param array $args Arguments à passer à la méthode (ex: groupes capturés par un motif)
     */
    private function callAction(string $controller, string $method, array $args = []): void
    {
        $file = CONTROLLERS_PATH . '/' . $controller . '.php';

        if (!file_exists($file)) {
            throw new RuntimeException("Contrôleur introuvable : {$controller}");
        }
        require_once $file;

        if (!class_exists($controller)) {
            throw new RuntimeException("Classe introuvable : {$controller}");
        }

        $instance = new $controller();

        if (!method_exists($instance, $method)) {
            throw new RuntimeException("Méthode introuvable : {$controller}@{$method}");
        }

        $instance->{$method}(...$args);
    }
}
