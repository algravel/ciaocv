import os
import ftplib
import time

def load_env():
    env = {}
    if os.path.exists('.env'):
        with open('.env', 'r') as f:
            for line in f:
                if line.strip() and not line.startswith('#'):
                    key, value = line.strip().split('=', 1)
                    env[key] = value.strip('"').strip("'")
    return env

def upload_directory(ftp, local_dir, remote_dir):
    if not os.path.exists(local_dir):
        print(f"Skipping {local_dir} (does not exist)")
        return

    print(f"Uploading {local_dir} to {remote_dir}...")
    
    # Create remote dir if it doesn't exist
    try:
        ftp.mkd(remote_dir)
    except ftplib.error_perm:
        pass # Directory probably exists

    # Walk through local directory
    for root, dirs, files in os.walk(local_dir):
        # Create corresponding remote paths
        rel_path = os.path.relpath(root, local_dir)
        remote_path = os.path.join(remote_dir, rel_path)
        if rel_path == '.':
            remote_path = remote_dir
        
        # Create directories
        for d in dirs:
            remote_subdir = os.path.join(remote_path, d)
            try:
                ftp.mkd(remote_subdir)
                print(f"Created directory: {remote_subdir}")
            except ftplib.error_perm:
                pass # Exists

        # Upload files
        for f in files:
            if f == '.DS_Store': continue # Skip .DS_Store but allow .htaccess
            
            local_file = os.path.join(root, f)
            remote_file = os.path.join(remote_path, f)
            
            print(f"Uploading {f}...")
            with open(local_file, 'rb') as fp:
                ftp.storbinary(f'STOR {remote_file}', fp)

def main():
    env = load_env()
    host = env.get('FTP_HOST')
    user = env.get('FTP_USER')
    password = env.get('FTP_PASS')

    if not all([host, user, password]):
        print("Error: Missing FTP credentials in .env")
        return

    print(f"Connecting to {host} as {user}...")
    try:
        ftp = ftplib.FTP(host)
        ftp.login(user, password)
        print("Connected!")

        # Upload public_html
        upload_directory(ftp, 'public_html', 'public_html')
        
        # Upload app
        upload_directory(ftp, 'app', 'app')

        # Upload specific gestion files (NOT .htaccess — server has its own)
        gestion_files = [
            'gestion/config.php',
            'gestion/models/Entrevue.php',
            'gestion/models/Event.php',
            'gestion/models/Entreprise.php',
            'gestion/models/PlatformUser.php',
            'gestion/models/Plan.php',
        ]
        for gf in gestion_files:
            if os.path.exists(gf):
                print(f"Uploading {gf}...")
                # Ensure remote directory exists
                remote_dir = os.path.dirname(gf)
                for part in remote_dir.split('/'):
                    try:
                        ftp.mkd(part if remote_dir.count('/') == 0 else remote_dir)
                    except ftplib.error_perm:
                        pass
                with open(gf, 'rb') as fp:
                    ftp.storbinary(f'STOR {gf}', fp)

        ftp.quit()
        print("Deployment complete!")
        
    except Exception as e:
        print(f"Deployment failed: {str(e)}")

if __name__ == "__main__":
    main()
