#!/bin/bash

##############################################################################
# JMC Website - Webhook Queue System Deployment Script
# Created: January 30, 2026
# Purpose: Deploy webhook queue system to production server
##############################################################################

echo "========================================="
echo "JMC Website - Webhook Queue Deployment"
echo "========================================="
echo ""

# Configuration
SERVER="ubuntu@167.114.97.221"
REMOTE_PATH="/var/www/html"
DB_NAME="jmc_website"
DB_USER="jmc_user"

echo "This script will deploy:"
echo "  1. Webhook queue database schema"
echo "  2. WebhookQueue PHP class"
echo "  3. Webhook processor cron script"
echo "  4. Updated documentation"
echo ""
echo "Prerequisites:"
echo "  - SSH access to production server"
echo "  - MySQL credentials for database"
echo ""
read -p "Press Enter to continue or Ctrl+C to cancel..."

##############################################################################
# STEP 1: Upload Files via SFTP
##############################################################################

echo ""
echo "STEP 1: Uploading files to production server..."
echo "----------------------------------------------"

sftp ${SERVER} << 'EOF'
cd /var/www/html
put database/webhook_queue_schema.sql database/webhook_queue_schema.sql
put php/webhook_queue.php php/webhook_queue.php
put php/process_webhook_queue.php php/process_webhook_queue.php
put php/contact_handler.php php/contact_handler.php
put php/process_assessment.php php/process_assessment.php
put CLAUDE.md CLAUDE.md
put PROGRESS_TRACKER.md PROGRESS_TRACKER.md
bye
EOF

if [ $? -eq 0 ]; then
    echo "✅ Files uploaded successfully"
else
    echo "❌ File upload failed. Please check your SSH credentials."
    exit 1
fi

##############################################################################
# STEP 2: Create Database Table
##############################################################################

echo ""
echo "STEP 2: Creating webhook_queue database table..."
echo "-----------------------------------------------"

ssh ${SERVER} << 'EOF'
cd /var/www/html
mysql -u jmc_user -p'Sphinx208!' jmc_website < database/webhook_queue_schema.sql
if [ $? -eq 0 ]; then
    echo "✅ Database table created successfully"
else
    echo "❌ Database table creation failed"
    exit 1
fi
EOF

##############################################################################
# STEP 3: Verify Database Table
##############################################################################

echo ""
echo "STEP 3: Verifying database table..."
echo "-----------------------------------"

ssh ${SERVER} << 'EOF'
mysql -u jmc_user -p'Sphinx208!' jmc_website -e "DESCRIBE webhook_queue;"
if [ $? -eq 0 ]; then
    echo "✅ Table verified successfully"
else
    echo "❌ Table verification failed"
    exit 1
fi
EOF

##############################################################################
# STEP 4: Set File Permissions
##############################################################################

echo ""
echo "STEP 4: Setting file permissions..."
echo "-----------------------------------"

ssh ${SERVER} << 'EOF'
sudo chmod 644 /var/www/html/php/webhook_queue.php
sudo chmod 755 /var/www/html/php/process_webhook_queue.php
sudo chown www-data:www-data /var/www/html/php/webhook_queue.php
sudo chown www-data:www-data /var/www/html/php/process_webhook_queue.php
echo "✅ Permissions set successfully"
EOF

##############################################################################
# STEP 5: Set Up Cron Job
##############################################################################

echo ""
echo "STEP 5: Setting up cron job..."
echo "------------------------------"

echo ""
echo "⚠️  MANUAL STEP REQUIRED:"
echo ""
echo "Run the following command on the production server:"
echo ""
echo "  crontab -e"
echo ""
echo "Then add this line:"
echo ""
echo "  */5 * * * * php /var/www/html/php/process_webhook_queue.php >> /var/log/jmc_webhooks.log 2>&1"
echo ""
echo "This will process the webhook queue every 5 minutes."
echo ""
read -p "Press Enter when you have added the cron job..."

##############################################################################
# STEP 6: Create Log File
##############################################################################

echo ""
echo "STEP 6: Creating log file..."
echo "----------------------------"

ssh ${SERVER} << 'EOF'
sudo touch /var/log/jmc_webhooks.log
sudo chown www-data:www-data /var/log/jmc_webhooks.log
sudo chmod 644 /var/log/jmc_webhooks.log
echo "✅ Log file created successfully"
EOF

##############################################################################
# STEP 7: Test Webhook Queue
##############################################################################

echo ""
echo "STEP 7: Testing webhook queue..."
echo "--------------------------------"

ssh ${SERVER} << 'EOF'
cd /var/www/html
php php/process_webhook_queue.php
if [ $? -eq 0 ]; then
    echo "✅ Webhook queue processor tested successfully"
else
    echo "❌ Webhook queue processor test failed"
    exit 1
fi
EOF

##############################################################################
# DEPLOYMENT COMPLETE
##############################################################################

echo ""
echo "========================================="
echo "✅ DEPLOYMENT COMPLETE!"
echo "========================================="
echo ""
echo "Next steps:"
echo "  1. Monitor webhook queue: tail -f /var/log/jmc_webhooks.log"
echo "  2. Check queue status:"
echo "     mysql -u jmc_user -p jmc_website -e \"SELECT status, COUNT(*) FROM webhook_queue GROUP BY status;\""
echo "  3. Test with a contact form submission"
echo ""
echo "Webhook queue system is now active!"
echo ""
