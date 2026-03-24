<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
requireLogin();

// Require CSRF token for upload operations
requireCSRFToken();

header('Content-Type: application/json');

// Configuration
define('UPLOAD_DIR', '../../images/blog/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

// Validation Functions
function validateImage($file) {
    $errors = [];

    // Check if file exists
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'No file uploaded';
        return $errors;
    }

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Upload failed with error code: ' . $file['error'];
        return $errors;
    }

    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        $errors[] = 'File size exceeds 5MB limit';
    }

    // Check MIME type (server-side)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, ALLOWED_TYPES)) {
        $errors[] = 'Invalid file type. Only JPG, PNG, and WebP allowed';
    }

    // Check file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        $errors[] = 'Invalid file extension';
    }

    // Additional security: Check for double extensions
    if (substr_count($file['name'], '.') > 1) {
        $errors[] = 'Invalid filename (multiple extensions detected)';
    }

    return $errors;
}

// Generate unique filename
function generateUniqueFilename($originalName) {
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $timestamp = time();
    $random = bin2hex(random_bytes(8));
    return "blog_{$timestamp}_{$random}.{$extension}";
}

// Convert image to WebP format
function convertToWebP($sourcePath, $mimeType) {
    // Check if GD library is available
    if (!extension_loaded('gd')) {
        error_log('GD extension not loaded - WebP conversion skipped');
        return false;
    }

    // Create image resource based on type
    switch ($mimeType) {
        case 'image/jpeg':
            $image = @imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $image = @imagecreatefrompng($sourcePath);
            // Preserve transparency for PNG
            if ($image) {
                imagesavealpha($image, true);
            }
            break;
        case 'image/webp':
            // Already WebP, no conversion needed
            return $sourcePath;
        default:
            return false;
    }

    if (!$image) {
        error_log('Failed to create image resource for WebP conversion');
        return false;
    }

    // Generate WebP filename
    $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $sourcePath);

    // Convert to WebP with 85% quality (good balance of quality and size)
    $result = imagewebp($image, $webpPath, 85);
    imagedestroy($image);

    if ($result) {
        // Set proper permissions
        chmod($webpPath, 0644);
        error_log("WebP version created: {$webpPath}");
        return $webpPath;
    }

    error_log('Failed to create WebP version');
    return false;
}

// Main Upload Logic
try {
    if (!isset($_FILES['image'])) {
        throw new Exception('No image file received');
    }

    $file = $_FILES['image'];

    // Validate image
    $validationErrors = validateImage($file);
    if (!empty($validationErrors)) {
        echo json_encode([
            'success' => false,
            'message' => implode(', ', $validationErrors)
        ]);
        exit;
    }

    // Ensure upload directory exists
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    // Generate unique filename
    $newFilename = generateUniqueFilename($file['name']);
    $targetPath = UPLOAD_DIR . $newFilename;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Get MIME type for WebP conversion
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $targetPath);
        finfo_close($finfo);

        // Automatically convert to WebP
        $webpPath = convertToWebP($targetPath, $mimeType);
        $webpFilename = $webpPath ? basename($webpPath) : null;
        $webpRelativePath = $webpPath ? '/images/blog/' . $webpFilename : null;

        // Log activity
        $relativePath = '/images/blog/' . $newFilename;
        $logMessage = "Uploaded: {$newFilename}";
        if ($webpPath) {
            $logMessage .= " (WebP: {$webpFilename})";
        }
        logActivity('upload_image', 'blog_posts', null, $logMessage);

        // Return success with both file paths
        $response = [
            'success' => true,
            'path' => $relativePath,
            'filename' => $newFilename,
            'message' => 'Image uploaded successfully'
        ];

        // Add WebP info if conversion succeeded
        if ($webpPath) {
            $response['webp_path'] = $webpRelativePath;
            $response['webp_filename'] = $webpFilename;
            $response['message'] .= ' (WebP version created)';
        }

        echo json_encode($response);
    } else {
        throw new Exception('Failed to move uploaded file');
    }

} catch (Exception $e) {
    error_log("Image upload error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
