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
                $events = $eventModel->recentByPlatformUser($platformUserId, 100);
            } catch (Throwable $e) {
                // $chartMonths remains []
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

        // Forfaits facturation (depuis gestion_plans en base) — même source que gestion/tarifs
        $billingPlans = [];
        if ($platformUserId) {
            try {
                require_once dirname(__DIR__, 2) . '/gestion/config.php';
                $pdo = Database::get();
                $stmt = $pdo->query('SELECT id, name_fr, name_en, video_limit, price_monthly, price_yearly FROM gestion_plans WHERE COALESCE(active, 1) = 1 ORDER BY price_monthly ASC');
                $dbPlans = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
                $lang = ($_COOKIE['language'] ?? 'fr') === 'en' ? 'en' : 'fr';
                foreach ($dbPlans as $p) {
                    $nameFr = $p['name_fr'] ?? '';
                    $nameEn = $p['name_en'] ?? $nameFr;
                    $name = $lang === 'en' ? $nameEn : $nameFr;
                    $priceMonthly = (float) ($p['price_monthly'] ?? 0);
                    $priceYearly = (float) ($p['price_yearly'] ?? 0);
                    $videoLimit = (int) ($p['video_limit'] ?? 0);

                    $price = $priceMonthly == 0 && $priceYearly == 0 ? '0' : number_format($priceMonthly, 0, ',', ' ');
                    $sub = $priceMonthly == 0 && $priceYearly == 0 ? 'Gratuit' : ($priceMonthly == $priceYearly && $priceMonthly > 0 ? 'paiement unique' : 'Facturé annuellement');
                    $priceSuffix = ($priceMonthly > 0 && $priceMonthly != $priceYearly) ? '/mois' : '';

                    $features = self::billingFeaturesForPlan($name, $videoLimit, $lang);
                    $cta = $priceMonthly == 0 ? 'Actuel' : ('Passer à ' . $name);
                    $popular = (stripos($name, 'Pro') !== false);

                    $billingPlans[] = [
                        'id' => $p['id'] ?? null,
                        'name' => $name,
                        'price' => $price . ' $',
                        'sub' => $sub,
                        'priceSuffix' => $priceSuffix,
                        'features' => $features,
                        'cta' => $cta,
                        'popular' => $popular,
                    ];
                }
            } catch (Throwable $e) {
                error_log('DashboardController: billing plans load error: ' . $e->getMessage());
            }
        }

        $this->view('dashboard.index', [
            'pageTitle' => 'Tableau de bord',
            'companyName' => $companyName,
            'userTimezone' => ($entreprise['timezone'] ?? null) ?: 'America/Montreal',
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
            'billingPlans' => $billingPlans,
        ]);
    }

    /**
     * Features affichées pour un forfait dans la section facturation.
     */
    private static function billingFeaturesForPlan(string $name, int $videoLimit, string $lang): array
    {
        $nameLower = strtolower($name);
        if (strpos($nameLower, 'découverte') !== false || strpos($nameLower, 'discovery') !== false) {
            return ['1 affichage actif', '5 entrevues vidéo', 'Questions standards'];
        }
        if (strpos($nameLower, 'à la carte') !== false || strpos($nameLower, 'pay per use') !== false) {
            return ['1 affichage', 'Accès 30 jours', 'Entrevues vidéo illimitées', 'Outils collaboratifs', 'Marque employeur', 'Questions personnalisées'];
        }
        if (strpos($nameLower, 'pro') !== false) {
            return [
                'Affichages illimités',
                ['i18n' => 'billing.plan.interviews_50', 'text' => "Gérez jusqu'à 50 entrevues à la fois (libérez des places en supprimant les anciennes)"],
                'Outils collaboratifs',
                'Marque employeur',
                'Questions personnalisées',
            ];
        }
        if (strpos($nameLower, 'expert') !== false) {
            return [
                'Affichages illimités',
                ['i18n' => 'billing.plan.interviews_200', 'text' => "Gérez jusqu'à 200 entrevues à la fois (libérez des places en supprimant les anciennes)"],
                'Outils collaboratifs',
                'Marque employeur',
                'Questions personnalisées',
                'Support prioritaire',
            ];
        }
        $limit = $videoLimit >= 9999 ? 'illimitées' : $videoLimit;
        return ['Entrevues vidéo : ' . $limit, 'Outils collaboratifs', 'Marque employeur', 'Questions personnalisées'];
    }

    /**
     * Page d'historique complet des événements.
     */
    public function history(): void
    {
        $this->requireAuth();
        $platformUserId = (int) $_SESSION['user_id'];

        $user = [
            'name' => $_SESSION['user_name'] ?? 'Utilisateur',
            'email' => $_SESSION['user_email'] ?? '',
        ];
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        try {
            require_once dirname(__DIR__, 2) . '/gestion/config.php';
            $eventModel = new Event();
            $events = $eventModel->recentByPlatformUser($platformUserId, 100);
        } catch (Throwable $e) {
            error_log('DashboardController::history error: ' . $e->getMessage());
            $events = [];
        }

        $this->view('dashboard.history', [
            'pageTitle' => 'Historique',
            'events' => $events,
            'user' => $user
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
            'timezone' => trim($_POST['timezone'] ?? '') ?: 'America/Montreal',
        ];
        $entrepriseModel = new Entreprise();
        $ok = $entrepriseModel->upsert($platformUserId, $data);
        $_SESSION['company_name'] = $data['name'];
        $timezone = $data['timezone'] ?? 'America/Montreal';
        $this->json(['success' => $ok, 'company_name' => $data['name'], 'timezone' => $timezone]);
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


    /**
     * Mettre à jour le statut ou le favori d'un candidat.
     * POST /candidats/update
     */
    public function updateCandidate(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->json(['success' => false, 'error' => 'Non authentifié'], 401);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'Méthode invalide'], 405);
            return;
        }

        try {
            // Verify CSRF
            // Verify CSRF
            if (!csrf_verify()) {
                $sent = $_POST['_csrf_token'] ?? 'null';
                $sess = $_SESSION['_csrf_token'] ?? 'null';
                $this->json(['success' => false, 'error' => "CSRF Invalid. Sent: $sent, Session: $sess"], 403);
                return;
            }
            $platformUserId = (int) $_SESSION['user_id'];
            $id = $_POST['id'] ?? '';
            $status = $_POST['status'] ?? null;
            $isFavorite = isset($_POST['is_favorite']) ? (bool) $_POST['is_favorite'] : null;
            $rating = isset($_POST['rating']) ? (int) $_POST['rating'] : null;

            if (!$id) {
                $this->json(['success' => false, 'error' => 'ID manquant'], 400);
                return;
            }

            $updateData = [];
            if ($status !== null)
                $updateData['status'] = $status;
            if ($isFavorite !== null)
                $updateData['is_favorite'] = $isFavorite;
            if ($rating !== null && $rating >= 0 && $rating <= 5)
                $updateData['rating'] = $rating;

            if (empty($updateData)) {
                $this->json(['success' => true, 'message' => 'Aucune modification']);
                return;
            }

            $success = Candidat::update($id, $platformUserId, $updateData);

            if ($success) {
                try {
                    require_once dirname(__DIR__, 2) . '/gestion/config.php';
                    $event = new Event();
                    $updateLabel = isset($updateData['status']) ? 'Statut: ' . $updateData['status'] : 'Favori modifié';
                    $event->logForPlatformUser(
                        $platformUserId,
                        'update',
                        'candidat',
                        $id,
                        'Candidat par ' . ($_SESSION['user_name'] ?? 'Utilisateur') . ' - ' . $updateLabel,
                        $_SESSION['user_name'] ?? null
                    );
                } catch (Throwable $logErr) {
                    // ignore
                }
            }

            $this->json(['success' => $success]);
        } catch (Throwable $e) {
            error_log('updateCandidate error: ' . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Erreur serveur'], 500);
        }
    }
}
