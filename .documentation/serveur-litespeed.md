# CiaoCV – Optimisation LiteSpeed (performance & sécurité)

L’application `/app` est compatible **LiteSpeed** (et Apache). Les réglages ci‑dessous sont déjà en place ou à vérifier sur le serveur.

---

## 1. Ce qui est déjà en place

### 1.1 `.htaccess` (app/.htaccess)

- **Sécurité**
  - Blocage d’accès aux fichiers sensibles : `.env`, `.log`, `.sql`, `.md`, `.json`, `.lock`, `.yml`, `.yaml`
  - En-têtes HTTP : `X-Frame-Options`, `X-Content-Type-Options`, `X-XSS-Protection`, `Referrer-Policy`, suppression de `X-Powered-By`
- **Performance**
  - Cache navigateur pour les assets (CSS, JS, images) : `Cache-Control: public, max-age=2592000` et `mod_expires` (1 mois)
  - Les requêtes vers des fichiers existants ne passent pas par PHP (RewriteCond `!-f` / `!-d`)

### 1.2 PHP (config/app.php)

- **Session**
  - Cookie de session : `HttpOnly`, `Secure` (si HTTPS), `SameSite=Lax`
  - Réduit le risque de vol de session (XSS, envoi cross-site)

---

## 2. Recommandations côté serveur LiteSpeed

### 2.1 HTTPS

- Forcer HTTPS pour `app.ciaocv.com` (redirection 301 HTTP → HTTPS) et utiliser un certificat valide (ex. Let’s Encrypt).
- En présence d’un reverse proxy (Cloudflare, load balancer), s’assurer que `X-Forwarded-Proto: https` est bien envoyé (déjà pris en compte pour le cookie `Secure`).

### 2.2 PHP (LiteSpeed / LSAPI)

- **OPcache** : activé (souvent par défaut) pour accélérer l’exécution PHP.
- **Version PHP** : 8.0+ recommandé pour performances et sécurité.
- **Limites** : `max_execution_time`, `memory_limit` et `upload_max_filesize` adaptés à l’upload de vidéos candidats si besoin.

### 2.3 LSCache (optionnel)

- Si un plugin LSCache (WordPress ou autre) ou une config LSCache pour PHP est utilisée sur le même serveur, il est possible d’exclure les chemins dynamiques de l’app (ex. `/app/` ou `app.ciaocv.com`) du cache page, tout en laissant le cache des assets statiques (déjà géré par le navigateur via `.htaccess`).
- Pour une app PHP custom comme CiaoCV, le cache navigateur des assets (déjà en place) suffit en général ; le cache de page LiteSpeed pour les URLs dynamiques n’est en général pas activé sur ce type d’app.

### 2.4 Fichiers sensibles

- Le `.env` doit être **hors de la racine web** (ex. un niveau au‑dessus de `public_html` / `app`). Le `.htaccess` bloque déjà l’accès direct aux fichiers `.env` au cas où l’un serait dans un répertoire servi.

---

## 3. Résumé

| Élément              | Statut |
|----------------------|--------|
| .htaccess compatible LiteSpeed | Oui (mod_rewrite, mod_headers, mod_expires) |
| En-têtes de sécurité | En place dans .htaccess |
| Cache des assets     | En place (1 mois) |
| Cookie session sécurisé | En place (HttpOnly, Secure, SameSite) |
| Blocage .env / fichiers sensibles | En place dans .htaccess |
| HTTPS               | À configurer / vérifier sur l’hébergement |

Avec ces réglages, l’app est **adaptée à LiteSpeed** du point de vue performance (cache statique, pas de réécriture inutile pour les assets) et **renforcée côté sécurité** (en-têtes, cookies, accès aux fichiers sensibles).
