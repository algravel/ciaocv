/**
 * CiaoCV – Application JavaScript (Dashboard)
 * Navigation, modals, logique métier.
 * Les données viennent de APP_DATA (injecté par le layout PHP).
 */

/* ═══════════════════════════════════════════════
   DONNÉES (depuis PHP → JSON)
   ═══════════════════════════════════════════════ */
var postesData = {};
var affichagesData = {};
var candidatsData = {};
var teamMembersData = [];
var departmentsData = [];
var affichageCandidats = {};
var emailTemplates = [];

// Convertir les tableaux PHP en objets indexés pour un accès rapide
(function initData() {
    if (typeof APP_DATA === 'undefined') return;

    // Postes : tableau → objet { id: data }
    if (Array.isArray(APP_DATA.postes)) {
        APP_DATA.postes.forEach(function (p) { postesData[p.id] = p; });
    }

    // Affichages : déjà indexés par clé
    affichagesData = APP_DATA.affichages || {};

    // Candidats : déjà indexés par clé
    candidatsData = APP_DATA.candidats || {};

    // Candidats par affichage
    affichageCandidats = APP_DATA.candidatsByAff || {};

    // Email templates
    emailTemplates = APP_DATA.emailTemplates || [];

    // Départements
    departmentsData = Array.isArray(APP_DATA.departments) ? APP_DATA.departments.slice() : [];

    // Équipe (utilisateurs de l'entreprise)
    teamMembersData = Array.isArray(APP_DATA.teamMembers) ? APP_DATA.teamMembers.slice() : [];
})();

/* ═══════════════════════════════════════════════
   HELPERS
   ═══════════════════════════════════════════════ */
function statusToLabel(status) {
    if (!status) return '—';
    var s = String(status).toLowerCase();
    if (s === 'shortlisted' || s.includes('favori') || s.includes('banque')) return 'Banque';
    if (s === 'reviewed' || s.includes('accept')) return 'Accepté';
    if (s === 'rejected' || s.includes('refus')) return 'Refusé';
    if (s === 'new' || s.includes('nouveau')) return 'Nouveau';
    return status;
}

function copyShareUrl() {
    var el = document.getElementById('affichage-share-url');
    if (!el) return;
    var url = (el.href || el.textContent || '').trim();
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(function () { alert('Lien copié !'); }).catch(function () { fallbackCopy(url); });
    } else {
        fallbackCopy(url);
    }
}
function fallbackCopy(text) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed'; ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); alert('Lien copié !'); } catch (e) { }
    document.body.removeChild(ta);
}
function escapeHtml(str) {
    var div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

/**
 * Convertit une date UTC (ISO string ou timestamp) en date formatée selon le fuseau horaire de l'utilisateur.
 * Les dates en base sont en UTC (GMT0) ; on les affiche dans le fuseau choisi (ex: America/Montreal).
 * @param {string|number} utcString - Date ISO (ex: "2025-02-10T14:30:00Z") ou timestamp ms
 * @param {string} [timezone] - Fuseau (ex: "America/Montreal"). Par défaut: APP_DATA.userTimezone
 * @param {object} [opts] - Options pour toLocaleString: { dateStyle, timeStyle } ou { day, month, hour, minute }
 * @returns {string} Date formatée
 */
function formatUtcToLocal(utcString, timezone, opts) {
    if (!utcString) return '—';
    var s = String(utcString).trim();
    // Format MySQL "2025-02-10 14:30:00" ou "2025-02-10 14:30" = UTC (bd stocke en GMT0)
    if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/.test(s)) {
        s = s.replace(' ', 'T') + (s.length <= 16 ? ':00' : '') + 'Z';
    }
    var tz = timezone || (typeof APP_DATA !== 'undefined' && APP_DATA.userTimezone) || 'America/Montreal';
    var d = typeof utcString === 'number' ? new Date(utcString) : new Date(s);
    if (isNaN(d.getTime())) return String(utcString);
    var def = opts || { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' };
    var opt = {}; for (var k in def) opt[k] = def[k]; opt.timeZone = tz;
    try {
        return d.toLocaleString('fr-CA', opt);
    } catch (e) {
        return d.toLocaleString('fr-CA', def);
    }
}

/* ═══════════════════════════════════════════════
   SIDEBAR & NAVIGATION
   ═══════════════════════════════════════════════ */
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('active');
    document.querySelector('.sidebar-overlay').classList.toggle('active');
}

// Fermer la sidebar au clic sur un lien (mobile)
document.querySelectorAll('.nav-item, .nav-subitem').forEach(function (link) {
    link.addEventListener('click', function () {
        if (window.innerWidth <= 768) {
            document.querySelector('.sidebar').classList.remove('active');
            document.querySelector('.sidebar-overlay').classList.remove('active');
        }
    });
});

function activateNavItem(item) {
    document.querySelectorAll('.nav-item').forEach(function (nav) { nav.classList.remove('active'); });
    item.classList.add('active');

    // Fermer les autres sous-menus
    document.querySelectorAll('.nav-submenu').forEach(function (submenu) {
        if (item.dataset.section !== submenu.dataset.parent) {
            submenu.classList.remove('open');
        }
    });
    document.querySelectorAll('.submenu-arrow').forEach(function (arrow) {
        if (!item.contains(arrow)) arrow.style.transform = 'rotate(0deg)';
    });

    // Ouvrir le sous-menu associé
    if (item.classList.contains('has-submenu')) {
        var section = item.dataset.section;
        var submenu = document.querySelector('.nav-submenu[data-parent="' + section + '"]');
        if (submenu) submenu.classList.add('open');
        var arrow = item.querySelector('.submenu-arrow');
        if (arrow) arrow.style.transform = 'rotate(180deg)';
    }

    // Afficher la section
    var sectionId = item.dataset.section;
    document.querySelectorAll('.content-section').forEach(function (sec) { sec.classList.remove('active'); });
    var sectionEl = document.getElementById(sectionId + '-section');
    if (sectionEl) sectionEl.classList.add('active');

    // Paramètres : afficher le panneau « Entreprise » par défaut
    if (sectionId === 'parametres') {
        document.querySelectorAll('.settings-pane').forEach(function (pane) { pane.style.display = 'none'; });
        var firstPane = document.getElementById('settings-company');
        if (firstPane) firstPane.style.display = 'block';
    }
}

document.querySelectorAll('.nav-item[data-section]').forEach(function (item) {
    item.addEventListener('click', function (e) {
        var href = item.getAttribute('href');
        var section = item.dataset.section;
        // Tableau de bord : rechargement pour rafraîchir les KPIs et tâches restantes
        if (section === 'statistiques' && href) {
            e.preventDefault();
            window.location.href = href;
            return;
        }
        e.preventDefault();
        activateNavItem(item);
        if (href) history.pushState(null, null, href);
    });
});

// Sous-menus : intercept pour navigation SPA (éviter rechargement)
document.querySelectorAll('.nav-subitem').forEach(function (link) {
    link.addEventListener('click', function (e) {
        var href = link.getAttribute('href');
        if (href && href.indexOf('/') === 0) {
            e.preventDefault();
            history.pushState(null, null, href);
            handleSubitemClick(link);
        }
    });
});

function handleSubitemClick(subitem) {
    var submenu = subitem.closest('.nav-submenu');
    if (submenu) {
        var parentNavItem = document.querySelector('.nav-item[data-section="' + submenu.dataset.parent + '"]');
        if (parentNavItem) activateNavItem(parentNavItem);
    }
    if (subitem.classList.contains('settings-subitem')) {
        var targetId = subitem.getAttribute('data-target');
        if (targetId) {
            setTimeout(function () {
                document.querySelectorAll('.settings-pane').forEach(function (pane) { pane.style.display = 'none'; });
                var pane = document.getElementById(targetId);
                if (pane) {
                    pane.style.display = 'block';
                    if (targetId === 'settings-team' && typeof renderTeamMembers === 'function') renderTeamMembers();
                    if (targetId === 'settings-departments' && typeof renderDepartments === 'function') renderDepartments();
                    if (targetId === 'settings-communications' && typeof fetchEmailTemplates === 'function') fetchEmailTemplates();
                }
            }, 0);
        }
        return;
    }
    var i18nKey = subitem.getAttribute('data-i18n');
    if (i18nKey) {
        setTimeout(function () {
            var activeSection = document.querySelector('.content-section.active');
            if (activeSection) {
                var tab = activeSection.querySelector('.view-tab[data-i18n="' + i18nKey + '"]');
                if (tab) tab.click();
            }
        }, 0);
    }
}

// Navigation par pathname (/tableau-de-bord, /postes, etc.)
function handlePathNavigation() {
    var path = window.location.pathname || '/tableau-de-bord';
    var hash = window.location.hash || '';
    path = path.replace(/\/$/, '') || '/';

    // Sous-menu sélectionné (hash)
    if (hash) {
        var subitems = document.querySelectorAll('.nav-subitem[href^="' + path + '"]');
        for (var i = 0; i < subitems.length; i++) {
            if (subitems[i].getAttribute('href').indexOf(hash) !== -1) {
                handleSubitemClick(subitems[i]);
                return;
            }
        }
    }

    // Item principal selon le path
    var navItem = document.querySelector('.nav-item[href="' + path + '"]');
    if (!navItem) navItem = document.querySelector('.nav-item[href="/tableau-de-bord"]');
    if (!navItem) navItem = document.querySelector('.nav-item[data-section="statistiques"]');
    if (navItem) activateNavItem(navItem);
}

window.addEventListener('popstate', handlePathNavigation);
window.addEventListener('hashchange', handlePathNavigation);
window.addEventListener('load', function () {
    handlePathNavigation();
    // Ouvrir l'accordéon Affichages par défaut
    var affSub = document.querySelector('.nav-submenu[data-parent="affichages"]');
    if (affSub) affSub.classList.add('open');
    var affArrow = document.querySelector('.nav-item[data-section="affichages"] .submenu-arrow');
    if (affArrow) affArrow.style.transform = 'rotate(180deg)';
});

/* ═══════════════════════════════════════════════
   MODALS
   ═══════════════════════════════════════════════ */
function openModal(type) {
    document.getElementById(type + '-modal').classList.add('active');
}

function closeModal(type) {
    document.getElementById(type + '-modal').classList.remove('active');
}

document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) overlay.classList.remove('active');
    });
});

/* ═══════════════════════════════════════════════
   TABS + GENERIC TABLE FILTER (Postes, Affichages, Candidats)
   ═══════════════════════════════════════════════ */

/**
 * Generic filter: show/hide table rows based on data-status attribute.
 * @param {string} tableSelector  CSS selector for the table
 * @param {string} status         Filter value ('all' shows everything)
 */
function filterTable(tableSelector, status) {
    var rows = document.querySelectorAll(tableSelector + ' tbody tr.row-clickable');
    rows.forEach(function (row) {
        var rowStatus = row.getAttribute('data-status') || '';
        row.style.display = (status === 'all' || rowStatus === status) ? '' : 'none';
    });
}

/* Map each tab-group ID → its table selector */
var filterTabMap = {
    'postes-filter-tabs': '#postes-table',
    'affichages-filter-tabs': '#affichages-table',
    'candidats-filter-tabs': '#candidats-table'
};

/* Attach click listeners on all filter tab groups */
document.querySelectorAll('.view-tabs').forEach(function (tabGroup) {
    tabGroup.querySelectorAll('.view-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabGroup.querySelectorAll('.view-tab').forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');
            var tableSelector = filterTabMap[tabGroup.id];
            if (tableSelector) {
                filterTable(tableSelector, tab.getAttribute('data-filter') || 'all');
            }
        });
    });
});

/* === Backward compat wrapper === */
function filterPostesTable(status) { filterTable('#postes-table', status); }

/* === Search bars (postes, affichages, candidats) === */
['postes', 'affichages', 'candidats'].forEach(function (section) {
    var searchInput = document.querySelector('#' + section + '-section .search-bar input');
    if (!searchInput) return;
    searchInput.addEventListener('input', function () {
        var q = this.value.toLowerCase().trim();
        var activeTab = document.querySelector('#' + section + '-filter-tabs .view-tab.active');
        var activeFilter = activeTab ? (activeTab.getAttribute('data-filter') || 'all') : 'all';
        var rows = document.querySelectorAll('#' + section + '-table tbody tr.row-clickable');
        rows.forEach(function (row) {
            var rowStatus = row.getAttribute('data-status') || '';
            var statusMatch = (activeFilter === 'all' || rowStatus === activeFilter);
            var textMatch = !q || row.textContent.toLowerCase().indexOf(q) !== -1;
            row.style.display = (statusMatch && textMatch) ? '' : 'none';
        });
    });
});

/* === Sidebar sub-item hash navigation === */
var hashFilterMap = {
    /* Postes */
    'postes-tous': { section: 'postes', filter: 'all' },
    'postes-actifs': { section: 'postes', filter: 'active' },
    'postes-inactifs': { section: 'postes', filter: 'paused' },
    'postes-archives': { section: 'postes', filter: 'closed' },
    /* Affichages */
    'affichages-tous': { section: 'affichages', filter: 'all' },
    'affichages-actifs': { section: 'affichages', filter: 'active' },
    'affichages-expires': { section: 'affichages', filter: 'expired' },
    /* Candidats */
    'candidats-tous': { section: 'candidats', filter: 'all' },
    'candidats-nouveaux': { section: 'candidats', filter: 'new' },
    'candidats-evalues': { section: 'candidats', filter: 'reviewed' },
    'candidats-shortlistes': { section: 'candidats', filter: 'shortlisted' }
};

function applyHashFilter() {
    var hash = (window.location.hash || '').replace('#', '');
    var mapping = hashFilterMap[hash];
    if (!mapping) return;
    /* Activate the correct section */
    if (typeof showSection === 'function') showSection(mapping.section);
    /* Click the matching filter tab */
    var tabGroup = document.getElementById(mapping.section + '-filter-tabs');
    if (tabGroup) {
        var btn = tabGroup.querySelector('.view-tab[data-filter="' + mapping.filter + '"]');
        if (btn) btn.click();
    }
}

/* Run on page load + hash change */
window.addEventListener('hashchange', applyHashFilter);
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(applyHashFilter, 100);
    if (typeof updateCommentFormUser === 'function') updateCommentFormUser();
});

/* Paramètres : visibilité des panneaux (navigation par le menu latéral) */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.settings-pane').forEach(function (pane) {
        if (pane.id !== 'settings-company') pane.style.display = 'none';
    });
});

/* Journalisation : pagination par 10 */
var EVENTS_PER_PAGE = 10;
var eventsCurrentPage = 1;

function renderEventsPage(page) {
    var tbody = document.getElementById('events-tbody');
    var prevBtn = document.getElementById('events-prev');
    var nextBtn = document.getElementById('events-next');
    var pageInfo = document.getElementById('events-page-info');
    var pagination = document.getElementById('events-pagination');
    if (!tbody || !pageInfo) return;

    var evts = (typeof APP_DATA !== 'undefined' && APP_DATA.events) ? APP_DATA.events : [];
    var total = evts.length;
    var totalPages = Math.max(1, Math.ceil(total / EVENTS_PER_PAGE));
    page = Math.max(1, Math.min(page, totalPages));
    eventsCurrentPage = page;

    var start = (page - 1) * EVENTS_PER_PAGE;
    var slice = evts.slice(start, start + EVENTS_PER_PAGE);

    var badgeMap = { creation: 'event-badge--creation', create: 'event-badge--creation', modification: 'event-badge--modification', update: 'event-badge--modification', suppression: 'event-badge--suppression', delete: 'event-badge--suppression', evaluation: 'event-badge--evaluation', invitation: 'event-badge--invitation' };
    var moisFr = { Jan: 'janv', Feb: 'fév', Mar: 'mars', Apr: 'avr', May: 'mai', Jun: 'juin', Jul: 'juil', Aug: 'août', Sep: 'sept', Oct: 'oct', Nov: 'nov', Dec: 'déc' };

    tbody.innerHTML = '';
    if (slice.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="cell-muted">Aucun événement enregistré.</td></tr>';
    } else {
        slice.forEach(function (ev) {
            var createdFormatted = formatUtcToLocal(ev.created_at);
            var type = (ev.action_type || 'modification').toLowerCase();
            var badgeClass = badgeMap[type] || 'event-badge--modification';
            var tr = document.createElement('tr');
            tr.innerHTML = '<td class="cell-date">' + escapeHtml(createdFormatted) + '</td><td><strong>' + escapeHtml(ev.user_name || '—') + '</strong></td><td><span class="event-badge ' + escapeHtml(badgeClass) + '">' + escapeHtml((type.charAt(0).toUpperCase() + type.slice(1))) + '</span></td><td class="cell-muted">' + escapeHtml(ev.details || '') + '</td>';
            tbody.appendChild(tr);
        });
    }

    if (prevBtn) prevBtn.disabled = page <= 1;
    if (nextBtn) nextBtn.disabled = page >= totalPages;
    pageInfo.textContent = total === 0 ? '—' : 'Page ' + page + ' / ' + totalPages + ' (' + total + ' événements)';
    if (pagination) pagination.style.display = total > 0 ? 'flex' : 'none';
}

document.addEventListener('DOMContentLoaded', function () {
    var prevBtn = document.getElementById('events-prev');
    var nextBtn = document.getElementById('events-next');
    renderEventsPage(1);
    if (prevBtn) prevBtn.addEventListener('click', function () { renderEventsPage(eventsCurrentPage - 1); });
    if (nextBtn) nextBtn.addEventListener('click', function () { renderEventsPage(eventsCurrentPage + 1); });
});

/* Filtre candidats par affichage (Tous / Nouveaux / Évalués / Refusés) */
document.addEventListener('click', function (e) {
    var btn = e.target && e.target.closest && e.target.closest('[data-action="add-comment"]');
    if (btn) {
        e.preventDefault();
        if (typeof addComment === 'function') addComment();
    }
});

document.addEventListener('keydown', function (e) {
    var input = document.getElementById('detail-new-comment-input');
    if (input && document.activeElement === input && e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        if (typeof addComment === 'function') addComment();
    }
});

document.addEventListener('DOMContentLoaded', function () {
    var tabs = document.getElementById('affichage-candidats-filter-tabs');
    if (tabs) {
        tabs.addEventListener('click', function (e) {
            var tab = e.target.closest('.view-tab[data-filter]');
            if (tab && window._currentAffichageId) {
                var filter = tab.getAttribute('data-filter');
                _affichageCandidatsFilter = filter;
                renderAffichageCandidatsTable(window._currentAffichageId, filter);
            }
        });
    }
});

function saveCompanySettings(e) {
    e.preventDefault();
    var form = document.getElementById('form-settings-company');
    if (!form) return false;
    var submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Enregistrement...'; }
    var formData = new FormData(form);
    fetch('/parametres/entreprise', { method: 'POST', body: formData })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                if (data.company_name !== undefined) {
                    var el = document.querySelector('.company-name');
                    if (el) el.textContent = data.company_name || '';
                }
                if (data.timezone !== undefined) {
                    location.reload();
                }
            }
        })
        .catch(function () { })
        .finally(function () {
            if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Enregistrer'; }
        });
    return false;
}

/* ═══════════════════════════════════════════════
   USER DROPDOWN
   ═══════════════════════════════════════════════ */
function toggleUserDropdown(e) {
    e.stopPropagation();
    document.getElementById('userDropdown').classList.toggle('open');
}

document.addEventListener('click', function () {
    var dd = document.getElementById('userDropdown');
    if (dd) dd.classList.remove('open');
});

/* ═══════════════════════════════════════════════
   POSTE DETAIL
   ═══════════════════════════════════════════════ */
var currentPosteId = null;

function showPosteDetail(id) {
    if (id == null || id === '') return;
    var data = postesData[id] || postesData[String(id)];
    if (!data) return;
    currentPosteId = data.id;

    document.getElementById('detail-poste-title').textContent = data.title;
    document.getElementById('detail-poste-dept-loc').textContent = data.department + ' • ' + data.location;
    var statusSelect = document.getElementById('detail-poste-status-select');
    var sKey = (data.status || 'Actif').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    if (sKey === 'actif') statusSelect.value = 'actif';
    else if (sKey === 'non actif' || sKey === 'inactif' || sKey === 'pause') statusSelect.value = 'inactif';
    else if (sKey === 'archive' || sKey === 'ferme') statusSelect.value = 'archive';
    else statusSelect.value = 'actif';
    applyPosteStatusStyle(statusSelect);
    document.getElementById('detail-poste-candidates').textContent = data.candidates;
    var dateEl = document.getElementById('detail-poste-date');
    if (dateEl) dateEl.textContent = formatUtcToLocal(data.date) || '—';

    // Durée d'enregistrement
    var durationSelect = document.getElementById('detail-poste-record-duration');
    if (durationSelect) {
        durationSelect.value = data.recordDuration || 3;
    }

    renderPosteQuestions();

    // Lien pour les candidats (premier affichage lié à ce poste)
    var shareUrlContent = document.getElementById('detail-poste-share-url-content');
    if (shareUrlContent) {
        var baseUrl = (typeof APP_DATA !== 'undefined' && APP_DATA.appUrl) ? APP_DATA.appUrl : 'https://app.ciaocv.com';
        var aff = null;
        Object.keys(affichagesData || {}).forEach(function (k) {
            var a = affichagesData[k];
            if (a && (String(a.posteId) === String(data.id)) && a.shareLongId) {
                if (!aff) aff = a;
            }
        });
        if (aff && aff.shareLongId) {
            var url = baseUrl + '/entrevue/' + aff.shareLongId;
            shareUrlContent.innerHTML = '<a class="search-row-url" href="' + escapeHtml(url) + '" target="_blank" rel="noopener" style="flex:1; min-width:0;">' + escapeHtml(url) + '</a>' +
                '<button type="button" class="btn-icon btn-icon--copy" title="Copier le lien" data-copy-url="' + escapeHtml(url) + '" onclick="copyPosteShareUrl(this.getAttribute(\'data-copy-url\'))"><i class="fa-regular fa-copy"></i></button>';
        } else {
            var emptyTxt = (typeof translations !== 'undefined' && translations.fr) ? (translations.fr.poste_share_url_empty || 'Créez un affichage pour ce poste pour obtenir le lien.') : 'Créez un affichage pour ce poste pour obtenir le lien.';
            var btnTxt = (typeof translations !== 'undefined' && translations.fr) ? (translations.fr.poste_create_affichage || 'Créer un affichage') : 'Créer un affichage';
            shareUrlContent.innerHTML = '<span class="subtitle-muted">' + escapeHtml(emptyTxt) + '</span> ' +
                '<button type="button" class="btn btn-primary btn-sm" data-poste-id="' + escapeHtml(String(data.id)) + '" onclick="openAffichageModalForPoste(this.getAttribute(\'data-poste-id\'))"><i class="fa-solid fa-plus"></i> ' + escapeHtml(btnTxt) + '</button>';
        }
    }

    document.querySelectorAll('.content-section').forEach(function (s) { s.classList.remove('active'); });
    var detailSection = document.getElementById('poste-detail-section');
    if (detailSection) detailSection.classList.add('active');
    window.scrollTo(0, 0);
}

function openAffichageModalForPoste(posteId) {
    if (!posteId) return;
    var navItem = document.querySelector('.nav-item[data-section="affichages"]');
    if (navItem) navItem.click();
    var posteSelect = document.getElementById('affichage-poste_id');
    if (posteSelect) posteSelect.value = String(posteId);
    setTimeout(function () { openModal('affichage'); }, 150);
}

function copyPosteShareUrl(url) {
    if (!url) return;
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(function () {
            var btn = document.querySelector('#detail-poste-share-url-content .btn-icon--copy');
            if (btn) { btn.title = 'Copié !'; setTimeout(function () { btn.title = 'Copier le lien'; }, 1500); }
        });
    } else {
        var ta = document.createElement('textarea');
        ta.value = url;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
    }
}

// Clic sur une ligne du tableau Postes (délégation sur document)
document.addEventListener('click', function (e) {
    var row = e.target && e.target.closest && e.target.closest('#postes-table tr.row-clickable[data-poste-id]');
    if (row) {
        var id = row.getAttribute('data-poste-id');
        if (id && typeof showPosteDetail === 'function') showPosteDetail(id);
    }
});
document.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    var row = e.target && e.target.closest && e.target.closest('#postes-table tr.row-clickable[data-poste-id]');
    if (row) {
        e.preventDefault();
        var id = row.getAttribute('data-poste-id');
        if (id && typeof showPosteDetail === 'function') showPosteDetail(id);
    }
});

function goBackToPostes() {
    currentPosteId = null;
    document.querySelectorAll('.content-section').forEach(function (s) { s.classList.remove('active'); });
    document.getElementById('postes-section').classList.add('active');
}

function applyPosteStatusStyle(select) {
    select.className = 'status-select ml-auto';
    if (select.value === 'actif') select.classList.add('status-select--actif');
    else if (select.value === 'inactif') select.classList.add('status-select--inactif');
    else if (select.value === 'archive') select.classList.add('status-select--archive');
}

function savePosteToServer(fields) {
    if (!currentPosteId || !postesData[currentPosteId]) return;
    var data = postesData[currentPosteId];
    var statusMap = { actif: 'active', inactif: 'paused', archive: 'closed' };
    var formData = new FormData();
    formData.append('_csrf_token', (document.querySelector('input[name="_csrf_token"]') || {}).value || '');
    formData.append('id', currentPosteId);
    if (fields.questions !== undefined) formData.append('questions', JSON.stringify(data.questions || []));
    if (fields.status !== undefined) {
        var sel = document.getElementById('detail-poste-status-select');
        formData.append('status', statusMap[sel ? sel.value : 'actif'] || 'active');
    }
    if (fields.record_duration !== undefined) formData.append('record_duration', String(data.recordDuration || 3));
    fetch('/postes/update', { method: 'POST', body: formData }).then(function (r) { return r.json(); }).catch(function () { });
}

function updatePosteStatus(value) {
    if (!currentPosteId || !postesData[currentPosteId]) return;
    var select = document.getElementById('detail-poste-status-select');
    applyPosteStatusStyle(select);
    var labels = { actif: 'Actif', inactif: 'Non actif', archive: 'Archivé' };
    var classes = { actif: 'status-active', inactif: 'status-paused', archive: 'status-closed' };
    postesData[currentPosteId].status = labels[value] || 'Actif';
    postesData[currentPosteId].statusClass = classes[value] || 'status-active';
    savePosteToServer({ status: true });
}

/* ─── Questions CRUD ─── */

function renderPosteQuestions() {
    var data = postesData[currentPosteId];
    if (!data) return;
    if (!data.questions) data.questions = [];

    var container = document.getElementById('detail-poste-questions-list');
    var countEl = document.getElementById('detail-poste-questions-count');
    var len = data.questions.length;
    if (countEl) countEl.textContent = len + ' question' + (len !== 1 ? 's' : '');
    if (!container) return;

    container.innerHTML = '';
    if (len === 0) {
        container.innerHTML = '<div class="text-center subtitle-muted" style="padding:1.5rem 0;">Aucune question définie. Ajoutez-en ci-dessous.</div>';
        return;
    }

    data.questions.forEach(function (q, i) {
        var div = document.createElement('div');
        div.className = 'question-item';
        div.setAttribute('data-index', i);
        div.setAttribute('draggable', 'true');
        div.innerHTML =
            '<span class="question-drag-handle" title="Glisser pour réordonner"><i class="fa-solid fa-grip-vertical"></i></span>' +
            '<span class="question-number">' + (i + 1) + '</span>' +
            '<span class="question-text">' + escapeHtml(q) + '</span>' +
            '<div class="question-actions">' +
            '<button class="btn-icon" title="Monter" onclick="movePosteQuestion(' + i + ',-1)"' + (i === 0 ? ' disabled style="opacity:0.3;pointer-events:none;"' : '') + '><i class="fa-solid fa-chevron-up"></i></button>' +
            '<button class="btn-icon" title="Descendre" onclick="movePosteQuestion(' + i + ',1)"' + (i === len - 1 ? ' disabled style="opacity:0.3;pointer-events:none;"' : '') + '><i class="fa-solid fa-chevron-down"></i></button>' +
            '<button class="btn-icon" title="Modifier" onclick="editPosteQuestion(' + i + ')"><i class="fa-solid fa-pen"></i></button>' +
            '<button class="btn-icon btn-icon--danger" title="Supprimer" onclick="deletePosteQuestion(' + i + ')"><i class="fa-solid fa-trash"></i></button>' +
            '</div>';

        // Drag & Drop
        div.addEventListener('dragstart', function (e) {
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', i);
            div.classList.add('question-item--dragging');
        });
        div.addEventListener('dragend', function () {
            div.classList.remove('question-item--dragging');
            container.querySelectorAll('.question-item--over').forEach(function (el) { el.classList.remove('question-item--over'); });
        });
        div.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            div.classList.add('question-item--over');
        });
        div.addEventListener('dragleave', function () {
            div.classList.remove('question-item--over');
        });
        div.addEventListener('drop', function (e) {
            e.preventDefault();
            var fromIndex = parseInt(e.dataTransfer.getData('text/plain'), 10);
            var toIndex = i;
            if (fromIndex !== toIndex) {
                var questions = postesData[currentPosteId].questions;
                var item = questions.splice(fromIndex, 1)[0];
                questions.splice(toIndex, 0, item);
                renderPosteQuestions();
                savePosteToServer({ questions: true });
            }
        });

        container.appendChild(div);
    });
}

function addPosteQuestion() {
    var input = document.getElementById('detail-poste-new-question');
    var text = input.value.trim();
    if (!text || !currentPosteId) return;

    postesData[currentPosteId].questions.push(text);
    input.value = '';
    renderPosteQuestions();
    savePosteToServer({ questions: true });
}

function addPosteQuestionFromChip(text) {
    if (!text || !currentPosteId) return;
    postesData[currentPosteId].questions.push(text);
    renderPosteQuestions();
    savePosteToServer({ questions: true });
}

// Clic sur une question proposée (chips)
document.addEventListener('click', function (e) {
    var chip = e.target && e.target.closest && e.target.closest('.question-chip[data-question]');
    if (chip && currentPosteId) {
        var q = chip.getAttribute('data-question');
        if (q) addPosteQuestionFromChip(q);
    }
});

function editPosteQuestion(index) {
    var data = postesData[currentPosteId];
    if (!data) return;

    var container = document.getElementById('detail-poste-questions-list');
    var item = container.querySelector('[data-index="' + index + '"]');
    if (!item) return;

    var currentText = data.questions[index];
    item.innerHTML =
        '<span class="question-number">' + (index + 1) + '</span>' +
        '<input type="text" class="question-edit-input" value="' + escapeHtml(currentText).replace(/"/g, '&quot;') + '" ' +
        'onkeydown="if(event.key===\'Enter\'){savePosteQuestion(' + index + ')} if(event.key===\'Escape\'){renderPosteQuestions()}">' +
        '<div class="question-actions">' +
        '<button class="btn-icon btn-icon--success" title="Enregistrer" onclick="savePosteQuestion(' + index + ')"><i class="fa-solid fa-check"></i></button>' +
        '<button class="btn-icon" title="Annuler" onclick="renderPosteQuestions()"><i class="fa-solid fa-xmark"></i></button>' +
        '</div>';

    var editInput = item.querySelector('.question-edit-input');
    editInput.focus();
    editInput.setSelectionRange(editInput.value.length, editInput.value.length);
}

function savePosteQuestion(index) {
    var container = document.getElementById('detail-poste-questions-list');
    var item = container.querySelector('[data-index="' + index + '"]');
    if (!item) return;

    var input = item.querySelector('.question-edit-input');
    var text = input.value.trim();
    if (!text) return;

    postesData[currentPosteId].questions[index] = text;
    renderPosteQuestions();
    savePosteToServer({ questions: true });
}

function deletePosteQuestion(index) {
    postesData[currentPosteId].questions.splice(index, 1);
    renderPosteQuestions();
    savePosteToServer({ questions: true });
}

function updatePosteRecordDuration(value) {
    if (!currentPosteId || !postesData[currentPosteId]) return;
    postesData[currentPosteId].recordDuration = parseInt(value, 10);
    savePosteToServer({ record_duration: true });
}

function movePosteQuestion(index, direction) {
    var questions = postesData[currentPosteId].questions;
    var newIndex = index + direction;
    if (newIndex < 0 || newIndex >= questions.length) return;
    var item = questions.splice(index, 1)[0];
    questions.splice(newIndex, 0, item);
    renderPosteQuestions();
    savePosteToServer({ questions: true });
}

/* ─── Modal Candidats du poste ─── */

function openPosteCandidatsModal() {
    if (!currentPosteId) return;
    var data = postesData[currentPosteId];
    if (!data) return;

    var title = data.title;
    document.getElementById('poste-candidats-modal-title').textContent = 'Candidats — ' + title;

    // Collecter tous les candidats dont le role correspond au titre du poste
    var matches = [];
    // Chercher dans candidatsData (vue globale)
    for (var key in candidatsData) {
        if (candidatsData[key].role === title) {
            matches.push(candidatsData[key]);
        }
    }
    // Chercher aussi dans affichageCandidats
    for (var affId in affichageCandidats) {
        var list = affichageCandidats[affId] || [];
        list.forEach(function (c) {
            // Vérifier que le candidat n'est pas déjà dans matches
            var alreadyIn = matches.some(function (m) { return m.id === c.id; });
            if (!alreadyIn && affId.indexOf(currentPosteId) === 0) {
                matches.push(c);
            }
        });
    }

    var listEl = document.getElementById('poste-candidats-modal-list');
    var emptyEl = document.getElementById('poste-candidats-modal-empty');

    if (matches.length === 0) {
        listEl.innerHTML = '';
        listEl.classList.add('hidden');
        emptyEl.classList.remove('hidden');
    } else {
        emptyEl.classList.add('hidden');
        listEl.classList.remove('hidden');
        listEl.innerHTML = '';

        matches.forEach(function (c) {
            var name = c.name || '';
            var email = c.email || '';
            var color = c.color || '64748B';
            var rating = c.rating || c.stars || 0;
            var status = '';
            if (c.status === 'new' || c.status === 'Nouveau') status = '<span class="status-badge status-new">Nouveau</span>';
            else if (c.status === 'reviewed' || c.status === 'Évalué') status = '<span class="status-badge status-active">Accepté</span>';
            else if (c.status === 'shortlisted' || c.status === 'Favori' || c.status === 'Banque') status = '<span class="status-badge status-shortlisted">Banque</span>';
            else if (c.status === 'rejected' || c.status === 'Refusé') status = '<span class="status-badge status-rejected">Refusé</span>';
            else status = '<span class="status-badge">' + escapeHtml(c.status || '') + '</span>';

            var stars = '';
            for (var i = 1; i <= 5; i++) {
                stars += '<i class="fa-' + (i <= rating ? 'solid' : 'regular') + ' fa-star"></i>';
            }

            var row = document.createElement('div');
            row.className = 'poste-candidat-row';
            row.onclick = function () {
                closeModal('poste-candidats');
                if (typeof showCandidateDetail === 'function' && candidatsData[c.id]) {
                    showCandidateDetail(c.id);
                }
            };
            row.innerHTML =
                '<img src="https://ui-avatars.com/api/?name=' + encodeURIComponent(name) + '&background=' + escapeHtml(color) + '&color=fff" class="avatar" alt="">' +
                '<div class="poste-candidat-info">' +
                '<div class="poste-candidat-name">' + escapeHtml(name) + '</div>' +
                '<div class="poste-candidat-email">' + escapeHtml(email) + '</div>' +
                '</div>' +
                '<div class="star-color">' + stars + '</div>' +
                status;
            listEl.appendChild(row);
        });
    }

    openModal('poste-candidats');
}

/* ═══════════════════════════════════════════════
   AFFICHAGE DETAIL / CANDIDATS PAR AFFICHAGE
   ═══════════════════════════════════════════════ */
var _affichageCandidatsFilter = 'all';

function candidateMatchesFilter(c, filter) {
    if (filter === 'all') return true;
    var s = (c.status || '').toLowerCase();
    if (filter === 'new') return s === 'new' || s.includes('nouveau');
    if (filter === 'reviewed') return s === 'reviewed' || s.includes('accept') || s === 'shortlisted' || s.includes('favori') || s.includes('banque') || s.includes('évalué');
    if (filter === 'rejected') return s === 'rejected' || s.includes('refus');
    return true;
}

function renderAffichageCandidatsTable(id, filter) {
    filter = filter || _affichageCandidatsFilter;
    _affichageCandidatsFilter = filter;
    var candidates = affichageCandidats[id] || [];
    var filtered = candidates.filter(function (c) { return candidateMatchesFilter(c, filter); });
    var tbody = document.getElementById('affichage-candidats-tbody');
    if (!tbody) return;
    tbody.innerHTML = '';

    filtered.forEach(function (c) {
        var row = document.createElement('tr');
        row.style.cursor = 'pointer';
        row.onclick = function () { if (typeof showCandidateDetail === 'function') showCandidateDetail(c.id); };
        var statusLabel = statusToLabel(c.status);

        var stars = '';
        var rating = c.rating || c.stars || 0;
        for (var i = 1; i <= 5; i++) {
            stars += '<i class="fa-' + (i <= rating ? 'solid' : 'regular') + ' fa-star"></i>';
        }

        var commCell = '';
        if (c.lastCommunication && c.lastCommunication.message) {
            commCell = '<td class="cell-communication cell-communication--sent" onclick="event.stopPropagation();showCommunicationModalFromCandidate(\'' + escapeHtml(String(c.id)) + '\')" title="Voir le message envoyé"><i class="fa-solid fa-envelope-circle-check" style="color:var(--success-color);"></i></td>';
        } else {
            commCell = '<td class="cell-communication" title="Aucune communication envoyée">—</td>';
        }
        row.innerHTML =
            '<td><div style="display: flex; align-items: center; gap: 0.75rem;">' +
            '<img src="https://ui-avatars.com/api/?name=' + encodeURIComponent(c.name) + '&background=' + escapeHtml(c.color) + '&color=fff" class="avatar" alt="">' +
            '<div><strong>' + escapeHtml(c.name) + '</strong><div class="subtitle-muted">' + escapeHtml(c.email) + '</div></div>' +
            '</div></td>' +
            '<td><span class="status-badge" style="background:' + (c.statusBg || '#DBEAFE') + '; color:' + (c.statusColor || '#1D4ED8') + ';">' + escapeHtml(statusLabel) + '</span></td>' +
            '<td><div class="star-color">' + stars + '</div></td>' +
            '<td>' + (c.isFavorite ? '<i class="fa-solid fa-heart" style="color: #EC4899;"></i>' : '<i class="fa-regular fa-heart" style="color: #D1D5DB;"></i>') + '</td>' +
            '<td>' + escapeHtml(formatUtcToLocal(c.date)) + '</td>' +
            commCell;
        tbody.appendChild(row);
    });

    if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 2rem; color: #94A3B8;">' + (candidates.length === 0 ? 'Aucun candidat pour cet affichage.' : 'Aucun candidat pour ce filtre.') + '</td></tr>';
    }

    // Mettre à jour l'onglet actif
    document.querySelectorAll('#affichage-candidats-filter-tabs .view-tab').forEach(function (tab) {
        tab.classList.toggle('active', tab.getAttribute('data-filter') === filter);
    });
}

function showAffichageDetail(id) {
    var data = affichagesData[id];
    if (!data) return;
    window._currentAffichageId = id;
    _affichageCandidatsFilter = 'all';

    document.getElementById('affichage-candidats-title').textContent = data.title;
    document.getElementById('affichage-candidats-subtitle').textContent = data.start;

    var shareUrlEl = document.getElementById('affichage-share-url');
    if (shareUrlEl && data.shareLongId) {
        var baseUrl = (typeof APP_DATA !== 'undefined' && APP_DATA.appUrl) ? APP_DATA.appUrl : 'https://app.ciaocv.com';
        var url = baseUrl + '/entrevue/' + data.shareLongId;
        shareUrlEl.href = url;
        shareUrlEl.textContent = url;
    }

    // Status select (absent pour les évaluateurs) — libellés dans la langue courante
    var statusSelect = document.getElementById('affichage-status-select');
    if (statusSelect) {
        var lang = typeof getLanguage === 'function' ? getLanguage() : 'fr';
        var t = (typeof translations !== 'undefined' && translations[lang]) ? translations[lang] : {};
        for (var o = 0; o < statusSelect.options.length; o++) {
            var opt = statusSelect.options[o];
            var key = opt.getAttribute('data-i18n');
            if (key && t[key]) opt.textContent = t[key];
        }
        // Valeur du select depuis la BDD (statusClass reflète le statut en base)
        var statusClass = data.statusClass || 'status-active';
        if (statusClass === 'status-closed') statusSelect.value = 'archive';
        else if (statusClass === 'status-paused' || statusClass === 'status-expired') statusSelect.value = 'termine';
        else statusSelect.value = 'actif';
        applyStatusSelectStyle(statusSelect);
    }

    // Alerte terminé + masquer la carte Évaluateurs si statut Terminé
    var alert = document.getElementById('affichage-termine-alert');
    var evaluatorsCard = document.getElementById('affichage-evaluateurs-card');
    if (statusSelect) {
        var statusVal = statusSelect.value;
        if (statusVal === 'termine') {
            if (alert) alert.classList.remove('hidden');
            if (evaluatorsCard) evaluatorsCard.style.display = 'none';
        } else {
            if (alert) alert.classList.add('hidden');
            if (evaluatorsCard) evaluatorsCard.style.display = '';
        }
    }

    renderAffichageCandidatsTable(id, 'all');

    // Render evaluateurs
    renderEvaluateurs();

    document.querySelectorAll('.content-section').forEach(function (s) { s.classList.remove('active'); });
    document.getElementById('affichage-candidats-section').classList.add('active');
    window.scrollTo(0, 0);
}

function goBackToAffichages() {
    document.querySelectorAll('.content-section').forEach(function (s) { s.classList.remove('active'); });
    document.getElementById('affichages-section').classList.add('active');
}

var _pendingDeleteAffichage = null;

function deleteAffichage(id, rowEl) {
    var data = affichagesData[id];
    var title = (data && data.title) ? data.title : '';
    var msg = title
        ? 'Êtes-vous sûr de vouloir supprimer l\'affichage « ' + escapeHtml(title) + ' » ?'
        : 'Êtes-vous sûr de vouloir supprimer cet affichage ?';
    _pendingDeleteAffichage = { id: id, rowEl: rowEl };
    var msgEl = document.getElementById('delete-affichage-message');
    if (msgEl) msgEl.textContent = msg;
    openModal('delete-affichage');
}

function confirmDeleteAffichage() {
    if (!_pendingDeleteAffichage) return;
    var id = _pendingDeleteAffichage.id;
    var rowEl = _pendingDeleteAffichage.rowEl;
    _pendingDeleteAffichage = null;
    closeModal('delete-affichage');

    var btn = document.querySelector('#delete-affichage-modal .btn-danger'); // Not reliable as modal is closed, but maybe for next time?
    // Actually, better to just call the API
    var formData = new FormData();
    formData.append('_csrf_token', (document.querySelector('input[name="_csrf_token"]') || {}).value || '');
    formData.append('id', String(id));

    fetch('/affichages/delete', { method: 'POST', body: formData })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.success) {
                delete affichagesData[id];
                if (rowEl && rowEl.parentNode) rowEl.remove();
            } else {
                alert('Erreur: ' + (res.error || 'Impossible de supprimer'));
            }
        })
        .catch(function () {
            // On errors, keep UI consistent or alert user
            alert('Erreur réseau lors de la suppression');
        });
}

function saveAffichageFromModal(e) {
    e.preventDefault();
    var form = document.getElementById('form-affichage-create');
    if (!form) return false;
    var posteSelect = document.getElementById('affichage-poste_id');
    if (!posteSelect || !posteSelect.value) {
        alert('Veuillez sélectionner un poste');
        return false;
    }
    var submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Enregistrement...'; }
    var formData = new FormData(form);
    fetch('/affichages', { method: 'POST', body: formData })
        .then(function (r) {
            var ct = r.headers.get('Content-Type') || '';
            if (!ct.includes('application/json')) {
                return r.text().then(function (t) {
                    console.error('createAffichage: reponse non-JSON', r.status, t ? t.substring(0, 500) : '');
                    throw new Error('Erreur serveur (status ' + r.status + '). Voir console.');
                });
            }
            return r.json();
        })
        .then(function (res) {
            if (res.success && res.affichage) {
                var a = res.affichage;
                affichagesData[a.id] = a;
                var tbody = document.querySelector('#affichages-table tbody');
                if (tbody) {
                    var tr = document.createElement('tr');
                    tr.className = 'row-clickable';
                    tr.setAttribute('data-affichage-id', a.id);
                    tr.onclick = function () { showAffichageDetail(a.id); };
                    (function () {
                        var statusLabel = a.status || 'Actif';
                        if (typeof getLanguage === 'function' && typeof translations !== 'undefined') {
                            var dict = translations[getLanguage()] || {};
                            if (a.statusClass === 'status-active') statusLabel = dict.status_active || statusLabel;
                            else if (a.statusClass === 'status-closed') statusLabel = dict.status_archived || statusLabel;
                            else if (a.statusClass === 'status-paused' || a.statusClass === 'status-expired') statusLabel = dict.status_termine || statusLabel;
                        }
                        tr.innerHTML = '<td><strong>' + escapeHtml(a.title || '') + '</strong></td><td>' + escapeHtml(a.department || '') + '</td><td><span class="status-badge ' + escapeHtml(a.statusClass || 'status-active') + '">' + escapeHtml(statusLabel) + '</span></td><td><span class="badge-count">0/0</span></td><td class="cell-actions"><button type="button" class="btn-icon btn-icon-edit" onclick="event.stopPropagation(); showAffichageDetail(\'' + escapeHtml(String(a.id)) + '\')" title="Modifier"><i class="fa-solid fa-pen"></i></button><button type="button" class="btn-icon btn-icon-delete" onclick="event.stopPropagation(); deleteAffichage(\'' + escapeHtml(String(a.id)) + '\', this.closest(\'tr\'))" title="Supprimer"><i class="fa-solid fa-trash"></i></button></td>';
                    })();
                    tbody.insertBefore(tr, tbody.firstChild);
                }
                closeModal('affichage');
                form.reset();
            } else {
                console.error('createAffichage: erreur', res);
                alert(res.error || 'Erreur lors de l\'enregistrement');
            }
        })
        .catch(function (err) {
            console.error('createAffichage: exception', err);
            alert('Erreur : ' + (err.message || 'Veuillez réessayer'));
        })
        .finally(function () {
            if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Enregistrer'; }
        });
    return false;
}

function savePosteFromModal(e) {
    e.preventDefault();
    var form = document.getElementById('form-poste-create');
    if (!form) return false;
    var submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Enregistrement...'; }
    var formData = new FormData(form);
    fetch('/postes', { method: 'POST', body: formData })
        .then(function (r) {
            var ct = r.headers.get('Content-Type') || '';
            if (!ct.includes('application/json')) {
                return r.text().then(function (t) {
                    console.error('createPoste: reponse non-JSON', r.status, t ? t.substring(0, 500) : '');
                    throw new Error('Erreur serveur (status ' + r.status + '). Voir console.');
                });
            }
            return r.json();
        })
        .then(function (res) {
            if (res.success && res.poste) {
                var p = res.poste;
                postesData[p.id] = p;
                var tbody = document.querySelector('#postes-table tbody');
                if (tbody) {
                    var tr = document.createElement('tr');
                    tr.className = 'row-clickable';
                    tr.setAttribute('data-poste-id', p.id);
                    tr.setAttribute('role', 'button');
                    tr.setAttribute('tabindex', '0');
                    tr.onclick = function () { showPosteDetail(p.id); };
                    tr.innerHTML = '<td><strong>' + escapeHtml(p.title || '') + '</strong></td><td>' + escapeHtml(p.department || '') + '</td><td>' + escapeHtml(p.location || '') + '</td><td><span class="status-badge ' + escapeHtml(p.statusClass || 'status-active') + '">' + escapeHtml(p.status || 'Actif') + '</span></td><td class="cell-candidates">' + (p.candidates || 0) + '</td><td class="cell-actions"><button type="button" class="btn-icon btn-icon-edit" onclick="event.stopPropagation(); showPosteDetail(\'' + p.id + '\')" title="Modifier"><i class="fa-solid fa-pen"></i></button><button type="button" class="btn-icon btn-icon-delete" onclick="event.stopPropagation(); deletePoste(\'' + p.id + '\', this.closest(\'tr\'))" title="Supprimer"><i class="fa-solid fa-trash"></i></button></td>';
                    tbody.insertBefore(tr, tbody.firstChild);
                }
                closeModal('poste');
                form.reset();
                if (typeof showPosteDetail === 'function') showPosteDetail(p.id);
            } else {
                console.error('createPoste: erreur', res);
                alert(res.error || 'Erreur lors de l\'enregistrement');
            }
        })
        .catch(function (err) {
            console.error('createPoste: exception', err);
            alert('Erreur : ' + (err.message || 'Veuillez réessayer'));
        })
        .finally(function () {
            if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Enregistrer'; }
        });
    return false;
}

var _pendingDeletePoste = null;

function deletePoste(id, rowEl) {
    var data = postesData[id] || postesData[String(id)];
    var title = (data && data.title) ? data.title : '';
    var msg = title
        ? 'Êtes-vous sûr de vouloir supprimer le poste « ' + escapeHtml(title) + ' » ?'
        : 'Êtes-vous sûr de vouloir supprimer ce poste ?';
    _pendingDeletePoste = { id: id, rowEl: rowEl };
    var msgEl = document.getElementById('delete-poste-message');
    if (msgEl) msgEl.textContent = msg;
    openModal('delete-poste');
}

function confirmDeletePoste() {
    if (!_pendingDeletePoste) return;
    var id = _pendingDeletePoste.id;
    var rowEl = _pendingDeletePoste.rowEl;
    _pendingDeletePoste = null;
    closeModal('delete-poste');
    var btn = document.querySelector('#delete-poste-modal .btn-danger');
    if (btn) { btn.disabled = true; btn.textContent = 'Suppression...'; }
    var formData = new FormData();
    formData.append('_csrf_token', (document.querySelector('input[name="_csrf_token"]') || {}).value || '');
    formData.append('id', String(id));
    fetch('/postes/delete', { method: 'POST', body: formData })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.success) {
                delete postesData[id];
                delete postesData[String(id)];
                if (!rowEl || !rowEl.parentNode) rowEl = document.querySelector('#postes-table tr[data-poste-id="' + String(id).replace(/"/g, '&quot;') + '"]');
                if (rowEl && rowEl.parentNode) rowEl.remove();
                if (currentPosteId === String(id) || currentPosteId === id) goBackToPostes();
            }
        })
        .catch(function () { })
        .finally(function () {
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-trash"></i> <span data-i18n="action_delete">Supprimer</span>'; }
        });
}

/* ═══════════════════════════════════════════════
   CANDIDAT DETAIL
   ═══════════════════════════════════════════════ */
var currentCandidateId = null;
var currentCandidateSource = null; // 'all' (default) or 'affichage'

function showCandidateDetail(id, source) {
    currentCandidateId = id;

    // Determine source if not provided, based on active section
    if (source) {
        currentCandidateSource = source;
    } else {
        // If we are currently in the affichage-candidats-section, source is affichage
        if (document.getElementById('affichage-candidats-section').classList.contains('active')) {
            currentCandidateSource = 'affichage';
        } else {
            currentCandidateSource = 'all';
        }
    }

    var data = null;
    // Quand on vient d'un affichage, prioriser l'objet de affichageCandidats (même référence pour addComment)
    if (currentCandidateSource === 'affichage') {
        for (var affId in affichageCandidats) {
            var list = affichageCandidats[affId];
            if (!Array.isArray(list)) continue;
            var found = list.find(function (c) { return String(c.id) === String(id); });
            if (found) {
                data = found;
                break;
            }
        }
    }
    if (!data) data = candidatsData[id] || candidatsData[String(id)];
    if (!data) {
        for (var affId in affichageCandidats) {
            var list = affichageCandidats[affId];
            if (!Array.isArray(list)) continue;
            var found = list.find(function (c) { return String(c.id) === String(id); });
            if (found) {
                data = found;
                break;
            }
        }
    }
    if (!data) return;
    if (!Array.isArray(data.comments)) data.comments = [];
    candidatsData[id] = data;

    document.getElementById('detail-candidate-name').textContent = data.name;
    document.getElementById('detail-candidate-role-source').textContent = (data.role || 'Candidat');
    document.getElementById('detail-candidate-email').textContent = data.email || '—';
    document.getElementById('detail-candidate-phone').textContent = data.phone || '—';

    var cvLink = document.getElementById('detail-candidate-cv-link');
    var cvMissing = document.getElementById('detail-candidate-cv-missing');
    if (cvLink && cvMissing) {
        if (data.cv) {
            cvLink.href = data.cv;
            cvLink.classList.remove('hidden');
            cvMissing.classList.add('hidden');
        } else {
            cvLink.classList.add('hidden');
            cvMissing.classList.remove('hidden');
        }
    }

    var ss = document.getElementById('detail-candidate-status-select');
    if (ss) {
        // Map status localized to value
        var rawStatus = (data.status || 'new').toLowerCase();
        var val = 'shortlisted'; // Banque par défaut pour "new"
        if (rawStatus.includes('accept') || rawStatus === 'reviewed') val = 'reviewed';
        else if (rawStatus.includes('refus') || rawStatus === 'rejected') val = 'rejected';
        else if (rawStatus.includes('banque') || rawStatus.includes('favori') || rawStatus === 'shortlisted') val = 'shortlisted';

        ss.value = val;
        ss.className = 'status-select status-select--candidate status-' + val;
    }

    var favBtn = document.getElementById('detail-candidate-favorite');
    var isFav = data.isFavorite || false;
    // Check if status implies favorite
    if (data.status === 'Favori' || data.status === 'shortlisted') isFav = true;

    if (isFav) {
        favBtn.innerHTML = '<i class="fa-solid fa-heart"></i>';
        favBtn.classList.add('active');
    } else {
        favBtn.innerHTML = '<i class="fa-regular fa-heart"></i>';
        favBtn.classList.remove('active');
    }

    // Populate new recording details
    document.getElementById('detail-candidate-date').textContent = formatUtcToLocal(data.date) || '—';
    document.getElementById('detail-candidate-retakes').textContent = data.retakes || '0';

    // Format duration
    var timeSpent = parseInt(data.timeSpent || 0, 10);
    var tm = Math.floor(timeSpent / 60);
    var ts = timeSpent % 60;
    document.getElementById('detail-candidate-time-spent').textContent = tm + 'm ' + (ts < 10 ? '0' + ts : ts) + 's';

    // Render stars (cliquables)
    var starsContainer = document.getElementById('detail-candidate-rating-stars');
    if (starsContainer) {
        var stars = parseInt(data.stars || 0, 10);
        if (!data.stars && data.rating) stars = data.rating;
        starsContainer.innerHTML = '';
        starsContainer.className = 'star-rating star-rating--header star-rating--clickable';
        for (var i = 1; i <= 5; i++) {
            var star = document.createElement('i');
            star.className = (i <= stars ? 'fa-solid' : 'fa-regular') + ' fa-star ' + (i <= stars ? 'text-warning' : 'text-muted-light');
            star.setAttribute('data-rating', String(i));
            star.setAttribute('role', 'button');
            star.setAttribute('tabindex', '0');
            star.setAttribute('title', i + ' / 5');
            star.onclick = function () { saveRating(parseInt(this.getAttribute('data-rating'), 10)); };
            star.onkeydown = function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); saveRating(parseInt(this.getAttribute('data-rating'), 10)); } };
            starsContainer.appendChild(star);
        }
    }

    var vp = document.getElementById('detail-candidate-video-player');
    var ph = document.getElementById('detail-video-placeholder');
    var speedControls = document.getElementById('video-speed-controls');

    if (data.video) {
        vp.style.display = 'block';
        ph.style.display = 'none';
        vp.src = data.video;
        if (speedControls) speedControls.classList.remove('hidden');
        // Reset speed to 1x
        vp.playbackRate = 1.0;
        if (speedControls) {
            speedControls.querySelectorAll('.speed-btn').forEach(function (btn) {
                btn.classList.remove('active');
                if (btn.textContent === '1x') btn.classList.add('active');
            });
        }
    } else {
        vp.style.display = 'none';
        ph.style.display = 'block';
        if (speedControls) speedControls.classList.add('hidden');
    }

    renderTimeline(data.comments || []);
    updateCommentFormUser();

    document.querySelectorAll('.content-section').forEach(function (s) { s.classList.remove('active'); });
    document.getElementById('candidate-detail-section').classList.add('active');
    window.scrollTo(0, 0);
}

function getCurrentCandidateData() {
    if (!currentCandidateId) return null;
    var id = String(currentCandidateId);
    var data = candidatsData[currentCandidateId] || candidatsData[id];
    if (data) return data;
    for (var affId in affichageCandidats) {
        var list = affichageCandidats[affId];
        if (!Array.isArray(list)) continue;
        var found = list.find(function (c) { return String(c.id) === id; });
        if (found) return found;
    }
    return null;
}

function syncCandidateUpdatesToAllSources(candidateId, updates) {
    function apply(o) {
        if (!o) return;
        if (updates.status !== undefined) o.status = updates.status;
        if (updates.isFavorite !== undefined) o.isFavorite = updates.isFavorite;
        if (updates.rating !== undefined) { o.rating = updates.rating; o.stars = updates.rating; }
    }
    var data = candidatsData[candidateId] || candidatsData[String(candidateId)];
    if (data) apply(data);
    for (var affId in affichageCandidats) {
        var found = affichageCandidats[affId].find(function (c) { return String(c.id) === String(candidateId); });
        if (found) apply(found);
    }
}

function saveCandidateState(updates) {
    if (!currentCandidateId) return;

    var params = new URLSearchParams();
    params.append('_csrf_token', (document.querySelector('input[name="_csrf_token"]') || {}).value || '');
    params.append('id', currentCandidateId);

    if (updates.status !== undefined) params.append('status', updates.status);
    if (updates.isFavorite !== undefined) params.append('is_favorite', updates.isFavorite ? '1' : '0');
    if (updates.rating !== undefined) params.append('rating', String(updates.rating));

    // Optimistic UI update : sync dans candidatsData ET affichageCandidats (objets distincts en JS)
    syncCandidateUpdatesToAllSources(currentCandidateId, updates);

    fetch('/candidats/update', { method: 'POST', body: params })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (!res.success) console.error('Error saving candidate:', res.error);
        })
        .catch(function (e) { console.error('Network error saving candidate:', e); });
}

function toggleFavorite() {
    if (!currentCandidateId) return;
    var data = candidatsData[currentCandidateId]; // Note: this might need to find deeper if strictly using objects
    // If not found in global, find in sub-lists, but for now assuming global sync.
    // Actually we should handle the case where it's only in an Affichage list.

    var isFav = document.getElementById('detail-candidate-favorite').classList.contains('active');
    var newState = !isFav;

    var favBtn = document.getElementById('detail-candidate-favorite');
    favBtn.innerHTML = newState ? '<i class="fa-solid fa-heart"></i>' : '<i class="fa-regular fa-heart"></i>';
    favBtn.classList.toggle('active', newState);

    saveCandidateState({ isFavorite: newState });
}

function saveRating(rating) {
    if (!currentCandidateId || rating < 1 || rating > 5) return;
    var starsContainer = document.getElementById('detail-candidate-rating-stars');
    if (starsContainer) {
        var stars = starsContainer.querySelectorAll('i');
        stars.forEach(function (s, idx) {
            var val = idx + 1;
            s.className = (val <= rating ? 'fa-solid' : 'fa-regular') + ' fa-star ' + (val <= rating ? 'text-warning' : 'text-muted-light');
        });
    }
    saveCandidateState({ rating: rating });
}

function updateCandidateStatus(newStatus) {
    if (!currentCandidateId) return;

    var ss = document.getElementById('detail-candidate-status-select');
    if (ss) ss.className = 'status-select status-select--candidate status-' + newStatus;

    // Map value back to label if needed, or just send code
    saveCandidateState({ status: newStatus });
}

function goBackToCandidates() {
    document.querySelectorAll('.content-section').forEach(function (s) { s.classList.remove('active'); });

    if (currentCandidateSource === 'affichage' && window._currentAffichageId) {
        renderAffichageCandidatsTable(window._currentAffichageId);
        document.getElementById('affichage-candidats-section').classList.add('active');
    } else {
        document.getElementById('candidats-section').classList.add('active');
    }
}


var COMMENT_AVATAR_COLORS = ['#2563EB', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#06B6D4', '#EF4444'];

function commentAvatarColor(userName) {
    if (!userName) return COMMENT_AVATAR_COLORS[0];
    var h = 0;
    for (var i = 0; i < userName.length; i++) h = ((h << 5) - h) + userName.charCodeAt(i);
    return COMMENT_AVATAR_COLORS[Math.abs(h) % COMMENT_AVATAR_COLORS.length];
}

function renderTimeline(comments) {
    var container = document.getElementById('detail-timeline-list');
    if (!container) return;
    container.innerHTML = '';
    if (!Array.isArray(comments)) comments = [];

    if (comments.length === 0) {
        var empty = document.createElement('div');
        empty.className = 'comments-empty';
        empty.innerHTML = '<i class="fa-regular fa-comments comments-empty-icon"></i>' +
            '<span class="comments-empty-title" data-i18n="no_comments">Aucun commentaire</span>' +
            '<span class="comments-empty-desc" data-i18n="comments_empty_desc">Soyez le premier à partager votre avis avec l\'équipe</span>';
        container.appendChild(empty);
        return;
    }

    comments.forEach(function (c) {
        var userName = c.user || 'Utilisateur';
        var initial = userName.charAt(0).toUpperCase();
        var color = commentAvatarColor(userName);

        var d = formatUtcToLocal(c.date, null, { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
        if (!d || d === '—') d = c.date || '';

        var item = document.createElement('div');
        item.className = 'comment-item';
        item.innerHTML =
            '<div class="comment-avatar" style="background:' + color + ';">' + escapeHtml(initial) + '</div>' +
            '<div class="comment-body">' +
            '<div class="comment-meta">' +
            '<span class="comment-author">' + escapeHtml(userName) + '</span>' +
            '<span class="comment-date">' + escapeHtml(d) + '</span>' +
            '</div>' +
            '<div class="comment-text">' + escapeHtml(c.text) + '</div>' +
            '</div>';
        container.appendChild(item);
    });
}

function updateCommentFormUser() {
    var av = document.getElementById('comment-current-user-avatar');
    if (!av) return;
    var name = (typeof APP_DATA !== 'undefined' && APP_DATA.currentUser) ? APP_DATA.currentUser : 'Utilisateur';
    av.textContent = name.charAt(0).toUpperCase();
    av.style.background = commentAvatarColor(name);
}

function addComment() {
    var input = document.getElementById('detail-new-comment-input');
    if (!input) return;
    var text = input.value.trim();
    if (!text || !currentCandidateId) return;
    var data = getCurrentCandidateData();
    if (!data) return;
    var params = new URLSearchParams();
    params.append('_csrf_token', (document.querySelector('input[name="_csrf_token"]') || {}).value || '');
    params.append('id', currentCandidateId);
    params.append('text', text);
    input.value = '';
    fetch('/candidats/comment', { method: 'POST', body: params })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (!res.success) {
                console.error('Erreur ajout commentaire:', res.error);
                input.value = text;
                return;
            }
            var newComment = res.comment || { user: (typeof APP_DATA !== 'undefined' && APP_DATA.currentUser) ? APP_DATA.currentUser : 'Moi', date: new Date().toISOString(), text: text };
            if (!Array.isArray(data.comments)) data.comments = [];
            data.comments.unshift(newComment);
            for (var affId in affichageCandidats) {
                var list = affichageCandidats[affId];
                if (!Array.isArray(list)) continue;
                var found = list.find(function (c) { return String(c.id) === String(currentCandidateId); });
                if (found && found !== data) {
                    if (!Array.isArray(found.comments)) found.comments = [];
                    found.comments.unshift(newComment);
                    break;
                }
            }
            renderTimeline(data.comments);
        })
        .catch(function (e) {
            console.error('Erreur réseau:', e);
            input.value = text;
        });
}

/* ═══════════════════════════════════════════════
   STATUT AFFICHAGE
   ═══════════════════════════════════════════════ */
function applyStatusSelectStyle(select) {
    select.className = 'status-select';
    if (select.value === 'actif') select.classList.add('status-select--actif');
    else if (select.value === 'termine') select.classList.add('status-select--termine');
    else if (select.value === 'archive') select.classList.add('status-select--archive');
}

function updateAffichageStatus(value) {
    var id = window._currentAffichageId;
    if (!id || !affichagesData[id]) return;

    var select = document.getElementById('affichage-status-select');
    var previousValue = affichagesData[id].statusClass === 'status-closed' ? 'archive' : (affichagesData[id].statusClass === 'status-paused' ? 'termine' : 'actif');
    applyStatusSelectStyle(select);

    var alert = document.getElementById('affichage-termine-alert');
    var evaluatorsCard = document.getElementById('affichage-evaluateurs-card');
    if (value === 'termine') {
        if (alert) alert.classList.remove('hidden');
        if (evaluatorsCard) evaluatorsCard.style.display = 'none';
    } else {
        if (alert) alert.classList.add('hidden');
        if (evaluatorsCard) evaluatorsCard.style.display = '';
    }

    var t = (typeof translations !== 'undefined' && translations[typeof getLanguage === 'function' ? getLanguage() : 'fr']) || {};
    var labels = { actif: t.status_active || 'Actif', termine: t.status_termine || 'Terminé', archive: t.status_archived || 'Archivé' };
    var classes = { actif: 'status-active', termine: 'status-paused', archive: 'status-paused' };
    affichagesData[id].status = labels[value] || labels.actif;
    affichagesData[id].statusClass = value === 'archive' ? 'status-closed' : (value === 'termine' ? 'status-paused' : 'status-active');

    var formData = new FormData();
    formData.append('_csrf_token', (document.querySelector('input[name="_csrf_token"]') || {}).value || '');
    formData.append('affichage_id', String(id));
    formData.append('status', value);
    fetch('/affichages/update', { method: 'POST', body: formData })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (!res.success) {
                select.value = previousValue;
                applyStatusSelectStyle(select);
                affichagesData[id].status = labels[previousValue] || labels.actif;
                affichagesData[id].statusClass = previousValue === 'archive' ? 'status-closed' : (previousValue === 'termine' ? 'status-paused' : 'status-active');
                if (previousValue === 'termine') { if (alert) alert.classList.remove('hidden'); if (evaluatorsCard) evaluatorsCard.style.display = 'none'; } else { if (alert) alert.classList.add('hidden'); if (evaluatorsCard) evaluatorsCard.style.display = ''; }
                alert(res.error || 'Erreur lors de l\'enregistrement du statut.');
            } else {
                updateAffichageRowInTable(id);
            }
        })
        .catch(function () {
            select.value = previousValue;
            applyStatusSelectStyle(select);
            affichagesData[id].status = labels[previousValue] || labels.actif;
            affichagesData[id].statusClass = previousValue === 'archive' ? 'status-closed' : (previousValue === 'termine' ? 'status-paused' : 'status-active');
            if (previousValue === 'termine') { if (alert) alert.classList.remove('hidden'); if (evaluatorsCard) evaluatorsCard.style.display = 'none'; } else { if (alert) alert.classList.add('hidden'); if (evaluatorsCard) evaluatorsCard.style.display = ''; }
            alert('Erreur réseau. Le statut n\'a pas été enregistré.');
        });
}

function updateAffichageRowInTable(affichageId) {
    var a = affichagesData[affichageId];
    if (!a) return;
    var row = document.querySelector('#affichages-table tbody tr[data-affichage-id="' + affichageId + '"]');
    if (!row || !row.cells[2]) return;
    var lang = typeof getLanguage === 'function' ? getLanguage() : 'fr';
    var t = (typeof translations !== 'undefined' && translations[lang]) ? translations[lang] : {};
    var statusLabel = a.statusClass === 'status-closed' ? (t.status_archived || 'Archivé') : (a.statusClass === 'status-paused' ? (t.status_termine || 'Terminé') : (t.status_active || 'Actif'));
    var badge = row.cells[2].querySelector('.status-badge');
    if (badge) {
        badge.textContent = statusLabel;
        badge.className = 'status-badge ' + (a.statusClass || 'status-active');
    }
    var dataStatus = a.statusClass === 'status-closed' ? 'closed' : (a.statusClass === 'status-paused' ? 'paused' : 'active');
    row.setAttribute('data-status', dataStatus);
}

function showCommunicationModalFromCandidate(candidateId) {
    var affId = window._currentAffichageId;
    if (!affId) return;
    var candidates = affichageCandidats[affId] || [];
    var c = candidates.find(function (x) { return String(x.id) === String(candidateId); });
    if (!c || !c.lastCommunication || !c.lastCommunication.message) return;
    var contentEl = document.getElementById('communication-detail-content');
    var titleEl = document.getElementById('communication-detail-title');
    var dateEl = document.getElementById('communication-detail-date');
    if (contentEl) contentEl.innerHTML = escapeHtml(c.lastCommunication.message).replace(/\n/g, '<br>');
    if (titleEl) titleEl.textContent = 'Dernier message envoyé à ' + (c.name || 'candidat');
    if (dateEl) dateEl.textContent = formatUtcToLocal(c.lastCommunication.sent_at) || '';
    openModal('communication-detail');
}

/* ═══════════════════════════════════════════════
   NOTIFIER CANDIDATS MODAL
   ═══════════════════════════════════════════════ */
function openNotifyCandidatsModal() {
    var id = window._currentAffichageId;
    var candidates = affichageCandidats[id] || [];
    var container = document.getElementById('notify-candidats-list');
    container.innerHTML = '';

    candidates.forEach(function (c, i) {
        var div = document.createElement('div');
        div.style.cssText = 'display:flex;align-items:center;gap:0.75rem;padding:0.6rem 1rem;border-bottom:1px solid var(--border-color);';
        if (i === candidates.length - 1) div.style.borderBottom = 'none';
        div.innerHTML =
            '<input type="checkbox" class="notify-candidate-cb" value="' + escapeHtml(c.id) + '" checked style="accent-color:var(--primary-color);flex-shrink:0;">' +
            '<img src="https://ui-avatars.com/api/?name=' + encodeURIComponent(c.name) + '&background=' + escapeHtml(c.color) + '&color=fff&size=32" style="width:32px;height:32px;border-radius:50%;flex-shrink:0;" alt="">' +
            '<div style="flex:1;min-width:0;"><strong style="font-size:0.875rem;">' + escapeHtml(c.name) + '</strong><div class="subtitle-muted">' + escapeHtml(c.email) + '</div></div>' +
            '<span class="notify-row-favorite" style="display:inline-flex;align-items:center;justify-content:center;width:1.25rem;flex-shrink:0;">' + (c.isFavorite ? '<i class="fa-solid fa-heart" style="color:#EC4899;"></i>' : '<i class="fa-regular fa-heart" style="color:#D1D5DB;"></i>') + '</span>' +
            '<span class="status-badge" style="background:' + (c.statusBg || '#DBEAFE') + ';color:' + (c.statusColor || '#1D4ED8') + ';font-size:0.7rem;flex-shrink:0;">' + escapeHtml(statusToLabel(c.status)) + '</span>';
        container.appendChild(div);
    });

    if (candidates.length === 0) {
        container.innerHTML = '<div style="padding:1.5rem;text-align:center;color:#94A3B8;font-size:0.85rem;">Aucun candidat à notifier.</div>';
    }

    var selectAll = document.getElementById('notify-select-all');
    if (selectAll) selectAll.checked = true;
    document.getElementById('notify-candidats-message').value = '';

    var btnContainer = document.getElementById('notify-template-buttons');
    if (btnContainer) {
        btnContainer.innerHTML = '';
        var templates = Array.isArray(emailTemplates) ? emailTemplates : [];
        templates.forEach(function (tpl, i) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-secondary btn-sm';
            btn.textContent = tpl.title || 'Modèle ' + (i + 1);
            btn.onclick = function () { setNotifyMessage(i); };
            btnContainer.appendChild(btn);
        });
        var customBtn = document.createElement('button');
        customBtn.type = 'button';
        customBtn.className = 'btn btn-secondary btn-sm';
        customBtn.textContent = 'Personnalisé';
        customBtn.onclick = function () { setNotifyMessage('custom'); };
        btnContainer.appendChild(customBtn);
    }
    openModal('notify-candidats');
}

function toggleSelectAllNotify(cb) {
    document.querySelectorAll('.notify-candidate-cb').forEach(function (box) { box.checked = cb.checked; });
}

function setNotifyMessage(idxOrCustom) {
    var textarea = document.getElementById('notify-candidats-message');
    if (!textarea) return;
    if (idxOrCustom === 'custom') {
        textarea.value = '';
        textarea.focus();
    } else {
        var templates = Array.isArray(emailTemplates) ? emailTemplates : [];
        var idx = parseInt(idxOrCustom, 10);
        if (!isNaN(idx) && idx >= 0 && idx < templates.length && templates[idx]) {
            textarea.value = templates[idx].content || '';
        }
    }
}

function confirmNotifyCandidats() {
    var affichageId = window._currentAffichageId;
    if (!affichageId) {
        alert('Erreur : aucun affichage sélectionné.');
        return;
    }
    var checkboxes = document.querySelectorAll('.notify-candidate-cb:checked');
    var candidateIds = [];
    checkboxes.forEach(function (cb) {
        var v = (cb.value || '').trim();
        if (v) candidateIds.push(v);
    });
    if (candidateIds.length === 0) {
        alert('Veuillez sélectionner au moins un candidat.');
        return;
    }
    var message = (document.getElementById('notify-candidats-message') || {}).value || '';
    if (!message.trim()) {
        alert('Veuillez rédiger un message à envoyer aux candidats.');
        return;
    }
    var btn = document.querySelector('#notify-candidats-modal .btn-primary');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Envoi en cours...';
    }
    var params = new URLSearchParams();
    params.append('_csrf_token', (document.querySelector('input[name="_csrf_token"]') || {}).value || '');
    params.append('affichage_id', String(affichageId));
    var msgTrim = message.trim();
    params.append('message_b64', btoa(unescape(encodeURIComponent(msgTrim))));
    candidateIds.forEach(function (id) { params.append('candidate_ids[]', id); });

    fetch('/candidats/notify', { method: 'POST', body: params })
        .then(function (r) {
            return r.text().then(function (text) {
                var res = null;
                try {
                    res = text ? JSON.parse(text) : {};
                } catch (parseErr) {
                    if (!r.ok) {
                        console.error('Notify candidats: réponse non-JSON (probable erreur serveur)', r.status, text ? text.slice(0, 200) : '');
                        return { _httpError: true, status: r.status };
                    }
                    throw parseErr;
                }
                return { ok: r.ok, status: r.status, body: res };
            });
        })
        .then(function (payload) {
            closeModal('notify-candidats');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Envoyer';
            }
            if (payload._httpError) {
                alert('Erreur serveur (code ' + payload.status + '). Veuillez réessayer ou contacter le support.');
                return;
            }
            var res = payload.body;
            if (payload.ok && res.success) {
                if (res.sent > 0 && affichageId) {
                    var msgText = document.getElementById('notify-candidats-message').value.trim();
                    var sentAt = new Date().toISOString().slice(0, 19).replace('T', ' ');
                    candidateIds.forEach(function (cid) {
                        var list = affichageCandidats[affichageId] || [];
                        var cand = list.find(function (x) { return String(x.id) === String(cid); });
                        if (cand) {
                            cand.lastCommunication = { message: msgText, sent_at: sentAt };
                        }
                    });
                    renderAffichageCandidatsTable(affichageId, _affichageCandidatsFilter);
                }
                var msg = res.sent + ' courriel(s) envoyé(s) avec succès.';
                if (res.failed && res.failed.length > 0) {
                    msg += ' Échec pour : ' + res.failed.join(', ');
                }
                alert(msg);
            } else {
                alert(res.error || 'Une erreur est survenue lors de l\'envoi.');
            }
        })
        .catch(function (e) {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Envoyer';
            }
            console.error('Notify candidats error:', e);
            alert('Erreur réseau. Vérifiez votre connexion et réessayez.');
        });
}

/* ═══════════════════════════════════════════════
   AJOUTER CANDIDAT MODAL
   ═══════════════════════════════════════════════ */
function openAddCandidatModal() {
    document.getElementById('add-candidat-prenom').value = '';
    document.getElementById('add-candidat-nom').value = '';
    document.getElementById('add-candidat-email').value = '';
    document.getElementById('add-candidat-phone').value = '';
    openModal('add-candidat');
}

function submitAddCandidat(e) {
    e.preventDefault();
    var id = window._currentAffichageId;
    if (!id) return;

    var prenom = document.getElementById('add-candidat-prenom').value.trim();
    var nom = document.getElementById('add-candidat-nom').value.trim();
    var email = document.getElementById('add-candidat-email').value.trim();
    var phone = document.getElementById('add-candidat-phone').value.trim();
    if (!prenom || !nom || !email) return;

    var fullName = prenom + ' ' + nom;
    var colors = ['3B82F6', '8B5CF6', 'EC4899', '10B981', 'F59E0B', 'EF4444'];
    var color = colors[Math.floor(Math.random() * colors.length)];

    var newCandidat = {
        id: 'candidat-' + Date.now(),
        name: fullName,
        email: email,
        phone: phone,
        color: color,
        status: 'Nouveau',
        statusBg: '#DBEAFE',
        statusColor: '#1E40AF',
        video: false,
        stars: 0,
        date: new Date().toISOString().split('T')[0]
    };

    if (!affichageCandidats[id]) affichageCandidats[id] = [];
    affichageCandidats[id].push(newCandidat);

    closeModal('add-candidat');
    showAffichageDetail(id); // Re-render
}

/* ═══════════════════════════════════════════════
   ÉVALUATEURS CRUD
   ═══════════════════════════════════════════════ */
function renderEvaluateurs() {
    var id = window._currentAffichageId;
    if (!id || !affichagesData[id]) return;

    var evaluateurs = affichagesData[id].evaluateurs || [];
    var container = document.getElementById('affichage-evaluateurs-list');
    var countEl = document.getElementById('affichage-evaluateurs-count');
    var lang = typeof getLanguage === 'function' ? getLanguage() : 'fr';
    var t = (typeof translations !== 'undefined' && translations[lang]) ? translations[lang] : { evaluator: 'évaluateur', evaluators: 'évaluateurs', no_evaluators_assigned: 'Aucun évaluateur assigné.', evaluator_remove: 'Retirer' };
    container.innerHTML = '';
    countEl.textContent = evaluateurs.length + ' ' + (evaluateurs.length === 1 ? t.evaluator : t.evaluators);

    var isEvaluateur = !!(typeof APP_DATA !== 'undefined' && APP_DATA.isEvaluateur);
    evaluateurs.forEach(function (ev, index) {
        var initials = ev.name.split(' ').map(function (w) { return w.charAt(0).toUpperCase(); }).join('').substring(0, 2);
        var evId = ev.id != null ? ev.id : '';
        var deleteBtn = '';
        if (!isEvaluateur) {
            var removeTitle = escapeHtml(t.evaluator_remove || 'Retirer');
            var onclickAttr = evId ? 'onclick="event.preventDefault();event.stopPropagation();deleteEvaluateur(' + index + ', ' + evId + ')"' : 'onclick="event.preventDefault();event.stopPropagation();deleteEvaluateur(' + index + ', null)"';
            deleteBtn = '<button type="button" class="btn-icon btn-icon--danger" title="' + removeTitle + '" ' + onclickAttr + '><i class="fa-solid fa-trash"></i></button>';
        }
        var div = document.createElement('div');
        div.className = 'evaluateur-item';
        div.innerHTML =
            '<div class="evaluateur-avatar">' + escapeHtml(initials) + '</div>' +
            '<div class="evaluateur-info">' +
            '<div class="evaluateur-name">' + escapeHtml(ev.name) + '</div>' +
            '<div class="evaluateur-email">' + escapeHtml(ev.email) + '</div>' +
            '</div>' + deleteBtn;
        container.appendChild(div);
    });

    if (evaluateurs.length === 0) {
        container.innerHTML = '<div style="padding:1rem;text-align:center;color:#94A3B8;font-size:0.85rem;">' + escapeHtml(t.no_evaluators_assigned || 'Aucun évaluateur assigné.') + '</div>';
    }
}

function addEvaluateur() {
    var id = window._currentAffichageId;
    if (!id || !affichagesData[id]) {
        alert('Veuillez d\'abord sélectionner un affichage.');
        return;
    }

    var prenomInput = document.getElementById('eval-new-prenom');
    var nomInput = document.getElementById('eval-new-nom');
    var emailInput = document.getElementById('eval-new-email');
    if (!prenomInput || !nomInput || !emailInput) {
        alert('Champs du formulaire introuvables.');
        return;
    }
    var prenom = prenomInput.value.trim();
    var nom = nomInput.value.trim();
    var email = emailInput.value.trim();
    if (!prenom || !nom || !email) {
        alert('Veuillez remplir le prénom, le nom et le courriel.');
        return;
    }

    var formData = new FormData();
    formData.append('_csrf_token', (document.querySelector('input[name="_csrf_token"]') || {}).value || '');
    formData.append('affichage_id', String(id));
    formData.append('prenom', prenom);
    formData.append('nom', nom);
    formData.append('email', email);

    fetch('/affichages/evaluateur/add', { method: 'POST', body: formData })
        .then(function (r) {
            var ct = r.headers.get('Content-Type') || '';
            if (!ct.includes('application/json')) {
                throw new Error(r.status === 403 ? 'Session expirée. Rechargez la page.' : 'Erreur serveur (HTTP ' + r.status + ')');
            }
            return r.json();
        })
        .then(function (res) {
            if (res.success) {
                if (!affichagesData[id].evaluateurs) affichagesData[id].evaluateurs = [];
                affichagesData[id].evaluateurs.push(res.evaluateur);
                prenomInput.value = '';
                nomInput.value = '';
                emailInput.value = '';
                if (prenomInput) prenomInput.focus();
                renderEvaluateurs();
                if (!res.email_sent) {
                    alert('Évaluateur ajouté, mais l\'envoi du courriel a échoué. Contactez ' + email + ' pour l\'informer.');
                }
            } else {
                alert('Erreur: ' + (res.error || 'Impossible d\'ajouter l\'évaluateur'));
            }
        })
        .catch(function (err) { alert(err && err.message ? err.message : 'Erreur réseau'); });
}

function deleteEvaluateur(index, evaluateurId) {
    var id = window._currentAffichageId;
    if (!id || !affichagesData[id] || !affichagesData[id].evaluateurs) return;
    var ev = affichagesData[id].evaluateurs[index];
    if (!ev) return;

    var lang = typeof getLanguage === 'function' ? getLanguage() : 'fr';
    var t = (typeof translations !== 'undefined' && translations[lang]) ? translations[lang] : {};
    if (!confirm(t.remove_evaluateur_confirm || 'Retirer cet évaluateur de l\'affichage ?')) return;

    if (evaluateurId && evaluateurId > 0) {
        var formData = new FormData();
        formData.append('_csrf_token', (document.querySelector('input[name="_csrf_token"]') || {}).value || '');
        formData.append('affichage_id', String(id));
        formData.append('evaluateur_id', String(evaluateurId));

        fetch('/affichages/evaluateur/remove', { method: 'POST', body: formData })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) {
                    affichagesData[id].evaluateurs.splice(index, 1);
                    renderEvaluateurs();
                } else {
                    alert('Erreur: ' + (res.error || 'Impossible de retirer'));
                }
            })
            .catch(function () { alert('Erreur réseau'); });
    } else {
        affichagesData[id].evaluateurs.splice(index, 1);
        renderEvaluateurs();
    }
}

window.addEventListener('i18n-updated', function () {
    if (typeof renderEvaluateurs === 'function' && window._currentAffichageId) renderEvaluateurs();
    // Rafraîchir les libellés du select statut affichage (Active / Completed / Archived)
    var statusSelect = document.getElementById('affichage-status-select');
    if (statusSelect) {
        var lang = typeof getLanguage === 'function' ? getLanguage() : 'fr';
        var t = (typeof translations !== 'undefined' && translations[lang]) ? translations[lang] : {};
        for (var o = 0; o < statusSelect.options.length; o++) {
            var opt = statusSelect.options[o];
            var key = opt.getAttribute('data-i18n');
            if (key && t[key]) opt.textContent = t[key];
        }
    }
});

function setPlaybackSpeed(speed, btn) {
    var vp = document.getElementById('detail-candidate-video-player');
    if (vp) {
        vp.playbackRate = speed;
    }
    if (btn && btn.parentNode) {
        btn.parentNode.querySelectorAll('.speed-btn').forEach(function (b) {
            b.classList.remove('active');
        });
        btn.classList.add('active');
    }
}

/* ═══════════════════════════════════════════════
   DÉPARTEMENTS
   ═══════════════════════════════════════════════ */
function renderDepartments() {
    var container = document.getElementById('settings-departments-list');
    var countEl = document.getElementById('settings-departments-count');
    if (!container) return;

    container.innerHTML = '';
    countEl.textContent = departmentsData.length + ' département' + (departmentsData.length !== 1 ? 's' : '');

    departmentsData.forEach(function (name, index) {
        var div = document.createElement('div');
        div.className = 'department-item';
        div.innerHTML =
            '<span class="department-name">' + escapeHtml(name) + '</span>' +
            '<button class="btn-icon btn-icon--danger" title="Supprimer" onclick="deleteDepartment(' + index + ')"><i class="fa-solid fa-trash"></i></button>';
        container.appendChild(div);
    });

    if (departmentsData.length === 0) {
        container.innerHTML = '<div class="departments-empty">Aucun département. Ajoutez-en un ci-dessous.</div>';
    }
}

function addDepartment() {
    var input = document.getElementById('dept-new-name');
    var name = input.value.trim();
    if (!name) return;
    if (departmentsData.indexOf(name) >= 0) return;

    departmentsData.push(name);
    input.value = '';
    input.focus();
    renderDepartments();
}

function deleteDepartment(index) {
    if (index < 0 || index >= departmentsData.length) return;
    departmentsData.splice(index, 1);
    renderDepartments();
}

/* ═══════════════════════════════════════════════
   ÉQUIPE (utilisateurs plateforme)
   ═══════════════════════════════════════════════ */
function renderTeamMembers() {
    var container = document.getElementById('settings-team-list');
    var countEl = document.getElementById('settings-team-count');
    if (!container) return;

    var currentUserId = (typeof APP_DATA !== 'undefined' && APP_DATA.currentUserId) ? String(APP_DATA.currentUserId) : '';
    var effectiveOwnerId = (typeof APP_DATA !== 'undefined' && APP_DATA.effectiveOwnerId) ? String(APP_DATA.effectiveOwnerId) : '';
    container.innerHTML = '';
    countEl.textContent = teamMembersData.length + ' utilisateur' + (teamMembersData.length !== 1 ? 's' : '');

    teamMembersData.forEach(function (m, index) {
        var initials = (m.name || '').split(' ').map(function (w) { return w.charAt(0).toUpperCase(); }).join('').substring(0, 2) || '?';
        var isOwner = (m.role === 'owner');
        var isSelf = (String(m.id) === currentUserId);
        var canDelete = !isOwner && !isSelf;

        var badgeLabel = isOwner ? 'Propriétaire' : 'Accès complet';
        var badgeClass = isOwner ? 'team-member-role-badge team-member-role-badge--owner' : 'team-member-role-badge';

        var div = document.createElement('div');
        div.className = 'team-member-item';
        div.setAttribute('data-member-id', String(m.id));
        div.innerHTML =
            '<div class="team-member-avatar">' + escapeHtml(initials) + '</div>' +
            '<div class="team-member-info">' +
            '<div class="team-member-name">' + escapeHtml(m.name || '') + (isSelf ? ' <span style="color:#94a3b8;font-size:0.8em">(vous)</span>' : '') + '</div>' +
            '<div class="team-member-email">' + escapeHtml(m.email || '') + '</div>' +
            '</div>' +
            '<span class="' + badgeClass + '">' + badgeLabel + '</span>' +
            (canDelete ? '<button class="btn-icon btn-icon--danger" title="Retirer" onclick="deleteTeamMember(' + index + ')"><i class="fa-solid fa-trash"></i></button>' : '');
        container.appendChild(div);
    });

    if (teamMembersData.length === 0) {
        container.innerHTML = '<div class="team-members-empty">Aucun utilisateur avec accès entreprise. Ajoutez-en un ci-dessous.</div>';
    }
}

function addTeamMember() {
    var prenom = document.getElementById('team-new-prenom').value.trim();
    var nom = document.getElementById('team-new-nom').value.trim();
    var email = document.getElementById('team-new-email').value.trim();
    if (!email || email.indexOf('@') === -1) {
        alert('Veuillez entrer un courriel valide.');
        return;
    }

    var formData = new FormData();
    formData.append('prenom', prenom);
    formData.append('nom', nom);
    formData.append('email', email);
    formData.append('_csrf_token', (document.querySelector('input[name="_csrf_token"]') || {}).value || '');

    var btn = document.querySelector('#settings-team-add-row .btn-primary');
    if (btn) { btn.disabled = true; }

    fetch('/parametres/company-members/add', {
        method: 'POST',
        body: formData
    })
        .then(function (r) {
            return r.json().catch(function () {
                return { success: false, error: 'Erreur serveur (code ' + r.status + ')' };
            }).then(function (data) { return { ok: r.ok, data: data }; });
        })
        .then(function (res) {
            if (res.ok && res.data.success && res.data.member) {
                teamMembersData.push(res.data.member);
                clearTeamMemberForm();
                renderTeamMembers();
            } else {
                alert(res.data.error || 'Erreur lors de l\'ajout');
            }
        })
        .catch(function (err) { alert('Erreur réseau : ' + (err.message || err)); })
        .finally(function () {
            if (btn) { btn.disabled = false; }
        });
}

function clearTeamMemberForm() {
    var prenom = document.getElementById('team-new-prenom');
    var nom = document.getElementById('team-new-nom');
    var email = document.getElementById('team-new-email');
    if (prenom) prenom.value = '';
    if (nom) nom.value = '';
    if (email) email.value = '';
    if (prenom) prenom.focus();
}

function deleteTeamMember(index) {
    if (index < 0 || index >= teamMembersData.length) return;
    var member = teamMembersData[index];
    var memberId = member.id;
    var params = new FormData();
    params.append('member_id', memberId);
    params.append('_csrf_token', (document.querySelector('input[name="_csrf_token"]') || {}).value || '');

    fetch('/parametres/company-members/remove', {
        method: 'POST',
        body: params
    })
        .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
        .then(function (res) {
            if (res.ok && res.data.success) {
                teamMembersData.splice(index, 1);
                renderTeamMembers();
            } else {
                alert(res.data.error || 'Erreur lors du retrait');
            }
        })
        .catch(function () { alert('Erreur réseau'); });
}

/* ═══════════════════════════════════════════════
   FEEDBACK
   ═══════════════════════════════════════════════ */
function sendFeedback(e) {
    e.preventDefault();
    var form = e.target;
    var submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Envoi...';
    }
    var formData = new FormData(form);
    var fbType = form.querySelector('input[name="feedback_type"]:checked');
    if (fbType && fbType.value === 'problem' && window.location && window.location.href) {
        formData.append('page_url', window.location.href);
    }
    fetch('/feedback', {
        method: 'POST',
        body: formData
    })
        .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
        .then(function (res) {
            var lang = typeof getLanguage === 'function' ? getLanguage() : 'fr';
            var msg = (typeof translations !== 'undefined' && translations[lang]) ? translations[lang].feedback_success : 'Merci pour votre retour !';
            alert(res.data.ok ? msg : (res.data.error || msg));
            if (res.data.ok) {
                closeModal('feedback');
                form.reset();
            }
        })
        .catch(function () {
            alert('Une erreur est survenue. Réessayez plus tard.');
        })
        .finally(function () {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = typeof translations !== 'undefined' && translations.fr ? translations.fr.btn_send : 'Envoyer';
            }
        });
}

/* ═══════════════════════════════════════════════
   EMAIL TEMPLATE CRUD
   ═══════════════════════════════════════════════ */
var editingTemplateIndex = -1;

function fetchEmailTemplates() {
    fetch('/parametres/email-templates')
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.success && Array.isArray(res.templates)) {
                emailTemplates = res.templates;
                if (typeof renderEmailTemplates === 'function') renderEmailTemplates();
            }
        })
        .catch(function (err) { console.error('fetchEmailTemplates:', err); });
}

function openEmailTemplateEditor(index) {
    editingTemplateIndex = (typeof index === 'number') ? index : -1;
    var editor = document.getElementById('email-template-editor');
    if (!editor) return;
    document.getElementById('email-editor-title').textContent = editingTemplateIndex >= 0 ? 'Modifier le modèle' : 'Nouveau modèle';
    var idInput = document.getElementById('email-tpl-id');
    if (idInput) idInput.value = editingTemplateIndex >= 0 && emailTemplates[editingTemplateIndex] && emailTemplates[editingTemplateIndex].id ? String(emailTemplates[editingTemplateIndex].id) : '';
    document.getElementById('email-tpl-title').value = editingTemplateIndex >= 0 ? (emailTemplates[editingTemplateIndex] && emailTemplates[editingTemplateIndex].title) || '' : '';
    document.getElementById('email-tpl-content').value = editingTemplateIndex >= 0 ? (emailTemplates[editingTemplateIndex] && emailTemplates[editingTemplateIndex].content) || '' : '';
    editor.classList.remove('hidden');
    document.getElementById('email-tpl-title').focus();
}

function closeEmailTemplateEditor() {
    var editor = document.getElementById('email-template-editor');
    if (editor) {
        editor.classList.add('hidden');
        var form = document.getElementById('form-email-template');
        if (form) form.reset();
    }
    var idEl = document.getElementById('email-tpl-id');
    if (idEl) idEl.value = '';
    editingTemplateIndex = -1;
}

function editEmailTemplate(index) { openEmailTemplateEditor(index); }

function deleteEmailTemplate(btn) {
    var row = btn.closest('.email-template-row');
    var rows = Array.from(document.querySelectorAll('.email-template-row'));
    var index = rows.indexOf(row);
    if (index < 0) return;
    var tpl = emailTemplates[index];
    var id = tpl && tpl.id ? tpl.id : 0;
    if (!id) {
        emailTemplates.splice(index, 1);
        row.remove();
        return;
    }
    if (!confirm('Supprimer ce modèle ?')) return;
    var params = new URLSearchParams();
    params.append('_csrf_token', (document.querySelector('input[name="_csrf_token"]') || {}).value || '');
    params.append('id', String(id));
    fetch('/parametres/email-templates/delete', { method: 'POST', body: params })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.success) {
                emailTemplates.splice(index, 1);
                row.remove();
            } else {
                alert(res.error || 'Erreur lors de la suppression.');
            }
        })
        .catch(function (err) {
            console.error('deleteEmailTemplate:', err);
            alert('Erreur réseau. Veuillez réessayer.');
        });
}

function saveEmailTemplate(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    var titleEl = document.getElementById('email-tpl-title');
    var contentEl = document.getElementById('email-tpl-content');
    var title = (titleEl && titleEl.value || '').trim();
    var content = (contentEl && contentEl.value || '').trim();
    if (!title || !content) {
        alert('Veuillez remplir le titre et le contenu.');
        return false;
    }
    var idEl = document.getElementById('email-tpl-id');
    var id = idEl ? idEl.value : '';
    var form = document.getElementById('form-email-template');
    if (!form) return false;
    var formData = new FormData();
    var csrfToken = (form.querySelector('input[name="_csrf_token"]') || {}).value || '';
    formData.append('_csrf_token', csrfToken);
    formData.append('title_b64', btoa(unescape(encodeURIComponent(title))));
    formData.append('content_b64', btoa(unescape(encodeURIComponent(content))));
    if (id) formData.append('id', id);

    var submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enregistrement...'; }
    fetch('/parametres/email-templates', { method: 'POST', body: formData })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="fa-solid fa-check"></i> Enregistrer'; }
            if (res.success && res.template) {
                if (editingTemplateIndex >= 0) {
                    emailTemplates[editingTemplateIndex] = res.template;
                } else {
                    emailTemplates.push(res.template);
                }
                renderEmailTemplates();
                closeEmailTemplateEditor();
            } else {
                alert(res.error || 'Erreur lors de l\'enregistrement.');
            }
        })
        .catch(function (err) {
            if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="fa-solid fa-check"></i> Enregistrer'; }
            console.error('saveEmailTemplate:', err);
            alert('Erreur réseau. Veuillez réessayer.');
        });
    return false;
}

function renderEmailTemplates() {
    var container = document.getElementById('email-templates-list');
    if (!container) return;
    container.innerHTML = '';
    var icons = [
        { bg: '#DBEAFE', color: '#1D4ED8', icon: 'fa-envelope' },
        { bg: '#D1FAE5', color: '#065F46', icon: 'fa-circle-check' },
        { bg: '#FEE2E2', color: '#991B1B', icon: 'fa-xmark' },
        { bg: '#FEF3C7', color: '#92400E', icon: 'fa-clock' },
        { bg: '#E0E7FF', color: '#3730A3', icon: 'fa-paper-plane' },
        { bg: '#FCE7F3', color: '#9D174D', icon: 'fa-heart' }
    ];
    emailTemplates.forEach(function (tpl, i) {
        var ic = icons[i % icons.length];
        var div = document.createElement('div');
        div.className = 'email-template-row';
        div.style.cssText = 'display:flex;align-items:center;gap:1rem;padding:1rem;border:1px solid var(--border-color);border-radius:10px;margin-bottom:0.75rem;transition:box-shadow 0.15s;';
        div.onmouseover = function () { this.style.boxShadow = '0 2px 12px rgba(0,0,0,0.06)'; };
        div.onmouseout = function () { this.style.boxShadow = 'none'; };
        var preview = (tpl.content || '').substring(0, 100);
        if (preview.length >= 100) preview += '...';
        div.innerHTML =
            '<div style="width:40px;height:40px;background:' + ic.bg + ';border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fa-solid ' + ic.icon + '" style="color:' + ic.color + ';"></i></div>' +
            '<div style="flex:1;min-width:0;"><strong style="font-size:0.9rem;color:var(--text-primary);">' + escapeHtml(tpl.title || '') + '</strong><p style="font-size:0.8rem;color:var(--text-secondary);margin:0.2rem 0 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + escapeHtml(preview) + '</p></div>' +
            '<div style="display:flex;gap:0.5rem;flex-shrink:0;"><button class="btn-icon" title="Modifier" onclick="editEmailTemplate(' + i + ')"><i class="fa-solid fa-pen" style="color:var(--primary-color);"></i></button><button class="btn-icon" title="Supprimer" onclick="deleteEmailTemplate(this)"><i class="fa-solid fa-trash" style="color:#EF4444;"></i></button></div>';
        container.appendChild(div);
    });
}

// Rendu initial des templates
document.addEventListener('DOMContentLoaded', function () {
    renderEmailTemplates();
});
// Modal "Compléter votre profil" : navigation interne
document.querySelectorAll('.completer-profil-item').forEach(function (item) {
    item.addEventListener('click', function (e) {
        e.preventDefault();
        var href = item.getAttribute('href');
        if (!href) return;
        var section = href.replace('#', '').replace('/', '');

        // Trouver l'élément du menu correspondant et simuler le clic
        var navItem = document.querySelector('.nav-item[data-section="' + section + '"]');
        if (navItem) {
            navItem.click();
        }

        closeModal('completer-profil');
    });
});