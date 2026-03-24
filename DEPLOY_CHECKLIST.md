# Backup System Deployment Checklist

**Quick 10-Minute Deployment Guide**

---

## ✅ Step-by-Step Checklist

### 1. Connect to Server (1 minute)
```bash
ssh ubuntu@167.114.97.221
# Password: quxqof-sYkzim-7xymva
```
- [ ] Connected successfully

---

### 2. Create Scripts Directory (30 seconds)
```bash
mkdir -p /var/www/html/scripts
ls -ld /var/www/html/scripts
```
- [ ] Directory created

---

### 3. Upload Scripts (2 minutes)

**Option A - VS Code SFTP (Recommended):**
- [ ] Right-click `scripts/backup_database.sh` → "SFTP: Upload File"
- [ ] Right-click `scripts/backup_images.sh` → "SFTP: Upload File"
- [ ] Right-click `scripts/BACKUP_README.md` → "SFTP: Upload File"

**Option B - SCP from local terminal:**
```bash
cd /Users/rushabhjoshi/Desktop/jmc-website
scp scripts/backup_database.sh ubuntu@167.114.97.221:/var/www/html/scripts/
scp scripts/backup_images.sh ubuntu@167.114.97.221:/var/www/html/scripts/
scp scripts/BACKUP_README.md ubuntu@167.114.97.221:/var/www/html/scripts/
```

---

### 4. Set Execute Permissions (30 seconds)
```bash
cd /var/www/html/scripts
chmod +x backup_database.sh backup_images.sh
ls -lh *.sh
```
- [ ] Permissions set (-rwxr-xr-x)

---

### 5. Create Backup Directories (1 minute)
```bash
sudo mkdir -p /var/backups/jmc_website/database
sudo mkdir -p /var/backups/jmc_website/images
sudo chown -R ubuntu:ubuntu /var/backups/jmc_website
sudo chmod -R 755 /var/backups/jmc_website
ls -lh /var/backups/jmc_website/
```
- [ ] Directories created

---

### 6. Create Log File (30 seconds)
```bash
sudo touch /var/log/jmc_backup.log
sudo chown ubuntu:ubuntu /var/log/jmc_backup.log
sudo chmod 644 /var/log/jmc_backup.log
ls -lh /var/log/jmc_backup.log
```
- [ ] Log file created

---

### 7. Test Database Backup (1 minute)
```bash
cd /var/www/html/scripts
./backup_database.sh
```

**Check results:**
```bash
ls -lh /var/backups/jmc_website/database/
tail -n 10 /var/log/jmc_backup.log
```
- [ ] Backup file created (db_*.sql.gz)
- [ ] Log shows "Backup completed successfully"

---

### 8. Test Images Backup (1 minute)
```bash
cd /var/www/html/scripts
./backup_images.sh
```

**Check results:**
```bash
ls -lh /var/backups/jmc_website/images/
tail -n 10 /var/log/jmc_backup.log
```
- [ ] Backup file created (images_*.tar.gz)
- [ ] Log shows "Backup completed successfully"

---

### 9. Configure Cron Jobs (2 minutes)
```bash
crontab -e
```

**Add these lines at the end:**
```
# JMC Website Automated Backups
0 2 * * * /var/www/html/scripts/backup_database.sh >> /var/log/jmc_backup.log 2>&1
0 3 * * 0 /var/www/html/scripts/backup_images.sh >> /var/log/jmc_backup.log 2>&1
```

**Save:** Press ESC, type `:wq`, press ENTER

**Verify:**
```bash
crontab -l | grep backup
```
- [ ] Cron jobs added

---

### 10. Final Verification (1 minute)
```bash
# Check everything is in place
echo "=== Scripts ==="
ls -lh /var/www/html/scripts/backup*.sh

echo "=== Directories ==="
ls -ld /var/backups/jmc_website/*

echo "=== Backups ==="
ls -lh /var/backups/jmc_website/database/
ls -lh /var/backups/jmc_website/images/

echo "=== Cron Jobs ==="
crontab -l | grep backup

echo "=== Deployment Complete! ==="
```
- [ ] All checks passed

---

## 🎯 You're Done!

**Backup Schedule:**
- 📅 Database: Daily at 2:00 AM
- 📅 Images: Weekly on Sunday at 3:00 AM

**Monitor Backups:**
```bash
# View logs
tail -f /var/log/jmc_backup.log

# List backups
ls -lh /var/backups/jmc_website/database/
ls -lh /var/backups/jmc_website/images/
```

---

## ⚠️ Troubleshooting

**If backup script fails:**
1. Check .env file exists: `ls -lh /var/www/html/.env`
2. Check mysqldump installed: `which mysqldump`
3. Check log file: `tail -n 50 /var/log/jmc_backup.log`

**If cron jobs don't run:**
1. Check cron service: `sudo systemctl status cron`
2. Check cron logs: `grep CRON /var/log/syslog | tail -n 20`

---

**Estimated Time:** 10 minutes
**Difficulty:** Easy
**See Also:** MANUAL_BACKUP_DEPLOYMENT.md (detailed guide)
