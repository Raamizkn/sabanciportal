<?php

// Set the content type to JSON
header('Content-Type: application/json');

// Mock data (replace with database calls later)
$all_applications = [
    1 => [
        "101" => ['id' => "101", 'internship_id' => "INT001", 'company_name' => 'Tech Solutions Inc.', 'position' => 'Software Engineer Intern', 'status' => 'Pending', 'applied_date' => '2024-07-01'],
        "102" => ['id' => "102", 'internship_id' => "INT002", 'company_name' => 'Innovate Hub', 'position' => 'Data Analyst Intern', 'status' => 'Accepted', 'applied_date' => '2024-07-05'],
        "103" => ['id' => "103", 'internship_id' => "INT003", 'company_name' => 'Marketing Masters', 'position' => 'Marketing Intern', 'status' => 'Rejected', 'applied_date' => '2024-07-10']
    ],
    // ...applications for other students...
];

$method = $_SERVER['REQUEST_METHOD'];

// Simple routing
$entity = $_GET['entity'] ?? null;
$action = $_GET['action'] ?? null;
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : null; // Example: /index.php?entity=applications&student_id=1
$application_id = $_GET['application_id'] ?? null; // Example: /index.php?action=withdraw&application_id=101


if ($method === 'GET') {
    if ($entity === 'applications' && $student_id !== null) {
        if (isset($all_applications[$student_id])) {
            echo json_encode(array_values($all_applications[$student_id])); // Return applications for the student
        } else {
            echo json_encode([]); // No applications for this student
        }
    } elseif (isset($_GET['path']) && $_GET['path'] === 'users') { // Keep existing example
        $users = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob']
        ];
        echo json_encode($users);
    } 
    else {
        echo json_encode(['message' => 'Welcome to the PHP Backend! Use specific endpoints like ?entity=applications&student_id=1']);
    }
} 
elseif ($method === 'POST') {
    if ($action === 'withdraw' && $application_id !== null) {
        // Simulate withdrawing an application
        // In a real app, you'd find the student_id from session/auth
        // and update the status in the database.
        $found_app = false;
        foreach ($all_applications as $s_id => $apps) {
            if (isset($apps[$application_id])) {
                $all_applications[$s_id][$application_id]['status'] = 'Withdrawn';
                // Note: This change to $all_applications is only for the duration of this script execution.
                // It won't persist without a database.
                echo json_encode(['message' => "Application {$application_id} withdrawn successfully.", 'application' => $all_applications[$s_id][$application_id]]);
                $found_app = true;
                break;
            }
        }
        if (!$found_app) {
            http_response_code(404);
            echo json_encode(['error' => "Application {$application_id} not found."]);
        }

    } elseif ($action === 'confirm' && $application_id !== null) {
        // Simulate confirming an application
        $found_app = false;
        foreach ($all_applications as $s_id => $apps) {
            if (isset($apps[$application_id]) && $all_applications[$s_id][$application_id]['status'] === 'Accepted') {
                $all_applications[$s_id][$application_id]['status'] = 'Confirmed';
                echo json_encode(['message' => "Application {$application_id} confirmed successfully.", 'application' => $all_applications[$s_id][$application_id]]);
                $found_app = true;
                break;
            } elseif (isset($apps[$application_id])) { // App exists but not in 'Accepted' state
                 echo json_encode(['message' => "Application {$application_id} cannot be confirmed. Status is not 'Accepted'.", 'application' => $all_applications[$s_id][$application_id]]);
                 $found_app = true; // Still counts as found for response purposes
                 break;
            }
        }
         if (!$found_app) {
            http_response_code(404);
            echo json_encode(['error' => "Application {$application_id} not found or cannot be confirmed."]);
        }
    }
    // Keep existing POST example for /api/data if ?action is not specified
    // This condition might need refinement based on actual desired behavior for general POSTs.
    elseif (empty($action)) { 
        $input = json_decode(file_get_contents('php://input'), true);
        echo json_encode(['message' => 'Data received (general POST)', 'received_data' => $input]);
    }
    else {
        http_response_code(400); // Bad Request for unknown POST actions
        echo json_encode(['error' => 'Unknown POST action.']);
    }
} 
else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Method Not Allowed']);
}

?> 