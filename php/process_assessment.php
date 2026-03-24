<?php
/**
 * AI Opportunity Assessment - Processing Engine
 *
 * Receives assessment responses, calculates opportunity scores,
 * ranks them, personalizes costs/ROI, and stores results
 */

session_start();
require_once 'db_config.php';
require_once __DIR__ . '/webhook_queue.php';
require_once __DIR__ . '/../admin/includes/csrf.php';
require_once __DIR__ . '/rate_limiter.php';

header('Content-Type: application/json');

// Validate CSRF token (sent as X-CSRF-Token header by assessment.js)
requireCSRFToken();

// Rate limiting - 3 submissions per hour per IP
$rateLimiter = new RateLimiter();
$ip_address = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'unknown';

if ($rateLimiter->isRateLimited("assessment_{$ip_address}", 3, 3600)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many submissions. Please try again later.']);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit;
}

// Validate required fields
$required = ['Q1', 'Q2', 'Q3', 'Q4', 'Q5', 'Q6', 'Q7', 'Q8', 'Q9', 'Q10',
             'Q11', 'Q12', 'Q13', 'Q14', 'Q15', 'Q16', 'Q17',
             'contactName', 'contactEmail', 'contactPhone', 'contactCompany'];

foreach ($required as $field) {
    // Check if field exists
    if (!isset($data[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }

    // For text fields (not numeric scales), also check if empty
    $numericFields = ['Q5', 'Q6', 'Q7', 'Q8', 'Q9', 'Q10'];
    if (!in_array($field, $numericFields)) {
        if ($data[$field] === '') {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            exit;
        }
    }
    // Note: For numeric fields (Q5-Q10), we allow 0 as a valid value, so we only check isset()
}

try {
    // =====================================================
    // STEP 1: INSERT ASSESSMENT SUBMISSION
    // =====================================================

    $stmt = $conn->prepare("
        INSERT INTO assessment_submissions (
            name, email, phone, company_name,
            company_size, industry, annual_revenue, tech_comfort,
            pain_customer_support, pain_content_creation, pain_sales_lead_mgmt,
            pain_document_processing, pain_knowledge_mgmt, pain_repetitive_data,
            primary_goal, value_priority, budget_range, timeline_expectation,
            executive_sponsor, data_organization, biggest_concern,
            ip_address, user_agent, submitted_at
        ) VALUES (
            ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, NOW()
        )
    ");

    // Assign all values to variables first (required for PHP 8.4 bind_param by reference)
    $contact_name = $data['contactName'];
    $contact_email = $data['contactEmail'];
    $contact_phone = $data['contactPhone'] ?? '';
    $contact_company = $data['contactCompany'];
    $q1 = $data['Q1'];
    $q2 = $data['Q2'];
    $q3 = $data['Q3'];
    $q4 = $data['Q4'];
    $q5 = $data['Q5'];
    $q6 = $data['Q6'];
    $q7 = $data['Q7'];
    $q8 = $data['Q8'];
    $q9 = $data['Q9'];
    $q10 = $data['Q10'];
    $q11 = $data['Q11'];
    $q12 = $data['Q12'];
    $q13 = $data['Q13'];
    $q14 = $data['Q14'];
    $q15 = $data['Q15'];
    $q16 = $data['Q16'];
    $q17 = $data['Q17'];
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $stmt->bind_param("ssssssssiiiiiisssssssss",
        $contact_name,
        $contact_email,
        $contact_phone,
        $contact_company,
        $q1,  // company_size
        $q2,  // industry
        $q3,  // annual_revenue
        $q4,  // tech_comfort
        $q5,  // pain_customer_support
        $q6,  // pain_content_creation
        $q7,  // pain_sales_lead_mgmt
        $q8,  // pain_document_processing
        $q9,  // pain_knowledge_mgmt
        $q10, // pain_repetitive_data
        $q11, // primary_goal
        $q12, // value_priority
        $q13, // budget_range
        $q14, // timeline_expectation
        $q15, // executive_sponsor
        $q16, // data_organization
        $q17, // biggest_concern
        $ip_address,
        $user_agent
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to save assessment: " . $stmt->error);
    }

    $submission_id = $conn->insert_id;

    // =====================================================
    // STEP 2: CALCULATE READINESS SCORE
    // =====================================================

    $readiness_score = calculateReadinessScore($data);
    $readiness_level = getReadinessLevel($readiness_score);

    // Update submission with readiness
    $stmt = $conn->prepare("UPDATE assessment_submissions SET readiness_score = ?, readiness_level = ? WHERE id = ?");
    $stmt->bind_param("dsi", $readiness_score, $readiness_level, $submission_id);
    $stmt->execute();

    // =====================================================
    // STEP 3: GET ALL OPPORTUNITIES FROM DATABASE
    // =====================================================

    $opportunities_result = $conn->query("SELECT * FROM ai_opportunities ORDER BY id");
    $opportunities = [];
    while ($row = $opportunities_result->fetch_assoc()) {
        $opportunities[] = $row;
    }

    // =====================================================
    // STEP 4: SCORE ALL OPPORTUNITIES
    // =====================================================

    $scored_opportunities = [];

    foreach ($opportunities as $opp) {
        $score_data = calculateOpportunityScore($opp, $data, $readiness_score);

        // Store detailed score
        $stmt = $conn->prepare("
            INSERT INTO assessment_opportunity_scores (
                submission_id, opportunity_id,
                pain_score, strategic_fit_score, readiness_bonus, complexity_penalty, final_score,
                personalized_cost, personalized_roi_annual
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param("isdddddii",
            $submission_id,
            $opp['opportunity_id'],
            $score_data['pain_score'],
            $score_data['strategic_fit_score'],
            $score_data['readiness_bonus'],
            $score_data['complexity_penalty'],
            $score_data['final_score'],
            $score_data['personalized_cost'],
            $score_data['personalized_roi_annual']
        );

        $stmt->execute();

        // Add to scored array
        $scored_opportunities[] = [
            'opportunity' => $opp,
            'score_data' => $score_data
        ];
    }

    // =====================================================
    // STEP 5: RANK AND SELECT TOP 5
    // =====================================================

    // Sort by final score (descending)
    usort($scored_opportunities, function($a, $b) {
        return $b['score_data']['final_score'] <=> $a['score_data']['final_score'];
    });

    // Filter: only keep scores > 2.0 threshold
    $filtered = array_filter($scored_opportunities, function($item) {
        return $item['score_data']['final_score'] >= 2.0;
    });

    // Take top 5 (or fewer if less than 5 qualify)
    $top_opportunities = array_slice($filtered, 0, 5);

    // If fewer than 3 qualified, lower threshold and try again
    if (count($top_opportunities) < 3) {
        $filtered = array_filter($scored_opportunities, function($item) {
            return $item['score_data']['final_score'] >= 1.0;
        });
        $top_opportunities = array_slice($filtered, 0, 5);
    }

    // Update ranks in database
    $rank = 1;
    $opportunity_ids = [];
    foreach ($top_opportunities as $item) {
        $opp_id = $item['opportunity']['opportunity_id'];
        $opportunity_ids[] = $opp_id;

        $stmt = $conn->prepare("
            UPDATE assessment_opportunity_scores
            SET `rank` = ?
            WHERE submission_id = ? AND opportunity_id = ?
        ");
        $stmt->bind_param("iis", $rank, $submission_id, $opp_id);
        $stmt->execute();
        $rank++;
    }

    // Store recommended opportunities as JSON
    $stmt = $conn->prepare("
        UPDATE assessment_submissions
        SET recommended_opportunities = ?
        WHERE id = ?
    ");
    $json_opps = json_encode($opportunity_ids);
    $stmt->bind_param("si", $json_opps, $submission_id);
    $stmt->execute();

    // =====================================================
    // STEP 6: ENQUEUE WEBHOOK FOR N8N
    // =====================================================

    $webhook_data = [
        'submission_id' => $submission_id,
        'name' => $data['contactName'],
        'email' => $data['contactEmail'],
        'phone' => $data['contactPhone'] ?? '',
        'company' => $data['contactCompany'],
        'industry' => $data['Q2'],
        'company_size' => $data['Q1'],
        'readiness_level' => $readiness_level,
        'top_opportunities' => array_map(function($item) {
            return [
                'name' => $item['opportunity']['name'],
                'score' => round($item['score_data']['final_score'], 2),
                'cost' => '$' . number_format($item['score_data']['personalized_cost']),
                'roi' => '$' . number_format($item['score_data']['personalized_roi_annual']) . '/year'
            ];
        }, $top_opportunities),
        'submitted_at' => date('Y-m-d H:i:s')
    ];

    // Enqueue webhook for reliable delivery (with automatic retry)
    $queue = new WebhookQueue($conn);
    $webhook_id = $queue->enqueue('assessment', $webhook_data, 5);

    if ($webhook_id) {
        error_log("Assessment #{$submission_id} webhook enqueued (webhook #{$webhook_id})");
    } else {
        error_log("Warning: Failed to enqueue webhook for assessment #{$submission_id}");
    }

    // =====================================================
    // STEP 7: RETURN SUCCESS RESPONSE
    // =====================================================

    echo json_encode([
        'success' => true,
        'submission_id' => $submission_id,
        'readiness_level' => $readiness_level,
        'opportunity_count' => count($top_opportunities),
        'message' => 'Assessment processed successfully'
    ]);

} catch (Exception $e) {
    error_log("Assessment processing error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred processing your assessment. Please try again.'
    ]);
}

$conn->close();

// =====================================================
// HELPER FUNCTIONS
// =====================================================

/**
 * Calculate readiness score (0-7 scale)
 */
function calculateReadinessScore($data) {
    $score = 0;

    // Tech Comfort (Q4)
    switch ($data['Q4']) {
        case 'Very comfortable':
            $score += 2;
            break;
        case 'Moderately comfortable':
            $score += 1;
            break;
        case 'Somewhat comfortable':
            $score += 0;
            break;
        case 'Not very comfortable':
            $score -= 1;
            break;
    }

    // Executive Sponsor (Q15)
    switch ($data['Q15']) {
        case 'Yes - C-level executive committed':
            $score += 3;
            break;
        case 'Yes - VP/Director level committed':
            $score += 2;
            break;
        case 'Maybe - Need to build the business case first':
            $score += 1;
            break;
        case 'No - Would need to convince leadership':
            $score -= 2;
            break;
    }

    // Data Organization (Q16)
    switch ($data['Q16']) {
        case 'Well-organized in centralized systems':
            $score += 2;
            break;
        case 'Somewhat organized but spread across multiple tools':
            $score += 1;
            break;
        case 'Not very organized':
            $score += 0;
            break;
        case 'Mostly on paper or not digitized':
            $score -= 2;
            break;
    }

    return max(0, min(7, $score)); // Clamp between 0-7
}

/**
 * Get readiness level from score
 */
function getReadinessLevel($score) {
    if ($score >= 5) {
        return 'Advanced';
    } elseif ($score >= 2) {
        return 'Intermediate';
    } else {
        return 'Beginner';
    }
}

/**
 * Calculate score for a single opportunity
 */
function calculateOpportunityScore($opp, $data, $readiness_score) {
    // Pain Score (0-5 from user, weighted 40%)
    $pain_question = $opp['pain_question'];
    $pain_score = (float) $data[$pain_question];

    // Strategic Fit Score (0-5 scale, weighted 30%)
    $strategic_fit = 0;

    // Decode JSON arrays
    $primary_goals = json_decode($opp['primary_goals'], true);
    $value_priorities = json_decode($opp['value_priorities'], true);

    // Check if user's primary goal matches (add 3 points)
    if (in_array($data['Q11'], $primary_goals)) {
        $strategic_fit += 3;
    }

    // Check if user's value priority matches (add 2 points)
    if (in_array($data['Q12'], $value_priorities)) {
        $strategic_fit += 2;
    }

    // Readiness Bonus (normalize to 0-5 scale, weighted 20%)
    $readiness_bonus = ($readiness_score / 7) * 5;

    // Complexity Penalty (weighted 10%)
    $complexity_penalty = 0;

    // Budget filter
    $budget_fits = budgetFits($opp['budget_tier'], $data['Q13']);
    if (!$budget_fits) {
        $complexity_penalty += 5; // Heavy penalty if out of budget
    }

    // Timeline check
    $timeline_fits = timelineFits($opp['timeline_weeks'], $data['Q14']);
    if (!$timeline_fits) {
        $complexity_penalty += 2;
    }

    // Calculate final score
    $final_score =
        ($pain_score * 0.4) +
        ($strategic_fit * 0.3) +
        ($readiness_bonus * 0.2) -
        ($complexity_penalty * 0.1);

    // Personalize cost and ROI based on company size
    $cost_multiplier = getCompanySizeMultiplier($data['Q1'], 'cost');
    $roi_multiplier = getCompanySizeMultiplier($data['Q1'], 'roi');

    $personalized_cost = (int) ($opp['cost_base'] * $cost_multiplier);
    $personalized_roi_annual = (int) ($opp['roi_annual_base'] * $roi_multiplier);

    return [
        'pain_score' => $pain_score,
        'strategic_fit_score' => $strategic_fit,
        'readiness_bonus' => $readiness_bonus,
        'complexity_penalty' => $complexity_penalty,
        'final_score' => max(0, $final_score), // Never negative
        'personalized_cost' => $personalized_cost,
        'personalized_roi_annual' => $personalized_roi_annual,
        'payback_months' => $personalized_roi_annual > 0 ?
            round(($personalized_cost / $personalized_roi_annual) * 12) : 999
    ];
}

/**
 * Check if opportunity budget tier fits user's budget range
 */
function budgetFits($budget_tier, $user_budget) {
    $budget_map = [
        'Under $10K' => [1],
        '$10K - $25K' => [1, 2],
        '$25K - $50K' => [1, 2, 3],
        '$50K - $100K' => [1, 2, 3, 4],
        '$100K+' => [1, 2, 3, 4, 5],
        'Not sure yet / Depends on ROI' => [1, 2, 3] // Assume moderate budget
    ];

    $allowed_tiers = $budget_map[$user_budget] ?? [1, 2, 3, 4, 5];
    return in_array($budget_tier, $allowed_tiers);
}

/**
 * Check if opportunity timeline fits user's expectation
 */
function timelineFits($opp_weeks, $user_timeline) {
    $timeline_map = [
        '1-2 months' => 8,  // 8 weeks
        '3-6 months' => 26, // 26 weeks
        '6-12 months' => 52,
        '12+ months' => 999,
        'Flexible / Depends on the opportunity' => 26 // Assume moderate
    ];

    $max_weeks = $timeline_map[$user_timeline] ?? 26;
    return $opp_weeks <= $max_weeks;
}

/**
 * Get cost/ROI multiplier based on company size
 */
function getCompanySizeMultiplier($company_size, $type) {
    if ($type === 'cost') {
        $multipliers = [
            '1-10 employees' => 0.7,
            '11-50 employees' => 1.0,
            '51-200 employees' => 1.3,
            '201-1,000 employees' => 1.8,
            '1,000+ employees' => 2.5
        ];
    } else { // roi
        $multipliers = [
            '1-10 employees' => 0.6,
            '11-50 employees' => 1.0,
            '51-200 employees' => 1.5,
            '201-1,000 employees' => 2.5,
            '1,000+ employees' => 4.0
        ];
    }

    return $multipliers[$company_size] ?? 1.0;
}
?>
