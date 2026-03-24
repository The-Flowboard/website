<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../../php/db_config.php';

header('Content-Type: application/json');

$stats = [
    'contacts' => 0,
    'blogs' => 0,
    'courses' => 0,
    'total_views' => 0
];

// Get contact count
$result = $conn->query("SELECT COUNT(*) as count FROM contact_submissions");
$stats['contacts'] = $result->fetch_assoc()['count'];

// Get blog count
$result = $conn->query("SELECT COUNT(*) as count FROM blog_posts");
$stats['blogs'] = $result->fetch_assoc()['count'];

// Get courses interest count
$result = $conn->query("SELECT COUNT(*) as count FROM courses_interest");
$stats['courses'] = $result->fetch_assoc()['count'];

// Get total blog views
$result = $conn->query("SELECT SUM(views) as total FROM blog_posts");
$stats['total_views'] = $result->fetch_assoc()['total'] ?? 0;

echo json_encode($stats);
