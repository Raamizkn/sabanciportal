<?php

// API Entry Point for Company Actions

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../data/mock_data.php';
require_once __DIR__ . '/../handlers/company_handler.php';

$action = $_GET['action'] ?? '';
$company_id = $_GET['company_id'] ?? 1; // Default to company 1 for now
$method = $_SERVER['REQUEST_METHOD'];

$response = null;

// Validate company access
if (!validate_company_access($company_id)) {
    http_response_code(404);
    $response = ['error' => 'Company not found'];
    echo json_encode($response);
    exit();
}

switch ($action) {
    // Company Profile Management
    case 'get_profile':
        if ($method === 'GET') {
            $response = get_company_profile($company_id);
        } else {
            http_response_code(405);
            $response = ['error' => 'GET method required'];
        }
        break;
        
    case 'update_profile':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                $response = ['error' => 'Invalid JSON data'];
                break;
            }
            $response = update_company_profile($company_id, $data);
        } else {
            http_response_code(405);
            $response = ['error' => 'POST method required'];
        }
        break;

    // Internship Management
    case 'get_internships':
        if ($method === 'GET') {
            $response = get_company_internships($company_id);
        } else {
            http_response_code(405);
            $response = ['error' => 'GET method required'];
        }
        break;
        
    case 'create_internship':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                $response = ['error' => 'Invalid JSON data'];
                break;
            }
            $response = create_company_internship($company_id, $data);
        } else {
            http_response_code(405);
            $response = ['error' => 'POST method required'];
        }
        break;
        
    case 'update_internship':
        if ($method === 'POST') {
            $internship_id = $_GET['internship_id'] ?? null;
            if (!$internship_id) {
                http_response_code(400);
                $response = ['error' => 'internship_id parameter required'];
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                $response = ['error' => 'Invalid JSON data'];
                break;
            }
            $response = update_company_internship($company_id, $internship_id, $data);
        } else {
            http_response_code(405);
            $response = ['error' => 'POST method required'];
        }
        break;
        
    case 'delete_internship':
        if ($method === 'POST' || $method === 'DELETE') {
            $internship_id = $_GET['internship_id'] ?? null;
            if (!$internship_id) {
                http_response_code(400);
                $response = ['error' => 'internship_id parameter required'];
                break;
            }
            $response = delete_company_internship($company_id, $internship_id);
        } else {
            http_response_code(405);
            $response = ['error' => 'POST or DELETE method required'];
        }
        break;

    // Application Management
    case 'get_applications':
        if ($method === 'GET') {
            $response = get_company_applications($company_id);
        } else {
            http_response_code(405);
            $response = ['error' => 'GET method required'];
        }
        break;
        
    case 'get_application':
        if ($method === 'GET') {
            $application_id = $_GET['application_id'] ?? null;
            if (!$application_id) {
                http_response_code(400);
                $response = ['error' => 'application_id parameter required'];
                break;
            }
            $response = get_single_application($company_id, $application_id);
        } else {
            http_response_code(405);
            $response = ['error' => 'GET method required'];
        }
        break;
        
    case 'update_application_status':
        if ($method === 'POST') {
            $application_id = $_GET['application_id'] ?? null;
            if (!$application_id) {
                http_response_code(400);
                $response = ['error' => 'application_id parameter required'];
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE || !isset($data['status'])) {
                http_response_code(400);
                $response = ['error' => 'Invalid JSON data or missing status field'];
                break;
            }
            
            $response = update_application_status($company_id, $application_id, $data['status']);
        } else {
            http_response_code(405);
            $response = ['error' => 'POST method required'];
        }
        break;

    // Evaluation Management
    case 'create_evaluation':
        if ($method === 'POST') {
            $application_id = $_GET['application_id'] ?? null;
            if (!$application_id) {
                http_response_code(400);
                $response = ['error' => 'application_id parameter required'];
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                $response = ['error' => 'Invalid JSON data'];
                break;
            }
            
            $response = create_evaluation($company_id, $application_id, $data);
        } else {
            http_response_code(405);
            $response = ['error' => 'POST method required'];
        }
        break;
        
    case 'get_evaluations':
        if ($method === 'GET') {
            $response = get_company_evaluations($company_id);
        } else {
            http_response_code(405);
            $response = ['error' => 'GET method required'];
        }
        break;

    // Dashboard & Statistics
    case 'get_dashboard_stats':
        if ($method === 'GET') {
            $response = get_company_dashboard_stats($company_id);
        } else {
            http_response_code(405);
            $response = ['error' => 'GET method required'];
        }
        break;

    // Default case - API documentation
    case '':
    case 'help':
        $response = [
            'message' => 'Sabanci Internship Portal - Company API',
            'version' => '1.0',
            'endpoints' => [
                'Profile Management' => [
                    'GET ?action=get_profile' => 'Get company profile information',
                    'POST ?action=update_profile' => 'Update company profile'
                ],
                'Internship Management' => [
                    'GET ?action=get_internships' => 'Get all company internships',
                    'POST ?action=create_internship' => 'Create new internship posting',
                    'POST ?action=update_internship&internship_id=ID' => 'Update existing internship',
                    'POST ?action=delete_internship&internship_id=ID' => 'Delete internship posting'
                ],
                'Application Management' => [
                    'GET ?action=get_applications' => 'Get all applications for company internships',
                    'GET ?action=get_application&application_id=ID' => 'Get specific application details',
                    'POST ?action=update_application_status&application_id=ID' => 'Update application status'
                ],
                'Evaluation Management' => [
                    'POST ?action=create_evaluation&application_id=ID' => 'Create student evaluation',
                    'GET ?action=get_evaluations' => 'Get all company evaluations'
                ],
                'Dashboard' => [
                    'GET ?action=get_dashboard_stats' => 'Get company dashboard statistics'
                ]
            ],
            'usage' => 'All requests should include company_id parameter. POST requests should include JSON data in request body.',
            'example' => 'http://localhost:8001/backend/api/company.php?company_id=1&action=get_profile'
        ];
        break;

    default:
        http_response_code(400);
        $response = [
            'error' => 'Invalid action',
            'available_actions' => [
                'get_profile', 'update_profile', 'get_internships', 'create_internship', 
                'update_internship', 'delete_internship', 'get_applications', 'get_application',
                'update_application_status', 'create_evaluation', 'get_evaluations', 'get_dashboard_stats'
            ]
        ];
        break;
}

// Return response
if (isset($response['error']) && !headers_sent()) {
    if (!http_response_code()) {
        http_response_code(400);
    }
}

echo json_encode($response, JSON_PRETTY_PRINT);

?>
