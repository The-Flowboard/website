# JMC Website
**Last Updated:** March 23, 2026 | **Version:** Production v1.3

---

## Tech Stack

**Frontend:** HTML5, CSS3 (custom glass morphism — no Bootstrap/Tailwind), Vanilla JS
**Backend:** PHP 7.4+, MySQLi, PHP native sessions
**Database:** MySQL/MariaDB — db `jmc_website`, user `jmc_user`, host `localhost`, charset `utf8mb4`
**Automation:** n8n at `https://n8n.joshimc.com`
**Server:** Ubuntu/Apache · SFTP auto-deploy via VS Code extension · `/var/www/html/`
**Dependencies:** PHPOffice/PhpSpreadsheet ^5.3 (Composer) · Google Fonts CDN · Font Awesome 6.4.0 CDN · GA4 `G-5HH2RHZLZ7`

---

## Architecture Overview

**17 PHP files** · **9 HTML pages** · **10 DB tables** · **11 admin AJAX endpoints** · **2 n8n integrations**

```
index.html / about.html / services.html / blog.html / courses.html
contact.html / assessment.html / assessment-results.php
real-estate.html / professional-services.html / financial-services.html
php/           ← backend handlers + APIs + webhook queue
admin/         ← dashboard (login, index, ajax/, includes/)
js/ / css/     ← assessment wizard JS + CSS, cookie consent
images/blog/   ← uploaded blog images (.htaccess blocks PHP)
database/      ← SQL schema + seed files
vendor/        ← Composer packages
```

Key flows:
- **Contact form** → `php/contact_handler.php` → DB → `WebhookQueue::enqueue` → n8n CRM/email
- **Assessment** → `php/process_assessment.php` → scoring engine → top-5 AI opportunities → n8n
- **Blog** → n8n generates content + DALL-E image → `php/upload_blog_image.php` + `php/blog_api.php`
- **Webhook queue** → cron every 5 min → `php/process_webhook_queue.php` → retry with exponential backoff

---

## Key Commands

```bash
# Manual deploy (local → server)
scp -r /Users/rushabhjoshi/Desktop/jmc-website/* ubuntu@167.114.97.221:/var/www/html/

# Fix permissions after deploy
sudo chown -R www-data:www-data /var/www/html
sudo chmod 775 /var/www/html/images/blog/

# Check webhook queue
mysql -u jmc_user -p jmc_website -e "SELECT status, COUNT(*) FROM webhook_queue GROUP BY status;"

# Monitor webhook cron logs
tail -f /var/log/jmc_webhooks.log
```

---

## Hard Rules

1. Never implement without four-lens analysis (Frontend · Backend · Design · Usability)
2. Never mark complete without Chrome DevTools visual verification
3. Never use hardcoded hex colours — always CSS variables
4. Never add a PHP endpoint without API key protection or session check
5. Never modify `db_config.php` — live production credentials
6. Never break existing AJAX endpoints — admin dashboard depends on all 11
7. Always use prepared statements — never string-concatenate SQL
8. Always test forms end-to-end: submit → check DB → confirm n8n fires

---

## Mandatory Change Workflow

```
1. ANALYSE   → Four lenses before touching any file
2. PLAN      → State exactly which files change and why
3. IMPLEMENT → Make the changes
4. VERIFY    → Chrome DevTools MCP visual check — NEVER SKIP
5. REPORT    → Summarise changes + verification result
```

Visual check: 1440px · 768px · 375px · no JS errors · no 4xx/5xx · glass morphism renders

---

## Slash Commands

| Command | Purpose |
|---------|---------|
| `/check-page [page]` | Full visual QA via Chrome DevTools MCP |
| `/add-feature [desc]` | Four-lens plan before building |
| `/fix-bug [desc]` | Root cause → fix → verify |
| `/before-deploy` | Pre-deployment checklist |
| `/design-review` | Full design consistency audit |
| `/update-doc` | Update CLAUDE.md after significant changes |

---

## Documentation

| Doc | Contents |
|-----|----------|
| [docs/blog-engine.md](docs/blog-engine.md) | n8n pipeline, blog_api.php, upload_blog_image.php, DALL-E prompts |
| [docs/solutions-pages.md](docs/solutions-pages.md) | Homepage, services, contact, courses, about — structure + lead flows |
| [docs/industry-pages.md](docs/industry-pages.md) | Real Estate, Professional Services, Financial Services — structure, pain points, solutions, before/after content |
| [docs/assessment-app.md](docs/assessment-app.md) | 17-question wizard, scoring formula, DB schema, n8n payload |
| [docs/deployment.md](docs/deployment.md) | SFTP workflow, permissions, cron, troubleshooting, pre-deploy checklist |
| [.claude/rules/code-style.md](.claude/rules/code-style.md) | PHP, MySQL, JS, CSS, HTML conventions |
| [.claude/rules/copy-guidelines.md](.claude/rules/copy-guidelines.md) | Tone, no hyphens rule, opportunity-focused framing, words to avoid |

---

## Changelog

**v1.3 — Jan 30, 2026** — Webhook queue system · Image Management System · Blog Automation API · Contact form updated (first/last name, company, referral_source)
**v1.2 — Dec 30, 2025** — Blog image upload · `blog_post.php` styling · n8n contact webhook · Admin status badges
**v1.1 — Dec 23, 2025** — AI Opportunity Assessment System (17-question wizard, scoring engine, 3 new DB tables)
**v1.0 — Dec 19, 2025** — Initial full-stack site: all pages, admin dashboard, MySQL schema, SFTP, GA4
