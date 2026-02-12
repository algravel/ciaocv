#!/bin/bash
# Load environment variables
if [ -f .env ]; then
  export $(cat .env | xargs)
fi

# Function to upload a file
upload_file() {
  local local_file="$1"
  local remote_file="$2"
  
  echo "Uploading $local_file to $remote_file..."
  
  # Upload file
  curl -T "$local_file" --user "$FTP_USER:$FTP_PASS" "ftp://$FTP_HOST/$remote_file" --ftp-create-dirs
  
  if [ $? -eq 0 ]; then
    echo "Upload successful."
    # Change permissions
    # curl -Q "SITE CHMOD 644 $remote_file" --user "$FTP_USER:$FTP_PASS" "ftp://$FTP_HOST/"
  else
    echo "Upload failed for $local_file"
  fi
}

# Upload modified files
upload_file "app/assets/css/app.css" "app/assets/css/app.css"
upload_file "app/assets/js/app.js" "app/assets/js/app.js"
upload_file "app/views/dashboard/index.php" "app/views/dashboard/index.php"

# Purge cache if configured
# if [ ! -z "$PURGE_CACHE_SECRET" ]; then
#   echo "Purging cache..."
#   curl -s -H "X-Purge-Secret: $PURGE_CACHE_SECRET" "${PURGE_CACHE_URL:-https://app.ciaocv.com/purge-cache}"
# fi
