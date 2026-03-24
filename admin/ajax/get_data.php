<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../../php/db_config.php';

header('Content-Type: application/json');

$type = $_GET['type'] ?? '';

$data = [];

switch($type) {
    case 'contacts':
        $result = $conn->query("SELECT * FROM contact_submissions ORDER BY submitted_at DESC");
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        break;
        
    case 'blogs':
        $result = $conn->query("
            SELECT bp.*,
                   GROUP_CONCAT(bt.name ORDER BY bt.name SEPARATOR ',') AS tags_csv
            FROM blog_posts bp
            LEFT JOIN blog_post_tags bpt ON bpt.post_id = bp.id
            LEFT JOIN blog_tags bt        ON bt.id = bpt.tag_id
            GROUP BY bp.id
            ORDER BY bp.created_at DESC
        ");
        while($row = $result->fetch_assoc()) {
            $row['tags'] = $row['tags_csv'] ? explode(',', $row['tags_csv']) : [];
            unset($row['tags_csv']);
            $data[] = $row;
        }
        break;
        
    case 'courses':
        $result = $conn->query("SELECT * FROM courses_interest ORDER BY submitted_at DESC");
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        break;

    case 'assessments':
        $result = $conn->query("SELECT id, name, email, phone, company_name, industry, company_size, readiness_score, readiness_level, submitted_at FROM assessment_submissions ORDER BY submitted_at DESC");
        if (!$result) {
            error_log("Assessment query failed: " . $conn->error);
            echo json_encode(['error' => 'Query failed']);
            exit;
        }
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        break;
}

echo json_encode($data);
