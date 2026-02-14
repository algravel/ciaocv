# Rapport securite et performance (audit code)

Ce rapport est base sur une analyse statique des fichiers du depot
`ciaocv`. Aucun acces serveur ni execution en production.

ChatGPT Codex

## 1. Securite

### 1.1 Critique

| Constat | Chemin(s) | Risque |
|---|---|---|
| Secrets versionnes (.env) | `.env` | Compromission totale en cas de fuite du depot |
| Cle de secours en dur | `app/debug-postes.php`, `gestion/clear-seed.php` | Execution d'actions sensibles si l'URL est connue |
| Script sensible public | `app/_documentation/record.php` | Operations B2 sans auth/CSRF |

### 1.2 Eleve

| Constat | Chemin(s) | Risque |
|---|---|---|
| Turnstile desactive | `app/controllers/AuthController.php` | Brute-force possible |
| 2FA (OTP) desactive | `app/controllers/AuthController.php` | Prise de compte plus facile |

### 1.3 Moyen

| Constat | Chemin(s) | Risque |
|---|---|---|
| `$_GET['id']` non type | `public_html/offre.php` | Validation faible (meme si requete preparee) |
| Base64 decode puis stocke | `app/controllers/DashboardController.php` | Verifier echappement a l'affichage |

### 1.4 Points positifs

- CSRF centralise (config + router)
- Sessions securisees (secure/httponly/samesite)
- Requetes SQL preparees
- Hash de mots de passe via bcrypt
- Chiffrement AES-256-GCM
- Headers de securite (X-Frame-Options, X-Content-Type-Options, Referrer-Policy)
- Purge cache protege par secret + hash_equals

## 2. Performance

### 2.1 Constats

| Constat | Chemin(s) | Impact |
|---|---|---|
| Chargement `.env` a chaque requete | `app/config/app.php`, `gestion/config.php`, `public_html/tarifs.php` | I/O recurrent |
| Pas de bundling/minification | `app/assets/js/`, `app/assets/css/` | Plus de requetes + poids |
| Dependances externes (fonts/icones) | Layouts | Latence reseau + indisponibilites |

### 2.2 Points positifs

- Cache busting via `filemtime()` ou `ASSET_VERSION`
- Cache navigateur via `.htaccess`
- Upload direct R2 via URLs presignees

## 3. Recommandations prioritaires

### P0 - Immediat

1. Retirer `.env` du depot
   - Ajouter a `.gitignore`
   - `git rm --cached .env`
   - Creer `.env.example`
   - Regenerer toutes les cles exposees
2. Supprimer la cle `ciaocv-debug-2025`
   - Retirer la backdoor
   - Restreindre ces scripts (auth stricte / CLI)
3. Securiser ou supprimer `app/_documentation/record.php`
   - Auth + CSRF, ou suppression si non utilise

### P1 - Court terme

4. Reactiver Turnstile sur le login
5. Reactiver le 2FA (OTP)
6. Corriger `public_html/offre.php` (typage + chemin DB)

### P2 - Moyen terme

7. Ne plus uploader `.env` via FTP
8. Optimiser le chargement `.env` (cache/opcache)
9. Bundling/minification (Vite/Webpack)
10. Ajouter une CSP
