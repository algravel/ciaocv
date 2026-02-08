<?php
/**
 * Contrôleur du tableau de bord employeur.
 * Charge les données mock depuis les modèles et rend la vue.
 */
class DashboardController extends Controller
{
    /**
     * Page principale du tableau de bord.
     */
    public function index(): void
    {
        // TODO : activer l'authentification quand la DB sera en place
        // $this->requireAuth();

        $platformUserId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

        // Charger les données depuis les modèles (filtrées par entreprise si connecté)
        $postes         = Poste::getAll($platformUserId);
        $affichages     = Affichage::getAll($platformUserId);
        $candidats      = Candidat::getAll($platformUserId);
        $candidatsByAff = Candidat::getByAffichage($platformUserId);
        $emailTemplates = EmailTemplate::getAll();
        $departments = ['Technologie', 'Gestion', 'Design', 'Stratégie', 'Marketing', 'Ressources humaines', 'Finance', 'Opérations'];

        $teamMembers = User::getAll();

        // Données utilisateur connecté (mock pour l'instant)
        $user = [
            'name'  => $_SESSION['user_name']  ?? 'Utilisateur',
            'email' => $_SESSION['user_email'] ?? 'admin@olymel.com',
        ];

        $this->view('dashboard.index', [
            'pageTitle'      => 'Tableau de bord',
            'postes'         => $postes,
            'affichages'     => $affichages,
            'candidats'      => $candidats,
            'candidatsByAff' => $candidatsByAff,
            'emailTemplates' => $emailTemplates,
            'departments'    => $departments,
            'teamMembers'    => $teamMembers,
            'user'           => $user,
        ]);
    }
}
