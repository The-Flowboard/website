# Fix SFTP Upload Permissions - Quick Guide

**Problem:** Getting "Permission Denied" errors when uploading files via SFTP

**Solution:** Configure proper permissions so both you (ubuntu user) and the web server (www-data) can work with files

---

## Quick Fix (5 minutes)

### Option 1: Run the Script (Easiest)

```bash
# 1. SSH to server
ssh ubuntu@167.114.97.221

# 2. Upload the fix script first (via VS Code SFTP or SCP)
# Right-click FIX_PERMISSIONS.sh → "SFTP: Upload File"

# 3. Run the script
cd /var/www/html
chmod +x FIX_PERMISSIONS.sh
sudo ./FIX_PERMISSIONS.sh

# 4. Log out and back in for group changes to take effect
exit
ssh ubuntu@167.114.97.221

# 5. Test upload permissions
touch /var/www/html/test_upload.txt
rm /var/www/html/test_upload.txt
```

---

### Option 2: Run Commands Manually

If you prefer to run commands one by one:

```bash
# SSH to server
ssh ubuntu@167.114.97.221

# 1. Add ubuntu user to www-data group
sudo usermod -a -G www-data ubuntu

# 2. Change ownership (ubuntu:www-data)
sudo chown -R ubuntu:www-data /var/www/html

# 3. Set directory permissions (775 = rwxrwxr-x)
sudo find /var/www/html -type d -exec chmod 775 {} \;

# 4. Set file permissions (664 = rw-rw-r--)
sudo find /var/www/html -type f -exec chmod 664 {} \;

# 5. Set execute on scripts
sudo find /var/www/html -type f -name "*.sh" -exec chmod 775 {} \;

# 6. Protect .env file
sudo chmod 660 /var/www/html/.env

# 7. Set setgid bit (new files inherit group)
sudo find /var/www/html -type d -exec chmod g+s {} \;

# 8. Log out and back in
exit
ssh ubuntu@167.114.97.221

# 9. Verify you can create files
touch /var/www/html/test_upload.txt && echo "✅ Upload works!" && rm /var/www/html/test_upload.txt
```

---

## What These Permissions Mean

### Permission Breakdown

| Type | Permission | Numeric | Meaning |
|------|-----------|---------|---------|
| **Directories** | rwxrwxr-x | 775 | Owner & group can read/write/execute, others can read/execute |
| **Files** | rw-rw-r-- | 664 | Owner & group can read/write, others can read only |
| **Scripts** | rwxrwxr-x | 775 | Owner & group can execute scripts |
| **Sensitive** | rw-rw---- | 660 | Only owner & group can read/write |

### User/Group Setup

- **Owner (ubuntu):** You - can upload/edit via SFTP
- **Group (www-data):** Web server - can read/execute files
- **Others:** Public - can only read (not write/execute)

### Setgid Bit

The setgid bit (`g+s`) ensures that:
- New files automatically inherit the group (`www-data`)
- No need to fix permissions after every upload
- Both you and the web server can always access files

---

## Verify Permissions

```bash
# Check current permissions
ls -lh /var/www/html/

# Check user groups
groups ubuntu

# Check specific directories
ls -ld /var/www/html/scripts
ls -ld /var/www/html/images/blog
ls -ld /var/www/html/admin

# Test upload capability
touch /var/www/html/test_upload.txt
echo "If you see this, uploads work!" > /var/www/html/test_upload.txt
cat /var/www/html/test_upload.txt
rm /var/www/html/test_upload.txt
```

Expected output:
```
drwxrwsr-x  ubuntu www-data  /var/www/html/scripts
drwxrwsr-x  ubuntu www-data  /var/www/html/images/blog
-rw-rw-r--  ubuntu www-data  index.html
```

---

## Troubleshooting

### Issue: Still getting "Permission Denied"

**Solution 1: Log out and back in**
```bash
exit
ssh ubuntu@167.114.97.221
groups ubuntu  # Should show "www-data" in the list
```

**Solution 2: Check specific file permissions**
```bash
ls -lh /var/www/html/problematic_file.php
sudo chown ubuntu:www-data /var/www/html/problematic_file.php
sudo chmod 664 /var/www/html/problematic_file.php
```

**Solution 3: Check directory permissions**
```bash
ls -ld /var/www/html/problematic_directory
sudo chmod 775 /var/www/html/problematic_directory
sudo chmod g+s /var/www/html/problematic_directory
```

---

### Issue: Web server can't read files

**Check web server user:**
```bash
ps aux | grep apache2  # For Apache
# OR
ps aux | grep nginx    # For Nginx
```

**Fix if different user:**
```bash
# Replace www-data with actual web server user
sudo chown -R ubuntu:ACTUAL_USER /var/www/html
```

---

### Issue: Can't upload to specific directory

```bash
# Fix specific directory
sudo chown ubuntu:www-data /var/www/html/target_directory
sudo chmod 775 /var/www/html/target_directory
sudo chmod g+s /var/www/html/target_directory
```

---

## Security Notes

### ✅ Safe Permissions

- Directories: `775` - Required for uploads and web server access
- Files: `664` - Web server can read, you can edit
- Scripts: `775` - Executable by owner and group
- .env: `660` - Hidden from public, readable by web server

### ⚠️ Avoid These

- **777 (rwxrwxrwx)** - Too permissive, security risk
- **666 (rw-rw-rw-)** - Anyone can write
- **755 on /var/www/html** - You can't upload files

---

## Permanent Fix

To prevent permission issues in the future:

### 1. Add to VS Code Settings

Create `.vscode/settings.json`:
```json
{
  "sftp.chmod": {
    "file": 664,
    "directory": 775
  }
}
```

### 2. Create Post-Upload Hook

Create `/var/www/html/.sftp-fix-permissions`:
```bash
#!/bin/bash
# Automatically fix permissions after upload
find /var/www/html -type f -newer /var/www/html/.last-upload -exec chmod 664 {} \;
find /var/www/html -type d -newer /var/www/html/.last-upload -exec chmod 775 {} \;
touch /var/www/html/.last-upload
```

### 3. Use rsync Instead of SFTP (Optional)

```bash
# Local machine
rsync -avz --chown=ubuntu:www-data --chmod=D775,F664 \
  /Users/rushabhjoshi/Desktop/jmc-website/ \
  ubuntu@167.114.97.221:/var/www/html/
```

---

## Quick Reference

```bash
# Fix all permissions (run after upload issues)
sudo chown -R ubuntu:www-data /var/www/html
sudo find /var/www/html -type d -exec chmod 775 {} \;
sudo find /var/www/html -type f -exec chmod 664 {} \;
sudo find /var/www/html -type d -exec chmod g+s {} \;

# Fix single file
sudo chown ubuntu:www-data /var/www/html/file.php
sudo chmod 664 /var/www/html/file.php

# Fix single directory
sudo chown ubuntu:www-data /var/www/html/directory
sudo chmod 775 /var/www/html/directory
sudo chmod g+s /var/www/html/directory

# Check your permissions
ls -lh /var/www/html/
groups ubuntu
```

---

## After Running Fix

1. ✅ Upload via SFTP will work without permission errors
2. ✅ Web server can read and execute PHP files
3. ✅ New files automatically have correct permissions
4. ✅ Sensitive files (.env) remain protected
5. ✅ Scripts remain executable

---

**Time to Fix:** 5 minutes
**Frequency:** One-time (persists after fix)
**Impact:** Eliminates all SFTP upload permission issues

---

**Need Help?** If you still have issues after running this fix, check:
1. Are you logged out and back in? (`exit` then `ssh` again)
2. Is ubuntu in www-data group? (`groups ubuntu`)
3. Is setgid bit set? (`ls -ld /var/www/html/` should show 's' in group permissions)
