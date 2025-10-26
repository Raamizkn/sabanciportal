<?php

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

?> 