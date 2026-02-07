#!/usr/bin/env python3
"""Sync FTP: Delete server-only files and upload local changes."""
import os
import ftplib

def load_env():
    env = {}
    if os.path.exists('.env'):
        with open('.env', 'r') as f:
            for line in f:
                if line.strip() and not line.startswith('#'):
                    key, value = line.strip().split('=', 1)
                    env[key] = value.strip('"').strip("'")
    return env

def list_local_files(local_dir):
    files = set()
    if not os.path.exists(local_dir):
        return files
    for root, dirs, filenames in os.walk(local_dir):
        for f in filenames:
            if f == '.DS_Store':
                continue
            local_path = os.path.join(root, f)
            rel_path = os.path.relpath(local_path, local_dir)
            files.add(rel_path)
    return files

def list_ftp_files(ftp, remote_dir, base_dir=None):
    if base_dir is None:
        base_dir = remote_dir
    files = {}
    try:
        items = []
        ftp.retrlines(f'LIST {remote_dir}', items.append)
        for item in items:
            parts = item.split(None, 8)
            if len(parts) < 9:
                continue
            permissions = parts[0]
            size = int(parts[4])
            name = parts[8]
            if name in ['.', '..', '.DS_Store']:
                continue
            full_path = f"{remote_dir}/{name}"
            rel_path = os.path.relpath(full_path, base_dir)
            if permissions.startswith('d'):
                sub_files = list_ftp_files(ftp, full_path, base_dir)
                files.update(sub_files)
            else:
                files[rel_path] = {'size': size, 'full_path': full_path}
    except Exception as e:
        print(f"Error listing {remote_dir}: {e}")
    return files

def delete_ftp_file(ftp, path):
    try:
        ftp.delete(path)
        return True
    except Exception as e:
        print(f"  Error deleting {path}: {e}")
        return False

def upload_file(ftp, local_path, remote_path):
    try:
        # Ensure parent directory exists
        parent = os.path.dirname(remote_path)
        if parent:
            try:
                ftp.mkd(parent)
            except:
                pass
        with open(local_path, 'rb') as fp:
            ftp.storbinary(f'STOR {remote_path}', fp)
        return True
    except Exception as e:
        print(f"  Error uploading {local_path}: {e}")
        return False

def sync_directory(ftp, local_dir, remote_dir):
    print(f"\n{'='*60}")
    print(f"Syncing: {local_dir} -> {remote_dir}")
    print('='*60)
    
    local_files = list_local_files(local_dir)
    ftp_files = list_ftp_files(ftp, remote_dir)
    
    # Find files to delete (on server but not local)
    to_delete = []
    for rel_path, info in ftp_files.items():
        if rel_path not in local_files:
            to_delete.append((rel_path, info['full_path']))
    
    # Find files to upload (different size or local only)
    to_upload = []
    for rel_path in local_files:
        local_path = os.path.join(local_dir, rel_path)
        local_size = os.path.getsize(local_path)
        remote_info = ftp_files.get(rel_path)
        
        if remote_info is None or remote_info['size'] != local_size:
            to_upload.append((rel_path, local_path, f"{remote_dir}/{rel_path}"))
    
    # Delete server-only files
    if to_delete:
        print(f"\n🗑️  Suppression de {len(to_delete)} fichiers du serveur...")
        for rel_path, full_path in to_delete:
            print(f"   Deleting: {rel_path}")
            delete_ftp_file(ftp, full_path)
    
    # Upload changed/new files
    if to_upload:
        print(f"\n📤 Upload de {len(to_upload)} fichiers...")
        for rel_path, local_path, remote_path in to_upload:
            print(f"   Uploading: {rel_path}")
            upload_file(ftp, local_path, remote_path)
    
    if not to_delete and not to_upload:
        print("\n✅ Déjà synchronisé!")
    
    return len(to_delete), len(to_upload)

def main():
    env = load_env()
    host = env.get('FTP_HOST')
    user = env.get('FTP_USER')
    password = env.get('FTP_PASS')

    if not all([host, user, password]):
        print("Error: Missing FTP credentials in .env")
        return

    print(f"Connexion à {host}...")
    try:
        ftp = ftplib.FTP(host)
        ftp.login(user, password)
        print("Connecté!")

        total_deleted = 0
        total_uploaded = 0
        
        # Sync public_html
        d, u = sync_directory(ftp, 'public_html', 'public_html')
        total_deleted += d
        total_uploaded += u
        
        # Sync app
        d, u = sync_directory(ftp, 'app', 'app')
        total_deleted += d
        total_uploaded += u

        print(f"\n{'='*60}")
        print("📊 RÉSUMÉ")
        print('='*60)
        print(f"   🗑️  Fichiers supprimés: {total_deleted}")
        print(f"   📤 Fichiers uploadés: {total_uploaded}")
        print("\n✅ Synchronisation terminée!")

        ftp.quit()
        
    except Exception as e:
        print(f"Erreur: {str(e)}")

if __name__ == "__main__":
    main()
