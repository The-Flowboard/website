-- ============================================================
-- JMC Website — Local Development Database Init
-- Auto-generated from database/ schema files
-- Order: blog_posts → core tables → FK-dependent tables → seed data
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '-05:00';

-- ============================================================
-- blog_posts (must exist before blog_tags FK)
-- ============================================================
CREATE TABLE IF NOT EXISTS blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(255) NOT NULL UNIQUE,
    title VARCHAR(500) NOT NULL,
    excerpt TEXT,
    content LONGTEXT,
    author VARCHAR(255) DEFAULT 'JMC Team',
    featured_image VARCHAR(500),
    meta_description TEXT,
    meta_keywords VARCHAR(500),
    status ENUM('draft', 'published') DEFAULT 'draft',
    views INT DEFAULT 0,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_slug (slug),
    INDEX idx_published_at (published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- contact_submissions (from database/contact_form_schema.sql)
-- ============================================================
CREATE TABLE IF NOT EXISTS `contact_submissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `first_name` VARCHAR(255) NOT NULL,
    `last_name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(50) NOT NULL,
    `company` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `referral_source` VARCHAR(100) DEFAULT NULL,
    `consent_marketing` TINYINT(1) DEFAULT 0,
    `consent_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` VARCHAR(50) DEFAULT 'new',
    `contacted_at` TIMESTAMP NULL DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`),
    INDEX `idx_email` (`email`),
    INDEX `idx_referral_source` (`referral_source`),
    INDEX `idx_submitted_at` (`submitted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ai_opportunities (from database/assessment_schema.sql)
-- ============================================================
CREATE TABLE IF NOT EXISTS ai_opportunities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opportunity_id VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    category ENUM('Customer Service', 'Content & Marketing', 'Sales & Lead Management', 'Operations & Automation', 'Strategic') NOT NULL,
    pain_question ENUM('Q5', 'Q6', 'Q7', 'Q8', 'Q9', 'Q10') NOT NULL,
    description_short TEXT NOT NULL,
    description_detailed TEXT NOT NULL,
    business_value_1 TEXT,
    business_value_2 TEXT,
    business_value_3 TEXT,
    business_value_4 TEXT,
    primary_goals JSON NOT NULL,
    value_priorities JSON NOT NULL,
    cost_base INT NOT NULL,
    cost_range_display VARCHAR(50) NOT NULL,
    timeline_weeks INT NOT NULL,
    timeline_display VARCHAR(50) NOT NULL,
    roi_annual_base INT NOT NULL,
    roi_range_display VARCHAR(50) NOT NULL,
    payback_months INT NOT NULL,
    complexity ENUM('Simple', 'Moderate', 'Complex') NOT NULL,
    budget_tier INT NOT NULL,
    deliverable_1 TEXT,
    deliverable_2 TEXT,
    deliverable_3 TEXT,
    deliverable_4 TEXT,
    deliverable_5 TEXT,
    maintenance_annual INT NOT NULL,
    industry_fit JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_pain_question (pain_question),
    INDEX idx_budget_tier (budget_tier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- assessment_submissions (from database/assessment_schema.sql)
-- ============================================================
CREATE TABLE IF NOT EXISTS assessment_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    company_name VARCHAR(255),
    company_size ENUM('1-10 employees', '11-50 employees', '51-200 employees', '201-1,000 employees', '1,000+ employees') NOT NULL,
    industry VARCHAR(100) NOT NULL,
    annual_revenue ENUM('Under $500K', '$500K - $2M', '$2M - $10M', '$10M - $50M', '$50M+') NOT NULL,
    tech_comfort ENUM('Very comfortable', 'Moderately comfortable', 'Somewhat comfortable', 'Not very comfortable') NOT NULL,
    pain_customer_support INT NOT NULL,
    pain_content_creation INT NOT NULL,
    pain_sales_lead_mgmt INT NOT NULL,
    pain_document_processing INT NOT NULL,
    pain_knowledge_mgmt INT NOT NULL,
    pain_repetitive_data INT NOT NULL,
    primary_goal VARCHAR(255) NOT NULL,
    value_priority VARCHAR(255) NOT NULL,
    budget_range ENUM('Under $10K', '$10K - $25K', '$25K - $50K', '$50K - $100K', '$100K+', 'Not sure yet / Depends on ROI') NOT NULL,
    timeline_expectation ENUM('1-2 months', '3-6 months', '6-12 months', '12+ months', 'Flexible / Depends on the opportunity') NOT NULL,
    executive_sponsor ENUM('Yes - C-level executive committed', 'Yes - VP/Director level committed', 'Maybe - Need to build the business case first', 'No - Would need to convince leadership') NOT NULL,
    data_organization ENUM('Well-organized in centralized systems', 'Somewhat organized but spread across multiple tools', 'Not very organized', 'Mostly on paper or not digitized') NOT NULL,
    biggest_concern VARCHAR(255) NOT NULL,
    readiness_score DECIMAL(5,2),
    readiness_level ENUM('Crawl', 'Walk', 'Run'),
    recommended_opportunities JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    results_viewed_at TIMESTAMP NULL,
    email_sent_at TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_company_size (company_size),
    INDEX idx_industry (industry),
    INDEX idx_submitted_at (submitted_at),
    INDEX idx_readiness_level (readiness_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- assessment_opportunity_scores (FK → assessment_submissions)
-- ============================================================
CREATE TABLE IF NOT EXISTS assessment_opportunity_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    submission_id INT NOT NULL,
    opportunity_id VARCHAR(100) NOT NULL,
    pain_score DECIMAL(5,2) NOT NULL,
    strategic_fit_score DECIMAL(5,2) NOT NULL,
    readiness_bonus DECIMAL(5,2) NOT NULL,
    complexity_penalty DECIMAL(5,2) NOT NULL,
    final_score DECIMAL(5,2) NOT NULL,
    `rank` INT,
    personalized_cost INT,
    personalized_roi_annual INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (submission_id) REFERENCES assessment_submissions(id) ON DELETE CASCADE,
    INDEX idx_submission (submission_id),
    INDEX idx_opportunity (opportunity_id),
    INDEX idx_final_score (final_score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- webhook_queue (from database/webhook_queue_schema.sql)
-- ============================================================
CREATE TABLE IF NOT EXISTS webhook_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event VARCHAR(100) NOT NULL,
    payload JSON NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    retry_count INT DEFAULT 0,
    max_retries INT DEFAULT 5,
    next_retry_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    error_message TEXT NULL,
    INDEX idx_status (status),
    INDEX idx_next_retry (next_retry_at),
    INDEX idx_event (event),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- blog_tags + blog_post_tags (from database/tags_schema.sql)
-- ============================================================
CREATE TABLE IF NOT EXISTS blog_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS blog_post_tags (
    post_id INT NOT NULL,
    tag_id  INT NOT NULL,
    PRIMARY KEY (post_id, tag_id),
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id)  REFERENCES blog_tags(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Seed: AI Opportunities (from database/opportunities_data.sql)
-- ============================================================
TRUNCATE TABLE ai_opportunities;

INSERT INTO ai_opportunities (opportunity_id, name, category, pain_question, description_short, description_detailed, business_value_1, business_value_2, business_value_3, business_value_4, primary_goals, value_priorities, cost_base, cost_range_display, timeline_weeks, timeline_display, roi_annual_base, roi_range_display, payback_months, complexity, budget_tier, deliverable_1, deliverable_2, deliverable_3, deliverable_4, deliverable_5, maintenance_annual, industry_fit) VALUES
('opp_customer_support_chatbot', 'AI-Powered Customer Support Assistant', 'Customer Service', 'Q5', 'Automated chatbot answers common customer questions 24/7, reducing support workload', 'Deploy an AI chatbot that handles 60-70% of customer inquiries automatically by searching your knowledge base and providing instant, accurate answers. Escalates complex issues to human agents seamlessly.', '40-60% reduction in support ticket volume', '24/7 customer support without hiring night shifts', '2-5 minute average response time (vs 2-4 hours)', 'Frees up support team for complex issues', '["Improve customer satisfaction / retention", "Reduce operational costs", "Scale operations without adding headcount"]', '["Freeing up employee time for higher-value work", "Faster response times to customers"]', 25000, '$15K-$45K', 10, '8-12 weeks', 45000, '$30K-$120K/year', 7, 'Moderate', 2, 'Custom-trained AI chatbot', 'Integration with your knowledge base', 'Website and email channel deployment', 'Escalation workflow to human agents', 'Admin dashboard for monitoring', 5000, '["All industries", "Especially: SaaS, E-commerce, Professional Services"]'),
('opp_internal_knowledge_base', 'Internal Knowledge Base (Employee Self-Service)', 'Customer Service', 'Q9', 'AI-powered search helps employees find information instantly across all company documents', 'Create an intelligent knowledge base that employees can query in plain English to find policies, procedures, and past work examples. Reduces time wasted searching and asking colleagues for information.', 'Employees find information in seconds vs hours', 'Reduces interruptions to senior team members', 'Preserves institutional knowledge', 'Onboard new employees faster', '["Improve team productivity", "Freeing up employee time for higher-value work"]', '["Freeing up employee time for higher-value work"]', 30000, '$20K-$50K', 12, '10-14 weeks', 40000, '$25K-$80K/year', 9, 'Moderate', 3, 'Internal knowledge base with AI search', 'Document indexing and categorization', 'Slack/Teams integration', 'Employee training', 'Analytics dashboard', 4000, '["All industries", "Especially: Professional Services, Healthcare"]'),
('opp_email_response_automation', 'Email Response Automation', 'Customer Service', 'Q5', 'AI drafts responses to common customer emails, reducing response time', 'Automatically generates draft responses to incoming customer emails based on email content and your knowledge base. Support team reviews and sends with one click.', '70-80% faster response times', 'Consistent, high-quality responses', 'Support team handles 3x more volume', 'Reduced training time for new support staff', '["Faster response times to customers", "Improve team productivity"]', '["Faster response times to customers", "Freeing up employee time for higher-value work"]', 18000, '$12K-$30K', 8, '6-10 weeks', 30000, '$20K-$60K/year', 7, 'Simple', 2, 'Email classification and routing', 'AI-powered draft generation', 'Integration with your email system', 'Quality control dashboard', 'Performance analytics', 3000, '["E-commerce", "SaaS", "Professional Services"]'),
('opp_multichannel_support_bot', 'Multi-Channel Support Bot (Website + Slack + Email)', 'Customer Service', 'Q5', 'Unified AI assistant across all customer communication channels', 'Single AI system that handles customer questions across website chat, Slack community, email, and internal help desk with consistent responses and full conversation history.', 'Omnichannel customer experience', 'Single system to train and maintain', 'Conversation history across channels', 'Reduced tool complexity', '["Improve customer satisfaction / retention", "Scale operations without adding headcount"]', '["Faster response times to customers"]', 45000, '$30K-$70K', 14, '12-16 weeks', 80000, '$50K-$150K/year', 7, 'Complex', 3, 'Multi-channel AI bot (web, Slack, email)', 'Unified conversation tracking', 'Advanced escalation workflows', 'Team collaboration features', 'Comprehensive analytics', 8000, '["SaaS", "Technology companies with online communities"]'),
('opp_blog_content_generation', 'Automated Blog & Content Generation', 'Content & Marketing', 'Q6', 'AI generates blog posts, articles, and social content from topics or outlines', 'Transform keywords or outlines into full blog posts, social media content, and newsletters. AI matches your brand voice and includes SEO optimization. Humans review and publish.', '10-15 blog posts per month vs 2-3', 'Consistent publishing schedule', 'SEO-optimized content', 'Frees marketing team for strategy', '["Increase revenue / sales", "Improve team productivity"]', '["Freeing up employee time for higher-value work"]', 20000, '$12K-$35K', 8, '6-10 weeks', 50000, '$30K-$100K/year', 5, 'Simple', 2, 'AI content generation system', 'Brand voice training', 'SEO optimization integration', 'Editorial workflow', 'Content calendar integration', 2000, '["Marketing/Agency", "Professional Services", "E-commerce"]'),
('opp_personalized_email_campaigns', 'Personalized Email Campaign Generator', 'Content & Marketing', 'Q6', 'AI creates personalized email sequences based on customer data and behavior', 'Generate customized email campaigns for different customer segments. AI personalizes subject lines, content, and CTAs based on customer data, purchase history, and engagement patterns.', '3-5x higher open rates', '2-4x higher click-through rates', 'Automated A/B testing', 'Scales to thousands of segments', '["Increase revenue / sales", "Improve customer satisfaction / retention"]', '["Freeing up employee time for higher-value work"]', 22000, '$15K-$40K', 10, '8-12 weeks', 60000, '$35K-$120K/year', 4, 'Moderate', 2, 'Email personalization engine', 'Segment-based automation', 'A/B testing framework', 'Integration with email platform', 'Performance analytics', 3000, '["E-commerce", "SaaS", "Professional Services"]'),
('opp_social_media_automation', 'Social Media Content Automation', 'Content & Marketing', 'Q6', 'AI generates social media posts from blog content, news, and company updates', 'Automatically create platform-optimized social posts from your blog content, industry news, or company announcements. Includes image suggestions, hashtags, and optimal posting times.', 'Save 10-15 hours/week on social media', 'Consistent posting schedule', 'Platform-optimized content', 'Increased engagement rates', '["Improve team productivity", "Increase revenue / sales"]', '["Freeing up employee time for higher-value work"]', 15000, '$10K-$25K', 6, '4-8 weeks', 35000, '$20K-$70K/year', 5, 'Simple', 1, 'Social media content generator', 'Multi-platform optimization', 'Scheduling integration', 'Brand voice consistency', 'Performance tracking', 1500, '["All industries"]'),
('opp_proposal_report_generator', 'Proposal & Report Generator', 'Content & Marketing', 'Q6', 'AI generates customized proposals and reports from templates and data', 'Create professional proposals, client reports, and presentations in minutes using AI. System pulls data from CRM, generates insights, and formats everything in your brand template.', '10x faster proposal creation', 'Consistent quality and branding', 'Higher win rates from personalization', 'Sales team focuses on relationships, not paperwork', '["Increase revenue / sales", "Improve team productivity"]', '["Freeing up employee time for higher-value work", "Higher quality / consistency in deliverables"]', 28000, '$18K-$50K', 10, '8-12 weeks', 70000, '$40K-$140K/year', 5, 'Moderate', 2, 'Proposal generation system', 'CRM data integration', 'Template customization', 'Automated data visualization', 'Version control and approval workflow', 4000, '["Professional Services", "Consulting", "B2B Sales"]'),
('opp_product_description_generator', 'Product Description Generator (E-commerce)', 'Content & Marketing', 'Q6', 'AI writes product descriptions, titles, and metadata at scale', 'Generate SEO-optimized product descriptions, titles, and metadata for your entire catalog. AI adapts tone for different product categories and target audiences.', 'Write 100s of descriptions per day', 'Consistent quality and SEO', 'Multi-language support', 'A/B test descriptions automatically', '["Scale operations without adding headcount", "Enter new markets / launch new products"]', '["Freeing up employee time for higher-value work"]', 18000, '$12K-$30K', 8, '6-10 weeks', 45000, '$25K-$90K/year', 5, 'Simple', 2, 'Product description generation', 'SEO optimization engine', 'Multi-language support', 'Bulk processing capabilities', 'Quality control dashboard', 2500, '["E-commerce", "Retail", "Distributors"]'),
('opp_lead_qualification_scoring', 'AI Lead Qualification & Scoring', 'Sales & Lead Management', 'Q7', 'AI analyzes leads and scores them based on likelihood to convert', 'Automatically scores and qualifies inbound leads using demographic data, behavioral signals, and historical patterns. Prioritizes sales team focus on highest-potential opportunities.', 'Sales team focuses on best-fit leads', '30-50% increase in conversion rates', 'Faster response to high-value leads', 'Data-driven lead routing', '["Increase revenue / sales", "Improve team productivity"]', '["Freeing up employee time for higher-value work", "Better data and insights for decisions"]', 25000, '$15K-$45K', 10, '8-12 weeks', 80000, '$45K-$150K/year', 4, 'Moderate', 2, 'Lead scoring model', 'CRM integration', 'Automated lead routing', 'Score explanation dashboard', 'Continuous model improvement', 4000, '["B2B Sales", "SaaS", "Professional Services"]'),
('opp_automated_lead_enrichment', 'Automated Lead Enrichment', 'Sales & Lead Management', 'Q7', 'AI automatically enriches lead records with company and contact data', 'Automatically research and enrich lead records with company size, revenue, tech stack, recent news, and decision-maker contacts. Eliminates manual research time.', 'Save 2-3 hours per lead on research', '90%+ data accuracy', 'Real-time enrichment', 'Better targeting and personalization', '["Increase revenue / sales", "Better data and insights for decisions"]', '["Freeing up employee time for higher-value work"]', 20000, '$12K-$35K', 8, '6-10 weeks', 50000, '$30K-$100K/year', 5, 'Moderate', 2, 'Lead enrichment engine', 'CRM integration', 'Data quality monitoring', 'Automated updates', 'Compliance and privacy controls', 3000, '["B2B Sales", "SaaS"]'),
('opp_sales_followup_automation', 'Sales Follow-Up Automation', 'Sales & Lead Management', 'Q7', 'AI manages follow-up sequences and detects buying signals', 'Automatically sends personalized follow-up emails based on prospect behavior, detects buying signals, and alerts sales team when leads are hot. Never miss a follow-up.', 'Zero leads fall through cracks', '3-5x more touches per lead', 'Respond to buying signals in minutes', 'Automated nurture for long sales cycles', '["Increase revenue / sales", "Improve team productivity"]', '["Freeing up employee time for higher-value work"]', 22000, '$15K-$40K', 8, '6-10 weeks', 65000, '$35K-$130K/year', 4, 'Simple', 2, 'Follow-up sequence automation', 'Buying signal detection', 'CRM and email integration', 'Performance analytics', 'A/B testing framework', 3500, '["B2B Sales", "High-ticket services"]'),
('opp_ai_crm_assistant', 'AI-Powered CRM Assistant (Data Entry + Insights)', 'Sales & Lead Management', 'Q7', 'AI automatically logs activities and surfaces insights from your CRM', 'Automatically capture emails, calls, and meetings in your CRM. AI generates deal summaries, next-step recommendations, and alerts for at-risk deals.', 'Eliminate manual CRM data entry', '100% activity tracking', 'Proactive deal alerts', 'Coaching insights for sales managers', '["Increase revenue / sales", "Better data and insights for decisions", "Improve team productivity"]', '["Freeing up employee time for higher-value work", "Better data and insights for decisions"]', 35000, '$20K-$60K', 12, '10-14 weeks', 90000, '$50K-$180K/year', 5, 'Moderate', 3, 'CRM data capture automation', 'AI deal insights and forecasting', 'Email and calendar integration', 'Pipeline health monitoring', 'Manager coaching dashboard', 5000, '["B2B Sales", "Enterprise sales teams"]'),
('opp_invoice_document_processing', 'Invoice & Document Processing Automation', 'Operations & Automation', 'Q8', 'AI extracts data from invoices, receipts, and documents automatically', 'Automatically process invoices, receipts, purchase orders, and contracts. AI extracts key data, validates against rules, routes for approval, and enters into your accounting system.', '90-95% reduction in manual data entry', 'Same-day processing vs 3-5 days', 'Near-zero errors', 'Audit trail and compliance', '["Reduce operational costs", "Improve team productivity"]', '["Freeing up employee time for higher-value work"]', 25000, '$15K-$45K', 10, '8-12 weeks', 55000, '$30K-$110K/year', 5, 'Moderate', 2, 'Document processing AI', 'Accounting system integration', 'Approval workflow automation', 'Exception handling', 'Audit and compliance reporting', 4000, '["All industries with high document volume"]'),
('opp_contract_review_extraction', 'Contract Review & Data Extraction', 'Operations & Automation', 'Q8', 'AI reviews contracts and extracts key terms, dates, and obligations', 'Automatically review contracts, extract key clauses, dates, and obligations. Flag non-standard terms and populate your contract database. Reduce legal review time by 70%.', '70-80% faster contract review', 'Catch risky clauses automatically', 'Searchable contract database', 'Proactive renewal reminders', '["Reduce operational costs", "Higher quality / consistency in deliverables"]', '["Freeing up employee time for higher-value work", "Higher quality / consistency in deliverables"]', 30000, '$18K-$55K', 12, '10-14 weeks', 70000, '$40K-$140K/year', 5, 'Moderate', 3, 'Contract analysis AI', 'Key term extraction', 'Risk flagging system', 'Contract database', 'Renewal tracking', 4500, '["Legal", "Real Estate", "Financial Services"]'),
('opp_workflow_automation', 'Workflow Automation (Multi-System Integration)', 'Operations & Automation', 'Q10', 'AI automates repetitive workflows across multiple systems', 'Connect your systems and automate repetitive workflows: lead to CRM, support ticket to project, order to fulfillment. Eliminate manual data copying and context switching.', 'Save 5-15 hours/week per employee', 'Zero manual data transfer errors', 'Real-time sync across systems', 'Process consistency', '["Reduce operational costs", "Improve team productivity"]', '["Freeing up employee time for higher-value work"]', 20000, '$12K-$35K', 8, '6-10 weeks', 45000, '$25K-$90K/year', 5, 'Simple', 2, 'Multi-system integration platform', 'Custom workflow builders', 'Error handling and monitoring', 'Pre-built connectors', 'Analytics dashboard', 3000, '["All industries"]'),
('opp_automated_report_generation', 'Automated Report Generation', 'Operations & Automation', 'Q10', 'AI generates business reports from data across multiple systems', 'Automatically generate weekly/monthly reports by pulling data from multiple systems, creating visualizations, and generating insights. No more manual spreadsheet work.', 'Reports in minutes vs hours', 'Always up-to-date data', 'Consistent formatting', 'Automatic insight generation', '["Improve team productivity", "Better data and insights for decisions"]', '["Freeing up employee time for higher-value work", "Better data and insights for decisions"]', 15000, '$8K-$25K', 6, '4-8 weeks', 30000, '$18K-$60K/year', 6, 'Simple', 1, 'Report automation engine', 'Data source integrations', 'Customizable templates', 'Automated distribution', 'Insight generation', 2000, '["All industries"]'),
('opp_competitive_intelligence', 'Competitive Intelligence Monitor', 'Strategic', 'Q6', 'AI monitors competitors and surfaces strategic insights', 'Automatically track competitor websites, product launches, pricing changes, and news. AI generates weekly intelligence reports with strategic recommendations.', 'Stay ahead of competitive threats', 'Automated competitor monitoring', 'Strategic insights from data', 'Market trend identification', '["Competitive advantage / innovation", "Better data and insights for decisions"]', '["Better data and insights for decisions", "Competitive advantage / innovation"]', 22000, '$15K-$40K', 8, '6-10 weeks', 40000, '$25K-$80K/year', 7, 'Moderate', 2, 'Competitive monitoring system', 'Automated data collection', 'Weekly intelligence reports', 'Alert system for major changes', 'Trend analysis', 3000, '["All industries", "Especially competitive markets"]'),
('opp_customer_sentiment_analysis', 'Customer Sentiment Analysis Dashboard', 'Strategic', 'Q5', 'AI analyzes customer feedback and surfaces insights', 'Aggregate feedback from support tickets, reviews, surveys, and social media. AI identifies trends, sentiment shifts, and product improvement opportunities.', 'Identify issues before they escalate', 'Data-driven product decisions', 'Track sentiment over time', 'Competitive positioning insights', '["Improve customer satisfaction / retention", "Better data and insights for decisions"]', '["Better data and insights for decisions"]', 28000, '$18K-$50K', 10, '8-12 weeks', 50000, '$30K-$100K/year', 7, 'Moderate', 2, 'Sentiment analysis engine', 'Multi-source data aggregation', 'Trend identification', 'Executive dashboard', 'Automated alerts', 4000, '["SaaS", "E-commerce", "Consumer products"]'),
('opp_voice_of_customer_engine', 'Voice-of-Customer Insights Engine', 'Strategic', 'Q5', 'AI synthesizes all customer interactions into actionable insights', 'Comprehensive analysis of all customer touchpoints: calls, emails, chats, surveys. AI identifies common requests, pain points, and expansion opportunities. Feeds product and marketing strategy.', '360-degree customer understanding', 'Prioritize product roadmap with data', 'Identify upsell opportunities', 'Reduce churn proactively', '["Improve customer satisfaction / retention", "Competitive advantage / innovation", "Better data and insights for decisions"]', '["Better data and insights for decisions"]', 35000, '$20K-$60K', 12, '10-14 weeks', 70000, '$40K-$140K/year', 6, 'Complex', 3, 'Multi-channel data aggregation', 'AI insight generation', 'Opportunity identification', 'Executive reporting', 'Integration with product/CRM tools', 5000, '["SaaS", "Professional Services", "B2B companies"]');
