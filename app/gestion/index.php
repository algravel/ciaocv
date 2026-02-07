<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/GestionController.php';

$controller = new GestionController();
$uri = $_SERVER['REQUEST_URI'] ?? '/';
if (($q = strpos($uri, '?')) !== false) {
    $uri = substr($uri, 0, $q);
}
$uri = rtrim($uri, '/') ?: '/';
$path = preg_replace('#^/gestion#', '', $uri) ?: '/';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST' && $path === '/connexion') {
    if (!csrf_verify()) {
        http_response_code(403);
        echo '403 Forbidden';
        exit;
    }
    $controller->authenticate();
    exit;
}

switch ($path) {
    case '':
    case '/':
        $controller->login();
        break;
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
