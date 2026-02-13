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
        "login.title": "Connexion",
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
        "login.error.turnstile": "Vérification de sécurité échouée. Veuillez réessayer.",
        "login.error.password_required": "Veuillez entrer votre mot de passe.",
        "login.error.invalid": "Courriel ou mot de passe incorrect.",
        "login.error.inactive": "Votre compte est désactivé. Contactez votre administrateur.",
        "login.error.generic": "Une erreur est survenue. Veuillez réessayer.",
        "login.error.otp_send_failed": "Impossible d'envoyer le code de vérification. Veuillez réessayer.",
        "login.error.otp_invalid": "Code invalide ou expiré. Veuillez réessayer.",
        "login.error.otp_expired": "Le code a expiré. Veuillez vous reconnecter.",
        "login.otp.title": "Vérification par courriel",
        "login.otp.desc": "Un code de vérification a été envoyé à",
        "login.otp.label": "Code à 6 chiffres",
        "login.otp.submit": "Vérifier",
        "login.otp.expires": "Le code expire dans 10 minutes.",
        "forgot.title": "Mot de passe oublié ?",
        "forgot.desc": "Entrez votre adresse courriel et nous vous enverrons un lien pour réinitialiser votre mot de passe.",
        "forgot.email.label": "Courriel",
        "forgot.email.placeholder": "votre@courriel.com",
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
        nav_postes: "Postes",
        nav_affichages: "Affichages",
        nav_candidats: "Candidats",
        nav_parametres: "Paramètres",
        dropdown_logout: "Se déconnecter",
        search: "Rechercher...",

        // ─── Dashboard – Filtres ───
        filter_all: "Tous",
        filter_active: "Actifs",
        filter_paused: "Pausés",
        filter_closed: "Fermés",
        filter_inactive: "Non actifs",
        filter_archived: "Archivés",
        filter_expired: "Expirés",
        filter_new: "Nouveaux",
        filter_reviewed: "Évalués",
        filter_rejected: "Refusés",
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
        delete_poste_title: "Supprimer le poste",
        action_edit: "Modifier",
        action_view: "Voir",
        action_delete: "Supprimer",
        action_video: "Voir vidéo",
        action_download_cv: "Télécharger CV",
        cv_label: "CV",
        cv_missing: "CV manquant",
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
        th_new_candidates: "Non évalué",
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
        status_banque: "Banque",
        status_accepted: "Accepté",
        status_rejected: "Refusé",
        status_shortlisted: "Favori",

        // ─── Dashboard – Statistiques ───
        stat_active_jobs: "Affichages actifs",
        stat_total_candidates: "Candidats totaux",
        stat_pending_review: "En attente d'évaluation",
        stat_shortlisted: "Shortlistés",
        chart_applications: "Candidatures par mois",

        // ─── Dashboard – Modals ───
        modal_add_poste: "Nouveau poste",
        modal_add_affichage: "Nouvel affichage",
        modal_feedback_title: "Feedback",

        // ─── Dashboard – Formulaires ───
        option_select: "— Sélectionner —",
        dept_technologie: "Technologie",
        dept_gestion: "Gestion",
        dept_design: "Design",
        dept_strategie: "Stratégie",
        dept_marketing: "Marketing",
        dept_ressources_humaines: "Ressources humaines",
        dept_finance: "Finance",
        dept_operations: "Opérations",
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
        btn_save: "Enregistrer",
        btn_upload: "Téléverser",
        btn_send: "Envoyer",

        // ─── Dashboard – Paramètres ───
        settings_company: "Entreprise",
        settings_branding: "Marque employeur",
        settings_branding_title: "Personnalisation de la marque",
        settings_team: "Évaluateurs",
        settings_departments: "Départements",
        settings_notifications: "Notifications",
        settings_billing: "Facturation",
        "billing.plan.interviews_50": "Gérez jusqu'à 50 entrevues à la fois (libérez des places en supprimant les anciennes)",
        "billing.plan.interviews_200": "Gérez jusqu'à 200 entrevues à la fois (libérez des places en supprimant les anciennes)",
        settings_integrations: "Intégrations",
        settings_company_info: "Informations de l'entreprise",
        form_timezone: "Fuseau horaire",
        timezone_help: "Les dates sont enregistrées en UTC et affichées selon ce fuseau.",
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
        comments_subtitle: "Échangez avec votre équipe sur ce candidat",
        add_note_placeholder: "Écrire un commentaire...",
        no_comments: "Aucun commentaire",
        comments_empty_desc: "Soyez le premier à partager votre avis avec l'équipe",
        comment_send: "Envoyer",

        // ─── Dashboard – KPI & Stats ───
        kpi_my_plan: "Mon forfait",
        kpi_interviews_available: "entrevues disponibles",
        kpi_complete_profile: "Compléter votre profil",
        kpi_profile_completed: "Profil complété",
        kpi_task_remaining: "tâche restante",
        questions_proposed: "Questions proposées",
        record_duration_help: "Temps maximum pour l'ensemble des questions pour le candidat.",
        kpi_tasks_remaining: "tâches restantes",
        kpi_all_done: "Tout est en ordre \u2713",
        kpi_since_last_month: "depuis le mois dernier",
        chart_no_data: "Aucune candidature pour le moment",
        forfait_manage: "Gérer mon forfait",

        // ─── Dashboard – Event Log ───
        events_title: "Journalisation des événements",
        events_empty: "Aucun événement enregistré.",
        th_date: "Date",
        th_user: "Utilisateur",
        th_action: "Action",
        th_details: "Détails",

        // ─── Dashboard – Modals ───
        modal_complete_profile: "Compléter votre profil",
        profile_step1_title: "Détail de votre organisation",
        profile_step1_sub: "Paramètres de l'entreprise",
        profile_step2_title: "Créer un poste",
        profile_step2_sub: "Définir vos postes à pourvoir",
        profile_step3_title: "Créer un affichage",
        profile_step3_sub: "Publier votre poste",
        badge_done: "Fait",
        modal_delete_affichage: "Supprimer l'affichage",
        modal_delete_affichage_msg: "Êtes-vous sûr de vouloir supprimer cet affichage ?",
        modal_delete_poste_msg: "Êtes-vous sûr de vouloir supprimer ce poste ?",
        btn_add: "Ajouter",
        contact_email: "Email"
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
        "login.title": "Login",
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
        "login.error.turnstile": "Security verification failed. Please try again.",
        "login.error.password_required": "Please enter your password.",
        "login.error.invalid": "Incorrect email or password.",
        "login.error.inactive": "Your account is disabled. Contact your administrator.",
        "login.error.generic": "An error occurred. Please try again.",
        "login.error.otp_send_failed": "Unable to send verification code. Please try again.",
        "login.error.otp_invalid": "Invalid or expired code. Please try again.",
        "login.error.otp_expired": "The code has expired. Please log in again.",
        "login.otp.title": "Email verification",
        "login.otp.desc": "A verification code has been sent to",
        "login.otp.label": "6-digit code",
        "login.otp.submit": "Verify",
        "login.otp.expires": "The code expires in 10 minutes.",
        "forgot.title": "Forgot password?",
        "forgot.desc": "Enter your email address and we'll send you a link to reset your password.",
        "forgot.email.label": "Email",
        "forgot.email.placeholder": "your@email.com",
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
        nav_postes: "Positions",
        nav_affichages: "Recruitment Drives",
        nav_candidats: "Candidates",
        nav_parametres: "Settings",
        dropdown_logout: "Log Out",
        search: "Search...",
        filter_all: "All", filter_active: "Active", filter_paused: "Paused", filter_closed: "Closed",
        filter_inactive: "Inactive", filter_archived: "Archived",
        filter_expired: "Expired", filter_new: "New", filter_reviewed: "Reviewed", filter_rejected: "Rejected", filter_shortlisted: "Favorites",
        filter_all_jobs: "All positions",
        postes_title: "Active Positions", affichages_title: "Current Drives", candidats_title: "Candidates",
        statistiques_title: "Dashboard", parametres_title: "Settings",
        add_poste: "New Position", add_affichage: "New Posting",
        delete_poste_title: "Delete position",
        action_edit: "Edit", action_view: "View", action_delete: "Delete",
        action_video: "Watch video", action_download_cv: "Download CV", cv_label: "CV", cv_missing: "CV missing", action_profile: "Profile", action_shortlist: "Mark as Favorite",
        th_title: "Title", th_department: "Department", th_location: "Location", th_status: "Status",
        th_candidates: "Candidates", th_created: "Created", th_actions: "Actions",
        th_poste: "Position", th_platform: "Platform", th_start_date: "Start Date", th_end_date: "End Date",
        th_views: "Views", th_applications: "Applications", th_new_candidates: "Unevaluated",
        th_candidate: "Candidate", th_video: "Video", th_rating: "Rating", th_applied: "Applied",
        status_active: "Active", status_paused: "Paused", status_closed: "Closed",
        status_new: "New", status_reviewed: "Reviewed", status_accepted: "Accepted", status_rejected: "Rejected", status_shortlisted: "Favorite",
        stat_active_jobs: "Active postings", stat_total_candidates: "Total candidates",
        stat_pending_review: "Pending review", stat_shortlisted: "Shortlisted",
        chart_applications: "Applications by month",
        modal_add_poste: "New Position", modal_add_affichage: "New Posting", modal_feedback_title: "Feedback",
        option_select: "— Select —",
        dept_technologie: "Technology",
        dept_gestion: "Management",
        dept_design: "Design",
        dept_strategie: "Strategy",
        dept_marketing: "Marketing",
        dept_ressources_humaines: "Human Resources",
        dept_finance: "Finance",
        dept_operations: "Operations",
        form_title: "Position Title", form_department: "Department", form_location: "Location", form_status: "Status",
        form_poste: "Position", form_platform: "Platform", form_start_date: "Start Date", form_end_date: "End Date",
        form_company_name: "Company name", form_industry: "Industry",
        form_email: "Contact email", form_phone: "Phone", form_address: "Address",
        form_description: "Company description", form_logo: "Company logo",
        form_timezone: "Timezone", timezone_help: "Dates are stored in UTC and displayed in this timezone.",
        form_brand_color: "Brand Color",
        btn_cancel: "Cancel", btn_save: "Save", btn_upload: "Upload", btn_send: "Send",
        settings_company: "Company", settings_branding: "Employer Branding",
        settings_branding_title: "Brand Customization",
        settings_team: "Evaluators", settings_departments: "Departments", settings_notifications: "Notifications",
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
        comments_subtitle: "Share feedback with your team on this candidate",
        add_note_placeholder: "Write a comment...",
        no_comments: "No comments",
        comments_empty_desc: "Be the first to share your feedback with the team",
        comment_send: "Send",

        // ─── Dashboard – KPI & Stats ───
        kpi_my_plan: "My Plan",
        kpi_interviews_available: "interviews available",
        kpi_complete_profile: "Complete your profile",
        kpi_profile_completed: "Profile completed",
        kpi_task_remaining: "task remaining",
        questions_proposed: "Suggested questions",
        record_duration_help: "Maximum time for all questions for the candidate.",
        kpi_tasks_remaining: "tasks remaining",
        kpi_all_done: "All done \u2713",
        kpi_since_last_month: "since last month",
        chart_no_data: "No applications yet",
        forfait_manage: "Manage my plan",

        // ─── Dashboard – Event Log ───
        events_title: "Event Log",
        events_empty: "No events recorded.",
        th_date: "Date",
        th_user: "User",
        th_action: "Action",
        th_details: "Details",

        // ─── Dashboard – Modals ───
        modal_complete_profile: "Complete your profile",
        profile_step1_title: "Organization details",
        profile_step1_sub: "Company settings",
        profile_step2_title: "Create a position",
        profile_step2_sub: "Define your open positions",
        profile_step3_title: "Create a posting",
        profile_step3_sub: "Publish your position",
        badge_done: "Done",
        modal_delete_affichage: "Delete posting",
        modal_delete_affichage_msg: "Are you sure you want to delete this posting?",
        modal_delete_poste_msg: "Are you sure you want to delete this position?",
        btn_add: "Add",
        contact_email: "Email"
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

    // data-i18n → innerHTML (ou placeholder pour input/textarea)
    document.querySelectorAll('[data-i18n]').forEach(function (el) {
        var key = el.getAttribute('data-i18n');
        if (!dict[key]) return;
        if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
            el.placeholder = dict[key];
        } else {
            el.innerHTML = dict[key];
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

document.addEventListener('DOMContentLoaded', function () {
    var lang = getLanguage();
    document.documentElement.lang = lang;
    updateContent();

    // Site vitrine : .lang-toggle
    document.querySelectorAll('.lang-toggle').forEach(function (btn) {
        btn.addEventListener('click', toggleLanguage);
    });

    // Dashboard : .lang-btn
    document.querySelectorAll('.lang-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            setLanguage(btn.dataset.lang);
        });
    });
});
