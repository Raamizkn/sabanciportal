<?php

/**
 * Login Handler
 * Handles user authentication requests
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'login') {
    // Login request
    if (!isset($input['email']) || !isset($input['password']) || !isset($input['role'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing email, password, or role']);
        exit;
    }
    
    $email = $input['email'];
    $password = $input['password'];
    $role = $input['role'];
    
    try {
        $db = getDB();
        
        // Authenticate based on role
        if ($role === ROLE_ADMIN) {
            // Check admin_users table
            $stmt = $db->prepare("SELECT * FROM admin_users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                login($user['id'], ROLE_ADMIN, $user);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Login successful',
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'role' => ROLE_ADMIN
                    ]
                ]);
            } else {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid credentials']);
            }
        } 
        elseif ($role === ROLE_COMPANY) {
            // Check companies table
            $stmt = $db->prepare("SELECT * FROM companies WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                login($user['id'], ROLE_COMPANY, $user);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Login successful',
                    'user' => [
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'role' => ROLE_COMPANY
                    ]
                ]);
            } else {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid credentials']);
            }
        } 
        elseif ($role === ROLE_STUDENT) {
            // Check students table
            $stmt = $db->prepare("SELECT * FROM students WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                login($user['id'], ROLE_STUDENT, $user);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Login successful',
                    'user' => [
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'role' => ROLE_STUDENT
                    ]
                ]);
            } else {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid credentials']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid role']);
        }
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Login failed: ' . $e->getMessage()]);
    }
    exit;
}

if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout();
    echo json_encode(['status' => 'success', 'message' => 'Logged out successfully']);
    exit;
}

if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'check') {
    // Check if user is logged in
    if (isLoggedIn()) {
        echo json_encode([
            'status' => 'success',
            'authenticated' => true,
            'user' => getSessionData()
        ]);
    } else {
        echo json_encode([
            'status' => 'success',
            'authenticated' => false
        ]);
    }
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request']);

