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
        $postes         = Poste::getAll($platformUserId);
        $affichages     = Affichage::getAll($platformUserId);
        $candidats      = Candidat::getAll($platformUserId);
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
            'name'  => $_SESSION['user_name']  ?? 'Utilisateur',
            'email' => $_SESSION['user_email'] ?? 'admin@olymel.com',
        ];
        $companyName = $_SESSION['company_name'] ?? company_name_from_email($user['email']) ?: 'Mon entreprise';

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
        $hasPoste = count($postes) > 0;
        $hasAffichage = count($affichages) > 0;
        // Nouvelle org = pas de postes ni affichages → 3 tâches à faire
        $isNewOrg = $forceNewOrg || (!$hasPoste && !$hasAffichage);
        $kpiTachesRestantes = $isNewOrg ? 3 : max(0, 3 - ($hasPoste ? 1 : 0) - ($hasAffichage ? 1 : 0) - 1);

        if (empty($chartMonths)) {
            $chartMonths = [['label' => 'Sep', 'count' => 60], ['label' => 'Oct', 'count' => 100], ['label' => 'Nov', 'count' => 80], ['label' => 'Déc', 'count' => 140], ['label' => 'Jan', 'count' => 180], ['label' => 'Fév', 'count' => 120]];
        }

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
            'pageTitle'           => 'Tableau de bord',
            'companyName'         => $companyName,
            'postes'              => $postes,
            'affichages'          => $affichages,
            'candidats'           => $candidats,
            'candidatsByAff'      => $candidatsByAff,
            'emailTemplates'      => $emailTemplates,
            'departments'         => $departments,
            'teamMembers'         => $teamMembers,
            'user'                => $user,
            'kpiForfaitUsed'      => $kpiForfaitUsed,
            'kpiForfaitLimit'     => $kpiForfaitLimit,
            'planName'            => $planName,
            'kpiAffichagesActifs' => $kpiAffichagesActifs,
            'kpiAffichagesActifsPrev' => $kpiAffichagesActifsPrev,
            'kpiTachesRestantes'  => $kpiTachesRestantes,
            'isNewOrg'            => $isNewOrg,
            'events'              => $events,
            'chartMonths'         => $chartMonths,
            'defaultSection'      => $defaultSection,
        ]);
    }

    /**
     * Enregistrer les infos entreprise (nom, etc.) – session pour l'instant.
     */
    public function saveCompany(): void
    {
        $this->requireAuth();
        $companyName = trim($_POST['company_name'] ?? '');
        $_SESSION['company_name'] = $companyName !== '' ? $companyName : (company_name_from_email($_SESSION['user_email'] ?? '') ?: 'Mon entreprise');
        $this->json(['success' => true, 'company_name' => $_SESSION['company_name']]);
    }
}
