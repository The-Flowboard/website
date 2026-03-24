#!/bin/bash
#
# Deploy Input Validation Framework to Production
#
# Usage: ./deploy_validation_framework.sh
#
# Created: January 31, 2026
#

echo "======================================"
echo "Validation Framework Deployment Script"
echo "======================================"
echo ""

# Server details
SERVER_USER="ubuntu"
SERVER_HOST="167.114.97.221"
SERVER_PATH="/var/www/html"

echo "Target Server: ${SERVER_USER}@${SERVER_HOST}:${SERVER_PATH}"
echo ""

# Files to deploy
echo "Files to deploy:"
echo "  1. php/input_validator.php (PHP validation class)"
echo "  2. js/form-validator.js (JavaScript validation framework)"
echo "  3. js/contact-form-init.js (Contact form integration)"
echo "  4. php/contact_handler.php (Updated contact handler)"
echo "  5. contact.html (Updated with new scripts)"
echo ""

# Confirm
read -p "Proceed with deployment? (y/N) " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Deployment cancelled."
    exit 0
fi

echo ""
echo "Deploying files..."
echo ""

# Deploy PHP validation class
echo "[1/5] Deploying php/input_validator.php..."
scp php/input_validator.php ${SERVER_USER}@${SERVER_HOST}:${SERVER_PATH}/php/
if [ $? -eq 0 ]; then
    echo "✓ php/input_validator.php deployed"
else
    echo "✗ Failed to deploy php/input_validator.php"
    exit 1
fi

# Deploy JavaScript validation framework
echo "[2/5] Deploying js/form-validator.js..."
scp js/form-validator.js ${SERVER_USER}@${SERVER_HOST}:${SERVER_PATH}/js/
if [ $? -eq 0 ]; then
    echo "✓ js/form-validator.js deployed"
else
    echo "✗ Failed to deploy js/form-validator.js"
    exit 1
fi

# Deploy contact form integration
echo "[3/5] Deploying js/contact-form-init.js..."
scp js/contact-form-init.js ${SERVER_USER}@${SERVER_HOST}:${SERVER_PATH}/js/
if [ $? -eq 0 ]; then
    echo "✓ js/contact-form-init.js deployed"
else
    echo "✗ Failed to deploy js/contact-form-init.js"
    exit 1
fi

# Deploy updated contact handler
echo "[4/5] Deploying php/contact_handler.php..."
scp php/contact_handler.php ${SERVER_USER}@${SERVER_HOST}:${SERVER_PATH}/php/
if [ $? -eq 0 ]; then
    echo "✓ php/contact_handler.php deployed"
else
    echo "✗ Failed to deploy php/contact_handler.php"
    exit 1
fi

# Deploy updated contact page
echo "[5/5] Deploying contact.html..."
scp contact.html ${SERVER_USER}@${SERVER_HOST}:${SERVER_PATH}/
if [ $? -eq 0 ]; then
    echo "✓ contact.html deployed"
else
    echo "✗ Failed to deploy contact.html"
    exit 1
fi

echo ""
echo "All files deployed successfully!"
echo ""

# Set file permissions
echo "Setting file permissions..."
ssh ${SERVER_USER}@${SERVER_HOST} << 'EOF'
cd /var/www/html

# Set ownership
sudo chown www-data:www-data php/input_validator.php
sudo chown www-data:www-data php/contact_handler.php
sudo chown www-data:www-data js/form-validator.js
sudo chown www-data:www-data js/contact-form-init.js
sudo chown www-data:www-data contact.html

# Set permissions
sudo chmod 644 php/input_validator.php
sudo chmod 644 php/contact_handler.php
sudo chmod 644 js/form-validator.js
sudo chmod 644 js/contact-form-init.js
sudo chmod 644 contact.html

echo "✓ File permissions set"
EOF

echo ""
echo "======================================"
echo "Deployment Complete!"
echo "======================================"
echo ""
echo "Next steps:"
echo "  1. Test contact form: https://joshimc.com/contact.html"
echo "  2. Check server logs: sudo tail -f /var/log/apache2/error.log"
echo "  3. Monitor webhook queue: SELECT * FROM webhook_queue ORDER BY id DESC LIMIT 10;"
echo ""
echo "For detailed testing instructions, see:"
echo "  VALIDATION_FRAMEWORK_DEPLOYMENT.md"
echo ""
