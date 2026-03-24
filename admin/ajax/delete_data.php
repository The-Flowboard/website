<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
requireLogin();

// Require CSRF token for DELETE operations
requireCSRFToken();

require_once '../../php/db_config.php';

header('Content-Type: application/json');

$type = $_POST['type'] ?? '';
$ids = $_POST['ids'] ?? [];

if (empty($type) || empty($ids)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$table = '';
switch($type) {
    case 'contacts':
        $table = 'contact_submissions';
        break;
    case 'blogs':
        $table = 'blog_posts';
        break;
    case 'courses':
        $table = 'courses_interest';
        break;
    case 'assessments':
        $table = 'assessment_submissions';
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid type']);
        exit;
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('i', count($ids));

// For blog posts, collect associated image paths before deleting so we can purge the files
$imagesToDelete = [];
if ($table === 'blog_posts') {
    $imgStmt = $conn->prepare("SELECT featured_image FROM blog_posts WHERE id IN ($placeholders) AND featured_image != ''");
    $imgStmt->bind_param($types, ...$ids);
    $imgStmt->execute();
    $imgResult = $imgStmt->get_result();
    while ($row = $imgResult->fetch_assoc()) {
        $imagesToDelete[] = $row['featured_image'];
    }
    $imgStmt->close();
}

$stmt = $conn->prepare("DELETE FROM $table WHERE id IN ($placeholders)");
$stmt->bind_param($types, ...$ids);

if ($stmt->execute()) {
    // Delete image files that belonged to the removed blog posts
    foreach ($imagesToDelete as $imgPath) {
        if (strpos($imgPath, '/images/blog/') === 0) {
            $fullPath = rtrim(__DIR__ . '/../../', '/') . '/' . ltrim($imgPath, '/');
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }
    }
    logActivity("Deleted " . count($ids) . " items from $table", $table, 0);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Delete failed']);
}
