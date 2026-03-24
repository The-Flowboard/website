#!/bin/bash

##############################################################################
# Backup System Deployment Script
#
# Purpose: Deploy backup scripts to production server and configure cron jobs
# Usage: ./DEPLOY_BACKUPS.sh
#
# Requirements:
#   - SSH access to production server
#   - Sudo privileges on production server
##############################################################################

set -e

# Configuration
SERVER_USER="ubuntu"
SERVER_HOST="167.114.97.221"
SERVER_PATH="/var/www/html"
LOCAL_SCRIPTS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "======================================"
echo "JMC Website Backup System Deployment"
echo "======================================"
echo ""

# Step 1: Upload scripts to server
echo "Step 1/6: Uploading backup scripts to server..."
echo "---"

if scp "${LOCAL_SCRIPTS_DIR}/backup_database.sh" \
       "${LOCAL_SCRIPTS_DIR}/backup_images.sh" \
       "${LOCAL_SCRIPTS_DIR}/BACKUP_README.md" \
       "${SERVER_USER}@${SERVER_HOST}:${SERVER_PATH}/scripts/"; then
    echo "✅ Scripts uploaded successfully"
else
    echo "❌ Failed to upload scripts"
    echo "Note: You may need to use VS Code SFTP extension or manual upload"
    exit 1
fi

echo ""

# Step 2: Set execute permissions
echo "Step 2/6: Setting execute permissions..."
echo "---"

ssh "${SERVER_USER}@${SERVER_HOST}" << 'EOF'
cd /var/www/html/scripts
chmod +x backup_database.sh backup_images.sh
ls -lh backup_database.sh backup_images.sh
EOF

if [ $? -eq 0 ]; then
    echo "✅ Permissions set successfully"
else
    echo "❌ Failed to set permissions"
    exit 1
fi

echo ""

# Step 3: Create backup directories
echo "Step 3/6: Creating backup directories..."
echo "---"

ssh "${SERVER_USER}@${SERVER_HOST}" << 'EOF'
# Create backup directories
sudo mkdir -p /var/backups/jmc_website/database
sudo mkdir -p /var/backups/jmc_website/images

# Set ownership
sudo chown -R ubuntu:ubuntu /var/backups/jmc_website

# Set permissions
sudo chmod -R 755 /var/backups/jmc_website

# Verify creation
ls -lhd /var/backups/jmc_website/*
EOF

if [ $? -eq 0 ]; then
    echo "✅ Backup directories created successfully"
else
    echo "❌ Failed to create backup directories"
    exit 1
fi

echo ""

# Step 4: Create log file
echo "Step 4/6: Creating log file..."
echo "---"

ssh "${SERVER_USER}@${SERVER_HOST}" << 'EOF'
# Create log file
sudo touch /var/log/jmc_backup.log

# Set ownership
sudo chown ubuntu:ubuntu /var/log/jmc_backup.log

# Set permissions
sudo chmod 644 /var/log/jmc_backup.log

# Verify creation
ls -lh /var/log/jmc_backup.log
EOF

if [ $? -eq 0 ]; then
    echo "✅ Log file created successfully"
else
    echo "❌ Failed to create log file"
    exit 1
fi

echo ""

# Step 5: Test backup scripts
echo "Step 5/6: Testing backup scripts..."
echo "---"

echo "Testing database backup..."
ssh "${SERVER_USER}@${SERVER_HOST}" << 'EOF'
cd /var/www/html/scripts
./backup_database.sh
EOF

if [ $? -eq 0 ]; then
    echo "✅ Database backup test successful"
else
    echo "⚠️  Database backup test failed (check log for details)"
fi

echo ""
echo "Testing images backup..."
ssh "${SERVER_USER}@${SERVER_HOST}" << 'EOF'
cd /var/www/html/scripts
./backup_images.sh
EOF

if [ $? -eq 0 ]; then
    echo "✅ Images backup test successful"
else
    echo "⚠️  Images backup test failed (check log for details)"
fi

echo ""

# Step 6: Configure cron jobs
echo "Step 6/6: Configuring cron jobs..."
echo "---"

ssh "${SERVER_USER}@${SERVER_HOST}" << 'EOF'
# Backup existing crontab
crontab -l > /tmp/crontab_backup_$(date +%Y%m%d_%H%M%S).txt 2>/dev/null || true

# Add cron jobs if they don't exist
(crontab -l 2>/dev/null | grep -v "backup_database.sh" | grep -v "backup_images.sh";
 echo "# JMC Website Automated Backups";
 echo "0 2 * * * /var/www/html/scripts/backup_database.sh >> /var/log/jmc_backup.log 2>&1";
 echo "0 3 * * 0 /var/www/html/scripts/backup_images.sh >> /var/log/jmc_backup.log 2>&1") | crontab -

echo "Current crontab:"
crontab -l
EOF

if [ $? -eq 0 ]; then
    echo "✅ Cron jobs configured successfully"
else
    echo "❌ Failed to configure cron jobs"
    exit 1
fi

echo ""
echo "======================================"
echo "✅ Deployment Complete!"
echo "======================================"
echo ""
echo "Summary:"
echo "- Scripts uploaded to ${SERVER_PATH}/scripts/"
echo "- Backup directories created at /var/backups/jmc_website/"
echo "- Log file created at /var/log/jmc_backup.log"
echo "- Cron jobs configured:"
echo "  - Database backup: Daily at 2 AM"
echo "  - Images backup: Weekly on Sunday at 3 AM"
echo ""
echo "View backup logs:"
echo "  ssh ${SERVER_USER}@${SERVER_HOST} 'tail -f /var/log/jmc_backup.log'"
echo ""
echo "View backups:"
echo "  ssh ${SERVER_USER}@${SERVER_HOST} 'ls -lh /var/backups/jmc_website/database/'"
echo "  ssh ${SERVER_USER}@${SERVER_HOST} 'ls -lh /var/backups/jmc_website/images/'"
echo ""
echo "For detailed documentation, see scripts/BACKUP_README.md"
echo ""
