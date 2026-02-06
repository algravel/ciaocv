#!/usr/bin/env python3
"""Compare local files with FTP server to detect differences."""
import os
import ftplib
import hashlib
from datetime import datetime

def load_env():
    env = {}
    if os.path.exists('.env'):
        with open('.env', 'r') as f:
            for line in f:
                if line.strip() and not line.startswith('#'):
                    key, value = line.strip().split('=', 1)
                    env[key] = value.strip('"').strip("'")
    return env

def get_local_file_info(local_path):
    """Get file size and modification time for a local file."""
    if os.path.exists(local_path):
        stat = os.stat(local_path)
        return {
            'size': stat.st_size,
            'mtime': datetime.fromtimestamp(stat.st_mtime)
        }
    return None

def get_ftp_file_size(ftp, remote_path):
    """Get file size from FTP server."""
    try:
        return ftp.size(remote_path)
    except:
        return None

def list_local_files(local_dir):
    """List all files in a local directory recursively."""
    files = {}
    if not os.path.exists(local_dir):
        return files
    
    for root, dirs, filenames in os.walk(local_dir):
        for f in filenames:
            if f == '.DS_Store':
                continue
            local_path = os.path.join(root, f)
            rel_path = os.path.relpath(local_path, local_dir)
            files[rel_path] = get_local_file_info(local_path)
    return files

def list_ftp_files(ftp, remote_dir, base_dir=None):
    """List all files in an FTP directory recursively."""
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
                # It's a directory, recurse
                sub_files = list_ftp_files(ftp, full_path, base_dir)
                files.update(sub_files)
            else:
                files[rel_path] = {'size': size}
    except Exception as e:
        print(f"Error listing {remote_dir}: {e}")
    
    return files

def compare_directories(local_dir, remote_dir, ftp):
    """Compare local directory with FTP directory."""
    print(f"\n{'='*60}")
    print(f"Comparing: {local_dir} <-> {remote_dir}")
    print('='*60)
    
    local_files = list_local_files(local_dir)
    ftp_files = list_ftp_files(ftp, remote_dir)
    
    all_files = set(local_files.keys()) | set(ftp_files.keys())
    
    only_local = []
    only_remote = []
    different = []
    same = []
    
    for f in sorted(all_files):
        local_info = local_files.get(f)
        remote_info = ftp_files.get(f)
        
        if local_info and not remote_info:
            only_local.append(f)
        elif remote_info and not local_info:
            only_remote.append(f)
        elif local_info['size'] != remote_info['size']:
            different.append((f, local_info['size'], remote_info['size']))
        else:
            same.append(f)
    
    # Report results
    if only_local:
        print(f"\n🆕 Fichiers LOCAUX SEULEMENT ({len(only_local)}):")
        for f in only_local:
            print(f"   + {f}")
    
    if only_remote:
        print(f"\n🌐 Fichiers SERVEUR SEULEMENT ({len(only_remote)}):")
        for f in only_remote:
            print(f"   - {f}")
    
    if different:
        print(f"\n⚠️  Fichiers DIFFÉRENTS ({len(different)}):")
        for f, local_size, remote_size in different:
            diff = local_size - remote_size
            diff_str = f"+{diff}" if diff > 0 else str(diff)
            print(f"   ~ {f} (local: {local_size}B, serveur: {remote_size}B, diff: {diff_str}B)")
    
    if same:
        print(f"\n✅ Fichiers IDENTIQUES (même taille): {len(same)}")
    
    return {
        'only_local': only_local,
        'only_remote': only_remote,
        'different': different,
        'same': same
    }

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
        print("Connecté!\n")

        # Compare both directories
        results = {}
        results['public_html'] = compare_directories('public_html', 'public_html', ftp)
        results['app'] = compare_directories('app', 'app', ftp)
        
        # Summary
        print(f"\n{'='*60}")
        print("📊 RÉSUMÉ")
        print('='*60)
        
        total_local = sum(len(r['only_local']) for r in results.values())
        total_remote = sum(len(r['only_remote']) for r in results.values())
        total_diff = sum(len(r['different']) for r in results.values())
        total_same = sum(len(r['same']) for r in results.values())
        
        if total_local == 0 and total_remote == 0 and total_diff == 0:
            print("\n✅ Tous les fichiers sont synchronisés (même taille)!")
        else:
            print(f"\n   🆕 Fichiers uniquement en local: {total_local}")
            print(f"   🌐 Fichiers uniquement sur serveur: {total_remote}")
            print(f"   ⚠️  Fichiers différents: {total_diff}")
            print(f"   ✅ Fichiers identiques: {total_same}")
            
            if total_local > 0 or total_diff > 0:
                print(f"\n💡 Pour synchroniser: python deploy_ftp.py")

        ftp.quit()
        
    except Exception as e:
        print(f"Erreur: {str(e)}")

if __name__ == "__main__":
    main()
