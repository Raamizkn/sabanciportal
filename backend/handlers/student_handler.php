<?php

/**
 * Student Handler
 * ---------------------------------------
 * Handles student profile, document, and application features.
 * Modernized to use the MySQL database instead of mock arrays.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';

/**
 * Shared database connection for this handler.
 */
function student_db() {
    static $db = null;
    if ($db === null) {
        $db = getDB();
    }
    return $db;
}

/**
 * Normalize stored file paths into downloadable URLs.
 */
function format_download_path($path) {
    if (!$path) {
        return null;
    }
    $normalized = str_replace('\\', '/', $path);
    return '/' . ltrim($normalized, '/');
}

/**
 * Generate a unique document identifier.
 */
function generate_document_id() {
    $db = student_db();
    do {
        $candidate = 'DOC' . random_int(100000, 999999);
        $stmt = $db->prepare('SELECT COUNT(*) FROM documents WHERE document_id = ?');
        $stmt->execute([$candidate]);
        $exists = $stmt->fetchColumn();
    } while ($exists);
    return $candidate;
}

/**
 * Generate a unique application identifier.
 */
function generate_application_code() {
    $db = student_db();
    do {
        $candidate = 'APP' . random_int(100000, 999999);
        $stmt = $db->prepare('SELECT COUNT(*) FROM applications WHERE application_id = ?');
        $stmt->execute([$candidate]);
        $exists = $stmt->fetchColumn();
    } while ($exists);
    return $candidate;
}

/**
 * Fetch the latest resume metadata for a student.
 */
function get_latest_resume($student_id) {
    $db = student_db();
    $stmt = $db->prepare("SELECT id, document_id, file_name, file_path, file_size, upload_date
        FROM documents
        WHERE student_id = ? AND document_type = 'CV' AND (application_id IS NULL OR application_id = 0)
        ORDER BY upload_date DESC, id DESC
        LIMIT 1");
    $stmt->execute([$student_id]);
    $resume = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($resume) {
        $resume['download_url'] = format_download_path($resume['file_path']);
    }
    return $resume ?: null;
}

/**
 * Retrieve a single student profile.
 */
function get_profile_info($student_id) {
    try {
        $db = student_db();
        $stmt = $db->prepare("SELECT id, student_id, name, email, major, gpa, phone, address, bio, profile_pic, created_at, updated_at
            FROM students
            WHERE id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            return ['error' => 'Student not found'];
        }

        $student['resume'] = get_latest_resume($student_id);
        return $student;
    } catch (PDOException $e) {
        return ['error' => 'Failed to load student profile: ' . $e->getMessage()];
    }
}

/**
 * Update student profile fields.
 */
function update_profile($student_id, $data) {
    $allowed_fields = ['name', 'email', 'phone', 'address', 'bio', 'major', 'gpa', 'profile_pic'];
    $set_clauses = [];
    $values = [];

    foreach ($allowed_fields as $field) {
        if (isset($data[$field])) {
            $set_clauses[] = "$field = ?";
            $values[] = $data[$field];
        }
    }

    if (empty($set_clauses)) {
        return ['error' => 'No valid fields provided for update'];
    }

    $values[] = $student_id;

    try {
        $db = student_db();
        $stmt = $db->prepare('UPDATE students SET ' . implode(', ', $set_clauses) . ', updated_at = NOW() WHERE id = ?');
        $stmt->execute($values);

        return [
            'status' => 'success',
            'message' => 'Profile updated successfully',
            'data' => get_profile_info($student_id)
        ];
    } catch (PDOException $e) {
        return ['error' => 'Failed to update profile: ' . $e->getMessage()];
    }
}

/**
 * List active internships for students.
 */
function get_internships_for_student() {
    try {
        $db = student_db();
        $stmt = $db->query("SELECT * FROM internships WHERE status = 'Active' ORDER BY posted_date DESC, created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return ['error' => 'Failed to load internships: ' . $e->getMessage()];
    }
}

/**
 * Apply to an internship on behalf of a student.
 */
function apply_for_internship($student_id, $internship_id, $cover_letter = '') {
    try {
        $db = student_db();

        $stmt = $db->prepare('SELECT id, company_name, position FROM internships WHERE id = ? AND status != \'Deleted\'');
        $stmt->execute([$internship_id]);
        $internship = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$internship) {
            return ['error' => 'Internship not found'];
        }

        $stmt = $db->prepare('SELECT COUNT(*) FROM applications WHERE student_id = ? AND internship_id = ?');
        $stmt->execute([$student_id, $internship_id]);
        if ($stmt->fetchColumn() > 0) {
            return ['error' => 'You have already applied for this internship'];
        }

        $application_id = generate_application_code();
        $stmt = $db->prepare("INSERT INTO applications (application_id, student_id, internship_id, status, cover_letter, applied_date) VALUES (?, ?, ?, 'Pending', ?, CURDATE())");
        $stmt->execute([$application_id, $student_id, $internship_id, $cover_letter]);

        return [
            'status' => 'success',
            'message' => 'Application submitted successfully',
            'data' => [
                'application_id' => $application_id,
                'student_id' => $student_id,
                'internship_id' => $internship_id,
                'company_name' => $internship['company_name'],
                'position' => $internship['position'],
                'status' => 'Pending',
                'applied_date' => date('Y-m-d'),
                'cover_letter' => $cover_letter
            ]
        ];
    } catch (PDOException $e) {
        return ['error' => 'Failed to submit application: ' . $e->getMessage()];
    }
}

/**
 * Withdraw an application owned by the student.
 */
function withdraw_application($student_id, $application_id) {
    try {
        $db = student_db();
        $stmt = $db->prepare('SELECT * FROM applications WHERE application_id = ? AND student_id = ?');
        $stmt->execute([$application_id, $student_id]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$application) {
            return ['error' => 'Application not found or access denied'];
        }

        $stmt = $db->prepare("UPDATE applications SET status = 'Withdrawn', status_updated_date = NOW() WHERE application_id = ?");
        $stmt->execute([$application_id]);

        $application['status'] = 'Withdrawn';
        return ['status' => 'success', 'message' => 'Application withdrawn successfully', 'data' => $application];
    } catch (PDOException $e) {
        return ['error' => 'Failed to withdraw application: ' . $e->getMessage()];
    }
}

/**
 * View details for a single application plus internship info.
 */
function view_application_details($student_id, $application_id) {
    try {
        $db = student_db();
        $stmt = $db->prepare("SELECT a.*, i.title, i.position, i.description, i.location, i.dates, i.company_name
            FROM applications a
            JOIN internships i ON a.internship_id = i.id
            WHERE a.application_id = ? AND a.student_id = ?");
        $stmt->execute([$application_id, $student_id]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$application) {
            return ['error' => 'Application not found or access denied'];
        }

        return $application;
    } catch (PDOException $e) {
        return ['error' => 'Failed to load application: ' . $e->getMessage()];
    }
}

/**
 * Get all applications for a student.
 */
function get_all_student_applications($student_id) {
    try {
        $db = student_db();
        $stmt = $db->prepare("SELECT a.application_id, a.status, a.applied_date, a.cover_letter,
                    i.position as internship_position, i.location as internship_location,
                    i.dates as internship_dates, i.company_name
                FROM applications a
                JOIN internships i ON a.internship_id = i.id
                WHERE a.student_id = ?
                ORDER BY a.applied_date DESC");
        $stmt->execute([$student_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return ['error' => 'Failed to load applications: ' . $e->getMessage()];
    }
}

/**
 * Accept an internship offer.
 */
function accept_internship_offer($student_id, $application_id) {
    try {
        $db = student_db();
        $stmt = $db->prepare('SELECT * FROM applications WHERE application_id = ? AND student_id = ?');
        $stmt->execute([$application_id, $student_id]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$application) {
            return ['error' => 'Application not found or access denied'];
        }

        // Student can confirm if status is 'Accepted' (Offered is consolidated to Accepted)
        if ($application['status'] !== 'Accepted') {
            return ['error' => 'This internship offer cannot be confirmed. Status must be Accepted.'];
        }

        $stmt = $db->prepare("UPDATE applications SET status = 'Confirmed', status_updated_date = NOW() WHERE application_id = ?");
        $stmt->execute([$application_id]);
        $application['status'] = 'Confirmed';

        return ['status' => 'success', 'message' => 'Internship offer accepted', 'data' => $application];
    } catch (PDOException $e) {
        return ['error' => 'Failed to accept offer: ' . $e->getMessage()];
    }
}

/**
 * List all documents for a student.
 */
function get_student_documents($student_id) {
    try {
        $db = student_db();
        if (!$db) {
            return ['error' => 'Database connection failed'];
        }
        
        // Check if file_content column exists (for database storage)
        $column_exists = false;
        try {
            $check_stmt = $db->query("SHOW COLUMNS FROM documents LIKE 'file_content'");
            $column_exists = $check_stmt->rowCount() > 0;
        } catch (PDOException $e) {
            // Column doesn't exist yet, use filesystem storage
        }
        
        if ($column_exists) {
            // Select with file_content check (but don't fetch the BLOB data)
            $stmt = $db->prepare("SELECT document_id, document_type, file_name, file_path, file_size, upload_date, application_id,
                CASE WHEN file_content IS NOT NULL THEN 1 ELSE 0 END as stored_in_db
                FROM documents
                WHERE student_id = ?
                ORDER BY upload_date DESC, id DESC");
        } else {
            // Fallback: column doesn't exist yet
            $stmt = $db->prepare("SELECT document_id, document_type, file_name, file_path, file_size, upload_date, application_id
                FROM documents
                WHERE student_id = ?
                ORDER BY upload_date DESC, id DESC");
        }
        
        if (!$stmt) {
            return ['error' => 'Failed to prepare query'];
        }
        
        $stmt->execute([$student_id]);
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($documents === false) {
            return ['error' => 'Failed to fetch documents'];
        }
        
        foreach ($documents as &$doc) {
            // For documents stored in DB (file_content exists), file_path is NULL
            // For documents stored in filesystem, use file_path
            if (isset($doc['stored_in_db']) && $doc['stored_in_db']) {
                $doc['download_url'] = null; // Will be generated via download endpoint
            } else {
                $doc['download_url'] = format_download_path($doc['file_path'] ?? null);
            }
        }
        unset($doc); // Break reference
        
        return $documents;
    } catch (PDOException $e) {
        error_log('Error loading student documents: ' . $e->getMessage());
        return ['error' => 'Failed to load documents: ' . $e->getMessage()];
    } catch (Exception $e) {
        error_log('Unexpected error loading student documents: ' . $e->getMessage());
        return ['error' => 'An unexpected error occurred'];
    }
}

/**
 * Handle document file upload (similar to resume upload).
 */
function handle_document_upload($student_id, $file, $document_name, $document_type = 'Other') {
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'No file uploaded or upload failed'];
    }

    $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    $max_size = 2 * 1024 * 1024; // 2MB (matching PHP upload_max_filesize)
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $allowed_extensions)) {
        return ['error' => 'Invalid file type. Allowed types: pdf, doc, docx, jpg, jpeg, png'];
    }

    if ($file['size'] > $max_size) {
        return ['error' => 'File exceeds maximum size of 2MB'];
    }

    // Read file content into memory
    $file_content = file_get_contents($file['tmp_name']);
    if ($file_content === false) {
        return ['error' => 'Failed to read uploaded file'];
    }

    try {
        $db = student_db();
        $document_id = generate_document_id();
        
        // Store file content directly in database (BLOB)
        // file_path is set to NULL since we're storing in DB
        $stmt = $db->prepare("INSERT INTO documents (document_id, student_id, document_type, file_name, file_path, file_size, file_content, upload_date)
            VALUES (?, ?, ?, ?, NULL, ?, ?, CURDATE())");
        $stmt->execute([
            $document_id,
            $student_id,
            $document_type,
            $document_name ?: $file['name'],
            $file['size'],
            $file_content  // Store binary content in database
        ]);

        $uploaded_doc = [
            'document_id' => $document_id,
            'student_id' => $student_id,
            'document_type' => $document_type,
            'file_name' => $document_name ?: $file['name'],
            'file_size' => $file['size'],
            'stored_in_db' => true
        ];

        return [
            'status' => 'success',
            'message' => 'Document uploaded successfully and stored in database',
            'data' => $uploaded_doc
        ];
    } catch (PDOException $e) {
        return ['error' => 'Failed to save document: ' . $e->getMessage()];
    }
}

/**
 * Upload document metadata (legacy support for JSON uploads).
 */
function upload_student_document($student_id, $file_data) {
    if (empty($file_data['document_type']) || empty($file_data['file_name'])) {
        return ['error' => 'Missing document_type or file_name'];
    }

    try {
        $db = student_db();
        $document_id = generate_document_id();
        $stmt = $db->prepare("INSERT INTO documents (document_id, student_id, document_type, file_name, file_path, file_size, upload_date) VALUES (?, ?, ?, ?, ?, ?, CURDATE())");
        $stmt->execute([
            $document_id,
            $student_id,
            $file_data['document_type'],
            $file_data['file_name'],
            $file_data['file_path'] ?? null,
            $file_data['file_size'] ?? null
        ]);

        return [
            'status' => 'success',
            'message' => 'Document uploaded successfully',
            'data' => [
                'document_id' => $document_id,
                'student_id' => $student_id,
                'document_type' => $file_data['document_type'],
                'file_name' => $file_data['file_name']
            ]
        ];
    } catch (PDOException $e) {
        return ['error' => 'Failed to upload document: ' . $e->getMessage()];
    }
}

/**
 * Download a student document (serves file securely).
 */
function download_student_document($student_id, $document_id) {
    try {
        $db = student_db();
        // Get file_content (BLOB) and file metadata
        $stmt = $db->prepare('SELECT file_content, file_path, file_name, document_type, file_size FROM documents WHERE document_id = ? AND student_id = ?');
        $stmt->execute([$document_id, $student_id]);
        $document = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$document) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Document not found or access denied']);
            exit;
        }

        // Clear any output buffers first
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        $file_name = $document['file_name'] ?: 'document';
        $file_size = $document['file_size'] ?? 0;
        
        // Determine MIME type from file extension
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

        // Check if file is stored in database (BLOB) or filesystem
        if (!empty($document['file_content'])) {
            // File stored in database as BLOB
            $file_content = $document['file_content'];
            $file_size = strlen($file_content);
            
            header('Content-Type: ' . $mime_type);
            header('Content-Disposition: attachment; filename="' . addslashes($file_name) . '"');
            header('Content-Length: ' . $file_size);
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            
            echo $file_content;
            exit;
        } elseif (!empty($document['file_path'])) {
            // Fallback: File stored in filesystem (for backward compatibility)
            $backend_root = dirname(__DIR__);
            $file_path = $backend_root . '/' . ltrim($document['file_path'], '/');
            
            if (!file_exists($file_path)) {
                http_response_code(404);
                header('Content-Type: application/json');
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
        } else {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'File content not available']);
            exit;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Failed to download document: ' . $e->getMessage()]);
        exit;
    }
}

/**
 * Delete a student document.
 */
function delete_student_document($student_id, $document_id) {
    try {
        $db = student_db();
        $stmt = $db->prepare('SELECT file_path FROM documents WHERE document_id = ? AND student_id = ?');
        $stmt->execute([$document_id, $student_id]);
        $document = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$document) {
            return ['error' => 'Document not found or access denied'];
        }

        if (!empty($document['file_path'])) {
            $full_path = dirname(__DIR__) . '/' . ltrim($document['file_path'], '/');
            if (file_exists($full_path)) {
                @unlink($full_path);
            }
        }

        $stmt = $db->prepare('DELETE FROM documents WHERE document_id = ?');
        $stmt->execute([$document_id]);
        return ['status' => 'success', 'message' => 'Document deleted successfully'];
    } catch (PDOException $e) {
        return ['error' => 'Failed to delete document: ' . $e->getMessage()];
    }
}


/**
 * Legacy login helper used by the standalone student API.
 */
function student_login($email, $password) {
    try {
        $db = student_db();
        $stmt = $db->prepare('SELECT id, name, email, password_hash FROM students WHERE email = ? AND is_active = 1');
        $stmt->execute([$email]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student && password_verify($password, $student['password_hash'])) {
            login($student['id'], ROLE_STUDENT, $student);
            return ['status' => 'success', 'message' => 'Login successful', 'user' => $student];
        }

        return ['error' => 'Invalid credentials'];
    } catch (PDOException $e) {
        return ['error' => 'Login failed: ' . $e->getMessage()];
    }
}

function student_logout() {
    logout();
    return ['status' => 'success', 'message' => 'Logout successful'];
}

/**
 * Create (or update) a resume file for the authenticated student.
 */
function handle_resume_upload($student_id, $file) {
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'No resume uploaded or upload failed'];
    }

    $allowed_extensions = ['pdf', 'doc', 'docx'];
    $max_size = 5 * 1024 * 1024; // 5MB
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $allowed_extensions)) {
        return ['error' => 'Invalid file type. Allowed types: pdf, doc, docx'];
    }

    if ($file['size'] > $max_size) {
        return ['error' => 'File exceeds maximum size of 2MB'];
    }

    $backend_root = dirname(__DIR__);
    $relative_dir = 'uploads/students/' . $student_id . '/resume';
    $absolute_dir = $backend_root . '/' . $relative_dir;

    if (!is_dir($absolute_dir) && !mkdir($absolute_dir, 0775, true)) {
        return ['error' => 'Failed to create storage directory'];
    }

    $safe_filename = 'resume_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $absolute_path = $absolute_dir . '/' . $safe_filename;

    if (!move_uploaded_file($file['tmp_name'], $absolute_path)) {
        return ['error' => 'Failed to store uploaded resume'];
    }

    $relative_path = $relative_dir . '/' . $safe_filename;

    try {
        $db = student_db();
        $existing_resume = get_latest_resume($student_id);

        if ($existing_resume) {
            if (!empty($existing_resume['file_path'])) {
                $old_file = $backend_root . '/' . ltrim($existing_resume['file_path'], '/');
                if (file_exists($old_file)) {
                    @unlink($old_file);
                }
            }

            $stmt = $db->prepare("UPDATE documents
                SET file_name = ?, file_path = ?, file_size = ?, upload_date = CURDATE(), updated_at = NOW()
                WHERE document_id = ?");
            $stmt->execute([$file['name'], $relative_path, $file['size'], $existing_resume['document_id']]);
        } else {
            $document_id = generate_document_id();
            $stmt = $db->prepare("INSERT INTO documents (document_id, student_id, document_type, file_name, file_path, file_size, upload_date)
                VALUES (?, ?, 'CV', ?, ?, ?, CURDATE())");
            $stmt->execute([$document_id, $student_id, $file['name'], $relative_path, $file['size']]);
        }

        $updated_resume = get_latest_resume($student_id);
        return [
            'status' => 'success',
            'message' => 'Resume uploaded successfully',
            'resume' => $updated_resume
        ];
    } catch (PDOException $e) {
        return ['error' => 'Failed to save resume: ' . $e->getMessage()];
    }
}

/**
 * Authorization helper for student resources.
 */
function ensure_student_access($target_student_id) {
    $current_role = getCurrentUserRole();
    $current_id = getCurrentUserId();

    if ($current_role === ROLE_ADMIN) {
        return true;
    }

    if ($current_role === ROLE_STUDENT && (int)$current_id === (int)$target_student_id) {
        return true;
    }

    http_response_code(403);
    echo json_encode(['error' => 'Access denied.']);
    exit;
}

// --------------------------------------------------------------------------
// Router binding when included via index.php
// --------------------------------------------------------------------------
if (isset($entity) && $entity === 'students') {
    global $method, $action, $id, $input;
    
    // Suppress any PHP warnings/errors that might output HTML
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    
    // Ensure no output before JSON
    if (ob_get_level()) {
        ob_clean();
    }
    
    requireAuth();

    if ($method === 'GET') {
        $target_student_id = $id ? (int)$id : (int)getCurrentUserId();
        if (!$target_student_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Student ID is required']);
            exit;
        }

        ensure_student_access($target_student_id);

        if ($action === 'documents') {
            try {
                $response = get_student_documents($target_student_id);
                if (isset($response['error'])) {
                    http_response_code(400);
                }
                echo json_encode($response);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to load documents: ' . $e->getMessage()]);
            }
        } elseif ($action === 'download_doc') {
            requireRole(ROLE_STUDENT);
            $document_id = $_GET['document_id'] ?? null;
            if (!$document_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Document ID is required']);
                exit;
            }
            ensure_student_access($target_student_id);
            download_student_document($target_student_id, $document_id);
            // download_student_document handles output and exits
        } elseif ($action === 'resume') {
            $resume = get_latest_resume($target_student_id);
            if (!$resume) {
                http_response_code(404);
                echo json_encode(['error' => 'Resume not found']);
            } else {
                echo json_encode($resume);
            }
        } else {
            $profile = get_profile_info($target_student_id);
            if (isset($profile['error'])) {
                http_response_code(404);
            }
            echo json_encode($profile);
        }
    } elseif ($method === 'POST') {
        if ($action === 'update') {
            $target_student_id = $id ? (int)$id : (int)getCurrentUserId();
            if (!$target_student_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Student ID is required']);
                exit;
            }
            ensure_student_access($target_student_id);

            $response = update_profile($target_student_id, $input ?? []);
            if (isset($response['error'])) {
                http_response_code(400);
            }
            echo json_encode($response);
        } elseif ($action === 'upload_resume') {
            requireRole(ROLE_STUDENT);
            $response = handle_resume_upload(getCurrentUserId(), $_FILES['resume'] ?? null);
            if (isset($response['error'])) {
                http_response_code(400);
            } else {
                http_response_code(201);
            }
            echo json_encode($response);
        } elseif ($action === 'upload_doc') {
            requireRole(ROLE_STUDENT);
            $target_student_id = $id ? (int)$id : (int)getCurrentUserId();
            if (!$target_student_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Student ID is required']);
                exit;
            }
            ensure_student_access($target_student_id);
            
            // Try to increase upload limits if possible
            $current_max = ini_get('upload_max_filesize');
            $current_post = ini_get('post_max_size');
            $desired_max = '10M';
            
            // Convert to bytes for comparison
            function convertToBytes($val) {
                $val = trim($val);
                $last = strtolower($val[strlen($val)-1]);
                $val = (int)$val;
                switch($last) {
                    case 'g': $val *= 1024;
                    case 'm': $val *= 1024;
                    case 'k': $val *= 1024;
                }
                return $val;
            }
            
            if (convertToBytes($current_max) < convertToBytes($desired_max)) {
                @ini_set('upload_max_filesize', $desired_max);
            }
            if (convertToBytes($current_post) < convertToBytes($desired_max)) {
                @ini_set('post_max_size', $desired_max);
            }
            
            // Check if file was uploaded via FormData
            if (isset($_FILES['file']) && is_array($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                // Handle actual file upload
                $document_name = $_POST['document_name'] ?? $_FILES['file']['name'];
                // Use 'Other' as default since 'General' is not in the ENUM
                $document_type = $_POST['document_type'] ?? 'Other';
                // Validate document_type against allowed ENUM values
                $allowed_types = ['CV', 'Transcript', 'Portfolio', 'Cover Letter', 'Certificate', 'Other'];
                if (!in_array($document_type, $allowed_types)) {
                    $document_type = 'Other';
                }
                $response = handle_document_upload($target_student_id, $_FILES['file'], $document_name, $document_type);
            } elseif (isset($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                $error_messages = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit (' . ini_get('upload_max_filesize') . '). Please choose a smaller file.',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds form size limit. Please choose a smaller file.',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded. Please try again.',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded. Please select a file.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Server configuration error. Please contact support.',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to save file. Please try again.',
                    UPLOAD_ERR_EXTENSION => 'File type not allowed or upload blocked by server.'
                ];
                $error_msg = $error_messages[$_FILES['file']['error']] ?? 'Unknown upload error (code: ' . $_FILES['file']['error'] . ')';
                $response = ['error' => $error_msg];
            } elseif (!empty($input)) {
                // Legacy JSON-based document upload (metadata only)
                $response = upload_student_document($target_student_id, $input);
            } else {
                $response = ['error' => 'No file or document data provided. FILES: ' . (isset($_FILES) ? 'set' : 'not set') . ', POST: ' . (isset($_POST) ? 'set' : 'not set')];
            }
            
            if (isset($response['error'])) {
                http_response_code(400);
            } else {
                http_response_code(201);
            }
            echo json_encode($response);
        } elseif ($action === 'delete_doc') {
            requireRole(ROLE_STUDENT);
            $target_student_id = $id ? (int)$id : (int)getCurrentUserId();
            if (!$target_student_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Student ID is required']);
                exit;
            }
            ensure_student_access($target_student_id);
            
            $document_id = $input['document_id'] ?? null;
            if (!$document_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Document ID is required']);
                exit;
            }
            
            $response = delete_student_document($target_student_id, $document_id);
            if (isset($response['error'])) {
                http_response_code(400);
            } else {
                http_response_code(200);
            }
            echo json_encode($response);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Unsupported action for students entity']);
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed for students entity']);
    }

    exit;
}

?>
