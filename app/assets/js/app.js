/**
 * CiaoCV – Application JavaScript (Dashboard)
 * Navigation, modals, logique métier.
 * Les données viennent de APP_DATA (injecté par le layout PHP).
 */

/* ═══════════════════════════════════════════════
   DONNÉES (depuis PHP → JSON)
   ═══════════════════════════════════════════════ */
var postesData      = {};
var affichagesData  = {};
var candidatsData   = {};
var affichageCandidats = {};
var emailTemplates  = [];

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
})();

/* ═══════════════════════════════════════════════
   HELPERS
   ═══════════════════════════════════════════════ */
function escapeHtml(str) {
    var div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
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
}

document.querySelectorAll('.nav-item[data-section]').forEach(function (item) {
    item.addEventListener('click', function (e) {
        e.preventDefault();
        activateNavItem(item);
        var href = item.getAttribute('href');
        if (href) history.pushState(null, null, href);
    });
});

// ─── Hash Navigation ───
function handleHashNavigation() {
    var hash = window.location.hash || '#dashboard';
    var subitem = document.querySelector('.nav-subitem[href="' + hash + '"]');
    if (subitem) {
        var submenu = subitem.closest('.nav-submenu');
        if (submenu) {
            var parentNavItem = document.querySelector('.nav-item[data-section="' + submenu.dataset.parent + '"]');
            if (parentNavItem) activateNavItem(parentNavItem);
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
        return;
    }
    var navItem = document.querySelector('.nav-item[href="' + hash + '"]');
    if (navItem) {
        activateNavItem(navItem);
    } else {
        var dashboard = document.querySelector('.nav-item[href="#dashboard"]');
        if (dashboard) activateNavItem(dashboard);
    }
}

window.addEventListener('hashchange', handleHashNavigation);
window.addEventListener('load', function () {
    handleHashNavigation();
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
   TABS
   ═══════════════════════════════════════════════ */
document.querySelectorAll('.view-tabs').forEach(function (tabGroup) {
    tabGroup.querySelectorAll('.view-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabGroup.querySelectorAll('.view-tab').forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');
        });
    });
});

/* ═══════════════════════════════════════════════
   SETTINGS TABS
   ═══════════════════════════════════════════════ */
document.querySelectorAll('.settings-nav-item').forEach(function (tab) {
    tab.addEventListener('click', function (e) {
        e.preventDefault();
        var targetId = tab.getAttribute('data-target');
        document.querySelectorAll('.settings-nav-item').forEach(function (t) {
            t.classList.remove('active');
        });
        tab.classList.add('active');
        document.querySelectorAll('.settings-pane').forEach(function (pane) { pane.style.display = 'none'; });
        var targetPane = document.getElementById(targetId);
        if (targetPane) targetPane.style.display = 'block';
    });
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.settings-pane').forEach(function (pane) {
        if (pane.id !== 'settings-company') pane.style.display = 'none';
    });
});

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
    var data = postesData[id];
    if (!data) return;
    currentPosteId = id;

    document.getElementById('detail-poste-title').textContent = data.title;
    document.getElementById('detail-poste-dept-loc').textContent = data.department + ' • ' + data.location;
    var sb = document.getElementById('detail-poste-status');
    sb.textContent = data.status;
    sb.className = 'status-badge ' + data.statusClass;
    document.getElementById('detail-poste-candidates').textContent = data.candidates;
    document.getElementById('detail-poste-date').textContent = data.date;

    // Durée d'enregistrement
    var durationSelect = document.getElementById('detail-poste-record-duration');
    if (durationSelect) {
        durationSelect.value = data.recordDuration || 3;
    }

    renderPosteQuestions();

    document.querySelectorAll('.content-section').forEach(function (s) { s.classList.remove('active'); });
    document.getElementById('poste-detail-section').classList.add('active');
    window.scrollTo(0, 0);
}

function goBackToPostes() {
    currentPosteId = null;
    document.querySelectorAll('.content-section').forEach(function (s) { s.classList.remove('active'); });
    document.getElementById('postes-section').classList.add('active');
}

/* ─── Questions CRUD ─── */

function renderPosteQuestions() {
    var data = postesData[currentPosteId];
    if (!data) return;
    if (!data.questions) data.questions = [];

    var container = document.getElementById('detail-poste-questions-list');
    var countEl = document.getElementById('detail-poste-questions-count');
    var len = data.questions.length;
    countEl.textContent = len + ' question' + (len !== 1 ? 's' : '');

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
}

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
}

function deletePosteQuestion(index) {
    postesData[currentPosteId].questions.splice(index, 1);
    renderPosteQuestions();
}

function updatePosteRecordDuration(value) {
    if (!currentPosteId || !postesData[currentPosteId]) return;
    postesData[currentPosteId].recordDuration = parseInt(value, 10);
}

function movePosteQuestion(index, direction) {
    var questions = postesData[currentPosteId].questions;
    var newIndex = index + direction;
    if (newIndex < 0 || newIndex >= questions.length) return;
    var item = questions.splice(index, 1)[0];
    questions.splice(newIndex, 0, item);
    renderPosteQuestions();
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
            else if (c.status === 'reviewed' || c.status === 'Évalué') status = '<span class="status-badge status-active">Évalué</span>';
            else if (c.status === 'shortlisted' || c.status === 'Favori') status = '<span class="status-badge status-shortlisted">Favori</span>';
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
function showAffichageDetail(id) {
    var data = affichagesData[id];
    if (!data) return;
    window._currentAffichageId = id;

    document.getElementById('affichage-candidats-title').textContent = data.title;
    document.getElementById('affichage-candidats-subtitle').textContent = data.platform + ' · ' + data.apps + ' candidatures · ' + data.views + ' vues';
    var sb = document.getElementById('affichage-candidats-status');
    sb.textContent = data.status;
    sb.className = 'status-badge ' + data.statusClass;

    var candidates = affichageCandidats[id] || [];
    var tbody = document.getElementById('affichage-candidats-tbody');
    tbody.innerHTML = '';

    candidates.forEach(function (c) {
        var stars = '';
        for (var i = 1; i <= 5; i++) {
            stars += '<i class="fa-' + (i <= c.stars ? 'solid' : 'regular') + ' fa-star"></i>';
        }
        var videoIcon = c.video
            ? '<i class="fa-solid fa-circle-check" style="color: var(--success-color);"></i>'
            : '<i class="fa-solid fa-circle-xmark" style="color: #CBD5E1;"></i>';

        var row = document.createElement('tr');
        row.style.cursor = 'pointer';
        row.onclick = function () { if (typeof showCandidateDetail === 'function') showCandidateDetail(c.id); };
        row.innerHTML =
            '<td><div style="display: flex; align-items: center; gap: 0.75rem;">' +
                '<img src="https://ui-avatars.com/api/?name=' + encodeURIComponent(c.name) + '&background=' + escapeHtml(c.color) + '&color=fff" class="avatar" alt="">' +
                '<div><strong>' + escapeHtml(c.name) + '</strong><div class="subtitle-muted">' + escapeHtml(c.email) + '</div></div>' +
            '</div></td>' +
            '<td><span class="status-badge" style="background:' + c.statusBg + '; color:' + c.statusColor + ';">' + escapeHtml(c.status) + '</span></td>' +
            '<td>' + videoIcon + '</td>' +
            '<td><div style="color: #F59E0B;">' + stars + '</div></td>' +
            '<td>' + escapeHtml(c.date) + '</td>' +
            '<td onclick="event.stopPropagation()">' +
                '<button class="btn-icon" title="Voir vidéo"><i class="fa-solid fa-play"></i></button>' +
                '<button class="btn-icon" title="Profil"><i class="fa-solid fa-user"></i></button>' +
                '<button class="btn-icon" title="Favori"><i class="fa-regular fa-star"></i></button>' +
            '</td>';
        tbody.appendChild(row);
    });

    if (candidates.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 2rem; color: #94A3B8;">Aucun candidat pour cet affichage.</td></tr>';
    }

    document.querySelectorAll('.content-section').forEach(function (s) { s.classList.remove('active'); });
    document.getElementById('affichage-candidats-section').classList.add('active');
    window.scrollTo(0, 0);
}

function goBackToAffichages() {
    document.querySelectorAll('.content-section').forEach(function (s) { s.classList.remove('active'); });
    document.getElementById('affichages-section').classList.add('active');
}

/* ═══════════════════════════════════════════════
   CANDIDAT DETAIL
   ═══════════════════════════════════════════════ */
var currentCandidateId = null;

function showCandidateDetail(id) {
    currentCandidateId = id;
    var data = candidatsData[id];
    if (!data) return;

    document.getElementById('detail-candidate-name').textContent = data.name;
    document.getElementById('detail-candidate-role-source').textContent = data.role + ' • LinkedIn';
    document.getElementById('detail-candidate-email').textContent = data.email;
    document.getElementById('detail-candidate-phone').textContent = data.phone;

    var ss = document.getElementById('detail-candidate-status-select');
    ss.value = data.status;
    ss.className = 'status-select status-' + data.status;

    var favBtn = document.getElementById('detail-candidate-favorite');
    if (data.isFavorite) {
        favBtn.innerHTML = '<i class="fa-solid fa-star"></i>';
        favBtn.classList.add('active');
    } else {
        favBtn.innerHTML = '<i class="fa-regular fa-star"></i>';
        favBtn.classList.remove('active');
    }

    var vp = document.getElementById('detail-candidate-video-player');
    var ph = document.getElementById('detail-video-placeholder');
    if (data.video) { vp.style.display = 'block'; ph.style.display = 'none'; vp.src = data.video; }
    else { vp.style.display = 'none'; ph.style.display = 'block'; }

    setRatingUI(data.rating);
    renderTimeline(data.comments);

    document.querySelectorAll('.content-section').forEach(function (s) { s.classList.remove('active'); });
    document.getElementById('candidate-detail-section').classList.add('active');
    window.scrollTo(0, 0);
}

function toggleFavorite() {
    if (!currentCandidateId) return;
    var data = candidatsData[currentCandidateId];
    data.isFavorite = !data.isFavorite;
    var favBtn = document.getElementById('detail-candidate-favorite');
    favBtn.innerHTML = data.isFavorite ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star"></i>';
    favBtn.classList.toggle('active', data.isFavorite);
}

function updateCandidateStatus(newStatus) {
    if (!currentCandidateId) return;
    candidatsData[currentCandidateId].status = newStatus;
    document.getElementById('detail-candidate-status-select').className = 'status-select status-' + newStatus;
}

function goBackToCandidates() {
    document.querySelectorAll('.content-section').forEach(function (s) { s.classList.remove('active'); });
    document.getElementById('candidats-section').classList.add('active');
}

function setRating(rating) {
    if (!currentCandidateId) return;
    candidatsData[currentCandidateId].rating = rating;
    setRatingUI(rating);
}

function setRatingUI(rating) {
    document.querySelectorAll('#detail-star-rating i').forEach(function (star) {
        var val = parseInt(star.getAttribute('data-value'));
        star.classList.toggle('active', val <= rating);
        star.style.color = val <= rating ? '#F59E0B' : '#D1D5DB';
    });
}

function renderTimeline(comments) {
    var container = document.getElementById('detail-timeline-list');
    container.innerHTML = '';
    comments.forEach(function (c) {
        var item = document.createElement('div');
        item.className = 'timeline-item';
        item.innerHTML =
            '<div style="width:32px;height:32px;background:#E5E7EB;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:600;color:#4B5563;">' + escapeHtml(c.user.charAt(0)) + '</div>' +
            '<div style="flex:1;"><div style="display:flex;justify-content:space-between;align-items:baseline;"><strong style="font-size:0.875rem;">' + escapeHtml(c.user) + '</strong><span class="timeline-date">' + escapeHtml(c.date) + '</span></div><p style="font-size:0.875rem;color:var(--text-primary);margin-top:0.25rem;">' + escapeHtml(c.text) + '</p></div>';
        container.appendChild(item);
    });
}

function addComment() {
    var input = document.getElementById('detail-new-comment-input');
    var text = input.value.trim();
    if (!text || !currentCandidateId) return;
    candidatsData[currentCandidateId].comments.unshift({ user: 'Moi', date: "À l'instant", text: text });
    renderTimeline(candidatsData[currentCandidateId].comments);
    input.value = '';
}

/* ═══════════════════════════════════════════════
   FERMER AFFICHAGE MODAL
   ═══════════════════════════════════════════════ */
var closeMessages = {
    polite: "Bonjour,\n\nNous vous remercions sincèrement pour l'intérêt que vous avez porté à notre offre. Après une analyse attentive de l'ensemble des candidatures reçues, nous avons décidé de poursuivre avec d'autres profils.\n\nCordialement,\nL'équipe de recrutement",
    filled: "Bonjour,\n\nNous tenons à vous informer que le poste pour lequel vous avez postulé a été comblé.\n\nMerci et bonne continuation,\nL'équipe de recrutement",
    custom: ""
};

function openCloseAffichageModal() {
    var id = window._currentAffichageId;
    var candidates = affichageCandidats[id] || [];
    var container = document.getElementById('close-affichage-candidates');
    container.innerHTML = '';

    candidates.forEach(function (c, i) {
        var div = document.createElement('div');
        div.style.cssText = 'display:flex;align-items:center;gap:0.75rem;padding:0.6rem 1rem;border-bottom:1px solid var(--border-color);';
        if (i === candidates.length - 1) div.style.borderBottom = 'none';
        div.innerHTML =
            '<input type="checkbox" class="close-candidate-cb" value="' + escapeHtml(c.id) + '" checked style="accent-color:var(--primary-color);">' +
            '<img src="https://ui-avatars.com/api/?name=' + encodeURIComponent(c.name) + '&background=' + escapeHtml(c.color) + '&color=fff&size=32" style="width:32px;height:32px;border-radius:50%;" alt="">' +
            '<div style="flex:1;"><strong style="font-size:0.875rem;">' + escapeHtml(c.name) + '</strong><div class="subtitle-muted">' + escapeHtml(c.email) + '</div></div>' +
            '<span class="status-badge" style="background:' + c.statusBg + ';color:' + c.statusColor + ';font-size:0.7rem;">' + escapeHtml(c.status) + '</span>';
        container.appendChild(div);
    });

    if (candidates.length === 0) {
        container.innerHTML = '<div style="padding:1.5rem;text-align:center;color:#94A3B8;font-size:0.85rem;">Aucun candidat à notifier.</div>';
    }

    var selectAll = document.getElementById('close-select-all');
    if (selectAll) selectAll.checked = true;
    openModal('close-affichage');
}

function toggleSelectAllClose(cb) {
    document.querySelectorAll('.close-candidate-cb').forEach(function (box) { box.checked = cb.checked; });
}

function setCloseMessage(type) {
    document.getElementById('close-affichage-message').value = closeMessages[type];
    if (type === 'custom') document.getElementById('close-affichage-message').focus();
}

function confirmCloseAffichage() {
    closeModal('close-affichage');
    goBackToAffichages();
}

/* ═══════════════════════════════════════════════
   FEEDBACK
   ═══════════════════════════════════════════════ */
function sendFeedback(e) {
    e.preventDefault();
    var lang = getLanguage();
    var msg = translations[lang] ? translations[lang].feedback_success : 'Merci !';
    alert(msg);
    closeModal('feedback');
    e.target.reset();
}

/* ═══════════════════════════════════════════════
   EMAIL TEMPLATE CRUD
   ═══════════════════════════════════════════════ */
var editingTemplateIndex = -1;

function openEmailTemplateEditor(index) {
    editingTemplateIndex = (typeof index === 'number') ? index : -1;
    document.getElementById('email-editor-title').textContent = editingTemplateIndex >= 0 ? 'Modifier le modèle' : 'Nouveau modèle';
    document.getElementById('email-tpl-title').value = editingTemplateIndex >= 0 ? emailTemplates[editingTemplateIndex].title : '';
    document.getElementById('email-tpl-content').value = editingTemplateIndex >= 0 ? emailTemplates[editingTemplateIndex].content : '';
    document.getElementById('email-template-editor').style.display = 'block';
    document.getElementById('email-tpl-title').focus();
}

function closeEmailTemplateEditor() {
    document.getElementById('email-template-editor').style.display = 'none';
    editingTemplateIndex = -1;
}

function editEmailTemplate(index) { openEmailTemplateEditor(index); }

function deleteEmailTemplate(btn) {
    var row = btn.closest('.email-template-row');
    var rows = Array.from(document.querySelectorAll('.email-template-row'));
    var index = rows.indexOf(row);
    if (index >= 0) { emailTemplates.splice(index, 1); row.remove(); }
}

function saveEmailTemplate(e) {
    e.preventDefault();
    var title = document.getElementById('email-tpl-title').value.trim();
    var content = document.getElementById('email-tpl-content').value.trim();
    if (!title || !content) return;
    if (editingTemplateIndex >= 0) {
        emailTemplates[editingTemplateIndex] = { title: title, content: content };
    } else {
        emailTemplates.push({ title: title, content: content });
    }
    renderEmailTemplates();
    closeEmailTemplateEditor();
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
        div.innerHTML =
            '<div style="width:40px;height:40px;background:' + ic.bg + ';border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fa-solid ' + ic.icon + '" style="color:' + ic.color + ';"></i></div>' +
            '<div style="flex:1;min-width:0;"><strong style="font-size:0.9rem;color:var(--text-primary);">' + escapeHtml(tpl.title) + '</strong><p style="font-size:0.8rem;color:var(--text-secondary);margin:0.2rem 0 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + escapeHtml(tpl.content.substring(0, 100)) + '...</p></div>' +
            '<div style="display:flex;gap:0.5rem;flex-shrink:0;"><button class="btn-icon" title="Modifier" onclick="editEmailTemplate(' + i + ')"><i class="fa-solid fa-pen" style="color:var(--primary-color);"></i></button><button class="btn-icon" title="Supprimer" onclick="deleteEmailTemplate(this)"><i class="fa-solid fa-trash" style="color:#EF4444;"></i></button></div>';
        container.appendChild(div);
    });
}

// Rendu initial des templates
document.addEventListener('DOMContentLoaded', function () {
    renderEmailTemplates();
});
