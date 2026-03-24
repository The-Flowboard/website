# Industry Pages

Three live industry-specific landing pages. All follow the same HTML structure and share `/css/main-styles.css`.

| Page | File | URL |
|------|------|-----|
| Real Estate | `real-estate.html` | `/real-estate` |
| Professional Services | `professional-services.html` | `/professional-services` |
| Financial Services | `financial-services.html` | `/financial-services` |

---

## Shared Page Structure

Every industry page is built with the same section order:

1. **Hero** — industry badge pill + H1 + subtitle + two CTA buttons (Assessment + Contact)
2. **Pain points** — 4-card grid with large gradient number, heading, description
3. **Solutions** — 5-card staggered grid (3 top row + 2 bottom row, centred)
4. **Before / After** — 2-column comparison (red-tinted Before, green-tinted After with bullet lists)
5. **CTA banner** — heading + subtext + Assessment button

### CSS classes used across all three

```
.industry-badge       — purple pill label at top of hero
.pain-grid            — auto-fit grid, minmax(280px, 1fr)
.pain-number          — large gradient number (purple→cyan)
.solutions-grid       — 6-column grid, 5 cards in staggered layout
.before-after-grid    — 2-column grid
.before-card          — red-tinted glass card
.after-card           — green-tinted glass card
```

Note: these pages use `/css/main-styles.css` (not the inline `:root` variables from the assessment CSS). Font body is **Nunito Sans** (not Outfit) + Sora for headings — loaded from Google Fonts CDN.

---

## Real Estate (`real-estate.html`)

**H1:** AI Solutions Built for Real Estate and Property Management

**Hero subtitle:** From lease administration to tenant communications to market analysis, real estate runs on high-volume, detail-heavy processes. We build AI and automation solutions that handle the repetitive work so your team can focus on deals, relationships, and revenue.

### Pain Points
1. **Manual Lease and Document Management** — Tracking hundreds of leases in spreadsheets; renewals slip, escalation clauses missed
2. **Tenant Communication Overload** — Maintenance requests, payment reminders, and move coordination flood teams daily
3. **Disconnected Systems and Data Silos** — Property management, accounting, CRM, and listings run as separate islands
4. **Competitive Pressure on Margins** — Rising operating costs require doing more with the same headcount

### Solutions
1. **AI Business Solutions** — Lease abstraction, AI tenant screening, intelligent maintenance triage
2. **Unified CRM and Property Management** — Integration of PM platform, accounting, CRM, and comms tools
3. **Custom Applications** — Tenant portals, vacancy tracking, lease renewal tools, listing generation
4. **Agentic AI** — Agents handling full maintenance request lifecycle and lease expiry monitoring
5. **Predictive Analytics** — Predictive maintenance, market analysis, tenant retention models

### Before → After
| Before | After |
|--------|-------|
| Manually reviewing 200+ leases in spreadsheets | AI extracts terms, flags renewals 90 days out |
| Maintenance requests via email chains, no tracking | AI triages, creates work orders, schedules vendors, follows up |
| Market comparables assembled manually (2–3 hrs/listing) | AI generates comparables, pricing, and listing descriptions in under 10 min |
| Monthly owner reports built by hand from 3 systems | Automated reporting pulls from all systems on schedule |
| Vacancy pricing by gut feel | Predictive models recommend pricing and timing from market data |

**CTA heading:** Ready to See What AI Can Do for Your Real Estate Operations?

---

## Professional Services (`professional-services.html`)

**H1:** AI Solutions for Law Firms, Accounting Practices, and Consulting Companies

**Hero subtitle:** Professional services firms sell expertise, not hours. Yet most firms have their highest-paid people spending significant time on document review, data entry, and administrative coordination.

### Pain Points
1. **High-Cost Talent on Low-Value Work** — Associates and analysts spend 30–50% of their time on tasks AI can do faster and more accurately
2. **Knowledge Trapped in People's Heads** — Precedents and institutional knowledge scattered across shared drives and email; leaves with staff
3. **Client Intake and Onboarding Bottlenecks** — Multiple manual handoffs from first contact to active engagement: intake, conflict checks, engagement letters
4. **Compliance and Regulatory Pressure** — Increasing documentation requirements are error-prone and time-consuming when done manually

### Solutions
1. **AI Business Solutions** — Document review, automated report generation, research tools for precedents and regulations
2. **Unified CRM and Practice Management** — Practice management, accounting, CRM, and comms in one connected system
3. **Custom Applications** — Client portals, internal knowledge management, billing/time-tracking tools
4. **Agentic AI** — Agents managing full client intake; agents monitoring regulatory changes for active matters
5. **Predictive Analytics** — Client profitability analysis, workload forecasting, pipeline analytics

### Before → After
| Before | After |
|--------|-------|
| Junior associates spending 40+ hrs on due diligence contract review | AI flags key clauses, risks, and discrepancies across 500+ docs in hours |
| Client intake requiring 3–5 manual handoffs to generate engagement letter | Automated intake captures data, runs conflicts, generates letter in one submission |
| Finding a precedent takes hours searching shared drives | AI indexes document history, returns results via natural language search in seconds |
| Accountants manually reconciling 2,000+ transactions at tax season | AI matches and categorises transactions, flags exceptions only |
| Partners tracking BD in spreadsheets with no pipeline visibility | CRM with deal stages, conversion rates, and revenue forecasts in real time |

**CTA heading:** Your Team's Expertise Is Too Valuable for Data Entry. Let AI Handle the Rest.

---

## Financial Services (`financial-services.html`)

**H1:** AI Solutions for Financial Services Firms

**Hero subtitle:** Banks, insurance companies, wealth management firms, and financial advisors operate under intense regulatory scrutiny while processing massive volumes of transactions and client data.

### Pain Points
1. **Regulatory Compliance Is Getting More Complex and Expensive** — KYC, AML, SOX, PIPEDA demand extensive manual documentation and monitoring; consumes resources that should go to clients
2. **Transaction Volume Outpacing Headcount** — Processing bottlenecks cause delayed approvals, missed SLAs, and frustrated clients
3. **Fraud and Risk Detection Lagging Behind Threats** — Rule-based systems miss emerging patterns; losses occur before rules are updated
4. **Client Expectations for Speed and Personalization** — Clients expect instant responses and seamless digital experiences; manual processes lose ground to automated competitors

### Solutions
1. **AI Business Solutions** — Document processing for loan applications and insurance claims, intelligent compliance monitoring
2. **Unified CRM and Financial Systems** — CRM, portfolio management, core banking, accounting, and comms in a unified data layer
3. **Custom Applications** — Client portals with real-time portfolio visibility, risk/compliance dashboards, custom underwriting tools
4. **Agentic AI** — Agents processing loan/insurance applications end-to-end; agents monitoring portfolios for rebalancing triggers
5. **Predictive Analytics** — Credit risk models, adaptive fraud detection, client churn prediction

### Before → After
| Before | After |
|--------|-------|
| Compliance officers manually reviewing thousands of transactions for AML/KYC | AI monitors transactions continuously, flags violations in real time with audit trails |
| Loan applications taking 5–10 business days (manual document verification) | AI validates docs and pre-qualifies applications in hours |
| Fraud detection relying on static rules that miss emerging patterns | Adaptive AI detects anomalies as they evolve |
| Client onboarding requiring multiple in-person meetings and paper forms | Digital onboarding with AI document verification, risk assessment, automated account setup |
| Portfolio reporting assembled manually from spreadsheets | Automated reporting delivers portfolio insights on demand |

**CTA heading:** Compliance. Speed. Intelligence. AI Delivers All Three.

---

## Adding a New Industry Page

When building a fourth industry page:
1. Copy an existing industry HTML file as the starting point
2. Keep all class names and section order identical
3. Link `/css/main-styles.css` — do not add a new stylesheet
4. Add GA4 tag `G-5HH2RHZLZ7`
5. Add to sitemap.xml
6. Add link in footer or nav
7. Chrome DevTools visual check at 1440px / 768px / 375px
8. Update CLAUDE.md file structure section
