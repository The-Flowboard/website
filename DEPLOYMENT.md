# JMC Website - Deployment Guide

**Last Updated:** January 30, 2026
**Purpose:** Automated CI/CD deployment using GitHub Actions

---

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [GitHub Secrets Configuration](#github-secrets-configuration)
4. [Deployment Workflow](#deployment-workflow)
5. [Manual Deployment](#manual-deployment)
6. [Rollback Procedure](#rollback-procedure)
7. [Troubleshooting](#troubleshooting)

---

## Overview

The JMC website uses **GitHub Actions** for automated continuous integration and deployment (CI/CD). Every push to the `main` branch triggers:

1. **Testing Phase:**
   - PHP syntax validation
   - Composer dependency check
   - Security vulnerability audit

2. **Deployment Phase:**
   - Rsync file transfer to production server
   - Composer install on server
   - File permission updates
   - Apache/PHP cache clearing

**Benefits:**
- ✅ Zero-downtime deployments
- ✅ Automatic rollback on failure
- ✅ Consistent deployment process
- ✅ No manual SFTP needed

---

## Prerequisites

### Local Development
- Git installed and configured
- SSH access to production server
- GitHub repository access

### Production Server
- Ubuntu server (167.114.97.221)
- Apache web server
- PHP 7.4+
- Composer installed
- SSH access enabled

---

## GitHub Secrets Configuration

### Step 1: Generate SSH Key (if not already done)

On your local machine:
```bash
# Generate new SSH key for GitHub Actions
ssh-keygen -t ed25519 -C "github-actions@joshimc.com" -f ~/.ssh/jmc_deploy_key

# This creates two files:
# - jmc_deploy_key (private key) - keep this secure!
# - jmc_deploy_key.pub (public key)
```

### Step 2: Add Public Key to Production Server

```bash
# Copy public key to server
cat ~/.ssh/jmc_deploy_key.pub | ssh ubuntu@167.114.97.221 "cat >> ~/.ssh/authorized_keys"

# Verify SSH access works with the key
ssh -i ~/.ssh/jmc_deploy_key ubuntu@167.114.97.221 "echo 'SSH connection successful!'"
```

### Step 3: Configure GitHub Repository Secrets

1. Go to your GitHub repository: `https://github.com/YOUR_USERNAME/jmc-website`
2. Click **Settings** → **Secrets and variables** → **Actions**
3. Click **New repository secret** and add each of the following:

#### Required Secrets:

| Secret Name | Value | Description |
|-------------|-------|-------------|
| `DEPLOY_HOST` | `167.114.97.221` | Production server IP address |
| `DEPLOY_USER` | `ubuntu` | SSH username for production server |
| `DEPLOY_SSH_KEY` | `<contents of jmc_deploy_key>` | Private SSH key for deployment |

**IMPORTANT:** For `DEPLOY_SSH_KEY`, paste the **entire contents** of the private key file:
```bash
# Copy private key to clipboard (macOS)
cat ~/.ssh/jmc_deploy_key | pbcopy

# On Linux/Windows, manually copy the contents:
cat ~/.ssh/jmc_deploy_key
```

The private key should look like this:
```
-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAAAMwAAAAtzc2gtZW
... (many lines of base64-encoded data) ...
-----END OPENSSH PRIVATE KEY-----
```

### Step 4: Verify Secrets Are Set

1. Go to **Settings** → **Secrets and variables** → **Actions**
2. You should see 3 secrets listed:
   - ✅ DEPLOY_HOST
   - ✅ DEPLOY_USER
   - ✅ DEPLOY_SSH_KEY

---

## Deployment Workflow

### Automatic Deployment (Recommended)

When you push code to the `main` branch, GitHub Actions automatically:

```bash
# Make your changes
git add .
git commit -m "Update feature XYZ"
git push origin main
```

**What happens next:**
1. GitHub Actions starts the workflow (takes ~2-5 minutes)
2. Tests run (PHP syntax check, Composer validation)
3. If tests pass, deployment begins
4. Files are synced to production server via rsync
5. Composer dependencies installed on server
6. File permissions updated
7. Apache reloaded to clear cache
8. ✅ Deployment complete!

### Monitor Deployment Progress

1. Go to your GitHub repository
2. Click the **Actions** tab
3. Click on the latest workflow run
4. Watch the progress in real-time

### Manual Trigger

You can also trigger deployment manually without pushing code:

1. Go to **Actions** tab
2. Select **Deploy to Production** workflow
3. Click **Run workflow** dropdown
4. Select `main` branch
5. Click **Run workflow** button

---

## Manual Deployment (Fallback)

If GitHub Actions is unavailable, you can deploy manually using SFTP or rsync:

### Option 1: SFTP (VS Code Extension)

1. Install **SFTP** extension in VS Code
2. Configure `.vscode/sftp.json` (already configured)
3. Save files to auto-upload

### Option 2: Rsync (Command Line)

```bash
# Deploy all files to production
rsync -avz --delete \
  --exclude='.git' \
  --exclude='.github' \
  --exclude='.env' \
  --exclude='vendor' \
  --exclude='node_modules' \
  --exclude='.DS_Store' \
  --exclude='.vscode' \
  --exclude='*.md' \
  ./ ubuntu@167.114.97.221:/var/www/html/

# SSH into server and install dependencies
ssh ubuntu@167.114.97.221
cd /var/www/html
composer install --no-dev --optimize-autoloader
sudo chown -R www-data:www-data /var/www/html
sudo systemctl reload apache2
```

### Option 3: SCP (Individual Files)

```bash
# Upload single file
scp path/to/file.php ubuntu@167.114.97.221:/var/www/html/path/to/file.php
```

---

## Rollback Procedure

### Option 1: Git Revert (Recommended)

If a deployment breaks the site, revert to the previous commit:

```bash
# View recent commits
git log --oneline -n 10

# Revert to previous commit (replace COMMIT_HASH with actual hash)
git revert COMMIT_HASH

# Push to trigger automatic deployment
git push origin main
```

### Option 2: Manual Rollback

1. Go to GitHub Actions
2. Find the last successful deployment
3. Click **Re-run all jobs**

### Option 3: Database Restore (if needed)

If database changes caused issues:

```bash
# SSH into server
ssh ubuntu@167.114.97.221

# List available backups
ls -lh /var/backups/jmc_website/

# Restore from backup
gunzip < /var/backups/jmc_website/db_YYYYMMDD_HHMMSS.sql.gz | \
  mysql -u jmc_user -p'Sphinx208!' jmc_website

# Verify restoration
mysql -u jmc_user -p'Sphinx208!' jmc_website -e "SELECT COUNT(*) FROM contact_submissions;"
```

---

## Troubleshooting

### Deployment Failed: "Permission denied (publickey)"

**Cause:** SSH key not configured correctly

**Solution:**
1. Verify public key is in `~/.ssh/authorized_keys` on server
2. Check `DEPLOY_SSH_KEY` secret contains complete private key
3. Test SSH connection manually:
   ```bash
   ssh -i ~/.ssh/jmc_deploy_key ubuntu@167.114.97.221
   ```

### Deployment Failed: "rsync error"

**Cause:** rsync command failed

**Solution:**
1. Check server disk space: `ssh ubuntu@167.114.97.221 "df -h"`
2. Verify target directory exists: `ssh ubuntu@167.114.97.221 "ls -la /var/www/html"`
3. Check rsync is installed: `ssh ubuntu@167.114.97.221 "which rsync"`

### Deployment Succeeded but Site Shows Errors

**Cause:** Composer dependencies not installed or .env file missing

**Solution:**
```bash
# SSH into server
ssh ubuntu@167.114.97.221

# Check Composer dependencies
cd /var/www/html
composer install --no-dev --optimize-autoloader

# Verify .env file exists
ls -la .env

# Check Apache error logs
sudo tail -f /var/log/apache2/error.log
```

### GitHub Actions Workflow Not Triggering

**Cause:** Workflow file has syntax errors or is not in correct location

**Solution:**
1. Verify file is at `.github/workflows/deploy.yml`
2. Check YAML syntax: https://www.yamllint.com/
3. Ensure `main` branch exists and is default branch
4. Check GitHub Actions is enabled: **Settings** → **Actions** → **General**

### Deployment Hangs on "Install Composer on server"

**Cause:** Composer taking too long or server resources exhausted

**Solution:**
1. Increase timeout in workflow (add `timeout-minutes: 10`)
2. SSH into server and check resources:
   ```bash
   ssh ubuntu@167.114.97.221
   top
   df -h
   free -m
   ```
3. Manually run Composer to see errors:
   ```bash
   cd /var/www/html
   composer install --no-dev --optimize-autoloader -vvv
   ```

### Files Deployed but Changes Not Visible

**Cause:** PHP OPcache or browser cache

**Solution:**
```bash
# Clear server-side cache
ssh ubuntu@167.114.97.221
sudo systemctl reload apache2

# Or restart Apache
sudo systemctl restart apache2

# Clear browser cache (Cmd+Shift+R on Mac, Ctrl+Shift+R on Windows)
```

---

## Best Practices

### Before Pushing to Main

✅ **Test locally first:**
```bash
php -l path/to/file.php  # Check syntax
composer install         # Test dependencies
```

✅ **Review changes:**
```bash
git diff                 # See what changed
git status               # Check staged files
```

✅ **Use meaningful commit messages:**
```bash
# Good
git commit -m "Fix contact form validation bug"

# Bad
git commit -m "updates"
```

### During Deployment

✅ **Monitor the deployment:**
- Watch GitHub Actions progress
- Check Apache error logs if issues occur

✅ **Test immediately after deployment:**
- Visit https://joshimc.com
- Test critical features (contact form, assessment, blog)
- Check browser console for JavaScript errors

### After Deployment

✅ **Verify deployment success:**
```bash
# Check file timestamps
ssh ubuntu@167.114.97.221 "ls -lt /var/www/html/ | head -10"

# Check Apache logs for errors
ssh ubuntu@167.114.97.221 "sudo tail -20 /var/log/apache2/error.log"
```

✅ **Keep backups:**
- Database backups run daily at 2 AM (automatic)
- Image backups run weekly on Sunday at 3 AM (automatic)

---

## Deployment Checklist

Use this checklist for major deployments:

- [ ] Code changes tested locally
- [ ] Composer dependencies up to date
- [ ] Database migrations prepared (if needed)
- [ ] Backup created (if critical changes)
- [ ] Changes committed with clear message
- [ ] Pushed to `main` branch
- [ ] GitHub Actions workflow succeeded
- [ ] Website tested and functioning
- [ ] Apache error logs checked
- [ ] Performance checked (Lighthouse)
- [ ] Rollback plan ready (just in case)

---

## Emergency Contacts

**Server Issues:**
- Hosting Provider: [Your hosting provider contact]
- Server IP: 167.114.97.221
- SSH Access: ubuntu@167.114.97.221

**GitHub Issues:**
- Repository: https://github.com/YOUR_USERNAME/jmc-website
- Actions: https://github.com/YOUR_USERNAME/jmc-website/actions

**Database Issues:**
- Database: jmc_website
- User: jmc_user
- Backups: /var/backups/jmc_website/

---

## Additional Resources

- **GitHub Actions Documentation:** https://docs.github.com/en/actions
- **Rsync Manual:** https://linux.die.net/man/1/rsync
- **Composer Documentation:** https://getcomposer.org/doc/
- **Apache Documentation:** https://httpd.apache.org/docs/2.4/

---

**Document Version:** 1.0
**Created:** January 30, 2026
**Author:** JMC Development Team
**Next Review:** February 28, 2026
