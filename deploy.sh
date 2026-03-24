#!/bin/bash

# JMC Website Deployment Script
# This script uploads changed files to the production server

HOST="167.114.97.221"
USER="ubuntu"
REMOTE_PATH="/var/www/html"

echo "==================================="
echo "JMC Website Deployment"
echo "==================================="
echo ""

# List of files to deploy
FILES=(
    "composer.json"
    ".env"
    ".gitignore"
    "php/db_config.php"
    "php/blog_api.php"
    "php/upload_blog_image.php"
    "php/update_lead_status.php"
)

echo "Files to deploy:"
for file in "${FILES[@]}"; do
    echo "  - $file"
done
echo ""

echo "NOTE: This script requires manual SFTP upload."
echo "Please use one of these methods:"
echo ""
echo "1. VS Code SFTP Extension (Recommended):"
echo "   - Open each file in VS Code"
echo "   - Save the file (Cmd+S / Ctrl+S)"
echo "   - The SFTP extension will auto-upload"
echo ""
echo "2. Manual SFTP:"
echo "   sftp ubuntu@167.114.97.221"
echo "   password: quxqof-sYkzim-7xymva"
echo "   cd /var/www/html"
echo "   put composer.json"
echo "   put .env"
echo "   (repeat for each file)"
echo ""
echo "3. SSH + Composer Install:"
echo "   ssh ubuntu@167.114.97.221"
echo "   cd /var/www/html"
echo "   composer install --no-dev --optimize-autoloader"
echo ""

echo "==================================="
