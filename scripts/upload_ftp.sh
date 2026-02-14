#!/usr/bin/env bash
# Upload FTP conforme à .documentation/upload.md
# Méthode officielle : curl, fichiers modifiés (git), CHMOD 644 après chaque upload.
set -e
cd "$(dirname "$0")/.."

# Charger .env
while IFS= read -r line; do
  line="${line%%#*}"
  [[ -z "$line" ]] && continue
  if [[ "$line" =~ ^([A-Za-z_][A-Za-z0-9_]*)=(.*)$ ]]; then
    key="${BASH_REMATCH[1]}"
    val="${BASH_REMATCH[2]}"
    val="${val%\"}"; val="${val#\"}"
    val="${val%\'}"; val="${val#\'}"
    export "$key=$val"
  fi
done < .env

[[ -z "$FTP_HOST" || -z "$FTP_USER" || -z "$FTP_PASS" ]] && { echo "FTP_HOST, FTP_USER, FTP_PASS requis dans .env"; exit 1; }

# 1. Cache busting site vitrine
echo "=== Cache busting ==="
bash scripts/update-asset-version.sh 2>/dev/null || true

# 2. Fichiers modifiés (git status) — app, gestion, public_html, .env uniquement
echo "=== Fichiers à uploader ==="
files=()
while IFS= read -r f; do
  [[ -z "$f" ]] && continue
  # Ignorer scripts, .cursor, tests, etc.
  [[ "$f" == scripts/* ]] && continue
  [[ "$f" == .cursor/* ]] && continue
  [[ "$f" == *.md ]] && continue
  [[ "$f" == .git* ]] && continue
  [[ -f "$f" ]] || continue
  # Inclure app/, gestion/, public_html/, .env
  [[ "$f" == app/* || "$f" == gestion/* || "$f" == public_html/* || "$f" == .env ]] || continue
  files+=("$f")
done < <(git status --porcelain | awk '{print $2}')

# Mode --all : tous les fichiers uploadables
if [[ "${1:-}" == "--all" ]]; then
  files=()
  [[ -f .env ]] && files+=(".env")
  while IFS= read -r f; do files+=("$f"); done < <(find app gestion public_html -type f 2>/dev/null | grep -v '\.md$' || true)
fi

if [[ ${#files[@]} -eq 0 ]]; then
  echo "Aucun fichier modifié à uploader. Utilisez --all pour tout envoyer."
  exit 0
fi

# 3. Upload + CHMOD 644 pour chaque fichier
upload() {
  local local_path="$1"
  local remote_path="$1"
  echo "Upload $local_path"
  if ! curl -s -T "$local_path" --user "$FTP_USER:$FTP_PASS" "ftp://$FTP_HOST/$remote_path" --ftp-create-dirs; then
    echo "  ERREUR upload"; return 1
  fi
  # CHMOD 644 après upload (éviter 403 Forbidden) — essayer 644 puis 0644 selon serveur FTP
  local parent="${remote_path%/*}"
  local name="${remote_path##*/}"
  local chmod_ok=0
  if [[ "$parent" != "$remote_path" ]]; then
    curl -s -Q "CWD /$parent" -Q "SITE CHMOD 644 $name" --user "$FTP_USER:$FTP_PASS" "ftp://$FTP_HOST/" >/dev/null && chmod_ok=1
    [[ $chmod_ok -eq 0 ]] && curl -s -Q "CWD /$parent" -Q "SITE CHMOD 0644 $name" --user "$FTP_USER:$FTP_PASS" "ftp://$FTP_HOST/" >/dev/null && chmod_ok=1
  else
    curl -s -Q "SITE CHMOD 644 $name" --user "$FTP_USER:$FTP_PASS" "ftp://$FTP_HOST/" >/dev/null && chmod_ok=1
    [[ $chmod_ok -eq 0 ]] && curl -s -Q "SITE CHMOD 0644 $name" --user "$FTP_USER:$FTP_PASS" "ftp://$FTP_HOST/" >/dev/null && chmod_ok=1
  fi
  [[ $chmod_ok -eq 1 ]] && echo "  CHMOD 644 OK" || echo "  CHMOD 644 (ignoré)"
}

for f in "${files[@]}"; do
  upload "$f" || true
done

# 4. Purge LSCache si configuré
if [[ -n "${PURGE_CACHE_SECRET:-}" ]]; then
  purge_url="${PURGE_CACHE_URL:-https://app.ciaocv.com/purge-cache}"
  echo "=== Purge cache ==="
  curl -s -o /dev/null -H "X-Purge-Secret: $PURGE_CACHE_SECRET" "$purge_url" && echo "Purge OK" || echo "Purge ERREUR"
fi

echo "Terminé."
