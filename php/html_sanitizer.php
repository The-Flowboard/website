<?php
/**
 * HTML Sanitization Utility
 *
 * Uses HTML Purifier to sanitize blog content and prevent XSS attacks
 * while preserving safe HTML formatting
 *
 * @package JMC Website
 * @version 1.0
 * @created January 31, 2026
 */

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Sanitize HTML content for blog posts
 * Removes dangerous tags/attributes while preserving formatting
 *
 * @param string $html Raw HTML content
 * @return string Sanitized HTML content
 */
function sanitizeBlogContent($html) {
    // Create HTML Purifier configuration
    $config = HTMLPurifier_Config::createDefault();

    // Set cache directory (improves performance)
    $config->set('Cache.SerializerPath', sys_get_temp_dir());

    // Allow safe HTML tags for blog content
    $config->set('HTML.Allowed', implode(',', [
        // Document structure
        'p', 'br', 'div', 'span',

        // Headings
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',

        // Text formatting
        'strong', 'b', 'em', 'i', 'u', 'strike', 'del', 'sup', 'sub',

        // Lists
        'ul', 'ol', 'li', 'dl', 'dt', 'dd',

        // Links
        'a[href|title|target|rel]',

        // Images
        'img[src|alt|width|height|title]',

        // Code
        'code', 'pre', 'kbd', 'samp', 'var',

        // Tables
        'table[border|cellpadding|cellspacing]',
        'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
        'caption', 'col', 'colgroup',

        // Quotes
        'blockquote[cite]', 'q[cite]', 'cite',

        // Other semantic elements
        'abbr[title]', 'acronym[title]', 'address', 'hr'
    ]));

    // Allow safe CSS properties
    $config->set('CSS.AllowedProperties', [
        // Colors
        'color', 'background-color',

        // Typography
        'font-family', 'font-size', 'font-weight', 'font-style',
        'text-align', 'text-decoration', 'line-height',

        // Spacing
        'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
        'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',

        // Layout
        'width', 'height', 'max-width', 'max-height',
        'display', 'float', 'clear',

        // Borders
        'border', 'border-width', 'border-style', 'border-color',
        'border-radius'
    ]);

    // Set allowed protocols for links (prevent javascript: protocol)
    $config->set('URI.AllowedSchemes', [
        'http' => true,
        'https' => true,
        'mailto' => true,
        'ftp' => true
    ]);

    // Disable protocols that can execute code
    $config->set('URI.DisableExternalResources', false); // Allow images from external sources
    $config->set('URI.DisableResources', false); // Allow img tags

    // Enforce target="_blank" on external links and add rel="noopener noreferrer"
    $config->set('Attr.AllowedFrameTargets', ['_blank', '_self', '_parent', '_top']);

    // Auto-add noopener/noreferrer to external links for security
    $config->set('HTML.Nofollow', false); // Don't force nofollow on all links

    // Convert newlines to <br> tags
    $config->set('AutoFormat.AutoParagraph', false); // Don't auto-wrap in <p> tags
    $config->set('AutoFormat.Linkify', true); // Convert URLs to links

    // HTML5 doctype
    $config->set('HTML.Doctype', 'HTML 4.01 Transitional');

    // Create purifier instance
    $purifier = new HTMLPurifier($config);

    // Sanitize the HTML
    $sanitized = $purifier->purify($html);

    // Additional post-processing for external links
    $sanitized = addSecurityToExternalLinks($sanitized);

    return $sanitized;
}

/**
 * Add security attributes to external links
 * Adds rel="noopener noreferrer" to links with target="_blank"
 *
 * @param string $html HTML content
 * @return string HTML with secured external links
 */
function addSecurityToExternalLinks($html) {
    // Use DOMDocument to safely parse and modify HTML
    $dom = new DOMDocument();

    // Suppress errors for malformed HTML
    libxml_use_internal_errors(true);

    // Load HTML (with UTF-8 encoding)
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    // Clear errors
    libxml_clear_errors();

    // Find all <a> tags
    $links = $dom->getElementsByTagName('a');

    foreach ($links as $link) {
        $target = $link->getAttribute('target');

        // If link opens in new tab/window
        if ($target === '_blank') {
            // Get existing rel attribute
            $rel = $link->getAttribute('rel');

            // Add noopener and noreferrer if not already present
            $rel_parts = array_filter(explode(' ', $rel));

            if (!in_array('noopener', $rel_parts)) {
                $rel_parts[] = 'noopener';
            }

            if (!in_array('noreferrer', $rel_parts)) {
                $rel_parts[] = 'noreferrer';
            }

            // Set updated rel attribute
            $link->setAttribute('rel', implode(' ', $rel_parts));
        }
    }

    // Return modified HTML
    $output = $dom->saveHTML();

    // Remove the XML encoding declaration we added
    $output = str_replace('<?xml encoding="UTF-8">', '', $output);

    return $output;
}

/**
 * Sanitize plain text input (for titles, excerpts, meta descriptions)
 * Strips all HTML tags and encodes special characters
 *
 * @param string $text Input text
 * @return string Sanitized text
 */
function sanitizePlainText($text) {
    // Strip all HTML tags
    $text = strip_tags($text);

    // Convert special characters to HTML entities
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    // Trim whitespace
    $text = trim($text);

    return $text;
}

/**
 * Sanitize blog metadata (title, slug, excerpt)
 * Allows some formatting but removes dangerous content
 *
 * @param string $text Input text
 * @param bool $allow_basic_formatting Allow <strong>, <em>, <a>
 * @return string Sanitized text
 */
function sanitizeBlogMetadata($text, $allow_basic_formatting = false) {
    if (!$allow_basic_formatting) {
        return sanitizePlainText($text);
    }

    // Create minimal configuration for metadata
    $config = HTMLPurifier_Config::createDefault();
    $config->set('Cache.SerializerPath', sys_get_temp_dir());

    // Only allow very basic formatting
    $config->set('HTML.Allowed', 'strong,em,b,i,a[href|title]');

    // Only allow safe protocols
    $config->set('URI.AllowedSchemes', [
        'http' => true,
        'https' => true
    ]);

    $purifier = new HTMLPurifier($config);
    return $purifier->purify($text);
}

/**
 * Validate and sanitize image URLs
 * Ensures image URLs are safe and from allowed sources
 *
 * @param string $url Image URL
 * @return string|false Sanitized URL or false if invalid
 */
function sanitizeImageUrl($url) {
    // Trim whitespace
    $url = trim($url);

    // Empty URLs are invalid
    if (empty($url)) {
        return false;
    }

    // Check if it's a data URI (base64 encoded image)
    if (strpos($url, 'data:image/') === 0) {
        // Validate data URI format
        if (preg_match('/^data:image\/(jpeg|jpg|png|gif|webp);base64,/', $url)) {
            return $url;
        }
        return false;
    }

    // For regular URLs, validate and sanitize
    $parsed = parse_url($url);

    // URL must have a scheme (http/https) or be a relative path
    if (isset($parsed['scheme'])) {
        // Only allow HTTP and HTTPS
        if (!in_array($parsed['scheme'], ['http', 'https'])) {
            return false;
        }
    }

    // Sanitize the URL
    $sanitized = filter_var($url, FILTER_SANITIZE_URL);

    // Validate the URL
    if (filter_var($sanitized, FILTER_VALIDATE_URL) === false && strpos($sanitized, '/') !== 0) {
        return false;
    }

    return $sanitized;
}
?>
