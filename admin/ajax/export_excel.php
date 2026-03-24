<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../../php/db_config.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$type = $_GET['type'] ?? '';

if (empty($type)) {
    die('Table not specified');
}

$tables = [
    'contacts' => ['table' => 'contact_submissions', 'filename' => 'Contact_Submissions'],
    'blogs' => ['table' => 'blog_posts', 'filename' => 'Blog_Posts'],
    'courses' => ['table' => 'courses_interest', 'filename' => 'Course_Interests'],
    'assessments' => ['table' => 'assessment_submissions', 'filename' => 'AI_Assessments']
];

if (!isset($tables[$type])) {
    die('Invalid table');
}

$config = $tables[$type];
$tableName = $config['table'];
$filename = $config['filename'] . '_' . date('Y-m-d') . '.xlsx';

// Get data — blog posts need a special query to include tags
if ($type === 'blogs') {
    $result = $conn->query("
        SELECT bp.id, bp.title, bp.slug,
               GROUP_CONCAT(bt.name ORDER BY bt.name SEPARATOR ', ') AS tags,
               bp.excerpt, bp.meta_description, bp.author,
               bp.views, bp.status, bp.published_at, bp.created_at, bp.updated_at
        FROM blog_posts bp
        LEFT JOIN blog_post_tags bpt ON bpt.post_id = bp.id
        LEFT JOIN blog_tags bt        ON bt.id = bpt.tag_id
        GROUP BY bp.id
        ORDER BY bp.id DESC
    ");
} else {
    $result = $conn->query("SELECT * FROM $tableName ORDER BY id DESC");
}

// Create spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Get column names
$columns = [];
$row = $result->fetch_assoc();
if ($row) {
    $columns = array_keys($row);
    
    // Write headers
    $col = 'A';
    foreach ($columns as $column) {
        $sheet->setCellValue($col . '1', ucwords(str_replace('_', ' ', $column)));
        $sheet->getStyle($col . '1')->getFont()->setBold(true);
        $col++;
    }
    
    // Write first row of data
    $rowNum = 2;
    $col = 'A';
    foreach ($row as $value) {
        $sheet->setCellValue($col . $rowNum, $value);
        $col++;
    }
    $rowNum++;
    
    // Write remaining rows
    while ($row = $result->fetch_assoc()) {
        $col = 'A';
        foreach ($row as $value) {
            $sheet->setCellValue($col . $rowNum, $value);
            $col++;
        }
        $rowNum++;
    }
    
    // Auto-size columns
    foreach (range('A', $col) as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }
}

// Log activity
logActivity("Exported $type to Excel", $tableName, 0);

// Send file to browser
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
