/**
 * Affiche les dates/heures en GMT-5 (America/Montreal).
 * Les éléments <time datetime="ISO8601"> (UTC) sont convertis à l'affichage.
 */
(function() {
    var TZ = 'America/Montreal'; // GMT-5 (Québec)

    function formatLocalTime(isoString) {
        try {
            var d = new Date(isoString);
            if (isNaN(d.getTime())) return null;
            return d.toLocaleString('fr-CA', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                timeZone: TZ
            });
        } catch (e) {
            return null;
        }
    }

    function init() {
        document.querySelectorAll('time[datetime]').forEach(function(el) {
            var iso = el.getAttribute('datetime');
            var formatted = formatLocalTime(iso);
            if (formatted) el.textContent = formatted;
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
