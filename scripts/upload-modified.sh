#!/usr/bin/env bash
# Upload des fichiers modifiés (git status) vers le serveur FTP
set -e
cd "$(dirname "$0")/.."

# Charger .env
while IFS= read -r line; do
  line="${line%%#*}"
  [[ -z "$line" ]] && continue
  if [[ "$line" =~ ^([A-Za-z_][A-Za-z0-9_]*)=(.*)$ ]]; then
    key="${BASH_REMATCH[1]}"
    val="${BASH_REMATCH[2]}"
    val="${val%\"}"
    val="${val#\"}"
    val="${val%\'}"
    val="${val#\'}"
    export "$key=$val"
  fi
done < .env

[[ -z "$FTP_HOST" || -z "$FTP_USER" || -z "$FTP_PASS" ]] && { echo "FTP_HOST, FTP_USER, FTP_PASS requis"; exit 1; }

upload() {
  local local="$1"
  local remote="$2"
  echo "Upload $local -> $remote"
  curl -s -T "$local" --user "$FTP_USER:$FTP_PASS" "ftp://$FTP_HOST/$remote" --ftp-create-dirs || { echo "  ERREUR"; return 1; }
}

# Cache buster public_html
bash scripts/update-asset-version.sh 2>/dev/null || true

# Fichiers modifiés (hors scripts, .cursor)
files=(
  ".env:.env"
  "app/.htaccess:app/.htaccess"
  "app/assets/css/app.css:app/assets/css/app.css"
  "app/assets/css/rec.css:app/assets/css/rec.css"
  "app/assets/img/favicon.png:app/assets/img/favicon.png"
  "app/assets/js/app.js:app/assets/js/app.js"
  "app/assets/js/i18n.js:app/assets/js/i18n.js"
  "app/config/app.php:app/config/app.php"
  "app/controllers/AuthController.php:app/controllers/AuthController.php"
  "app/debug-postes.php:app/debug-postes.php"
  "app/controllers/DashboardController.php:app/controllers/DashboardController.php"
  "app/controllers/EntrevueController.php:app/controllers/EntrevueController.php"
  "app/controllers/FeedbackController.php:app/controllers/FeedbackController.php"
  "app/helpers/R2Signer.php:app/helpers/R2Signer.php"
  "app/includes/functions.php:app/includes/functions.php"
  "app/index.php:app/index.php"
  "app/models/Affichage.php:app/models/Affichage.php"
  "app/models/Candidat.php:app/models/Candidat.php"
  "app/models/Poste.php:app/models/Poste.php"
  "app/views/auth/login.php:app/views/auth/login.php"
  "app/views/dashboard/_billing.php:app/views/dashboard/_billing.php"
  "app/views/dashboard/index.php:app/views/dashboard/index.php"
  "app/views/layouts/app.php:app/views/layouts/app.php"
  "app/views/layouts/auth.php:app/views/layouts/auth.php"
  "app/views/layouts/rec.php:app/views/layouts/rec.php"
  "app/views/rec/index.php:app/views/rec/index.php"
  "gestion/GestionController.php:gestion/GestionController.php"
  "gestion/assets/css/app.css:gestion/assets/css/app.css"
  "gestion/assets/img/favicon.png:gestion/assets/img/favicon.png"
  "gestion/assets/js/app.js:gestion/assets/js/app.js"
  "gestion/assets/js/i18n.js:gestion/assets/js/i18n.js"
  "gestion/check-mysql-version.php:gestion/check-mysql-version.php"
  "gestion/clear-seed.php:gestion/clear-seed.php"
  "gestion/config.php:gestion/config.php"
  "gestion/index.php:gestion/index.php"
  "gestion/migrate.php:gestion/migrate.php"
  "gestion/MockData.php:gestion/MockData.php"
  "gestion/layouts/app.php:gestion/layouts/app.php"
  "gestion/layouts/auth.php:gestion/layouts/auth.php"
  "gestion/models/Entreprise.php:gestion/models/Entreprise.php"
  "gestion/models/Entrevue.php:gestion/models/Entrevue.php"
  "gestion/models/Event.php:gestion/models/Event.php"
  "gestion/models/Feedback.php:gestion/models/Feedback.php"
  "gestion/models/Plan.php:gestion/models/Plan.php"
  "gestion/models/PlatformUser.php:gestion/models/PlatformUser.php"
  "gestion/sql/schema.sql:gestion/sql/schema.sql"
  "gestion/sql/seed.php:gestion/sql/seed.php"
  "gestion/views/dashboard/index.php:gestion/views/dashboard/index.php"
  "gestion/views/login.php:gestion/views/login.php"
  "public_html/assets/js/i18n.js:public_html/assets/js/i18n.js"
  "public_html/assets/js/cookie-consent.js:public_html/assets/js/cookie-consent.js"
  "public_html/.htaccess:public_html/.htaccess"
  "public_html/confidentialite.html:public_html/confidentialite.html"
  "public_html/conditions.html:public_html/conditions.html"
  "public_html/emplois.html:public_html/emplois.html"
  "public_html/guide-candidat.html:public_html/guide-candidat.html"
  "public_html/index.html:public_html/index.html"
  "public_html/offre.php:public_html/offre.php"
  "public_html/tarifs.html:public_html/tarifs.html"
  "public_html/tarifs.php:public_html/tarifs.php"
)

for entry in "${files[@]}"; do
  local="${entry%%:*}"
  remote="${entry##*:}"
  [[ -f "$local" ]] && upload "$local" "$remote" || echo "Skip (absent): $local"
done

# Purge LSCache après déploiement
if [[ -n "$PURGE_CACHE_SECRET" ]]; then
  purge_url="${PURGE_CACHE_URL:-https://app.ciaocv.com/purge-cache}"
  echo "Purge cache: $purge_url"
  curl -s -o /dev/null -H "X-Purge-Secret: $PURGE_CACHE_SECRET" "$purge_url" && echo "  Purge OK" || echo "  Purge ERREUR"
fi

echo "Terminé."
