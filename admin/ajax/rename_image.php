<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
requireLogin();

// Require CSRF token for rename operations
requireCSRFToken();

header('Content-Type: application/json');

// Read JSON body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$oldPath = $data['oldPath'] ?? '';
$newFilename = $data['newFilename'] ?? '';

if (empty($oldPath) || empty($newFilename)) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

// Security: Ensure old path is within blog directory
if (strpos($oldPath, '/images/blog/') !== 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid image path']);
    exit;
}

// Validate new filename
if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $newFilename)) {
    echo json_encode(['success' => false, 'message' => 'Invalid filename. Use only letters, numbers, dashes, underscores, and dots.']);
    exit;
}

// Ensure new filename has extension
$oldFilename = basename($oldPath);
$oldExtension = strtolower(pathinfo($oldFilename, PATHINFO_EXTENSION));
$newExtension = strtolower(pathinfo($newFilename, PATHINFO_EXTENSION));

// If new filename has no extension, add the old one
if (empty($newExtension)) {
    $newFilename .= '.' . $oldExtension;
} else {
    // Validate extension
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($newExtension, $allowedExtensions)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file extension. Allowed: jpg, jpeg, png, webp']);
        exit;
    }
}

$oldFullPath = __DIR__ . '/../../' . $oldPath;
$newPath = '/images/blog/' . $newFilename;
$newFullPath = __DIR__ . '/../../' . $newPath;

// Check if old file exists
if (!file_exists($oldFullPath)) {
    echo json_encode(['success' => false, 'message' => 'Image not found']);
    exit;
}

// Check if new filename already exists
if (file_exists($newFullPath) && $oldFullPath !== $newFullPath) {
    echo json_encode(['success' => false, 'message' => 'A file with that name already exists']);
    exit;
}

try {
    // Rename the file
    if (!rename($oldFullPath, $newFullPath)) {
        throw new Exception('Failed to rename file');
    }

    // Update database: Update all blog posts that use this image
    require_once __DIR__ . '/../../php/db_config.php';

    $stmt = $conn->prepare("UPDATE blog_posts SET featured_image = ? WHERE featured_image = ?");
    $stmt->bind_param('ss', $newPath, $oldPath);
    $stmt->execute();
    $affectedRows = $stmt->affected_rows;
    $stmt->close();

    // Log activity
    logActivity('rename_image', 'blog_posts', null, "Renamed: {$oldPath} to {$newPath}, updated {$affectedRows} blog posts");

    echo json_encode([
        'success' => true,
        'message' => 'Image renamed successfully',
        'newPath' => $newPath,
        'newFilename' => $newFilename,
        'updatedBlogs' => $affectedRows
    ]);

} catch (Exception $e) {
    // If rename succeeded but database update failed, try to rename back
    if (file_exists($newFullPath)) {
        rename($newFullPath, $oldFullPath);
    }

    echo json_encode(['success' => false, 'message' => 'Failed to rename image: ' . $e->getMessage()]);
}
