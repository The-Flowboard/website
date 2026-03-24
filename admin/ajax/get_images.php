<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// Check authentication
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Connect to database to check blog associations
    require_once __DIR__ . '/../../php/db_config.php';

    $imagesDir = __DIR__ . '/../../images/blog/';
    $baseUrl = 'https://joshimc.com/images/blog/';

    // Check if directory exists
    if (!is_dir($imagesDir)) {
        throw new Exception('Images directory not found');
    }

    // Get all files in directory
    $files = scandir($imagesDir);
    $images = [];

    foreach ($files as $file) {
        // Skip . and .. and .htaccess
        if ($file === '.' || $file === '..' || $file === '.htaccess') {
            continue;
        }

        $filePath = $imagesDir . $file;

        // Only include actual files (not directories)
        if (!is_file($filePath)) {
            continue;
        }

        // Get file info
        $fileSize = filesize($filePath);
        $uploadTime = filemtime($filePath);

        // Get image dimensions if possible
        $dimensions = null;
        $mimeType = mime_content_type($filePath);

        if (strpos($mimeType, 'image/') === 0) {
            $imageInfo = @getimagesize($filePath);
            if ($imageInfo !== false) {
                $dimensions = [
                    'width' => $imageInfo[0],
                    'height' => $imageInfo[1]
                ];
            }
        }

        // Format file size
        $fileSizeFormatted = formatFileSize($fileSize);

        // Check which blogs use this image
        $imagePath = '/images/blog/' . $file;
        $stmt = $conn->prepare("SELECT id, title, slug FROM blog_posts WHERE featured_image = ?");
        $stmt->bind_param('s', $imagePath);
        $stmt->execute();
        $result = $stmt->get_result();

        $usedInBlogs = [];
        while ($row = $result->fetch_assoc()) {
            $usedInBlogs[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'slug' => $row['slug']
            ];
        }
        $stmt->close();

        $images[] = [
            'filename' => $file,
            'url' => $baseUrl . $file,
            'path' => '/images/blog/' . $file,
            'size' => $fileSize,
            'sizeFormatted' => $fileSizeFormatted,
            'uploadedAt' => date('Y-m-d H:i:s', $uploadTime),
            'uploadedAtFormatted' => date('M j, Y g:i A', $uploadTime),
            'mimeType' => $mimeType,
            'dimensions' => $dimensions,
            'isImage' => strpos($mimeType, 'image/') === 0,
            'usedInBlogs' => $usedInBlogs,
            'usedCount' => count($usedInBlogs)
        ];
    }

    // Sort by upload time, newest first
    usort($images, function($a, $b) {
        return $b['uploadedAt'] <=> $a['uploadedAt'];
    });

    echo json_encode([
        'success' => true,
        'images' => $images,
        'count' => count($images)
    ]);

} catch (Exception $e) {
    error_log('Get images error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve images: ' . $e->getMessage()
    ]);
}

function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
