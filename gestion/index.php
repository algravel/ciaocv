<?php
/**
 * Gestion – Front Controller
 * Application autonome d'administration. Ne dépend pas de app/.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/MockData.php';
require_once __DIR__ . '/GestionController.php';

$controller = new GestionController();

// ─── Extraire le path ─────────────────────────────────────────────────────
$uri = $_SERVER['REQUEST_URI'] ?? '/';
if (($q = strpos($uri, '?')) !== false) {
    $uri = substr($uri, 0, $q);
}
$uri = rtrim($uri, '/') ?: '/';
$path = $uri;
if (GESTION_BASE_PATH !== '' && strpos($path, GESTION_BASE_PATH) === 0) {
    $path = substr($path, strlen(GESTION_BASE_PATH)) ?: '/';
}
$path = '/' . trim($path, '/');
if ($path === '') {
    $path = '/';
}
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ─── POST /connexion (authenticate) ───────────────────────────────────────
if ($method === 'POST' && $path === '/connexion') {
    if (!csrf_verify()) {
        http_response_code(403);
        echo '403 Forbidden';
        exit;
    }
    $controller->authenticate();
    exit;
}

// ─── Routes GET ───────────────────────────────────────────────────────────
switch ($path) {
    case '/':
    case '':
    case '/connexion':
        $controller->login();
        break;
    case '/deconnexion':
        $controller->logout();
        break;
    case '/tableau-de-bord':
        $controller->index();
        break;
    default:
        http_response_code(404);
        echo '404 Not Found';
        break;
}
