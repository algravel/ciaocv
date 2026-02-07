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

        // Charger les données depuis les modèles
        $postes         = Poste::getAll();
        $affichages     = Affichage::getAll();
        $candidats      = Candidat::getAll();
        $candidatsByAff = Candidat::getByAffichage();
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
