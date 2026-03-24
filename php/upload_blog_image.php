<?php
/**
 * Blog Image Upload API
 * Allows uploading images via API key authentication for n8n automation
 * Accepts both multipart/form-data and base64 encoded images
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'db_config.php';

// API Key from environment variables (same as blog_api.php)
define('API_KEY', $_ENV['BLOG_API_KEY'] ?? '');

// Configuration
define('UPLOAD_DIR', '../images/blog/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

/**
 * Verify API Key
 */
function verifyAPIKey() {
    $headers = getallheaders();
    $apiKey = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if ($apiKey !== 'Bearer ' . API_KEY) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized - Invalid API key'
        ]);
        exit;
    }
}

/**
 * Generate unique filename
 */
function generateUniqueFilename($extension) {
    $timestamp = time();
    $random = bin2hex(random_bytes(8));
    return "blog_{$timestamp}_{$random}.{$extension}";
}

/**
 * Validate MIME type
 */
function validateMimeType($filepath) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filepath);
    finfo_close($finfo);

    return in_array($mimeType, ALLOWED_TYPES);
}

/**
 * Get file extension from MIME type
 */
function getExtensionFromMime($mimeType) {
    $mimeMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];
    return $mimeMap[$mimeType] ?? null;
}

/**
 * Convert image to WebP format
 */
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

/**
 * Main upload logic
 */
try {
    // Verify API key
    verifyAPIKey();

    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed. Use POST.'
        ]);
        exit;
    }

    // Ensure upload directory exists
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $uploadedFilePath = null;
    $extension = null;

    // Method 1: Multipart form data (traditional file upload)
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['image'];

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload failed with error code: ' . $file['error']);
        }

        // Check file size
        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception('File size exceeds 5MB limit');
        }

        // Validate MIME type
        if (!validateMimeType($file['tmp_name'])) {
            throw new Exception('Invalid file type. Only JPG, PNG, and WebP are allowed');
        }

        // Get extension from uploaded file
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ALLOWED_EXTENSIONS)) {
            throw new Exception('Invalid file extension');
        }

        // Check for double extensions
        if (substr_count($file['name'], '.') > 1) {
            throw new Exception('Invalid filename (multiple extensions detected)');
        }

        $uploadedFilePath = $file['tmp_name'];
    }
    // Method 2: Base64 encoded image (for LLM-generated images)
    elseif (!empty($_POST['image_base64']) || !empty(file_get_contents('php://input'))) {
        // Try to get base64 from POST or raw input
        $rawInput = file_get_contents('php://input');
        $json = json_decode($rawInput, true);

        // Log incoming request for debugging
        error_log("Blog image upload: Received request. Raw input first 200 chars: " . substr($rawInput, 0, 200));
        error_log("Blog image upload: JSON decode " . (json_last_error() === JSON_ERROR_NONE ? "successful" : "failed: " . json_last_error_msg()));

        $base64Data = $_POST['image_base64'] ?? $json['image_base64'] ?? null;

        if (!$base64Data) {
            error_log("Blog image upload: No base64 data found in request");
            throw new Exception('No image data provided. Use "image" file field or "image_base64" parameter');
        }

        // Log the data URI prefix
        error_log("Blog image upload: Base64 data prefix: " . substr($base64Data, 0, 50));

        // Remove data URI scheme if present (e.g., "data:image/png;base64,")
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $matches)) {
            $extension = $matches[1];
            error_log("Blog image upload: Extension extracted from data URI: '{$extension}'");
            $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
        } else {
            // Default to jpg if no format specified
            $extension = $_POST['extension'] ?? $json['extension'] ?? 'jpg';
            error_log("Blog image upload: Extension from JSON field: '{$extension}'");
        }

        // Normalize extension: trim whitespace and convert to lowercase
        $extensionBefore = $extension;
        $extension = strtolower(trim($extension));
        error_log("Blog image upload: Extension before normalization: '{$extensionBefore}', after: '{$extension}'");

        // Validate extension
        if (!in_array($extension, ALLOWED_EXTENSIONS)) {
            error_log("Blog image upload: Invalid extension received: '{$extension}' (length: " . strlen($extension) . ", allowed: " . implode(', ', ALLOWED_EXTENSIONS) . ")");
            error_log("Blog image upload: Extension hex dump: " . bin2hex($extension));
            throw new Exception('Invalid image extension. Use jpg, jpeg, png, or webp');
        }

        // Decode base64
        $imageData = base64_decode($base64Data, true);
        if ($imageData === false) {
            throw new Exception('Invalid base64 encoding');
        }

        // Check size
        if (strlen($imageData) > MAX_FILE_SIZE) {
            throw new Exception('Image size exceeds 5MB limit');
        }

        // Create temporary file for validation
        $tempFile = tempnam(sys_get_temp_dir(), 'blog_upload_');
        file_put_contents($tempFile, $imageData);

        // Validate MIME type
        if (!validateMimeType($tempFile)) {
            unlink($tempFile);
            throw new Exception('Invalid image format. Only JPG, PNG, and WebP are allowed');
        }

        $uploadedFilePath = $tempFile;
    }
    else {
        throw new Exception('No image provided. Use multipart form-data with "image" field or JSON with "image_base64" field');
    }

    // Generate unique filename and move file
    $newFilename = generateUniqueFilename($extension);
    $targetPath = UPLOAD_DIR . $newFilename;

    if (!rename($uploadedFilePath, $targetPath)) {
        throw new Exception('Failed to save image');
    }

    // Set proper permissions
    chmod($targetPath, 0644);

    // Get MIME type for WebP conversion
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $targetPath);
    finfo_close($finfo);

    // Automatically convert to WebP
    $webpPath = convertToWebP($targetPath, $mimeType);
    $webpFilename = $webpPath ? basename($webpPath) : null;
    $webpRelativePath = $webpPath ? '/images/blog/' . $webpFilename : null;

    $relativePath = '/images/blog/' . $newFilename;

    // Log success
    $logMessage = "Blog image uploaded via API: {$newFilename}";
    if ($webpPath) {
        $logMessage .= " (WebP: {$webpFilename})";
    }
    error_log($logMessage);

    // Return success response with both paths
    $response = [
        'success' => true,
        'path' => $relativePath,
        'filename' => $newFilename,
        'url' => 'https://joshimc.com' . $relativePath,
        'message' => 'Image uploaded successfully'
    ];

    // Add WebP info if conversion succeeded
    if ($webpPath) {
        $response['webp_path'] = $webpRelativePath;
        $response['webp_filename'] = $webpFilename;
        $response['webp_url'] = 'https://joshimc.com' . $webpRelativePath;
        $response['message'] .= ' (WebP version created)';
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Blog image upload API error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
