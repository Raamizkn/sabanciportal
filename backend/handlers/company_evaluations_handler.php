<?php

/**
 * Company Evaluations Handler
 * Handles companies evaluating students who completed internships
 * 
 * This uses the existing 'evaluations' table (company → student)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';

/**
 * Generate unique company evaluation ID
 */
function generate_company_eval_id() {
    $db = getDB();
    do {
        $id = 'CEVAL' . date('Ymd') . rand(1000, 9999);
        $stmt = $db->prepare("SELECT COUNT(*) FROM evaluations WHERE evaluation_id = ?");
        $stmt->execute([$id]);
    } while ($stmt->fetchColumn() > 0);
    return $id;
}

/**
 * Get students eligible for evaluation by a company
 * CONSTRAINT: Only students with COMPLETED internships can be evaluated
 */
function get_evaluable_students($company_id) {
    try {
        $db = getDB();
        
        // Get students with COMPLETED applications at this company
        // who haven't been evaluated yet
        // IMPORTANT: Only 'Complete', 'Completed', 'Finalized' statuses are allowed
        $stmt = $db->prepare("
            SELECT 
                a.id as application_id,
                a.application_id as application_code,
                a.student_id,
                a.status,
                a.status_updated_date,
                s.name as student_name,
                s.email as student_email,
                s.student_id as student_number,
                s.major,
                s.profile_pic,
                i.id as internship_id,
                i.position,
                i.dates,
                (SELECT COUNT(*) FROM evaluations e WHERE e.application_id = a.id AND e.company_id = ?) as has_evaluation
            FROM applications a
            JOIN students s ON a.student_id = s.id
            JOIN internships i ON a.internship_id = i.id
            WHERE i.company_id = ?
            AND a.status IN ('Complete', 'Completed', 'Finalized')
            HAVING has_evaluation = 0
            ORDER BY a.status_updated_date DESC
        ");
        $stmt->execute([$company_id, $company_id]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'status' => 'success',
            'data' => $students
        ];
    } catch (PDOException $e) {
        error_log("Failed to fetch evaluable students: " . $e->getMessage());
        return ['error' => 'Failed to fetch students eligible for evaluation'];
    }
}

/**
 * Submit company evaluation of a student
 */
function submit_company_evaluation($company_id, $application_id, $evaluation_data) {
    try {
        $db = getDB();
        $db->beginTransaction();
        
        // Get application and verify it belongs to the company's internship
        $stmt = $db->prepare("
            SELECT a.*, i.company_id, i.position, s.name as student_name, s.email as student_email
            FROM applications a
            JOIN internships i ON a.internship_id = i.id
            JOIN students s ON a.student_id = s.id
            WHERE a.id = ? AND i.company_id = ?
        ");
        $stmt->execute([$application_id, $company_id]);
        $application = $stmt->fetch();
        
        if (!$application) {
            $db->rollBack();
            return ['error' => 'Application not found or access denied'];
        }
        
        // IMPORTANT: Check if the application is in a COMPLETED status
        // Only 'Complete', 'Completed', 'Finalized' statuses are allowed
        $allowed_statuses = ['Complete', 'Completed', 'Finalized'];
        if (!in_array($application['status'], $allowed_statuses)) {
            $db->rollBack();
            return ['error' => 'Can only evaluate students with completed internships. Current status: ' . $application['status'] . '. The internship must be marked as Complete before evaluation.'];
        }
        
        // Check if evaluation already exists
        $stmt = $db->prepare("SELECT id FROM evaluations WHERE application_id = ? AND company_id = ?");
        $stmt->execute([$application_id, $company_id]);
        if ($stmt->fetch()) {
            $db->rollBack();
            return ['error' => 'You have already submitted an evaluation for this student'];
        }
        
        // Generate evaluation ID
        $eval_id = generate_company_eval_id();
        
        // Calculate overall rating from the rating fields (1-5 scale)
        $ratings = [
            intval($evaluation_data['program_satisfaction'] ?? 0),
            intval($evaluation_data['student_impact'] ?? 0),
            intval($evaluation_data['motivation'] ?? 0),
            intval($evaluation_data['communication'] ?? 0),
            intval($evaluation_data['timeliness'] ?? 0),
            intval($evaluation_data['positive_attitude'] ?? 0),
            intval($evaluation_data['teamwork'] ?? 0),
            intval($evaluation_data['adaptation'] ?? 0),
            intval($evaluation_data['digital_tools'] ?? 0)
        ];
        
        // Filter out zeros and calculate average
        $valid_ratings = array_filter($ratings, function($r) { return $r > 0; });
        $overall_rating = count($valid_ratings) > 0 ? array_sum($valid_ratings) / count($valid_ratings) : 0;
        
        // Insert evaluation
        $stmt = $db->prepare("
            INSERT INTO evaluations (
                evaluation_id, application_id, company_id, student_id,
                program_satisfaction, student_impact, motivation, communication,
                timeliness, positive_attitude, teamwork, adaptation, digital_tools,
                participate_again, recommend_program, future_internship, interview_interest,
                redesign_suggestions, program_type,
                overall_rating, evaluator_name, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $eval_id,
            $application_id,
            $company_id,
            $application['student_id'],
            intval($evaluation_data['program_satisfaction'] ?? 0),
            intval($evaluation_data['student_impact'] ?? 0),
            intval($evaluation_data['motivation'] ?? 0),
            intval($evaluation_data['communication'] ?? 0),
            intval($evaluation_data['timeliness'] ?? 0),
            intval($evaluation_data['positive_attitude'] ?? 0),
            intval($evaluation_data['teamwork'] ?? 0),
            intval($evaluation_data['adaptation'] ?? 0),
            intval($evaluation_data['digital_tools'] ?? 0),
            $evaluation_data['participate_again'] ?? 'no',
            $evaluation_data['recommend_program'] ?? 'no',
            $evaluation_data['future_internship'] ?? 'no',
            $evaluation_data['interview_interest'] ?? 'no',
            $evaluation_data['redesign_suggestions'] ?? '',
            $evaluation_data['program_type'] ?? '',
            round($overall_rating, 2),
            $evaluation_data['evaluator_name'] ?? ''
        ]);
        
        $db->commit();
        
        return [
            'status' => 'success',
            'message' => 'Evaluation submitted successfully',
            'data' => [
                'evaluation_id' => $eval_id,
                'overall_rating' => round($overall_rating, 2),
                'student_name' => $application['student_name'],
                'position' => $application['position']
            ]
        ];
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        error_log("Company evaluation submission failed: " . $e->getMessage());
        return ['error' => 'Failed to submit evaluation: ' . $e->getMessage()];
    }
}

/**
 * Get all evaluations submitted by a company
 */
function get_company_evaluations($company_id) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT e.*, 
                   a.application_id as application_code,
                   s.name as student_name, s.email as student_email, s.profile_pic,
                   i.position, i.company_name
            FROM evaluations e
            JOIN applications a ON e.application_id = a.id
            JOIN students s ON e.student_id = s.id
            JOIN internships i ON a.internship_id = i.id
            WHERE e.company_id = ?
            ORDER BY e.created_at DESC
        ");
        $stmt->execute([$company_id]);
        $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'status' => 'success',
            'data' => $evaluations
        ];
    } catch (PDOException $e) {
        error_log("Failed to fetch company evaluations: " . $e->getMessage());
        return ['error' => 'Failed to fetch evaluations'];
    }
}

/**
 * Get evaluation details by ID
 */
function get_company_evaluation_details($evaluation_id, $company_id) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT e.*,
                   a.application_id as application_code, a.status as application_status,
                   s.name as student_name, s.email as student_email, s.profile_pic, s.major,
                   i.position, i.company_name, i.dates, i.location
            FROM evaluations e
            JOIN applications a ON e.application_id = a.id
            JOIN students s ON e.student_id = s.id
            JOIN internships i ON a.internship_id = i.id
            WHERE e.evaluation_id = ? AND e.company_id = ?
        ");
        $stmt->execute([$evaluation_id, $company_id]);
        $evaluation = $stmt->fetch(PDO::FETCH_ASSOC);
        
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


