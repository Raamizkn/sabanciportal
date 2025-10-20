<?php

// Company Handler - Contains all business logic for company-related API calls.

// --- Company Profile Management --- //

function get_company_profile($company_id) {
    global $companies;
    foreach ($companies as $company) {
        if ($company['id'] == $company_id) {
            return $company;
        }
    }
    return ['error' => 'Company not found'];
}

function update_company_profile($company_id, $data) {
    global $companies;
    foreach ($companies as &$company) {
        if ($company['id'] == $company_id) {
            // Validate and update company data
            $allowed_fields = ['name', 'email', 'industry', 'website', 'phone', 'address', 'description'];
            foreach ($allowed_fields as $field) {
                if (isset($data[$field])) {
                    $company[$field] = $data[$field];
                }
            }
            // In a real app, you would save this back to the database.
            return ['status' => 'success', 'message' => 'Company profile updated successfully', 'data' => $company];
        }
    }
    return ['error' => 'Company not found'];
}

// --- Internship Management --- //

function get_company_internships($company_id) {
    global $internships;
    $company_internships = [];
    foreach ($internships as $internship) {
        if (isset($internship['company_id']) && $internship['company_id'] == $company_id) {
            $company_internships[] = $internship;
        }
    }
    return $company_internships;
}

function create_company_internship($company_id, $data) {
    global $internships, $companies;
    
    // Validate company exists
    $company_exists = false;
    $company_name = '';
    foreach ($companies as $company) {
        if ($company['id'] == $company_id) {
            $company_exists = true;
            $company_name = $company['name'];
            break;
        }
    }
    
    if (!$company_exists) {
        return ['error' => 'Company not found'];
    }
    
    // Basic validation
    if (empty($data['title']) || empty($data['description']) || empty($data['location'])) {
        return ['error' => 'Missing required internship data (title, description, location)'];
    }
    
    $new_internship = [
        'id' => count($internships) + 1000, // Simple unique ID
        'company_id' => $company_id,
        'company_name' => $company_name,
        'title' => $data['title'],
        'description' => $data['description'],
        'location' => $data['location'],
        'dates' => $data['dates'] ?? 'TBD',
        'requirements' => $data['requirements'] ?? '',
        'salary' => $data['salary'] ?? 'Not specified',
        'type' => $data['type'] ?? 'Full-time',
        'status' => 'Active',
        'created_date' => date('Y-m-d'),
        'application_deadline' => $data['application_deadline'] ?? null
    ];
    
    // In a real app, you would insert this into the database.
    return ['status' => 'success', 'message' => 'Internship created successfully', 'data' => $new_internship];
}

function update_company_internship($company_id, $internship_id, $data) {
    global $internships;
    foreach ($internships as &$internship) {
        if ($internship['id'] == $internship_id && $internship['company_id'] == $company_id) {
            // Update allowed fields
            $allowed_fields = ['title', 'description', 'location', 'dates', 'requirements', 'salary', 'type', 'status', 'application_deadline'];
            foreach ($allowed_fields as $field) {
                if (isset($data[$field])) {
                    $internship[$field] = $data[$field];
                }
            }
            $internship['updated_date'] = date('Y-m-d');
            // In a real app, you would save this back to the database.
            return ['status' => 'success', 'message' => 'Internship updated successfully', 'data' => $internship];
        }
    }
    return ['error' => 'Internship not found or access denied'];
}

function delete_company_internship($company_id, $internship_id) {
    global $internships;
    foreach ($internships as $key => &$internship) {
        if ($internship['id'] == $internship_id && $internship['company_id'] == $company_id) {
            // In a real app, you would delete from the database
            // unset($internships[$key]);
            $internship['status'] = 'Deleted';
            return ['status' => 'success', 'message' => 'Internship deleted successfully'];
        }
    }
    return ['error' => 'Internship not found or access denied'];
}

// --- Application Management --- //

function get_company_applications($company_id) {
    global $applications, $internships, $students;
    $company_applications = [];
    
    // Get all internships for this company
    $company_internship_ids = [];
    foreach ($internships as $internship) {
        if (isset($internship['company_id']) && $internship['company_id'] == $company_id) {
            $company_internship_ids[] = $internship['id'];
        }
    }
    
    // Get all applications for company's internships
    foreach ($applications as $application) {
        if (in_array($application['internship_id'], $company_internship_ids)) {
            // Enrich application with student and internship details
            $enriched_application = $application;
            
            // Add student details
            foreach ($students as $student) {
                if ($student['id'] == $application['student_id']) {
                    $enriched_application['student'] = $student;
                    break;
                }
            }
            
            // Add internship details
            foreach ($internships as $internship) {
                if ($internship['id'] == $application['internship_id']) {
                    $enriched_application['internship'] = $internship;
                    break;
                }
            }
            
            $company_applications[] = $enriched_application;
        }
    }
    
    return $company_applications;
}

function get_single_application($company_id, $application_id) {
    global $applications, $internships, $students, $documents;
    
    foreach ($applications as $application) {
        if ($application['application_id'] == $application_id) {
            // Verify this application belongs to this company
            $internship_belongs_to_company = false;
            $internship_details = null;
            
            foreach ($internships as $internship) {
                if ($internship['id'] == $application['internship_id'] && 
                    isset($internship['company_id']) && 
                    $internship['company_id'] == $company_id) {
                    $internship_belongs_to_company = true;
                    $internship_details = $internship;
                    break;
                }
            }
            
            if (!$internship_belongs_to_company) {
                return ['error' => 'Application not found or access denied'];
            }
            
            // Enrich with student details
            $student_details = null;
            foreach ($students as $student) {
                if ($student['id'] == $application['student_id']) {
                    $student_details = $student;
                    break;
                }
            }
            
            // Get application documents
            $application_documents = [];
            foreach ($documents as $document) {
                if ($document['student_id'] == $application['student_id']) {
                    $application_documents[] = $document;
                }
            }
            
            return [
                'application' => $application,
                'student' => $student_details,
                'internship' => $internship_details,
                'documents' => $application_documents
            ];
        }
    }
    
    return ['error' => 'Application not found'];
}

function update_application_status($company_id, $application_id, $status) {
    global $applications, $internships;
    
    // Validate status
    $valid_statuses = ['Pending', 'Under Review', 'Shortlisted', 'Interview Scheduled', 'Offered', 'Rejected', 'Accepted', 'Declined'];
    if (!in_array($status, $valid_statuses)) {
        return ['error' => 'Invalid status. Valid statuses: ' . implode(', ', $valid_statuses)];
    }
    
    foreach ($applications as &$application) {
        if ($application['application_id'] == $application_id) {
            // Verify this application belongs to this company
            $internship_belongs_to_company = false;
            
            foreach ($internships as $internship) {
                if ($internship['id'] == $application['internship_id'] && 
                    isset($internship['company_id']) && 
                    $internship['company_id'] == $company_id) {
                    $internship_belongs_to_company = true;
                    break;
                }
            }
            
            if (!$internship_belongs_to_company) {
                return ['error' => 'Application not found or access denied'];
            }
            
            $application['status'] = $status;
            $application['status_updated_date'] = date('Y-m-d H:i:s');
            
            // In a real app, you would save this back to the database.
            return ['status' => 'success', 'message' => 'Application status updated successfully', 'data' => $application];
        }
    }
    
    return ['error' => 'Application not found'];
}

// --- Evaluation Management --- //

function create_evaluation($company_id, $application_id, $evaluation_data) {
    global $applications, $internships;
    
    // Verify application belongs to this company
    $application_found = false;
    foreach ($applications as $application) {
        if ($application['application_id'] == $application_id) {
            foreach ($internships as $internship) {
                if ($internship['id'] == $application['internship_id'] && 
                    isset($internship['company_id']) && 
                    $internship['company_id'] == $company_id) {
                    $application_found = true;
                    break 2;
                }
            }
        }
    }
    
    if (!$application_found) {
        return ['error' => 'Application not found or access denied'];
    }
    
    // Basic validation
    if (empty($evaluation_data['rating']) || empty($evaluation_data['comments'])) {
        return ['error' => 'Missing required evaluation data (rating, comments)'];
    }
    
    $evaluation = [
        'evaluation_id' => rand(3000, 9999), // Simple unique ID
        'application_id' => $application_id,
        'company_id' => $company_id,
        'rating' => $evaluation_data['rating'],
        'comments' => $evaluation_data['comments'],
        'technical_skills' => $evaluation_data['technical_skills'] ?? null,
        'communication_skills' => $evaluation_data['communication_skills'] ?? null,
        'teamwork' => $evaluation_data['teamwork'] ?? null,
        'problem_solving' => $evaluation_data['problem_solving'] ?? null,
        'overall_performance' => $evaluation_data['overall_performance'] ?? null,
        'recommendation' => $evaluation_data['recommendation'] ?? null,
        'created_date' => date('Y-m-d H:i:s'),
        'evaluator_name' => $evaluation_data['evaluator_name'] ?? 'Company Representative'
    ];
    
    // In a real app, you would save this to the database
    return ['status' => 'success', 'message' => 'Evaluation created successfully', 'data' => $evaluation];
}

function get_company_evaluations($company_id) {
    // In a real app, you would fetch evaluations from the database
    // For now, return mock data
    return [
        [
            'evaluation_id' => 3001,
            'application_id' => 1001,
            'student_name' => 'John Doe',
            'internship_title' => 'Software Engineer Intern',
            'rating' => 4.5,
            'comments' => 'Excellent performance throughout the internship period.',
            'created_date' => '2024-10-15'
        ]
    ];
}

// --- Dashboard/Statistics --- //

function get_company_dashboard_stats($company_id) {
    global $applications, $internships;
    
    // Get company internships
    $company_internships = get_company_internships($company_id);
    $total_internships = count($company_internships);
    $active_internships = 0;
    
    foreach ($company_internships as $internship) {
        if (($internship['status'] ?? 'Active') === 'Active') {
            $active_internships++;
        }
    }
    
    // Get application statistics
    $company_applications = get_company_applications($company_id);
    $total_applications = count($company_applications);
    
    $status_counts = [
        'Pending' => 0,
        'Under Review' => 0,
        'Shortlisted' => 0,
        'Offered' => 0,
        'Accepted' => 0,
        'Rejected' => 0
    ];
    
    foreach ($company_applications as $application) {
        $status = $application['status'];
        if (isset($status_counts[$status])) {
            $status_counts[$status]++;
        }
    }
    
    return [
        'total_internships' => $total_internships,
        'active_internships' => $active_internships,
        'total_applications' => $total_applications,
        'application_status_breakdown' => $status_counts,
        'recent_applications' => array_slice($company_applications, -5) // Last 5 applications
    ];
}

// --- Helper Functions --- //

function validate_company_access($company_id) {
    global $companies;
    foreach ($companies as $company) {
        if ($company['id'] == $company_id) {
            return true;
        }
    }
    return false;
}

?>
