# Deployment

## Environment

| | Local | Server |
|-|-------|--------|
| Path | `/Users/rushabhjoshi/Desktop/jmc-website/` | `/var/www/html/` |
| OS | macOS | Ubuntu / Apache |
| PHP | 7.4+ | 7.4+ |
| DB | — | MySQL/MariaDB `jmc_website` |

## Credentials

```
Database:    localhost / jmc_website / jmc_user / Sphinx208! / utf8mb4
SFTP:        167.114.97.221:22 / ubuntu / quxqof-sYkzim-7xymva
Local path:  /Users/rushabhjoshi/Desktop/jmc-website/
Server path: /var/www/html/
Images dir:  /var/www/html/images/blog/
```

## Normal Workflow (VS Code SFTP)

The VS Code SFTP extension is configured with `uploadOnSave: true`. Save a file locally → it auto-uploads to `/var/www/html/`. Config: `.vscode/sftp.json`. Ignored paths: `.vscode`, `.git`, `.DS_Store`, `node_modules`.

## Manual Batch Operations

```bash
# Push everything local → server
scp -r /Users/rushabhjoshi/Desktop/jmc-website/* ubuntu@167.114.97.221:/var/www/html/

# Pull server → local (sync production back)
scp -r ubuntu@167.114.97.221:/var/www/html/* /Users/rushabhjoshi/Desktop/jmc-website/
```

## File Permissions

Run after any batch deploy:

```bash
sudo chown -R www-data:www-data /var/www/html
sudo find /var/www/html -type f -exec chmod 644 {} \;
sudo find /var/www/html -type d -exec chmod 755 {} \;
sudo chmod 775 /var/www/html/images/blog/

# Lock down sensitive files
sudo chmod 600 /var/www/html/php/db_config.php
sudo chmod 600 /var/www/html/admin/includes/auth.php
sudo chmod 600 /var/www/html/.vscode/sftp.json
```

## Cron Jobs (server)

```bash
# Webhook queue processor — runs every 5 minutes
*/5 * * * * php /var/www/html/php/process_webhook_queue.php >> /var/log/jmc_webhooks.log
```

## Pre-Deploy Checklist

**PHP**
- [ ] No syntax errors in `php/` and `admin/ajax/`
- [ ] No `var_dump` / `print_r` debug output left in
- [ ] All admin AJAX files have session check at top
- [ ] DB credentials correct in `db_config.php` (never change this file)

**Frontend**
- [ ] All pages link to correct CSS/JS paths (no localhost paths)
- [ ] GA tag `G-5HH2RHZLZ7` present on all public pages
- [ ] No TODO comments in production files

**Security**
- [ ] API keys match production values
- [ ] `.htaccess` present in `/images/blog/` (blocks PHP execution)
- [ ] No credentials in JS files

**Visual**
- [ ] Chrome DevTools spot-check: homepage, contact, blog

## Troubleshooting

| Symptom | Check |
|---------|-------|
| 500 error | `/var/log/apache2/error.log` · file permissions |
| DB connection failed | MySQL running · test with `php/test_db.php` |
| Blog images not uploading | `/images/blog/` exists · permissions 775 · `upload_max_filesize` in php.ini |
| Admin dashboard blank | Clear browser cache · verify session · JS errors in console |
| Contact form not submitting | DB credentials · n8n webhook URL reachable |
| n8n image upload failing | API key correct (Bearer format) · `image_data` must be `data:image/TYPE;base64,...` · check `/images/blog/` perms |
| Assessment not resending | n8n webhook URL accessible · assessment ID exists · n8n workflow active |
| Contact form missing fields | Run `database/contact_form_schema.sql` migration · clear browser cache |
| Webhook queue stuck | `SELECT status, COUNT(*) FROM webhook_queue GROUP BY status;` · check cron running |
