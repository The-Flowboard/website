<?php
/**
 * AI Opportunity Assessment - Results Display Page
 * Shows personalized recommendations based on assessment responses
 */

require_once 'php/db_config.php';

// Get submission ID from URL
$submission_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$submission_id) {
    header('Location: /assessment');
    exit;
}

// Fetch submission data
$stmt = $conn->prepare("SELECT * FROM assessment_submissions WHERE id = ?");
$stmt->bind_param("i", $submission_id);
$stmt->execute();
$result = $stmt->get_result();
$submission = $result->fetch_assoc();

if (!$submission) {
    header('Location: /assessment');
    exit;
}

// Fetch top ranked opportunities with scores
$stmt = $conn->prepare("
    SELECT
        aos.*,
        ao.*
    FROM assessment_opportunity_scores aos
    JOIN ai_opportunities ao ON aos.opportunity_id = ao.opportunity_id
    WHERE aos.submission_id = ? AND aos.rank IS NOT NULL
    ORDER BY aos.rank ASC
");
$stmt->bind_param("i", $submission_id);
$stmt->execute();
$result = $stmt->get_result();
$opportunities = [];
while ($row = $result->fetch_assoc()) {
    $opportunities[] = $row;
}

// Update results_viewed_at timestamp
$stmt = $conn->prepare("UPDATE assessment_submissions SET results_viewed_at = NOW() WHERE id = ?");
$stmt->bind_param("i", $submission_id);
$stmt->execute();

$conn->close();

// Helper functions
function formatCurrency($amount) {
    return '$' . number_format($amount);
}

function getReadinessMessage($level) {
    $messages = [
        'Advanced' => 'Excellent! You\'re well-positioned to start with a pilot project immediately.',
        'Intermediate' => 'Good foundation. We recommend starting with a smaller proof-of-concept to build confidence.',
        'Beginner' => 'Let\'s start with a discovery workshop to assess data readiness and build executive buy-in.'
    ];
    return $messages[$level] ?? $messages['Intermediate'];
}

function getConcernResponse($concern) {
    $responses = [
        'Cost / ROI uncertainty' => 'We understand your concern about ROI. That\'s why we recommend starting with a small pilot project to prove value before larger investment. We offer phased payment structures tied to milestones, and our typical clients see payback within 6-12 months and 300-500% ROI by Year 2.',

        'Technical complexity / our team\'s ability to use it' => 'You don\'t need to be technical - that\'s our job. We handle all technical setup and integration, train your team to use the tools (not build them), and provide ongoing support. Most clients are up and running within the first week after deployment.',

        'Data security / privacy' => 'Data security is non-negotiable. We ensure SOC 2 compliant infrastructure, GDPR/HIPAA compliance (if applicable), data encryption in transit and at rest, and no training on your proprietary data without explicit consent.',

        'Accuracy / reliability concerns' => 'We build production-grade systems with multiple safety measures: human-in-the-loop for critical decisions, confidence scoring (AI only acts when >90% confident), rigorous testing, and phased rollouts. Typical accuracy: 85-95%, comparable to or better than human performance.',

        'Change management / employee adoption' => 'We\'ve helped dozens of companies navigate this. Our approach: involve employees from Day 1, position AI as "assistant" not "replacement", start with their biggest pain points, and provide hands-on training. Employees become the biggest advocates once they see how much time AI saves them.',

        'Not sure where to start' => 'That\'s exactly what this assessment is for! Review this report, discuss with your team, then book a free 30-minute call with us. We\'ll help you prioritize and build a business case for leadership - no obligation, just expert guidance.'
    ];
    return $responses[$concern] ?? $responses['Not sure where to start'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Your AI Opportunity Assessment Results | <?php echo htmlspecialchars($submission['company_name']); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-deep: #0a1128;
            --primary-dark: #1e2749;
            --primary-mid: #2d3561;
            --accent-purple: #a855f7;
            --accent-cyan: #06b6d4;
            --accent-blue: #3b82f6;
            --accent-green: #10b981;
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.8);
            --text-muted: rgba(255, 255, 255, 0.6);
            --font-display: 'Sora', sans-serif;
            --font-body: 'Outfit', sans-serif;
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-body);
            background: linear-gradient(135deg, var(--primary-deep) 0%, var(--primary-dark) 50%, var(--primary-mid) 100%);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 3rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .header h1 {
            font-family: var(--font-display);
            font-size: clamp(2rem, 5vw, 3rem);
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header .company-name {
            font-size: 1.5rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }

        .header .date {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .results-grid {
            display: grid;
            gap: 2rem;
        }

        .card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .card h2 {
            font-family: var(--font-display);
            font-size: 1.75rem;
            margin-bottom: 1.5rem;
            color: var(--accent-cyan);
        }

        .card h3 {
            font-family: var(--font-display);
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .summary-item {
            background: rgba(255, 255, 255, 0.03);
            padding: 1.25rem;
            border-radius: 12px;
            border-left: 3px solid var(--accent-purple);
        }

        .summary-item label {
            display: block;
            color: var(--text-muted);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .summary-item .value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .readiness-badge {
            display: inline-block;
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            margin: 1rem 0;
        }

        .readiness-Advanced {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            border: 2px solid rgba(16, 185, 129, 0.3);
        }

        .readiness-Intermediate {
            background: rgba(6, 182, 212, 0.2);
            color: #06b6d4;
            border: 2px solid rgba(6, 182, 212, 0.3);
        }

        .readiness-Beginner {
            background: rgba(168, 85, 247, 0.2);
            color: #a855f7;
            border: 2px solid rgba(168, 85, 247, 0.3);
        }

        .opportunity-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 2px solid rgba(168, 85, 247, 0.2);
            position: relative;
        }

        .opportunity-card::before {
            content: '#' attr(data-rank);
            position: absolute;
            top: -1rem;
            left: 1.5rem;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-cyan));
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .opportunity-header {
            margin-top: 1rem;
            margin-bottom: 1.5rem;
        }

        .opportunity-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--accent-cyan);
        }

        .category-badge {
            display: inline-block;
            padding: 0.375rem 0.875rem;
            background: rgba(6, 182, 212, 0.1);
            border: 1px solid rgba(6, 182, 212, 0.3);
            border-radius: 20px;
            font-size: 0.85rem;
            color: var(--accent-cyan);
            margin-bottom: 1rem;
        }

        .why-matters {
            background: rgba(168, 85, 247, 0.05);
            padding: 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 3px solid var(--accent-purple);
        }

        .why-matters strong {
            color: var(--accent-purple);
        }

        .value-list {
            list-style: none;
            padding: 0;
            margin: 1rem 0;
        }

        .value-list li {
            padding: 0.75rem 0 0.75rem 2rem;
            position: relative;
            color: var(--text-secondary);
        }

        .value-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--accent-green);
            font-weight: bold;
            font-size: 1.2rem;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1.5rem 0;
            padding: 1.5rem;
            background: rgba(6, 182, 212, 0.05);
            border-radius: 12px;
        }

        .metric {
            text-align: center;
        }

        .metric-label {
            display: block;
            color: var(--text-muted);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .metric-value {
            display: block;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent-cyan);
        }

        .projection-box {
            background: rgba(16, 185, 129, 0.05);
            padding: 1.5rem;
            border-radius: 12px;
            margin: 1.5rem 0;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .projection-box h4 {
            color: var(--accent-green);
            margin-bottom: 1rem;
        }

        .year-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .year-row:last-child {
            border-bottom: none;
        }

        .deliverables {
            margin: 1.5rem 0;
        }

        .deliverables ul {
            list-style: none;
            padding: 0;
        }

        .deliverables li {
            padding: 0.5rem 0 0.5rem 1.75rem;
            position: relative;
            color: var(--text-secondary);
        }

        .deliverables li::before {
            content: '→';
            position: absolute;
            left: 0;
            color: var(--accent-purple);
        }

        .cta-box {
            background: linear-gradient(135deg, rgba(168, 85, 247, 0.1), rgba(6, 182, 212, 0.1));
            border: 2px solid rgba(168, 85, 247, 0.3);
            border-radius: 16px;
            padding: 2.5rem;
            text-align: center;
            margin-top: 3rem;
        }

        .cta-box h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .cta-box p {
            font-size: 1.1rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }

        .btn-primary {
            display: inline-block;
            padding: 1.25rem 3rem;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-cyan));
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: var(--transition);
            border: none;
            cursor: pointer;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(168, 85, 247, 0.3);
        }

        .btn-secondary {
            display: inline-block;
            padding: 1rem 2rem;
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            border: 2px solid rgba(255, 255, 255, 0.2);
            margin-left: 1rem;
            transition: var(--transition);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        @media print {
            body {
                background: white !important;
                color: black !important;
            }

            * {
                color: black !important;
                background: white !important;
                border-color: #cccccc !important;
            }

            .card, .opportunity-card, .why-matters, .projection-box, .metrics-grid, .summary-item, .deliverables {
                background: white !important;
                border-color: #cccccc !important;
            }

            .header h1, .opportunity-title, .metric-value, h2, h3, h4 {
                color: black !important;
                -webkit-text-fill-color: black !important;
            }

            .readiness-badge {
                border: 2px solid black !important;
                color: black !important;
                background: white !important;
            }

            .btn-primary, .btn-secondary, .cta-box {
                display: none !important;
            }

            .year-row, .value-list li, .deliverables li {
                color: black !important;
            }
        }

        @media (max-width: 768px) {
            .summary-grid {
                grid-template-columns: 1fr;
            }
            .metrics-grid {
                grid-template-columns: 1fr;
            }
            .btn-secondary {
                display: block;
                margin: 1rem 0 0 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>Your AI Opportunity Assessment</h1>
            <div class="company-name"><?php echo htmlspecialchars($submission['company_name']); ?></div>
            <div class="date">Prepared for <?php echo htmlspecialchars($submission['name']); ?> | <?php echo date('F j, Y'); ?></div>
        </div>

        <div class="results-grid">
            <!-- Executive Summary -->
            <div class="card">
                <h2><i class="fas fa-chart-line"></i> Assessment Summary</h2>
                <p style="font-size: 1.1rem; margin-bottom: 1.5rem;">
                    Based on your responses, we've identified <strong><?php echo count($opportunities); ?> high-impact AI opportunities</strong>
                    that align with your business priorities and readiness level.
                </p>

                <h3>Your Business Profile</h3>
                <div class="summary-grid">
                    <div class="summary-item">
                        <label>Company Size</label>
                        <div class="value"><?php echo htmlspecialchars($submission['company_size']); ?></div>
                    </div>
                    <div class="summary-item">
                        <label>Industry</label>
                        <div class="value"><?php echo htmlspecialchars($submission['industry']); ?></div>
                    </div>
                    <div class="summary-item">
                        <label>Primary Goal</label>
                        <div class="value"><?php echo htmlspecialchars($submission['primary_goal']); ?></div>
                    </div>
                    <div class="summary-item">
                        <label>Top Priority</label>
                        <div class="value"><?php echo htmlspecialchars($submission['value_priority']); ?></div>
                    </div>
                </div>

                <h3 style="margin-top: 2rem;">AI Readiness Level</h3>
                <div class="readiness-badge readiness-<?php echo htmlspecialchars($submission['readiness_level'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($submission['readiness_level'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <p style="margin-top: 1rem; color: var(--text-secondary);">
                    <?php echo htmlspecialchars(getReadinessMessage($submission['readiness_level']), ENT_QUOTES, 'UTF-8'); ?>
                </p>

                <div class="summary-grid" style="margin-top: 1.5rem;">
                    <div class="summary-item">
                        <label>Executive Sponsorship</label>
                        <div class="value"><?php echo htmlspecialchars($submission['executive_sponsor']); ?></div>
                    </div>
                    <div class="summary-item">
                        <label>Data Organization</label>
                        <div class="value"><?php echo htmlspecialchars($submission['data_organization']); ?></div>
                    </div>
                    <div class="summary-item">
                        <label>Tech Comfort</label>
                        <div class="value"><?php echo htmlspecialchars($submission['tech_comfort']); ?></div>
                    </div>
                </div>
            </div>

            <!-- Top Opportunities -->
            <div class="card">
                <h2><i class="fas fa-trophy"></i> Your Top AI Opportunities (Ranked)</h2>

                <?php foreach ($opportunities as $index => $opp):
                    $rank = $opp['rank'];
                    $score = $opp['final_score'];
                    $cost = $opp['personalized_cost'];
                    $roi = $opp['personalized_roi_annual'];
                    $payback = $roi > 0 ? round(($cost / $roi) * 12) : 999;

                    // Decode JSON arrays
                    $business_values = array_filter([
                        $opp['business_value_1'],
                        $opp['business_value_2'],
                        $opp['business_value_3'],
                        $opp['business_value_4']
                    ]);

                    $deliverables = array_filter([
                        $opp['deliverable_1'],
                        $opp['deliverable_2'],
                        $opp['deliverable_3'],
                        $opp['deliverable_4'],
                        $opp['deliverable_5']
                    ]);

                    // Calculate 3-year projection
                    $year1_net = $roi - $cost;
                    $year2_net = $roi - $opp['maintenance_annual'];
                    $year3_net = $roi - $opp['maintenance_annual'];
                    $total_3year = $year1_net + $year2_net + $year3_net;
                ?>

                <div class="opportunity-card" data-rank="<?php echo $rank; ?>">
                    <div class="opportunity-header">
                        <div class="category-badge"><?php echo htmlspecialchars($opp['category']); ?></div>
                        <h3 class="opportunity-title"><?php echo htmlspecialchars($opp['name']); ?></h3>
                    </div>

                    <div class="why-matters">
                        <strong>Why This Matters for Your Business:</strong><br>
                        <?php echo htmlspecialchars($opp['description_detailed']); ?>
                    </div>

                    <h4>What You'll Get:</h4>
                    <ul class="value-list">
                        <?php foreach ($business_values as $value): ?>
                            <li><?php echo htmlspecialchars($value); ?></li>
                        <?php endforeach; ?>
                    </ul>

                    <h4>Investment & Returns:</h4>
                    <div class="metrics-grid">
                        <div class="metric">
                            <span class="metric-label">Project Cost</span>
                            <span class="metric-value"><?php echo formatCurrency($cost); ?></span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Annual ROI</span>
                            <span class="metric-value"><?php echo formatCurrency($roi); ?>/yr</span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Payback Period</span>
                            <span class="metric-value"><?php echo $payback; ?> months</span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Timeline</span>
                            <span class="metric-value"><?php echo htmlspecialchars($opp['timeline_display']); ?></span>
                        </div>
                    </div>

                    <div class="projection-box">
                        <h4>3-Year Financial Projection</h4>
                        <div class="year-row">
                            <span>Year 1:</span>
                            <span style="color: var(--accent-green); font-weight: 600;">
                                <?php echo formatCurrency($roi); ?> revenue - <?php echo formatCurrency($cost); ?> cost =
                                <strong><?php echo formatCurrency($year1_net); ?> net</strong>
                            </span>
                        </div>
                        <div class="year-row">
                            <span>Year 2:</span>
                            <span style="color: var(--accent-green); font-weight: 600;">
                                <?php echo formatCurrency($roi); ?> revenue - <?php echo formatCurrency($opp['maintenance_annual']); ?> maintenance =
                                <strong><?php echo formatCurrency($year2_net); ?> net</strong>
                            </span>
                        </div>
                        <div class="year-row">
                            <span>Year 3:</span>
                            <span style="color: var(--accent-green); font-weight: 600;">
                                <?php echo formatCurrency($roi); ?> revenue - <?php echo formatCurrency($opp['maintenance_annual']); ?> maintenance =
                                <strong><?php echo formatCurrency($year3_net); ?> net</strong>
                            </span>
                        </div>
                        <div class="year-row" style="border-top: 2px solid rgba(16, 185, 129, 0.3); margin-top: 0.5rem; padding-top: 1rem;">
                            <span style="font-weight: 700;">Total 3-Year Value:</span>
                            <span style="color: var(--accent-green); font-weight: 700; font-size: 1.25rem;">
                                <?php echo formatCurrency($total_3year); ?>
                            </span>
                        </div>
                    </div>

                    <div class="deliverables">
                        <h4>What's Included:</h4>
                        <ul>
                            <?php foreach ($deliverables as $deliverable): ?>
                                <li><?php echo htmlspecialchars($deliverable); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <?php endforeach; ?>
            </div>

            <!-- Addressing Concerns -->
            <div class="card">
                <h2><i class="fas fa-shield-alt"></i> Addressing Your Concerns</h2>
                <div class="why-matters">
                    <strong>Your Concern: <?php echo htmlspecialchars($submission['biggest_concern']); ?></strong>
                </div>
                <p style="line-height: 1.8; color: var(--text-secondary);">
                    <?php echo getConcernResponse($submission['biggest_concern']); ?>
                </p>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="cta-box">
            <h2>Ready to Move Forward?</h2>
            <p>Book a free 30-minute discovery call to discuss your top opportunities in detail</p>
            <a href="/contact" class="btn-primary">
                <i class="fas fa-calendar-alt"></i> Schedule Your Free Call
            </a>
            <a href="javascript:window.print()" class="btn-secondary">
                <i class="fas fa-download"></i> Save as PDF
            </a>
        </div>

        <div style="text-align: center; margin-top: 3rem; padding: 2rem; color: var(--text-muted);">
            <p style="font-size: 0.9rem;">
                This assessment is based on general information. Your actual ROI may vary based on specific business context,
                data quality, and implementation approach. We recommend a discovery call to refine these estimates for your unique situation.
            </p>
            <p style="margin-top: 1rem;">
                Questions? Email us at <a href="mailto:contact@joshimc.com" style="color: var(--accent-cyan);">contact@joshimc.com</a>
            </p>
        </div>
    </div>
</body>
</html>
