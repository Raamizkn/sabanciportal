<?php

/**
 * Company Evaluations Handler
 * Handles companies evaluating students after internship completion
 * 
 * This handles company → student evaluations
 * Stored in the 'evaluations' table
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';

/**
 * Get students that a company can evaluate
 * Only students with CONFIRMED applications can be evaluated
 */
function get_evaluable_students($company_id) {
    try {
        $db = getDB();
        
        // Get confirmed applications where company hasn't submitted evaluation yet
        $stmt = $db->prepare("
            SELECT 
                a.id as application_id,
                a.application_id as application_code,
                a.status,
                a.created_at as application_date,
                s.id as student_id,
                s.name as student_name,
                s.email as student_email,
                s.student_id as student_number,
                s.major as student_major,
                s.profile_pic as student_profile_pic,
                i.id as internship_id,
                i.position as internship_position,
                i.dates as internship_dates,
                i.location as internship_location,
                (SELECT COUNT(*) FROM evaluations e WHERE e.application_id = a.id AND e.company_id = ?) as has_evaluation
            FROM applications a
            JOIN students s ON a.student_id = s.id
            JOIN internships i ON a.internship_id = i.id
            WHERE i.company_id = ?
            AND a.status = 'Confirmed'
            HAVING has_evaluation = 0
            ORDER BY a.created_at DESC
        ");
        $stmt->execute([$company_id, $company_id]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'status' => 'success',
            'data' => $students
        ];
    } catch (PDOException $e) {
        error_log("Failed to fetch evaluable students: " . $e->getMessage());
        return ['error' => 'Failed to fetch evaluable students'];
    }
}

/**
 * Submit company evaluation of a student
 */
function submit_company_evaluation($company_id, $application_id, $evaluation_data) {
    try {
        $db = getDB();
        $db->beginTransaction();
        
        // Get application and verify it belongs to this company
        $stmt = $db->prepare("
            SELECT a.*, i.company_id, i.position, s.id as student_id, s.name as student_name
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
        
        // Check status - only CONFIRMED applications can be evaluated
        if ($application['status'] !== 'Confirmed') {
            $db->rollBack();
            return ['error' => 'Can only evaluate students with confirmed internships. Current status: ' . $application['status']];
        }
        
        // Check if evaluation already exists
        $stmt = $db->prepare("SELECT id FROM evaluations WHERE application_id = ? AND company_id = ?");
        $stmt->execute([$application_id, $company_id]);
        if ($stmt->fetch()) {
            $db->rollBack();
            return ['error' => 'You have already submitted an evaluation for this student'];
        }
        
        // Calculate overall rating from numeric fields (1-5 scale)
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
        
        // Filter out zero values and calculate average
        $valid_ratings = array_filter($ratings, function($r) { return $r > 0; });
        $overall_rating = count($valid_ratings) > 0 ? array_sum($valid_ratings) / count($valid_ratings) : 0;
        
        // Insert evaluation
        $stmt = $db->prepare("
            INSERT INTO evaluations (
                application_id, student_id, company_id,
                program_satisfaction, student_impact, motivation,
                communication, timeliness, positive_attitude,
                teamwork, adaptation, digital_tools,
                participate_again, recommend_program, future_internship, interview,
                redesign_suggestions, program_type,
                overall_rating, submitted_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $application_id,
            $application['student_id'],
            $company_id,
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
            $evaluation_data['interview'] ?? 'no',
            $evaluation_data['redesign'] ?? '',
            $evaluation_data['program_type'] ?? '',
            round($overall_rating, 2)
        ]);
        
        $eval_id = $db->lastInsertId();
        
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
                   s.name as student_name,
                   s.email as student_email,
                   s.profile_pic as student_profile_pic,
                   s.major as student_major,
                   i.position as internship_position,
                   i.dates as internship_dates,
                   a.application_id as application_code
            FROM evaluations e
            JOIN students s ON e.student_id = s.id
            JOIN applications a ON e.application_id = a.id
            JOIN internships i ON a.internship_id = i.id
            WHERE e.company_id = ?
            ORDER BY e.submitted_at DESC
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
                   s.name as student_name,
                   s.email as student_email,
                   s.profile_pic as student_profile_pic,
                   s.major as student_major,
                   i.position as internship_position,
                   i.dates as internship_dates,
                   i.location as internship_location,
                   a.application_id as application_code
            FROM evaluations e
            JOIN students s ON e.student_id = s.id
            JOIN applications a ON e.application_id = a.id
            JOIN internships i ON a.internship_id = i.id
            WHERE e.id = ? AND e.company_id = ?
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

?>
