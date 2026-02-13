/**
 * CiaoCV – JavaScript Administration (gestion)
 * Tableau de bord admin : ventes, forfaits, utilisateurs, configuration, bugs.
 * Pas de postes/affichages/candidats (réservés à l'app employeur).
 */

/* ═══════════════════════════════════════════════
   HELPERS
   ═══════════════════════════════════════════════ */
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
    var featuresEl = document.getElementById('forfait-edit-features');
    var isPopularEl = document.getElementById('forfait-edit-is-popular');
    if (!idEl || !statusEl) return;
    idEl.value = row.dataset.planId || '';
    statusEl.value = row.dataset.active === '1' ? '1' : '0';
    if (nameFrEl) nameFrEl.value = row.dataset.nameFr || '';
    if (nameEnEl) nameEnEl.value = row.dataset.nameEn || '';
    if (videoEl) videoEl.value = row.dataset.videoLimit || '';
    if (priceMoEl) priceMoEl.value = row.dataset.priceMonthly || '';
    if (priceYrEl) priceYrEl.value = row.dataset.priceYearly || '';
    if (featuresEl) {
        try {
            var features = JSON.parse(row.dataset.features || '[]');
            featuresEl.value = Array.isArray(features) ? features.join('\n') : '';
        } catch (_) { featuresEl.value = ''; }
    }
    if (isPopularEl) isPopularEl.checked = row.dataset.isPopular === '1';
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

/* Employer code removed - postes/affichages/candidats are in app only */
var departmentsData = [];
var teamMembersData = [];

/* ═══════════════════════════════════════════════
   DÉPARTEMENTS (paramètres admin)
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
    var fbType = form.querySelector('input[name="feedback_type"]:checked');
    if (fbType && fbType.value === 'problem' && window.location && window.location.href) {
        formData.append('page_url', window.location.href);
    }
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

// Ouvrir le modal de détail feedback (clic sur la date)
document.getElementById('feedback-table') && document.getElementById('feedback-table').addEventListener('click', function (e) {
    var cell = e.target.closest('.cell-date.cell-clickable');
    if (!cell) return;
    var row = cell.closest('tr');
    if (!row || !row.dataset.feedbackData) return;
    try {
        var data = JSON.parse(row.dataset.feedbackData);
        openFeedbackDetailModal(data, row);
    } catch (_) {}
});

function openFeedbackDetailModal(data, row) {
    var statusLabels = { new: 'Nouveau', in_progress: 'En cours', resolved: 'Réglé' };
    var typeLabel = (data.type === 'idea') ? 'Idée' : 'Bug';
    var sourceLabel = (data.source === 'gestion') ? 'Gestion' : 'App';
    var content = document.getElementById('feedback-detail-content');
    if (content) {
        var pageUrlHtml = (data.type === 'problem' && data.page_url) ? '<p><strong>Page:</strong> <a href="' + escapeHtml(data.page_url) + '" target="_blank" rel="noopener">' + escapeHtml(data.page_url) + '</a></p>' : '';
        content.innerHTML = '<div class="feedback-detail-readonly">' +
            '<p><strong>' + (typeof translations !== 'undefined' && translations.fr ? 'Date' : 'Date') + ':</strong> ' + escapeHtml(data.created_at || '') + '</p>' +
            '<p><strong>Type:</strong> ' + escapeHtml(typeLabel) + '</p>' +
            '<p><strong>Source:</strong> ' + escapeHtml(sourceLabel) + '</p>' +
            '<p><strong>Utilisateur:</strong> ' + escapeHtml(data.user_name || data.user_email || '—') + '</p>' +
            pageUrlHtml +
            '<p><strong>Message:</strong></p><div class="feedback-detail-message">' + escapeHtml(data.message || '') + '</div>' +
            '</div>';
    }
    document.getElementById('feedback-detail-id').value = data.id || '';
    document.getElementById('feedback-detail-status').value = data.status || 'new';
    document.getElementById('feedback-detail-internal-note').value = data.internal_note || '';
    window._feedbackDetailRow = row;
    openModal('feedback-detail');
}

function saveFeedbackDetail(e) {
    e.preventDefault();
    var form = document.getElementById('feedback-detail-form');
    if (!form) return;
    var submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Enregistrement...'; }
    var formData = new FormData(form);
    var basePath = (typeof APP_DATA !== 'undefined' && APP_DATA.basePath) ? APP_DATA.basePath : '';
    fetch(basePath + '/feedback/update', { method: 'POST', body: formData })
    .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
    .then(function (res) {
        if (res.data.ok) {
            var row = window._feedbackDetailRow;
            if (row && row.dataset.feedbackData) {
                try {
                    var d = JSON.parse(row.dataset.feedbackData);
                    d.status = form.querySelector('[name="status"]').value;
                    d.internal_note = form.querySelector('[name="internal_note"]').value;
                    row.dataset.feedbackData = JSON.stringify(d);
                    var statusLabels = { new: 'Nouveau', in_progress: 'En cours', resolved: 'Réglé' };
                    var st = d.status || 'new';
                    var statusClass = st === 'resolved' ? 'status-active' : (st === 'in_progress' ? 'status-pending' : 'status-paused');
                    var statusTd = row.querySelector('td:last-child');
                    if (statusTd) statusTd.innerHTML = '<span class="status-badge ' + statusClass + '">' + escapeHtml(statusLabels[st] || 'Nouveau') + '</span>';
                } catch (_) {}
            }
            closeModal('feedback-detail');
        } else {
            alert(res.data.error || 'Erreur lors de l\'enregistrement');
        }
    })
    .catch(function () { alert('Une erreur est survenue.'); })
    .finally(function () {
        if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = (typeof translations !== 'undefined' && translations.fr ? translations.fr.btn_save : 'Enregistrer'); }
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
