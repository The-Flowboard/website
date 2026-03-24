# Manual Deployment Setup Guide
**Status:** Server temporarily blocking automated SSH key installation
**Solution:** Manual setup (5 minutes)

---

## 🔧 STEP-BY-STEP SETUP

### Step 1: Install VS Code SFTP Extension (if not already installed)

1. Open VS Code
2. Go to Extensions (Cmd+Shift+X)
3. Search for "SFTP" by Natizyskunk
4. Click Install
5. Restart VS Code

---

### Step 2: Configure VS Code SFTP with SSH Key

The SFTP config file has already been created at `.vscode/sftp.json` with these settings:

```json
{
    "name": "JMC Production Server",
    "host": "167.114.97.221",
    "protocol": "sftp",
    "port": 22,
    "username": "ubuntu",
    "privateKeyPath": "/Users/rushabhjoshi/.ssh/id_ed25519",
    "remotePath": "/var/www/html",
    "uploadOnSave": true,
    "useTempFile": false,
    "openSsh": true,
    "ignore": [...],
    "watcher": {
        "files": "**/*.{html,css,js,php,json}",
        "autoUpload": true,
        "autoDelete": false
    }
}
```

**This config will work IF:**
- Your SSH key is already authorized on the server, OR
- Password authentication works through VS Code's SFTP extension

---

### Step 3: Test VS Code SFTP Connection

1. Open VS Code
2. Open any file (e.g., index.html)
3. Make a small change (add a comment: `<!-- test -->`)
4. Save the file (Cmd+S)
5. **Watch the Output panel:**
   - View → Output
   - Select "SFTP" from dropdown
6. **Expected outcomes:**

**✅ Success:**
```
[info] index.html uploaded to /var/www/html/index.html
```

**⚠️ Password Prompt:**
- VS Code SFTP will prompt for password
- Enter: `quxqof-sYkzim-7xymva`
- Check "Remember password" checkbox
- Click OK

**❌ Permission Denied:**
- Continue to Step 4 (manual SSH key installation)

---

### Step 4: Manual SSH Key Installation (If Step 3 Failed)

Since automated `ssh-copy-id` failed, we'll install the key manually:

**Option A: Via Web Panel (Recommended)**

If your server has a web panel (cPanel, Plesk, etc.):

1. Log in to your hosting control panel
2. Find "SSH Access" or "SSH Keys" section
3. Add this public key:
   ```
   ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIA+kAEdvzx2Xw6vqWUubAPySvUTlLwLx99yewxWURg5b your-email@example.com
   ```
4. Save and test

**Option B: Via Emergency Console Access**

If you have console/terminal access to the server:

1. Access server console
2. Run these commands:
   ```bash
   mkdir -p ~/.ssh
   chmod 700 ~/.ssh
   echo "ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIA+kAEdvzx2Xw6vqWUubAPySvUTlLwLx99yewxWURg5b your-email@example.com" >> ~/.ssh/authorized_keys
   chmod 600 ~/.ssh/authorized_keys
   ```
3. Test from local terminal:
   ```bash
   ssh -i ~/.ssh/id_ed25519 ubuntu@167.114.97.221
   ```

**Option C: Contact Hosting Provider**

If Options A & B don't work:

1. Contact your hosting provider's support
2. Ask them to add your SSH public key to the ubuntu user account
3. Provide them this public key:
   ```
   ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIA+kAEdvzx2Xw6vqWUubAPySvUTlLwLx99yewxWURg5b
   ```

---

### Step 5: Fix File Permissions (After Upload Working)

Once uploads are working, you need to fix file permissions on the server.

**Option A: Use the quick-deploy script**
```bash
./quick-deploy.sh fix-perms
```

**Option B: SSH directly**
```bash
ssh ubuntu@167.114.97.221
sudo chown -R www-data:www-data /var/www/html
sudo find /var/www/html -type f -exec chmod 644 {} \;
sudo find /var/www/html -type d -exec chmod 755 {} \;
sudo chmod 775 /var/www/html/images/blog
sudo usermod -a -G www-data ubuntu
sudo chmod g+s /var/www/html
```

**Option C: Use password with VS Code SFTP**

If VS Code SFTP is working with password, you can create a script on the server:

1. Create `/tmp/fix-perms.sh` on the server with these commands
2. Upload it via VS Code SFTP
3. SSH to server and run: `bash /tmp/fix-perms.sh`

---

## ⚡ ALTERNATIVE: Use Password with VS Code SFTP

**Quickest solution if SSH key setup is complex:**

1. Update `.vscode/sftp.json` to use password instead of SSH key:

```json
{
    "name": "JMC Production Server",
    "host": "167.114.97.221",
    "protocol": "sftp",
    "port": 22,
    "username": "ubuntu",
    "password": "quxqof-sYkzim-7xymva",
    "remotePath": "/var/www/html",
    "uploadOnSave": true,
    "useTempFile": false,
    "openSsh": false,
    "ignore": [
        ".vscode",
        ".git",
        ".DS_Store",
        "node_modules",
        "*.md",
        "*.sh",
        ".env.example",
        "vendor",
        "composer.lock",
        ".github",
        "docs",
        "database",
        "n8n",
        "*.backup*",
        "ARCHIVE_*"
    ],
    "watcher": {
        "files": "**/*.{html,css,js,php,json}",
        "autoUpload": true,
        "autoDelete": false
    },
    "syncOption": {
        "delete": false,
        "skipCreate": false,
        "ignoreExisting": false,
        "update": true
    }
}
```

**Changes:**
- Removed `privateKeyPath`
- Added `password: "quxqof-sYkzim-7xymva"`
- Changed `openSsh: false`

2. Save the file
3. Test upload (make a change and save any file)
4. ✅ Should work immediately!

**⚠️ Security Note:**
- Password is stored in plain text in sftp.json
- File is excluded from Git (.gitignore)
- Only accessible on your local machine
- This is fine for personal projects
- For better security, use SSH key (Step 4)

---

## 🎯 RECOMMENDED QUICK FIX

**For immediate auto-upload (30 seconds):**

1. Update `.vscode/sftp.json` with password (see above)
2. Open index.html in VS Code
3. Add a comment: `<!-- test -->`
4. Save (Cmd+S)
5. Check Output → SFTP
6. ✅ Should see "Upload success!"

**This will enable auto-upload immediately while we fix SSH key authentication separately.**

---

## ✅ TESTING AUTO-UPLOAD

After setup, test it:

1. Open `index.html` in VS Code
2. Add a comment somewhere: `<!-- Auto-upload test -->`
3. Save the file (Cmd+S)
4. **Check VS Code Output:**
   - View → Output
   - Select "SFTP" from dropdown
   - Should see: `[info] index.html uploaded to /var/www/html/index.html`
5. **Verify on production:**
   - Visit https://joshimc.com
   - View page source (Cmd+Option+U)
   - Find your comment
   - ✅ It's there = Upload working!

---

## 🔄 WHAT'S NEXT

Once auto-upload is working:

1. **Deploy all pending changes:**
   - Font Awesome removal (11 files)
   - Any other uncommitted changes

2. **Fix permissions on server:**
   ```bash
   ./quick-deploy.sh fix-perms
   ```

3. **Test everything:**
   - Visit website
   - Check mobile menu
   - Test forms
   - Run Lighthouse audit

4. **Commit to Git:**
   ```bash
   git add .
   git commit -m "Fix: Remove Font Awesome, enable auto-upload"
   git push origin main
   ```

---

## 📞 NEED HELP?

If you're stuck:

1. **VS Code SFTP not working:**
   - Check extension is installed
   - Check .vscode/sftp.json exists
   - Try using password instead of SSH key (see Alternative above)
   - Check Output → SFTP for error messages

2. **Files uploading but not visible:**
   - Fix permissions: `./quick-deploy.sh fix-perms`
   - Clear browser cache (Cmd+Shift+R)
   - Check file ownership on server

3. **SSH key won't install:**
   - Use password method (Alternative above)
   - Contact hosting provider
   - Use web panel to add SSH key

---

**Created:** February 1, 2026
**Purpose:** Manual workaround for automated deployment setup
**Next Steps:** Enable auto-upload → Deploy changes → Test → Commit to Git
