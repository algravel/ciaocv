/**
 * CiaoCV – Système i18n unifié
 * Gère les traductions FR/EN pour toutes les pages (login + dashboard).
 * Utilise localStorage pour persister le choix de l'utilisateur.
 */
const translations = {
    fr: {
        // ─── Page de connexion ───
        "nav.service": "Notre service",
        "nav.guide": "Préparez votre entrevue de présélection",
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
        "forgot.title": "Mot de passe oublié ?",
        "forgot.desc": "Entrez votre adresse courriel et nous vous enverrons un lien pour réinitialiser votre mot de passe.",
        "forgot.email.label": "Courriel",
        "forgot.email.placeholder": "votre@courriel.com",
        "forgot.turnstile": "Vérification de sécurité Cloudflare",
        "forgot.submit": "Envoyer le lien",

        // ─── Footer ───
        "footer.service": "Notre service",
        "footer.guide": "Préparez votre entrevue de présélection",
        "footer.legal": "Légal",
        "footer.privacy": "Politique de confidentialité",
        "footer.terms": "Conditions d'utilisation",
        "footer.contact": "Contact",
        "footer.proudly": "Fièrement humain ❤️",

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
        action_view: "Voir",
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
        settings_team: "Équipe",
        settings_notifications: "Notifications",
        settings_billing: "Facturation",
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
        "nav.guide": "Prepare your pre-selection interview",
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
        "forgot.title": "Forgot password?",
        "forgot.desc": "Enter your email address and we'll send you a link to reset your password.",
        "forgot.email.label": "Email",
        "forgot.email.placeholder": "your@email.com",
        "forgot.turnstile": "Cloudflare security check",
        "forgot.submit": "Send reset link",

        // ─── Footer ───
        "footer.service": "Our Service",
        "footer.guide": "Prepare your pre-selection interview",
        "footer.legal": "Legal",
        "footer.privacy": "Privacy Policy",
        "footer.terms": "Terms of Use",
        "footer.contact": "Contact",
        "footer.proudly": "Proudly human ❤️",

        // ─── Dashboard ───
        nav_dashboard: "Dashboard",
        nav_postes: "Positions",
        nav_affichages: "Recruitment Drives",
        nav_candidats: "Candidates",
        nav_parametres: "Settings",
        dropdown_logout: "Log Out",
        search: "Search...",
        filter_all: "All", filter_active: "Active", filter_paused: "Paused", filter_closed: "Closed",
        filter_expired: "Expired", filter_new: "New", filter_reviewed: "Reviewed", filter_shortlisted: "Favorites",
        filter_all_jobs: "All positions",
        postes_title: "Active Positions", affichages_title: "Current Drives", candidats_title: "Candidates",
        statistiques_title: "Dashboard", parametres_title: "Settings",
        add_poste: "New Position", add_affichage: "New Posting",
        action_edit: "Edit", action_view: "View", action_delete: "Delete",
        action_video: "Watch video", action_profile: "Profile", action_shortlist: "Mark as Favorite",
        th_title: "Title", th_department: "Department", th_location: "Location", th_status: "Status",
        th_candidates: "Candidates", th_created: "Created", th_actions: "Actions",
        th_poste: "Position", th_platform: "Platform", th_start_date: "Start Date", th_end_date: "End Date",
        th_views: "Views", th_applications: "Applications",
        th_candidate: "Candidate", th_video: "Video", th_rating: "Rating", th_applied: "Applied",
        status_active: "Active", status_paused: "Paused", status_closed: "Closed",
        status_new: "New", status_reviewed: "Reviewed", status_rejected: "Rejected", status_shortlisted: "Favorite",
        stat_active_jobs: "Active postings", stat_total_candidates: "Total candidates",
        stat_pending_review: "Pending review", stat_shortlisted: "Shortlisted",
        chart_applications: "Applications by month",
        modal_add_poste: "New Position", modal_add_affichage: "New Posting", modal_feedback_title: "Feedback",
        form_title: "Position Title", form_department: "Department", form_location: "Location", form_status: "Status",
        form_poste: "Position", form_platform: "Platform", form_start_date: "Start Date", form_end_date: "End Date",
        form_company_name: "Company name", form_industry: "Industry",
        form_email: "Contact email", form_phone: "Phone", form_address: "Address",
        form_description: "Company description", form_logo: "Company logo",
        form_brand_color: "Brand Color",
        btn_cancel: "Cancel", btn_save: "Save", btn_upload: "Upload", btn_send: "Send",
        settings_company: "Company", settings_branding: "Employer Branding",
        settings_branding_title: "Brand Customization",
        settings_team: "Team", settings_notifications: "Notifications",
        settings_billing: "Billing", settings_integrations: "Integrations",
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
