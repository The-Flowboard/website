<?php
/**
 * CSRF Protection Utilities
 * Provides functions for generating and validating CSRF tokens
 */

/**
 * Generate CSRF token for current session
 * @return string The generated CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token from request
 * @return bool True if valid, false otherwise
 */
function validateCSRFToken() {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (empty($_SESSION['csrf_token'])) {
        return false;
    }

    // Use timing-safe comparison to prevent timing attacks
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Require valid CSRF token or die with 403 error
 * Used in admin AJAX endpoints
 */
function requireCSRFToken() {
    if (!validateCSRFToken()) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'CSRF token validation failed. Please refresh the page and try again.'
        ]);
        exit;
    }
}

/**
 * Get CSRF token HTML input field
 * @return string HTML input field with CSRF token
 */
function csrfTokenField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Get CSRF token meta tag for HTML head
 * @return string HTML meta tag with CSRF token
 */
function csrfTokenMeta() {
    $token = generateCSRFToken();
    return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}
?>
