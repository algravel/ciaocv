/**
 * i18n pour la page de connexion (app) et pages utilisant data-i18n.
 * FR / EN avec bascule via .lang-toggle et localStorage.
 */
const translations = {
    fr: {
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
        "login.signup_prompt": "Pas encore de compte ?",
        "login.signup_link": "S'inscrire gratuitement",
        "login.oauth.divider": "ou",
        "login.oauth.google": "Continuer avec Google",
        "login.oauth.microsoft": "Continuer avec Microsoft",
        "footer.service": "Notre service",
        "footer.guide": "Préparez votre entrevue de présélection",
        "footer.legal": "Légal",
        "footer.privacy": "Politique de confidentialité",
        "footer.terms": "Conditions d'utilisation",
        "footer.contact": "Contact",
        "footer.proudly": "Fièrement humain ❤️",
        "forgot.title": "Mot de passe oublié ?",
        "forgot.desc": "Entrez votre adresse courriel et nous vous enverrons un lien pour réinitialiser votre mot de passe.",
        "forgot.email.label": "Courriel",
        "forgot.email.placeholder": "votre@courriel.com",
        "forgot.turnstile": "Vérification de sécurité Cloudflare",
        "forgot.submit": "Envoyer le lien"
    },
    en: {
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
        "login.signup_prompt": "Don't have an account?",
        "login.signup_link": "Sign up for free",
        "login.oauth.divider": "or",
        "login.oauth.google": "Continue with Google",
        "login.oauth.microsoft": "Continue with Microsoft",
        "footer.service": "Our Service",
        "footer.guide": "Prepare your pre-selection interview",
        "footer.legal": "Legal",
        "footer.privacy": "Privacy Policy",
        "footer.terms": "Terms of Use",
        "footer.contact": "Contact",
        "footer.proudly": "Proudly human ❤️",
        "forgot.title": "Forgot password?",
        "forgot.desc": "Enter your email address and we'll send you a link to reset your password.",
        "forgot.email.label": "Email",
        "forgot.email.placeholder": "your@email.com",
        "forgot.turnstile": "Cloudflare security check",
        "forgot.submit": "Send reset link"
    }
};

function getLanguage() {
    const stored = localStorage.getItem('language');
    if (stored) return stored;
    const browserLang = navigator.language || navigator.userLanguage || '';
    return browserLang.toLowerCase().startsWith('fr') ? 'fr' : 'en';
}

function setLanguage(lang) {
    localStorage.setItem('language', lang);
    document.documentElement.lang = lang;
    updateContent();
    updateToggleState();
}

function updateToggleState() {
    const lang = getLanguage();
    document.querySelectorAll('.lang-toggle').forEach(function (toggle) {
        toggle.textContent = lang === 'fr' ? 'EN' : 'FR';
    });
}

function updateContent() {
    const lang = getLanguage();
    const elements = document.querySelectorAll('[data-i18n]');
    elements.forEach(function (element) {
        const key = element.getAttribute('data-i18n');
        if (translations[lang] && translations[lang][key]) {
            if (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA') {
                element.placeholder = translations[lang][key];
            } else {
                element.innerHTML = translations[lang][key];
            }
        }
    });
    updateToggleState();
}

function toggleLanguage(e) {
    if (e) e.preventDefault();
    var current = getLanguage();
    var next = current === 'fr' ? 'en' : 'fr';
    setLanguage(next);
}

document.addEventListener('DOMContentLoaded', function () {
    var lang = getLanguage();
    document.documentElement.lang = lang;
    updateContent();
    document.querySelectorAll('.lang-toggle').forEach(function (btn) {
        btn.addEventListener('click', toggleLanguage);
    });
});
