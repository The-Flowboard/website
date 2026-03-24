-- ============================================
-- AI OPPORTUNITY ASSESSMENT - DATABASE SCHEMA
-- ============================================
-- Created: December 19, 2025
-- Purpose: Store AI opportunities and user assessment submissions

-- Table 1: AI Opportunities Database (20 pre-defined opportunities)
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
    primary_goals JSON NOT NULL COMMENT 'Array of goals from Q11',
    value_priorities JSON NOT NULL COMMENT 'Array of value priorities from Q12',
    cost_base INT NOT NULL COMMENT 'Base cost in dollars',
    cost_range_display VARCHAR(50) NOT NULL,
    timeline_weeks INT NOT NULL,
    timeline_display VARCHAR(50) NOT NULL,
    roi_annual_base INT NOT NULL COMMENT 'Base annual ROI in dollars',
    roi_range_display VARCHAR(50) NOT NULL,
    payback_months INT NOT NULL,
    complexity ENUM('Simple', 'Moderate', 'Complex') NOT NULL,
    budget_tier INT NOT NULL COMMENT '1-5 tier for budget filtering',
    deliverable_1 TEXT,
    deliverable_2 TEXT,
    deliverable_3 TEXT,
    deliverable_4 TEXT,
    deliverable_5 TEXT,
    maintenance_annual INT NOT NULL COMMENT 'Annual maintenance cost',
    industry_fit JSON COMMENT 'Array of industries this fits best',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_pain_question (pain_question),
    INDEX idx_budget_tier (budget_tier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 2: Assessment Submissions (stores user responses and results)
CREATE TABLE IF NOT EXISTS assessment_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Contact Information
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    company_name VARCHAR(255),

    -- Section 1: Business Context (Q1-Q4)
    company_size ENUM('1-10 employees', '11-50 employees', '51-200 employees', '201-1,000 employees', '1,000+ employees') NOT NULL,
    industry VARCHAR(100) NOT NULL,
    annual_revenue ENUM('Under $500K', '$500K - $2M', '$2M - $10M', '$10M - $50M', '$50M+') NOT NULL,
    tech_comfort ENUM('Very comfortable', 'Moderately comfortable', 'Somewhat comfortable', 'Not very comfortable') NOT NULL,

    -- Section 2: Pain Points (Q5-Q10) - Scale 0-5
    pain_customer_support INT NOT NULL COMMENT 'Q5: 0-5 scale',
    pain_content_creation INT NOT NULL COMMENT 'Q6: 0-5 scale',
    pain_sales_lead_mgmt INT NOT NULL COMMENT 'Q7: 0-5 scale',
    pain_document_processing INT NOT NULL COMMENT 'Q8: 0-5 scale',
    pain_knowledge_mgmt INT NOT NULL COMMENT 'Q9: 0-5 scale',
    pain_repetitive_data INT NOT NULL COMMENT 'Q10: 0-5 scale',

    -- Section 3: Strategic Priorities (Q11-Q14)
    primary_goal VARCHAR(255) NOT NULL,
    value_priority VARCHAR(255) NOT NULL,
    budget_range ENUM('Under $10K', '$10K - $25K', '$25K - $50K', '$50K - $100K', '$100K+', 'Not sure yet / Depends on ROI') NOT NULL,
    timeline_expectation ENUM('1-2 months', '3-6 months', '6-12 months', '12+ months', 'Flexible / Depends on the opportunity') NOT NULL,

    -- Section 4: Readiness Assessment (Q15-Q17)
    executive_sponsor ENUM('Yes - C-level executive committed', 'Yes - VP/Director level committed', 'Maybe - Need to build the business case first', 'No - Would need to convince leadership') NOT NULL,
    data_organization ENUM('Well-organized in centralized systems', 'Somewhat organized but spread across multiple tools', 'Not very organized', 'Mostly on paper or not digitized') NOT NULL,
    biggest_concern VARCHAR(255) NOT NULL,

    -- Calculated Scores
    readiness_score DECIMAL(5,2) COMMENT 'Calculated readiness 0-7',
    readiness_level ENUM('Crawl', 'Walk', 'Run') COMMENT 'Calculated readiness level',

    -- Top 5 Recommended Opportunities (stored as JSON for flexibility)
    recommended_opportunities JSON COMMENT 'Array of top 5 opportunity IDs with scores',

    -- Metadata
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

-- Table 3: Assessment Opportunity Scores (detailed scoring breakdown for analytics)
CREATE TABLE IF NOT EXISTS assessment_opportunity_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    submission_id INT NOT NULL,
    opportunity_id VARCHAR(100) NOT NULL,
    pain_score DECIMAL(5,2) NOT NULL,
    strategic_fit_score DECIMAL(5,2) NOT NULL,
    readiness_bonus DECIMAL(5,2) NOT NULL,
    complexity_penalty DECIMAL(5,2) NOT NULL,
    final_score DECIMAL(5,2) NOT NULL,
    `rank` INT COMMENT 'Ranking 1-5 for top opportunities, NULL for others',
    personalized_cost INT COMMENT 'Cost adjusted for company size',
    personalized_roi_annual INT COMMENT 'ROI adjusted for company size',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (submission_id) REFERENCES assessment_submissions(id) ON DELETE CASCADE,
    INDEX idx_submission (submission_id),
    INDEX idx_opportunity (opportunity_id),
    INDEX idx_final_score (final_score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
