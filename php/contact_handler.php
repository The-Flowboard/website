<?php
session_start();
require_once 'db_config.php';
require_once __DIR__ . '/../admin/includes/csrf.php';
require_once __DIR__ . '/webhook_queue.php';
require_once __DIR__ . '/input_validator.php';

header('Content-Type: application/json');

// Validate CSRF token
requireCSRFToken();

// Rate limiting - 5 submissions per hour per IP
require_once __DIR__ . '/rate_limiter.php';
$rateLimiter = new RateLimiter();
$ip_address = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'unknown';

if ($rateLimiter->isRateLimited("contact_form_{$ip_address}", 5, 3600)) {
    $reset_time = $rateLimiter->getResetTime("contact_form_{$ip_address}", 3600);
    $minutes = ceil($reset_time / 60);

    http_response_code(429);
    echo json_encode([
        'success' => false,
        'message' => "Too many submissions. Please try again in {$minutes} minutes."
    ]);
    exit;
}

// Initialize validator
$validator = new InputValidator();

// Set validation rules
$validator->setRule('firstName', 'required|min:2|max:100|alpha_dash', 'First Name')
          ->setRule('lastName', 'required|min:2|max:100|alpha_dash', 'Last Name')
          ->setRule('email', 'required|email|max:255', 'Email')
          ->setRule('phone', 'required|phone|max:50', 'Phone')
          ->setRule('company', 'required|min:2|max:255', 'Company')
          ->setRule('referralSource', 'required|in:Search Engine,YouTube,Instagram,LinkedIn,Referral,Other', 'Referral Source')
          ->setRule('message', 'required|min:10|max:5000', 'Message');

// Validate input data
if (!$validator->validate($_POST)) {
    // Get first error message
    $errors = $validator->getErrors();
    $firstError = reset($errors);

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $firstError,
        'errors' => $errors
    ]);
    exit;
}

// Get validated and sanitized data
$validatedData = $validator->getValidated();
$first_name = $validatedData['firstName'];
$last_name = $validatedData['lastName'];
$email = $validatedData['email'];
$phone = $validatedData['phone'];
$company = $validatedData['company'];
$referral_source = $validatedData['referralSource'];
$message = $validatedData['message'];
$consent = isset($_POST['consent_marketing']) ? 1 : 0;
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '';

// Insert into database
$stmt = $conn->prepare("INSERT INTO contact_submissions (first_name, last_name, email, phone, company, referral_source, message, consent_marketing, consent_timestamp, ip_address, submitted_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, NOW())");
$stmt->bind_param("sssssssis", $first_name, $last_name, $email, $phone, $company, $referral_source, $message, $consent, $ip_address);

if ($stmt->execute()) {
    $submission_id = $conn->insert_id;

    // Prepare webhook payload
    $webhook_data = [
        'id' => $submission_id,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'phone' => $phone,
        'company' => $company,
        'referral_source' => $referral_source,
        'message' => $message,
        'consent_marketing' => $consent,
        'status' => 'new',
        'submitted_at' => date('Y-m-d H:i:s')
    ];

    // Enqueue webhook for reliable delivery (with automatic retry)
    $queue = new WebhookQueue($conn);
    $webhook_id = $queue->enqueue('contact_form', $webhook_data, 5);

    if ($webhook_id) {
        error_log("Contact form #{$submission_id} webhook enqueued (webhook #{$webhook_id})");
    } else {
        error_log("Warning: Failed to enqueue webhook for contact form #{$submission_id}");
    }

    // Return success immediately (webhook will be processed asynchronously)
    echo json_encode(['success' => true, 'message' => 'Thank you for your message. We will get back to you soon!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit. Please try again.']);
}

$stmt->close();
$conn->close();
