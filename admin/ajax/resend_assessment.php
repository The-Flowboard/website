<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
requireLogin();

// Require CSRF token for resend operations
requireCSRFToken();

require_once '../../php/db_config.php';
require_once '../../php/webhook_queue.php';

header('Content-Type: application/json');

$assessment_id = $_POST['assessment_id'] ?? 0;

if (!$assessment_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid assessment ID']);
    exit;
}

try {
    // Fetch assessment data
    $stmt = $conn->prepare("SELECT * FROM assessment_submissions WHERE id = ?");
    $stmt->bind_param("i", $assessment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $submission = $result->fetch_assoc();

    if (!$submission) {
        echo json_encode(['success' => false, 'message' => 'Assessment not found']);
        exit;
    }

    // Fetch top opportunities
    $stmt = $conn->prepare("
        SELECT
            aos.*,
            ao.*
        FROM assessment_opportunity_scores aos
        JOIN ai_opportunities ao ON aos.opportunity_id = ao.opportunity_id
        WHERE aos.submission_id = ? AND aos.rank IS NOT NULL
        ORDER BY aos.rank ASC
        LIMIT 5
    ");
    $stmt->bind_param("i", $assessment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $opportunities = [];
    while ($row = $result->fetch_assoc()) {
        $opportunities[] = [
            'name' => $row['name'],
            'score' => round($row['final_score'], 2),
            'cost' => '$' . number_format($row['personalized_cost']),
            'roi' => '$' . number_format($row['personalized_roi_annual']) . '/year'
        ];
    }

    // Prepare webhook data
    $webhook_data = [
        'submission_id' => $assessment_id,
        'name' => $submission['name'],
        'email' => $submission['email'],
        'phone' => $submission['phone'] ?? '',
        'company' => $submission['company_name'],
        'industry' => $submission['industry'],
        'company_size' => $submission['company_size'],
        'readiness_level' => $submission['readiness_level'],
        'top_opportunities' => $opportunities,
        'results_url' => 'https://joshimc.com/assessment-results.php?id=' . $assessment_id,
        'resend' => true, // Flag to indicate this is a resend
        'resent_at' => date('Y-m-d H:i:s')
    ];

    // Enqueue webhook via WebhookQueue (non-blocking, with retry)
    $queue = new WebhookQueue($conn);
    $queue->enqueue('assessment', $webhook_data, 5);

    // Log the activity
    logActivity("Resent assessment results for submission #$assessment_id to {$submission['email']}", 'assessment_submissions', $assessment_id);

    echo json_encode([
        'success' => true,
        'message' => 'Assessment results queued for delivery'
    ]);

} catch (Exception $e) {
    error_log("Resend assessment error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while resending the assessment'
    ]);
}

$conn->close();
?>
