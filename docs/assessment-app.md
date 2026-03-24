# AI Readiness Assessment

## Overview

17-question, 5-step wizard that scores a business's AI readiness and recommends their top 5 personalised AI opportunities. Contact gate appears before results are shown.

Files: `assessment.html` (1050 lines) · `assessment-results.php` (550 lines) · `js/assessment.js` (700 lines) · `css/assessment.css` (450 lines) · `php/process_assessment.php`

## Question Structure

| Step | Questions | Topic |
|------|-----------|-------|
| 1 | Q1–Q4 | Business Context (company size, industry, revenue, tech comfort) |
| 2 | Q5–Q10 | Pain Point scores (0–5 each) |
| 3 | Q11–Q13 | Strategic (primary goal, value priority, budget) |
| 4 | Q14–Q17 | Readiness (timeline, executive sponsor, data organisation, biggest concern) |
| 5 | Contact gate | Name, email, phone before results shown |

## Pain Point Questions (Q5–Q10)

| Question | DB Column |
|----------|-----------|
| Q5 | `pain_customer_support` |
| Q6 | `pain_content_creation` |
| Q7 | `pain_sales_lead_mgmt` |
| Q8 | `pain_document_processing` |
| Q9 | `pain_knowledge_mgmt` |
| Q10 | `pain_repetitive_data` |

## Readiness Score (0–7)

```
Tech comfort Q4:        −1 to +2
Executive sponsor Q15:  −2 to +3
Data organisation Q16:  −2 to +2
Total clamped 0–7
```

Levels: 0–1 = Crawl · 2–4 = Walk · 5–7 = Run

## Opportunity Scoring Formula

```
final = (Pain × 0.4) + (StrategicFit × 0.3) + (ReadinessBonus × 0.2) − (ComplexityPenalty × 0.1)
```

- **Strategic fit:** +3 if primary_goal in opportunity's `primary_goals` JSON; +2 if value_priority matches
- **Complexity penalty:** +5 if budget doesn't fit `budget_tier`; +2 if timeline doesn't fit `timeline_weeks`
- Minimum score to qualify for top 5: ≥ 2.0

## Company Size Multipliers

| Size | Cost × | ROI × |
|------|--------|-------|
| 1–10 | 0.7 | 0.6 |
| 11–50 | 1.0 | 1.0 (baseline) |
| 51–200 | 1.3 | 1.5 |
| 201–1000 | 1.8 | 2.5 |
| 1000+ | 2.5 | 4.0 |

## Processing Flow (`php/process_assessment.php`)

```
POST submission
  → INSERT assessment_submissions
  → Calculate readiness score
  → Fetch all 20 opportunities from ai_opportunities
  → Score each with weighted formula
  → Personalise cost/ROI by company size multiplier
  → SELECT top 5 (score ≥ 2.0)
  → INSERT 20 rows into assessment_opportunity_scores
  → WebhookQueue::enqueue('assessment', payload)
  → Return {success, id}
  → Redirect to assessment-results.php?id=X
```

## Results Page

Displays: readiness level badge · top 5 AI opportunities with personalised cost/ROI ranges · 3-year ROI forecast · concern-specific messaging. Print-to-PDF supported.

## n8n Webhook Payload

```json
{
  "submission_id": 123,
  "name": "...",
  "email": "...",
  "phone": "...",
  "company": "...",
  "industry": "...",
  "company_size": "11-50",
  "readiness_level": "Walk",
  "top_opportunities": [{"name": "...", "score": 4.2, "cost": "$12k–$18k", "roi": "$45k/yr"}],
  "submitted_at": "...",
  "resend": true  // only when triggered by admin resend
}
```

Webhook URL: `https://n8n.joshimc.com/webhook/2fa44a47-4368-4ec5-81c5-d30a2de72e92`

## Database Tables

**`assessment_submissions`** — Full response record including all Q1–Q17 answers, calculated `readiness_score`, `readiness_level`, and `recommended_opportunities` (JSON top-5 IDs).

**`assessment_opportunity_scores`** — One row per opportunity per submission (20 rows). Columns: `submission_id` · `opportunity_id` · `pain_score` · `strategic_fit_score` · `readiness_bonus` · `complexity_penalty` · `final_score` · `rank` (1–5 for top 5, NULL otherwise) · `personalized_cost` · `personalized_roi_annual`

**`ai_opportunities`** — 20 pre-seeded templates. Key columns: `opportunity_id` (slug) · `name` · `category` · `pain_question` · `primary_goals` (JSON) · `value_priorities` (JSON) · `cost_base/range` · `roi_annual_base/range` · `timeline_weeks` · `complexity` (1–10) · `budget_tier` (1–5) · `industries_best_fit` (JSON)

## Admin Actions

- View assessment details → links to `assessment-results.php?id=X`
- Resend results → `admin/ajax/resend_assessment.php` → re-fetches submission + top 5 → enqueues n8n webhook with `resend: true`
