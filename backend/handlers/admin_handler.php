<?php

// Admin Handler - Contains all business logic for admin-related API calls.

// Get database connection
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';
$db = getDB();

// --- Term Management --- //
function get_all_terms() {
    global $terms;
    return $terms;
}
function add_term($data) {
    global $terms;
    
    // Require admin role
    requireRole(ROLE_ADMIN);
    
    // Basic validation
    if (empty($data['name']) || empty($data['start_date']) || empty($data['end_date'])) {
        return ['error' => 'Missing required term data.'];
    }

    $new_term = [
        'id' => count($terms) + 1, // Simple unique ID
        'name' => $data['name'],
        'start_date' => $data['start_date'],
        'end_date' => $data['end_date']
    ];

    // In a real app, you would insert this into the database.
    return ['status' => 'success', 'message' => 'Term added successfully.', 'data' => $new_term];
}
function update_term($term_id, $data) {
    global $terms;
    
    // Require admin role
    requireRole(ROLE_ADMIN);
    
    foreach ($terms as &$term) {
        if ($term['id'] == $term_id) {
            $term = array_merge($term, $data);
            return ['status' => 'success', 'message' => 'Term updated successfully.', 'data' => $term];
        }
    }
    return ['error' => 'Term not found.'];
}
function delete_term($term_id) {
    global $terms;
    
    // Require admin role
    requireRole(ROLE_ADMIN);
    
    foreach ($terms as $key => $term) {
        if ($term['id'] == $term_id) {
            // In a real app, you would delete from the database.
            // unset($terms[$key]);
            return ['status' => 'success', 'message' => 'Term deleted successfully.'];
        }
    }
    return ['error' => 'Term not found.'];
}

// --- Student Management --- //
function get_all_students() {
    global $students;
    // Return as array of values (not associative array)
    return array_values($students);
}
function add_new_student($data) {
    global $students;
    
    // Require admin role
    requireRole(ROLE_ADMIN);
    
    // Basic validation
    if (empty($data['name']) || empty($data['email']) || empty($data['student_id'])) {
        return ['error' => 'Missing required student data.'];
    }

    $new_student = [
        'id' => count($students) + 1, // Simple unique ID
        'name' => $data['name'],
        'email' => $data['email'],
        'student_id' => $data['student_id'],
        'major' => $data['major'] ?? '',
        'gpa' => $data['gpa'] ?? 'N/A',
        'phone' => $data['phone'] ?? '',
        'address' => $data['address'] ?? '',
        'bio' => $data['bio'] ?? '',
        'profile_pic' => '/assets/images/demo/users/face' . (count($students) + 1) . '.jpg'
    ];

    return ['status' => 'success', 'message' => 'Student added successfully.', 'data' => $new_student];
}
function update_student($student_id, $data) {
    global $students;
    
    // Require admin role
    requireRole(ROLE_ADMIN);
    
    foreach ($students as &$student) {
        if ($student['id'] == $student_id) {
            $student = array_merge($student, $data);
            return ['status' => 'success', 'message' => 'Student updated successfully.', 'data' => $student];
        }
    }
    return ['error' => 'Student not found.'];
}
function delete_student($student_id) {
    global $students;
    
    // Require admin role
    requireRole(ROLE_ADMIN);
    
    foreach ($students as $key => $student) {
        if ($student['id'] == $student_id) {
            // unset($students[$key]);
            return ['status' => 'success', 'message' => 'Student deleted successfully.'];
        }
    }
    return ['error' => 'Student not found.'];
}
function get_student_details_admin($student_id) {
    global $students;
    foreach ($students as $student) {
        if ($student['id'] == $student_id) {
            return $student;
        }
    }
    return ['error' => 'Student not found'];
}
function impersonate_student($student_id) {
    global $db;
    
    // Require admin role
    requireRole(ROLE_ADMIN);
    
    if (!$student_id) {
        return ['error' => 'Student ID is required.'];
    }
    
    try {
        // Fetch student from database
        $stmt = $db->prepare("SELECT id, name, email, student_id, major, gpa, phone, address, bio, profile_pic, is_active FROM students WHERE id = ? AND is_active = 1");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student) {
            return ['error' => 'Student not found or inactive.'];
        }
        
        // Return student data for frontend to use
        return [
            'status' => 'success', 
            'message' => 'Impersonation started successfully.',
            'impersonated_user' => $student,
            'user_type' => 'student'
        ];
    } catch(PDOException $e) {
        error_log("Impersonate student error: " . $e->getMessage());
        return ['error' => 'Failed to impersonate student: ' . $e->getMessage()];
    }
}

// --- Company Management --- //
function get_all_companies() {
    global $companies;
    // Return as array of values (not associative array)
    return array_values($companies);
}
function add_new_company($data) {
    global $db;
    
    // Require admin role
    requireRole(ROLE_ADMIN);
    
    if (empty($data['name']) || empty($data['email'])) {
        return ['error' => 'Missing required company data.'];
    }

    try {
        // Hash password if provided
        $passwordHash = null;
        if (!empty($data['password'])) {
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        // Insert into database
        $stmt = $db->prepare("INSERT INTO companies (name, email, password_hash, industry, website, phone, address, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['name'],
            $data['email'],
            $passwordHash,
            $data['industry'] ?? null,
            $data['website'] ?? null,
            $data['phone'] ?? null,
            $data['address'] ?? null,
            $data['description'] ?? null
        ]);
        
        $newId = $db->lastInsertId();
        
        // Fetch the created company
        $stmt = $db->prepare("SELECT * FROM companies WHERE id = ?");
        $stmt->execute([$newId]);
        $new_company = $stmt->fetch();
        
        return ['status' => 'success', 'message' => 'Company added successfully.', 'data' => $new_company];
    } catch(PDOException $e) {
        return ['error' => 'Failed to create company: ' . $e->getMessage()];
    }
}
function get_company_details_admin($company_id) {
    global $companies;
    foreach ($companies as $company) {
        if ($company['id'] == $company_id) {
            return $company;
        }
    }
    return ['error' => 'Company not found'];
}
function update_company($company_id, $data) {
    global $companies;
    
    // Require admin role
    requireRole(ROLE_ADMIN);
    
    foreach ($companies as &$company) {
        if ($company['id'] == $company_id) {
            $company = array_merge($company, $data);
            return ['status' => 'success', 'message' => 'Company updated successfully.', 'data' => $company];
        }
    }
    return ['error' => 'Company not found.'];
}
function delete_company($company_id) {
    global $companies;
    
    // Require admin role
    requireRole(ROLE_ADMIN);
    
    foreach ($companies as $key => $company) {
        if ($company['id'] == $company_id) {
            // unset($companies[$key]);
            return ['status' => 'success', 'message' => 'Company deleted successfully.'];
        }
    }
    return ['error' => 'Company not found.'];
}
function impersonate_company($company_id) {
    global $db;
    
    // Require admin role
    requireRole(ROLE_ADMIN);
    
    if (!$company_id) {
        return ['error' => 'Company ID is required.'];
    }
    
    try {
        // Fetch company from database
        $stmt = $db->prepare("SELECT id, name, email, industry, website, phone, address, description, is_active FROM companies WHERE id = ? AND is_active = 1");
        $stmt->execute([$company_id]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$company) {
            return ['error' => 'Company not found or inactive.'];
        }
        
        // Return company data for frontend to use
        return [
            'status' => 'success', 
            'message' => 'Impersonation started successfully.',
            'impersonated_user' => $company,
            'user_type' => 'company'
        ];
    } catch(PDOException $e) {
        error_log("Impersonate company error: " . $e->getMessage());
        return ['error' => 'Failed to impersonate company: ' . $e->getMessage()];
    }
}

// --- Internship Management (Admin) --- //
function get_all_internships_admin() {
    global $internships;
    // Return as array of values (not associative array)
    return array_values($internships);
}
function get_internship_info_admin($internship_id) {
    global $internships;
    foreach ($internships as $internship) {
        if ($internship['id'] == $internship_id) {
            return $internship;
        }
    }
    return ['error' => 'Internship not found'];
}
function edit_internship_admin($internship_id, $data) {
    global $internships;
    foreach ($internships as &$internship) {
        if ($internship['id'] == $internship_id) {
            $internship = array_merge($internship, $data);
            return ['status' => 'success', 'message' => 'Internship updated successfully.', 'data' => $internship];
        }
    }
    return ['error' => 'Internship not found.'];
}
function delete_internship_admin($internship_id) {
    global $internships;
    foreach ($internships as $key => $internship) {
        if ($internship['id'] == $internship_id) {
            // unset($internships[$key]);
            return ['status' => 'success', 'message' => 'Internship deleted successfully.'];
        }
    }
    return ['error' => 'Internship not found.'];
}
function get_internship_applicants($internship_id) {
    global $applications, $students;
    $applicants = [];
    foreach ($applications as $application) {
        if ($application['internship_id'] == $internship_id) {
            // Find student details
            $student_details = null;
            foreach ($students as $student) {
                if ($student['id'] == $application['student_id']) {
                    $student_details = $student;
                    break;
                }
            }
            $application['student'] = $student_details;
            $applicants[] = $application;
        }
    }
    return $applicants;
}

// --- Application Management (Admin) --- //

function get_all_applications_admin() {
    global $db;
    requireRole(ROLE_ADMIN);
    
    try {
        $stmt = $db->query("
            SELECT 
                a.id,
                a.application_id,
                a.student_id,
                a.internship_id,
                a.cover_letter,
                a.status,
                a.created_at,
                a.status_updated_date,
                s.name as student_name,
                s.email as student_email,
                s.profile_pic as student_profile_pic,
                s.major as student_major,
                i.position as internship_position,
                i.company_id,
                i.company_name,
                i.location,
                c.name as company_full_name,
                c.logo as company_logo
            FROM applications a
            JOIN students s ON a.student_id = s.id
            JOIN internships i ON a.internship_id = i.id
            LEFT JOIN companies c ON i.company_id = c.id
            ORDER BY a.created_at DESC
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Failed to fetch all applications: " . $e->getMessage());
        return ['error' => 'Failed to fetch applications: ' . $e->getMessage()];
    }
}

function get_application_details_admin($application_id) {
    global $db;
    requireRole(ROLE_ADMIN);
    
    try {
        $stmt = $db->prepare("
            SELECT 
                a.*,
                s.name as student_name,
                s.email as student_email,
                s.student_id as student_number,
                s.major as student_major,
                s.gpa as student_gpa,
                s.profile_pic as student_profile_pic,
                i.position as internship_position,
                i.company_id,
                i.company_name,
                i.location as internship_location,
                i.dates as internship_dates,
                i.description as internship_description,
                c.name as company_full_name,
                c.logo as company_logo,
                c.industry as company_industry
            FROM applications a
            JOIN students s ON a.student_id = s.id
            JOIN internships i ON a.internship_id = i.id
            LEFT JOIN companies c ON i.company_id = c.id
            WHERE a.application_id = ? OR a.id = ?
        ");
        $stmt->execute([$application_id, $application_id]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$application) {
            return ['error' => 'Application not found.'];
        }
        
        // Structure the response with nested objects for frontend compatibility
        $result = $application;
        $result['student'] = [
            'id' => $application['student_id'],
            'name' => $application['student_name'],
            'email' => $application['student_email'],
            'student_id' => $application['student_number'],
            'major' => $application['student_major'],
            'gpa' => $application['student_gpa']
        ];
        $result['internship'] = [
            'id' => $application['internship_id'],
            'position' => $application['internship_position'],
            'location' => $application['internship_location'],
            'dates' => $application['internship_dates'],
            'description' => $application['internship_description'],
            'company' => [
                'id' => $application['company_id'],
                'name' => $application['company_full_name'] ?? $application['company_name'],
                'logo' => $application['company_logo'],
                'industry' => $application['company_industry']
            ]
        ];
        
        return $result;
    } catch(PDOException $e) {
        error_log("Failed to fetch application details: " . $e->getMessage());
        return ['error' => 'Failed to fetch application details: ' . $e->getMessage()];
    }
}
function approve_application($application_id) {
    global $applications;
    foreach ($applications as &$application) {
        if ($application['application_id'] == $application_id) {
            $application['status'] = 'Approved';
            return ['status' => 'success', 'message' => 'Application approved.', 'data' => $application];
        }
    }
    return ['error' => 'Application not found.'];
}
function reject_application($application_id) {
    global $applications;
    foreach ($applications as &$application) {
        if ($application['application_id'] == $application_id) {
            $application['status'] = 'Rejected';
            return ['status' => 'success', 'message' => 'Application rejected.', 'data' => $application];
        }
    }
    return ['error' => 'Application not found.'];
}

// --- Dashboard Stats --- //
function get_admin_dashboard_stats() {
    global $db;
    requireRole(ROLE_ADMIN);
    
    try {
        // Get counts from database
        $stats = [];
        
        // Total students
        $stmt = $db->query("SELECT COUNT(*) as count FROM students WHERE is_active = 1");
        $stats['total_students'] = (int)$stmt->fetch()['count'];
        
        // Total companies
        $stmt = $db->query("SELECT COUNT(*) as count FROM companies WHERE is_active = 1");
        $stats['total_companies'] = (int)$stmt->fetch()['count'];
        
        // Total internships
        $stmt = $db->query("SELECT COUNT(*) as count FROM internships WHERE status != 'Deleted'");
        $stats['total_internships'] = (int)$stmt->fetch()['count'];
        
        // Total applications
        $stmt = $db->query("SELECT COUNT(*) as count FROM applications");
        $stats['total_applications'] = (int)$stmt->fetch()['count'];
        
        // Pending applications
        $stmt = $db->query("SELECT COUNT(*) as count FROM applications WHERE status IN ('Pending', 'Pending Review', 'Under Review')");
        $stats['pending_applications'] = (int)$stmt->fetch()['count'];
        
        // Approved/Finalized applications
        $stmt = $db->query("SELECT COUNT(*) as count FROM applications WHERE status IN ('Approved_By_Company', 'Confirmed_By_Student')");
        $stats['finalized_applications'] = (int)$stmt->fetch()['count'];
        
        return $stats;
    } catch(PDOException $e) {
        return ['error' => 'Failed to fetch dashboard stats: ' . $e->getMessage()];
    }
}

function get_admin_activity_feed($limit = 10) {
    global $db;
    requireRole(ROLE_ADMIN);
    
    try {
        $activities = array();
        $limitInt = (int)$limit;
        
        $query1 = "SELECT a.*, s.name as student_name, i.position as internship_position, c.name as company_name FROM applications a LEFT JOIN students s ON a.student_id = s.id LEFT JOIN internships i ON a.internship_id = i.id LEFT JOIN companies c ON i.company_id = c.id ORDER BY a.created_at DESC LIMIT " . $limitInt;
        $stmt = $db->query($query1);
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($applications as $app) {
            $msg = $app['student_name'] . ' applied for ' . $app['internship_position'] . ' at ' . $app['company_name'];
            $activities[] = array(
                'type' => 'application',
                'timestamp' => $app['created_at'],
                'message' => $msg,
                'status' => $app['status']
            );
        }
        
        $query2 = "SELECT i.*, c.name as company_name FROM internships i LEFT JOIN companies c ON i.company_id = c.id WHERE i.status != 'Deleted' ORDER BY i.created_at DESC LIMIT 5";
        $stmt = $db->query($query2);
        $internships = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($internships as $internship) {
            $pos = isset($internship['position']) ? $internship['position'] : 'internship';
            $ts = isset($internship['created_at']) ? $internship['created_at'] : date('Y-m-d H:i:s');
            $cn = isset($internship['company_name']) ? $internship['company_name'] : 'Unknown Company';
            $msg = $cn . ' posted a new ' . $pos . ' position';
            $activities[] = array(
                'type' => 'internship',
                'timestamp' => $ts,
                'message' => $msg,
                'status' => isset($internship['status']) ? $internship['status'] : 'Active'
            );
        }
        
        usort($activities, function($a, $b) {
            $tsA = isset($a['timestamp']) ? strtotime($a['timestamp']) : 0;
            $tsB = isset($b['timestamp']) ? strtotime($b['timestamp']) : 0;
            return $tsB - $tsA;
        });
        
        return array_slice($activities, 0, $limitInt);
    } catch(PDOException $e) {
        return array('error' => 'Failed to fetch activity feed: ' . $e->getMessage());
    }
}

// --- Reports --- //
function generate_report($report_type, $params) {
    global $db;
    requireRole(ROLE_ADMIN);
    
    try {
        $report_data = [
            'report_type' => $report_type,
            'generated_on' => date('Y-m-d H:i:s'),
            'params' => $params,
            'data' => []
        ];
        
        switch($report_type) {
            case 'applications':
                $stmt = $db->query("SELECT COUNT(*) as count FROM applications");
                $report_data['data'][] = ['metric' => 'Total Applications', 'value' => (int)$stmt->fetch()['count']];
                
                $stmt = $db->query("SELECT COUNT(*) as count FROM applications WHERE status IN ('Approved_By_Company', 'Confirmed_By_Student')");
                $report_data['data'][] = ['metric' => 'Approved Internships', 'value' => (int)$stmt->fetch()['count']];
                
                $stmt = $db->query("SELECT COUNT(*) as count FROM applications WHERE status IN ('Pending', 'Pending Review', 'Under Review')");
                $report_data['data'][] = ['metric' => 'Pending Applications', 'value' => (int)$stmt->fetch()['count']];
                break;
            default:
                $report_data['data'] = [
                    ['metric' => 'Total Applications', 'value' => 0],
                    ['metric' => 'Approved Internships', 'value' => 0],
                    ['metric' => 'Pending Applications', 'value' => 0]
                ];
        }
        
        return ['status' => 'success', 'report' => $report_data];
    } catch(PDOException $e) {
        return ['error' => 'Failed to generate report: ' . $e->getMessage()];
    }
}

// --- Evaluations --- //

function get_all_evaluations_admin() {
    global $db;
    requireRole(ROLE_ADMIN);
    
    try {
        $evaluations = [];
        
        // Get student evaluations (student → company)
        // Note: Using COALESCE to handle different possible column names
        $stmt = $db->query("
            SELECT 
                se.evaluation_id,
                se.student_id,
                se.company_id,
                se.overall_rating,
                COALESCE(se.submitted_date, se.created_at) as date_submitted,
                s.name as student_name,
                s.email as student_email,
                s.profile_pic as student_profile_pic,
                c.name as company_name,
                c.logo as company_logo,
                i.position as internship_position,
                'Student' as submitted_by,
                'student_evaluation' as evaluation_type
            FROM student_evaluations se
            JOIN students s ON se.student_id = s.id
            JOIN companies c ON se.company_id = c.id
            JOIN applications a ON se.application_id = a.id
            JOIN internships i ON a.internship_id = i.id
            ORDER BY COALESCE(se.submitted_date, se.created_at) DESC
        ");
        $studentEvals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($studentEvals as $eval) {
            $evaluations[] = $eval;
        }
        
        // Get company evaluations (company → student) if the table exists
        try {
            $stmt = $db->query("
                SELECT 
                    e.id as evaluation_id,
                    e.student_id,
                    e.company_id,
                    e.overall_rating,
                    COALESCE(e.submitted_at, e.created_at) as date_submitted,
                    s.name as student_name,
                    s.email as student_email,
                    s.profile_pic as student_profile_pic,
                    c.name as company_name,
                    c.logo as company_logo,
                    i.position as internship_position,
                    'Company' as submitted_by,
                    'company_evaluation' as evaluation_type
                FROM evaluations e
                JOIN students s ON e.student_id = s.id
                JOIN companies c ON e.company_id = c.id
                JOIN applications a ON e.application_id = a.id
                JOIN internships i ON a.internship_id = i.id
                ORDER BY COALESCE(e.submitted_at, e.created_at) DESC
            ");
            $companyEvals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($companyEvals as $eval) {
                $evaluations[] = $eval;
            }
        } catch(PDOException $e) {
            // Company evaluations table may not exist, that's OK
            error_log("Note: evaluations table query failed: " . $e->getMessage());
        }
        
        // Sort by date descending
        usort($evaluations, function($a, $b) {
            return strtotime($b['date_submitted'] ?? '1970-01-01') - strtotime($a['date_submitted'] ?? '1970-01-01');
        });
        
        return ['status' => 'success', 'data' => $evaluations];
    } catch(PDOException $e) {
        error_log("Failed to fetch all evaluations: " . $e->getMessage());
        return ['error' => 'Failed to fetch evaluations: ' . $e->getMessage()];
    }
}

function get_evaluation_details_admin($evaluation_id, $type = 'student') {
    global $db;
    requireRole(ROLE_ADMIN);
    
    try {
        if ($type === 'student' || $type === 'student_evaluation') {
            $stmt = $db->prepare("
                SELECT 
                    se.*,
                    s.name as student_name,
                    s.email as student_email,
                    c.name as company_name,
                    i.position as internship_position,
                    a.application_id
                FROM student_evaluations se
                JOIN students s ON se.student_id = s.id
                JOIN companies c ON se.company_id = c.id
                JOIN applications a ON se.application_id = a.id
                JOIN internships i ON a.internship_id = i.id
                WHERE se.evaluation_id = ?
            ");
            $stmt->execute([$evaluation_id]);
        } else {
            $stmt = $db->prepare("
                SELECT 
                    e.*,
                    s.name as student_name,
                    s.email as student_email,
                    c.name as company_name,
                    i.position as internship_position,
                    a.application_id
                FROM evaluations e
                JOIN students s ON e.student_id = s.id
                JOIN companies c ON e.company_id = c.id
                JOIN applications a ON e.application_id = a.id
                JOIN internships i ON a.internship_id = i.id
                WHERE e.id = ?
            ");
            $stmt->execute([$evaluation_id]);
        }
        
        $evaluation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$evaluation) {
            return ['error' => 'Evaluation not found'];
        }
        
        return ['status' => 'success', 'data' => $evaluation];
    } catch(PDOException $e) {
        error_log("Failed to fetch evaluation details: " . $e->getMessage());
        return ['error' => 'Failed to fetch evaluation details'];
    }
}

function get_evaluation_profile($evaluation_id) {
    // Legacy mock implementation - kept for backward compatibility
    $evaluation = [
        'evaluation_id' => $evaluation_id,
        'student_name' => 'John Doe',
        'company_name' => 'ABC Technologies',
        'internship_title' => 'Software Engineer Intern',
        'q1' => 'Excellent',
        'q2' => 'Good',
        'q3' => 'Satisfactory',
        'comments' => 'John was a valuable member of the team.'
    ];
    return $evaluation;
}

// --- Other Tools --- //
function send_email_admin($data) {
    // Mock implementation
    if (empty($data['to']) || empty($data['subject']) || empty($data['body'])) {
        return ['error' => 'Missing required email fields.'];
    }
    return ['status' => 'success', 'message' => 'Email sent successfully.'];
}

?>