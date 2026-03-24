# JMC Website - Deployment Guide
**Last Updated:** February 1, 2026
**Purpose:** Complete guide for deploying changes automatically

---

## 🚀 QUICK START (Auto-Upload Setup)

### One-Time Setup (5 minutes)

Run this script **once** to enable automatic deployments:

```bash
./FIX_DEPLOYMENT_PERMANENTLY.sh
```

**What it does:**
1. ✅ Sets up SSH key authentication (no more passwords!)
2. ✅ Fixes file permissions on server permanently
3. ✅ Configures VS Code for auto-upload on save
4. ✅ Creates quick deployment scripts

**After running this, files will auto-upload whenever you save in VS Code! 🎉**

---

## 📝 DEPLOYMENT METHODS

### Method 1: Auto-Upload (Recommended) ⭐

**Best for:** Day-to-day file edits

1. Open any file in VS Code
2. Make changes
3. Save file (Cmd+S / Ctrl+S)
4. ✅ **File automatically uploads!**

**Check upload status:**
- View → Output → Select "SFTP" from dropdown
- Look for "Upload success!" message

**Supported file types:**
- HTML, CSS, JavaScript, PHP, JSON

**Not auto-uploaded:**
- Markdown files (*.md)
- Shell scripts (*.sh)
- Documentation files
- .env files (security)

---

### Method 2: Quick Deploy Script

**Best for:** Deploying specific files or batches

**Deploy single file:**
```bash
./quick-deploy.sh index.html
```

**Deploy all changes:**
```bash
./quick-deploy.sh all
```

**Fix permissions only:**
```bash
./quick-deploy.sh fix-perms
```

**Examples:**
```bash
./quick-deploy.sh css/main-styles.css     # Deploy CSS
./quick-deploy.sh php/contact_handler.php # Deploy PHP
./quick-deploy.sh all                     # Deploy everything
```

---

### Method 3: GitHub Actions CI/CD

**Best for:** Major updates, team deployments, automated testing

**Trigger automatic deployment:**
```bash
git add .
git commit -m "Your commit message"
git push origin main
```

**What happens:**
1. GitHub Actions runs tests (PHP syntax, Composer validation)
2. If tests pass, deploys to production server
3. Sets correct file permissions
4. Clears PHP OPcache
5. Sends notification (success/failure)

**View deployment status:**
- GitHub → Actions tab
- See real-time deployment logs

**Rollback if needed:**
```bash
git revert HEAD
git push origin main
```

---

### Method 4: Manual SCP/SFTP

**Best for:** Troubleshooting, one-off files

**Upload single file:**
```bash
scp index.html ubuntu@167.114.97.221:/var/www/html/
```

**Upload directory:**
```bash
scp -r css ubuntu@167.114.97.221:/var/www/html/
```

**Fix permissions after manual upload:**
```bash
ssh ubuntu@167.114.97.221 "sudo /usr/local/bin/fix-jmc-permissions.sh"
```

---

## 🔧 TROUBLESHOOTING

### Auto-Upload Not Working

**Check VS Code SFTP extension status:**
1. View → Output → Select "SFTP"
2. Look for error messages

**Common issues:**

**Issue 1: "Connection timeout"**
```bash
# Test SSH connection
ssh -i ~/.ssh/id_ed25519 ubuntu@167.114.97.221

# If fails, re-run setup
./FIX_DEPLOYMENT_PERMANENTLY.sh
```

**Issue 2: "Permission denied"**
```bash
# Fix permissions on server
./quick-deploy.sh fix-perms
```

**Issue 3: "File already exists"**
- VS Code SFTP config has `update: true` - should overwrite
- Check .vscode/sftp.json syncOption settings

**Issue 4: "Upload success but changes not visible"**
```bash
# Clear server cache
ssh ubuntu@167.114.97.221 "sudo systemctl reload apache2"
# OR
ssh ubuntu@167.114.97.221 "sudo systemctl reload nginx"
```

---

### Permission Errors on Server

**Symptoms:**
- 403 Forbidden errors
- "Permission denied" when accessing files
- Changes upload but don't display

**Fix:**
```bash
# Run permissions fix
./quick-deploy.sh fix-perms

# OR ssh to server and run
ssh ubuntu@167.114.97.221
sudo /usr/local/bin/fix-jmc-permissions.sh
# OR use the alias
fix-perms
```

**What it fixes:**
- Sets ownership to `www-data:www-data` (web server user)
- Files: 644 (rw-r--r--)
- Directories: 755 (rwxr-xr-x)
- Upload directories: 775 (rwxrwxr-x)

---

### SSH Key Issues

**Re-add SSH key to server:**
```bash
ssh-copy-id -i ~/.ssh/id_ed25519 ubuntu@167.114.97.221
```

**Test SSH connection:**
```bash
ssh -i ~/.ssh/id_ed25519 ubuntu@167.114.97.221 exit
```

**Generate new SSH key (if needed):**
```bash
ssh-keygen -t ed25519 -C "your_email@example.com"
ssh-copy-id -i ~/.ssh/id_ed25519 ubuntu@167.114.97.221
```

---

## 📋 PRE-DEPLOYMENT CHECKLIST

Before deploying major changes:

### Local Testing
- [ ] All files saved
- [ ] No syntax errors (check browser console)
- [ ] Test locally (open HTML files in browser)
- [ ] Check responsive design (resize browser)
- [ ] Validate HTML (W3C validator)

### Server Testing
- [ ] Upload to production
- [ ] Clear browser cache (Cmd+Shift+R / Ctrl+Shift+F5)
- [ ] Test all pages
- [ ] Test forms (contact, assessment)
- [ ] Check mobile view
- [ ] Check different browsers (Chrome, Firefox, Safari)
- [ ] Verify no console errors

### Performance Testing
- [ ] Run Lighthouse audit
- [ ] Check page load time
- [ ] Verify images load
- [ ] Test on slow 3G connection

---

## 🔐 SECURITY

### Files NOT Auto-Uploaded
These files are excluded from auto-upload for security:

- `.env` - Environment variables (credentials)
- `.git/` - Git repository
- `*.md` - Documentation (unnecessary on production)
- `*.sh` - Shell scripts
- `vendor/` - Composer dependencies (install on server)
- `node_modules/` - NPM packages
- `.vscode/sftp.json` - SFTP config (has sensitive paths)

### SSH Key Security
- SSH keys stored in `~/.ssh/` (600 permissions)
- Private key never leaves your machine
- Public key added to server's `authorized_keys`

### Server Access
- SSH key authentication only (password auth disabled)
- Files owned by `www-data` (web server user)
- Ubuntu user added to `www-data` group for editing

---

## 📊 DEPLOYMENT COMPARISON

| Method | Speed | Ease | Use Case |
|--------|-------|------|----------|
| **Auto-Upload** | ⚡ Instant | ⭐⭐⭐⭐⭐ | Daily edits |
| **Quick Deploy** | ⚡ Fast | ⭐⭐⭐⭐ | Batch uploads |
| **GitHub Actions** | 🐢 2-3 min | ⭐⭐⭐⭐ | Major releases |
| **Manual SCP** | ⚡ Fast | ⭐⭐ | Troubleshooting |

---

## 🎯 RECOMMENDED WORKFLOW

### For Small Changes (1-5 files)
1. Edit file in VS Code
2. Save (auto-uploads)
3. Test on production
4. ✅ Done!

### For Medium Changes (5-20 files)
1. Edit all files
2. Save each (auto-uploads)
3. OR run `./quick-deploy.sh all`
4. Test on production
5. ✅ Done!

### For Major Changes (refactoring, new features)
1. Create new git branch
2. Make all changes
3. Test locally
4. Commit to branch
5. Create pull request
6. Merge to main
7. GitHub Actions auto-deploys
8. Test on production
9. ✅ Done!

---

## 🆘 EMERGENCY ROLLBACK

### If something breaks on production:

**Option 1: Revert via Git (if using GitHub Actions)**
```bash
git log  # Find commit hash to revert to
git revert <commit-hash>
git push origin main  # Triggers auto-deployment
```

**Option 2: Restore from backup**
```bash
# Database backup (daily at 2 AM)
ssh ubuntu@167.114.97.221
ls /var/backups/jmc_website/db_*.sql.gz
# Restore specific backup
gunzip < /var/backups/jmc_website/db_20260201_020000.sql.gz | mysql -u jmc_user -p jmc_website

# Image backup (weekly on Sunday 3 AM)
ls /var/backups/jmc_website/images/images_*.tar.gz
# Restore specific backup
cd /var/www/html
sudo tar -xzf /var/backups/jmc_website/images/images_20260126.tar.gz
```

**Option 3: Manual file restore**
```bash
# Upload old version of file
scp old_version.html ubuntu@167.114.97.221:/var/www/html/index.html
./quick-deploy.sh fix-perms
```

---

## 📞 SUPPORT

### Deployment Issues
1. Check this guide first
2. Run `./FIX_DEPLOYMENT_PERMANENTLY.sh` again
3. Check VS Code Output → SFTP for error messages
4. SSH to server and run `fix-perms`

### Server Access Issues
- SSH: `ssh ubuntu@167.114.97.221`
- SFTP: Check `.vscode/sftp.json` settings
- Permissions: Run `./quick-deploy.sh fix-perms`

---

**Last Updated:** February 1, 2026
**Maintained by:** Development Team
**Server:** 167.114.97.221 (Ubuntu)
**Web Server:** Apache/Nginx
**Document Root:** /var/www/html
