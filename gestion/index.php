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

// ─── POST /feedback ───────────────────────────────────────────────────────
if ($method === 'POST' && $path === '/feedback') {
    if (!csrf_verify()) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'CSRF invalide']);
        exit;
    }
    $controller->submitFeedback();
    exit;
}

// ─── POST /feedback/update ─────────────────────────────────────────────────
if (($method === 'POST' || $method === 'PATCH') && $path === '/feedback/update') {
    $controller->updateFeedback();
    exit;
}

// ─── POST /migrate/run ─────────────────────────────────────────────────────
if ($method === 'POST' && $path === '/migrate/run') {
    if (!csrf_verify()) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'CSRF invalide']);
        exit;
    }
    $controller->runMigrations();
    exit;
}

// ─── POST /feedback/delete ─────────────────────────────────────────────────
if ($method === 'POST' && $path === '/feedback/delete') {
    if (!csrf_verify()) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'CSRF invalide']);
        exit;
    }
    $controller->deleteFeedback();
    exit;
}

// ─── Développement / Backlog Kanban ────────────────────────────────────────
if (($method === 'POST' || $method === 'PATCH') && $path === '/developpement/tasks/update') {
    $controller->updateDevTask();
    exit;
}
if ($method === 'POST' && $path === '/developpement/tasks/delete') {
    $controller->deleteDevTask();
    exit;
}
if ($method === 'POST' && $path === '/developpement/tasks') {
    $controller->createDevTask();
    exit;
}

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

// ─── POST /forfaits/synchroniser ─────────────────────────────────────────────
if ($method === 'POST' && $path === '/forfaits/synchroniser') {
    if (!csrf_verify()) {
        http_response_code(403);
        echo '403 Forbidden';
        exit;
    }
    $controller->syncPlans();
    exit;
}

// ─── POST /forfaits/ajouter ──────────────────────────────────────────────────
if ($method === 'POST' && $path === '/forfaits/ajouter') {
    if (!csrf_verify()) {
        http_response_code(403);
        echo '403 Forbidden';
        exit;
    }
    $controller->createPlan();
    exit;
}

// ─── POST /forfaits/modifier ─────────────────────────────────────────────────
if ($method === 'POST' && $path === '/forfaits/modifier') {
    if (!csrf_verify()) {
        http_response_code(403);
        echo '403 Forbidden';
        exit;
    }
    $controller->updatePlan();
    exit;
}

// ─── POST /admin/ajouter ────────────────────────────────────────────────────
if ($method === 'POST' && $path === '/admin/ajouter') {
    if (!csrf_verify()) {
        http_response_code(403);
        echo '403 Forbidden';
        exit;
    }
    $controller->createAdmin();
    exit;
}

// ─── POST /admin/modifier ───────────────────────────────────────────────────
if ($method === 'POST' && $path === '/admin/modifier') {
    if (!csrf_verify()) {
        http_response_code(403);
        echo '403 Forbidden';
        exit;
    }
    $controller->updateAdmin();
    exit;
}

// ─── POST /changer-mot-de-passe ─────────────────────────────────────────────
if ($method === 'POST' && $path === '/changer-mot-de-passe') {
    if (!csrf_verify()) {
        http_response_code(403);
        echo '403 Forbidden';
        exit;
    }
    $controller->changeOwnPassword();
    exit;
}

// ─── POST /admin/reinitialiser-mot-de-passe ─────────────────────────────────
if ($method === 'POST' && $path === '/admin/reinitialiser-mot-de-passe') {
    if (!csrf_verify()) {
        http_response_code(403);
        echo '403 Forbidden';
        exit;
    }
    $controller->resetAdminPassword();
    exit;
}

// ─── POST /utilisateurs/ajouter ─────────────────────────────────────────────
if ($method === 'POST' && $path === '/utilisateurs/ajouter') {
    if (!csrf_verify()) {
        http_response_code(403);
        echo '403 Forbidden';
        exit;
    }
    $controller->createPlatformUser();
    exit;
}

// ─── POST /utilisateurs/modifier ───────────────────────────────────────────
if ($method === 'POST' && $path === '/utilisateurs/modifier') {
    if (!csrf_verify()) {
        http_response_code(403);
        echo '403 Forbidden';
        exit;
    }
    $controller->updatePlatformUser();
    exit;
}

// ─── POST /utilisateurs/reinitialiser-mot-de-passe ───────────────────────────
if ($method === 'POST' && $path === '/utilisateurs/reinitialiser-mot-de-passe') {
    if (!csrf_verify()) {
        http_response_code(403);
        echo '403 Forbidden';
        exit;
    }
    $controller->resetPlatformUserPassword();
    exit;
}

// ─── POST /utilisateurs/supprimer ───────────────────────────────────────────
if ($method === 'POST' && $path === '/utilisateurs/supprimer') {
    if (!csrf_verify()) {
        http_response_code(403);
        echo '403 Forbidden';
        exit;
    }
    $controller->deletePlatformUser();
    exit;
}

// ─── POST /admin/supprimer ─────────────────────────────────────────────────
if ($method === 'POST' && $path === '/admin/supprimer') {
    if (!csrf_verify()) {
        http_response_code(403);
        echo '403 Forbidden';
        exit;
    }
    $controller->deleteAdmin();
    exit;
}

// ─── POST /verifier-otp ───────────────────────────────────────────────────
if ($method === 'POST' && $path === '/verifier-otp') {
    if (!csrf_verify()) {
        http_response_code(403);
        echo '403 Forbidden';
        exit;
    }
    $controller->verifyOtp();
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
        header('Location: ' . (GESTION_BASE_PATH ?: '') . '/dashboard', true, 301);
        exit;
    case '/configuration':
        header('Location: ' . (GESTION_BASE_PATH ?: '') . '/utilisateurs', true, 301);
        exit;
    case '/dashboard':
    case '/sales':
    case '/forfaits':
    case '/utilisateurs':
    case '/synchronisation':
    case '/bugs-idees':
    case '/developpement':
        $controller->index();
        break;
    case '/debug':
        $controller->debug();
        break;
    case '/migrate':
        $controller->migrate();
        break;
    default:
        http_response_code(404);
        echo '404 Not Found';
        break;
}
