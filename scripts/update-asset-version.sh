#!/usr/bin/env bash
# Cache busting automatique pour le site vitrine (public_html).
# Met à jour ?v= dans tous les HTML avec un timestamp unique.
# À exécuter avant chaque upload du site vitrine.
set -e
cd "$(dirname "$0")/.."
VERSION=$(date +%s)
count=0
for f in public_html/*.html public_html/tarifs.php public_html/offre.php; do
  [ -f "$f" ] || continue
  perl -i -pe "s/\?v=[0-9][0-9.]*/\?v=$VERSION/g" "$f"
  count=$((count + 1))
done
echo "Cache bust: ?v=$VERSION appliqué dans $count fichier(s) (public_html)."
