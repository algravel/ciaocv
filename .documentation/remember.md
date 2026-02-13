# Mémo technique – CiaoCV

Documentation de tout ce qui peut être utile pour les prochaines interventions (agent ou dev).

---

## Upload FTP

- **Commande** : `bash scripts/upload_ftp.sh` (fichiers modifiés détectés via git).
- **Tout envoyer** : `bash scripts/upload_ftp.sh --all`.
- Ne pas créer d’autres scripts ou méthodes d’upload (voir `.documentation/upload.md`).

---

## i18n (FR/EN)

- **Fichier** : `app/assets/js/i18n.js` — objets `translations.fr` et `translations.en`.
- **Langue** : `getLanguage()` → `localStorage.getItem('language')` ou langue navigateur (fr/en). `setLanguage(lang)` enregistre dans localStorage **et** dans le cookie `language` (pour le serveur).
- **Attributs HTML** :
  - `data-i18n="cle"` → le texte de l’élément est remplacé par la traduction (innerHTML). Pour les `<option>`, idem.
  - `data-i18n-placeholder="cle"` → attribut `placeholder` des inputs/textarea.
  - `data-i18n-title="cle"` → attribut `title`.
- **Exécution** : au `DOMContentLoaded`, `updateContent()` parcourt tous les `[data-i18n]` et applique la langue courante. Après changement de langue, l’événement personnalisé **`i18n-updated`** est émis.
- **Contenu généré en JS** : utiliser `translations[getLanguage()]` (ou `translations[lang]`) et la clé pour obtenir le libellé ; après injection en DOM, les éléments avec `data-i18n` déjà présents sont mis à jour au prochain `updateContent()`. Pour les blocs rendus dynamiquement (ex. liste évaluateurs, select statut), soit ajouter `data-i18n` sur les nœuds créés, soit réagir à `i18n-updated` et re-render (ex. `renderEvaluateurs()`, mise à jour des options du select statut).
- **Layout** : `app/views/layouts/app.php` met `<html lang="...">` selon le cookie `language` pour le premier rendu.

---

## Courriels candidats (notify)

- **Variables de modèle** : `{{nom_candidat}}`, `{{titre_poste}}`, `{{nom_entreprise}}`. Remplacées côté serveur dans `DashboardController::notifyCandidats()` avant envoi (pour chaque candidat : nom, titre du poste de l’affichage, nom entreprise).
- **Modèles par défaut** : `app/models/EmailTemplate.php` → `defaultTemplates()`. Utilisés uniquement quand l’utilisateur n’a **aucun** modèle en base (insert initial).
- **Migration modèles** : dans `gestion/migrate.php`, un bloc peut mettre à jour le contenu des modèles existants (ex. « Invitation à l'entrevue vidéo » / « Invitation 2e entrevue ») pour un libellé unifié.
- **Envoi** : `gestion/config.php` → `zeptomail_send_candidate_notification()`. Le corps reçu contient déjà la salutation ; la regex enlève « Bonjour [nom], » du corps pour éviter le doublon. Sujet : « Votre candidature – message de l'équipe recrutement » (sans « – CiaoCV »). Titre dans le mail : « Message concernant votre candidature » (centré, marge dessous). Contenu : aligné à gauche, un seul saut de ligne entre paragraphes (`\n{2,}` → `\n`).

---

## Statut d’affichage (select + BDD)

- **Valeurs front** : `actif`, `termine`, `archive` (attribut `value` des `<option>`).
- **Valeurs BDD** : `active`, `paused`, `closed` (colonne `status` de `app_affichages`).
- **Correspondance** : `actif` → `active`, `termine` → `paused`, `archive` → `closed`.
- **Persistance** : `POST /affichages/update` avec `affichage_id` et `status` (valeurs front). Contrôleur : `DashboardController::updateAffichageStatus()`, modèle : `Affichage::updateStatus()`.
- **Valeur du select à l’ouverture** : prendre `data.statusClass` (ex. `status-active`, `status-paused`, `status-closed`, `status-expired`) pour définir `select.value`, **pas** le libellé `data.status` (pour éviter les ambiguïtés « Non actif » / « Expiré »).
- **Quand statut = Terminé** : afficher l’alerte « Cet affichage est terminé… » et **masquer** la carte `#affichage-evaluateurs-card` (`display: none`). À appliquer dans `showAffichageDetail()` et `updateAffichageStatus()` (et en cas de revert après erreur).

---

## Sections dashboard & navigation

- **Sections** : `div.content-section` avec id `*-section` (ex. `affichages-section`, `affichage-candidats-section`, `candidate-detail-section`, `parametres-section`). Une seule a la classe `.active`.
- **Affichage en cours** : `window._currentAffichageId` est défini quand on ouvre le détail d’un affichage (utilisé pour notify, statut, évaluateurs).
- **Données** : `affichagesData` initialisé depuis `APP_DATA.affichages` ; `affichageCandidats` depuis `APP_DATA.candidatsByAff` (et mises à jour en JS).

---

## Backend (app)

- **Routes** : `app/index.php` — GET pour pages, POST pour actions (ex. `/affichages/update`, `/candidats/notify`).
- **Contrôleurs** : `app/controllers/` ; réponses JSON via `$this->json($data, $status)`.
- **Modèles** : `app/models/` (Poste, Affichage, Candidat, EmailTemplate). Certaines actions chargent `gestion/config.php` pour DB, Zepto, modèle Entreprise.
- **CSRF** : champ `_csrf_token` en POST ; `csrf_verify()` en début d’action pour les mutations.
- **Évaluateurs** : `requireNotEvaluateur()` dans le contrôleur pour réserver certaines actions au propriétaire (pas aux évaluateurs invités).

---

## Modals

- Overlay : `id="*-modal"` (ex. `notify-candidats-modal`). Ouvrir : `openModal('notify-candidats')` ajoute `.active` au overlay. Fermer : `closeModal('notify-candidats')` ou clic sur l’overlay.

---

## Fichiers clés

| Rôle | Fichier |
|------|--------|
| Routes app | `app/index.php` |
| i18n | `app/assets/js/i18n.js` |
| Logique dashboard | `app/assets/js/app.js` |
| Vues dashboard | `app/views/dashboard/index.php`, `app/views/dashboard/_communications.php` |
| Layout app | `app/views/layouts/app.php` |
| Notify + statut affichage | `app/controllers/DashboardController.php` |
| Modèles affichage / email | `app/models/Affichage.php`, `app/models/EmailTemplate.php` |
| Envoi mail candidat | `gestion/config.php` (zeptomail_send_candidate_notification) |
| Migrations / seeds | `gestion/migrate.php` |

---

## Conventions rapides

- **Nouveau texte à traduire** : ajouter la clé dans `translations.fr` et `translations.en`, puis `data-i18n="cle"` (ou placeholder/title) dans le HTML, ou utiliser `translations[getLanguage()].cle` dans le JS pour le contenu injecté.
- **Nouvelle route POST** : ajouter dans `app/index.php`, méthode dans le contrôleur avec `csrf_verify()`, et si besoin un modèle (ex. `Affichage::updateStatus`).
- **Erreur réseau côté fetch** : gérer `!response.ok` et `response.json()` qui peut échouer si le serveur renvoie du HTML ; afficher un message explicite (ex. « Erreur serveur (code X) » vs « Erreur réseau »).
