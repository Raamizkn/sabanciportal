<?php

// API Entry Point for Admin Actions

header('Content-Type: application/json');

// Handle CORS
$allowedOrigin = $_SERVER['HTTP_ORIGIN'] ?? 'http://localhost:8000';
header("Access-Control-Allow-Origin: $allowedOrigin");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

require_once __DIR__ . '/../data/mock_data.php';
require_once __DIR__ . '/../handlers/admin_handler.php';

$action = $_GET['action'] ?? '';

$response = null;

// Some actions depend on the request method
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {
    // Term Management
    case 'get_terms':
        $response = get_all_terms();
        break;
    case 'add_term':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $response = add_term($data);
        }
        break;
    case 'update_term':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $term_id = $data['term_id'] ?? null;
            $response = update_term($term_id, $data);
        }
        break;
    case 'delete_term':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $term_id = $data['term_id'] ?? null;
            $response = delete_term($term_id);
        }
        break;

    // Student Management
    case 'get_students':
        $response = get_all_students();
        break;
    case 'get_student_details':
        $response = get_student_details_admin($_GET['student_id'] ?? null);
        break;
    case 'add_student':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $response = add_new_student($data);
        }
        break;
    case 'update_student':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $student_id = $data['student_id'] ?? null;
            $response = update_student($student_id, $data);
        }
        break;
    case 'delete_student':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $student_id = $data['student_id'] ?? null;
            $response = delete_student($student_id);
        }
        break;
    case 'impersonate_student':
        $response = impersonate_student($_GET['student_id'] ?? null);
        break;

    // Company Management
    case 'get_companies':
        $response = get_all_companies();
        break;
    case 'get_company_details':
        $response = get_company_details_admin($_GET['company_id'] ?? null);
        break;
    case 'add_company':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $response = add_new_company($data);
        }
        break;
    case 'update_company':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $company_id = $data['company_id'] ?? null;
            $response = update_company($company_id, $data);
        }
        break;
    case 'delete_company':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $company_id = $data['company_id'] ?? null;
            $response = delete_company($company_id);
        }
        break;
    case 'impersonate_company':
        $response = impersonate_company($_GET['company_id'] ?? null);
        break;

    // Internship Management
    case 'get_internships':
        $response = get_all_internships_admin();
        break;
    case 'get_internship_info':
        $response = get_internship_info_admin($_GET['internship_id'] ?? null);
        break;
    case 'edit_internship':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $internship_id = $data['internship_id'] ?? null;
            $response = edit_internship_admin($internship_id, $data);
        }
        break;
    case 'delete_internship':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $internship_id = $data['internship_id'] ?? null;
            $response = delete_internship_admin($internship_id);
        }
        break;
    case 'get_internship_applicants':
        $response = get_internship_applicants($_GET['internship_id'] ?? null);
        break;

    // Application Management
    case 'get_application_details':
        $response = get_application_details_admin($_GET['application_id'] ?? null);
        break;
    case 'approve_application':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $app_id = $data['application_id'] ?? null;
            $response = approve_application($app_id);
        }
        break;
    case 'reject_application':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $app_id = $data['application_id'] ?? null;
            $response = reject_application($app_id);
        }
        break;

    // Reports & Tools
    case 'generate_report':
        $params = json_decode(file_get_contents('php://input'), true);
        $report_type = $params['report_type'] ?? 'general';
        $response = generate_report($report_type, $params);
        break;
    case 'get_evaluation':
        $response = get_evaluation_profile($_GET['evaluation_id'] ?? null);
        break;
    case 'send_email':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $response = send_email_admin($data);
        }
        break;

    default:
        http_response_code(404);
        $response = ['error' => 'Admin action not found.'];
        break;
}

if ($response === null && $method !== 'POST') {
    http_response_code(405); // Method Not Allowed
    $response = ['error' => 'This endpoint requires a POST request.'];
}

echo json_encode($response);

?>