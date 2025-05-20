<?php

// backend/handlers/applications_handler.php

global $applications, $internships, $method, $entity, $id, $action, $student_id_param, $input;

if ($entity === 'applications') {
    if ($method === 'GET') {
        $company_id_param = $_GET['company_id'] ?? null;
        $internship_id_param = $_GET['internship_id'] ?? null;

        if ($id !== null) { // Get specific application by its ID: ?entity=applications&id=APP001
            if (isset($applications[$id])) {
                echo json_encode($applications[$id]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => "Application with ID {$id} not found."]);
            }
        } elseif ($student_id_param !== null) { // Get applications for a specific student: ?entity=applications&student_id=1
            $student_apps = [];
            foreach ($applications as $app) {
                if ($app['student_id'] == $student_id_param) {
                    $student_apps[] = $app;
                }
            }
            echo json_encode($student_apps);
        } elseif ($company_id_param !== null) { // Get applications for a specific company: ?entity=applications&company_id=COMP001 (mock)
            $company_apps = [];
            foreach ($applications as $app) {
                // We need to check if the application's internship_id belongs to the company.
                // This requires that internships have a company_id and applications link to internships.
                if (isset($internships[$app['internship_id']]) && $internships[$app['internship_id']]['company_id'] == $company_id_param) {
                    $company_apps[] = $app;
                }
            }
            echo json_encode($company_apps);
        } elseif ($internship_id_param !== null) { // Get applications for a specific internship: ?entity=applications&internship_id=INT001
            $internship_apps = [];
            foreach ($applications as $app) {
                if ($app['internship_id'] == $internship_id_param) {
                    $internship_apps[] = $app;
                }
            }
            echo json_encode($internship_apps);
        }
        else { // Get all applications (less common for students/companies, more for admin)
            echo json_encode(array_values($applications));
        }
    } 
    elseif ($method === 'POST') {
        // Student actions: apply, withdraw, confirm_offer
        if ($action === 'apply') {
            // Expected input: {"student_id": 1, "internship_id": "INT00X", "cover_letter": "My letter"}
            if (!isset($input['student_id']) || !isset($input['internship_id']) || !isset($internships[$input['internship_id']])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing student_id, internship_id, or invalid internship_id.']);
                exit;
            }
            $new_app_id = "APP" . str_pad(count($applications) + 100, 3, "0", STR_PAD_LEFT); // Adjusted to avoid simple count collision if script re-runs fast, better with DB sequence
            $internship = $internships[$input['internship_id']];
            $new_application = [
                'id' => $new_app_id,
                'student_id' => $input['student_id'],
                'internship_id' => $input['internship_id'],
                'company_name' => $internship['company_name'],
                'position' => $internship['position'],
                'status' => 'Pending', 
                'applied_date' => date('Y-m-d'),
                'cover_letter' => $input['cover_letter'] ?? ''
            ];
            $applications[$new_app_id] = $new_application; // Add to our mock data
            http_response_code(201); // Created
            echo json_encode($new_application);
        }
        elseif ($id !== null && $action === 'withdraw') {
            if (isset($applications[$id])) {
                if ($applications[$id]['status'] !== 'Withdrawn' && $applications[$id]['status'] !== 'Confirmed_By_Student' && $applications[$id]['status'] !== 'Approved_By_Company') {
                    $applications[$id]['status'] = 'Withdrawn';
                    echo json_encode(['message' => "Application {$id} withdrawn successfully.", 'application' => $applications[$id]]);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => "Application {$id} cannot be withdrawn (current status: {$applications[$id]['status']})."]);
                }
            } else {
                http_response_code(404);
                echo json_encode(['error' => "Application {$id} not found."]);
            }
        }
        elseif ($id !== null && $action === 'confirm_offer') {
            if (isset($applications[$id])) {
                if ($applications[$id]['status'] === 'Offered') {
                    $applications[$id]['status'] = 'Confirmed_By_Student'; // Student confirms the offer
                    echo json_encode(['message' => "Application {$id} offer confirmed successfully by student.", 'application' => $applications[$id]]);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => "Application {$id} cannot be confirmed. Status must be 'Offered'. Current status: {$applications[$id]['status']}."]);
                }
            } else {
                http_response_code(404);
                echo json_encode(['error' => "Application {$id} not found."]);
            }
        }
        // Company actions on applications: e.g. offer, reject
        elseif ($id !== null && $action === 'update_status_company') { // Example: ?entity=applications&id=APP001&action=update_status_company
            // Expected input: {"status": "Offered"} or {"status": "Rejected_By_Company"}
            // Here, we'd also check if the logged-in company owns the internship linked to this application.
            if (!isset($applications[$id])) {
                http_response_code(404);
                echo json_encode(['error' => "Application {$id} not found."]);
                exit;
            }
            if (!isset($input['status'])) {
                http_response_code(400);
                echo json_encode(['error' => "Missing status in request body."]);
                exit;
            }
            // Add more validation for allowed status transitions by company
            $allowed_statuses = ['Offered', 'Rejected_By_Company', 'Interview_Scheduled', 'Approved_By_Company'];
            if (!in_array($input['status'], $allowed_statuses)){
                http_response_code(400);
                echo json_encode(['error' => "Invalid status '{$input['status']}' for company update."]);
                exit;
            }
            $applications[$id]['status'] = $input['status'];
            // If status is 'Offered', maybe add offer_details from input?
            if ($input['status'] === 'Offered' && isset($input['offer_details'])) {
                $applications[$id]['offer_details'] = $input['offer_details'];
            }
            echo json_encode(['message' => "Application {$id} status updated to {$input['status']} by company.", 'application' => $applications[$id]]);

        }
        else {
            http_response_code(400);
            echo json_encode(['error' => "Unknown action for POST request to applications entity or missing ID."]);
        }
    } 
    else {
        http_response_code(405); // Method Not Allowed for this entity if not GET or POST
        echo json_encode(['error' => "Method $method not allowed for $entity entity."]);
    }
    exit; // Stop further processing
}

?> 