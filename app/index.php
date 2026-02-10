<?php
/**
 * CiaoCV – Front Controller
 *
 * Point d'entrée unique de l'application.
 * Charge la configuration, les dépendances, définit les routes
 * et dispatche vers le contrôleur approprié.
 */

// ─── Configuration ─────────────────────────────────────────────────────
require_once __DIR__ . '/config/app.php';

// ─── Core MVC ──────────────────────────────────────────────────────────
require_once __DIR__ . '/core/Router.php';
require_once __DIR__ . '/core/Controller.php';

// ─── Modèles ───────────────────────────────────────────────────────────
require_once __DIR__ . '/models/Poste.php';
require_once __DIR__ . '/models/Affichage.php';
require_once __DIR__ . '/models/Candidat.php';
require_once __DIR__ . '/models/EmailTemplate.php';
require_once __DIR__ . '/models/User.php';

// ─── Fonctions partagées ───────────────────────────────────────────────
require_once __DIR__ . '/includes/functions.php';

// ─── Routes ────────────────────────────────────────────────────────────
$router = new Router();

// Authentification (slugs SEO-friendly)
$router->get('/', 'AuthController', 'login');
$router->get('/connexion', 'AuthController', 'login');
$router->post('/connexion', 'AuthController', 'authenticate');
$router->post('/connexion/otp', 'AuthController', 'verifyOtp');
$router->get('/deconnexion', 'AuthController', 'logout');
$router->get('/logout', 'AuthController', 'logout'); // alias, redirige vers /connexion après déco

// Dashboard employeur (sections accessibles par URL)
$router->get('/tableau-de-bord', 'DashboardController', 'index');
$router->get('/postes', 'DashboardController', 'index');
$router->get('/affichages', 'DashboardController', 'index');
$router->get('/candidats', 'DashboardController', 'index');
$router->get('/parametres', 'DashboardController', 'index');
$router->post('/parametres/entreprise', 'DashboardController', 'saveCompany');
$router->post('/postes', 'DashboardController', 'createPoste');
$router->post('/postes/update', 'DashboardController', 'updatePoste');
$router->post('/postes/delete', 'DashboardController', 'deletePoste');
$router->post('/affichages', 'DashboardController', 'createAffichage');
$router->post('/affichages/delete', 'DashboardController', 'deleteAffichage');

// Feedback (FAB bugs et idées)
$router->post('/feedback', 'FeedbackController', 'submit');

// Page candidat – entrevue de présélection (slug: /entrevue/{longId})
$router->getPattern('#^/entrevue/([a-f0-9]{16})$#', 'RecController', 'show');

// Purge LSCache (après déploiement) – protégé par PURGE_CACHE_SECRET
$router->get('/purge-cache', 'PurgeController', 'index');

// Redirections 301 : anciennes URLs → slugs
$router->get('/login', 'RedirectController', 'toConnexion');
$router->post('/login', 'RedirectController', 'toConnexion');
$router->get('/dashboard', 'RedirectController', 'toTableauDeBord');
$router->getPattern('#^/rec/([a-f0-9]{16})$#', 'RedirectController', 'toEntrevue');

// ─── Dispatch ──────────────────────────────────────────────────────────
try {
    $router->dispatch();
} catch (Throwable $e) {
    error_log('CiaoCV dispatch: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    $isApi = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && in_array(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), ['/postes', '/postes/update', '/postes/delete', '/parametres/entreprise', '/feedback', '/affichages', '/affichages/delete']);
    if ($isApi) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Erreur serveur: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    } else {
        throw $e;
    }
}
