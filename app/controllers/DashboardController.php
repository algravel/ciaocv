<?php
/**
 * Contrôleur du tableau de bord employeur.
 * Charge les données depuis les modèles et la base de données (gestion).
 */
class DashboardController extends Controller
{
    /**
     * Page principale du tableau de bord.
     */
    public function index(): void
    {
        $this->requireAuth();
        $platformUserId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

        // Charger les données depuis les modèles (filtrées par entreprise si connecté)
        $postes = Poste::getAll($platformUserId);
        $affichages = Affichage::getAll($platformUserId);
        $candidats = Candidat::getAll($platformUserId);
        $candidatsByAff = Candidat::getByAffichage($platformUserId);
        $emailTemplates = EmailTemplate::getAll();

        // ?demo=new_org simule une nouvelle organisation (tests)
        $forceNewOrg = ($_GET['demo'] ?? '') === 'new_org';
        if ($forceNewOrg) {
            $postes = [];
            $affichages = [];
            $candidats = [];
            $candidatsByAff = [];
        }
        $departments = ['Technologie', 'Gestion', 'Design', 'Stratégie', 'Marketing', 'Ressources humaines', 'Finance', 'Opérations'];

        $teamMembers = User::getAll();

        $user = [
            'name' => $_SESSION['user_name'] ?? 'Utilisateur',
            'email' => $_SESSION['user_email'] ?? '',
        ];

        $entreprise = null;
        if ($platformUserId) {
            require_once dirname(__DIR__, 2) . '/gestion/config.php';
            try {
                $entrepriseModel = new Entreprise();
                $entreprise = $entrepriseModel->getByPlatformUserId($platformUserId);
            } catch (Throwable $e) {
                // Ignorer si table non encore créée
            }
        }
        $companyName = ($entreprise['name'] ?? null) ?: ($_SESSION['company_name'] ?? '');

        $kpiForfaitUsed = 0;
        $kpiForfaitLimit = 50;
        $planName = 'Découverte';
        $events = [];
        $chartMonths = [];
        $kpiAffichagesActifs = 0;
        $kpiAffichagesActifsPrev = 0;
        $kpiTachesRestantes = 2;

        if ($platformUserId) {
            require_once dirname(__DIR__, 2) . '/gestion/config.php';
            try {
                $platformUserModel = new PlatformUser();
                $platformUser = $platformUserModel->findById($platformUserId);
                if ($platformUser && $platformUser['plan_id']) {
                    $planModel = new Plan();
                    $plan = $planModel->findById($platformUser['plan_id']);
                    if ($plan) {
                        $kpiForfaitLimit = $plan['video_limit'];
                        $planName = $plan['name'];
                    }
                }
                $entrevueModel = new Entrevue();
                $kpiForfaitUsed = $entrevueModel->countUsed($platformUserId);
                $chartMonths = $entrevueModel->countByMonth($platformUserId, 6);
                $eventModel = new Event();
                $events = $eventModel->recentByPlatformUser($platformUserId, 20);
            } catch (Throwable $e) {
                $chartMonths = [];
            }
        }

        foreach ($affichages as $a) {
            if (($a['status'] ?? '') === 'Actif') {
                $kpiAffichagesActifs++;
            }
        }
        // Calcul des tâches restantes
        $hasCompanyName = !empty($companyName) && $companyName !== 'Mon entreprise';
        $hasPoste = count($postes) > 0;
        $hasAffichage = count($affichages) > 0;

        // Nouvelle org logic restored (used for placeholders in view)
        $isNewOrg = $forceNewOrg || (!$hasPoste && !$hasAffichage);

        $tasksCompleted = 0;
        if ($hasCompanyName)
            $tasksCompleted++;
        if ($hasPoste)
            $tasksCompleted++;
        if ($hasAffichage)
            $tasksCompleted++;

        $kpiTachesRestantes = $forceNewOrg ? 3 : max(0, 3 - $tasksCompleted);

        // chartMonths reste vide si aucune entrevue réelle — la vue affiche un état vide

        // Section active selon l'URL (/postes, /affichages, etc.)
        $path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
        $path = rtrim($path, '/') ?: '/';
        $defaultSection = match ($path) {
            '/postes' => 'postes',
            '/affichages' => 'affichages',
            '/candidats' => 'candidats',
            '/parametres' => 'parametres',
            default => 'statistiques',
        };

        $this->view('dashboard.index', [
            'pageTitle' => 'Tableau de bord',
            'companyName' => $companyName,
            'entreprise' => $entreprise,
            'postes' => $postes,
            'affichages' => $affichages,
            'candidats' => $candidats,
            'candidatsByAff' => $candidatsByAff,
            'emailTemplates' => $emailTemplates,
            'departments' => $departments,
            'teamMembers' => $teamMembers,
            'user' => $user,
            'kpiForfaitUsed' => $kpiForfaitUsed,
            'kpiForfaitLimit' => $kpiForfaitLimit,
            'planName' => $planName,
            'kpiAffichagesActifs' => $kpiAffichagesActifs,
            'kpiAffichagesActifsPrev' => $kpiAffichagesActifsPrev,
            'kpiTachesRestantes' => $kpiTachesRestantes,
            'isNewOrg' => $isNewOrg,
            'events' => $events,
            'chartMonths' => $chartMonths,
            'defaultSection' => $defaultSection,
            'hasCompanyName' => $hasCompanyName,
            'hasPoste' => $hasPoste,
            'hasAffichage' => $hasAffichage,
        ]);
    }

    /**
     * Enregistrer les infos entreprise en base (app_entreprises).
     */
    public function saveCompany(): void
    {
        $this->requireAuth();
        $platformUserId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
        if (!$platformUserId) {
            $this->json(['success' => false, 'error' => 'Non connecté'], 401);
            return;
        }
        require_once dirname(__DIR__, 2) . '/gestion/config.php';
        $data = [
            'name' => trim($_POST['company_name'] ?? ''),
            'industry' => trim($_POST['industry'] ?? '') ?: null,
            'email' => trim($_POST['email'] ?? '') ?: null,
            'phone' => trim($_POST['phone'] ?? '') ?: null,
            'address' => trim($_POST['address'] ?? '') ?: null,
            'description' => trim($_POST['description'] ?? '') ?: null,
        ];
        $entrepriseModel = new Entreprise();
        $ok = $entrepriseModel->upsert($platformUserId, $data);
        $_SESSION['company_name'] = $data['name'];
        $this->json(['success' => $ok, 'company_name' => $data['name']]);
    }

    /**
     * Créer un poste en base (app_postes).
     */
    public function createPoste(): void
    {
        try {
            $this->requireAuth();
            $platformUserId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
            if (!$platformUserId) {
                $this->json(['success' => false, 'error' => 'Non connecté'], 401);
                return;
            }
            $data = [
                'title' => trim($_POST['title'] ?? ''),
                'department' => trim($_POST['department'] ?? ''),
                'location' => trim($_POST['location'] ?? ''),
                'status' => $_POST['status'] ?? 'active',
                'description' => trim($_POST['description'] ?? '') ?: null,
                'record_duration' => (int) ($_POST['record_duration'] ?? 3) ?: 3,
            ];
            $poste = Poste::create($platformUserId, $data);
            if ($poste) {
                // Journaliser la création
                try {
                    require_once dirname(__DIR__, 2) . '/gestion/config.php';
                    $event = new Event();
                    $event->logForPlatformUser(
                        $platformUserId,
                        'create',
                        'poste',
                        (string) ($poste['id'] ?? ''),
                        'Poste créé: ' . ($data['title'] ?: '(sans titre)'),
                        $_SESSION['user_name'] ?? null
                    );
                } catch (Throwable $logErr) {
                    error_log('Event log error: ' . $logErr->getMessage());
                }
                $this->json(['success' => true, 'poste' => $poste]);
            } else {
                $this->json(['success' => false, 'error' => 'Titre requis'], 400);
            }
        } catch (Throwable $e) {
            error_log('createPoste: ' . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Erreur serveur: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mettre à jour un poste (questions, statut, durée d'enregistrement).
     */
    public function updatePoste(): void
    {
        try {
            $this->requireAuth();
            $platformUserId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
            if (!$platformUserId) {
                $this->json(['success' => false, 'error' => 'Non connecté'], 401);
                return;
            }
            $input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: $_POST;
            $id = isset($input['id']) ? (int) $input['id'] : 0;
            if ($id <= 0) {
                $this->json(['success' => false, 'error' => 'ID invalide'], 400);
                return;
            }
            $data = [];
            if (isset($input['questions'])) {
                $q = $input['questions'];
                $data['questions'] = is_array($q) ? $q : (json_decode($q ?? '[]', true) ?: []);
            }
            if (isset($input['status'])) {
                $data['status'] = in_array($input['status'], ['active', 'paused', 'closed'], true) ? $input['status'] : 'active';
            }
            if (isset($input['record_duration'])) {
                $data['record_duration'] = (int) $input['record_duration'] ?: 3;
            }
            if (empty($data)) {
                $this->json(['success' => false, 'error' => 'Aucune donnée à mettre à jour'], 400);
                return;
            }
            $ok = Poste::update($id, $platformUserId, $data);
            if ($ok) {
                // Journaliser la modification
                try {
                    require_once dirname(__DIR__, 2) . '/gestion/config.php';
                    $event = new Event();
                    $changedFields = implode(', ', array_keys($data));
                    $event->logForPlatformUser(
                        $platformUserId,
                        'update',
                        'poste',
                        (string) $id,
                        'Poste modifié (champs: ' . $changedFields . ')',
                        $_SESSION['user_name'] ?? null
                    );
                } catch (Throwable $logErr) {
                    error_log('Event log error: ' . $logErr->getMessage());
                }
            }
            $this->json(['success' => $ok]);
        } catch (Throwable $e) {
            error_log('updatePoste: ' . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Supprimer un poste en base (app_postes).
     */
    public function deletePoste(): void
    {
        $this->requireAuth();
        $platformUserId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
        if (!$platformUserId) {
            $this->json(['success' => false, 'error' => 'Non connecté'], 401);
            return;
        }
        $input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: $_POST;
        $id = isset($input['id']) ? (int) $input['id'] : 0;
        if ($id <= 0) {
            $this->json(['success' => false, 'error' => 'ID invalide'], 400);
            return;
        }
        $ok = Poste::delete($id, $platformUserId);
        if ($ok) {
            // Journaliser la suppression
            try {
                require_once dirname(__DIR__, 2) . '/gestion/config.php';
                $event = new Event();
                $event->logForPlatformUser(
                    $platformUserId,
                    'delete',
                    'poste',
                    (string) $id,
                    'Poste supprimé #' . $id,
                    $_SESSION['user_name'] ?? null
                );
            } catch (Throwable $logErr) {
                error_log('Event log error: ' . $logErr->getMessage());
            }
        }
        $this->json(['success' => $ok]);
    }

    /**
     * Créer un affichage en base (app_affichages).
     */
    public function createAffichage(): void
    {
        try {
            $this->requireAuth();
            $platformUserId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
            if (!$platformUserId) {
                $this->json(['success' => false, 'error' => 'Non connecté'], 401);
                return;
            }
            if (!csrf_verify()) {
                $this->json(['success' => false, 'error' => 'Token CSRF invalide'], 403);
                return;
            }
            $posteId = (int) ($_POST['poste_id'] ?? 0);
            if ($posteId <= 0) {
                $this->json(['success' => false, 'error' => 'Poste requis'], 400);
                return;
            }
            $affichage = Affichage::create($platformUserId, ['poste_id' => $posteId]);
            if ($affichage) {
                // Journaliser la création de l'affichage
                try {
                    require_once dirname(__DIR__, 2) . '/gestion/config.php';
                    $event = new Event();
                    $event->logForPlatformUser(
                        $platformUserId,
                        'create',
                        'affichage',
                        (string) ($affichage['id'] ?? ''),
                        'Affichage créé pour le poste #' . $posteId,
                        $_SESSION['user_name'] ?? null
                    );
                } catch (Throwable $logErr) {
                    error_log('Event log error: ' . $logErr->getMessage());
                }
                $this->json(['success' => true, 'affichage' => $affichage]);
            } else {
                $this->json(['success' => false, 'error' => 'Impossible de créer l\'affichage'], 400);
            }
        } catch (Throwable $e) {
            error_log('createAffichage: ' . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Erreur serveur: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Supprimer un affichage.
     */
    public function deleteAffichage(): void
    {
        try {
            $this->requireAuth();
            $platformUserId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
            if (!$platformUserId) {
                $this->json(['success' => false, 'error' => 'Non connecté'], 401);
                return;
            }
            // ID envoyé soit en POST raw, soit en FormData
            $id = (string) ($_POST['id'] ?? '');
            if (!$id) {
                $this->json(['success' => false, 'error' => 'ID manquant'], 400);
                return;
            }

            $success = Affichage::delete($id, $platformUserId);

            if ($success) {
                // Journaliser la suppression
                try {
                    require_once dirname(__DIR__, 2) . '/gestion/config.php';
                    $event = new Event();
                    $event->logForPlatformUser(
                        $platformUserId,
                        'delete',
                        'affichage',
                        $id,
                        'Affichage supprimé #' . $id,
                        $_SESSION['user_name'] ?? null
                    );
                } catch (Throwable $logErr) {
                    // ignore
                }
            }

            $this->json(['success' => $success]);
        } catch (Throwable $e) {
            error_log('deleteAffichage error: ' . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Erreur serveur'], 500);
        }
    }
}
