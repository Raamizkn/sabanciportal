<?php

/**
 * Authentication and Authorization Module
 * Handles user login, session management, and role-based access control
 */

// Configure session cookies to work across subdirectories with enhanced security
if (session_status() === PHP_SESSION_NONE) {
    // Set cookie path to root so cookies work across all subdirectories
    ini_set('session.cookie_path', '/');
    // Use lax SameSite for better compatibility
    ini_set('session.cookie_samesite', 'Lax');
    // Enhanced security: HTTP-only cookies prevent XSS attacks
    ini_set('session.cookie_httponly', '1');
    // Secure cookies in production (HTTPS only)
    // Uncomment in production: ini_set('session.cookie_secure', '1');
    // Regenerate session ID periodically to prevent session fixation
    ini_set('session.use_strict_mode', '1');
    session_start();
    
    // Regenerate session ID every 30 minutes for security
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// User roles
define('ROLE_ADMIN', 'admin');
define('ROLE_COMPANY', 'company');
define('ROLE_STUDENT', 'student');

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 */
function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    return getCurrentUserRole() === $role;
}

/**
 * Check if user has one of the specified roles
 */
function hasAnyRole($roles) {
    return in_array(getCurrentUserRole(), $roles);
}

/**
 * Require authentication - redirect to login if not logged in
 */
function requireAuth() {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
}

/**
 * Require specific role
 * Admins can bypass role checks when impersonating users
 */
function requireRole($role) {
    requireAuth();
    
    // Allow admin to bypass role checks (for impersonation)
    if (hasRole(ROLE_ADMIN)) {
        return;
    }
    
    if (!hasRole($role)) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied. Required role: ' . $role]);
        exit;
    }
}

/**
 * Require any of the specified roles
 */
function requireAnyRole($roles) {
    requireAuth();
    if (!hasAnyRole($roles)) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied. Required roles: ' . implode(', ', $roles)]);
        exit;
    }
}

/**
 * Login user
 */
function login($userId, $role, $userData = []) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_role'] = $role;
    $_SESSION['user_data'] = $userData;
}

/**
 * Logout user
 */
function logout() {
    session_destroy();
}

/**
 * Get session data
 */
function getSessionData() {
    return [
        'user_id' => $_SESSION['user_id'] ?? null,
        'user_role' => $_SESSION['user_role'] ?? null,
        'user_data' => $_SESSION['user_data'] ?? []
    ];
}

