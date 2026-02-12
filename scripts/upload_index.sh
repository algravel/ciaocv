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
  else
    echo "Upload failed for $local_file"
  fi
}

# Upload modified file
upload_file "app/views/dashboard/index.php" "app/views/dashboard/index.php"
upload_file "app/assets/js/app.js" "public_html/assets/js/app.js"
