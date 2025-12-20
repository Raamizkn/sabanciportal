<?php
/**
 * Security Utilities for XSS Protection and Input Sanitization
 */

/**
 * Sanitize HTML content for cover letters
 * Allows safe HTML tags from Quill.js editor (bold, italic, underline, headers, lists, links)
 * Strips dangerous tags and attributes
 * 
 * @param string $html The HTML content to sanitize
 * @return string Sanitized HTML
 */
function sanitize_cover_letter_html($html) {
    if (empty($html)) {
        return '';
    }
    
    // Allowed HTML tags for rich text editor
    $allowed_tags = '<p><br><strong><b><em><i><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><blockquote>';
    
    // Strip all tags except allowed ones
    $html = strip_tags($html, $allowed_tags);
    
    // Remove dangerous attributes from <a> tags, keep only href
    $html = preg_replace_callback('/<a\s+([^>]*)>/i', function($matches) {
        $attrs = $matches[1];
        // Extract href attribute
        if (preg_match('/href=["\']([^"\']*)["\']/i', $attrs, $hrefMatch)) {
            $href = $hrefMatch[1];
            // Only allow http, https, and mailto protocols
            if (preg_match('/^(https?|mailto):/i', $href)) {
                return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">';
            }
        }
        return '<a>';
    }, $html);
    
    // Remove any remaining dangerous attributes from all tags
    $html = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html); // Remove event handlers
    $html = preg_replace('/\s*javascript:/i', '', $html); // Remove javascript: protocol
    
    return trim($html);
}

/**
 * Escape HTML for safe display
 * Use this when displaying user-generated content that shouldn't contain HTML
 * 
 * @param string $text The text to escape
 * @return string Escaped HTML
 */
function escape_html($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Sanitize user input (for non-HTML fields)
 * 
 * @param string $input The input to sanitize
 * @return string Sanitized input
 */
function sanitize_input($input) {
    if (is_array($input)) {
        return array_map('sanitize_input', $input);
    }
    return htmlspecialchars(trim($input ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 * 
 * @param string $email Email to validate
 * @return bool True if valid
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate CSRF token
 * 
 * @return string CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * 
 * @param string $token Token to verify
 * @return bool True if valid
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

