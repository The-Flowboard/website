<?php
/**
 * CSRF Token Generator for Public Forms
 * Returns a CSRF token for use in contact and other public forms
 */

session_start();

// Load CSRF utility
require_once __DIR__ . '/../admin/includes/csrf.php';

// Set JSON header
header('Content-Type: application/json');

// Generate and return CSRF token
echo json_encode([
    'success' => true,
    'token' => generateCSRFToken()
]);
?>
