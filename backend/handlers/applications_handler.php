<?php

// backend/handlers/applications_handler.php

global $applications, $internships, $method, $entity, $id, $action, $student_id_param, $input;

// Get database connection
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/rounds_handler.php';
require_once __DIR__ . '/quotas_handler.php';
$db = getDB();

function format_document_download_url($path, $document_id = null) {
    // Prefer secure download endpoint so BLOBs (file_content) also work
    if ($document_id) {
        return "/index.php?entity=applications&action=download_document&document_id={$document_id}";
    }
    if (!$path) {
        return null;
    }
    $normalized = str_replace('\\', '/', $path);
    return '/' . ltrim($normalized, '/');
}

function get_application_documents($application_primary_id) {
    global $db;
    try {
        $stmt = $db->prepare("SELECT document_id, document_type, file_name, file_path, file_size, upload_date, student_id, file_content
            FROM documents
            WHERE application_id = ?
            ORDER BY upload_date DESC, id DESC");
        $stmt->execute([$application_primary_id]);
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($documents as &$doc) {
            $doc['download_url'] = format_document_download_url($doc['file_path'], $doc['document_id'] ?? null);
            // Don't leak BLOB in listing
            unset($doc['file_content']);
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

function normalize_status($status) {
    if (!$status) {
        return 'Pending';
    }
    // Map database values to clean display names
    // Since we can't ALTER the ENUM, we keep database values but map for display
    $map = array(
        'Confirmed_By_Student' => 'Confirmed',
        'Approved_By_Company' => 'Finalized'
    );
    return isset($map[$status]) ? $map[$status] : $status;
}

function generate_application_code(PDO $db) {
    do {
        $code = 'APP' . random_int(100000, 999999);
        $stmt = $db->prepare('SELECT COUNT(*) FROM applications WHERE application_id = ?');
        $stmt->execute([$code]);
        $exists = $stmt->fetchColumn();
    } while ($exists);

    return $code;
}

if ($entity === 'applications') {
    if ($method === 'GET') {
        if (isset($_GET['action']) && $_GET['action'] === 'download_document') {
            // Secure document download for applications (students, companies, admins)
            requireAuth();
            $document_id = $_GET['document_id'] ?? null;
            if (!$document_id) {
                http_response_code(400);
                echo json_encode(['error' => 'document_id is required']);
                exit;
            }

            try {
                $stmt = $db->prepare("
                    SELECT d.*, a.application_id, a.student_id, i.company_id
                    FROM documents d
                    LEFT JOIN applications a ON d.application_id = a.id
                    LEFT JOIN internships i ON a.internship_id = i.id
                    WHERE d.document_id = ?
                ");
                $stmt->execute([$document_id]);
                $document = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$document) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Document not found']);
                    exit;
                }

                $currentUser = getCurrentUserId();
                $role = getCurrentUserRole();
                $ownsAsStudent = ($role === ROLE_STUDENT && $document['student_id'] == $currentUser);
                $ownsAsCompany = ($role === ROLE_COMPANY && $document['company_id'] && $document['company_id'] == $currentUser);
                $isAdmin = ($role === ROLE_ADMIN);
                if (!$ownsAsStudent && !$ownsAsCompany && !$isAdmin) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Forbidden']);
                    exit;
                }

                // Serve file_content if present, else fallback to file_path
                $file_name = $document['file_name'] ?: 'document';
                $file_size = $document['file_size'] ?? 0;
                $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $mime_types = [
                    'pdf' => 'application/pdf',
                    'doc' => 'application/msword',
                    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png' => 'image/png'
                ];
                $mime_type = $mime_types[$extension] ?? 'application/octet-stream';

                while (ob_get_level()) {
                    ob_end_clean();
                }

                if (!empty($document['file_content'])) {
                    $file_content = $document['file_content'];
                    $file_size = strlen($file_content);
                    header('Content-Type: ' . $mime_type);
                    header('Content-Disposition: attachment; filename="' . addslashes($file_name) . '"');
                    header('Content-Length: ' . $file_size);
                    header('Cache-Control: private, max-age=0, must-revalidate');
                    header('Pragma: public');
                    echo $file_content;
                    exit;
                }

                if (!empty($document['file_path'])) {
                    $backend_root = dirname(__DIR__);
                    $file_path = $backend_root . '/' . ltrim($document['file_path'], '/');
                    if (!file_exists($file_path)) {
                        http_response_code(404);
                        echo json_encode(['error' => 'File not found on server']);
                        exit;
                    }
                    header('Content-Type: ' . $mime_type);
                    header('Content-Disposition: attachment; filename="' . addslashes($file_name) . '"');
                    header('Content-Length: ' . filesize($file_path));
                    header('Cache-Control: private, max-age=0, must-revalidate');
                    header('Pragma: public');
                    readfile($file_path);
                    exit;
                }

                http_response_code(404);
                echo json_encode(['error' => 'File content not available']);
                exit;
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to download document: ' . $e->getMessage()]);
                exit;
            }
        }

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
                    $application['status'] = normalize_status($application['status']);
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
                // Support term filtering
                $term_id = $_GET['term_id'] ?? null;
                $term_condition = $term_id ? " AND a.term_id = ?" : "";
                $params = [$student_id_param];
                if ($term_id) {
                    $params[] = $term_id;
                }
                
                $stmt = $db->prepare("
                    SELECT 
                        a.application_id, 
                        a.status,
                        a.offer_details,
                        a.applied_date as created_at, 
                        a.cover_letter,
                        a.term_id,
                        a.round_id,
                        t.name as term_name,
                        r.name as round_name,
                        i.position as internship_position, 
                        i.location as internship_location,
                        i.dates as internship_dates,
                        c.name as company_name
                    FROM applications a
                    JOIN internships i ON a.internship_id = i.id
                    JOIN companies c ON i.company_id = c.id
                    LEFT JOIN terms t ON a.term_id = t.id
                    LEFT JOIN application_rounds r ON a.round_id = r.id
                    WHERE a.student_id = ?{$term_condition}
                    ORDER BY a.applied_date DESC
                ");
                $stmt->execute($params);
                $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($applications as &$application) {
                    $application['status'] = normalize_status($application['status']);
                }
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
                $status_param = $_GET['status'] ?? null;
                $stmt = $db->prepare("
                    SELECT 
                        a.id AS internal_id,
                        a.application_id, 
                        i.id as internship_id,
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
                    WHERE i.company_id = ?" . ($status_param ? " AND a.status = ?" : "") . "
                    ORDER BY a.applied_date DESC
                ");
                $params = [$company_id_param];
                if ($status_param) {
                    $params[] = $status_param;
                }
                $stmt->execute($params);
                $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($applications as &$application) {
                    $docs = get_application_documents($application['internal_id']);
                    $application['documents'] = $docs;
                    $resume = get_primary_resume($docs);
                    if ($resume) {
                        $application['resume_download_url'] = $resume['download_url'];
                        $application['resume_file_name'] = $resume['file_name'];
                    }
                    $application['status'] = normalize_status($application['status']);
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
        else { // Get all applications (admin view)
            requireRole(ROLE_ADMIN);
            try {
                $stmt = $db->prepare("
                    SELECT 
                        a.id AS internal_id,
                        a.application_id,
                        a.student_id,
                        a.internship_id,
                        a.status,
                        a.applied_date as created_at,
                        a.cover_letter,
                        a.offer_details,
                        i.company_id,
                        i.position as internship_position,
                        i.location as internship_location,
                        c.name as company_name,
                        c.logo as company_logo,
                        s.name as student_name,
                        s.email as student_email,
                        s.profile_pic as student_profile_pic
                    FROM applications a
                    JOIN internships i ON a.internship_id = i.id
                    JOIN companies c ON i.company_id = c.id
                    JOIN students s ON a.student_id = s.id
                    ORDER BY a.applied_date DESC
                ");
                $stmt->execute();
                $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($applications as &$application) {
                    $application['status'] = normalize_status($application['status']);
                }
                echo json_encode($applications);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            }
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
            
            try {
                $db->beginTransaction();

                // Check for active round
                $active_round = get_active_round();
                if (!$active_round) {
                    // Fallback: Check if any rounds exist at all
                    $roundsCheck = $db->query("SELECT COUNT(*) FROM application_rounds")->fetchColumn();
                    if ($roundsCheck > 0) {
                        // Rounds exist but none are active - applications are closed
                        $db->rollBack();
                        http_response_code(403);
                        echo json_encode(['error' => 'No active application round. Applications are currently closed. Please contact an administrator to activate an application round.']);
                        exit;
                    }
                    // No rounds exist yet - allow applications without round (backward compatibility)
                    // This allows the system to work before rounds are set up
                    $active_round = null;
                }

                // Get term for today
                $current_term = get_term_for_date();
                if (!$current_term) {
                    $db->rollBack();
                    http_response_code(403);
                    echo json_encode(['error' => 'No active term found.']);
                    exit;
                }

                $stmt = $db->prepare("SELECT id, company_name, position FROM internships WHERE id = ? AND status != 'Deleted'");
                $stmt->execute([$input['internship_id']]);
                $internship = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$internship) {
                    $db->rollBack();
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid internship_id.']);
                    exit;
                }

                $stmt = $db->prepare("SELECT COUNT(*) AS count FROM applications WHERE student_id = ? AND internship_id = ?");
                $stmt->execute([$student_id, $input['internship_id']]);
                if ($stmt->fetch()['count'] > 0) {
                    $db->rollBack();
                    http_response_code(409);
                    echo json_encode(['error' => 'You have already applied for this internship.']);
                    exit;
                }

                // Enforce max applications per student from active round (if round exists)
                if ($active_round) {
                    $activeStmt = $db->prepare("
                        SELECT COUNT(*) AS active_count
                        FROM applications
                        WHERE student_id = ?
                          AND round_id = ?
                          AND status NOT IN ('Rejected', 'Withdrawn', 'Approved_By_Company', 'Finalized')
                    ");
                    $activeStmt->execute([$student_id, $active_round['id']]);
                    $activeCount = (int) $activeStmt->fetchColumn();
                    $maxApps = (int) $active_round['max_applications_per_student'];
                    if ($activeCount >= $maxApps) {
                        $db->rollBack();
                        http_response_code(400);
                        echo json_encode([
                            'error' => "You have reached the limit of {$maxApps} applications for this round. Withdraw or wait for decisions to free slots.",
                            'max_applications' => $maxApps,
                            'current_count' => $activeCount
                        ]);
                        exit;
                    }
                }

                $new_app_id = generate_application_code($db);

                $stmt = $db->prepare("INSERT INTO applications (application_id, student_id, internship_id, term_id, round_id, status, cover_letter, applied_date) VALUES (?, ?, ?, ?, ?, 'Pending', ?, CURDATE())");
                $stmt->execute([
                    $new_app_id, 
                    $student_id, 
                    $input['internship_id'], 
                    $current_term ? $current_term['id'] : null,
                    $active_round ? $active_round['id'] : null,
                    $input['cover_letter'] ?? ''
                ]);
                $newPrimaryId = $db->lastInsertId();

                if (!empty($input['document_ids']) && is_array($input['document_ids'])) {
                    $docStmt = $db->prepare("UPDATE documents SET application_id = ? WHERE document_id = ? AND student_id = ?");
                    foreach ($input['document_ids'] as $docId) {
                        $docStmt->execute([$newPrimaryId, $docId, $student_id]);
                    }
                }

                $db->commit();

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

                http_response_code(201);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Application submitted successfully.',
                    'data' => $new_application
                ]);
            } catch (PDOException $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }

                if ($e->getCode() === '23000') {
                    http_response_code(409);
                    echo json_encode(['error' => 'You have already applied for this internship.']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to submit application: ' . $e->getMessage()]);
                }
            }
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
                
                $rawStatus = $application['status'];
                $normalizedStatus = normalize_status($rawStatus);
                // Block withdraw only after finalization or rejection
                $blockedWithdrawStatuses = array('Finalized', 'Approved_By_Company', 'Rejected');

                if (!in_array($rawStatus, $blockedWithdrawStatuses, true) && !in_array($normalizedStatus, $blockedWithdrawStatuses, true) && $normalizedStatus !== 'Withdrawn') {
                    // Update status to Withdrawn
                    $stmt = $db->prepare("UPDATE applications SET status = 'Withdrawn', status_updated_date = NOW() WHERE application_id = ?");
                    $stmt->execute([$id]);
                    
                    // Fetch updated application
                    $stmt = $db->prepare("SELECT * FROM applications WHERE application_id = ?");
                    $stmt->execute([$id]);
                    $updated_application = $stmt->fetch();
                    
                    $updated_application['status'] = normalize_status($updated_application['status']);
                    echo json_encode(['status' => 'success', 'message' => "Application {$id} withdrawn successfully.", 'application' => $updated_application]);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => "Application {$id} cannot be withdrawn (current status: {$application['status']})."]);
                }
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to withdraw application: ' . $e->getMessage()]);
            }
        }
        elseif ($id !== null && ($action === 'confirm_offer' || $action === 'confirm')) {
            requireRole(ROLE_STUDENT);
            
            try {
                $stmt = $db->prepare("SELECT * FROM applications WHERE application_id = ? AND student_id = ?");
                $stmt->execute([$id, getCurrentUserId()]);
                $application = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$application) {
                    http_response_code(404);
                    echo json_encode(['error' => "Application {$id} not found."]);
                    exit;
                }
                
                // Check raw database status, not normalized
                $rawStatus = $application['status'];
                $currentStatus = normalize_status($rawStatus);
                
                // Student can confirm if status is 'Accepted'
                if ($rawStatus === 'Accepted' || $currentStatus === 'Accepted') {
                    // Ensure student has no other confirmed/finalized placement
                    $limitStmt = $db->prepare("
                        SELECT COUNT(*) FROM applications 
                        WHERE student_id = ? 
                          AND application_id <> ? 
                          AND status IN ('Confirmed_By_Student', 'Approved_By_Company', 'Finalized')
                    ");
                    $limitStmt->execute([getCurrentUserId(), $id]);
                    $alreadyConfirmed = (int) $limitStmt->fetchColumn();
                    if ($alreadyConfirmed > 0) {
                        http_response_code(400);
                        echo json_encode(['error' => 'You already have a confirmed or finalized internship. Withdraw or wait for rejection before confirming another.']);
                        exit;
                    }

                    // Use database value since we can't ALTER ENUM
                    $stmt = $db->prepare("UPDATE applications SET status = 'Confirmed_By_Student', status_updated_date = NOW() WHERE application_id = ?");
                    $stmt->execute([$id]);
                    
                    $stmt = $db->prepare("SELECT * FROM applications WHERE application_id = ?");
                    $stmt->execute([$id]);
                    $updated_application = $stmt->fetch(PDO::FETCH_ASSOC);
                    $updated_application['status'] = normalize_status($updated_application['status']);
                    echo json_encode([
                        'status' => 'success', 
                        'message' => "Application confirmed successfully. Company can now finalize.",
                        'application' => $updated_application
                    ]);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => "Application cannot be confirmed. Status must be 'Accepted'. Current status: '{$currentStatus}'."]);
                }
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to confirm application: ' . $e->getMessage()]);
            }
        }
        // Company actions on applications: accept, reject, finalize
        elseif ($id !== null && $action === 'update_status_company') {
            requireRole(ROLE_COMPANY);
            
            if (!isset($input['status'])) {
                http_response_code(400);
                echo json_encode(['error' => "Missing status in request body."]);
                exit;
            }
            
            try {
                // Get current application status
                $stmt = $db->prepare("SELECT a.*, i.company_id FROM applications a JOIN internships i ON a.internship_id = i.id WHERE a.application_id = ?");
                $stmt->execute([$id]);
                $application = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$application) {
                    http_response_code(404);
                    echo json_encode(['error' => "Application {$id} not found."]);
                    exit;
                }
                
                // Verify company owns this internship
                if ($application['company_id'] != getCurrentUserId()) {
                    http_response_code(403);
                    echo json_encode(['error' => "Access denied. You don't own this internship."]);
                    exit;
                }
                
                $currentStatus = normalize_status($application['status']);
                $newStatus = $input['status'];
                
                // Define valid status transitions for company (using clean status names)
                $validTransitions = array(
                    'Pending' => array('Accepted', 'Rejected'),
                    'Accepted' => array(), // Company cannot change Accepted - student must confirm
                    'Confirmed' => array('Finalized'), // Company can finalize after student confirms
                    'Confirmed_By_Student' => array('Finalized'), // Database value - company can finalize
                    'Finalized' => array(), // Finalized is final
                    'Approved_By_Company' => array(), // Database value - finalized is final
                    'Rejected' => array(), // Rejected is final
                    'Withdrawn' => array() // Withdrawn is final
                );
                
                // Check if transition is valid
                $allowedNextStatuses = isset($validTransitions[$currentStatus]) ? $validTransitions[$currentStatus] : array();
                
                // No mapping needed - statuses are clean
                $dbStatus = $newStatus;
                
                // Check if transition is valid
                if (!in_array($dbStatus, $allowedNextStatuses)) {
                http_response_code(400);
                    $allowedStr = !empty($allowedNextStatuses) ? implode(', ', $allowedNextStatuses) : 'none (this status is final)';
                    echo json_encode([
                        'error' => "Invalid status transition. Current status: '{$currentStatus}'. Allowed next statuses: {$allowedStr}."
                    ]);
                exit;
            }
            
                // Check company quota when finalizing
                if ($dbStatus === 'Approved_By_Company' || $dbStatus === 'Finalized') {
                    if (!empty($application['round_id'])) {
                        $effective_quota = get_effective_quota($application['company_id'], $application['round_id']);
                        $used_quota = get_used_quota($application['company_id'], $application['round_id']);
                        
                        // If this application is already finalized, don't count it again
                        if ($application['status'] === 'Approved_By_Company' || $application['status'] === 'Finalized') {
                            $used_quota--; // Don't double-count
                        }
                        
                        if ($used_quota >= $effective_quota) {
                            http_response_code(403);
                            echo json_encode([
                                'error' => "Company quota reached. You have finalized {$used_quota} out of {$effective_quota} allowed placements for this round.",
                                'quota' => $effective_quota,
                                'used' => $used_quota
                            ]);
                            exit;
                        }
                    }
                }
                
                // Update in database using ENUM value
                $stmt = $db->prepare("UPDATE applications SET status = ?, offer_details = ?, status_updated_date = NOW() WHERE application_id = ?");
                $stmt->execute([
                    $dbStatus,
                    $input['offer_details'] ?? null,
                    $id
                ]);
                
                // Fetch updated application
                $stmt = $db->prepare("SELECT * FROM applications WHERE application_id = ?");
                $stmt->execute([$id]);
                $updated_application = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $updated_application['status'] = normalize_status($updated_application['status']);
                echo json_encode([
                    'status' => 'success', 
                    'message' => "Application {$id} status updated to {$newStatus}.",
                    'application' => $updated_application
                ]);
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update application: ' . $e->getMessage()]);
            }
        }
        // Company accept action (shortcut)
        elseif ($id !== null && $action === 'accept') {
            requireRole(ROLE_COMPANY);
            
            try {
                $stmt = $db->prepare("SELECT a.*, i.company_id FROM applications a JOIN internships i ON a.internship_id = i.id WHERE a.application_id = ?");
                $stmt->execute([$id]);
                $application = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$application) {
                    http_response_code(404);
                    echo json_encode(['error' => "Application {$id} not found."]);
                    exit;
                }
                
                if ($application['company_id'] != getCurrentUserId()) {
                    http_response_code(403);
                    echo json_encode(['error' => "Access denied."]);
                    exit;
                }
                
                $currentStatus = normalize_status($application['status']);
                if ($currentStatus !== 'Pending') {
                    http_response_code(400);
                    echo json_encode(['error' => "Can only accept applications with status 'Pending'. Current status: '{$currentStatus}'."]);
                    exit;
                }
                
                $stmt = $db->prepare("UPDATE applications SET status = 'Accepted', status_updated_date = NOW() WHERE application_id = ?");
                $stmt->execute([$id]);
                
                $stmt = $db->prepare("SELECT * FROM applications WHERE application_id = ?");
                $stmt->execute([$id]);
                $updated = $stmt->fetch(PDO::FETCH_ASSOC);
                $updated['status'] = normalize_status($updated['status']);
                
                echo json_encode(['status' => 'success', 'message' => 'Application accepted successfully.', 'application' => $updated]);
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to accept application: ' . $e->getMessage()]);
            }
        }
        // Company reject action (shortcut)
        elseif ($id !== null && $action === 'reject') {
            requireRole(ROLE_COMPANY);
            
            try {
                $stmt = $db->prepare("SELECT a.*, i.company_id FROM applications a JOIN internships i ON a.internship_id = i.id WHERE a.application_id = ?");
                $stmt->execute([$id]);
                $application = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$application) {
                    http_response_code(404);
                    echo json_encode(['error' => "Application {$id} not found."]);
                    exit;
                }
                
                if ($application['company_id'] != getCurrentUserId()) {
                    http_response_code(403);
                    echo json_encode(['error' => "Access denied."]);
                    exit;
                }
                
                $currentStatus = normalize_status($application['status']);
                if ($currentStatus !== 'Pending') {
                    http_response_code(400);
                    echo json_encode(['error' => "Can only reject applications with status 'Pending'. Current status: '{$currentStatus}'."]);
                    exit;
                }
                
                $stmt = $db->prepare("UPDATE applications SET status = 'Rejected', status_updated_date = NOW() WHERE application_id = ?");
                $stmt->execute([$id]);
                
                $stmt = $db->prepare("SELECT * FROM applications WHERE application_id = ?");
                $stmt->execute([$id]);
                $updated = $stmt->fetch(PDO::FETCH_ASSOC);
                $updated['status'] = normalize_status($updated['status']);
                
                echo json_encode(['status' => 'success', 'message' => 'Application rejected.', 'application' => $updated]);
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to reject application: ' . $e->getMessage()]);
            }
        }
        // Company finalize action
        elseif ($id !== null && $action === 'finalize') {
            requireRole(ROLE_COMPANY);
            
            try {
                $stmt = $db->prepare("SELECT a.*, i.company_id FROM applications a JOIN internships i ON a.internship_id = i.id WHERE a.application_id = ?");
                $stmt->execute([$id]);
                $application = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$application) {
                    http_response_code(404);
                    echo json_encode(['error' => "Application {$id} not found."]);
                    exit;
                }
                
                if ($application['company_id'] != getCurrentUserId()) {
                    http_response_code(403);
                    echo json_encode(['error' => "Access denied."]);
                    exit;
                }
                
                // Check status
                $currentStatus = $application['status'];
                
                // Can finalize if status is 'Confirmed' (check both display and database values)
                $rawStatus = $application['status'];
                if ($currentStatus !== 'Confirmed' && $rawStatus !== 'Confirmed_By_Student') {
                    http_response_code(400);
                    echo json_encode(['error' => "Can only finalize applications with status 'Confirmed'. Current status: '{$currentStatus}'."]);
                    exit;
                }
                
                // Check company quota when finalizing
                if (!empty($application['round_id'])) {
                    $effective_quota = get_effective_quota($application['company_id'], $application['round_id']);
                    $used_quota = get_used_quota($application['company_id'], $application['round_id']);
                    
                    // If this application is already finalized, don't count it again
                    if ($application['status'] === 'Approved_By_Company' || $application['status'] === 'Finalized') {
                        $used_quota--; // Don't double-count
                    }
                    
                    if ($used_quota >= $effective_quota) {
                        http_response_code(403);
                        echo json_encode([
                            'error' => "Company quota reached. You have finalized {$used_quota} out of {$effective_quota} allowed placements for this round.",
                            'quota' => $effective_quota,
                            'used' => $used_quota
                        ]);
                        exit;
                    }
                }
                
                // Use database value since we can't ALTER ENUM
                $stmt = $db->prepare("UPDATE applications SET status = 'Approved_By_Company', status_updated_date = NOW() WHERE application_id = ?");
                $stmt->execute([$id]);
                
                $stmt = $db->prepare("SELECT * FROM applications WHERE application_id = ?");
                $stmt->execute([$id]);
                $updated = $stmt->fetch(PDO::FETCH_ASSOC);
                $updated['status'] = normalize_status($updated['status']);
                
                echo json_encode(['status' => 'success', 'message' => 'Application finalized successfully.', 'application' => $updated]);
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to finalize application: ' . $e->getMessage()]);
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
