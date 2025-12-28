<?php

/**
 * Student Evaluations Handler
 * Handles students evaluating their internship experience at companies
 * 
 * This is SEPARATE from the evaluations table (which is company → student)
 * This handles student → company evaluations
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';

/**
 * Generate unique student evaluation ID
 */
function generate_student_eval_id() {
    $db = getDB();
    do {
        $id = 'SEVAL' . date('Ymd') . rand(1000, 9999);
        $stmt = $db->prepare("SELECT COUNT(*) FROM student_evaluations WHERE evaluation_id = ?");
        $stmt->execute([$id]);
    } while ($stmt->fetchColumn() > 0);
    return $id;
}

/**
 * Submit student evaluation
 * Student evaluates their internship experience
 * CONSTRAINT: Only COMPLETED internships can be evaluated
 */
function submit_student_evaluation($student_id, $application_id, $evaluation_data) {
    try {
        $db = getDB();
        $db->beginTransaction();
        
        // Get application and verify it belongs to the student
        $stmt = $db->prepare("
            SELECT a.*, i.company_id, i.company_name, i.position
            FROM applications a
            JOIN internships i ON a.internship_id = i.id
            WHERE a.application_id = ? AND a.student_id = ?
        ");
        $stmt->execute([$application_id, $student_id]);
        $application = $stmt->fetch();
        
        if (!$application) {
            $db->rollBack();
            return ['error' => 'Application not found or access denied'];
        }
        
        // IMPORTANT: Only allow evaluation of COMPLETED/FINALIZED internships
        // Include statuses that indicate the internship process is complete
        $allowed_statuses = ['Complete', 'Completed', 'Finalized', 'Approved_By_Company', 'Confirmed_By_Student'];
        if (!in_array($application['status'], $allowed_statuses)) {
            $db->rollBack();
            return ['error' => 'Can only evaluate completed internships. Current status: ' . $application['status'] . '. The internship must be approved/finalized before evaluation.'];
        }
        
        // Check if evaluation already exists
        $stmt = $db->prepare("SELECT id FROM student_evaluations WHERE application_id = ? AND student_id = ?");
        $stmt->execute([$application['id'], $student_id]);
        if ($stmt->fetch()) {
            $db->rollBack();
            return ['error' => 'You have already submitted an evaluation for this internship'];
        }
        
        // Generate evaluation ID
        $eval_id = generate_student_eval_id();
        
        // Extract rating values with proper field name mapping
        $programSatisfaction = intval($evaluation_data['program_satisfaction'] ?? 0);
        $companyImpression = intval($evaluation_data['company_impression'] ?? $evaluation_data['institution_selection'] ?? 0);
        $recommendProgram = intval($evaluation_data['recommend_program'] ?? $evaluation_data['future_participation'] ?? 0);
        $recommendCompany = intval($evaluation_data['recommend_company'] ?? $evaluation_data['institution_recommendation'] ?? 0);
        $consultantSatisfaction = intval($evaluation_data['consultant_satisfaction'] ?? 0);
        $consultantCare = $evaluation_data['consultant_care'] ?? 'no';
        
        // Calculate overall rating from numeric fields only (1-5 scale)
        $ratings = [
            $programSatisfaction,
            $companyImpression,
            $recommendProgram,
            $recommendCompany
        ];
        
        // Only include consultant satisfaction if they answered 'yes' to having a consultant
        if ($consultantCare === 'yes' && $consultantSatisfaction > 0) {
            $ratings[] = $consultantSatisfaction;
        }
        
        // Filter out zeros and calculate average
        $valid_ratings = array_filter($ratings, function($r) { return $r > 0; });
        $overall_rating = count($valid_ratings) > 0 ? array_sum($valid_ratings) / count($valid_ratings) : 0;
        
        // Insert evaluation with properly mapped values
        $stmt = $db->prepare("
            INSERT INTO student_evaluations (
                evaluation_id, application_id, student_id, company_id,
                program_satisfaction, future_participation, consultant_care,
                consultant_satisfaction, institution_selection, institution_recommendation,
                benefits, department, problems_encountered, additional_feedback,
                overall_rating, submitted_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())
        ");
        
        $stmt->execute([
            $eval_id,
            $application['id'],
            $student_id,
            $application['company_id'],
            $programSatisfaction,                    // program_satisfaction
            $recommendProgram,                       // future_participation (recommend program)
            $consultantCare,                         // consultant_care
            $consultantSatisfaction,                 // consultant_satisfaction
            $companyImpression,                      // institution_selection (company impression)
            $recommendCompany,                       // institution_recommendation (recommend company)
            $evaluation_data['benefits'] ?? '',
            $evaluation_data['department'] ?? '',
            $evaluation_data['problems'] ?? '',
            $evaluation_data['additional_feedback'] ?? '',
            round($overall_rating, 2)
        ]);
        
        $db->commit();
        
        return [
            'status' => 'success',
            'message' => 'Evaluation submitted successfully',
            'data' => [
                'evaluation_id' => $eval_id,
                'overall_rating' => round($overall_rating, 2),
                'company_name' => $application['company_name'],
                'position' => $application['position']
            ]
        ];
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        error_log("Student evaluation submission failed: " . $e->getMessage());
        return ['error' => 'Failed to submit evaluation: ' . $e->getMessage()];
    }
}

/**
 * Get all evaluations submitted by a student
 */
function get_student_evaluations($student_id) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT se.*, 
                   a.application_id,
                   i.company_name, i.position, i.location,
                   c.name as company_full_name
            FROM student_evaluations se
            JOIN applications a ON se.application_id = a.id
            JOIN internships i ON a.internship_id = i.id
            JOIN companies c ON se.company_id = c.id
            WHERE se.student_id = ?
            ORDER BY se.submitted_date DESC
        ");
        $stmt->execute([$student_id]);
        $evaluations = $stmt->fetchAll();
        
        return [
            'status' => 'success',
            'data' => $evaluations
        ];
    } catch (PDOException $e) {
        error_log("Failed to fetch student evaluations: " . $e->getMessage());
        return ['error' => 'Failed to fetch evaluations'];
    }
}

/**
 * Get evaluation details by ID
 */
function get_student_evaluation_details($evaluation_id, $student_id) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT se.*,
                   a.application_id,
                   i.company_name, i.position, i.location, i.dates,
                   c.name as company_full_name
            FROM student_evaluations se
            JOIN applications a ON se.application_id = a.id
            JOIN internships i ON a.internship_id = i.id
            JOIN companies c ON se.company_id = c.id
            WHERE se.evaluation_id = ? AND se.student_id = ?
        ");
        $stmt->execute([$evaluation_id, $student_id]);
        $evaluation = $stmt->fetch();
        
        if (!$evaluation) {
            return ['error' => 'Evaluation not found or access denied'];
        }
        
        return [
            'status' => 'success',
            'data' => $evaluation
        ];
    } catch (PDOException $e) {
        error_log("Failed to fetch evaluation details: " . $e->getMessage());
        return ['error' => 'Failed to fetch evaluation'];
    }
}

/**
 * Admin: Get all student evaluations (optional - for admin view)
 */
function get_all_student_evaluations_admin() {
    requireRole(ROLE_ADMIN);
    
    try {
        $db = getDB();
        
        $stmt = $db->query("
            SELECT se.*,
                   s.name as student_name, s.email as student_email,
                   i.company_name, i.position,
                   a.application_id
            FROM student_evaluations se
            JOIN students s ON se.student_id = s.id
            JOIN applications a ON se.application_id = a.id
            JOIN internships i ON a.internship_id = i.id
            ORDER BY se.submitted_date DESC
        ");
        $evaluations = $stmt->fetchAll();
        
        return [
            'status' => 'success',
            'data' => $evaluations
        ];
    } catch (PDOException $e) {
        error_log("Failed to fetch all student evaluations: " . $e->getMessage());
        return ['error' => 'Failed to fetch evaluations'];
    }
}

/**
 * Get pending evaluations for a student
 * Returns applications that student completed but hasn't evaluated yet
 */
function get_pending_student_evaluations($student_id) {
    try {
        $db = getDB();
        
        // Get applications where student was confirmed but hasn't submitted evaluation
        $stmt = $db->prepare("
            SELECT a.application_id, a.status,
                   i.company_name, i.position, i.location, i.dates,
                   (SELECT COUNT(*) FROM student_evaluations se WHERE se.application_id = a.id) as has_evaluation
            FROM applications a
            JOIN internships i ON a.internship_id = i.id
            WHERE a.student_id = ?
            AND a.status IN ('Confirmed_By_Student', 'Approved_By_Company', 'Finalized')
            HAVING has_evaluation = 0
            ORDER BY a.status_updated_date DESC
        ");
        $stmt->execute([$student_id]);
        $pending = $stmt->fetchAll();
        
        return [
            'status' => 'success',
            'data' => $pending
        ];
    } catch (PDOException $e) {
        error_log("Failed to fetch pending evaluations: " . $e->getMessage());
        return ['error' => 'Failed to fetch pending evaluations'];
    }
}

