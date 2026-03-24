<?php
// Load environment variables
require_once __DIR__ . '/../vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
} catch (Exception $e) {
    error_log("Failed to load .env file: " . $e->getMessage());
    die("System configuration error. Please contact support.");
}

// Set timezone to Eastern Time (America/Toronto)
date_default_timezone_set('America/Toronto');

// Database configuration from environment
$db_host = $_ENV['DB_HOST'] ?? 'localhost';
$db_name = $_ENV['DB_NAME'] ?? '';
$db_user = $_ENV['DB_USER'] ?? '';
$db_pass = $_ENV['DB_PASS'] ?? '';

// Validate configuration
if (empty($db_name) || empty($db_user) || empty($db_pass)) {
    error_log("Database configuration incomplete");
    die("System configuration error. Please contact support.");
}

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("System error. Please try again later.");
}

// Set charset
$conn->set_charset("utf8mb4");

// Set MySQL session timezone to Eastern Time
// Calculate current offset based on PHP timezone (handles DST automatically)
$offset = date('P'); // Returns +HH:MM or -HH:MM format
$conn->query("SET time_zone = '$offset'");
?>
