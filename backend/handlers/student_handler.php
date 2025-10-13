<?php

// Student Handler - Contains all business logic for student-related API calls.

// --- Profile Management --- //

function get_profile_info($student_id) {
    global $students;
    foreach ($students as $student) {
        if ($student['id'] == $student_id) {
            return $student;
        }
    }
    return ['error' => 'Student not found'];
}

function update_profile($student_id, $data) {
    global $students;
    foreach ($students as &$student) {
        if ($student['id'] == $student_id) {
            // In a real application, you would validate the data here.
            $student = array_merge($student, $data);
            // In a real app, you would save this back to the database.
            return ['status' => 'success', 'message' => 'Profile updated successfully', 'data' => $student];
        }
    }
    return ['error' => 'Student not found'];
}

// --- Internship Management --- //

function get_internships_for_student() {
    global $internships;
    return $internships;
}

// --- Application Management --- //

function apply_for_internship($student_id, $internship_id) {
    global $applications, $internships;

    // Check if internship exists
    $internship_exists = false;
    foreach ($internships as $internship) {
        if ($internship['id'] == $internship_id) {
            $internship_exists = true;
            break;
        }
    }
    if (!$internship_exists) {
        return ['error' => 'Internship not found.'];
    }

    // Check for duplicate application
    foreach ($applications as $application) {
        if ($application['student_id'] == $student_id && $application['internship_id'] == $internship_id) {
            return ['error' => 'You have already applied for this internship.'];
        }
    }

    // Create new application (simulation)
    $new_application = [
        "application_id" => count($applications) + 1001, // Simple unique ID generation
        "student_id" => (int)$student_id,
        "internship_id" => (int)$internship_id,
        "status" => "Pending",
        "application_date" => date('Y-m-d')
    ];

    // In a real app, you would add this to the database.
    // $applications[] = $new_application;

    return ['status' => 'success', 'message' => 'Application submitted successfully.', 'data' => $new_application];
}

function withdraw_application($student_id, $application_id) {
    global $applications;
    foreach ($applications as &$application) {
        if ($application['application_id'] == $application_id && $application['student_id'] == $student_id) {
            $application['status'] = 'Withdrawn';
            // In a real app, you would save this change to the database.
            return ['status' => 'success', 'message' => 'Application withdrawn successfully.', 'data' => $application];
        }
    }
    return ['error' => 'Application not found or access denied.'];
}

function view_application_details($student_id, $application_id) {
    global $applications, $internships;
    foreach ($applications as $application) {
        if ($application['application_id'] == $application_id && $application['student_id'] == $student_id) {
            // Find the corresponding internship details
            $internship_details = null;
            foreach ($internships as $internship) {
                if ($internship['id'] == $application['internship_id']) {
                    $internship_details = $internship;
                    break;
                }
            }
            $application['internship'] = $internship_details;
            return $application;
        }
    }
    return ['error' => 'Application not found or access denied'];
}

function get_all_student_applications($student_id) {
    global $applications, $internships;
    $student_applications = [];
    foreach ($applications as $application) {
        if ($application['student_id'] == $student_id) {
            // Find the corresponding internship details
            $internship_details = null;
            foreach ($internships as $internship) {
                if ($internship['id'] == $application['internship_id']) {
                    $internship_details = $internship;
                    break;
                }
            }
            // Add internship details to the application record
            $application['internship'] = $internship_details;
            $student_applications[] = $application;
        }
    }
    return $student_applications;
}

function accept_internship_offer($student_id, $application_id) {
    global $applications;
    foreach ($applications as &$application) {
        if ($application['application_id'] == $application_id && $application['student_id'] == $student_id) {
            if ($application['status'] === 'Approved') {
                $application['status'] = 'Accepted';
                // In a real app, you would save this change to the database.
                return ['status' => 'success', 'message' => 'Internship offer accepted.', 'data' => $application];
            } else {
                return ['error' => 'This internship offer is not in an approved state.'];
            }
        }
    }
    return ['error' => 'Application not found or access denied.'];
}

// --- Document Management --- //

function get_student_documents($student_id) {
    global $documents;
    $student_docs = [];
    foreach ($documents as $doc) {
        if ($doc['student_id'] == $student_id) {
            $student_docs[] = $doc;
        }
    }
    return $student_docs;
}

function upload_student_document($student_id, $file_data) {
    global $documents;
    // In a real app, you would handle the actual file upload here (e.g., using $_FILES)
    // and perform validation (file type, size, etc.).

    $new_doc = [
        "document_id" => count($documents) + 2001, // Simple unique ID
        "student_id" => (int)$student_id,
        "document_type" => $file_data['document_type'] ?? 'CV', // Default to CV
        "file_name" => $file_data['file_name'] ?? 'new_document.pdf',
        "upload_date" => date('Y-m-d')
    ];

    // $documents[] = $new_doc; // In a real app, save to DB

    return ['status' => 'success', 'message' => 'Document uploaded successfully.', 'data' => $new_doc];
}

function delete_student_document($student_id, $document_id) {
    global $documents;
    foreach ($documents as $key => $doc) {
        if ($doc['document_id'] == $document_id && $doc['student_id'] == $student_id) {
            // In a real app, you would remove from DB and delete the file from storage.
            // unset($documents[$key]);
            return ['status' => 'success', 'message' => 'Document deleted successfully.'];
        }
    }
    return ['error' => 'Document not found or access denied.'];
}

function download_student_document($student_id, $document_id) {
    global $documents;
    foreach ($documents as $doc) {
        if ($doc['document_id'] == $document_id && $doc['student_id'] == $student_id) {
            // In a real app, you would serve the file with appropriate headers.
            // For this API, we'll return a simulated URL.
            $download_url = '/path/to/documents/' . $doc['file_name']; // Example URL
            return ['status' => 'success', 'download_url' => $download_url];
        }
    }
    return ['error' => 'Document not found or access denied.'];
}

// --- Auth --- //

function student_login($email, $password) {
    global $students;
    // This is a mock login. In a real app, you would verify the password hash.
    foreach ($students as $student) {
        if (strtolower($student['email']) === strtolower($email)) {
            return ['status' => 'success', 'message' => 'Login successful.', 'user' => $student];
        }
    }
    return ['error' => 'Invalid credentials.'];
}

function student_logout() {
    // In a real app, you would destroy the session.
    return ['status' => 'success', 'message' => 'Logout successful.'];
}

?>