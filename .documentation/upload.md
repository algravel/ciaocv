---
trigger: always_on
---

---
description: Toujours uploader après une action ; transférer les fichiers modifiés sur le serveur FTP via curl
alwaysApply: true
---

# Upload FTP

> **IMPORTANT — Méthode unique** : Toujours utiliser `scripts/upload_ftp.sh`. Les anciens scripts (deploy_ftp.py, sync_ftp.py, upload-modified.sh, etc.) ont été supprimés. **Ne pas recréer d'autres méthodes d'upload.**

**Toujours uploader après une action** — après toute modification de fichiers, l'agent effectue l'upload sans attendre que l'utilisateur le demande.

Quand l'utilisateur dit **« upload »** (ou demande un upload), l’agent doit :

1. **Commande** : `bash scripts/upload_ftp.sh` — fait tout : cache busting, fichiers modifiés (git), upload curl, CHMOD 644, purge LSCache.
2. Pour tout envoyer : `bash scripts/upload_ftp.sh --all`.

## Configuration FTP

Les identifiants sont dans `.env` :

- `FTP_HOST`
- `FTP_USER`
- `FTP_PASS`

Utiliser ces variables (depuis l’environnement ou en les chargeant depuis `.env`) pour construire la commande curl. Ne jamais hardcoder les identifiants dans le code ou les règles.

## Exemple curl (upload d’un fichier)

```bash
curl -T <fichier_local> --user "$FTP_USER:$FTP_PASS" "ftp://$FTP_HOST/<chemin_remote>"
```

Adapter `<chemin_remote>` au répertoire cible sur le serveur (structure du projet, dossier web, etc.).

## Comportement attendu

- Transférer **uniquement les fichiers modifiés** (pas tout le projet).
- Conserver la structure des dossiers sur le serveur si nécessaire.
- Utiliser **curl** pour le transfert FTP (pas d’autre outil sauf demande explicite).
- **Après chaque upload** : exécuter `CHMOD 644` sur le fichier pour éviter les 403 Forbidden (les fichiers uploadés peuvent avoir des permissions incorrectes pour le serveur web). Commande :
  ```bash
  curl -Q "CWD /chemin/vers/dossier" -Q "SITE CHMOD 644 nomfichier" --user "$FTP_USER:$FTP_PASS" "ftp://$FTP_HOST/"
  ```
- **Purge LSCache (optionnel)** : si `PURGE_CACHE_SECRET` est défini dans `.env`, après le transfert FTP appeler l’URL de purge pour vider le cache LiteSpeed de l’ensemble des pages. URL par défaut : `https://app.ciaocv.com/purge-cache` (ou `PURGE_CACHE_URL` si défini). Commande :
  ```bash
  curl -s -H "X-Purge-Secret: $PURGE_CACHE_SECRET" "${PURGE_CACHE_URL:-https://app.ciaocv.com/purge-cache}"
  ```
  L’app expose la route `GET /purge-cache` (voir `PurgeController`). À configurer dans `.env` : `PURGE_CACHE_SECRET=<secret_fort>` (et optionnellement `PURGE_CACHE_URL` si le domaine diffère).