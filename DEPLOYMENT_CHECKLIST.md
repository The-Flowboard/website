# AI Assessment Tool - Deployment Checklist

Complete this checklist to deploy the AI Opportunity Assessment tool to production.

---

## Pre-Deployment Checklist

### 1. Database Setup ✓

- [ ] Connect to your MySQL database server
- [ ] Create database (if not using existing):
  ```sql
  CREATE DATABASE jmc_website CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  ```
- [ ] Run schema creation script:
  ```bash
  mysql -u your_username -p jmc_website < database/assessment_schema.sql
  ```
- [ ] Run opportunities data script:
  ```bash
  mysql -u your_username -p jmc_website < database/opportunities_data.sql
  ```
- [ ] Verify tables created:
  ```sql
  SHOW TABLES;
  -- Should show: ai_opportunities, assessment_submissions, assessment_opportunity_scores
  ```
- [ ] Verify opportunities populated:
  ```sql
  SELECT COUNT(*) FROM ai_opportunities;
  -- Should return: 20
  ```

### 2. File Upload ✓

Upload the following files to your server:

#### New Files:
- [ ] `/js/assessment.js` → Upload to server
- [ ] `/css/assessment.css` → Upload to server
- [ ] `/php/process_assessment.php` → Upload to server
- [ ] `/assessment-results.php` → Upload to server (root directory)

#### Modified Files:
- [ ] `/assessment.html` → Upload to server (overwrites existing)

#### Configuration Files (for reference only):
- [ ] `/database/assessment_schema.sql` (keep local copy)
- [ ] `/database/opportunities_data.sql` (keep local copy)
- [ ] `/n8n/assessment_workflow_setup.md` (keep local copy)

### 3. File Permissions ✓

Ensure proper permissions on uploaded files:

```bash
# PHP files should be readable and executable
chmod 644 /path/to/php/process_assessment.php
chmod 644 /path/to/assessment-results.php

# JavaScript and CSS should be readable
chmod 644 /path/to/js/assessment.js
chmod 644 /path/to/css/assessment.css

# HTML should be readable
chmod 644 /path/to/assessment.html
```

### 4. Database Connection ✓

- [ ] Verify `php/db_config.php` has correct credentials:
  ```php
  $servername = "your-server";
  $username = "your-username";
  $password = "your-password";
  $dbname = "jmc_website"; // or your database name
  ```
- [ ] Test database connection:
  ```php
  // Run a test query
  $result = $conn->query("SELECT COUNT(*) FROM ai_opportunities");
  // Should return 20
  ```

---

## n8n Workflow Setup

### 5. Create n8n Workflow ✓

- [ ] Log into your n8n instance
- [ ] Create new workflow: "AI Assessment - Lead Notification"
- [ ] Add Webhook trigger node
  - HTTP Method: POST
  - Path: `assessment-submitted`
  - Response Mode: Immediately
- [ ] Add Email node (Gmail or SMTP)
  - Configure credentials
  - Use email template from `/n8n/assessment_workflow_setup.md`
  - Test with sample data
- [ ] Add Slack node
  - Configure OAuth token
  - Select target channel (e.g., `#leads`)
  - Use Slack template from setup guide
  - Test with sample data
- [ ] **Copy the webhook URL** (e.g., `https://your-n8n.com/webhook/assessment-submitted`)
- [ ] Activate the workflow (toggle switch in top-right)

### 6. Update PHP Webhook URL ✓

- [ ] Open `/php/process_assessment.php` on your server
- [ ] Find line 232:
  ```php
  sendWebhook('YOUR_N8N_WEBHOOK_URL', $webhook_data);
  ```
- [ ] Replace with your actual webhook URL:
  ```php
  sendWebhook('https://your-n8n-instance.com/webhook/assessment-submitted', $webhook_data);
  ```
- [ ] Save and upload the updated file

---

## Testing Phase

### 7. Unit Tests ✓

Test each component individually:

#### Database Queries:
- [ ] Query opportunities table:
  ```sql
  SELECT * FROM ai_opportunities LIMIT 5;
  ```
- [ ] Check all pain_question fields are valid (Q5-Q10):
  ```sql
  SELECT DISTINCT pain_question FROM ai_opportunities;
  ```

#### Frontend:
- [ ] Load `https://yourdomain.com/assessment.html`
- [ ] Verify CSS loads (check for styled progress bar)
- [ ] Verify JavaScript loads (check browser console for errors)
- [ ] Click through each section without submitting
- [ ] Test validation (try to proceed without answering questions)

#### Results Page:
- [ ] Test with mock submission ID:
  ```
  https://yourdomain.com/assessment-results.php?id=1
  ```
- [ ] Verify error message if ID doesn't exist
- [ ] Check for PHP errors in logs

### 8. End-to-End Test ✓

Complete a full assessment submission:

- [ ] Navigate to `https://yourdomain.com/assessment.html`
- [ ] Complete Section 1 (Business Context) - all 4 questions
- [ ] Complete Section 2 (Pain Points) - all 6 scale questions
- [ ] Complete Section 3 (Strategic Priorities) - all 3 questions
- [ ] Complete Section 4 (Readiness Assessment) - all 4 questions
- [ ] Fill out contact form with **your own email** for testing:
  - Name: Your name
  - Email: Your email
  - Phone: Your phone
  - Company: Test company name
- [ ] Click "Submit Assessment"
- [ ] Verify redirection to results page
- [ ] Verify results page displays:
  - Your company name
  - Readiness level badge
  - Top 5 opportunities ranked
  - Cost and ROI for each opportunity
  - 3-year financial projections
  - Personalized concern response

### 9. Integration Tests ✓

- [ ] Check email inbox (within 1-2 minutes)
  - [ ] Email received from correct sender
  - [ ] Subject line includes company name
  - [ ] HTML renders correctly (no broken styles)
  - [ ] All opportunity data displays
  - [ ] "View Full Report" button works
  - [ ] Footer links work
- [ ] Check Slack channel
  - [ ] Notification posted
  - [ ] All data fields populated
  - [ ] Report link works
- [ ] Check database:
  ```sql
  SELECT * FROM assessment_submissions ORDER BY submitted_at DESC LIMIT 1;
  ```
  - [ ] All fields populated correctly
  - [ ] Readiness score calculated (0-7)
  - [ ] Readiness level assigned (Crawl/Walk/Run)
  ```sql
  SELECT * FROM assessment_opportunity_scores WHERE submission_id = [your_id] ORDER BY rank;
  ```
  - [ ] 5 opportunities scored and ranked
  - [ ] Scores look reasonable (not all zeros)

### 10. Validation Tests ✓

Test error handling and edge cases:

- [ ] Try to access results page without ID:
  ```
  https://yourdomain.com/assessment-results.php
  ```
  → Should show error message

- [ ] Try to access results with invalid ID:
  ```
  https://yourdomain.com/assessment-results.php?id=99999
  ```
  → Should show "Assessment not found" error

- [ ] Try to submit assessment with missing questions:
  → Should show validation error

- [ ] Test with different company sizes:
  - [ ] 1-10 employees (should show lower costs/ROI)
  - [ ] 1,000+ employees (should show higher costs/ROI)

- [ ] Test with different readiness combinations:
  - [ ] All "not comfortable" answers → Should be "Crawl"
  - [ ] All "very comfortable" answers → Should be "Run"

### 11. Performance Tests ✓

- [ ] Page load time < 3 seconds
- [ ] Assessment submission < 2 seconds
- [ ] Results page load < 2 seconds
- [ ] No JavaScript errors in console
- [ ] No PHP warnings in error logs

### 12. Mobile Responsiveness ✓

Test on mobile devices or use browser dev tools:

- [ ] Assessment form displays correctly on mobile
- [ ] Scale questions (0-5) are easy to tap
- [ ] Progress bar visible
- [ ] Buttons properly sized
- [ ] Results page readable on mobile
- [ ] Email renders well on mobile clients

---

## Security Checks

### 13. Security Validation ✓

- [ ] SQL injection prevention:
  - All queries use prepared statements (check `process_assessment.php`)
  - No direct variable interpolation in queries

- [ ] XSS prevention:
  - Results page uses `htmlspecialchars()` for user input
  - No unescaped user data in HTML

- [ ] CSRF protection:
  - Consider adding CSRF tokens if assessment is behind auth

- [ ] Data validation:
  - All required fields validated server-side
  - Email format validated
  - Enum values checked against allowed options

- [ ] Webhook security:
  - Webhook URL uses HTTPS
  - Consider adding authentication header
  - IP whitelist if possible

- [ ] Database security:
  - Database user has minimal required permissions
  - No sensitive data stored in plain text
  - Regular backups configured

---

## Production Deployment

### 14. Go Live ✓

- [ ] Review all test results
- [ ] Fix any issues found during testing
- [ ] Clear any test data from database:
  ```sql
  DELETE FROM assessment_submissions WHERE email LIKE '%test%';
  DELETE FROM assessment_opportunity_scores WHERE submission_id NOT IN (SELECT id FROM assessment_submissions);
  ```
- [ ] Update Google Analytics (if tracking conversions)
- [ ] Update meta tags on assessment.html (SEO)
- [ ] Submit to Google Search Console
- [ ] Enable monitoring/alerts for errors

### 15. Marketing Launch ✓

- [ ] Announce on social media
- [ ] Add link to website navigation
- [ ] Add CTA on homepage
- [ ] Add to email signature
- [ ] Create LinkedIn post about the tool
- [ ] Consider paid ads driving to assessment

---

## Monitoring & Maintenance

### 16. Post-Launch Monitoring ✓

**Daily (first week)**:
- [ ] Check for new submissions in database
- [ ] Review error logs for PHP errors
- [ ] Monitor n8n workflow executions
- [ ] Check email deliverability

**Weekly**:
- [ ] Review submission conversion rate
- [ ] Analyze which opportunities score highest
- [ ] Check for any failed webhook deliveries
- [ ] Review Slack notifications working

**Monthly**:
- [ ] Database backup verification
- [ ] Review scoring algorithm effectiveness
- [ ] Update opportunities if needed
- [ ] Analyze assessment completion rate

### 17. Analytics to Track ✓

Set up tracking for:
- [ ] Assessment page visits
- [ ] Assessment starts (Section 1 completed)
- [ ] Assessment completions (Contact form submitted)
- [ ] Conversion rate (visits → completions)
- [ ] Time to complete assessment
- [ ] Most common readiness levels
- [ ] Most recommended opportunities
- [ ] Leads converted to customers

---

## Troubleshooting Guide

### Common Issues:

**Problem**: Assessment not submitting
- Check browser console for JavaScript errors
- Verify all questions are answered
- Check network tab for failed POST request
- Review PHP error logs

**Problem**: Results page shows "Not Found"
- Verify submission_id in URL
- Check database for submission record
- Review process_assessment.php return value

**Problem**: Email not received
- Check spam folder
- Verify n8n workflow is active
- Review n8n execution logs
- Test SMTP credentials manually

**Problem**: Slack notification not posting
- Verify Slack OAuth token
- Check bot permissions in channel
- Review n8n Slack node configuration
- Test with manual workflow execution

**Problem**: Wrong cost/ROI displayed
- Verify company size multipliers in PHP
- Check opportunities_data.sql for base costs
- Review calculation in calculateOpportunityScore()

**Problem**: Opportunities not ranked correctly
- Review scoring algorithm weights
- Check pain_question mapping to Q5-Q10
- Verify strategic_fit calculation
- Test with different answer combinations

---

## Rollback Plan

If critical issues occur:

1. **Quick Disable**:
   - Remove CSS/JS links from assessment.html
   - Replace with "Coming Soon" message

2. **Full Rollback**:
   - Restore previous version of assessment.html
   - Disable n8n workflow
   - Mark feature as "under maintenance"

3. **Data Preservation**:
   - DO NOT delete database tables
   - Export submissions for later analysis
   - Fix issues in development environment

---

## Success Metrics

After 30 days, evaluate:

- [ ] Assessment completions: Target 20+
- [ ] Email deliverability: Target 95%+
- [ ] Lead conversion: Target 10%+ (booked calls)
- [ ] User feedback: Collect via follow-up survey
- [ ] Technical performance: Zero critical errors

---

## Support Contacts

- **Technical Issues**: Your development team
- **n8n Support**: [community.n8n.io](https://community.n8n.io)
- **Email Deliverability**: Your email provider support
- **Database Issues**: Your hosting provider

---

**Deployment Date**: _________________
**Deployed By**: _________________
**Version**: 1.0
**Last Updated**: December 2024

---

## Post-Deployment Notes

Use this space to document any issues encountered or customizations made:

```
[Notes section - to be filled during deployment]
```
