<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // For n8n access
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once 'db_config.php';
require_once 'html_sanitizer.php';

// API Key from environment variables
define('API_KEY', $_ENV['BLOG_API_KEY'] ?? '');

// Save tags for a post (replaces all existing tags).
// Returns silently if the tags tables don't exist yet (migration not run).
function savePostTags($conn, $post_id, $tags) {
    if (empty($tags)) return;
    try {
        // Remove all existing tag associations for this post
        $del = $conn->prepare("DELETE FROM blog_post_tags WHERE post_id = ?");
        if (!$del) return; // table doesn't exist yet
        $del->bind_param("i", $post_id);
        $del->execute();

        foreach ($tags as $raw_name) {
            $name = trim($raw_name);
            if ($name === '') continue;
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));

            // Upsert tag
            $ins = $conn->prepare("INSERT IGNORE INTO blog_tags (name, slug) VALUES (?, ?)");
            if (!$ins) continue;
            $ins->bind_param("ss", $name, $slug);
            $ins->execute();

            // Get tag id
            $sel = $conn->prepare("SELECT id FROM blog_tags WHERE slug = ?");
            if (!$sel) continue;
            $sel->bind_param("s", $slug);
            $sel->execute();
            $res = $sel->get_result()->fetch_assoc();
            if (!$res) continue;
            $tag_id = $res['id'];

            // Link post ↔ tag
            $link = $conn->prepare("INSERT IGNORE INTO blog_post_tags (post_id, tag_id) VALUES (?, ?)");
            if (!$link) continue;
            $link->bind_param("ii", $post_id, $tag_id);
            $link->execute();
        }
    } catch (Exception $e) {
        // Tags tables may not exist yet — log and continue, post is already saved
        error_log("savePostTags (non-fatal): " . $e->getMessage());
    }
}

// Verify API key
function verifyAPIKey() {
    $headers = getallheaders();
    $apiKey = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if ($apiKey !== 'Bearer ' . API_KEY) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    
    switch ($method) {
        case 'GET':
            // List blog posts, get single post, or list all tags
            if ($action === 'list') {
                // Unauthenticated callers may only read published posts
                $status = 'published';
                $limit = intval($_GET['limit'] ?? 10);
                $offset = intval($_GET['offset'] ?? 0);

                $stmt = $conn->prepare("
                    SELECT bp.*,
                           GROUP_CONCAT(bt.name ORDER BY bt.name SEPARATOR ',') AS tags_csv,
                           GROUP_CONCAT(bt.slug ORDER BY bt.name SEPARATOR ',') AS tag_slugs_csv
                    FROM blog_posts bp
                    LEFT JOIN blog_post_tags bpt ON bpt.post_id = bp.id
                    LEFT JOIN blog_tags bt        ON bt.id = bpt.tag_id
                    WHERE bp.status = ?
                    GROUP BY bp.id
                    ORDER BY bp.published_at DESC
                    LIMIT ? OFFSET ?
                ");
                $stmt->bind_param("sii", $status, $limit, $offset);
                $stmt->execute();
                $result = $stmt->get_result();

                $posts = [];
                while ($row = $result->fetch_assoc()) {
                    $row['tags']      = $row['tags_csv']       ? explode(',', $row['tags_csv'])      : [];
                    $row['tag_slugs'] = $row['tag_slugs_csv']  ? explode(',', $row['tag_slugs_csv']) : [];
                    unset($row['tags_csv'], $row['tag_slugs_csv']);
                    $posts[] = $row;
                }

                echo json_encode(['success' => true, 'posts' => $posts]);

            } elseif ($action === 'tags') {
                // Return all tags for public filter UI
                $result = $conn->query("SELECT name, slug FROM blog_tags ORDER BY name ASC");
                $tags = [];
                while ($row = $result->fetch_assoc()) {
                    $tags[] = $row;
                }
                echo json_encode(['success' => true, 'tags' => $tags]);

            } elseif ($action === 'get' && isset($_GET['slug'])) {
                $slug = $_GET['slug'];

                $stmt = $conn->prepare("
                    SELECT bp.*,
                           GROUP_CONCAT(bt.name ORDER BY bt.name SEPARATOR ',') AS tags_csv,
                           GROUP_CONCAT(bt.slug ORDER BY bt.name SEPARATOR ',') AS tag_slugs_csv
                    FROM blog_posts bp
                    LEFT JOIN blog_post_tags bpt ON bpt.post_id = bp.id
                    LEFT JOIN blog_tags bt        ON bt.id = bpt.tag_id
                    WHERE bp.slug = ? AND bp.status = 'published'
                    GROUP BY bp.id
                ");
                $stmt->bind_param("s", $slug);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    $row['tags']      = $row['tags_csv']      ? explode(',', $row['tags_csv'])      : [];
                    $row['tag_slugs'] = $row['tag_slugs_csv'] ? explode(',', $row['tag_slugs_csv']) : [];
                    unset($row['tags_csv'], $row['tag_slugs_csv']);

                    // Increment view count
                    $update = $conn->prepare("UPDATE blog_posts SET views = views + 1 WHERE id = ?");
                    $update->bind_param("i", $row['id']);
                    $update->execute();

                    echo json_encode(['success' => true, 'post' => $row]);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Post not found']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
            }
            break;
            
        case 'POST':
            // Create new blog post (requires API key)
            verifyAPIKey();

            $data = json_decode(file_get_contents('php://input'), true);

            $required = ['slug', 'title', 'content'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    http_response_code(400);
                    echo json_encode(['error' => "Missing required field: $field"]);
                    exit;
                }
            }

            // Sanitize all input fields
            $slug = sanitizePlainText($data['slug']);
            $title = sanitizePlainText($data['title']);
            $excerpt = isset($data['excerpt']) ? sanitizeBlogMetadata($data['excerpt'], true) : '';
            $content = sanitizeBlogContent($data['content']);
            $author = isset($data['author']) ? sanitizePlainText($data['author']) : 'Joshi Management Consultancy';
            $meta_description = isset($data['meta_description']) ? sanitizePlainText($data['meta_description']) : '';
            $meta_keywords = isset($data['meta_keywords']) ? sanitizePlainText($data['meta_keywords']) : '';
            // Accept tags as array, or fall back to legacy category string
            if (isset($data['tags']) && is_array($data['tags'])) {
                $tags = $data['tags'];
            } elseif (isset($data['tags']) && is_string($data['tags']) && $data['tags'] !== '') {
                $tags = array_filter(array_map('trim', explode(',', $data['tags'])));
            } elseif (isset($data['category']) && $data['category'] !== '') {
                $tags = [sanitizePlainText($data['category'])];
            } else {
                $tags = [];
            }

            // Sanitize featured image URL
            $featured_image = null;
            if (isset($data['featured_image']) && !empty($data['featured_image'])) {
                $featured_image = sanitizeImageUrl($data['featured_image']);
                if ($featured_image === false) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid featured image URL']);
                    exit;
                }
            }

            // Validate status
            $allowed_statuses = ['draft', 'published'];
            $status = in_array($data['status'] ?? '', $allowed_statuses) ? $data['status'] : 'published';

            // Sanitize published_at (must be valid datetime)
            $published_at = isset($data['published_at']) ? $data['published_at'] : date('Y-m-d H:i:s');
            if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $published_at)) {
                $published_at = date('Y-m-d H:i:s');
            }

            // Check if slug already exists (upsert: update instead of failing)
            $check = $conn->prepare("SELECT id FROM blog_posts WHERE slug = ?");
            $check->bind_param("s", $slug);
            $check->execute();
            $existing = $check->get_result()->fetch_assoc();

            if ($existing) {
                // Slug exists — update the post instead
                $post_id = $existing['id'];
                $upd = $conn->prepare("UPDATE blog_posts SET title=?, excerpt=?, content=?, author=?, featured_image=?, meta_description=?, meta_keywords=?, status=?, updated_at=NOW() WHERE id=?");
                $upd->bind_param("ssssssssi", $title, $excerpt, $content, $author, $featured_image, $meta_description, $meta_keywords, $status, $post_id);
                if (!$upd->execute()) {
                    throw new Exception("Failed to update existing post: " . $upd->error);
                }
                savePostTags($conn, $post_id, $tags);
                echo json_encode(['success' => true, 'id' => $post_id, 'message' => 'Post updated (slug already existed)']);
            } else {
                $stmt = $conn->prepare("INSERT INTO blog_posts (slug, title, excerpt, content, author, featured_image, meta_description, meta_keywords, status, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssssss", $slug, $title, $excerpt, $content, $author, $featured_image, $meta_description, $meta_keywords, $status, $published_at);
                if ($stmt->execute()) {
                    $post_id = $conn->insert_id;
                    savePostTags($conn, $post_id, $tags);
                    echo json_encode(['success' => true, 'id' => $post_id, 'message' => 'Post created']);
                } else {
                    throw new Exception("Failed to create post: " . $stmt->error);
                }
            }
            break;
            
        case 'PUT':
            // Update blog post (requires API key)
            verifyAPIKey();

            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing post ID']);
                exit;
            }

            $id = intval($_GET['id']);
            $data = json_decode(file_get_contents('php://input'), true);

            // Build update query dynamically with sanitized values
            $updates = [];
            $types = '';
            $values = [];

            // Sanitize each field based on its type
            if (isset($data['title'])) {
                $updates[] = "title = ?";
                $types .= 's';
                $values[] = sanitizePlainText($data['title']);
            }

            if (isset($data['excerpt'])) {
                $updates[] = "excerpt = ?";
                $types .= 's';
                $values[] = sanitizeBlogMetadata($data['excerpt'], true);
            }

            if (isset($data['content'])) {
                $updates[] = "content = ?";
                $types .= 's';
                $values[] = sanitizeBlogContent($data['content']);
            }

            if (isset($data['featured_image'])) {
                $sanitized_image = sanitizeImageUrl($data['featured_image']);
                if ($sanitized_image === false && !empty($data['featured_image'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid featured image URL']);
                    exit;
                }
                $updates[] = "featured_image = ?";
                $types .= 's';
                $values[] = $sanitized_image ?: null;
            }

            if (isset($data['meta_description'])) {
                $updates[] = "meta_description = ?";
                $types .= 's';
                $values[] = sanitizePlainText($data['meta_description']);
            }

            if (isset($data['meta_keywords'])) {
                $updates[] = "meta_keywords = ?";
                $types .= 's';
                $values[] = sanitizePlainText($data['meta_keywords']);
            }

            if (isset($data['status'])) {
                $allowed_statuses = ['draft', 'published'];
                $status = in_array($data['status'], $allowed_statuses) ? $data['status'] : 'published';
                $updates[] = "status = ?";
                $types .= 's';
                $values[] = $status;
            }

            if (empty($updates)) {
                http_response_code(400);
                echo json_encode(['error' => 'No fields to update']);
                exit;
            }

            $types .= 'i';
            $values[] = $id;

            $sql = "UPDATE blog_posts SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$values);

            if ($stmt->execute()) {
                // Sync tags if provided (accept array, CSV string, or legacy category)
                if (isset($data['tags']) && is_array($data['tags'])) {
                    $put_tags = $data['tags'];
                } elseif (isset($data['tags']) && is_string($data['tags']) && $data['tags'] !== '') {
                    $put_tags = array_filter(array_map('trim', explode(',', $data['tags'])));
                } elseif (isset($data['category']) && $data['category'] !== '') {
                    $put_tags = [sanitizePlainText($data['category'])];
                } else {
                    $put_tags = null;
                }
                if ($put_tags !== null) {
                    savePostTags($conn, $id, $put_tags);
                }
                echo json_encode(['success' => true, 'message' => 'Post updated']);
            } else {
                throw new Exception("Failed to update post");
            }
            break;
            
        case 'DELETE':
            // Delete blog post (requires API key)
            verifyAPIKey();
            
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing post ID']);
                exit;
            }
            
            $id = intval($_GET['id']);
            $stmt = $conn->prepare("DELETE FROM blog_posts WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Post deleted']);
            } else {
                throw new Exception("Failed to delete post");
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
    
    $conn->close();
    
} catch (Exception $e) {
    error_log("Blog API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
