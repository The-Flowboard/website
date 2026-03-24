# JMC Website Backup System

## Overview

Automated backup system for the JMC website database and uploaded images.

**Features:**
- Daily MySQL database backups (2 AM)
- Weekly image backups (Sunday 3 AM)
- Automatic cleanup of old backups (30 days for database, 90 days for images)
- Integrity verification after each backup
- Detailed logging to `/var/log/jmc_backup.log`
- Optional S3 upload support

---

## Installation Instructions

### 1. Upload Backup Scripts to Server

```bash
# From local machine
scp -r scripts/ ubuntu@167.114.97.221:/var/www/html/

# OR manually upload via SFTP:
# - backup_database.sh
# - backup_images.sh
```

### 2. Set Execute Permissions

```bash
# SSH into server
ssh ubuntu@167.114.97.221

# Navigate to scripts directory
cd /var/www/html/scripts

# Make scripts executable
chmod +x backup_database.sh
chmod +x backup_images.sh

# Verify permissions
ls -lh
```

### 3. Create Backup Directories

```bash
# Create backup directories
sudo mkdir -p /var/backups/jmc_website/database
sudo mkdir -p /var/backups/jmc_website/images

# Set ownership
sudo chown -R ubuntu:ubuntu /var/backups/jmc_website

# Set permissions
sudo chmod -R 755 /var/backups/jmc_website
```

### 4. Create Log File

```bash
# Create log file
sudo touch /var/log/jmc_backup.log

# Set ownership
sudo chown ubuntu:ubuntu /var/log/jmc_backup.log

# Set permissions
sudo chmod 644 /var/log/jmc_backup.log
```

### 5. Test Backup Scripts Manually

**Test database backup:**
```bash
cd /var/www/html/scripts
./backup_database.sh

# Check if backup was created
ls -lh /var/backups/jmc_website/database/

# Check log
tail -n 20 /var/log/jmc_backup.log
```

**Test images backup:**
```bash
cd /var/www/html/scripts
./backup_images.sh

# Check if backup was created
ls -lh /var/backups/jmc_website/images/

# Check log
tail -n 20 /var/log/jmc_backup.log
```

### 6. Set Up Cron Jobs

```bash
# Open crontab editor
crontab -e

# Add the following lines:

# Database backup - Daily at 2 AM
0 2 * * * /var/www/html/scripts/backup_database.sh >> /var/log/jmc_backup.log 2>&1

# Images backup - Weekly on Sunday at 3 AM
0 3 * * 0 /var/www/html/scripts/backup_images.sh >> /var/log/jmc_backup.log 2>&1

# Save and exit (:wq in vim)
```

### 7. Verify Cron Jobs

```bash
# List current cron jobs
crontab -l

# Check cron is running
sudo systemctl status cron

# OR
sudo service cron status
```

---

## Backup Verification

### Check Backup Files

```bash
# List database backups
ls -lh /var/backups/jmc_website/database/

# List image backups
ls -lh /var/backups/jmc_website/images/

# Check total backup size
du -sh /var/backups/jmc_website/
```

### View Backup Logs

```bash
# View full log
cat /var/log/jmc_backup.log

# View last 50 lines
tail -n 50 /var/log/jmc_backup.log

# View real-time logs
tail -f /var/log/jmc_backup.log

# Search for errors
grep "ERROR" /var/log/jmc_backup.log
```

### Test Backup Integrity

**Database backup:**
```bash
# Test gzip integrity
gunzip -t /var/backups/jmc_website/database/db_YYYYMMDD_HHMMSS.sql.gz

# Extract and verify SQL (optional)
gunzip -c /var/backups/jmc_website/database/db_YYYYMMDD_HHMMSS.sql.gz | head -n 20
```

**Images backup:**
```bash
# Test tar integrity
tar -tzf /var/backups/jmc_website/images/images_YYYYMMDD.tar.gz

# List contents
tar -tzf /var/backups/jmc_website/images/images_YYYYMMDD.tar.gz | head -n 20
```

---

## Backup Restoration

### Restore Database

```bash
# 1. Identify backup file
ls -lh /var/backups/jmc_website/database/

# 2. Extract and restore
gunzip -c /var/backups/jmc_website/database/db_YYYYMMDD_HHMMSS.sql.gz | mysql -h localhost -u jmc_user -p jmc_website

# Enter password: Sphinx208!

# 3. Verify restoration
mysql -h localhost -u jmc_user -p jmc_website -e "SHOW TABLES;"
```

### Restore Images

```bash
# 1. Identify backup file
ls -lh /var/backups/jmc_website/images/

# 2. Extract to temporary location first (recommended)
mkdir -p /tmp/restore_images
tar -xzf /var/backups/jmc_website/images/images_YYYYMMDD.tar.gz -C /tmp/restore_images

# 3. Verify contents
ls -lh /tmp/restore_images/images/

# 4. Copy to production (if verified)
cp -r /tmp/restore_images/images/* /var/www/html/images/

# 5. Set permissions
sudo chown -R www-data:www-data /var/www/html/images
sudo chmod -R 755 /var/www/html/images

# 6. Cleanup
rm -rf /tmp/restore_images
```

---

## Maintenance

### Manual Backup Trigger

```bash
# Run database backup manually
/var/www/html/scripts/backup_database.sh

# Run images backup manually
/var/www/html/scripts/backup_images.sh
```

### Cleanup Old Backups Manually

```bash
# Database backups older than 30 days
find /var/backups/jmc_website/database -name "db_*.sql.gz" -mtime +30 -ls
find /var/backups/jmc_website/database -name "db_*.sql.gz" -mtime +30 -delete

# Image backups older than 90 days
find /var/backups/jmc_website/images -name "images_*.tar.gz" -mtime +90 -ls
find /var/backups/jmc_website/images -name "images_*.tar.gz" -mtime +90 -delete
```

### Monitor Disk Space

```bash
# Check backup directory size
du -sh /var/backups/jmc_website/*

# Check available disk space
df -h /var/backups
```

### Log Rotation (Optional)

```bash
# Create logrotate config
sudo nano /etc/logrotate.d/jmc-backup

# Add the following:
/var/log/jmc_backup.log {
    weekly
    rotate 4
    compress
    delaycompress
    missingok
    notifempty
}

# Save and exit
```

---

## Optional: S3 Backup Setup

### Install AWS CLI

```bash
# Install AWS CLI
sudo apt-get update
sudo apt-get install awscli -y

# Verify installation
aws --version
```

### Configure AWS CLI

```bash
# Configure AWS credentials
aws configure

# Enter:
# - AWS Access Key ID
# - AWS Secret Access Key
# - Default region name: us-east-1 (or your region)
# - Default output format: json
```

### Create S3 Bucket

```bash
# Create bucket (replace with your bucket name)
aws s3 mb s3://jmc-backups

# Enable versioning (recommended)
aws s3api put-bucket-versioning \
    --bucket jmc-backups \
    --versioning-configuration Status=Enabled

# Set lifecycle policy to delete old backups
aws s3api put-bucket-lifecycle-configuration \
    --bucket jmc-backups \
    --lifecycle-configuration file://s3-lifecycle.json
```

### Enable S3 Uploads in Scripts

```bash
# Edit backup scripts
nano /var/www/html/scripts/backup_database.sh
nano /var/www/html/scripts/backup_images.sh

# Uncomment the S3 upload section (lines with # aws s3 cp)
```

---

## Monitoring & Alerts

### Email Alerts on Failure

```bash
# Install mailutils
sudo apt-get install mailutils -y

# Add to cron jobs:
0 2 * * * /var/www/html/scripts/backup_database.sh >> /var/log/jmc_backup.log 2>&1 || echo "Database backup failed" | mail -s "JMC Backup Failed" admin@example.com
```

### Weekly Backup Report

```bash
# Create weekly report script
nano /var/www/html/scripts/backup_report.sh

#!/bin/bash
echo "JMC Website Backup Report"
echo "=========================="
echo ""
echo "Database Backups:"
ls -lh /var/backups/jmc_website/database/ | tail -n 7
echo ""
echo "Image Backups:"
ls -lh /var/backups/jmc_website/images/ | tail -n 4
echo ""
echo "Total Backup Size:"
du -sh /var/backups/jmc_website/*

# Make executable
chmod +x /var/www/html/scripts/backup_report.sh

# Add to cron (weekly on Monday 9 AM)
0 9 * * 1 /var/www/html/scripts/backup_report.sh | mail -s "JMC Weekly Backup Report" admin@example.com
```

---

## Troubleshooting

### Common Issues

**1. Permission Denied**
```bash
# Fix script permissions
chmod +x /var/www/html/scripts/*.sh

# Fix directory permissions
sudo chown -R ubuntu:ubuntu /var/backups/jmc_website
```

**2. mysqldump Command Not Found**
```bash
# Install MySQL client
sudo apt-get install mysql-client -y
```

**3. .env File Not Found**
```bash
# Verify .env exists
ls -lh /var/www/html/.env

# Check contents (redact sensitive info before sharing)
cat /var/www/html/.env
```

**4. Backup Directory Full**
```bash
# Check disk space
df -h /var/backups

# Manually cleanup old backups
find /var/backups/jmc_website -mtime +30 -delete
```

**5. Cron Not Running**
```bash
# Check cron status
sudo systemctl status cron

# Restart cron
sudo systemctl restart cron

# Check cron logs
grep CRON /var/log/syslog
```

---

## Best Practices

1. **Test backups regularly** - Restore to a test environment monthly
2. **Monitor log files** - Check for errors weekly
3. **Verify disk space** - Ensure adequate space for backups
4. **Document changes** - Keep this README updated
5. **Off-site backups** - Enable S3 uploads for disaster recovery
6. **Retention policy** - Adjust based on compliance requirements
7. **Encryption** - Consider encrypting backups for sensitive data

---

## Support

For issues or questions:
- Check log file: `/var/log/jmc_backup.log`
- Review cron jobs: `crontab -l`
- Test scripts manually before troubleshooting cron

**Created:** January 30, 2026
**Version:** 1.0
