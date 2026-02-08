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
var teamMembersData   = [];
var departmentsData   = [];
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

    // Départements
    departmentsData = Array.isArray(APP_DATA.departments) ? APP_DATA.departments.slice() : [];

    // Équipe (utilisateurs de l'entreprise)
    teamMembersData = Array.isArray(APP_DATA.teamMembers) ? APP_DATA.teamMembers.slice() : [];
})();

/* ═══════════════════════════════════════════════
   HELPERS
   ═══════════════════════════════════════════════ */
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
    try { document.execCommand('copy'); alert('Lien copié !'); } catch (e) {}
    document.body.removeChild(ta);
}
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

    // Paramètres : afficher le panneau « Entreprise » par défaut
    if (sectionId === 'parametres') {
        document.querySelectorAll('.settings-pane').forEach(function (pane) { pane.style.display = 'none'; });
        var firstPane = document.getElementById('settings-company');
        if (firstPane) firstPane.style.display = 'block';
    }

    // Rafraîchir les traductions (i18n) quand on affiche une section
    if (typeof updateContent === 'function') updateContent();
}

document.querySelectorAll('.nav-item[data-section]').forEach(function (item) {
    item.addEventListener('click', function (e) {
        var sectionId = item.getAttribute('data-section');
        var sectionEl = sectionId ? document.getElementById(sectionId + '-section') : null;
        if (!sectionEl) {
            return;
        }
        e.preventDefault();
        activateNavItem(item);
        var href = item.getAttribute('href');
        if (href) history.pushState(null, null, href);
    });
});

// Clic sur nav-subitem avec data-section
document.querySelectorAll('.nav-subitem[data-section]').forEach(function (subitem) {
    subitem.addEventListener('click', function (e) {
        e.preventDefault();
        var sectionId = subitem.getAttribute('data-section');
        var submenu = subitem.closest('.nav-submenu');
        if (!submenu) return;
        var parentNavItem = document.querySelector('.nav-item[data-section="' + submenu.dataset.parent + '"]');
        if (parentNavItem) activateNavItem(parentNavItem);

        document.querySelectorAll('.content-section').forEach(function (sec) { sec.classList.remove('active'); });
        var sectionEl = document.getElementById(sectionId + '-section');
        if (sectionEl) sectionEl.classList.add('active');

        document.querySelectorAll('.nav-subitem').forEach(function (s) { s.classList.remove('active'); });
        subitem.classList.add('active');

        var href = subitem.getAttribute('href');
        if (href) history.pushState(null, null, href);
    });
});

// ─── Path Navigation ───
function getCurrentPath() {
    var pathname = window.location.pathname || '/';
    var basePath = (typeof APP_DATA !== 'undefined' && APP_DATA.basePath) ? APP_DATA.basePath : '';
    if (basePath && pathname.indexOf(basePath) === 0) {
        pathname = pathname.slice(basePath.length) || '/';
    }
    return pathname.replace(/\/$/, '') || '/dashboard';
}

function handlePathNavigation() {
    if (window.location.pathname.indexOf('debug') !== -1) return;
    var path = getCurrentPath();
    var navItem = document.querySelector('.nav-item[data-path="' + path + '"]') ||
        document.querySelector('.nav-item[href$="' + path + '"]');
    if (navItem) {
        activateNavItem(navItem);
    } else {
        var dashboard = document.querySelector('.nav-item[data-path="/dashboard"]') || document.querySelector('.nav-item[data-section="statistiques"]');
        if (dashboard) activateNavItem(dashboard);
    }
}

window.addEventListener('popstate', handlePathNavigation);
window.addEventListener('load', function () {
    handlePathNavigation();
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

function openForfaitEditModal(row) {
    if (!row || !row.dataset.planId) return;
    var idEl = document.getElementById('forfait-edit-id');
    var statusEl = document.getElementById('forfait-edit-status');
    var nameFrEl = document.getElementById('forfait-edit-name-fr');
    var nameEnEl = document.getElementById('forfait-edit-name-en');
    var videoEl = document.getElementById('forfait-edit-video-limit');
    var priceMoEl = document.getElementById('forfait-edit-price-monthly');
    var priceYrEl = document.getElementById('forfait-edit-price-yearly');
    if (!idEl || !statusEl) return;
    idEl.value = row.dataset.planId || '';
    statusEl.value = row.dataset.active === '1' ? '1' : '0';
    if (nameFrEl) nameFrEl.value = row.dataset.nameFr || '';
    if (nameEnEl) nameEnEl.value = row.dataset.nameEn || '';
    if (videoEl) videoEl.value = row.dataset.videoLimit || '';
    if (priceMoEl) priceMoEl.value = row.dataset.priceMonthly || '';
    if (priceYrEl) priceYrEl.value = row.dataset.priceYearly || '';
    openModal('forfait-edit');
}

document.addEventListener('click', function (e) {
    var btn = e.target.closest('.forfait-edit-btn');
    if (!btn) return;
    e.preventDefault();
    var row = btn.closest('tr');
    if (row) openForfaitEditModal(row);
});

/* ─── Modal édition / suppression utilisateur plateforme ─── */
function openUtilisateurEditModal(btn) {
    if (!btn) return;
    var modal = document.getElementById('utilisateur-edit-modal');
    if (!modal) return;
    var id = btn.getAttribute('data-user-id') || '';
    var prenom = btn.getAttribute('data-user-prenom') || '';
    var nom = btn.getAttribute('data-user-nom') || '';
    var email = btn.getAttribute('data-user-email') || '';
    var role = btn.getAttribute('data-user-role') || 'client';
    var planId = btn.getAttribute('data-user-plan-id') || '';
    var billable = btn.getAttribute('data-user-billable') === '1';
    var active = btn.getAttribute('data-user-active') !== '0';

    var idEl = document.getElementById('utilisateur-edit-id');
    if (idEl) idEl.value = id;
    var prenomEl = document.getElementById('utilisateur-edit-prenom');
    if (prenomEl) prenomEl.value = prenom;
    var nomEl = document.getElementById('utilisateur-edit-nom');
    if (nomEl) nomEl.value = nom;
    var emailEl = document.getElementById('utilisateur-edit-email');
    if (emailEl) emailEl.value = email;
    var roleEl = document.getElementById('utilisateur-edit-role');
    if (roleEl) roleEl.value = role;
    var planEl = document.getElementById('utilisateur-edit-plan');
    if (planEl) planEl.value = planId;
    var billableEl = document.getElementById('utilisateur-edit-billable');
    if (billableEl) billableEl.checked = billable;
    var activeEl = document.getElementById('utilisateur-edit-active');
    if (activeEl) activeEl.value = active ? '1' : '0';
    if (typeof updateContent === 'function') updateContent();
    modal.classList.add('active');
}

document.addEventListener('click', function (e) {
    var btn = e.target.closest('.utilisateur-edit-btn');
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();
    openUtilisateurEditModal(btn);
}, true);

var _utilisateurDeleteFormPending = null;

function openUtilisateurDeleteConfirm(formOrEditModal) {
    var displayName, formToSubmit;
    if (formOrEditModal && formOrEditModal.tagName === 'FORM') {
        displayName = formOrEditModal.getAttribute('data-user-name') || formOrEditModal.getAttribute('data-user-email') || '';
        formToSubmit = formOrEditModal;
    } else {
        var id = document.getElementById('utilisateur-edit-id').value;
        var prenom = document.getElementById('utilisateur-edit-prenom').value;
        var nom = document.getElementById('utilisateur-edit-nom').value;
        var email = document.getElementById('utilisateur-edit-email').value;
        if (!id) return;
        displayName = (prenom || nom ? (prenom + ' ' + nom).trim() : '') || email || id;
        document.getElementById('utilisateur-delete-id').value = id;
        formToSubmit = document.getElementById('utilisateur-delete-form');
    }
    var msgKey = 'utilisateur_delete_modal_message';
    var t = typeof translations !== 'undefined' && typeof getLanguage === 'function' ? (translations[getLanguage()] || {}) : {};
    var msg = t[msgKey] ? t[msgKey].replace('{name}', displayName) : 'Êtes-vous sûr de vouloir supprimer l\'utilisateur « ' + displayName + ' » ? Cette action est irréversible.';
    document.getElementById('utilisateur-delete-message').textContent = msg;
    _utilisateurDeleteFormPending = formToSubmit;
    if (typeof updateContent === 'function') updateContent();
    if (formOrEditModal && formOrEditModal.tagName === 'FORM') {
        openModal('utilisateur-delete');
    } else {
        closeModal('utilisateur-edit');
        openModal('utilisateur-delete');
    }
}

var utilisateurDeleteBtn = document.getElementById('utilisateur-delete-btn');
if (utilisateurDeleteBtn) {
    utilisateurDeleteBtn.addEventListener('click', function () {
        openUtilisateurDeleteConfirm(null);
    });
}

var utilisateurResetPasswordBtn = document.getElementById('utilisateur-reset-password-btn');
if (utilisateurResetPasswordBtn) {
    utilisateurResetPasswordBtn.addEventListener('click', function () {
        var id = document.getElementById('utilisateur-edit-id') && document.getElementById('utilisateur-edit-id').value;
        var email = document.getElementById('utilisateur-edit-email') && document.getElementById('utilisateur-edit-email').value;
        var prenom = document.getElementById('utilisateur-edit-prenom') && document.getElementById('utilisateur-edit-prenom').value;
        var nom = document.getElementById('utilisateur-edit-nom') && document.getElementById('utilisateur-edit-nom').value;
        if (!id) return;
        var displayName = (prenom || nom ? (prenom + ' ' + nom).trim() : '') || email;
        var msgKey = 'config_reset_password_message';
        var t = typeof translations !== 'undefined' && typeof getLanguage === 'function' ? (translations[getLanguage()] || {}) : {};
        var msg = t[msgKey] ? t[msgKey].replace('{email}', email).replace('{name}', displayName) : 'Un nouveau mot de passe sera généré et envoyé à ' + email + '.';
        document.getElementById('utilisateur-reset-password-id').value = id;
        document.getElementById('utilisateur-reset-password-message').textContent = msg;
        if (typeof updateContent === 'function') updateContent();
        closeModal('utilisateur-edit');
        openModal('utilisateur-reset-password-confirm');
    });
}

document.addEventListener('click', function (e) {
    var btn = e.target.closest('.utilisateur-delete-btn');
    if (!btn) return;
    e.preventDefault();
    var form = btn.closest('.utilisateur-delete-form');
    if (!form) return;
    openUtilisateurDeleteConfirm(form);
});

var utilisateurDeleteConfirmBtn = document.getElementById('utilisateur-delete-confirm');
if (utilisateurDeleteConfirmBtn) {
    utilisateurDeleteConfirmBtn.addEventListener('click', function () {
        if (_utilisateurDeleteFormPending) {
            _utilisateurDeleteFormPending.submit();
            _utilisateurDeleteFormPending = null;
        }
        closeModal('utilisateur-delete');
    });
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

/* Paramètres : visibilité des panneaux (navigation par le menu latéral) */
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

/* ─── Formulaire changement mot de passe ─── */
var changePasswordForm = document.getElementById('change-password-form');
if (changePasswordForm) {
    changePasswordForm.addEventListener('submit', function (e) {
        var newPwd = document.getElementById('change-pwd-new');
        var confirmPwd = document.getElementById('change-pwd-confirm');
        var errorEl = document.getElementById('change-pwd-match-error');
        if (!newPwd || !confirmPwd) return;
        if (newPwd.value !== confirmPwd.value) {
            e.preventDefault();
            if (errorEl) {
                errorEl.classList.remove('hidden');
                confirmPwd.classList.add('border-red');
            }
            return false;
        }
        if (errorEl) errorEl.classList.add('hidden');
        if (confirmPwd) confirmPwd.classList.remove('border-red');
    });
    document.getElementById('change-pwd-confirm')?.addEventListener('input', function () {
        var errorEl = document.getElementById('change-pwd-match-error');
        if (errorEl) errorEl.classList.add('hidden');
        this.classList.remove('border-red');
    });
}

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
    document.getElementById('detail-poste-date').textContent = data.date;

    // Durée d'enregistrement
    var durationSelect = document.getElementById('detail-poste-record-duration');
    if (durationSelect) {
        durationSelect.value = data.recordDuration || 3;
    }

    renderPosteQuestions();

    document.querySelectorAll('.content-section').forEach(function (s) { s.classList.remove('active'); });
    var detailSection = document.getElementById('poste-detail-section');
    if (detailSection) detailSection.classList.add('active');
    window.scrollTo(0, 0);
}

// Clic sur une ligne du tableau Postes (délégation)
var postesTable = document.getElementById('postes-table');
if (postesTable && postesTable.tBodies && postesTable.tBodies[0]) {
    postesTable.tBodies[0].addEventListener('click', function (e) {
        var row = e.target && e.target.closest && e.target.closest('tr.row-clickable[data-poste-id]');
        if (row) {
            var id = row.getAttribute('data-poste-id');
            if (id) showPosteDetail(id);
        }
    });
    postesTable.tBodies[0].addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        var row = e.target && e.target.closest && e.target.closest('tr.row-clickable[data-poste-id]');
        if (row) {
            e.preventDefault();
            var id = row.getAttribute('data-poste-id');
            if (id) showPosteDetail(id);
        }
    });
}

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

function updatePosteStatus(value) {
    if (!currentPosteId || !postesData[currentPosteId]) return;
    var select = document.getElementById('detail-poste-status-select');
    applyPosteStatusStyle(select);
    var labels = { actif: 'Actif', inactif: 'Non actif', archive: 'Archivé' };
    var classes = { actif: 'status-active', inactif: 'status-paused', archive: 'status-closed' };
    postesData[currentPosteId].status = labels[value] || 'Actif';
    postesData[currentPosteId].statusClass = classes[value] || 'status-active';
    // TODO: envoyer la mise à jour au serveur
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
    document.getElementById('affichage-candidats-subtitle').textContent = data.platform + ' · Début : ' + data.start;

    var shareUrlEl = document.getElementById('affichage-share-url');
    if (shareUrlEl && data.shareLongId) {
        var baseUrl = (typeof APP_DATA !== 'undefined' && APP_DATA.appUrl) ? APP_DATA.appUrl : 'https://app.ciaocv.com';
        var url = baseUrl + '/entrevue/' + data.shareLongId;
        shareUrlEl.href = url;
        shareUrlEl.textContent = url;
    }

    // Status select
    var statusSelect = document.getElementById('affichage-status-select');
    var statusKey = (data.status || 'Actif').toLowerCase().replace('é', 'e').replace('é', 'e');
    if (statusKey === 'actif') statusSelect.value = 'actif';
    else if (statusKey === 'termine') statusSelect.value = 'termine';
    else if (statusKey === 'archive') statusSelect.value = 'archive';
    else statusSelect.value = 'actif';
    applyStatusSelectStyle(statusSelect);

    // Alerte terminé
    var alert = document.getElementById('affichage-termine-alert');
    if (statusSelect.value === 'termine') alert.classList.remove('hidden');
    else alert.classList.add('hidden');

    var candidates = affichageCandidats[id] || [];
    var tbody = document.getElementById('affichage-candidats-tbody');
    tbody.innerHTML = '';

    candidates.forEach(function (c) {
        var row = document.createElement('tr');
        row.style.cursor = 'pointer';
        row.onclick = function () { if (typeof showCandidateDetail === 'function') showCandidateDetail(c.id); };
        row.innerHTML =
            '<td><div style="display: flex; align-items: center; gap: 0.75rem;">' +
                '<img src="https://ui-avatars.com/api/?name=' + encodeURIComponent(c.name) + '&background=' + escapeHtml(c.color) + '&color=fff" class="avatar" alt="">' +
                '<div><strong>' + escapeHtml(c.name) + '</strong><div class="subtitle-muted">' + escapeHtml(c.email) + '</div></div>' +
            '</div></td>' +
            '<td><span class="status-badge" style="background:' + c.statusBg + '; color:' + c.statusColor + ';">' + escapeHtml(c.status) + '</span></td>' +
            '<td style="text-align: center;">' + (c.isFavorite ? '<i class="fa-solid fa-star" style="color: #F59E0B;"></i>' : '<i class="fa-regular fa-star" style="color: #D1D5DB;"></i>') + '</td>' +
            '<td>' + escapeHtml(c.date) + '</td>';
        tbody.appendChild(row);
    });

    if (candidates.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 2rem; color: #94A3B8;">Aucun candidat pour cet affichage.</td></tr>';
    }

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
    if (ss) {
        ss.value = data.status || 'new';
        ss.className = 'status-select status-select--candidate status-' + (data.status || 'new');
    }

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
    var ss = document.getElementById('detail-candidate-status-select');
    if (ss) ss.className = 'status-select status-select--candidate status-' + newStatus;
}

function goBackToCandidates() {
    document.querySelectorAll('.content-section').forEach(function (s) { s.classList.remove('active'); });
    document.getElementById('candidats-section').classList.add('active');
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
    applyStatusSelectStyle(select);

    var alert = document.getElementById('affichage-termine-alert');
    if (value === 'termine') {
        alert.classList.remove('hidden');
    } else {
        alert.classList.add('hidden');
    }

    // Mettre à jour les données mock
    var labels = { actif: 'Actif', termine: 'Terminé', archive: 'Archivé' };
    var classes = { actif: 'status-active', termine: 'status-paused', archive: 'status-paused' };
    affichagesData[id].status = labels[value] || 'Actif';
    affichagesData[id].statusClass = classes[value] || 'status-active';
}

/* ═══════════════════════════════════════════════
   NOTIFIER CANDIDATS MODAL
   ═══════════════════════════════════════════════ */
var notifyMessages = {
    polite: "Bonjour,\n\nNous vous remercions sincèrement pour l'intérêt que vous avez porté à notre offre. Après une analyse attentive de l'ensemble des candidatures reçues, nous avons décidé de poursuivre avec d'autres profils.\n\nCordialement,\nL'équipe de recrutement",
    filled: "Bonjour,\n\nNous tenons à vous informer que le poste pour lequel vous avez postulé a été comblé.\n\nMerci et bonne continuation,\nL'équipe de recrutement",
    custom: ""
};

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
            '<span class="notify-row-favorite" style="display:inline-flex;align-items:center;justify-content:center;width:1.25rem;flex-shrink:0;">' + (c.isFavorite ? '<i class="fa-solid fa-star" style="color:#F59E0B;"></i>' : '<i class="fa-regular fa-star" style="color:#D1D5DB;"></i>') + '</span>' +
            '<span class="status-badge" style="background:' + c.statusBg + ';color:' + c.statusColor + ';font-size:0.7rem;flex-shrink:0;">' + escapeHtml(c.status) + '</span>';
        container.appendChild(div);
    });

    if (candidates.length === 0) {
        container.innerHTML = '<div style="padding:1.5rem;text-align:center;color:#94A3B8;font-size:0.85rem;">Aucun candidat à notifier.</div>';
    }

    var selectAll = document.getElementById('notify-select-all');
    if (selectAll) selectAll.checked = true;
    document.getElementById('notify-candidats-message').value = '';
    openModal('notify-candidats');
}

function toggleSelectAllNotify(cb) {
    document.querySelectorAll('.notify-candidate-cb').forEach(function (box) { box.checked = cb.checked; });
}

function setNotifyMessage(type) {
    document.getElementById('notify-candidats-message').value = notifyMessages[type];
    if (type === 'custom') document.getElementById('notify-candidats-message').focus();
}

function confirmNotifyCandidats() {
    // En production : envoyer la requête API avec les candidats sélectionnés et le message
    closeModal('notify-candidats');
    alert('Notifications envoyées avec succès !');
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
        statusBg: '#F5E6EA',
        statusColor: '#5C1A1F',
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
    container.innerHTML = '';
    countEl.textContent = evaluateurs.length + ' évaluateur' + (evaluateurs.length !== 1 ? 's' : '');

    evaluateurs.forEach(function (ev, index) {
        var initials = ev.name.split(' ').map(function (w) { return w.charAt(0).toUpperCase(); }).join('').substring(0, 2);

        var div = document.createElement('div');
        div.className = 'evaluateur-item';
        div.innerHTML =
            '<div class="evaluateur-avatar">' + escapeHtml(initials) + '</div>' +
            '<div class="evaluateur-info">' +
                '<div class="evaluateur-name">' + escapeHtml(ev.name) + '</div>' +
                '<div class="evaluateur-email">' + escapeHtml(ev.email) + '</div>' +
            '</div>' +
            '<button class="btn-icon btn-icon--danger" title="Retirer" onclick="deleteEvaluateur(' + index + ')"><i class="fa-solid fa-trash"></i></button>';
        container.appendChild(div);
    });

    if (evaluateurs.length === 0) {
        container.innerHTML = '<div style="padding:1rem;text-align:center;color:#94A3B8;font-size:0.85rem;">Aucun évaluateur assigné.</div>';
    }
}

function addEvaluateur() {
    var id = window._currentAffichageId;
    if (!id || !affichagesData[id]) return;

    var prenomInput = document.getElementById('eval-new-prenom');
    var nomInput = document.getElementById('eval-new-nom');
    var emailInput = document.getElementById('eval-new-email');
    var prenom = prenomInput.value.trim();
    var nom = nomInput.value.trim();
    var email = emailInput.value.trim();
    if (!prenom || !nom || !email) return;

    var name = prenom + ' ' + nom;
    if (!affichagesData[id].evaluateurs) affichagesData[id].evaluateurs = [];
    affichagesData[id].evaluateurs.push({ name: name, email: email });
    prenomInput.value = '';
    nomInput.value = '';
    emailInput.value = '';
    prenomInput.focus();
    renderEvaluateurs();
}

function deleteEvaluateur(index) {
    var id = window._currentAffichageId;
    if (!id || !affichagesData[id] || !affichagesData[id].evaluateurs) return;
    affichagesData[id].evaluateurs.splice(index, 1);
    renderEvaluateurs();
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

    container.innerHTML = '';
    countEl.textContent = teamMembersData.length + ' utilisateur' + (teamMembersData.length !== 1 ? 's' : '');

    teamMembersData.forEach(function (m, index) {
        var initials = m.name.split(' ').map(function (w) { return w.charAt(0).toUpperCase(); }).join('').substring(0, 2);
        var roleLabel = m.role === 'administrateur' ? 'Administrateur' : 'Évaluateur';

        var div = document.createElement('div');
        div.className = 'team-member-item';
        div.innerHTML =
            '<div class="team-member-avatar">' + escapeHtml(initials) + '</div>' +
            '<div class="team-member-info">' +
                '<div class="team-member-name">' + escapeHtml(m.name) + '</div>' +
                '<div class="team-member-email">' + escapeHtml(m.email) + '</div>' +
            '</div>' +
            '<select class="form-select form-select--role team-member-role" onchange="updateTeamMemberRole(' + index + ', this.value)" title="Rôle">' +
                '<option value="evaluateur"' + (m.role === 'evaluateur' ? ' selected' : '') + '>Évaluateur</option>' +
                '<option value="administrateur"' + (m.role === 'administrateur' ? ' selected' : '') + '>Administrateur</option>' +
            '</select>' +
            '<button class="btn-icon btn-icon--danger" title="Retirer" onclick="deleteTeamMember(' + index + ')"><i class="fa-solid fa-trash"></i></button>';
        container.appendChild(div);
    });

    if (teamMembersData.length === 0) {
        container.innerHTML = '<div class="team-members-empty">Aucun utilisateur. Ajoutez-en un ci-dessous.</div>';
    }
}

function addTeamMember() {
    var prenom = document.getElementById('team-new-prenom').value.trim();
    var nom = document.getElementById('team-new-nom').value.trim();
    var email = document.getElementById('team-new-email').value.trim();
    var role = document.getElementById('team-new-role').value;
    if (!prenom || !nom || !email) return;

    var name = prenom + ' ' + nom;
    teamMembersData.push({ id: String(Date.now()), name: name, email: email, role: role });

    document.getElementById('team-new-prenom').value = '';
    document.getElementById('team-new-nom').value = '';
    document.getElementById('team-new-email').value = '';
    document.getElementById('team-new-prenom').focus();
    renderTeamMembers();
}

function updateTeamMemberRole(index, role) {
    if (index < 0 || index >= teamMembersData.length) return;
    teamMembersData[index].role = role;
}

function deleteTeamMember(index) {
    if (index < 0 || index >= teamMembersData.length) return;
    teamMembersData.splice(index, 1);
    renderTeamMembers();
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
    var basePath = (typeof APP_DATA !== 'undefined' && APP_DATA.basePath) ? APP_DATA.basePath : '';
    fetch(basePath + '/feedback', { method: 'POST', body: formData })
    .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
    .then(function (res) {
        var lang = typeof getLanguage === 'function' ? getLanguage() : 'fr';
        var msg = (typeof translations !== 'undefined' && translations[lang]) ? translations[lang].feedback_success : 'Merci pour votre retour !';
        alert(res.data.ok ? msg : (res.data.error || msg));
        if (res.data.ok) {
            closeModal('feedback');
            form.reset();
            if (typeof refreshFeedbackList === 'function') refreshFeedbackList();
        }
    })
    .catch(function () { alert('Une erreur est survenue. Réessayez plus tard.'); })
    .finally(function () {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = (typeof translations !== 'undefined' && translations.fr) ? translations.fr.btn_send : 'Envoyer';
        }
    });
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
        { bg: '#F5E6EA', color: '#5C1A1F', icon: 'fa-envelope' },
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

/* ─── Modal édition administrateur ─── */
function openConfigEditAdminModal(btn) {
    var id = btn.getAttribute('data-admin-id') || '';
    var name = btn.getAttribute('data-admin-name') || '';
    var email = btn.getAttribute('data-admin-email') || '';
    var role = btn.getAttribute('data-admin-role') || 'admin';

    var idEl = document.getElementById('config-edit-admin-id');
    var nameEl = document.getElementById('config-edit-admin-name');
    var emailEl = document.getElementById('config-edit-admin-email');
    var roleEl = document.getElementById('config-edit-admin-role');
    if (idEl) idEl.value = id;
    if (nameEl) nameEl.value = name;
    if (emailEl) emailEl.value = email;
    if (roleEl) roleEl.value = role;
    if (typeof updateContent === 'function') updateContent();
    openModal('config-edit-admin');
}

document.addEventListener('click', function (e) {
    var btn = e.target.closest('.config-edit-admin-btn');
    if (!btn) return;
    e.preventDefault();
    openConfigEditAdminModal(btn);
});

var configResetPasswordBtn = document.getElementById('config-reset-password-btn');
if (configResetPasswordBtn) {
    configResetPasswordBtn.addEventListener('click', function () {
    var id = document.getElementById('config-edit-admin-id').value;
    var email = document.getElementById('config-edit-admin-email').value;
    var name = document.getElementById('config-edit-admin-name').value;
    if (!id) return;
    var displayName = name ? name + (email ? ' (' + email + ')' : '') : email;
    var msgKey = 'config_reset_password_message';
    var t = typeof translations !== 'undefined' && typeof getLanguage === 'function' ? (translations[getLanguage()] || {}) : {};
    var msg = t[msgKey] ? t[msgKey].replace('{email}', email).replace('{name}', displayName) : 'Un nouveau mot de passe sera généré et envoyé à ' + email + '.';
    document.getElementById('config-reset-password-id').value = id;
    document.getElementById('config-reset-password-message').textContent = msg;
    if (typeof updateContent === 'function') updateContent();
    closeModal('config-edit-admin');
    openModal('config-reset-password-confirm');
    });
}

/* ─── Modal confirmation suppression administrateur ─── */
var _configDeleteFormPending = null;

document.addEventListener('click', function (e) {
    var btn = e.target.closest('.config-delete-btn');
    if (!btn) return;
    e.preventDefault();
    var form = btn.closest('.config-delete-form');
    if (!form) return;
    var email = form.getAttribute('data-admin-email') || '';
    var name = form.getAttribute('data-admin-name') || '';

    var displayName = name ? name + (email ? ' (' + email + ')' : '') : email;
    var msgKey = 'config_delete_modal_message';
    var t = typeof translations !== 'undefined' && typeof getLanguage === 'function' ? (translations[getLanguage()] || {}) : {};
    var msg = t[msgKey] ? t[msgKey].replace('{name}', displayName) : 'Êtes-vous sûr de vouloir désactiver l\'administrateur « ' + displayName + ' » ?';

    var msgEl = document.getElementById('config-delete-admin-message');
    if (msgEl) msgEl.textContent = msg;
    if (typeof updateContent === 'function') updateContent();
    _configDeleteFormPending = form;
    openModal('config-delete-admin');
});

var configDeleteConfirmBtn = document.getElementById('config-delete-admin-confirm');
if (configDeleteConfirmBtn) {
    configDeleteConfirmBtn.addEventListener('click', function () {
    if (_configDeleteFormPending) {
        _configDeleteFormPending.submit();
        _configDeleteFormPending = null;
    }
    closeModal('config-delete-admin');
    });
}

var configDeleteModalEl = document.getElementById('config-delete-admin-modal');
if (configDeleteModalEl) {
    configDeleteModalEl.addEventListener('click', function (e) {
        if (e.target === configDeleteModalEl) _configDeleteFormPending = null;
    });
}
