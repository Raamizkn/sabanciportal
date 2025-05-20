<?php

// backend/handlers/internships_handler.php

global $internships, $method, $entity, $id, $action, $input;

// Helper function to generate a new internship ID (mock)
function generateNewInternshipId() {
    global $internships;
    $max_id = 0;
    foreach (array_keys($internships) as $key) {
        if (strpos($key, 'INT') === 0) {
            $num = (int)substr($key, 3);
            if ($num > $max_id) {
                $max_id = $num;
            }
        }
    }
    return "INT" . str_pad($max_id + 1, 3, "0", STR_PAD_LEFT);
}

if ($entity === 'internships') {
    if ($method === 'GET') {
        if ($id !== null) { // Get specific internship: ?entity=internships&id=INT001
            if (isset($internships[$id])) {
                echo json_encode($internships[$id]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => "Internship with ID {$id} not found."]);
            }
        } else { // Get all internships (or filter by company_id if provided)
            $company_id_param = $_GET['company_id'] ?? null;
            if ($company_id_param !== null) {
                $company_internships = [];
                foreach ($internships as $internship) {
                    // Assuming internships have a 'company_id' field when created
                    if (isset($internship['company_id']) && $internship['company_id'] == $company_id_param) {
                        $company_internships[] = $internship;
                    }
                }
                echo json_encode($company_internships);
            } else {
                echo json_encode(array_values($internships)); // List all
            }
        }
    } 
    elseif ($method === 'POST') {
        // Assumes company_id is part of the input for create, or derived from auth later
        if ($action === 'create') {
            if (!isset($input['company_id']) || !isset($input['position']) || !isset($input['description'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing company_id, position, or description for creating internship.']);
                exit;
            }
            $new_internship_id = generateNewInternshipId();
            $new_internship = [
                'id' => $new_internship_id,
                'company_id' => $input['company_id'], // This would come from authenticated user later
                'company_name' => $input['company_name'] ?? 'Unknown Company', // Should be linked from company profile
                'position' => $input['position'],
                'description' => $input['description'],
                'location' => $input['location'] ?? 'Not specified',
                'posted_date' => date('Y-m-d'),
                'status' => 'active' // Default status
                // Add other fields like duration, requirements etc. from $input
            ];
            $internships[$new_internship_id] = $new_internship;
            http_response_code(201);
            echo json_encode($new_internship);
        }
        elseif ($id !== null && $action === 'update') { // For POST-based update
            if (!isset($internships[$id])) {
                http_response_code(404);
                echo json_encode(['error' => "Internship {$id} not found for update."]);
                exit;
            }
            // Assuming company_id in $input is checked against the internship's owner later with auth
            $internships[$id] = array_merge($internships[$id], $input); // Simple merge, can be more specific
            $internships[$id]['id'] = $id; // Ensure ID is not overwritten by input
            echo json_encode($internships[$id]);
        }
        elseif ($id !== null && $action === 'set_status') {
            if (!isset($internships[$id])) {
                http_response_code(404);
                echo json_encode(['error' => "Internship {$id} not found."]);
                exit;
            }
            if (!isset($input['status']) || !in_array($input['status'], ['active', 'inactive'])){
                http_response_code(400);
                echo json_encode(['error' => "Invalid status provided. Must be 'active' or 'inactive'."]);
                exit;
            }
            $internships[$id]['status'] = $input['status'];
            echo json_encode($internships[$id]);
        }
        elseif ($id !== null && $action === 'delete') {
            if (!isset($internships[$id])) {
                http_response_code(404);
                echo json_encode(['error' => "Internship {$id} not found."]);
                exit;
            }
            // Add auth check: ensure this company owns the internship
            unset($internships[$id]);
            echo json_encode(['message' => "Internship {$id} deleted successfully."]);
        }
        elseif ($id !== null && $action === 'duplicate') {
            if (!isset($internships[$id])) {
                http_response_code(404);
                echo json_encode(['error' => "Internship {$id} not found to duplicate."]);
                exit;
            }
            $original_internship = $internships[$id];
            $new_internship_id = generateNewInternshipId();
            $duplicated_internship = $original_internship;
            $duplicated_internship['id'] = $new_internship_id;
            $duplicated_internship['position'] = $original_internship['position'] . ' (Copy)';
            $duplicated_internship['posted_date'] = date('Y-m-d');
            $duplicated_internship['status'] = 'inactive'; // Duplicates are inactive by default
            $internships[$new_internship_id] = $duplicated_internship;
            http_response_code(201);
            echo json_encode($duplicated_internship);
        }
        else {
            http_response_code(400);
            echo json_encode(['error' => "Unknown action for POST to internships or missing ID."]);
        }
    } 
    else {
        // For methods like PUT or DELETE if we adopt more RESTful verb usage later
        http_response_code(405); 
        echo json_encode(['error' => "Method $method not currently supported for $entity entity via this handler (consider POST with action)."]);
    }
    exit; 
}

?> 