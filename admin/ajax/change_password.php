<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
requireLogin();

// Require CSRF token for password change
requireCSRFToken();

require_once '../../php/db_config.php';

header('Content-Type: application/json');

$current = $_POST['current_password'] ?? '';
$new = $_POST['new_password'] ?? '';

if (empty($current) || empty($new)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

if (strlen($new) < 6) {
    echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters']);
    exit;
}

$admin_id = $_SESSION['admin_id'];

// Verify current password
$stmt = $conn->prepare("SELECT password FROM admin_users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

if (!password_verify($current, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
    exit;
}

// Update password
$new_hash = password_hash($new, PASSWORD_DEFAULT);
$update = $conn->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
$update->bind_param("si", $new_hash, $admin_id);

if ($update->execute()) {
    logActivity("Changed password", 'admin_users', $admin_id);
    echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update password']);
}

$conn->close();
