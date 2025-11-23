<?php

/**
 * SAML Login Endpoint
 * Handles SAML authentication and redirects to appropriate dashboard
 */

// Start session
session_name("internship-portal-sess");
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

require_once __DIR__ . '/saml_auth.php';

try {
    // Check if SAML is available
    if (!isSAMLAvailable()) {
        throw new Exception('SAML authentication is not configured. Please contact your administrator.');
    }
    
    // Check if already logged in via regular session
    if (isLoggedIn()) {
        // Already logged in, redirect to dashboard
        $role = getCurrentUserRole();
        $redirectUrl = getSAMLRedirectURL($role);
        header('Location: ' . $redirectUrl);
        exit;
    }
    
    // Handle SAML authentication
    $userData = handleSAMLAuthentication();
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Redirect to appropriate dashboard
    $redirectUrl = getSAMLRedirectURL($userData['role']);
    header('Location: ' . $redirectUrl);
    exit;
    
} catch (Exception $e) {
    // Log error
    error_log('SAML Login Error: ' . $e->getMessage());
    
    // Display user-friendly error
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Login Error</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 600px;
                margin: 50px auto;
                padding: 20px;
                text-align: center;
            }
            .error-box {
                background: #fee;
                border: 1px solid #fcc;
                border-radius: 5px;
                padding: 20px;
                margin: 20px 0;
            }
            h1 { color: #c00; }
            a {
                display: inline-block;
                margin-top: 20px;
                padding: 10px 20px;
                background: #007bff;
                color: white;
                text-decoration: none;
                border-radius: 5px;
            }
            a:hover { background: #0056b3; }
        </style>
    </head>
    <body>
        <h1>Authentication Error</h1>
        <div class="error-box">
            <p><?php echo htmlspecialchars($e->getMessage()); ?></p>
        </div>
        <a href="../../internship-portal/index.html">Back to Login</a>
    </body>
    </html>
    <?php
    exit;
}

