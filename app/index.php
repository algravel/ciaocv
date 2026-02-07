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

// Authentification
$router->get('/',           'AuthController', 'login');
$router->get('/login',      'AuthController', 'login');
$router->post('/login',     'AuthController', 'authenticate');
$router->get('/logout',     'AuthController', 'logout');

// Dashboard employeur
$router->get('/dashboard',  'DashboardController', 'index');

// Page candidat – entrevue de présélection (app.ciaocv.com/rec/{longid})
$router->getPattern('#^/rec/([a-f0-9]{16})$#', 'RecController', 'show');

// ─── Dispatch ──────────────────────────────────────────────────────────
$router->dispatch();
