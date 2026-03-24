<?php
/**
 * Enhanced Admin Authentication & Authorization System
 * Version: 2.0 (Security Hardened)
 *
 * Security Features:
 * - Secure session configuration (HttpOnly, Secure, SameSite)
 * - Session timeout enforcement (30 minutes)
 * - Session binding (IP + User-Agent fingerprint)
 * - Login rate limiting (5 attempts per 15 minutes)
 * - Session regeneration on login
 * - CSRF token generation
 * - Activity logging
 */

// Configure secure session settings BEFORE session_start()
if (session_status() === PHP_SESSION_NONE) {
    // Session cookie configuration
    ini_set('session.cookie_httponly', 1);  // Prevent JavaScript access
    ini_set('session.cookie_secure', 1);    // HTTPS only (comment out for local dev)
    ini_set('session.cookie_samesite', 'Strict'); // CSRF protection
    ini_set('session.gc_maxlifetime', 1800); // 30 minutes
    ini_set('session.use_strict_mode', 1);   // Reject uninitialized session IDs

    session_start();
}

// Load database connection
require_once '/var/www/html/php/db_config.php';

// Load CSRF protection
require_once __DIR__ . '/csrf.php';

if (!isset($conn)) {
    die("Database connection failed");
}

/**
 * Check if user is logged in
 * Also enforces session timeout and binding validation
 */
function isLoggedIn() {
    // Check session timeout
    checkSessionTimeout();

    // Check session binding
    if (!validateSessionBinding()) {
        return false;
    }

    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Require login - redirect if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $current_file = basename($_SERVER['PHP_SELF']);
        if ($current_file !== 'login.php') {
            header('Location: /admin/login.php?expired=1');
            exit;
        }
    }
}

/**
 * Check and enforce session timeout (30 minutes)
 */
function checkSessionTimeout() {
    $timeout = 1800; // 30 minutes

    if (isset($_SESSION['last_activity'])) {
        $elapsed = time() - $_SESSION['last_activity'];

        if ($elapsed > $timeout) {
            // Session expired
            logActivity('session_timeout', null, null, 'Session expired after ' . $elapsed . ' seconds');
            session_unset();
            session_destroy();
            header('Location: /admin/login.php?expired=1');
            exit;
        }
    }

    // Update last activity time
    $_SESSION['last_activity'] = time();
}

/**
 * Validate session is bound to same client
 * Prevents session hijacking
 */
function validateSessionBinding() {
    if (!isset($_SESSION['client_fingerprint'])) {
        return true; // First request, will be set on login
    }

    $current_fingerprint = getClientFingerprint();

    if ($_SESSION['client_fingerprint'] !== $current_fingerprint) {
        // Session hijacking attempt detected
        logActivity('session_hijack_attempt', null, null, 'Fingerprint mismatch detected');
        session_unset();
        session_destroy();
        return false;
    }

    return true;
}

/**
 * Get client fingerprint (IP + User-Agent hash)
 * Used to bind session to specific client
 */
function getClientFingerprint() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return hash('sha256', $ip . $user_agent);
}

/**
 * Enhanced login function with security features
 */
function login($username, $password) {
    global $conn;

    if (!$conn) {
        error_log("No database connection in login()");
        return false;
    }

    // Check login rate limiting
    if (isLoginRateLimited($username)) {
        $reset_time = getLoginResetTime($username);
        $minutes = ceil($reset_time / 60);
        error_log("Login rate limited for user: $username ($minutes minutes remaining)");
        return false;
    }

    // Fetch user from database
    $stmt = $conn->prepare("SELECT id, username, password FROM admin_users WHERE username = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            // ===== SUCCESSFUL LOGIN =====

            // Regenerate session ID to prevent fixation attacks
            session_regenerate_id(true);

            // Set session variables
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();
            $_SESSION['client_fingerprint'] = getClientFingerprint();

            // Generate CSRF token for new session
            generateCSRFToken();

            // Update last login timestamp
            $update = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
            $update->bind_param("i", $user['id']);
            $update->execute();

            // Log successful login
            logActivity('login', null, null, 'Admin logged in successfully');

            // Clear failed login attempts
            clearLoginAttempts($username);

            return true;
        } else {
            // ===== FAILED LOGIN =====
            // Record failed attempt
            recordFailedLogin($username);
            logActivity('failed_login', null, null, "Failed login attempt for user: $username");
        }
    } else {
        // User not found - still record attempt to prevent username enumeration timing attacks
        recordFailedLogin($username);
        logActivity('failed_login', null, null, "Failed login attempt for unknown user: $username");
    }

    return false;
}

/**
 * Check if login attempts are rate limited
 * 5 attempts per 15 minutes
 */
function isLoginRateLimited($username) {
    $key = "login_attempts_" . md5($username);
    $file = sys_get_temp_dir() . "/{$key}.json";

    if (!file_exists($file)) {
        return false;
    }

    $data = json_decode(file_get_contents($file), true);
    $attempts = $data['count'] ?? 0;
    $last_attempt = $data['time'] ?? 0;
    $window = 900; // 15 minutes

    // Allow 5 attempts per 15 minutes
    if ($attempts >= 5 && (time() - $last_attempt) < $window) {
        return true;
    }

    return false;
}

/**
 * Get remaining time until rate limit resets
 */
function getLoginResetTime($username) {
    $key = "login_attempts_" . md5($username);
    $file = sys_get_temp_dir() . "/{$key}.json";

    if (!file_exists($file)) {
        return 0;
    }

    $data = json_decode(file_get_contents($file), true);
    $last_attempt = $data['time'] ?? 0;
    $window = 900; // 15 minutes

    $reset_time = ($last_attempt + $window) - time();
    return max(0, $reset_time);
}

/**
 * Record failed login attempt
 */
function recordFailedLogin($username) {
    $key = "login_attempts_" . md5($username);
    $file = sys_get_temp_dir() . "/{$key}.json";

    $data = ['count' => 1, 'time' => time()];

    if (file_exists($file)) {
        $existing = json_decode(file_get_contents($file), true);
        $last_attempt = $existing['time'] ?? 0;
        $window = 900; // 15 minutes

        // Reset counter if outside window
        if ((time() - $last_attempt) > $window) {
            $data = ['count' => 1, 'time' => time()];
        } else {
            $data['count'] = ($existing['count'] ?? 0) + 1;
        }
    }

    file_put_contents($file, json_encode($data));
}

/**
 * Clear login attempts on successful login
 */
function clearLoginAttempts($username) {
    $key = "login_attempts_" . md5($username);
    $file = sys_get_temp_dir() . "/{$key}.json";

    if (file_exists($file)) {
        unlink($file);
    }
}

/**
 * Logout function
 */
function logout() {
    logActivity('logout', null, null, 'Admin logged out');
    session_unset();
    session_destroy();
    header('Location: /admin/login.php');
    exit;
}

/**
 * Log activity to admin_activity_log table
 */
function logActivity($action, $table = null, $record_id = null, $details = null) {
    global $conn;

    if (!$conn) return;

    $admin_id = $_SESSION['admin_id'] ?? null;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $stmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, action, table_name, record_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ississ", $admin_id, $action, $table, $record_id, $details, $ip_address);
        $stmt->execute();
    }
}

/**
 * Change password function
 */
function changePassword($current_password, $new_password) {
    global $conn;

    if (!$conn) return false;

    $admin_id = $_SESSION['admin_id'];

    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM admin_users WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!password_verify($current_password, $user['password'])) {
        return false;
    }

    // Validate new password strength (minimum 6 characters)
    if (strlen($new_password) < 6) {
        return false;
    }

    // Hash and update password
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $update = $conn->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
    $update->bind_param("si", $hashed, $admin_id);
    $update->execute();

    logActivity('change_password', 'admin_users', $admin_id, 'Password changed');
    return true;
}

/**
 * Get session info for debugging/monitoring
 */
function getSessionInfo() {
    if (!isLoggedIn()) {
        return null;
    }

    $login_time = $_SESSION['login_time'] ?? 0;
    $last_activity = $_SESSION['last_activity'] ?? 0;
    $timeout = 1800;
    $remaining = $timeout - (time() - $last_activity);

    return [
        'admin_id' => $_SESSION['admin_id'] ?? null,
        'admin_username' => $_SESSION['admin_username'] ?? null,
        'login_time' => date('Y-m-d H:i:s', $login_time),
        'last_activity' => date('Y-m-d H:i:s', $last_activity),
        'session_expires_in' => max(0, $remaining) . ' seconds',
        'client_fingerprint' => substr($_SESSION['client_fingerprint'] ?? '', 0, 16) . '...'
    ];
}
?>
