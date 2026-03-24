<?php
session_start();
header('Content-Type: application/json');

require_once 'db_config.php';
require_once __DIR__ . '/../admin/includes/csrf.php';
require_once __DIR__ . '/rate_limiter.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireCSRFToken();

    // Rate limiting - 5 submissions per hour per IP
    $rateLimiter = new RateLimiter();
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'unknown';
    if ($rateLimiter->isRateLimited("courses_interest_{$ip_address}", 5, 3600)) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Too many submissions. Please try again later.']);
        exit;
    }
    
    $email = trim($_POST['email'] ?? '');
    
    // Validation
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please enter a valid email address'
        ]);
        exit;
    }
    
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    try {
        $conn = getDBConnection();
        
        // Check if email already exists
        $check_stmt = $conn->prepare("SELECT id FROM courses_interest WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'This email is already registered for updates!'
            ]);
            exit;
        }
        
        // Insert new email
        $stmt = $conn->prepare("INSERT INTO courses_interest (email, ip_address) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $ip_address);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Thank you! We\'ll notify you when courses are available.'
            ]);
        } else {
            throw new Exception("Database error");
        }
        
        $stmt->close();
        $conn->close();
        
    } catch (Exception $e) {
        error_log("Courses interest error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred. Please try again later.'
        ]);
    }
    
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?>
