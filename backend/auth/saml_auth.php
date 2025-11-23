<?php

/**
 * SAML Authentication Handler for Internship Portal
 * Handles SimpleSAMLPHP authentication and user provisioning
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

// Path to SimpleSAMLPHP autoloader
define('SIMPLESAMLPHP_AUTOLOADER', dirname(dirname(__DIR__)) . '/simplesamlphp-2.4.1/vendor/autoload.php');

/**
 * Check if SAML is properly configured
 */
function isSAMLAvailable() {
    return file_exists(SIMPLESAMLPHP_AUTOLOADER);
}

/**
 * Initialize SAML authentication
 */
function initializeSAML() {
    if (!isSAMLAvailable()) {
        throw new Exception('SimpleSAMLPHP is not installed or configured');
    }
    
    require_once SIMPLESAMLPHP_AUTOLOADER;
    
    return new \SimpleSAML\Auth\Simple('default-sp');
}

/**
 * Require SAML authentication
 * Redirects to IdP if not authenticated
 */
function requireSAMLAuth() {
    $auth = initializeSAML();
    $auth->requireAuth();
    
    // Cleanup SimpleSAMLPHP session to use our PHP session
    \SimpleSAML\Session::getSessionFromRequest()->cleanup();
    
    return $auth;
}

/**
 * Check if user is authenticated via SAML
 */
function isSAMLAuthenticated() {
    try {
        $auth = initializeSAML();
        return $auth->isAuthenticated();
    } catch (Exception $e) {
        error_log('SAML check failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get SAML attributes for authenticated user
 */
function getSAMLAttributes() {
    try {
        $auth = initializeSAML();
        if (!$auth->isAuthenticated()) {
            return null;
        }
        return $auth->getAttributes();
    } catch (Exception $e) {
        error_log('Failed to get SAML attributes: ' . $e->getMessage());
        return null;
    }
}

/**
 * Logout from SAML
 */
function samlLogout($returnUrl = null) {
    try {
        $auth = initializeSAML();
        if ($returnUrl) {
            $auth->logout(['ReturnTo' => $returnUrl]);
        } else {
            $auth->logout();
        }
    } catch (Exception $e) {
        error_log('SAML logout failed: ' . $e->getMessage());
    }
}

/**
 * Extract user information from SAML attributes
 */
function extractUserInfoFromSAML($attributes) {
    return [
        'username' => $attributes['samaccountname'][0] ?? null,
        'email' => $attributes['mail'][0] ?? null,
        'first_name' => $attributes['prefFirstName'][0] ?? $attributes['givenname'][0] ?? null,
        'last_name' => $attributes['prefLastName'][0] ?? $attributes['sn'][0] ?? null,
        'full_name' => trim(($attributes['prefFirstName'][0] ?? $attributes['givenname'][0] ?? '') . ' ' . ($attributes['prefLastName'][0] ?? $attributes['sn'][0] ?? '')),
        'university_id' => $attributes['universityID'][0] ?? null,
        'affiliation' => $attributes['eduPersonPrimaryAffiliation'][0] ?? null,  // Key for role detection!
        'ou' => $attributes['ou'][0] ?? null,
    ];
}

/**
 * Determine user role from eduPersonPrimaryAffiliation attribute
 * 
 * Affiliation values from Sabancı University:
 * - student = öğrenci (Student)
 * - faculty = akademik (Faculty/Academic)
 * - staff = idari (Administrative Staff)
 * - affiliate = outsource akademik (External Academic)
 * - alum = mezun (Alumni)
 */
function detectUserRole($userInfo) {
    $affiliation = strtolower($userInfo['affiliation'] ?? '');
    $email = strtolower($userInfo['email'] ?? '');
    
    // Primary: Use eduPersonPrimaryAffiliation (most reliable)
    if ($affiliation) {
        switch ($affiliation) {
            case 'student':
                return ROLE_STUDENT;
                
            case 'faculty':
            case 'staff':
            case 'affiliate':
                return ROLE_ADMIN;
                
            case 'alum':
                // Alumni - could be restricted or treated as students
                // For now, treat as students but you can change this
                return ROLE_STUDENT;
                
            default:
                // Unknown affiliation, continue to fallback checks
                break;
        }
    }
    
    // Fallback 1: Check email patterns (for special cases)
    if (strpos($email, 'admin@') !== false) {
        return ROLE_ADMIN;
    }
    
    // Fallback 2: Default for @sabanciuniv.edu emails
    if (strpos($email, '@sabanciuniv.edu') !== false) {
        // If we have Sabancı email but no affiliation, default to student
        return ROLE_STUDENT;
    }
    
    // Unable to determine role
    return null;
}

/**
 * Provision or update user from SAML attributes
 */
function provisionUserFromSAML($attributes) {
    $db = getDB();
    $userInfo = extractUserInfoFromSAML($attributes);
    
    // Validate required fields
    if (!$userInfo['email']) {
        throw new Exception('Email not provided by SAML');
    }
    
    // Detect role
    $role = detectUserRole($userInfo);
    
    if (!$role) {
        throw new Exception('Could not determine user role. Please contact administrator.');
    }
    
    $email = $userInfo['email'];
    $name = trim($userInfo['full_name']);
    $universityId = $userInfo['university_id'];
    
    // Provision based on role
    if ($role === ROLE_STUDENT) {
        // Check if student exists
        $stmt = $db->prepare("SELECT * FROM students WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Update existing student
            $stmt = $db->prepare("
                UPDATE students 
                SET name = ?, university_id = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$name, $universityId, $user['id']]);
            return ['id' => $user['id'], 'role' => ROLE_STUDENT, 'user' => $user];
        } else {
            // Create new student
            $stmt = $db->prepare("
                INSERT INTO students (name, email, university_id, is_active, created_at, updated_at)
                VALUES (?, ?, ?, 1, NOW(), NOW())
            ");
            $stmt->execute([$name, $email, $universityId]);
            $userId = $db->lastInsertId();
            
            // Get the newly created user
            $stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            return ['id' => $userId, 'role' => ROLE_STUDENT, 'user' => $user];
        }
    } elseif ($role === ROLE_ADMIN) {
        // Check if admin exists
        $stmt = $db->prepare("SELECT * FROM admin_users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            return ['id' => $user['id'], 'role' => ROLE_ADMIN, 'user' => $user];
        } else {
            // For admins, don't auto-provision - they need to be manually created first
            throw new Exception('Admin user not found in database. Please contact system administrator to create your account.');
        }
    }
    
    throw new Exception('Unsupported user role: ' . $role);
}

/**
 * Handle SAML authentication and create session
 * This is called after successful SAML authentication
 */
function handleSAMLAuthentication() {
    // Require SAML authentication
    $auth = requireSAMLAuth();
    
    // Get SAML attributes
    $attributes = $auth->getAttributes();
    
    if (!$attributes) {
        throw new Exception('Could not retrieve SAML attributes');
    }
    
    // Store SAML attributes in session for debugging/reference
    $_SESSION['saml_attributes'] = $attributes;
    
    // Provision or update user
    $userData = provisionUserFromSAML($attributes);
    
    // Create application session using existing auth system
    login($userData['id'], $userData['role'], $userData['user']);
    
    return $userData;
}

/**
 * Get redirect URL based on user role
 */
function getSAMLRedirectURL($role) {
    $baseUrl = dirname(dirname($_SERVER['SCRIPT_NAME']));
    
    switch ($role) {
        case ROLE_ADMIN:
            return $baseUrl . '/internship-portal/admin/admin-dashboard.html';
        case ROLE_COMPANY:
            return $baseUrl . '/internship-portal/company/company-dashboard.html';
        case ROLE_STUDENT:
            return $baseUrl . '/internship-portal/student/student-dashboard.html';
        default:
            return $baseUrl . '/internship-portal/index.html';
    }
}

