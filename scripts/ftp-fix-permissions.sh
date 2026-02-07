#!/usr/bin/env bash
# Applique 755 sur les dossiers et 644 sur les fichiers via FTP pour /app (et optionnellement public_html).
# Usage: depuis la racine du projet, après avoir chargé .env (FTP_HOST, FTP_USER, FTP_PASS)
set +e
cd "$(dirname "$0")/.."
[ -n "$FTP_HOST" ] || { echo "FTP_HOST, FTP_USER, FTP_PASS requis (source .env)"; exit 1; }

apply_dir() {
  local dir="$1"
  local parent="${dir%/*}"
  local name="${dir##*/}"
  if [ -z "$parent" ] || [ "$parent" = "$dir" ]; then
    parent="."
    name="$dir"
  fi
  if [ "$parent" = "." ]; then
    curl -s -Q "SITE CHMOD 755 $name" --user "$FTP_USER:$FTP_PASS" "ftp://$FTP_HOST/" >/dev/null && echo "  CHMOD 755 $dir" || echo "  ERREUR 755 $dir"
  else
    curl -s -Q "CWD $parent" -Q "SITE CHMOD 755 $name" --user "$FTP_USER:$FTP_PASS" "ftp://$FTP_HOST/" >/dev/null && echo "  CHMOD 755 $dir" || echo "  ERREUR 755 $dir"
  fi
}

apply_file() {
  local file="$1"
  local parent="${file%/*}"
  local name="${file##*/}"
  curl -s -Q "CWD $parent" -Q "SITE CHMOD 644 $name" --user "$FTP_USER:$FTP_PASS" "ftp://$FTP_HOST/" >/dev/null && echo "  CHMOD 644 $file" || echo "  ERREUR 644 $file"
}

echo "=== app: dossiers (755) ==="
find app -type d | sort | while read -r d; do apply_dir "$d"; done
echo "=== app: fichiers (644) ==="
find app -type f | sort | while read -r f; do apply_file "$f"; done

if [ -d "public_html" ]; then
  echo "=== public_html: dossiers (755) ==="
  find public_html -type d | sort | while read -r d; do apply_dir "$d"; done
  echo "=== public_html: fichiers (644) ==="
  find public_html -type f | sort | while read -r f; do apply_file "$f"; done
fi
echo "Terminé."