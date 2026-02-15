/**
 * CiaoCV – Système i18n unifié
 * Gère les traductions FR/EN pour toutes les pages (login + dashboard).
 * Utilise localStorage pour persister le choix de l'utilisateur.
 */
const translations = {
    fr: {
        // ─── Page de connexion ───
        "nav.service": "Notre service",
        "nav.guide": "Guide candidat",
        "nav.recruiter": "Espace Recruteur",
        "nav.candidate": "Espace Candidat",
        "nav.login": "Se connecter",
        "nav.logout": "Se déconnecter",
        "login.hero.title": "Content de vous <br><span class=\"highlight\">revoir !</span>",
        "login.hero.subtitle": "Accédez à votre espace pour gérer vos entrevues vidéo de présélection et vos candidatures en toute simplicité.",
        "login.hero.subtitle.candidat": "Accédez à votre espace candidat pour gérer vos entrevues vidéo et vos candidatures en toute simplicité.",
        "login.hero.subtitle.entreprise": "Accédez à votre espace recruteur pour gérer vos affichages de postes et évaluer vos candidats en toute simplicité.",
        "login.modal.title": "Vous êtes ?",
        "login.modal.candidate": "Un Candidat",
        "login.modal.recruiter": "Un Recruteur",
        "login.admin.badge": "Administration",
        "login.admin.hero": "Connexion <span class=\"highlight\">administration</span>",
        "login.title": "Connexion",
        "login.recruiter_link": "Espace recruteur",
        "login.back_site": "Retour au site",
        "login.email.label": "Courriel",
        "login.email.placeholder": "votre@courriel.com",
        "login.password.label": "Mot de passe",
        "login.password.placeholder": "••••••••",
        "login.submit": "Se connecter",
        "login.forgot_password": "Mot de passe oublié ?",
        "login.create_account": "Créer un compte",
        "login.create_demo": "Créer votre compte démo",
        "login.signup_prompt": "Pas encore de compte ?",
        "login.signup_link": "S'inscrire gratuitement",
        "login.oauth.divider": "ou",
        "login.oauth.google": "Continuer avec Google",
        "login.oauth.microsoft": "Continuer avec Microsoft",
        "login.error.email_required": "Veuillez entrer votre courriel.",
        "login.error.password_required": "Veuillez entrer votre mot de passe.",
        "login.error.turnstile": "Vérification de sécurité échouée. Veuillez réessayer.",
        "login.error.generic": "Une erreur est survenue. Veuillez réessayer.",
        "login.error.invalid": "Courriel ou mot de passe incorrect.",
        "login.error.otp_send_failed": "Impossible d'envoyer le code par courriel. Veuillez réessayer.",
        "login.error.otp_invalid": "Code invalide ou expiré. Veuillez réessayer.",
        "login.error.otp_expired": "Le code a expiré. Veuillez vous reconnecter.",
        "login.otp.title": "Vérification en deux étapes",
        "login.otp.desc": "Un code à 6 chiffres a été envoyé à",
        "login.otp.label": "Code de vérification",
        "login.otp.placeholder": "000000",
        "login.otp.submit": "Vérifier",
        "forgot.title": "Mot de passe oublié ?",
        "forgot.desc": "Entrez votre adresse courriel et nous vous enverrons un lien pour réinitialiser votre mot de passe.",
        "forgot.email.label": "Courriel",
        "forgot.email.placeholder": "votre@courriel.com",
        "forgot.turnstile": "Vérification de sécurité Cloudflare",
        "forgot.submit": "Envoyer le lien",

        // ─── Footer ───
        "footer.service": "Notre service",
        "footer.guide": "Guide candidat",
        "footer.legal": "Légal",
        "footer.privacy": "Politique de confidentialité",
        "footer.terms": "Conditions d'utilisation",
        "footer.contact": "Contact",
        "footer.proudly": "❤️<br>Fièrement humain",

        // ─── Dashboard – Navigation ───
        nav_dashboard: "Tableau de bord",
        nav_ventes: "Ventes",
        nav_forfaits: "Forfaits",
        nav_utilisateurs: "Utilisateurs",
        utilisateurs_tab_users: "Utilisateurs plateforme",
        nav_synchronisation: "Synchronisation",
        nav_configuration: "Configuration",
        nav_bugs_idees: "Bugs et idées",
        nav_developpement: "Développement",
        page_developpement_desc: "Outils et informations pour les développeurs.",
        dev_task_add: "Ajouter une tâche",
        dev_task_edit: "Modifier la tâche",
        dev_task_title: "Titre",
        dev_task_description: "Description",
        dev_task_priority: "Priorité (0 = plus prioritaire, 99 = moins prioritaire)",
        dev_col_todo: "À faire",
        dev_col_in_progress: "En cours",
        dev_col_to_test: "À tester",
        dev_col_deployed: "Déployé",
        dev_col_done: "Terminé",
        nav_database: "Base de données",
        nav_migrate: "Migration SQL",
        migrate_btn_run: "Exécuter les migrations",
        config_admins_title: "Administrateurs",
        config_admins_desc: "Comptes ayant accès à l'interface d'administration.",
        config_no_admins: "Aucun administrateur.",
        th_email: "Email",
        role_admin: "Admin",
        role_viewer: "Lecteur",
        nav_postes: "Postes",
        nav_affichages: "Affichages",
        nav_candidats: "Candidats",
        nav_parametres: "Paramètres",
        dropdown_logout: "Se déconnecter",
        dropdown_change_password: "Changer mot de passe",
        change_password_title: "Changer mon mot de passe",
        change_password_current: "Mot de passe actuel",
        change_password_new: "Nouveau mot de passe",
        change_password_confirm: "Confirmer le nouveau mot de passe",
        change_password_mismatch: "Les mots de passe ne correspondent pas.",
        change_password_submit: "Enregistrer",
        search: "Rechercher...",

        // ─── Dashboard – Filtres ───
        filter_all: "Tous",
        filter_active: "Actifs",
        filter_paused: "Pausés",
        filter_closed: "Fermés",
        filter_expired: "Expirés",
        filter_new: "Nouveaux",
        filter_reviewed: "Évalués",
        filter_shortlisted: "Favoris",
        filter_all_jobs: "Tous les postes",

        // ─── Dashboard – Titres ───
        postes_title: "Postes actifs",
        affichages_title: "Affichages en cours",
        candidats_title: "Candidats",
        statistiques_title: "Tableau de bord",
        parametres_title: "Paramètres",

        // ─── Dashboard – Actions ───
        add_poste: "Nouveau poste",
        add_affichage: "Nouvel affichage",
        action_edit: "Modifier",
        action_delete: "Désactiver",
        action_view: "Voir",
        delete_poste_title: "Supprimer le poste",
        config_confirm_delete: "Désactiver cet administrateur ?",
        config_delete_modal_title: "Confirmer la désactivation",
        config_delete_modal_message: "Êtes-vous sûr de vouloir désactiver l'administrateur « {name} » ?",
        config_delete_confirm_btn: "Désactiver",
        config_add_admin_btn: "Ajouter un administrateur",
        config_add_admin_title: "Ajouter un administrateur",
        config_add_admin_help: "Un mot de passe temporaire sera généré et envoyé par courriel à l'administrateur.",
        config_add_admin_submit: "Créer et envoyer les identifiants",
        config_edit_admin_title: "Modifier l'administrateur",
        config_reset_password_btn: "Réinitialiser le mot de passe",
        config_reset_password_title: "Réinitialiser le mot de passe",
        config_reset_password_message: "Un nouveau mot de passe sera généré et envoyé à {email}.",
        config_reset_password_confirm_btn: "Envoyer le nouveau mot de passe par courriel",
        role_admin: "admin",
        role_viewer: "viewer",
        action_delete: "Supprimer",
        action_video: "Voir vidéo",
        action_profile: "Profil",
        action_shortlist: "Mettre en favoris",

        // ─── Dashboard – Tableaux ───
        th_title: "Titre",
        th_department: "Département",
        th_location: "Lieu",
        th_status: "Statut",
        th_candidates: "Candidats",
        th_created: "Créé le",
        th_actions: "Actions",
        th_poste: "Poste",
        th_platform: "Plateforme",
        th_start_date: "Date début",
        th_end_date: "Date fin",
        th_views: "Vues",
        th_applications: "Candidatures",
        th_candidate: "Candidat",
        th_video: "Vidéo",
        th_rating: "Note",
        th_applied: "Postulé le",

        // ─── Dashboard – Statuts ───
        status_active: "Actif",
        status_paused: "Pausé",
        status_closed: "Fermé",
        status_new: "Nouveau",
        status_reviewed: "Évalué",
        status_accepted: "Accepté",
        status_rejected: "Refusé",
        status_shortlisted: "Favori",

        // ─── Dashboard – Statistiques ───
        stat_active_jobs: "Affichages actifs",
        stat_total_candidates: "Candidats totaux",
        stat_pending_review: "En attente d'évaluation",
        stat_shortlisted: "Shortlistés",
        chart_applications: "Candidatures par mois",
        chart_sales_history: "Historiques des ventes",
        dashboard_kpi_users: "Nombre d'utilisateurs",
        dashboard_kpi_videos: "Nombre de vidéos sous gestion",
        dashboard_kpi_sales: "Ventes du mois",
        dashboard_this_month: "ce mois",
        dashboard_vs_prev_month: "vs mois précédent",
        dashboard_events_log: "Journalisation des événements",
        dashboard_events_desc: "Historique des modifications et actions effectuées dans l'administration (Ventes, Configuration, Utilisateurs).",
        th_id: "ID",
        th_date: "Date",
        th_user: "Utilisateur",
        th_action: "Action",
        th_details: "Détails",
        event_modification: "Modification",
        event_creation: "Création",
        event_deletion: "Suppression",
        event_sale: "Vente",
        page_ventes_title: "Liste des ventes Stripe",
        page_ventes_desc: "Historique des transactions et abonnements Stripe.",
        th_client: "Client",
        th_amount: "Montant",
        status_paid: "Payé",
        btn_add: "Ajouter",
        btn_new_user: "Nouvel utilisateur",
        utilisateur_add_title: "Nouvel utilisateur",
        utilisateur_edit_title: "Modifier l'utilisateur",
        utilisateur_delete_modal_title: "Confirmer la suppression",
        utilisateur_delete_modal_message: "Êtes-vous sûr de vouloir supprimer l'utilisateur « {name} » ? Cette action est irréversible.",
        utilisateur_delete_confirm_btn: "Supprimer",
        role_client: "Client",
        role_evaluateur: "Évaluateur",
        role_user: "Utilisateur",
        utilisateur_add_plan: "Forfait",
        utilisateur_add_billable: "Facturable",
        th_prenom: "Prénom",
        th_nom: "Nom",
        th_name: "Nom",
        th_status: "Statut",
        th_video_limit: "Limite vidéos",
        status_active: "Actif",
        status_inactive: "Désactivé",
        th_price_monthly: "Prix mensuel",
        th_price_yearly: "Prix annuel",
        th_role: "Rôle",
        page_sync_desc: "Synchronisation des données avec les services externes.",
        page_bugs_idees_desc: "Signaler un problème ou proposer une amélioration pour la plateforme.",
        feedback_empty: "Aucun retour pour le moment.",
        feedback_th_type: "Type",
        feedback_th_source: "Source",
        feedback_th_user: "Utilisateur",
        feedback_th_status: "Statut",
        feedback_th_actions: "Actions",
        feedback_filter_bugs: "Bugs",
        feedback_filter_ideas: "Idées",
        feedback_delete_confirm: "Supprimer ce retour ?",
        feedback_transfer_to_task: "Transfert en tâche",
        modal_feedback_detail_title: "Détail du feedback",
        status_new: "Nouveau",
        status_in_progress: "En cours",
        status_resolved: "Réglé",
        feedback_internal_note: "Note interne",
        content_coming: "Contenu à venir",

        // ─── Dashboard – Modals ───
        modal_add_poste: "Nouveau poste",
        modal_add_affichage: "Nouvel affichage",
        modal_feedback_title: "Feedback",
        forfait_add_title: "Ajouter un forfait",
        forfait_edit_title: "Modifier le forfait",
        forfait_name_fr: "Nom (français)",
        forfait_name_en: "Nom (anglais)",
        forfait_features: "Fonctionnalités (une par ligne)",
        forfait_features_help: "Une fonctionnalité par ligne. Vide = affichage par défaut.",
        forfait_is_popular: "Badge POPULAIRE",
        btn_sync_plans: "Synchroniser avec tarifs",
        action_deactivate: "Désactiver",
        action_reactivate: "Réactiver",
        forfaits_inactive_title: "Forfaits désactivés",
        confirm_title: "Confirmation",

        // ─── Dashboard – Formulaires ───
        form_title: "Titre du poste",
        form_department: "Département",
        form_location: "Lieu",
        form_status: "Statut",
        form_poste: "Poste",
        form_platform: "Plateforme",
        form_start_date: "Date de début",
        form_end_date: "Date de fin",
        form_company_name: "Nom de l'entreprise",
        form_industry: "Secteur d'activité",
        form_email: "Email de contact",
        form_phone: "Téléphone",
        form_address: "Adresse",
        form_description: "Description de l'entreprise",
        form_logo: "Logo de l'entreprise",
        form_brand_color: "Couleur de la marque",
        btn_cancel: "Annuler",
        btn_confirm: "Confirmer",
        btn_save: "Enregistrer",
        btn_upload: "Téléverser",
        btn_send: "Envoyer",

        // ─── Dashboard – Paramètres ───
        settings_company: "Entreprise",
        settings_branding: "Marque employeur",
        settings_branding_title: "Personnalisation de la marque",
        settings_team: "Évaluateurs",
        settings_notifications: "Notifications",
        settings_billing: "Facturation",
        "billing.plan.interviews_50": "Gérez jusqu'à 50 entrevues à la fois (libérez des places en supprimant les anciennes)",
        "billing.plan.interviews_200": "Gérez jusqu'à 200 entrevues à la fois (libérez des places en supprimant les anciennes)",
        settings_integrations: "Intégrations",
        settings_company_info: "Informations de l'entreprise",
        logo_help: "Affiché sur votre profil et vos offres.",

        // ─── Dashboard – Feedback ───
        nav_feedback: "Feedback",
        label_type: "Type de retour",
        option_problem: "Signaler un problème",
        option_idea: "Soumettre une idée",
        label_message: "Votre message",
        feedback_placeholder: "Dites-nous en plus...",
        feedback_success: "Merci pour votre retour !",

        // ─── Dashboard – Candidat ───
        video_preview: "Aperçu vidéo",
        comments_title: "Commentaires",
        add_note_placeholder: "Ajouter une note..."
    },

    en: {
        // ─── Login page ───
        "nav.service": "Our Service",
        "nav.guide": "Candidate guide",
        "nav.recruiter": "Recruiter Login",
        "nav.candidate": "Candidate Login",
        "nav.login": "Log In",
        "nav.logout": "Log Out",
        "login.hero.title": "Good to see you <br><span class=\"highlight\">again!</span>",
        "login.hero.subtitle": "Access your space to manage your pre-selection video interviews and applications with ease.",
        "login.hero.subtitle.candidat": "Access your candidate space to manage your video interviews and applications with ease.",
        "login.hero.subtitle.entreprise": "Access your recruiter space to manage your job postings and evaluate candidates with ease.",
        "login.modal.title": "Are you?",
        "login.modal.candidate": "A Candidate",
        "login.modal.recruiter": "A Recruiter",
        "login.admin.badge": "Administration",
        "login.admin.hero": "Administration <span class=\"highlight\">login</span>",
        "login.title": "Login",
        "login.recruiter_link": "Recruiter space",
        "login.back_site": "Back to site",
        "login.email.label": "Email",
        "login.email.placeholder": "your@email.com",
        "login.password.label": "Password",
        "login.password.placeholder": "••••••••",
        "login.submit": "Log In",
        "login.forgot_password": "Forgot password?",
        "login.create_account": "Create an account",
        "login.create_demo": "Create your demo account",
        "login.signup_prompt": "Don't have an account?",
        "login.signup_link": "Sign up for free",
        "login.oauth.divider": "or",
        "login.oauth.google": "Continue with Google",
        "login.oauth.microsoft": "Continue with Microsoft",
        "login.error.email_required": "Please enter your email.",
        "login.error.password_required": "Please enter your password.",
        "login.error.turnstile": "Security verification failed. Please try again.",
        "login.error.generic": "An error occurred. Please try again.",
        "login.error.invalid": "Invalid email or password.",
        "login.error.otp_send_failed": "Could not send the code by email. Please try again.",
        "login.error.otp_invalid": "Invalid or expired code. Please try again.",
        "login.error.otp_expired": "The code has expired. Please log in again.",
        "login.otp.title": "Two-factor verification",
        "login.otp.desc": "A 6-digit code has been sent to",
        "login.otp.label": "Verification code",
        "login.otp.placeholder": "000000",
        "login.otp.submit": "Verify",
        "forgot.title": "Forgot password?",
        "forgot.desc": "Enter your email address and we'll send you a link to reset your password.",
        "forgot.email.label": "Email",
        "forgot.email.placeholder": "your@email.com",
        "forgot.turnstile": "Cloudflare security check",
        "forgot.submit": "Send reset link",

        // ─── Footer ───
        "footer.service": "Our Service",
        "footer.guide": "Candidate guide",
        "footer.legal": "Legal",
        "footer.privacy": "Privacy Policy",
        "footer.terms": "Terms of Use",
        "footer.contact": "Contact",
        "footer.proudly": "❤️<br>Proudly human",

        // ─── Dashboard ───
        nav_dashboard: "Dashboard",
        nav_ventes: "Sales",
        nav_forfaits: "Plans",
        nav_utilisateurs: "Users",
        utilisateurs_tab_users: "Platform users",
        nav_synchronisation: "Synchronization",
        nav_configuration: "Configuration",
        nav_bugs_idees: "Bugs and ideas",
        nav_developpement: "Development",
        page_developpement_desc: "Tools and information for developers.",
        dev_task_add: "Add a task",
        dev_task_edit: "Edit task",
        dev_task_title: "Title",
        dev_task_description: "Description",
        dev_task_priority: "Priority (0 = highest, 99 = lowest)",
        dev_col_todo: "To do",
        dev_col_in_progress: "In progress",
        dev_col_to_test: "To test",
        dev_col_deployed: "Deployed",
        dev_col_done: "Done",
        nav_database: "Database",
        nav_migrate: "SQL Migration",
        migrate_btn_run: "Run migrations",
        config_admins_title: "Administrators",
        config_admins_desc: "Accounts with access to the administration interface.",
        config_no_admins: "No administrators.",
        th_email: "Email",
        role_admin: "Admin",
        role_viewer: "Viewer",
        nav_postes: "Positions",
        nav_affichages: "Recruitment Drives",
        nav_candidats: "Candidates",
        nav_parametres: "Settings",
        dropdown_logout: "Log Out",
        dropdown_change_password: "Change password",
        change_password_title: "Change my password",
        change_password_current: "Current password",
        change_password_new: "New password",
        change_password_confirm: "Confirm new password",
        change_password_mismatch: "Passwords do not match.",
        change_password_submit: "Save",
        search: "Search...",
        filter_all: "All", filter_active: "Active", filter_paused: "Paused", filter_closed: "Closed",
        filter_expired: "Expired", filter_new: "New", filter_reviewed: "Reviewed", filter_shortlisted: "Favorites",
        filter_all_jobs: "All positions",
        postes_title: "Active Positions", affichages_title: "Current Drives", candidats_title: "Candidates",
        statistiques_title: "Dashboard", parametres_title: "Settings",
        add_poste: "New Position", add_affichage: "New Posting",
        delete_poste_title: "Delete position",
        action_edit: "Edit", action_view: "View", action_delete: "Deactivate",
        config_confirm_delete: "Deactivate this administrator?",
        config_delete_modal_title: "Confirm deactivation",
        config_delete_modal_message: "Are you sure you want to deactivate the administrator « {name} »?",
        config_delete_confirm_btn: "Deactivate",
        config_add_admin_btn: "Add administrator",
        config_add_admin_title: "Add administrator",
        config_add_admin_help: "A temporary password will be generated and sent by email to the administrator.",
        config_add_admin_submit: "Create and send credentials",
        config_edit_admin_title: "Edit administrator",
        config_reset_password_btn: "Reset password",
        config_reset_password_title: "Reset password",
        config_reset_password_message: "A new password will be generated and sent to {email}.",
        config_reset_password_confirm_btn: "Send new password by email",
        role_admin: "admin",
        role_viewer: "viewer",
        action_video: "Watch video", action_profile: "Profile", action_shortlist: "Mark as Favorite",
        th_title: "Title", th_department: "Department", th_location: "Location", th_status: "Status",
        th_candidates: "Candidates", th_created: "Created", th_actions: "Actions",
        th_poste: "Position", th_platform: "Platform", th_start_date: "Start Date", th_end_date: "End Date",
        th_views: "Views", th_applications: "Applications",
        th_candidate: "Candidate", th_video: "Video", th_rating: "Rating", th_applied: "Applied",
        status_active: "Active", status_paused: "Paused", status_closed: "Closed",
        status_new: "New", status_reviewed: "Reviewed", status_accepted: "Accepted", status_rejected: "Rejected", status_shortlisted: "Favorite",
        stat_active_jobs: "Active postings", stat_total_candidates: "Total candidates",
        stat_pending_review: "Pending review", stat_shortlisted: "Shortlisted",
        chart_applications: "Applications by month",
        chart_sales_history: "Sales history",
        dashboard_kpi_users: "Number of users",
        dashboard_kpi_videos: "Number of videos managed",
        dashboard_kpi_sales: "Monthly sales",
        dashboard_this_month: "this month",
        dashboard_vs_prev_month: "vs previous month",
        dashboard_events_log: "Event log",
        dashboard_events_desc: "History of modifications and actions in the admin (Sales, Configuration, Users).",
        th_id: "ID",
        th_date: "Date",
        th_user: "User",
        th_action: "Action",
        th_details: "Details",
        event_modification: "Modification",
        event_creation: "Creation",
        event_deletion: "Deletion",
        event_sale: "Sale",
        page_ventes_title: "Stripe sales list",
        page_ventes_desc: "History of transactions and Stripe subscriptions.",
        th_client: "Client",
        th_amount: "Amount",
        status_paid: "Paid",
        btn_add: "Add",
        btn_new_user: "New user",
        utilisateur_add_title: "New user",
        utilisateur_edit_title: "Edit user",
        utilisateur_delete_modal_title: "Confirm deletion",
        utilisateur_delete_modal_message: "Are you sure you want to delete the user « {name} »? This action cannot be undone.",
        utilisateur_delete_confirm_btn: "Delete",
        role_client: "Client",
        role_evaluateur: "Evaluator",
        role_user: "User",
        utilisateur_add_plan: "Plan",
        utilisateur_add_billable: "Billable",
        th_prenom: "First name",
        th_nom: "Last name",
        th_name: "Name",
        th_status: "Status",
        th_video_limit: "Video limit",
        status_active: "Active",
        status_inactive: "Disabled",
        th_price_monthly: "Monthly price",
        th_price_yearly: "Yearly price",
        th_role: "Role",
        page_sync_desc: "Data synchronization with external services.",
        page_bugs_idees_desc: "Report a problem or suggest an improvement for the platform.",
        feedback_empty: "No feedback yet.",
        feedback_th_type: "Type",
        feedback_th_source: "Source",
        feedback_th_user: "User",
        feedback_th_status: "Status",
        feedback_th_actions: "Actions",
        feedback_filter_bugs: "Bugs",
        feedback_filter_ideas: "Ideas",
        feedback_delete_confirm: "Delete this feedback?",
        feedback_transfer_to_task: "Transfer to task",
        modal_feedback_detail_title: "Feedback detail",
        status_new: "New",
        status_in_progress: "In progress",
        status_resolved: "Resolved",
        feedback_internal_note: "Internal note",
        content_coming: "Content coming soon",
        modal_add_poste: "New Position", modal_add_affichage: "New Posting", modal_feedback_title: "Feedback",
        forfait_add_title: "Add a plan", forfait_edit_title: "Edit plan", forfait_name_fr: "Name (French)", forfait_name_en: "Name (English)",
        forfait_features: "Features (one per line)", forfait_features_help: "One feature per line. Empty = default display.", forfait_is_popular: "POPULAR badge",
        btn_sync_plans: "Sync with pricing page",
        action_deactivate: "Deactivate",
        action_reactivate: "Reactivate",
        forfaits_inactive_title: "Disabled plans",
        confirm_title: "Confirmation",
        form_title: "Position Title", form_department: "Department", form_location: "Location", form_status: "Status",
        form_poste: "Position", form_platform: "Platform", form_start_date: "Start Date", form_end_date: "End Date",
        form_company_name: "Company name", form_industry: "Industry",
        form_email: "Contact email", form_phone: "Phone", form_address: "Address",
        form_description: "Company description", form_logo: "Company logo",
        form_brand_color: "Brand Color",
        btn_cancel: "Cancel", btn_confirm: "Confirm", btn_save: "Save", btn_upload: "Upload", btn_send: "Send",
        settings_company: "Company", settings_branding: "Employer Branding",
        settings_branding_title: "Brand Customization",
        settings_team: "Evaluators", settings_notifications: "Notifications",
        settings_billing: "Billing", settings_integrations: "Integrations",
        "billing.plan.interviews_50": "Manage up to 50 interviews at a time (free up slots by removing older ones)",
        "billing.plan.interviews_200": "Manage up to 200 interviews at a time (free up slots by removing older ones)",
        settings_company_info: "Company information",
        logo_help: "Displayed on your profile and postings.",
        nav_feedback: "Feedback", label_type: "Feedback Type",
        option_problem: "Report a Problem", option_idea: "Submit an Idea",
        label_message: "Your Message", feedback_placeholder: "Tell us more...",
        feedback_success: "Thank you for your feedback!",
        video_preview: "Video Preview", comments_title: "Comments",
        add_note_placeholder: "Add a note..."
    }
};

// ─── API publique ───────────────────────────────────────────────────────

function getLanguage() {
    var stored = localStorage.getItem('language');
    if (stored) return stored;
    var browserLang = navigator.language || navigator.userLanguage || '';
    return browserLang.toLowerCase().startsWith('fr') ? 'fr' : 'en';
}

function setLanguage(lang) {
    localStorage.setItem('language', lang);
    document.documentElement.lang = lang;
    updateContent();
}

function updateContent() {
    var lang = getLanguage();
    var dict = translations[lang] || translations.fr;

    // data-i18n → texte (ou placeholder pour input/textarea)
    document.querySelectorAll('[data-i18n]').forEach(function (el) {
        var key = el.getAttribute('data-i18n');
        if (!dict[key]) return;
        if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
            el.placeholder = dict[key];
        } else {
            var val = dict[key];
            el[val.indexOf('<') >= 0 ? 'innerHTML' : 'textContent'] = val;
        }
    });

    // data-i18n-placeholder → placeholder explicite
    document.querySelectorAll('[data-i18n-placeholder]').forEach(function (el) {
        var key = el.getAttribute('data-i18n-placeholder');
        if (dict[key]) el.placeholder = dict[key];
    });

    // data-i18n-title → title
    document.querySelectorAll('[data-i18n-title]').forEach(function (el) {
        var key = el.getAttribute('data-i18n-title');
        if (dict[key]) el.title = dict[key];
    });

    // Boutons .lang-toggle (site vitrine)
    document.querySelectorAll('.lang-toggle').forEach(function (toggle) {
        toggle.textContent = lang === 'fr' ? 'EN' : 'FR';
    });

    // Boutons .lang-btn (dashboard)
    document.querySelectorAll('.lang-btn').forEach(function (btn) {
        btn.classList.toggle('active', btn.dataset.lang === lang);
    });
}

function toggleLanguage(e) {
    if (e) e.preventDefault();
    setLanguage(getLanguage() === 'fr' ? 'en' : 'fr');
}

// ─── Initialisation ─────────────────────────────────────────────────────

function initI18n() {
    var lang = getLanguage();
    document.documentElement.lang = lang;
    updateContent();

    // Site vitrine : .lang-toggle
    document.querySelectorAll('.lang-toggle').forEach(function (btn) {
        btn.addEventListener('click', toggleLanguage);
    });

    // Dashboard : .lang-btn
    document.querySelectorAll('.lang-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            setLanguage(btn.getAttribute('data-lang'));
        });
    });
}

document.addEventListener('DOMContentLoaded', initI18n);
