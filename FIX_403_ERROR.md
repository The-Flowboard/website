# Fix 403 Error - HTML Purifier Missing

**Error:** `ajax/save_blog.php:1 Failed to load resource: the server responded with a status of 403 ()`

**Root Cause:** HTML Purifier library not installed on server (composer install wasn't run)

---

## Quick Fix (Choose One Method)

### Method 1: Run Diagnostic First (Recommended)

1. **Upload diagnostic script:**
   ```bash
   scp check_htmlpurifier.php ubuntu@167.114.97.221:/var/www/html/
   ```

2. **Visit in browser:**
   ```
   https://joshimc.com/check_htmlpurifier.php
   ```

3. **Follow the recommendations** shown on the diagnostic page

4. **Delete diagnostic script when done:**
   ```bash
   ssh ubuntu@167.114.97.221
   rm /var/www/html/check_htmlpurifier.php
   ```

---

### Method 2: Direct SSH Fix (Fast)

```bash
# 1. SSH into server
ssh ubuntu@167.114.97.221
# Password: quxqof-sYkzim-7xymva

# 2. Navigate to web root
cd /var/www/html

# 3. Install HTML Purifier via Composer
composer install --no-dev --optimize-autoloader

# 4. Verify installation
ls -la vendor/ezyang/htmlpurifier

# 5. Set correct permissions
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html

# 6. Check for errors
tail -n 50 /var/log/apache2/error.log

# 7. Exit
exit
```

---

### Method 3: Temporary Workaround (If composer fails)

If composer install fails or you need to test immediately, temporarily disable HTML Purifier:

**Create a fallback version of html_sanitizer.php:**

```php
<?php
// Temporary fallback - NO XSS protection (use only for testing)

function sanitizeBlogContent($html) {
    // WARNING: This does NOT provide XSS protection!
    return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
}

function sanitizePlainText($text) {
    return htmlspecialchars(strip_tags($text), ENT_QUOTES, 'UTF-8');
}

function sanitizeBlogMetadata($text, $allow_formatting = false) {
    return sanitizePlainText($text);
}

function sanitizeImageUrl($url) {
    return filter_var($url, FILTER_SANITIZE_URL);
}
?>
```

⚠️ **IMPORTANT:** This is ONLY for testing. You MUST install HTML Purifier for production!

---

## Verification Steps

After running composer install:

1. **Test in browser:**
   - Visit: https://joshimc.com/admin/
   - Try creating a blog post
   - Should save without 403 error

2. **Test XSS protection:**
   - Create a blog post with content:
     ```html
     <script>alert('XSS')</script>
     <h2>Test Heading</h2>
     <p>Normal paragraph</p>
     ```
   - Save the post
   - Verify the `<script>` tag was removed
   - Verify `<h2>` and `<p>` tags remain

3. **Check logs:**
   ```bash
   ssh ubuntu@167.114.97.221
   tail -f /var/log/apache2/error.log
   # (Try creating a blog post while watching logs)
   ```

---

## Common Issues & Solutions

### Issue: "composer: command not found"

**Solution:**
```bash
# Install Composer on server
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
```

### Issue: "Your requirements could not be resolved"

**Solution:**
```bash
# Clear composer cache
composer clear-cache

# Try again
composer install --no-dev --optimize-autoloader
```

### Issue: Permission denied errors

**Solution:**
```bash
# Fix ownership
sudo chown -R www-data:www-data /var/www/html

# Fix permissions
sudo find /var/www/html -type d -exec chmod 755 {} \;
sudo find /var/www/html -type f -exec chmod 644 {} \;

# Make vendor executable if needed
sudo chmod -R 755 /var/www/html/vendor
```

### Issue: Still getting 403 after composer install

**Check these:**

1. **Verify HTML Purifier installed:**
   ```bash
   ls -la /var/www/html/vendor/ezyang/htmlpurifier
   ```

2. **Check PHP error log:**
   ```bash
   tail -n 100 /var/log/apache2/error.log | grep -i "purifier\|fatal\|error"
   ```

3. **Test PHP can load the class:**
   ```bash
   php -r "require '/var/www/html/vendor/autoload.php'; echo class_exists('HTMLPurifier') ? 'OK' : 'FAIL';"
   ```
   Should output: `OK`

4. **Verify file permissions:**
   ```bash
   ls -la /var/www/html/php/html_sanitizer.php
   ls -la /var/www/html/admin/ajax/save_blog.php
   ```
   Should be: `644 www-data:www-data`

---

## Expected Output After Fix

**Before (Current - Broken):**
```
403 Forbidden
ajax/save_blog.php failed to load
```

**After (Fixed - Working):**
```
Blog post saved successfully ✓
XSS protection active
Malicious scripts removed
```

---

## Need Help?

If you still get errors after trying these solutions:

1. Run the diagnostic script (Method 1 above)
2. Copy the full output
3. Check the error log:
   ```bash
   ssh ubuntu@167.114.97.221
   tail -n 100 /var/log/apache2/error.log
   ```
4. Share the error messages

---

**Last Updated:** January 31, 2026
**Related Files:**
- check_htmlpurifier.php (diagnostic script)
- php/html_sanitizer.php (sanitization utility)
- admin/ajax/save_blog.php (blog save handler)
