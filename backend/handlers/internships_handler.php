<?php

// backend/handlers/internships_handler.php

global $internships, $method, $entity, $id, $action, $input;

// Get database connection
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';
$db = getDB();

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

function mapInternshipWithCompany($row) {
    if (!$row) {
        return null;
    }

    $company = [
        'id' => $row['company_id'],
        'name' => $row['live_company_name'] ?? $row['company_name'],
        'industry' => $row['company_industry'] ?? null,
        'website' => $row['company_website'] ?? null,
        'phone' => $row['company_phone'] ?? null,
        'address' => $row['company_address'] ?? null,
        'description' => $row['company_description'] ?? null,
        'logo' => $row['company_logo'] ?? null
    ];

    unset($row['company_industry'], $row['company_website'], $row['company_phone'], $row['company_address'], $row['company_description'], $row['company_logo'], $row['live_company_name']);
    $row['company_name'] = $company['name'];
    $row['company'] = $company;
    return $row;
}

if ($entity === 'internships') {
    if ($method === 'GET') {
        $company_id_param = $_GET['company_id'] ?? null;
        if ($id !== null) {
            try {
                $stmt = $db->prepare("SELECT i.*, c.name AS live_company_name, c.industry AS company_industry, c.website AS company_website,
                        c.phone AS company_phone, c.address AS company_address, c.description AS company_description,
                        c.logo AS company_logo
                    FROM internships i
                    JOIN companies c ON i.company_id = c.id
                    WHERE i.id = ? AND i.status != 'Deleted'");
                $stmt->execute([$id]);
                $internship = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$internship) {
                    http_response_code(404);
                    echo json_encode(['error' => "Internship with ID {$id} not found."]);
                    exit;
                }
                echo json_encode(mapInternshipWithCompany($internship));
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to load internship: ' . $e->getMessage()]);
            }
        } else {
            try {
                $query = "SELECT i.*, c.name AS live_company_name, c.industry AS company_industry, c.website AS company_website,
                            c.phone AS company_phone, c.address AS company_address, c.description AS company_description,
                            c.logo AS company_logo
                        FROM internships i
                        JOIN companies c ON i.company_id = c.id
                        WHERE i.status != 'Deleted'";
                $params = [];
                if ($company_id_param !== null) {
                    $query .= " AND i.company_id = ?";
                    $params[] = $company_id_param;
                }
                $query .= " ORDER BY i.created_at DESC";
                $stmt = $db->prepare($query);
                $stmt->execute($params);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $result = array_map('mapInternshipWithCompany', $rows);
                echo json_encode($result);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to load internships: ' . $e->getMessage()]);
            }
        }
    } 
    elseif ($method === 'POST') {
        // Assumes company_id is part of the input for create, or derived from auth later
        if ($action === 'create') {
            // Require company role
            requireRole(ROLE_COMPANY);
            
            // Use authenticated company's ID
            $company_id = getCurrentUserId();
            
            if (!isset($input['position']) || !isset($input['description'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing position or description for creating internship.']);
                exit;
            }
            
            try {
                // Get company name from authenticated user
                $stmt = $db->prepare("SELECT name FROM companies WHERE id = ?");
                $stmt->execute([$company_id]);
                $company = $stmt->fetch();
                
                if (!$company) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Company not found.']);
                    exit;
                }
                
                // Insert into database
                $stmt = $db->prepare("INSERT INTO internships (company_id, company_name, title, position, description, location, dates, requirements, salary, type, status, application_deadline, posted_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, CURDATE())");
                $stmt->execute([
                    $company_id,
                    $company['name'],
                    $input['position'], // title
                    $input['position'],
                    $input['description'],
                    $input['location'] ?? 'Not specified',
                    $input['dates'] ?? 'TBD',
                    $input['requirements'] ?? '',
                    $input['salary'] ?? 'Not specified',
                    $input['type'] ?? 'Full-time',
                    $input['application_deadline'] ?? null
                ]);
                
                $newId = $db->lastInsertId();
                
                // Fetch the created internship
                $stmt = $db->prepare("SELECT * FROM internships WHERE id = ?");
                $stmt->execute([$newId]);
                $new_internship = $stmt->fetch();
                
                http_response_code(201);
                echo json_encode($new_internship);
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create internship: ' . $e->getMessage()]);
            }
        }
        elseif ($id !== null && $action === 'update') { // For POST-based update
            // Require company role
            requireRole(ROLE_COMPANY);
            
            // Verify company owns this internship
            $stmt = $db->prepare("SELECT company_id FROM internships WHERE id = ?");
            $stmt->execute([$id]);
            $internship = $stmt->fetch();
            
            if (!$internship) {
                http_response_code(404);
                echo json_encode(['error' => "Internship {$id} not found for update."]);
                exit;
            }
            
            if ($internship['company_id'] != getCurrentUserId()) {
                http_response_code(403);
                echo json_encode(['error' => "Access denied. You don't own this internship."]);
                exit;
            }
            
            // Update allowed fields
            $allowed_fields = ['title', 'position', 'description', 'location', 'dates', 'requirements', 'salary', 'type', 'application_deadline'];
            $update_fields = [];
            $update_values = [];
            
            foreach ($allowed_fields as $field) {
                if (isset($input[$field])) {
                    $update_fields[] = "$field = ?";
                    $update_values[] = $input[$field];
                }
            }
            
            if (empty($update_fields)) {
                http_response_code(400);
                echo json_encode(['error' => 'No valid fields to update.']);
                exit;
            }
            
            $update_values[] = $id;
            $stmt = $db->prepare("UPDATE internships SET " . implode(', ', $update_fields) . " WHERE id = ?");
            $stmt->execute($update_values);
            
            // Fetch updated internship
            $stmt = $db->prepare("SELECT * FROM internships WHERE id = ?");
            $stmt->execute([$id]);
            $updated_internship = $stmt->fetch();
            
            echo json_encode($updated_internship);
        }
        elseif ($id !== null && $action === 'set_status') {
            // Require company role
            requireRole(ROLE_COMPANY);
            
            // Verify company owns this internship
            $stmt = $db->prepare("SELECT * FROM internships WHERE id = ?");
            $stmt->execute([$id]);
            $internship = $stmt->fetch();
            
            if (!$internship) {
                http_response_code(404);
                echo json_encode(['error' => "Internship {$id} not found."]);
                exit;
            }
            
            if ($internship['company_id'] != getCurrentUserId()) {
                http_response_code(403);
                echo json_encode(['error' => "Access denied. You don't own this internship."]);
                exit;
            }
            
            if (!isset($input['status']) || !in_array($input['status'], ['Active', 'Inactive', 'Closed'])){
                http_response_code(400);
                echo json_encode(['error' => "Invalid status provided. Must be 'Active', 'Inactive', or 'Closed'."]);
                exit;
            }
            
            $stmt = $db->prepare("UPDATE internships SET status = ? WHERE id = ?");
            $stmt->execute([$input['status'], $id]);
            
            // Fetch updated internship
            $stmt = $db->prepare("SELECT * FROM internships WHERE id = ?");
            $stmt->execute([$id]);
            $updated_internship = $stmt->fetch();
            
            echo json_encode($updated_internship);
        }
        elseif ($id !== null && $action === 'delete') {
            // Require company role
            requireRole(ROLE_COMPANY);
            
            // Verify company owns this internship
            $stmt = $db->prepare("SELECT company_id FROM internships WHERE id = ?");
            $stmt->execute([$id]);
            $internship = $stmt->fetch();
            
            if (!$internship) {
                http_response_code(404);
                echo json_encode(['error' => "Internship {$id} not found."]);
                exit;
            }
            
            if ($internship['company_id'] != getCurrentUserId()) {
                http_response_code(403);
                echo json_encode(['error' => "Access denied. You don't own this internship."]);
                exit;
            }
            
            // Soft delete - set status to 'Deleted'
            $stmt = $db->prepare("UPDATE internships SET status = 'Deleted' WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['message' => "Internship {$id} deleted successfully."]);
        }
        elseif ($id !== null && $action === 'duplicate') {
            // Require company role
            requireRole(ROLE_COMPANY);
            
            // Verify company owns this internship
            $stmt = $db->prepare("SELECT * FROM internships WHERE id = ?");
            $stmt->execute([$id]);
            $original_internship = $stmt->fetch();
            
            if (!$original_internship) {
                http_response_code(404);
                echo json_encode(['error' => "Internship {$id} not found to duplicate."]);
                exit;
            }
            
            if ($original_internship['company_id'] != getCurrentUserId()) {
                http_response_code(403);
                echo json_encode(['error' => "Access denied. You don't own this internship."]);
                exit;
            }
            
            // Create duplicate
            $stmt = $db->prepare("INSERT INTO internships (company_id, company_name, title, position, description, location, dates, requirements, salary, type, status, application_deadline, posted_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Inactive', ?, CURDATE())");
            $stmt->execute([
                $original_internship['company_id'],
                $original_internship['company_name'],
                $original_internship['title'] . ' (Copy)',
                $original_internship['position'] . ' (Copy)',
                $original_internship['description'],
                $original_internship['location'],
                $original_internship['dates'],
                $original_internship['requirements'],
                $original_internship['salary'],
                $original_internship['type'],
                $original_internship['application_deadline']
            ]);
            
            $newId = $db->lastInsertId();
            
            // Fetch duplicated internship
            $stmt = $db->prepare("SELECT * FROM internships WHERE id = ?");
            $stmt->execute([$newId]);
            $duplicated_internship = $stmt->fetch();
            
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
