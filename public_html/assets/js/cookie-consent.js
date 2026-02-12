/**
 * Bandeau de consentement des cookies - CiaoCV
 * Affiche un message permettant d'accepter ou refuser les cookies.
 */
(function () {
    const STORAGE_KEY = 'ciaocv_cookie_consent';

    function getConsent() {
        return localStorage.getItem(STORAGE_KEY);
    }

    function setConsent(value) {
        localStorage.setItem(STORAGE_KEY, value);
    }

    var defaultTexts = {
        'cookie.message': 'Nous utilisons des cookies pour améliorer votre expérience et l\'analyse du site. Vous pouvez accepter ou refuser les cookies non essentiels.',
        'cookie.accept': 'Accepter',
        'cookie.refuse': 'Refuser'
    };

    function getText(key) {
        if (typeof translations !== 'undefined' && typeof getLanguage === 'function') {
            const lang = getLanguage();
            const dict = translations[lang] || translations.fr;
            if (dict && dict[key]) return dict[key];
        }
        return defaultTexts[key] || key;
    }

    function hideBanner() {
        const banner = document.getElementById('cookie-consent-banner');
        if (banner) {
            banner.classList.add('cookie-consent--hidden');
            setTimeout(function () {
                banner.remove();
            }, 300);
        }
    }

    function createBanner() {
        if (getConsent()) return;

        const banner = document.createElement('div');
        banner.id = 'cookie-consent-banner';
        banner.className = 'cookie-consent';
        banner.setAttribute('role', 'dialog');
        banner.setAttribute('aria-label', 'Consentement des cookies');

        banner.innerHTML =
            '<div class="cookie-consent__inner">' +
            '<p class="cookie-consent__text" id="cookie-consent-text">' + getText('cookie.message') + '</p>' +
            '<div class="cookie-consent__actions">' +
            '<button type="button" class="cookie-consent__btn cookie-consent__btn--refuse" id="cookie-consent-refuse">' + getText('cookie.refuse') + '</button>' +
            '<button type="button" class="cookie-consent__btn cookie-consent__btn--accept" id="cookie-consent-accept">' + getText('cookie.accept') + '</button>' +
            '</div></div>';

        const style = document.createElement('style');
        style.textContent =
            '.cookie-consent{position:fixed;bottom:0;left:0;right:0;z-index:9999;padding:1rem 5%;background:var(--glass, rgba(15,23,42,0.95));backdrop-filter:blur(20px);border-top:1px solid var(--glass-border, rgba(255,255,255,0.1));box-shadow:0 -4px 20px rgba(0,0,0,0.2);transition:transform 0.3s ease,opacity 0.3s ease}.cookie-consent--hidden{transform:translateY(100%);opacity:0}.cookie-consent__inner{max-width:900px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:1.5rem;flex-wrap:wrap}.cookie-consent__text{margin:0;flex:1;min-width:200px;font-size:0.9rem;color:var(--text-gray,#94a3b8);line-height:1.5}.cookie-consent__actions{display:flex;gap:0.75rem;flex-shrink:0}.cookie-consent__btn{padding:0.6rem 1.25rem;border-radius:8px;font-weight:600;font-size:0.9rem;cursor:pointer;border:none;transition:background 0.2s,color 0.2s}.cookie-consent__btn--refuse{background:transparent;color:var(--text-gray,#94a3b8);border:1px solid var(--glass-border,rgba(255,255,255,0.2))}.cookie-consent__btn--refuse:hover{background:rgba(255,255,255,0.05)}.cookie-consent__btn--accept{background:var(--primary,#800020);color:white}.cookie-consent__btn--accept:hover{filter:brightness(1.1)}@media(max-width:600px){.cookie-consent__inner{flex-direction:column;text-align:center}.cookie-consent__actions{width:100%;justify-content:center}}';
        document.head.appendChild(style);

        document.body.appendChild(banner);

        document.getElementById('cookie-consent-accept').addEventListener('click', function () {
            setConsent('accepted');
            hideBanner();
        });

        document.getElementById('cookie-consent-refuse').addEventListener('click', function () {
            setConsent('refused');
            hideBanner();
        });
    }

    function init() {
        if (getConsent()) return;
        createBanner();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Exposer pour mise à jour quand la langue change
    window.updateCookieBannerText = function () {
        const textEl = document.getElementById('cookie-consent-text');
        const acceptBtn = document.getElementById('cookie-consent-accept');
        const refuseBtn = document.getElementById('cookie-consent-refuse');
        if (textEl) textEl.textContent = getText('cookie.message');
        if (acceptBtn) acceptBtn.textContent = getText('cookie.accept');
        if (refuseBtn) refuseBtn.textContent = getText('cookie.refuse');
    };
})();
