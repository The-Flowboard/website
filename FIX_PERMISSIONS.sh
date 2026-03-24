#!/bin/bash
#
# Permanent Fix for File Upload Permissions
#

echo "=========================================="
echo "JMC Website - Permanent Permissions Fix"
echo "=========================================="
echo ""
echo "This will fix permissions on the server so you can upload files."
echo ""
echo "Commands to run on server (copy and paste):"
echo ""

cat << 'EOF'
# SSH into server
ssh ubuntu@167.114.97.221

# Run these commands (paste all at once):
sudo usermod -a -G www-data ubuntu
sudo chown -R ubuntu:www-data /var/www/html
sudo find /var/www/html -type d -exec chmod 775 {} \;
sudo find /var/www/html -type f -exec chmod 664 {} \;
sudo chmod g+s /var/www/html
sudo find /var/www/html -type d -exec chmod g+s {} \;

# Verify it worked
ls -la /var/www/html/ | head -5
groups ubuntu

# Exit and reconnect for group changes to take effect
exit

# Reconnect
ssh ubuntu@167.114.97.221

# Now test uploading a file
echo "test" > /tmp/test.txt
cp /tmp/test.txt /var/www/html/test.txt
ls -la /var/www/html/test.txt

# If that worked, permissions are fixed!
rm /var/www/html/test.txt

EOF

echo ""
echo "=========================================="
