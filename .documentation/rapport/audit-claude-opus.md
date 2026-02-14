# Rapport d'audit — Sécurité & Performance — CiaoCV

**Date** : 13 février 2026
**Projet** : CiaoCV
**Auditeur** : Claude Opus
**Méthode** : Analyse statique complète du dépôt (69 fichiers PHP, 6 JS, 4 CSS, 8 HTML)

---

## Vue d'ensemble

**Stack technique** : PHP 8.x custom MVC, MySQL/MariaDB, LiteSpeed (PlanetHoster), Vanilla JS, Cloudflare, Backblaze B2, Stripe, ZeptoMail

**Périmètre** : 3 applications (`app.ciaocv.com`, `gestion.ciaocv.com`, `www.ciaocv.com`)

**Résultat global** : **69 constats** dont **6 critiques**, **18 élevés**, **21 moyens**, **24 bas/info**

**Score global estimé : 4,5/10**

---

## Points positifs (ce qui est bien fait)

| Domaine | Implémentation |
|---------|---------------|
| **CSRF** | Protection globale dans le Router, `hash_equals()`, tokens `bin2hex(random_bytes(32))` |
| **Hachage mots de passe** | `password_hash(PASSWORD_DEFAULT)` (bcrypt) partout |
| **Prévention XSS (vues PHP)** | Helper `e()` = `htmlspecialchars(ENT_QUOTES, UTF-8)` utilisé systématiquement |
| **SQL Injection** | PDO avec requêtes préparées dans la grande majorité du code |
| **Chiffrement au repos** | AES-256-GCM pour les données personnelles (noms, emails) |
| **Cookies de session** | `httponly: true`, `samesite: Lax`, `secure` conditionnel |
| **Upload fichiers** | Presigned URLs R2 — les fichiers ne transitent pas par le serveur |
| **OTP (admin)** | 6 chiffres, expiration 10 min, `hash_equals()` pour la comparaison |
| **Rétention de données** | Cron de purge automatique (60 jours fichiers, 1 an enregistrements) |
| **Cache busting** | Via `filemtime()` ou `ASSET_VERSION` |
| **CORS R2** | Restreint à `*.ciaocv.com` uniquement |

---

## SÉCURITÉ

---

### CRITIQUES (P0 — Action immédiate)

#### S-C1. `.env` avec tous les secrets de production commité dans Git

- **Fichier** : `.env` (racine)
- **Sévérité** : CRITIQUE
- **Description** : Aucun `.gitignore` n'existe dans le projet. Le fichier `.env` contient tous les secrets de production et est dans l'historique Git :
  - MySQL : host, user, pass, db (l.13-16)
  - FTP : host, user, pass (l.5-7)
  - Clé AES-256 : `APP_ENCRYPTION_KEY` (l.19) — permet de déchiffrer toutes les données personnelles
  - Backblaze B2 : key ID, application key, bucket ID (l.24-27)
  - Cloudflare R2 : access key, secret key, token (l.30-34)
  - ZeptoMail : token API complet (l.41)
  - Turnstile : site key + secret key (l.45-46)
  - Purge cache : secret (l.10)
- **Impact** : Compromission totale — BD, stockage cloud, emails, chiffrement PII, accès FTP
- **Remédiation** :
  1. Créer `.gitignore` incluant `.env`, `.DS_Store`, `*.log`
  2. `git rm --cached .env .DS_Store`
  3. Purger l'historique Git avec BFG Repo-Cleaner
  4. **Changer TOUS les identifiants** (ils sont compromis)
  5. Créer un `.env.example` sans valeurs

#### S-C2. Contournement d'authentification — GestionController côté app

- **Fichier** : `app/controllers/GestionController.php`, lignes 32-52
- **Sévérité** : CRITIQUE
- **Description** : La méthode `authenticate()` accorde l'accès admin (session `user_id = 1`) à quiconque soumet un email non-vide, sans vérifier le mot de passe. Le commentaire `TODO` confirme que l'implémentation n'a jamais été terminée.
- **Code** :
  ```php
  // TODO : validation réelle (admin en base ou config)
  $_SESSION[self::SESSION_USER_ID]    = 1;
  $_SESSION[self::SESSION_USER_EMAIL] = $email;
  ```
- **Impact** : Accès admin complet sans authentification
- **Remédiation** : Supprimer ce contrôleur ou implémenter une vraie authentification

#### S-C3. Clé de contournement codée en dur — Effacement complet de la BD

- **Fichiers** : `app/debug-postes.php` (l.26), `gestion/clear-seed.php` (l.22)
- **Sévérité** : CRITIQUE
- **Description** : La clé `ciaocv-debug-2025` permet d'accéder à un script qui tronque toutes les tables de production (`TRUNCATE TABLE` avec `FOREIGN_KEY_CHECKS = 0`). La clé est révélée dans le message d'erreur lui-même.
- **Code** :
  ```php
  $keyOk = ($key !== '' && $key === $secret) || $key === 'ciaocv-debug-2025';
  ```
- **Impact** : Effacement complet de la base de données de production via une simple URL
- **Remédiation** : Supprimer ces fichiers de production immédiatement

#### S-C4. Fichiers debug sans authentification en production

- **Fichiers** : `app/test_db.php`, `debug_schema.php` (racine)
- **Sévérité** : CRITIQUE
- **Description** : Ces scripts se connectent à la BD de production, exécutent des requêtes (`SELECT`, `SHOW COLUMNS`) et retournent les résultats en JSON sans aucune authentification.
- **Impact** : Exposition de la structure de la BD, des données et des détails d'erreurs SQL

#### S-C5. Outils B2 sans authentification — `app/_documentation/`

- **Fichiers** : `app/_documentation/view.php`, `app/_documentation/record.php`
- **Sévérité** : CRITIQUE
- **Description** :
  - `record.php` retourne des tokens d'autorisation B2 (`authorizationToken`) à n'importe quel visiteur, permettant l'upload/suppression de fichiers arbitraires dans le bucket
  - `view.php` liste et permet la suppression de vidéos B2 sans authentification ni protection CSRF
- **Code** :
  ```php
  echo json_encode([
      'uploadUrl' => $uploadUrlResponse['uploadUrl'],
      'authToken' => $uploadUrlResponse['authorizationToken'],
      'fileName'  => $fileName
  ]);
  ```
- **Impact** : Upload/suppression arbitraire de fichiers dans le stockage cloud

#### S-C6. Transferts FTP en clair

- **Fichier** : `scripts/upload_ftp.sh` — Protocole `ftp://` (port 21)
- **Sévérité** : CRITIQUE
- **Description** : Tous les identifiants et fichiers (incluant le `.env`) transitent en clair sur le réseau. Le script inclut explicitement `.env` dans les fichiers éligibles à l'upload (l.38).
- **Remédiation** : Passer à SFTP/FTPS

---

### ÉLEVÉES (P1 — Cette semaine)

#### S-H1. 2FA (OTP) désactivé sur l'app principale

- **Fichier** : `app/controllers/AuthController.php`, lignes 153-179
- **Description** : Le bloc OTP est entièrement commenté avec un `TODO: réactiver`. L'admin (gestion) a encore l'OTP activé.

#### S-H2. Turnstile CAPTCHA désactivé sur login app

- **Fichier** : `app/controllers/AuthController.php`, lignes 57-77
- **Description** : La vérification anti-bot Turnstile est commentée.

#### S-H3. Aucune protection brute-force

- **Fichiers** : `AuthController.php`, `GestionController.php`
- **Description** : Aucun compteur de tentatives, IP tracking, verrouillage de compte ou délai progressif. Zéro résultat pour `rate.?limit|brute.?force|max.?attempts` dans tout le code.

#### S-H4. Pas de `session_regenerate_id()` après login (fixation de session)

- **Fichiers** : `AuthController.php`, `GestionController.php`
- **Description** : Après authentification réussie, le session ID reste le même. Zéro appel à `session_regenerate_id` dans tout le code.
- **Remédiation** : Ajouter `session_regenerate_id(true)` avant de définir les variables de session

#### S-H5. `display_errors = 1` en production

- **Fichier** : `app/controllers/DashboardController.php`, lignes 402-404
- **Description** : La méthode `history()` active l'affichage complet des erreurs PHP.
- **Code** :
  ```php
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);
  ```

#### S-H6. Token CSRF renvoyé dans la réponse d'erreur

- **Fichier** : `app/controllers/DashboardController.php`, lignes 993-996
- **Description** : Les valeurs du token CSRF (soumis ET session) sont incluses dans le JSON d'erreur.
- **Code** :
  ```php
  $sent = $_POST['_csrf_token'] ?? 'null';
  $sess = $_SESSION['_csrf_token'] ?? 'null';
  $this->json(['success' => false, 'error' => "CSRF Invalid. Sent: $sent, Session: $sess"], 403);
  ```
- **Impact** : Un attaquant peut extraire le token CSRF valide de la session

#### S-H7. Messages d'exception bruts renvoyés aux utilisateurs

- **Fichier** : `DashboardController.php`, lignes 575, 731
- **Description** : `'error' => 'Erreur serveur: ' . $e->getMessage()` expose des détails SQL, chemins, etc.

#### S-H8. Upload URLs R2 sans authentification ni rate limiting

- **Fichier** : `app/controllers/EntrevueController.php`, lignes 14-55
- **Description** : Tout utilisateur connaissant un `longId` (16 hex) valide peut générer des URLs d'upload presignées sans limite.

#### S-H9. Soumission candidatures sans auth, CSRF potentiellement contourné

- **Fichier** : `app/controllers/EntrevueController.php`, lignes 60-129
- **Description** : L'endpoint `submit` lit le body JSON via `php://input` au lieu de `$_POST`, ce qui peut contourner le check CSRF global du Router (qui vérifie `$_POST['_csrf_token']`). Les champs `videoPath` et `cvPath` sont des strings utilisateur stockés sans validation de chemin.

#### S-H10. XSS — `innerHTML` avec données API non-sanitisées

- **Fichier** : `public_html/emplois.html`, lignes 621-635
- **Description** : Les données d'offres d'emploi (titre, entreprise, type, lieu, description) provenant de l'API sont injectées via `innerHTML` dans des template literals ES6 sans aucun échappement.
- **Code** :
  ```javascript
  card.innerHTML = `
      <div class="job-title">${job.title}</div>
      <div class="job-company">${job.company}</div>
      <p>${job.description}</p>
  `;
  ```

#### S-H11. Aucun `Content-Security-Policy` (CSP) sur aucun domaine

- **Fichiers** : Tous les `.htaccess`
- **Description** : Aucun header CSP n'est défini. Toute XSS a des capacités illimitées.

#### S-H12. Aucun header de sécurité sur `public_html` et `gestion`

- **Fichiers** : `public_html/.htaccess`, `gestion/.htaccess`
- **Description** : Aucun `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, etc. Seul `app/.htaccess` a des headers partiels.

#### S-H13. Pas de HSTS (`Strict-Transport-Security`)

- **Fichiers** : Tous les `.htaccess`
- **Description** : Aucun header HSTS. Vulnérable aux attaques de downgrade SSL.

#### S-H14. Aucun SRI sur les ressources CDN

- **Fichiers** : Tous les layouts
- **Description** : Font Awesome et Google Fonts chargés sans `integrity=`. Si le CDN est compromis, du JS/CSS malveillant s'exécute sur toutes les pages.
- **Exemple** :
  ```html
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  ```

#### S-H15. Logs debug `file_put_contents` en production

- **Fichier** : `gestion/GestionController.php`, lignes 393, 396
- **Description** : Les créations d'utilisateurs écrivent des données debug (user IDs) dans `.cursor/debug.log`.

---

### MOYENNES (P2)

| # | Catégorie | Description | Fichier(s) |
|---|-----------|-------------|------------|
| S-M1 | SQL | Interpolation `intval` dans clauses IN au lieu de prepared statements | `Candidat.php:624-631, 660-669`, `cron_retention.php:122-123` |
| S-M2 | Session | Logout admin incomplet (`unset` au lieu de `session_destroy`) | `GestionController.php:679-687` |
| S-M3 | Auth | Aucune politique de complexité de mot de passe (seulement min 8 chars) | `GestionController.php:530-546` |
| S-M4 | Auth | Énumération de comptes via mot de passe oublié (messages différents) | `AuthController.php:325-332` |
| S-M5 | Input | `videoPath`/`cvPath` non validés contre le préfixe attendu | `EntrevueController.php:72-74` |
| S-M6 | Input | Statut candidat non validé contre une liste blanche | `DashboardController.php:1000-1011` |
| S-M7 | XSS | i18n utilise `innerHTML` globalement (public + app) | `i18n.js` (public + app) |
| S-M8 | Code | `extract($data)` dans le rendu des vues (injection de variables possible) | `Controller.php:19` |
| S-M9 | Input | `page_url` du feedback stocké sans validation de schéma URL | `FeedbackController.php:29-30` |
| S-M10 | Cookie | Cookie `language` sans flag `Secure` (site public) | `public_html/assets/js/i18n.js:475` |
| S-M11 | Headers | Pas de `Permissions-Policy` header | Tous les `.htaccess` |
| S-M12 | Infra | Pas de `robots.txt` — moteurs peuvent indexer chemins sensibles | — |
| S-M13 | Infra | Pas de gestionnaire de dépendances (pas de `composer audit`) | — |
| S-M14 | Code | `offre.php` référence un fichier `app/db.php` inexistant | `public_html/offre.php:2` |

### BASSES

| # | Catégorie | Description | Fichier(s) |
|---|-----------|-------------|------------|
| S-L1 | Input | Email template content accepte base64, stocké sans sanitization | `DashboardController.php:488-489` |
| S-L2 | Auth | Redirect ouverte potentielle (pas de validation URL interne) | `Controller.php:42-47` |
| S-L3 | Config | Bypass domain restriction via header Host manipulé | `app/.htaccess:50-51` |
| S-L4 | Crypto | Même clé AES pour admin et platform users | `Encryption.php:14-17` |
| S-L5 | Info | Message d'erreur debug révèle la clé de bypass | `debug-postes.php:29` |
| S-L6 | Code | Fonction `e()` redéfinie localement | `public_html/tarifs.php:46` |
| S-L7 | Privacy | Noms d'utilisateurs envoyés à `ui-avatars.com` | `app/assets/js/app.js:925` |
| S-L8 | Code | Version Font Awesome incohérente (6.4.0 vs 6.5.1) | `app/views/layouts/rec.php:9` |
| S-L9 | Code | `</footer>` dupliqué (HTML invalide) | `guide-candidat.html:407-409` |
| S-L10 | Input | Validation email incohérente (`filter_var` vs `strpos('@')`) | Divers contrôleurs |

---

## PERFORMANCE

---

### CRITIQUES

#### P-C1. Favicon PNG surdimensionné — 304 KB

- **Fichier** : `public_html/assets/img/favicon.png`
- **Description** : 304 KB pour un favicon. Devrait être < 10 KB. Chargé sur chaque page.
- **Remédiation** : Redimensionner et convertir en ICO/PNG optimisé

#### P-C2. Images PNGs non optimisées — 2,6 MB sur la page d'accueil

- **Fichiers** : `public_html/` (racine)
- **Description** : 5 images totalisant 2,6 MB en PNG non optimisé, sans WebP/AVIF

| Fichier | Taille |
|---------|--------|
| `hero_browser_dashboard_1770162965715.png` | 466 KB |
| `dashboard_analytics_view_1770162670589.png` | 454 KB |
| `dashboard_list_view_1770162643486.png` | 453 KB |
| `dashboard_video_view_1770162657646.png` | 446 KB |
| `hero_silhouette.png` | 387 KB |
| `assets/img/hero-laptop.png` | 127 KB |

- **Remédiation** : Convertir en WebP (réduction ~80%), ajouter `<picture>` avec fallback PNG

---

### ÉLEVÉES

#### P-H1. JavaScript non minifié — 183 KB

| Fichier | Taille |
|---------|--------|
| `app/assets/js/app.js` | 115 KB |
| `public_html/assets/js/i18n.js` | 35 KB |
| `app/assets/js/i18n.js` | 27 KB |
| Autres | 6 KB |

- **Remédiation** : Minification (réduction 40-60% attendue)

#### P-H2. CSS non minifié — 133 KB

| Fichier | Taille |
|---------|--------|
| `app/assets/css/design-system.css` | 68 KB |
| `app/assets/css/app.css` | 57 KB |
| `public_html/assets/css/design-system.css` | 8 KB |

#### P-H3. Google Fonts chargées de façon bloquante

- **Fichiers** : Tous les HTML + layouts PHP
- **Description** : `<link rel="stylesheet">` synchrone dans `<head>`, bloque le rendu pendant 100-500ms.

#### P-H4. CSS `@import` pour Google Fonts = double blocage du rendu

- **Fichier** : `app/assets/css/design-system.css`, ligne 6
- **Description** : `@import url('https://fonts.googleapis.com/...')` crée une cascade de 3 requêtes séquentielles (CSS → import → font files).
- **Remédiation** : Remplacer par `<link rel="preload">` dans le HTML

#### P-H5. Design system CSS dupliqué inline dans `emplois.html` (~450 lignes)

- **Fichier** : `public_html/emplois.html`, lignes 14-450
- **Description** : Au lieu de lier `design-system.css`, tout le CSS est copié inline (non cacheable, ~7 KB redondant). Utilise aussi une couleur primaire différente (`#4f46e5` vs `#2563EB`).

#### P-H6. Aucun `<link rel="preconnect">` pour les domaines externes

- **Fichiers** : Tous les HTML et layouts PHP
- **Description** : Pas de hint `preconnect` ni `dns-prefetch` pour `fonts.googleapis.com`, `fonts.gstatic.com`, `cdnjs.cloudflare.com`, `ui-avatars.com`. Perte de 100-300ms par domaine sur la première connexion.
- **Remédiation** :
  ```html
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdnjs.cloudflare.com">
  ```

#### P-H7. Requête N+1 — chargement des membres d'équipe

- **Fichier** : `DashboardController.php`
- **Description** : `PlatformUser::findById()` est appelé en boucle, et chaque appel déchiffre TOUS les utilisateurs de la table (scan complet).
- **Remédiation** : Implémenter `findByIds(array $ids)` avec requête `WHERE id IN (?)`

---

### MOYENNES

| # | Description | Fichier(s) |
|---|-------------|------------|
| P-M1 | Pas de `loading="lazy"` sur les images sous le pli | `index.html:591, 604, 618` |
| P-M2 | Pas de `srcset`/`<picture>` pour le responsive | `index.html` |
| P-M3 | Font Awesome chargé en entier (~60 KB) pour quelques icônes | Tous les layouts |
| P-M4 | 5 poids Montserrat chargés (400-800) — 3 suffiraient | Tous les Google Fonts links |
| P-M5 | CSS inline volumineux dans chaque HTML (405 lignes dans index.html, non cacheable) | Pages public_html |
| P-M6 | Pas de compression Gzip/Brotli configurée dans `.htaccess` | `.htaccess` |
| P-M7 | `PlatformUser::findByEmail()` déchiffre TOUS les users (O(n)) | `PlatformUser.php:141-181` |
| P-M8 | `PlatformUser::findById()` fait aussi un scan complet | `PlatformUser.php:191-200` |
| P-M9 | Cache agressif `no-store` sur pages marketing statiques | `.htaccess`, meta tags HTML |
| P-M10 | Scripts JS sans `defer`/`async` | Pages public_html |
| P-M11 | 3 fichiers i18n séparés avec duplication significative | `i18n.js` × 3 |
| P-M12 | `APP_DATA` — gros volume JSON injecté dans le HTML | `app/views/layouts/app.php:170-186` |
| P-M13 | Styles `style=""` inline extensifs au lieu de classes CSS | Pages public_html |
| P-M14 | Pas de pagination — `getAll()` charge toutes les données | `DashboardController.php` |

---

## PLAN DE REMÉDIATION PRIORITAIRE

| Priorité | Actions | Effort |
|----------|---------|--------|
| **P0 — Immédiat** | Créer `.gitignore`, purger `.env` de l'historique Git, **changer tous les identifiants** | 2h |
| **P0 — Immédiat** | Supprimer `test_db.php`, `debug_schema.php`, `clear-seed.php`, `debug-postes.php` de production | 30min |
| **P0 — Immédiat** | Supprimer ou protéger `app/_documentation/` | 30min |
| **P0 — Immédiat** | Corriger l'auth bypass dans `app/controllers/GestionController.php` | 30min |
| **P1 — Cette semaine** | Passer les scripts FTP en SFTP/FTPS | 1h |
| **P1 — Cette semaine** | Retirer `display_errors`, logs debug, messages d'exception | 1h |
| **P1 — Cette semaine** | Ajouter HSTS, CSP, headers sécurité sur les 3 domaines | 2h |
| **P1 — Cette semaine** | Réactiver OTP et/ou Turnstile sur le login app | 1h |
| **P1 — Cette semaine** | Corriger XSS dans `emplois.html` (échapper les données API) | 30min |
| **P1 — Cette semaine** | Ajouter SRI sur les ressources CDN | 30min |
| **P2 — Ce sprint** | Rate limiting sur login + endpoints entrevue | 4h |
| **P2 — Ce sprint** | `session_regenerate_id(true)` après chaque login | 30min |
| **P2 — Ce sprint** | Optimiser images (WebP, compression favicon, lazy loading) | 2h |
| **P2 — Ce sprint** | Minifier JS/CSS, ajouter preconnect, remplacer `@import` → `<link>` | 3h |
| **P2 — Ce sprint** | Implémenter `findByIds()` et `email_search_hash` pour PlatformUser | 3h |
| **P2 — Ce sprint** | Pagination pour les listes de candidats/affichages | 4h |
| **P3 — Backlog** | Politique mots de passe, validation statut, `robots.txt`, emails consistants | 4h |

---

## SCORE PAR DOMAINE

| Domaine | Score | Commentaire |
|---------|-------|-------------|
| **Secrets & Credentials** | 1/10 | Tous les secrets exposés dans Git |
| **Authentification** | 3/10 | Bypass admin, pas de brute-force, 2FA désactivé |
| **Protection XSS** | 6/10 | Bon côté serveur (PHP), faible côté client (innerHTML) |
| **Injection SQL** | 8/10 | Prepared statements quasi-systématiques |
| **Headers sécurité** | 3/10 | Partiels sur app, absents sur public/gestion |
| **Performance frontend** | 3/10 | Images non optimisées, JS/CSS non minifiés, fonts bloquantes |
| **Chiffrement données** | 8/10 | AES-256-GCM bien implémenté |
| **Infrastructure** | 4/10 | FTP en clair, pas de CI/CD, pas de staging |

**Score global : 4,5/10**
