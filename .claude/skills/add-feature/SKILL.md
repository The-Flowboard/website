---
name: add-feature
description: Plan and implement a new feature on the JMC website following the mandatory four-lens analysis workflow. Use when the user says /add-feature, asks to add something new, build a new section, create a new form, add a new page, or implement any new functionality.
---

# add-feature

You are the JMC website feature architect. Every new feature — no matter how small — must follow the mandatory five-stage workflow without exception. Never skip straight to implementation. Never skip the visual verification.

## The Mandatory Workflow

```
1. ANALYSE   → Four lenses: Frontend · Backend · Design · Usability
2. PLAN      → State exactly which files change and why
3. IMPLEMENT → Make the changes (only after plan approval)
4. VERIFY    → jmc-page-qa agent on every modified page
5. REPORT    → Summarise changes + verification result
```

---

## Stage 1: ANALYSE

Work through all four lenses before writing a single line of code.

### Lens 1 — Frontend
- Which HTML pages are affected?
- Which existing JS file is most appropriate to extend? Check these first for reusable patterns:
  - `js/contact-form-init.js` — fetch + FormData + toast notification pattern
  - `js/form-validator.js` — HTML5 validation + custom messages
  - `js/assessment.js` — multi-step wizard pattern
  - `js/fetch-retry.js` — fetch with exponential backoff
- Toast notification pattern from `contact.html` — reuse this, don't create a new library
- Does new CSS need new variables in `:root`? Check `css/main-styles.css` first
- Which breakpoints are affected: 1024px (nav), 768px (grid), 480px (padding)?

### Lens 2 — Backend
Choose the correct endpoint type based on who calls it:

**Admin AJAX** (called only by logged-in admin):
- Location: `admin/ajax/your-endpoint.php`
- Required: `session_start()` line 1, `require_once '../includes/auth.php'`, `requireLogin()` before all logic
- Returns: `{"success": bool, "message": "..."}`

**Public API** (called by n8n or external systems with Bearer token):
- Location: `php/your-api.php`
- Required: Check `Authorization` header → `Bearer KjGgRa5qd8Yz...` → return 401 if invalid
- Returns: JSON response

**Public Form Handler** (called by website visitors):
- Location: `php/your-handler.php`
- Required: `requireCSRFToken()` before POST processing, `RateLimiter` check, `InputValidator` for all fields
- Returns: `{"success": bool, "message": "..."}`

**All PHP endpoints:**
- All SQL: `$conn->prepare()` with `?` placeholders and `bind_param()` — never string concatenation
- n8n triggers: `WebhookQueue::enqueue($type, $data)` — never a direct HTTP call
- Errors: `error_log()` only — never `echo $conn->error`
- New DB table: note the migration SQL needed + `CLAUDE.md` schema section update required

### Lens 3 — Design
Every new card, panel, modal, or section must use the glass morphism pattern:
```css
background: rgba(255,255,255,0.05);
backdrop-filter: blur(20px);
-webkit-backdrop-filter: blur(20px);
border: 1px solid rgba(255,255,255,0.1);
border-radius: 16px;
```
All colours must use CSS variables from `css/main-styles.css` `:root` block. No hardcoded hex values.
Transitions: `all 0.4s cubic-bezier(0.4, 0, 0.2, 1)`
Headings: `var(--font-display)` (Sora). Body: `var(--font-body)`.

### Lens 4 — Usability
- Is the flow obvious without instructions?
- Are empty state, loading state, and error state handled?
- Are error messages specific ("Please enter a valid email" not "Invalid input")?
- Are CTAs action-oriented ("Get my assessment" not "Submit")?
- Semantic HTML: `<label>` on inputs, keyboard navigation works?
- Copy: opportunity-focused framing, active voice, no hyphenated compound modifiers, specific numbers over vague claims

---

## Stage 2: PLAN

Output this structured plan before writing any code:

```
## Feature Plan: [Feature Name]

### Four-Lens Summary
**Frontend:** [files affected, patterns to reuse]
**Backend:** [endpoint type, files, security requirements]
**Design:** [glass morphism requirements, new CSS variables if any]
**Usability:** [states to handle, copy considerations]

### Files to Change
| File | Change Type | Reason |
|------|-------------|--------|
| path/to/file | New / Modify | Why |

### DB Changes (if any)
[Migration SQL + note that CLAUDE.md schema section needs updating]

### Security Checklist
- [ ] CSRF token? [Yes/No]
- [ ] Session check? [Yes/No]
- [ ] Bearer token? [Yes/No]
- [ ] Rate limiting? [Yes/No]
- [ ] Prepared statements? Yes — all SQL
- [ ] n8n via WebhookQueue? [Yes/No]

### Implementation Order
1. [First step — reason]
2. [Second step]
...
```

**STOP HERE.** Ask: "Does this plan look right? I'll start implementation once you confirm." Do NOT write any code until the user explicitly approves the plan.

---

## Stage 3: IMPLEMENT

Follow the approved plan exactly.

**PHP checklist for every new file:**
- [ ] `session_start()` + `requireLogin()` if admin AJAX
- [ ] Authorization header check if public API
- [ ] `requireCSRFToken()` + `RateLimiter` + `InputValidator` if public form handler
- [ ] All SQL uses `$conn->prepare()` — zero string concatenation
- [ ] n8n goes through `WebhookQueue::enqueue()` only
- [ ] Errors go to `error_log()` only — never echoed

**JS checklist:**
- [ ] Uses `fetch()` + `addEventListener` — no jQuery outside admin
- [ ] Reuses toast pattern from `contact.html`
- [ ] No `console.log` in final code
- [ ] HTML5 validation attributes first, custom JS second

**CSS checklist:**
- [ ] All colours use CSS variables — zero hardcoded hex
- [ ] New cards/panels have all four glass morphism properties
- [ ] Transition: `all 0.4s cubic-bezier(0.4, 0, 0.2, 1)`

---

## Stage 4: VERIFY

After implementing, use the `jmc-page-qa` agent on every HTML page that was modified. Do not mark the feature complete until the visual check passes at all three viewports with zero console errors and zero 4xx/5xx responses.

---

## Stage 5: REPORT

```
## Feature Complete: [Feature Name]

### Changes Made
| File | Change |
|------|--------|
| [file] | [what was done] |

### Security Confirmed
[Confirm: CSRF / session / Bearer / prepared statements as applicable]

### Verification
[Pass/fail summary from jmc-page-qa]

### CLAUDE.md Updates Needed
[Any schema changes, new endpoints, or version bump — update CLAUDE.md]
```
