#!/bin/bash
#
# Quick Fix for 403 Error
# This script helps diagnose and fix the HTML Purifier issue
#

echo "=========================================="
echo "JMC Website - 403 Error Quick Fix"
echo "=========================================="
echo ""

# Ask user which option they want
echo "Choose an option:"
echo ""
echo "1. Install HTML Purifier on server (RECOMMENDED)"
echo "2. Use temporary fallback (bypass HTML Purifier)"
echo "3. Run diagnostic script"
echo ""
read -p "Enter choice (1, 2, or 3): " choice

case $choice in
    1)
        echo ""
        echo "Installing HTML Purifier on server..."
        echo "--------------------------------------"
        echo ""
        echo "Running: ssh ubuntu@167.114.97.221"
        echo "Password: quxqof-sYkzim-7xymva"
        echo ""

        ssh ubuntu@167.114.97.221 << 'ENDSSH'
cd /var/www/html
echo "Current directory: $(pwd)"
echo ""
echo "Running composer install..."
composer install --no-dev --optimize-autoloader
echo ""
echo "Setting permissions..."
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html
echo ""
echo "Verifying HTML Purifier installation..."
if [ -d "vendor/ezyang/htmlpurifier" ]; then
    echo "✅ HTML Purifier installed successfully!"
else
    echo "❌ HTML Purifier NOT found!"
fi
echo ""
echo "Done! Try saving a blog post now."
ENDSSH
        ;;

    2)
        echo ""
        echo "Using temporary fallback..."
        echo "--------------------------------------"
        echo ""
        echo "⚠️  WARNING: This provides BASIC sanitization only!"
        echo "⚠️  Install HTML Purifier ASAP for proper security!"
        echo ""

        # Backup original
        scp ubuntu@167.114.97.221:/var/www/html/php/html_sanitizer.php \
            /Users/rushabhjoshi/Desktop/jmc-website/php/html_sanitizer_backup.php

        # Upload fallback
        scp /Users/rushabhjoshi/Desktop/jmc-website/php/html_sanitizer_fallback.php \
            ubuntu@167.114.97.221:/var/www/html/php/html_sanitizer.php

        echo ""
        echo "✅ Fallback uploaded. Try saving a blog post now."
        echo ""
        echo "To restore HTML Purifier later:"
        echo "1. Run: composer install on server"
        echo "2. Restore original: html_sanitizer_backup.php"
        ;;

    3)
        echo ""
        echo "Running diagnostic..."
        echo "--------------------------------------"
        echo ""

        # Upload diagnostic script
        scp /Users/rushabhjoshi/Desktop/jmc-website/check_htmlpurifier.php \
            ubuntu@167.114.97.221:/var/www/html/

        echo ""
        echo "✅ Diagnostic script uploaded!"
        echo ""
        echo "Visit: https://joshimc.com/check_htmlpurifier.php"
        echo ""
        echo "After viewing, delete it:"
        echo "ssh ubuntu@167.114.97.221 'rm /var/www/html/check_htmlpurifier.php'"
        ;;

    *)
        echo ""
        echo "Invalid choice. Exiting."
        exit 1
        ;;
esac

echo ""
echo "=========================================="
echo "Done!"
echo "=========================================="
