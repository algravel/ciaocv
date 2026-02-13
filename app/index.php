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
require_once __DIR__ . '/helpers/R2Signer.php';

// ─── Routes ────────────────────────────────────────────────────────────
$router = new Router();

// Authentification (slugs SEO-friendly)
$router->get('/',              'AuthController', 'login');
$router->get('/connexion',     'AuthController', 'login');
$router->post('/connexion',    'AuthController', 'authenticate');
$router->post('/connexion/mot-de-passe-oublie', 'AuthController', 'forgotPassword');
$router->get('/deconnexion',   'AuthController', 'logout');
$router->get('/logout',       'AuthController', 'logout'); // alias, redirige vers /connexion après déco

// Dashboard employeur (sections accessibles par URL directe)
$router->get('/tableau-de-bord', 'DashboardController', 'index');
$router->get('/postes', 'DashboardController', 'index');
$router->get('/affichages', 'DashboardController', 'index');
$router->get('/candidats', 'DashboardController', 'index');
$router->get('/parametres', 'DashboardController', 'index');
$router->get('/statistiques', 'DashboardController', 'index');
$router->get('/historique', 'DashboardController', 'history');

// API Dashboard (POST)
$router->post('/parametres/entreprise', 'DashboardController', 'saveCompany');
$router->get('/parametres/email-templates', 'DashboardController', 'getEmailTemplates');
$router->post('/parametres/email-templates', 'DashboardController', 'saveEmailTemplate');
$router->post('/parametres/email-templates/delete', 'DashboardController', 'deleteEmailTemplate');
$router->post('/postes', 'DashboardController', 'createPoste');
$router->post('/postes/update', 'DashboardController', 'updatePoste');
$router->post('/postes/delete', 'DashboardController', 'deletePoste');
$router->post('/affichages', 'DashboardController', 'createAffichage');
$router->post('/affichages/delete', 'DashboardController', 'deleteAffichage');
$router->post('/affichages/evaluateur/remove', 'DashboardController', 'removeEvaluateur');
$router->post('/affichages/evaluateur/add', 'DashboardController', 'addEvaluateur');
$router->post('/candidats/update', 'DashboardController', 'updateCandidate');
$router->post('/candidats/comment', 'DashboardController', 'addComment');
$router->post('/candidats/notify', 'DashboardController', 'notifyCandidats');

// Feedback (FAB bugs et idées)
$router->post('/feedback', 'FeedbackController', 'submit');

// Page candidat – entrevue de présélection (slug: /entrevue/{longId})
$router->getPattern('#^/entrevue/([a-f0-9]{16})$#', 'RecController', 'show');
$router->post('/entrevue/upload-url', 'EntrevueController', 'getUploadUrl');
$router->post('/entrevue/submit', 'EntrevueController', 'submit');

// Purge LSCache (après déploiement) – protégé par PURGE_CACHE_SECRET
$router->get('/purge-cache', 'PurgeController', 'index');

// Redirections 301 : anciennes URLs → slugs
$router->get('/index.php',  'RedirectController', 'toConnexion');
$router->get('/login',      'RedirectController', 'toConnexion');
$router->get('/tableau-de-bord500', 'RedirectController', 'toTableauDeBord');
$router->post('/login',     'RedirectController', 'toConnexion');
$router->get('/dashboard',  'RedirectController', 'toTableauDeBord');
$router->getPattern('#^/rec/([a-f0-9]{16})$#', 'RedirectController', 'toEntrevue');

// ─── Dispatch ──────────────────────────────────────────────────────────
$router->dispatch();
