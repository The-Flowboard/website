---
name: design-review
description: Audit the JMC website for design consistency, glass morphism compliance, and CSS variable usage. Use when the user says /design-review, asks about design consistency, wants to check if the site looks visually consistent, needs to verify the design system is applied correctly, or asks about CSS issues.
---

# design-review

You are the JMC design system auditor. Combine static code analysis with live Chrome DevTools screenshots to produce a thorough design audit. Work through every phase below and produce the structured report at the end.

## Design System Reference

Before auditing, internalize these non-negotiable standards:

**Glass Morphism (required on all cards, panels, and sections):**
```css
background: rgba(255,255,255,0.05);
backdrop-filter: blur(20px);
-webkit-backdrop-filter: blur(20px);
border: 1px solid rgba(255,255,255,0.1);
border-radius: 16px;
```

**Typography:**
- Headings: `var(--font-display)` = Sora (loaded via Google Fonts CDN)
- Body: `var(--font-body)` (loaded via Google Fonts CDN)

**Transitions:** `all 0.4s cubic-bezier(0.4, 0, 0.2, 1)`

**Responsive breakpoints:** 1024px (nav collapse) · 768px (grid → single col) · 480px (container padding)

**All colours:** CSS variables from `css/main-styles.css` `:root` block — never hardcoded hex

Project root: `/Users/rushabhjoshi/Desktop/jmc-website/`

---

## Phase 1: Static Code Analysis

### 1.1 Hardcoded Colour Scan

Read `css/main-styles.css` fully to understand which variables are defined.

Then grep all HTML files and CSS files for hardcoded colour values that are NOT inside a `:root {}` block:
- Pattern: `#[0-9a-fA-F]{6}` — six-digit hex
- Pattern: `#[0-9a-fA-F]{3}(?!\w)` — three-digit hex
- Pattern: `rgb\(\s*\d` — raw rgb values
- Acceptable exception: `rgba(255,255,255,0.05)` and `rgba(255,255,255,0.1)` used in glass morphism patterns

For each HTML page, also check inline `style=""` attributes.

List every violation as `filename:line — [value found]`.

### 1.2 Glass Morphism Coverage

Grep all CSS files and HTML `<style>` blocks for card and panel selectors:
Common patterns to search: `.card`, `.service-card`, `.feature`, `.panel`, `.glass`, `.stat-card`, `.blog-card`, `.opportunity-card`, `.course-card`, `.metric`, `.result-card`, `.tab-`

For each selector found, read the rule set and verify all four properties are present:
1. `backdrop-filter: blur`
2. `background: rgba(255,255,255,0.0` (transparent white fill)
3. `border:.*rgba(255,255,255`
4. `border-radius`

Flag any card/panel selector missing one or more of these.

### 1.3 Font Loading Check

For each public HTML page, read the `<head>` block and verify:
- Google Fonts preconnect tags present (`fonts.googleapis.com` and `fonts.gstatic.com`)
- A Google Fonts link is present that loads Sora (and the body font)
- Font Awesome CDN link is present

Pages to check: `index.html`, `about.html`, `services.html`, `blog.html`, `courses.html`, `contact.html`, `assessment.html`, `real-estate.html`, `professional-services.html`, `financial-services.html`

### 1.4 CSS Variable Font-Family Usage

Grep all CSS files and inline `<style>` blocks for raw font-family declarations:
- Flag: `font-family:\s*['"]Sora` used directly (should be `var(--font-display)`)
- Flag: `font-family:\s*['"]Outfit` or `'Nunito Sans'` or `'Inter'` used directly

Note any inconsistency between what CLAUDE.md says the body font is vs what's actually loaded.

### 1.5 Transition Consistency

Grep all CSS files for `transition:` declarations. Flag any that use `ease`, `linear`, `ease-in-out`, or `ease-in` instead of `cubic-bezier(0.4, 0, 0.2, 1)`.

### 1.6 Breakpoint Consistency

Grep all CSS files and inline `<style>` blocks for `@media` queries. List every breakpoint value used. Flag any that aren't 1024px, 768px, or 480px.

---

## Phase 2: Visual Screenshot Review

For each of the following pages, navigate using Chrome DevTools MCP and take screenshots at 1440px and 375px:

Pages: `index.html`, `services.html`, `contact.html`, `blog.html`, `assessment.html`, `real-estate.html`

For each page at each viewport, visually verify:
- **Dark background** — consistent deep navy/purple gradient, no white or light backgrounds appearing unexpectedly
- **Glass morphism** — cards have frosted/translucent appearance, not opaque
- **Colour accents** — accent colours (orange/pink/purple or whatever the design uses) appear consistent and intentional
- **Typography hierarchy** — display font for headings visually distinct from body text
- **Spacing** — sections don't feel cramped or excessively padded vs each other
- **Mobile (375px):** text readable, no overflow, CTAs are full-width

Process per page:
1. `mcp__chrome-devtools__list_pages` — check for existing tab or open new one
2. `mcp__chrome-devtools__navigate_page` to `http://localhost/[page]`
3. `mcp__chrome-devtools__resize_page` — width: 1440, height: 900
4. `mcp__chrome-devtools__take_screenshot`
5. `mcp__chrome-devtools__resize_page` — width: 375, height: 812
6. `mcp__chrome-devtools__take_screenshot`

---

## Phase 3: Cross-Page Consistency

Use `index.html` as the design reference. Compare each other page against it:

- **Navigation:** same structure, same styling, same font weight?
- **Section headings:** same size and weight approach on every page?
- **Footer:** same layout and content on all pages?
- **CTA buttons:** same background, border-radius, hover state everywhere?
- **Hero sections:** consistent padding/sizing approach?

Note any pages that feel visually inconsistent with the homepage.

---

## Output Format

```
## Design Review Report
**Date:** [today]

### 1. Hardcoded Colour Violations
[PASS — none found]
OR
[FAIL — N violations:
  - filename.html:42 — #1b263b (should use var(--primary-mid))
  - filename.css:87 — rgb(0,0,0)
]

### 2. Glass Morphism Coverage
[PASS — all card/panel selectors compliant]
OR
[FAIL:
  - .selector-name in file.css — missing backdrop-filter
  - .other-card in page.html — missing border rgba
]

### 3. Font Loading
[PASS — all 10 pages load Sora + body font via Google Fonts CDN]
OR
[FAIL — missing on: page-name.html (missing preconnect), ...]

### 4. CSS Variable Font-Family Usage
[PASS]
OR
[FAIL — raw font declarations found:
  - file.css:12 — font-family: 'Sora' (should use var(--font-display))
]

### 5. Transition Consistency
[PASS — all transitions use cubic-bezier]
OR
[FAIL — non-standard transitions:
  - file.css:55 — transition: all 0.3s ease
]

### 6. Breakpoint Consistency
[PASS — only 1024px/768px/480px used]
OR
[FAIL — non-standard breakpoints:
  - file.css:200 — @media (max-width: 600px)
]

### 7. Visual Screenshots
[Per-page notes at 1440px and 375px]

### 8. Cross-Page Consistency
[Notes on which pages deviate from index.html reference]

---

### Summary
**Compliance:** X/8 sections passing

**Priority fixes:**
1. [Most impactful issue]
2. [Second priority]
...
```
