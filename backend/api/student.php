<?php

// API Entry Point for Student Actions

header('Content-Type: application/json');

require_once __DIR__ . '/../data/mock_data.php';
require_once __DIR__ . '/../handlers/student_handler.php';

$action = $_GET['action'] ?? '';
$student_id = $_GET['student_id'] ?? 1; // Default to student 1 for now

$response = null;

switch ($action) {
    // Profile
    case 'get_profile_info':
        $response = get_profile_info($student_id);
        break;
    case 'update_profile':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $response = update_profile($student_id, $data);
        } else {
            http_response_code(405); // Method Not Allowed
            $response = ['error' => 'POST method required.'];
        }
        break;

    // Internships & Applications
    case 'get_internships':
        $response = get_internships_for_student();
        break;
    case 'get_student_applications':
        $response = get_all_student_applications($student_id);
        break;
    case 'view_application':
        $app_id = $_GET['application_id'] ?? null;
        $response = view_application_details($student_id, $app_id);
        break;
    case 'apply':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $internship_id = $data['internship_id'] ?? null;
            $response = apply_for_internship($student_id, $internship_id);
        } else {
            http_response_code(405);
            $response = ['error' => 'POST method required.'];
        }
        break;
    case 'withdraw_application':
         if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $app_id = $data['application_id'] ?? null;
            $response = withdraw_application($student_id, $app_id);
        } else {
            http_response_code(405);
            $response = ['error' => 'POST method required.'];
        }
        break;
    case 'accept_internship':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $app_id = $data['application_id'] ?? null;
            $response = accept_internship_offer($student_id, $app_id);
        } else {
            http_response_code(405);
            $response = ['error' => 'POST method required.'];
        }
        break;

    // Documents
    case 'get_docs':
        $response = get_student_documents($student_id);
        break;
    case 'upload_doc':
        // This is a placeholder. Real file uploads are more complex.
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = ['file_data' => '...']; // Placeholder
            $response = upload_student_document($student_id, $data);
        } else {
            http_response_code(405);
            $response = ['error' => 'POST method required.'];
        }
        break;
    case 'delete_doc':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $doc_id = $data['document_id'] ?? null;
            $response = delete_student_document($student_id, $doc_id);
        } else {
            http_response_code(405);
            $response = ['error' => 'POST method required.'];
        }
        break;
    case 'download_doc':
        // This is a placeholder. Real file downloads require different headers.
        $doc_id = $_GET['document_id'] ?? null;
        $response = download_student_document($student_id, $doc_id);
        break;

    // Auth
    case 'login':
        // Placeholder
        $response = ['status' => 'success', 'message' => 'Login successful.'];
        break;
    case 'logout':
        // Placeholder
        $response = ['status' => 'success', 'message' => 'Logout successful.'];
        break;

    default:
        http_response_code(404);
        $response = ['error' => 'Action not found.'];
        break;
}

echo json_encode($response);

?>