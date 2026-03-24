# 🚀 Auto-Upload Quick Start Guide
**Setup Time:** 2 minutes
**Result:** Files automatically upload to production when you save in VS Code!

---

## ✅ SETUP COMPLETE!

The VS Code SFTP extension is now configured at `.vscode/sftp.json`

**What's configured:**
- ✅ Password authentication (immediate, no SSH key setup needed)
- ✅ Auto-upload on save enabled
- ✅ Server: 167.114.97.221
- ✅ Path: /var/www/html
- ✅ Upload on save: **ENABLED**

---

## 🎯 HOW TO USE

### 1. Install VS Code SFTP Extension (if not already installed)

**In VS Code:**
1. Press `Cmd+Shift+X` (Extensions)
2. Search for: **"SFTP"** by Natizyskunk
3. Click **Install**
4. Reload VS Code if prompted

---

### 2. Test Auto-Upload NOW

**Let's test with index.html:**

1. **Open index.html** in VS Code
2. **Add a test comment** anywhere (e.g., line 10):
   ```html
   <!-- Auto-upload test - Feb 1, 2026 -->
   ```
3. **Save the file** (Cmd+S or Ctrl+S)
4. **Watch the magic happen:**
   - Bottom-right corner: "Uploading..." notification
   - Status bar: Upload progress
   - Output panel (View → Output → Select "SFTP"): Upload details

**Expected output:**
```
[info] /Users/rushabhjoshi/Desktop/jmc-website/index.html uploaded to /var/www/html/index.html
```

5. **Verify on production:**
   - Visit: https://joshimc.com
   - View source (Cmd+Option+U or Ctrl+U)
   - Search for your comment
   - ✅ **It's there!** Auto-upload is working!

---

## 📝 WHAT FILES AUTO-UPLOAD

**Automatically uploads when you save:**
- ✅ `*.html` - HTML files
- ✅ `*.css` - CSS stylesheets
- ✅ `*.js` - JavaScript files
- ✅ `*.php` - PHP backend files
- ✅ `*.json` - JSON data files

**Ignored (won't upload):**
- ❌ `*.md` - Documentation (not needed on production)
- ❌ `*.sh` - Shell scripts
- ❌ `.env` - Environment variables (security)
- ❌ `vendor/` - Composer packages (install on server)
- ❌ `.git/` - Git repository
- ❌ `.vscode/` - VS Code settings

---

## 🎨 NEXT STEP: Deploy Font Awesome Changes

Now that auto-upload works, let's deploy the Font Awesome removal:

**Option 1: Auto-upload (Recommended - Easy)**

1. Open each of these 11 files in VS Code:
   - index.html
   - about.html
   - services.html
   - blog.html
   - courses.html
   - contact.html
   - assessment.html
   - privacy-policy.html
   - terms-of-service.html
   - 404.html
   - css/main-styles.css

2. For each file:
   - Open it (VS Code will show it's already modified)
   - Save it (Cmd+S)
   - ✅ File automatically uploads!

3. After all 11 files upload, test:
   - Visit https://joshimc.com
   - Resize browser to mobile view (<1024px)
   - Click hamburger menu icon
   - ✅ Should display correctly (3 horizontal lines)

**Option 2: Bulk Upload (Faster)**

```bash
# Upload all modified files at once
./quick-deploy.sh all
```

---

## 🔧 TROUBLESHOOTING

### "Connection failed" or "Permission denied"

**Check SFTP extension output:**
1. View → Output
2. Select "SFTP" from dropdown
3. Read error message

**Common fixes:**

**Error: "Authentication failed"**
- Check `.vscode/sftp.json` has correct password
- Reload VS Code

**Error: "Connection timeout"**
- Check internet connection
- Try again in a few minutes (server may be busy)

**Error: "Upload failed - Permission denied"**
- Files uploaded but wrong permissions
- Fix: Run `./quick-deploy.sh fix-perms`

---

### Upload succeeded but changes not visible on website

**Fix:**
```bash
# Fix file permissions on server
./quick-deploy.sh fix-perms
```

**Or manually:**
```bash
ssh ubuntu@167.114.97.221
sudo chown -R www-data:www-data /var/www/html
sudo find /var/www/html -type f -exec chmod 644 {} \;
```

---

### Want to disable auto-upload temporarily?

Edit `.vscode/sftp.json`:
```json
{
    "uploadOnSave": false,  // Changed from true
    ...
}
```

Save the file. Auto-upload is now disabled.

**Re-enable:** Change back to `true` and save.

---

## 📊 AUTO-UPLOAD STATUS

**Check upload status in real-time:**

1. **Bottom-right notifications:**
   - "Uploading..." → Upload in progress
   - "Upload success!" → File uploaded
   - "Upload failed!" → Check Output panel

2. **Status bar (bottom):**
   - Shows current upload progress
   - Click for details

3. **Output panel:**
   - View → Output → Select "SFTP"
   - Shows detailed upload log
   - Keep this open while testing

---

## 🎯 WORKFLOW EXAMPLES

### Editing a single file
1. Open `index.html`
2. Make changes
3. Save (Cmd+S)
4. ✅ Auto-uploaded!

### Editing multiple files
1. Open all files you want to edit
2. Make changes to each
3. Save each file (Cmd+S on each)
4. ✅ Each file auto-uploads when saved!

### Batch editing
1. Make changes to multiple files
2. File → Save All (Cmd+Option+S)
3. ✅ All files upload at once!

---

## ✨ BENEFITS OF AUTO-UPLOAD

**Before (manual deployment):**
- Edit file locally
- Run `scp` command
- Enter password
- Fix permissions
- Test on production
- ⏱️ **~2 minutes per file**

**After (auto-upload):**
- Edit file
- Press Cmd+S
- ✅ **~2 seconds per file**

**Time saved:** ~1 minute 58 seconds per file!

For 11 files (Font Awesome removal):
- Manual: ~22 minutes
- Auto: ~22 seconds
- **Saved: 21 minutes 38 seconds!** ⚡

---

## 📋 CHECKLIST

Complete this checklist to confirm auto-upload is working:

- [ ] VS Code SFTP extension installed
- [ ] `.vscode/sftp.json` file exists
- [ ] Opened index.html in VS Code
- [ ] Added test comment
- [ ] Saved file (Cmd+S)
- [ ] Saw "Upload success!" notification
- [ ] Checked Output → SFTP (shows upload log)
- [ ] Visited https://joshimc.com
- [ ] Viewed source, found test comment
- [ ] ✅ **Auto-upload confirmed working!**

---

## 🚀 READY TO DEPLOY

Once auto-upload is confirmed working:

1. **Deploy Font Awesome changes** (11 files)
2. **Fix permissions:** `./quick-deploy.sh fix-perms`
3. **Test on production:** Visit website, test mobile menu
4. **Commit to Git:** Save changes for version control
5. **Update progress tracker**
6. ✅ **Task complete!**

---

**Created:** February 1, 2026
**Status:** Auto-upload enabled with password authentication
**Next:** Test upload → Deploy Font Awesome → Fix permissions → Celebrate! 🎉
