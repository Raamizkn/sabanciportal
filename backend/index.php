<?php

// Start output buffering to catch any stray output
ob_start();

// Suppress display of errors (log them instead)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Get the origin from the request
$origin = $_SERVER['HTTP_ORIGIN'] ?? null;

// Allow specific origins (for development and production)
$allowedOrigins = [
    'http://localhost:8000',
    'http://localhost:3000',
    'http://127.0.0.1:8000',
    'http://127.0.0.1:3000',
    'http://pro2-dev.sabanciuniv.edu',
    'https://pro2-dev.sabanciuniv.edu'
];

// Set the allowed origin
if ($origin && in_array($origin, $allowedOrigins)) {
    $allowedOrigin = $origin;
} else if ($origin) {
    // If origin is provided but not in list, use it anyway (for flexibility)
    $allowedOrigin = $origin;
} else {
    // Fallback for wildcard (no credentials)
    $allowedOrigin = '*';
}

// Handle CORS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: $allowedOrigin");
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
    http_response_code(200);
    exit;
}

// Set CORS headers for all requests
header("Access-Control-Allow-Origin: $allowedOrigin");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Set the content type to JSON
header('Content-Type: application/json');

// --- Load Data from Database ---
require_once __DIR__ . '/data/data.php';

$method = $_SERVER['REQUEST_METHOD'];

// Simple routing parameters
$entity = $_GET['entity'] ?? null;
$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null; // Generic ID, could be application_id, student_id, internship_id
$student_id_param = $_GET['student_id'] ?? null;
$application_id_param = $_GET['application_id'] ?? null;

$input = null;
if ($method === 'POST' || $method === 'PUT') { // Assuming PUT might be used later
    $input = json_decode(file_get_contents('php://input'), true);
}

// Route to the appropriate handler
if ($entity === 'auth') {
    require_once __DIR__ . '/auth/login_handler.php';
    exit;
}
elseif ($entity === 'internships') {
    require_once __DIR__ . '/handlers/internships_handler.php';
}
elseif ($entity === 'applications') {
    require_once __DIR__ . '/handlers/applications_handler.php';
}
elseif ($entity === 'documents') {
    require_once __DIR__ . '/handlers/documents_handler.php';
}
elseif ($entity === 'students') {
    require_once __DIR__ . '/handlers/student_handler.php';
}
elseif ($entity === 'companies') {
    require_once __DIR__ . '/handlers/company_handler.php';
}
elseif ($entity === 'rounds') {
    require_once __DIR__ . '/handlers/rounds_handler.php';
}
elseif ($entity === 'quotas') {
    require_once __DIR__ . '/handlers/quotas_handler.php';
}
elseif ($entity === 'admin') {
    require_once __DIR__ . '/handlers/admin_handler.php';
    
    $resource = $_GET['resource'] ?? null;
    // Note: The 'id' from the general parameters is used here as well.
    $action = $_GET['action'] ?? null;
    $response = null;

    switch ($resource) {
        case 'students':
            if ($method === 'GET') {
                $response = ($id !== null) ? get_student_details_admin($id) : get_all_students();
            } elseif ($method === 'POST') {
                if ($action === 'add') $response = add_new_student($input);
                elseif ($action === 'update' && $id !== null) $response = update_student($id, $input);
                elseif ($action === 'delete' && $id !== null) $response = delete_student($id);
                elseif ($action === 'impersonate' && $id !== null) $response = impersonate_student($id);
            }
            break;

        case 'companies':
            if ($method === 'GET') {
                $response = ($id !== null) ? get_company_details_admin($id) : get_all_companies();
            } elseif ($method === 'POST') {
                if ($action === 'add') $response = add_new_company($input);
                elseif ($action === 'update' && $id !== null) $response = update_company($id, $input);
                elseif ($action === 'delete' && $id !== null) $response = delete_company($id);
                elseif ($action === 'impersonate' && $id !== null) $response = impersonate_company($id);
            }
            break;

        case 'terms':
            if ($method === 'GET') {
                $response = get_all_terms();
            } elseif ($method === 'POST') {
                if ($action === 'add') $response = add_term($input);
                elseif ($action === 'update' && $id !== null) $response = update_term($id, $input);
                elseif ($action === 'delete' && $id !== null) $response = delete_term($id);
            }
            break;
        
        case 'dashboard':
            if ($method === 'GET') {
                if ($action === 'stats') {
                    $response = get_admin_dashboard_stats();
                } elseif ($action === 'activity') {
                    $limit = $_GET['limit'] ?? 10;
                    $response = get_admin_activity_feed((int)$limit);
                }
            }
            break;
        
        case 'applications':
            if ($method === 'GET') {
                if ($id !== null) {
                    $response = get_application_details_admin($id);
                } else {
                    // Get all applications for admin
                    global $applications;
                    $response = array_values($applications);
                }
            }
            break;
        
        case 'internships':
            if ($method === 'GET') {
                if ($id !== null) {
                    $response = get_internship_info_admin($id);
                } else {
                    $response = get_all_internships_admin();
                }
            }
            break;
        
        case 'reports':
            if ($method === 'GET' || $method === 'POST') {
                $report_type = $_GET['type'] ?? $input['type'] ?? 'applications';
                $params = $_GET['params'] ?? $input['params'] ?? [];
                $response = generate_report($report_type, $params);
            }
            break;
        
        // Add more admin resources here as needed...
    }

    if ($response !== null) {
        if (isset($response['error'])) {
            http_response_code(400); // Bad Request or Not Found, depending on context
        }
        echo json_encode($response);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or unsupported admin resource or action.']);
    }
    exit; // IMPORTANT: Stop script execution to prevent falling through to the default message
}
// Legacy endpoint for old tests - can be removed later
elseif ($method === 'GET' && isset($_GET['path']) && $_GET['path'] === 'users') {
    $users = [ ['id' => 1, 'name' => 'Alice'], ['id' => 2, 'name' => 'Bob'] ];
    echo json_encode($users);
} 
// Legacy general POST data receiver - can be removed later
elseif ($method === 'POST' && empty($action) && empty($entity) && !empty($input)) { 
    echo json_encode(['message' => 'Data received (general POST)', 'received_data' => $input]);
}
else {
    // If no entity is matched by handlers or legacy routes
    if ($entity !== null) { // An entity was specified but not handled
        http_response_code(404);
        echo json_encode(['error' => "Entity '{$entity}' not found or no handler defined."]);
    } else { // No entity specified at all, default welcome
        echo json_encode(['message' => 'Welcome to the PHP Backend! Please specify an entity (e.g., /index.php?entity=internships).']);
    }
}

// End output buffering - handlers should have already output their JSON
if (ob_get_level() > 0) {
    ob_end_flush();
}

?> 
