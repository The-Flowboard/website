# Blog Engine

## Overview

n8n drives automated blog publishing end-to-end. The site exposes two PHP APIs that n8n calls; humans never need to touch the DB directly.

## API Endpoints

**`php/blog_api.php`** — RESTful CRUD. Auth: `Authorization: Bearer KjGgRa5qd8Yzs3Di1Ayew2Qksn8cGH2P9OYdqlwOxhY=`. CORS enabled.

| Method | Params | Returns |
|--------|--------|---------|
| `GET ?action=list&status=published&limit=10&offset=0` | — | Array of posts |
| `GET ?action=get&slug=X` | — | Single post + increments views |
| `POST` | JSON blog data in body | `{success, id}` |
| `PUT ?id=X` | JSON fields to update in body | `{success, message}` |
| `DELETE ?id=X` | — | `{success, message}` |

**`php/upload_blog_image.php`** — Same Bearer auth. Two upload modes:
- **(A) base64** — JSON body `{image_data: "data:image/TYPE;base64,...", filename: "name.ext"}` — used by n8n after DALL-E generates image
- **(B) multipart** — form-data `image` field — used by browser/admin uploads

Validation: finfo MIME check → extension whitelist (jpg/jpeg/png/webp) → double extension rejection → 5 MB max. Output filename: `blog_{TIMESTAMP}_{RANDOM_HEX}.{EXT}`. Returns `{success, path, filename, message}`.

## n8n Automation Pipeline

```
Schedule/manual trigger
  → LLM generates title, slug, category, HTML content, excerpt
  → Select category-specific DALL-E prompt (text-free)
  → DALL-E 3 HD 1792×1024, b64_json ($0.08/image)
  → Convert response to data URI
  → POST upload_blog_image.php (Bearer) → receive image path
  → POST blog_api.php with full post + image path → {success, id}
  → Notify team via Slack/email
```

## DALL-E Prompt Categories

All prompts MUST produce text-free images.

| Category | Visual style |
|----------|-------------|
| AI Trends | Neural networks, glowing nodes, futuristic tech |
| Implementation Tips | Gears, mechanisms, workflow diagrams |
| Business Strategy | Hub networks, trending data lines |
| AI Tools | Floating UI elements, interface components |
| Industry Insights | Data visualisation, analytics dashboards |

## Blog Post Renderer

`php/blog_post.php` — dynamic, `?slug=post-slug`. Fetches from DB, auto-increments views. Max content width 1200px. Heading sizes: H1 1.75–2.25 rem, H2 1.5–1.875 rem. Styled: links (cyan), code blocks (monospace + bg), images (rounded + shadow). SEO meta pulled from `blog_posts.meta_description` and `meta_keywords`.

## Admin Blog Editor

Located in `admin/index.php` (Blog Posts tab). Fields: title, slug (auto-generated, editable), category, excerpt, content (raw HTML textarea), featured image (drag-drop / click-to-browse / "Browse Uploaded Images" picker), meta description, status (published/draft). Image uploads go through `admin/ajax/upload_image.php` (same validation as above).

## Database Table: `blog_posts`

Columns: `id` · `title` · `slug` (UNIQUE) · `excerpt` · `content` (LONGTEXT) · `category` · `featured_image` · `meta_description` (160 char) · `meta_keywords` · `author` (default: 'Joshi Management Consultancy') · `views` · `status` (published/draft) · `published_at` · `created_at` · `updated_at`

Categories enum: AI Trends / Implementation Tips / Case Studies / Business Strategy / AI Tools / Industry Insights
