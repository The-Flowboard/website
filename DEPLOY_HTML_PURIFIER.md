# HTML Purifier Deployment Instructions

**Date:** January 31, 2026
**Task:** Deploy XSS protection (HTML Purifier) to production

## Files Changed

1. ✅ `composer.json` - Added HTML Purifier dependency
2. ✅ `php/html_sanitizer.php` - NEW FILE - Sanitization utility
3. ✅ `admin/ajax/save_blog.php` - Updated to sanitize blog content
4. ✅ `php/blog_api.php` - Updated to sanitize API inputs

## Deployment Steps

### Option A: Automatic Deployment via VS Code SFTP Extension

If you have VS Code open with the SFTP extension configured:

1. Open VS Code
2. Right-click on each file and select "SFTP: Upload"
   - composer.json
   - php/html_sanitizer.php
   - admin/ajax/save_blog.php
   - php/blog_api.php

### Option B: Manual SFTP Upload

```bash
# Using SFTP command line
sftp ubuntu@167.114.97.221

# Once connected, upload files:
put /Users/rushabhjoshi/Desktop/jmc-website/composer.json /var/www/html/composer.json
put /Users/rushabhjoshi/Desktop/jmc-website/php/html_sanitizer.php /var/www/html/php/html_sanitizer.php
put /Users/rushabhjoshi/Desktop/jmc-website/admin/ajax/save_blog.php /var/www/html/admin/ajax/save_blog.php
put /Users/rushabhjoshi/Desktop/jmc-website/php/blog_api.php /var/www/html/php/blog_api.php

# Exit SFTP
bye
```

### Option C: Using SCP (Batch Upload)

```bash
# Upload all files at once
scp /Users/rushabhjoshi/Desktop/jmc-website/composer.json \
    ubuntu@167.114.97.221:/var/www/html/composer.json

scp /Users/rushabhjoshi/Desktop/jmc-website/php/html_sanitizer.php \
    ubuntu@167.114.97.221:/var/www/html/php/html_sanitizer.php

scp /Users/rushabhjoshi/Desktop/jmc-website/admin/ajax/save_blog.php \
    ubuntu@167.114.97.221:/var/www/html/admin/ajax/save_blog.php

scp /Users/rushabhjoshi/Desktop/jmc-website/php/blog_api.php \
    ubuntu@167.114.97.221:/var/www/html/php/blog_api.php
```

Password: `quxqof-sYkzim-7xymva`

## Post-Deployment: Install Composer Dependencies

After uploading files, SSH into the server and run composer install:

```bash
# SSH into server
ssh ubuntu@167.114.97.221
# Password: quxqof-sYkzim-7xymva

# Navigate to web root
cd /var/www/html

# Install HTML Purifier via Composer
composer install --no-dev --optimize-autoloader

# Verify HTML Purifier is installed
ls -la vendor/ezyang/htmlpurifier

# Set correct permissions
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html

# Exit SSH
exit
```

## Verification

1. **Test Admin Blog Creation:**
   - Log into admin dashboard: https://joshimc.com/admin/
   - Create a new blog post with HTML content
   - Verify content is saved without errors

2. **Test XSS Protection:**
   - Try creating a blog post with malicious script:
     ```html
     <script>alert('XSS')</script>
     <p>Normal content</p>
     ```
   - Verify the `<script>` tag is removed but `<p>` tag remains

3. **Test API Blog Creation:**
   - Use the n8n blog automation workflow
   - Verify blogs are created successfully with images

## Rollback (If Needed)

If anything breaks, restore the old files:

```bash
# Restore old save_blog.php (no sanitization)
# Restore old blog_api.php (no sanitization)
# Remove html_sanitizer.php
# Restore old composer.json
```

## Expected Impact

- ✅ XSS attacks prevented in blog content
- ✅ All HTML content sanitized on save
- ✅ Malicious scripts automatically removed
- ✅ Safe HTML formatting preserved
- ✅ No impact on existing functionality

## Completion Checklist

- [ ] Files uploaded to production
- [ ] Composer dependencies installed
- [ ] Permissions set correctly
- [ ] Admin blog creation tested
- [ ] XSS protection verified
- [ ] API blog creation tested
- [ ] No errors in error logs

---

**Next Task:** Input validation framework (Month 2-3 Task #2)
