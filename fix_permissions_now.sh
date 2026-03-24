#!/bin/bash
# Fix Server Permissions NOW - Enable Uploads
# Run this once to allow VS Code SFTP uploads

echo "=========================================="
echo "Fixing Server Permissions"
echo "=========================================="
echo ""
echo "This will fix permissions on /var/www/html"
echo "so the ubuntu user can upload files."
echo ""
echo "You'll need to enter the server password:"
echo "Password: quxqof-sYkzim-7xymva"
echo ""
read -p "Press Enter to continue..."

# SSH into server and fix permissions
ssh ubuntu@167.114.97.221 << 'ENDSSH'
echo "Step 1: Adding ubuntu user to www-data group..."
sudo usermod -a -G www-data ubuntu

echo ""
echo "Step 2: Setting directory ownership..."
sudo chown -R ubuntu:www-data /var/www/html

echo ""
echo "Step 3: Setting correct permissions..."
# Directories: rwxrwxr-x (775) - ubuntu and www-data can write
sudo find /var/www/html -type d -exec chmod 775 {} \;

# Files: rw-rw-r-- (664) - ubuntu and www-data can write
sudo find /var/www/html -type f -exec chmod 664 {} \;

# Set sticky bit so new files inherit group
sudo chmod g+s /var/www/html

echo ""
echo "Step 4: Verifying permissions..."
ls -la /var/www/html | head -10

echo ""
echo "=========================================="
echo "✅ Permissions Fixed!"
echo "=========================================="
echo ""
echo "Changes made:"
echo "  - Ubuntu user added to www-data group"
echo "  - Owner: ubuntu:www-data (you can write!)"
echo "  - Directories: 775 (rwxrwxr-x)"
echo "  - Files: 664 (rw-rw-r--)"
echo "  - Sticky bit set (new files inherit group)"
echo ""
echo "⚠️  You may need to log out and log back in"
echo "    for group changes to take effect."
echo ""
ENDSSH

if [ $? -eq 0 ]; then
    echo ""
    echo "=========================================="
    echo "✅ SUCCESS! Permissions are fixed."
    echo "=========================================="
    echo ""
    echo "Next steps:"
    echo "1. Go back to VS Code"
    echo "2. Open index.html"
    echo "3. Add a comment: <!-- test -->"
    echo "4. Save (Cmd+S)"
    echo "5. ✅ Should upload successfully now!"
    echo ""
else
    echo ""
    echo "❌ Failed to fix permissions."
    echo ""
    echo "Manual fix:"
    echo "  ssh ubuntu@167.114.97.221"
    echo "  sudo chown -R ubuntu:www-data /var/www/html"
    echo "  sudo find /var/www/html -type d -exec chmod 775 {} \;"
    echo "  sudo find /var/www/html -type f -exec chmod 664 {} \;"
    echo ""
fi
