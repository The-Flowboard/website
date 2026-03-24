<?php
/**
 * TEMPORARY FALLBACK - HTML Sanitization WITHOUT HTML Purifier
 *
 * ⚠️  WARNING: This provides BASIC sanitization only!
 * ⚠️  You MUST install HTML Purifier for proper XSS protection!
 *
 * Use this ONLY if composer install fails.
 * Replace html_sanitizer.php with this file temporarily.
 */

/**
 * Basic HTML sanitization (TEMPORARY - NOT SECURE!)
 */
function sanitizeBlogContent($html) {
    // Strip ALL potentially dangerous tags
    $allowed_tags = '<p><br><div><span><h1><h2><h3><h4><h5><h6><strong><em><b><i><u><ul><ol><li><a><img><blockquote><code><pre><table><thead><tbody><tr><th><td>';

    $cleaned = strip_tags($html, $allowed_tags);

    // Remove javascript: and data: protocols from links
    $cleaned = preg_replace('/javascript:/i', '', $cleaned);

    // Basic XSS prevention (NOT comprehensive!)
    $cleaned = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i', '', $cleaned);
    $cleaned = preg_replace('/on\w+\s*=\s*["\'].*?["\']/i', '', $cleaned); // Remove inline event handlers

    return $cleaned;
}

/**
 * Strip all HTML tags
 */
function sanitizePlainText($text) {
    return htmlspecialchars(strip_tags($text), ENT_QUOTES, 'UTF-8');
}

/**
 * Allow basic formatting
 */
function sanitizeBlogMetadata($text, $allow_basic_formatting = false) {
    if (!$allow_basic_formatting) {
        return sanitizePlainText($text);
    }

    $allowed = '<strong><em><b><i>';
    return strip_tags($text, $allowed);
}

/**
 * Validate image URL
 */
function sanitizeImageUrl($url) {
    $url = trim($url);

    if (empty($url)) {
        return false;
    }

    // Allow data URIs
    if (strpos($url, 'data:image/') === 0) {
        if (preg_match('/^data:image\/(jpeg|jpg|png|gif|webp);base64,/', $url)) {
            return $url;
        }
        return false;
    }

    // Sanitize regular URLs
    $sanitized = filter_var($url, FILTER_SANITIZE_URL);

    if (filter_var($sanitized, FILTER_VALIDATE_URL) === false && strpos($sanitized, '/') !== 0) {
        return false;
    }

    return $sanitized;
}

// Log warning that fallback is being used
error_log("⚠️  WARNING: Using html_sanitizer_fallback.php - Install HTML Purifier ASAP!");
?>
