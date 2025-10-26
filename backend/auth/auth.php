<?php

/**
 * Authentication and Authorization Module
 * Handles user login, session management, and role-based access control
 */

session_start();

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
 */
function requireRole($role) {
    requireAuth();
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

