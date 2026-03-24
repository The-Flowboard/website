<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_once '../../php/db_config.php';
requireLogin();

// Require CSRF token for delete operations
requireCSRFToken();

header('Content-Type: application/json');

// Read JSON body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$imagePath = $data['path'] ?? $_POST['path'] ?? '';

if (empty($imagePath)) {
    echo json_encode(['success' => false, 'message' => 'No image path provided']);
    exit;
}

// Security: Ensure path is within blog directory
if (strpos($imagePath, '/images/blog/') !== 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid image path']);
    exit;
}

$fullPath = rtrim(__DIR__ . '/../../', '/') . '/' . ltrim($imagePath, '/');

if (!file_exists($fullPath)) {
    echo json_encode(['success' => false, 'message' => 'Image not found']);
    exit;
}

if (!unlink($fullPath)) {
    error_log("delete_image: unlink failed for {$fullPath}");
    echo json_encode(['success' => false, 'message' => 'Failed to delete image — check server file permissions']);
    exit;
}

// Clear any blog_posts rows that referenced this image
$stmt = $conn->prepare("UPDATE blog_posts SET featured_image = '' WHERE featured_image = ?");
$stmt->bind_param('s', $imagePath);
$stmt->execute();
$stmt->close();

logActivity('delete_image', 'blog_posts', null, "Deleted: {$imagePath}");
echo json_encode(['success' => true, 'message' => 'Image deleted']);
