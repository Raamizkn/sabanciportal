<?php

// Admin Handler - Contains all business logic for admin-related API calls.

// --- Term Management --- //
function get_all_terms() {
    global $terms;
    return $terms;
}
function add_term($data) {
    global $terms;
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
    return $students;
}
function add_new_student($data) {
    global $students;
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
    global $students;
    foreach ($students as $student) {
        if ($student['id'] == $student_id) {
            // In a real app, this would set a special session variable.
            return ['status' => 'success', 'message' => 'Impersonation started.', 'impersonated_user' => $student];
        }
    }
    return ['error' => 'Student not found.'];
}

// --- Company Management --- //
function get_all_companies() {
    global $companies;
    return $companies;
}
function add_new_company($data) {
    global $companies;
    if (empty($data['name']) || empty($data['email'])) {
        return ['error' => 'Missing required company data.'];
    }

    $new_company = [
        'id' => count($companies) + 1,
        'name' => $data['name'],
        'email' => $data['email'],
        'industry' => $data['industry'] ?? '',
        'website' => $data['website'] ?? '',
        'phone' => $data['phone'] ?? '',
        'address' => $data['address'] ?? ''
    ];

    return ['status' => 'success', 'message' => 'Company added successfully.', 'data' => $new_company];
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
    foreach ($companies as $key => $company) {
        if ($company['id'] == $company_id) {
            // unset($companies[$key]);
            return ['status' => 'success', 'message' => 'Company deleted successfully.'];
        }
    }
    return ['error' => 'Company not found.'];
}
function impersonate_company($company_id) {
    global $companies;
    foreach ($companies as $company) {
        if ($company['id'] == $company_id) {
            return ['status' => 'success', 'message' => 'Impersonation started.', 'impersonated_company' => $company];
        }
    }
    return ['error' => 'Company not found.'];
}

// --- Internship Management (Admin) --- //
function get_all_internships_admin() {
    global $internships;
    return $internships;
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
function get_application_details_admin($application_id) {
    global $applications, $students, $internships, $companies;

    foreach ($applications as $application) {
        if ($application['application_id'] == $application_id) {
            // Find student details
            foreach ($students as $student) {
                if ($student['id'] == $application['student_id']) {
                    $application['student'] = $student;
                    break;
                }
            }

            // Find internship and company details
            foreach ($internships as $internship) {
                if ($internship['id'] == $application['internship_id']) {
                    // Find company details for the internship
                    foreach ($companies as $company) {
                        if ($company['name'] == $internship['company_name']) {
                            $internship['company'] = $company;
                            break;
                        }
                    }
                    $application['internship'] = $internship;
                    break;
                }
            }
            return $application;
        }
    }
    return ['error' => 'Application not found.'];
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

// --- Reports --- //
function generate_report($report_type, $params) {
    // This is a mock implementation.
    $report_data = [
        'report_type' => $report_type,
        'generated_on' => date('Y-m-d H:i:s'),
        'params' => $params,
        'data' => [
            ['metric' => 'Total Applications', 'value' => 150],
            ['metric' => 'Approved Internships', 'value' => 75],
            ['metric' => 'Pending Applications', 'value' => 25]
        ]
    ];
    return ['status' => 'success', 'report' => $report_data];
}

// --- Evaluations --- //
function get_evaluation_profile($evaluation_id) {
    // Mock implementation
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