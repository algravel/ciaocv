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

# Upload legal files
upload_file "public_html/confidentialite.html" "public_html/confidentialite.html"
upload_file "public_html/conditions.html" "public_html/conditions.html"
upload_file "public_html/assets/js/i18n.js" "public_html/assets/js/i18n.js"
