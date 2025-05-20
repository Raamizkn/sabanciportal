<?php

// Set the content type to JSON
header('Content-Type: application/json');

// --- Mock Data (replace with database calls later) ---

// Available Internships
$internships = [
    "INT001" => ['id' => "INT001", 'company_id' => "COMP001", 'company_name' => 'Tech Solutions Inc.', 'position' => 'Software Engineer Intern', 'description' => 'Work on exciting new software projects.', 'location' => 'Remote', 'posted_date' => '2024-06-15', 'status' => 'active'],
    "INT002" => ['id' => "INT002", 'company_id' => "COMP002", 'company_name' => 'Innovate Hub', 'position' => 'Data Analyst Intern', 'description' => 'Analyze data and generate insights.', 'location' => 'New York, NY', 'posted_date' => '2024-06-20', 'status' => 'active'],
    "INT003" => ['id' => "INT003", 'company_id' => "COMP003", 'company_name' => 'Marketing Masters', 'position' => 'Marketing Intern', 'description' => 'Assist with marketing campaigns.', 'location' => 'San Francisco, CA', 'posted_date' => '2024-06-25', 'status' => 'active'],
    "INT004" => ['id' => "INT004", 'company_id' => "COMP004", 'company_name' => 'Green Future Co.', 'position' => 'Sustainability Intern', 'description' => 'Support sustainability initiatives.', 'location' => 'Austin, TX', 'posted_date' => '2024-07-01', 'status' => 'inactive'],
];

// Student Applications (keyed by application_id for easier lookup)
// student_id will be part of the application data
$applications = [
    "APP001" => ['id' => "APP001", 'student_id' => 1, 'internship_id' => "INT001", 'company_name' => 'Tech Solutions Inc.', 'position' => 'Software Engineer Intern', 'status' => 'Pending', 'applied_date' => '2024-07-01', 'cover_letter' => 'I am very interested in this role.'],
    "APP002" => ['id' => "APP002", 'student_id' => 1, 'internship_id' => "INT002", 'company_name' => 'Innovate Hub', 'position' => 'Data Analyst Intern', 'status' => 'Offered', 'applied_date' => '2024-07-05', 'offer_details' => 'Offer valid until 2024-07-20.', 'cover_letter' => 'Eager to analyze data.'],
    "APP003" => ['id' => "APP003", 'student_id' => 2, 'internship_id' => "INT001", 'company_name' => 'Tech Solutions Inc.', 'position' => 'Software Engineer Intern', 'status' => 'Rejected', 'applied_date' => '2024-07-10', 'cover_letter' => 'Passionate about software.'],
];

// Uploaded Documents (keyed by document_id)
$documents = [
    "DOC001" => ['id' => "DOC001", 'application_id' => "APP002", 'student_id' => 1, 'document_type' => 'CV', 'file_name' => 'student1_cv.pdf', 'upload_date' => '2024-07-15', 'file_path_mock' => '/uploads/student1/APP002/student1_cv.pdf'],
];

// Original $all_applications (keyed by student_id, then app_id) - We will transition away from this towards $applications
$all_applications_legacy = [
    1 => [
        "101" => ['id' => "101", 'internship_id' => "INT001", 'company_name' => 'Tech Solutions Inc.', 'position' => 'Software Engineer Intern', 'status' => 'Pending', 'applied_date' => '2024-07-01'],
        "102" => ['id' => "102", 'internship_id' => "INT002", 'company_name' => 'Innovate Hub', 'position' => 'Data Analyst Intern', 'status' => 'Accepted', 'applied_date' => '2024-07-05'], // Note: status was 'Accepted', now using 'Offered', 'Confirmed' etc.
        "103" => ['id' => "103", 'internship_id' => "INT003", 'company_name' => 'Marketing Masters', 'position' => 'Marketing Intern', 'status' => 'Rejected', 'applied_date' => '2024-07-10']
    ],
];

// --- End Mock Data ---

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
if ($entity === 'internships') {
    require_once __DIR__ . '/handlers/internships_handler.php';
}
elseif ($entity === 'applications') {
    require_once __DIR__ . '/handlers/applications_handler.php';
}
elseif ($entity === 'documents') {
    require_once __DIR__ . '/handlers/documents_handler.php';
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