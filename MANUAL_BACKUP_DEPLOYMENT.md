# Manual Backup System Deployment Guide

**Important:** Follow these steps to deploy the backup system to production.

---

## Prerequisites

- VS Code with SFTP extension (already configured in `.vscode/sftp.json`)
- SSH access to production server: ubuntu@167.114.97.221
- Password: quxqof-sYkzim-7xymva

---

## Step 1: Create Scripts Directory on Server

**Via SSH:**

```bash
ssh ubuntu@167.114.97.221
# Enter password: quxqof-sYkzim-7xymva

# Create scripts directory
mkdir -p /var/www/html/scripts

# Verify creation
ls -ld /var/www/html/scripts

# Keep SSH session open for next steps
```

---

## Step 2: Upload Backup Scripts

### Option A: Using VS Code SFTP Extension (Recommended)

1. In VS Code, open the project folder
2. Right-click on `scripts/backup_database.sh`
3. Select "SFTP: Upload File"
4. Repeat for:
   - `scripts/backup_images.sh`
   - `scripts/BACKUP_README.md`

### Option B: Using SCP (Command Line)

```bash
# From local machine
cd /Users/rushabhjoshi/Desktop/jmc-website

# Upload scripts
scp scripts/backup_database.sh ubuntu@167.114.97.221:/var/www/html/scripts/
scp scripts/backup_images.sh ubuntu@167.114.97.221:/var/www/html/scripts/
scp scripts/BACKUP_README.md ubuntu@167.114.97.221:/var/www/html/scripts/
```

---

## Step 3: Set Execute Permissions

```bash
# Via SSH (already connected)
cd /var/www/html/scripts

# Set execute permissions
chmod +x backup_database.sh backup_images.sh

# Verify permissions
ls -lh backup_database.sh backup_images.sh
```

Expected output:
```
-rwxr-xr-x 1 ubuntu ubuntu 2.5K Jan 30 18:00 backup_database.sh
-rwxr-xr-x 1 ubuntu ubuntu 1.8K Jan 30 18:00 backup_images.sh
```

---

## Step 4: Create Backup Directories

```bash
# Create backup directories
sudo mkdir -p /var/backups/jmc_website/database
sudo mkdir -p /var/backups/jmc_website/images

# Set ownership
sudo chown -R ubuntu:ubuntu /var/backups/jmc_website

# Set permissions
sudo chmod -R 755 /var/backups/jmc_website

# Verify creation
ls -lh /var/backups/jmc_website/
```

Expected output:
```
drwxr-xr-x 2 ubuntu ubuntu 4.0K Jan 30 18:00 database
drwxr-xr-x 2 ubuntu ubuntu 4.0K Jan 30 18:00 images
```

---

## Step 5: Create Log File

```bash
# Create log file
sudo touch /var/log/jmc_backup.log

# Set ownership
sudo chown ubuntu:ubuntu /var/log/jmc_backup.log

# Set permissions
sudo chmod 644 /var/log/jmc_backup.log

# Verify creation
ls -lh /var/log/jmc_backup.log
```

---

## Step 6: Test Backup Scripts Manually

### Test Database Backup

```bash
cd /var/www/html/scripts
./backup_database.sh

# Check if backup was created
ls -lh /var/backups/jmc_website/database/

# Check log
tail -n 20 /var/log/jmc_backup.log
```

Expected output:
```
[2026-01-30 18:10:23] Starting database backup...
[2026-01-30 18:10:23] Database: jmc_website
[2026-01-30 18:10:23] Backup file: db_20260130_181023.sql.gz
[2026-01-30 18:10:25] Backup completed successfully: db_20260130_181023.sql.gz (1.2 MB)
[2026-01-30 18:10:25] Backup integrity verified (gzip test passed)
[2026-01-30 18:10:25] Total backups: 1 (Total size: 1.2 MB)
[2026-01-30 18:10:25] Backup process completed
```

### Test Images Backup

```bash
cd /var/www/html/scripts
./backup_images.sh

# Check if backup was created
ls -lh /var/backups/jmc_website/images/

# Check log
tail -n 20 /var/log/jmc_backup.log
```

Expected output:
```
[2026-01-30 18:12:45] Starting images backup...
[2026-01-30 18:12:45] Source: /var/www/html/images
[2026-01-30 18:12:45] Backup file: images_20260130.tar.gz
[2026-01-30 18:12:45] Files to backup: 15
[2026-01-30 18:12:47] Backup completed successfully: images_20260130.tar.gz (3.5 MB)
[2026-01-30 18:12:47] Backup integrity verified (tar test passed)
[2026-01-30 18:12:47] Total backups: 1 (Total size: 3.5 MB)
[2026-01-30 18:12:47] Images backup process completed
```

---

## Step 7: Configure Cron Jobs

### Backup Existing Crontab (Optional)

```bash
crontab -l > ~/crontab_backup_$(date +%Y%m%d_%H%M%S).txt 2>/dev/null || true
```

### Add Cron Jobs

```bash
# Edit crontab
crontab -e

# Add these lines at the end:
# JMC Website Automated Backups
0 2 * * * /var/www/html/scripts/backup_database.sh >> /var/log/jmc_backup.log 2>&1
0 3 * * 0 /var/www/html/scripts/backup_images.sh >> /var/log/jmc_backup.log 2>&1

# Save and exit (in vim: press ESC, then :wq, then ENTER)
```

### Verify Cron Jobs

```bash
# List current cron jobs
crontab -l

# Check cron service status
sudo systemctl status cron
# OR
sudo service cron status
```

Expected output:
```
# JMC Website Automated Backups
0 2 * * * /var/www/html/scripts/backup_database.sh >> /var/log/jmc_backup.log 2>&1
0 3 * * 0 /var/www/html/scripts/backup_images.sh >> /var/log/jmc_backup.log 2>&1
```

---

## Step 8: Verify Deployment

### Check All Components

```bash
# 1. Scripts exist and are executable
ls -lh /var/www/html/scripts/backup*.sh

# 2. Backup directories exist
ls -ld /var/backups/jmc_website/*

# 3. Log file exists
ls -lh /var/log/jmc_backup.log

# 4. Test backups exist
ls -lh /var/backups/jmc_website/database/
ls -lh /var/backups/jmc_website/images/

# 5. Cron jobs configured
crontab -l | grep backup
```

---

## Troubleshooting

### Issue: "Permission Denied" when running script

```bash
# Check execute permissions
ls -l /var/www/html/scripts/backup_database.sh

# Set execute permission
chmod +x /var/www/html/scripts/backup_database.sh
```

### Issue: ".env file not found"

```bash
# Verify .env exists
ls -lh /var/www/html/.env

# If missing, check if it was uploaded
# Upload from local: scp .env ubuntu@167.114.97.221:/var/www/html/
```

### Issue: "mysqldump: command not found"

```bash
# Install MySQL client
sudo apt-get update
sudo apt-get install mysql-client -y
```

### Issue: Backup directory doesn't exist

```bash
# Create backup directories
sudo mkdir -p /var/backups/jmc_website/database
sudo mkdir -p /var/backups/jmc_website/images
sudo chown -R ubuntu:ubuntu /var/backups/jmc_website
```

### Issue: Cron jobs not running

```bash
# Check cron service
sudo systemctl status cron

# Restart cron
sudo systemctl restart cron

# Check cron logs
grep CRON /var/log/syslog | tail -n 20
```

---

## Monitoring

### View Backup Logs in Real-Time

```bash
tail -f /var/log/jmc_backup.log
```

### Check Disk Space

```bash
# Check backup directory size
du -sh /var/backups/jmc_website/*

# Check available disk space
df -h /var/backups
```

### List All Backups

```bash
# Database backups
ls -lh /var/backups/jmc_website/database/ | tail -n 10

# Image backups
ls -lh /var/backups/jmc_website/images/ | tail -n 5
```

---

## Testing Backup Restoration

### Test Database Restoration

```bash
# DO NOT run in production without backup!

# 1. Identify backup file
ls -lh /var/backups/jmc_website/database/

# 2. Test extraction (doesn't restore, just verifies)
gunzip -t /var/backups/jmc_website/database/db_YYYYMMDD_HHMMSS.sql.gz

# 3. View first 20 lines (verify it's valid SQL)
gunzip -c /var/backups/jmc_website/database/db_YYYYMMDD_HHMMSS.sql.gz | head -n 20
```

---

## Quick Reference Commands

```bash
# Connect to server
ssh ubuntu@167.114.97.221

# Run backup manually
/var/www/html/scripts/backup_database.sh
/var/www/html/scripts/backup_images.sh

# View recent log entries
tail -n 50 /var/log/jmc_backup.log

# List backups
ls -lh /var/backups/jmc_website/database/
ls -lh /var/backups/jmc_website/images/

# Check cron jobs
crontab -l

# Check disk space
df -h /var/backups
```

---

## Schedule Summary

- **Database Backups:** Daily at 2:00 AM (30-day retention)
- **Image Backups:** Weekly on Sunday at 3:00 AM (90-day retention)
- **Logs:** `/var/log/jmc_backup.log`
- **Backups:** `/var/backups/jmc_website/`

---

## Next Steps After Deployment

1. ✅ Wait for first automated backup (2 AM tomorrow)
2. ✅ Verify backup was created successfully
3. ✅ Monitor log file for any errors
4. ✅ Consider setting up S3 uploads (see `scripts/BACKUP_README.md`)
5. ✅ Test restoration procedure once backups are running

---

## Support

For detailed documentation, see `scripts/BACKUP_README.md`

For troubleshooting, check:
- Backup logs: `/var/log/jmc_backup.log`
- Cron logs: `grep CRON /var/log/syslog`
- System logs: `/var/log/syslog`

---

**Deployment Date:** January 30, 2026
**Status:** Ready for manual deployment
