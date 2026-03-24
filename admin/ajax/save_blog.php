<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
requireLogin();

// Require CSRF token for POST operations
requireCSRFToken();

require_once '../../php/db_config.php';
require_once '../../php/html_sanitizer.php';

header('Content-Type: application/json');

// Get raw input
$id = $_POST['id'] ?? '';
$title_raw = $_POST['title'] ?? '';
$slug_raw = $_POST['slug'] ?? '';
$tags_raw = $_POST['tags'] ?? ''; // comma-separated tag names
$excerpt_raw = $_POST['excerpt'] ?? '';
$content_raw = $_POST['content'] ?? '';
$featured_image_raw = $_POST['featured_image'] ?? '';
$meta_description_raw = $_POST['meta_description'] ?? '';
$status_raw = $_POST['status'] ?? 'draft';

// Validate required fields before sanitization
if (empty($title_raw) || empty($slug_raw) || empty($content_raw)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Sanitize all inputs
$title = sanitizePlainText($title_raw);
$slug = sanitizePlainText($slug_raw);
// Parse comma-separated tags into array of sanitized names
$tags = array_values(array_filter(array_map('trim', explode(',', $tags_raw))));
$excerpt = sanitizeBlogMetadata($excerpt_raw, true); // Allow basic formatting in excerpt
$content = sanitizeBlogContent($content_raw); // Full HTML sanitization for content
$meta_description = sanitizePlainText($meta_description_raw);

// Sanitize featured image URL
if (!empty($featured_image_raw)) {
    $featured_image = sanitizeImageUrl($featured_image_raw);
    if ($featured_image === false) {
        echo json_encode(['success' => false, 'message' => 'Invalid image URL']);
        exit;
    }
} else {
    $featured_image = '';
}

// Validate status
$allowed_statuses = ['draft', 'published'];
$status = in_array($status_raw, $allowed_statuses) ? $status_raw : 'draft';

// Additional validation
if (empty($title) || empty($slug) || empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input after sanitization']);
    exit;
}

if (empty($id)) {
    // Create new blog post
    $stmt = $conn->prepare("INSERT INTO blog_posts (title, slug, excerpt, content, featured_image, meta_description, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->bind_param("sssssss", $title, $slug, $excerpt, $content, $featured_image, $meta_description, $status);
} else {
    // Update existing blog post
    $stmt = $conn->prepare("UPDATE blog_posts SET title=?, slug=?, excerpt=?, content=?, featured_image=?, meta_description=?, status=?, updated_at=NOW() WHERE id=?");
    $stmt->bind_param("sssssssi", $title, $slug, $excerpt, $content, $featured_image, $meta_description, $status, $id);
}

if ($stmt->execute()) {
    $blog_id = $id ?: $conn->insert_id;

    // Sync tags via pivot tables
    // Remove all existing tags for this post
    $del = $conn->prepare("DELETE FROM blog_post_tags WHERE post_id = ?");
    $del->bind_param("i", $blog_id);
    $del->execute();

    foreach ($tags as $tag_name) {
        if ($tag_name === '') continue;
        $tag_slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $tag_name));

        // Upsert tag
        $ins_tag = $conn->prepare("INSERT IGNORE INTO blog_tags (name, slug) VALUES (?, ?)");
        $ins_tag->bind_param("ss", $tag_name, $tag_slug);
        $ins_tag->execute();

        // Get tag id
        $sel_tag = $conn->prepare("SELECT id FROM blog_tags WHERE slug = ?");
        $sel_tag->bind_param("s", $tag_slug);
        $sel_tag->execute();
        $tag_row = $sel_tag->get_result()->fetch_assoc();
        if (!$tag_row) continue;
        $tag_id = $tag_row['id'];

        // Link post ↔ tag
        $ins_link = $conn->prepare("INSERT IGNORE INTO blog_post_tags (post_id, tag_id) VALUES (?, ?)");
        $ins_link->bind_param("ii", $blog_id, $tag_id);
        $ins_link->execute();
    }

    logActivity($id ? "Updated blog post" : "Created blog post", 'blog_posts', $blog_id);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save blog post']);
}
