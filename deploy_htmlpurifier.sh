#!/bin/bash
#
# HTML Purifier Deployment Script
# Deploys XSS protection to production server
#
# Usage: ./deploy_htmlpurifier.sh
#

set -e  # Exit on error

# Configuration
SERVER="ubuntu@167.114.97.221"
REMOTE_PATH="/var/www/html"
LOCAL_PATH="/Users/rushabhjoshi/Desktop/jmc-website"

echo "=========================================="
echo "HTML Purifier Deployment Script"
echo "=========================================="
echo ""

# Check if files exist locally
echo "Checking local files..."
FILES_TO_UPLOAD=(
    "composer.json"
    "php/html_sanitizer.php"
    "admin/ajax/save_blog.php"
    "php/blog_api.php"
)

for file in "${FILES_TO_UPLOAD[@]}"; do
    if [ ! -f "$LOCAL_PATH/$file" ]; then
        echo "❌ ERROR: File not found: $file"
        exit 1
    fi
    echo "✅ Found: $file"
done

echo ""
echo "All files found locally."
echo ""

# Upload files via SCP
echo "Uploading files to production server..."
echo "(You will be prompted for password: quxqof-sYkzim-7xymva)"
echo ""

for file in "${FILES_TO_UPLOAD[@]}"; do
    echo "Uploading $file..."
    scp "$LOCAL_PATH/$file" "$SERVER:$REMOTE_PATH/$file"

    if [ $? -eq 0 ]; then
        echo "✅ Uploaded: $file"
    else
        echo "❌ Failed to upload: $file"
        exit 1
    fi
done

echo ""
echo "=========================================="
echo "Files uploaded successfully!"
echo "=========================================="
echo ""
echo "⚠️  IMPORTANT: Next Steps"
echo ""
echo "1. SSH into the server:"
echo "   ssh $SERVER"
echo ""
echo "2. Run composer install:"
echo "   cd $REMOTE_PATH"
echo "   composer install --no-dev --optimize-autoloader"
echo ""
echo "3. Set permissions:"
echo "   sudo chown -R www-data:www-data $REMOTE_PATH"
echo "   sudo chmod -R 755 $REMOTE_PATH"
echo ""
echo "4. Verify HTML Purifier is installed:"
echo "   ls -la vendor/ezyang/htmlpurifier"
echo ""
echo "5. Test blog creation in admin dashboard"
echo ""
echo "=========================================="
echo "Deployment script completed!"
echo "=========================================="
