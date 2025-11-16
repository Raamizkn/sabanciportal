<?php

// backend/handlers/applications_handler.php

global $applications, $internships, $method, $entity, $id, $action, $student_id_param, $input;

// Get database connection
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';
$db = getDB();

function format_document_download_url($path) {
    if (!$path) {
        return null;
    }
    $normalized = str_replace('\\', '/', $path);
    return '/' . ltrim($normalized, '/');
}

function get_application_documents($application_primary_id) {
    global $db;
    try {
        $stmt = $db->prepare("SELECT document_id, document_type, file_name, file_path, file_size, upload_date
            FROM documents
            WHERE application_id = ?
            ORDER BY upload_date DESC, id DESC");
        $stmt->execute([$application_primary_id]);
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($documents as &$doc) {
            $doc['download_url'] = format_document_download_url($doc['file_path']);
        }
        return $documents;
    } catch (PDOException $e) {
        return [];
    }
}

function get_primary_resume($documents) {
    if (empty($documents)) {
        return null;
    }
    foreach ($documents as $doc) {
        if (isset($doc['document_type']) && strtoupper($doc['document_type']) === 'CV') {
            return $doc;
        }
    }
    return $documents[0];
}

if ($entity === 'applications') {
    if ($method === 'GET') {
        $company_id_param = $_GET['company_id'] ?? null;
        $internship_id_param = $_GET['internship_id'] ?? null;

        if ($id !== null) { // Get specific application by its ID: ?entity=applications&id=APP001
            try {
                $stmt = $db->prepare("
                    SELECT 
                        a.*,
                        a.id AS internal_id,
                        i.company_id as internship_company_id,
                        i.position as internship_position, 
                        i.description as internship_description,
                        i.location as internship_location,
                        i.dates as internship_dates,
                        c.name as company_name,
                        c.industry as company_industry,
                        c.website as company_website,
                        c.phone as company_phone,
                        c.address as company_address,
                        c.description as company_description,
                        s.name as student_name,
                        s.email as student_email,
                        s.phone as student_phone,
                        s.major as student_major,
                        s.gpa as student_gpa,
                        s.bio as student_bio,
                        s.profile_pic as student_profile_pic
                    FROM applications a
                    JOIN internships i ON a.internship_id = i.id
                    JOIN companies c ON i.company_id = c.id
                    JOIN students s ON a.student_id = s.id
                    WHERE a.application_id = ?
                ");
                $stmt->execute([$id]);
                $application = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($application) {
                    // Authorization check: Ensure the current user is the student who owns the application or an admin
                    $currentUser = getCurrentUserId();
                    $userRole = getCurrentUserRole();
                    $isStudentOwner = ($userRole === ROLE_STUDENT && $application['student_id'] == $currentUser);
                    $isCompanyOwner = ($userRole === ROLE_COMPANY && $application['internship_company_id'] == $currentUser);
                    $isAdmin = ($userRole === ROLE_ADMIN);
                    if (!$isStudentOwner && !$isCompanyOwner && !$isAdmin) {
                        http_response_code(403);
                        echo json_encode(['error' => 'Forbidden: You do not have access to this application.']);
                        exit;
                    }
                    $application['documents'] = get_application_documents($application['internal_id']);
                    $resume = get_primary_resume($application['documents']);
                    if ($resume) {
                        $application['resume_download_url'] = $resume['download_url'];
                        $application['resume_file_name'] = $resume['file_name'];
                    }
                    unset($application['internal_id']);
                    echo json_encode($application);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => "Application with ID {$id} not found."]);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            }
        } elseif ($student_id_param !== null) { // Get applications for a specific student: ?entity=applications&student_id=1
            requireRole(ROLE_STUDENT);
            if (getCurrentUserId() != $student_id_param) {
                http_response_code(403);
                echo json_encode(['error' => 'You are not authorized to view these applications.']);
                exit;
            }

            try {
                $stmt = $db->prepare("
                    SELECT 
                        a.application_id, 
                        a.status, 
                        a.applied_date as created_at, 
                        a.cover_letter,
                        i.position as internship_position, 
                        i.location as internship_location,
                        i.dates as internship_dates,
                        c.name as company_name
                    FROM applications a
                    JOIN internships i ON a.internship_id = i.id
                    JOIN companies c ON i.company_id = c.id
                    WHERE a.student_id = ?
                    ORDER BY a.applied_date DESC
                ");
                $stmt->execute([$student_id_param]);
                $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($applications);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            }
        } elseif ($company_id_param !== null) { // Get applications for a specific company
            requireRole(ROLE_COMPANY);
            if (getCurrentUserId() != $company_id_param) {
                http_response_code(403);
                echo json_encode(['error' => 'You are not authorized to view these applications.']);
                exit;
            }

            try {
                $stmt = $db->prepare("
                    SELECT 
                        a.id AS internal_id,
                        a.application_id, 
                        a.status, 
                        a.applied_date, 
                        a.cover_letter,
                        a.offer_details,
                        i.position as internship_position, 
                        i.location as internship_location,
                        i.dates as internship_dates,
                        i.description as internship_description,
                        s.name as student_name,
                        s.major as student_major,
                        s.email as student_email,
                        s.phone as student_phone,
                        s.gpa as student_gpa,
                        s.bio as student_bio,
                        s.profile_pic as student_profile_pic
                    FROM applications a
                    JOIN internships i ON a.internship_id = i.id
                    JOIN students s ON a.student_id = s.id
                    WHERE i.company_id = ?
                    ORDER BY a.applied_date DESC
                ");
                $stmt->execute([$company_id_param]);
                $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($applications as &$application) {
                    $docs = get_application_documents($application['internal_id']);
                    $application['documents'] = $docs;
                    $resume = get_primary_resume($docs);
                    if ($resume) {
                        $application['resume_download_url'] = $resume['download_url'];
                        $application['resume_file_name'] = $resume['file_name'];
                    }
                    unset($application['internal_id']);
                }
                echo json_encode($applications);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            }
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
            // Require student role
            requireRole(ROLE_STUDENT);
            
            // Use authenticated student's ID
            $student_id = getCurrentUserId();
            
            // Expected input: {"internship_id": 1, "cover_letter": "My letter"}
            if (!isset($input['internship_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing internship_id.']);
                exit;
            }
            
            // Check if internship exists
            $stmt = $db->prepare("SELECT * FROM internships WHERE id = ?");
            $stmt->execute([$input['internship_id']]);
            $internship = $stmt->fetch();
            
            if (!$internship) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid internship_id.']);
                exit;
            }

            // Ensure student has not already applied
            $stmt = $db->prepare("SELECT COUNT(*) AS count FROM applications WHERE student_id = ? AND internship_id = ?");
            $stmt->execute([$student_id, $input['internship_id']]);
            if ($stmt->fetch()['count'] > 0) {
                http_response_code(409);
                echo json_encode(['error' => 'You have already applied for this internship.']);
                exit;
            }
            
            // Generate application_id
            $stmt = $db->query("SELECT COUNT(*) as count FROM applications");
            $count = $stmt->fetch()['count'];
            $new_app_id = "APP" . str_pad($count + 100, 3, "0", STR_PAD_LEFT);
            
            // Insert into database
            $stmt = $db->prepare("INSERT INTO applications (application_id, student_id, internship_id, status, cover_letter, applied_date) VALUES (?, ?, ?, 'Pending', ?, CURDATE())");
            $stmt->execute([$new_app_id, $student_id, $input['internship_id'], $input['cover_letter'] ?? '']);
            $newPrimaryId = $db->lastInsertId();

            // Link selected documents to this application
            if (!empty($input['document_ids']) && is_array($input['document_ids'])) {
                $docStmt = $db->prepare("UPDATE documents SET application_id = ? WHERE document_id = ? AND student_id = ?");
                foreach ($input['document_ids'] as $docId) {
                    $docStmt->execute([$newPrimaryId, $docId, $student_id]);
                }
            }
            
            // Return the new application
            $new_application = [
                'application_id' => $new_app_id,
                'student_id' => $student_id,
                'internship_id' => $input['internship_id'],
                'company_name' => $internship['company_name'],
                'position' => $internship['position'],
                'status' => 'Pending', 
                'applied_date' => date('Y-m-d'),
                'cover_letter' => $input['cover_letter'] ?? ''
            ];
            
            http_response_code(201); // Created
            echo json_encode([
                'status' => 'success',
                'message' => 'Application submitted successfully.',
                'data' => $new_application
            ]);
        }
        elseif ($id !== null && $action === 'withdraw') {
            // Require student role
            requireRole(ROLE_STUDENT);
            
            try {
                // Fetch application from database
                $stmt = $db->prepare("SELECT * FROM applications WHERE application_id = ? AND student_id = ?");
                $stmt->execute([$id, getCurrentUserId()]);
                $application = $stmt->fetch();
                
                if (!$application) {
                    http_response_code(404);
                    echo json_encode(['error' => "Application {$id} not found."]);
                    exit;
                }
                
                if ($application['status'] !== 'Withdrawn' && $application['status'] !== 'Confirmed_By_Student' && $application['status'] !== 'Approved_By_Company') {
                    // Update status to Withdrawn
                    $stmt = $db->prepare("UPDATE applications SET status = 'Withdrawn', status_updated_date = NOW() WHERE application_id = ?");
                    $stmt->execute([$id]);
                    
                    // Fetch updated application
                    $stmt = $db->prepare("SELECT * FROM applications WHERE application_id = ?");
                    $stmt->execute([$id]);
                    $updated_application = $stmt->fetch();
                    
                    echo json_encode(['message' => "Application {$id} withdrawn successfully.", 'application' => $updated_application]);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => "Application {$id} cannot be withdrawn (current status: {$application['status']})."]);
                }
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to withdraw application: ' . $e->getMessage()]);
            }
        }
        elseif ($id !== null && $action === 'confirm_offer') {
            // Require student role
            requireRole(ROLE_STUDENT);
            
            try {
                // Fetch application from database
                $stmt = $db->prepare("SELECT * FROM applications WHERE application_id = ? AND student_id = ?");
                $stmt->execute([$id, getCurrentUserId()]);
                $application = $stmt->fetch();
                
                if (!$application) {
                    http_response_code(404);
                    echo json_encode(['error' => "Application {$id} not found."]);
                    exit;
                }
                
                if ($application['status'] === 'Offered') {
                    // Update status to Confirmed_By_Student
                    $stmt = $db->prepare("UPDATE applications SET status = 'Confirmed_By_Student', status_updated_date = NOW() WHERE application_id = ?");
                    $stmt->execute([$id]);
                    
                    // Fetch updated application
                    $stmt = $db->prepare("SELECT * FROM applications WHERE application_id = ?");
                    $stmt->execute([$id]);
                    $updated_application = $stmt->fetch();
                    
                    echo json_encode(['message' => "Application {$id} offer confirmed successfully by student.", 'application' => $updated_application]);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => "Application {$id} cannot be confirmed. Status must be 'Offered'. Current status: {$application['status']}."]);
                }
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to confirm offer: ' . $e->getMessage()]);
            }
        }
        // Company actions on applications: e.g. offer, reject
        elseif ($id !== null && $action === 'update_status_company') { // Example: ?entity=applications&id=APP001&action=update_status_company
            // Require company role
            requireRole(ROLE_COMPANY);
            
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
            
            try {
                // Update in database
                $stmt = $db->prepare("UPDATE applications SET status = ?, offer_details = ?, status_updated_date = NOW() WHERE application_id = ?");
                $stmt->execute([
                    $input['status'],
                    $input['offer_details'] ?? null,
                    $id
                ]);
                
                // Fetch updated application
                $stmt = $db->prepare("SELECT * FROM applications WHERE application_id = ?");
                $stmt->execute([$id]);
                $updated_application = $stmt->fetch();
                
                if (!$updated_application) {
                    http_response_code(404);
                    echo json_encode(['error' => "Application {$id} not found."]);
                    exit;
                }
                
                echo json_encode(['message' => "Application {$id} status updated to {$input['status']} by company.", 'application' => $updated_application]);
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update application: ' . $e->getMessage()]);
            }
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
