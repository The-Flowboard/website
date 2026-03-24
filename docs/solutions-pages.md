# Solutions Pages

The five core public-facing pages that explain JMC's offerings and capture leads.

## Page Inventory

### Homepage (`index.html`, 1719 lines)
Animated gradient hero · fixed nav · services showcase · testimonials carousel · YouTube modal · footer with social links · GA4 tracking · cookie consent banner. Primary CTA: contact form.

### Services (`services.html`)
Overview of JMC's AI consulting service lines. Feeds into contact form CTA.

### Courses (`courses.html`)
AI training / course offering. Includes email interest capture form → `php/courses_interest_handler.php` → inserts to `courses_interest` table.

### About (`about.html`)
Team, mission, credentials. Trust-building page for B2B prospects.

### Contact (`contact.html`, 1415 lines)
Primary lead capture form. Fields: `first_name`, `last_name`, `email`, `phone`, `company`, `referral_source` (dropdown: Search Engine / YouTube / Instagram / LinkedIn / Referral / Other), `message`, marketing consent checkbox.

**Submit flow:** HTML5 + JS client validation → `POST php/contact_handler.php` → server validates 7 required fields + email format → INSERT `contact_submissions` (status='new') → `WebhookQueue::enqueue('contact_form', payload)` (non-blocking) → JSON response → toast notification on success.

**n8n payload:**
```json
{
  "id": 1,
  "first_name": "...",
  "last_name": "...",
  "email": "...",
  "phone": "...",
  "company": "...",
  "referral_source": "LinkedIn",
  "message": "...",
  "consent_marketing": 1,
  "status": "new",
  "submitted_at": "..."
}
```

Webhook: `https://n8n.joshimc.com/webhook/529e4b39-b4a7-491d-bfe8-3e7d2d0c7936`

## Design Patterns (all five pages)

All pages share the same glass morphism design system:
- Background: `var(--primary-deep)` (#0a1128)
- Cards/panels: `background: rgba(255,255,255,0.05)` + `backdrop-filter: blur(20px)` + `border: 1px solid rgba(255,255,255,0.1)` + `border-radius: 16px`
- Primary CTAs: `var(--accent-purple)` (#a855f7)
- Links/highlights: `var(--accent-cyan)` (#06b6d4)
- Fonts: Sora (headings) + Outfit (body) via Google Fonts CDN
- Icons: Font Awesome 6.4.0 CDN

## Navigation

Fixed nav shared across all pages. Collapses at 1024px breakpoint. Links: Home · About · Services · Blog · Courses · Contact · Assessment CTA button.

## Lead Capture Database Table: `contact_submissions`

Columns: `id` · `first_name` · `last_name` · `email` · `phone` · `company` · `message` · `referral_source` · `consent_marketing` (TINYINT) · `consent_timestamp` · `status` (new/contacted/qualified/proposal_sent/won/lost/nurture) · `contacted_at` · `ip_address` · `user_agent` · `submitted_at`

Status is updated by n8n calling `php/update_lead_status.php` (auth: `api_key` JSON field = `jmc_n8n_webhook_key_8f9d2a3c5e7b1d4f6a8c`).
