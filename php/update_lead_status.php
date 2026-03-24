<?php
/**
 * Update Lead Status Endpoint
 *
 * This endpoint allows n8n to update the status of contact submissions
 *
 * Usage:
 * POST /php/update_lead_status.php
 *
 * Body (JSON):
 * {
 *   "api_key": "your-secure-api-key",
 *   "id": 123,
 *   "status": "contacted"
 * }
 *
 * Supported statuses:
 * - new (default)
 * - contacted
 * - qualified
 * - proposal_sent
 * - won
 * - lost
 * - nurture
 */

require_once 'db_config.php';

header('Content-Type: application/json');

// API Key from environment variables
define('API_KEY', $_ENV['N8N_API_KEY'] ?? '');

// Get JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate API key
$api_key = $data['api_key'] ?? '';
if ($api_key !== API_KEY) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid API key']);
    exit;
}

// Get parameters
$id = $data['id'] ?? null;
$status = $data['status'] ?? '';

// Validate required fields
if (empty($id) || empty($status)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields: id and status']);
    exit;
}

// Validate status value
$allowed_statuses = ['new', 'contacted', 'qualified', 'proposal_sent', 'won', 'lost', 'nurture'];
if (!in_array($status, $allowed_statuses)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid status. Allowed values: ' . implode(', ', $allowed_statuses)
    ]);
    exit;
}

// Update the status
$stmt = $conn->prepare("UPDATE contact_submissions SET status = ?, contacted_at = NOW() WHERE id = ?");
$stmt->bind_param("si", $status, $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Lead status updated successfully',
            'id' => $id,
            'status' => $status
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Lead not found']);
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update status']);
}

$stmt->close();
$conn->close();
