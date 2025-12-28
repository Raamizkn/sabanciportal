<?php
/**
 * Company Evaluations Handler
 * Handles company-side evaluation of students who completed internships
 * 
 * Field Mapping (Frontend → Database):
 * - program_satisfaction → overall_performance
 * - student_impact → technical_skills
 * - motivation → problem_solving
 * - communication → communication_skills
 * - teamwork → teamwork
 * - timeliness, positive_attitude, adaptation, digital_tools → stored in comments as JSON
 * - yes/no questions → stored in recommendation field
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';

/**
 * Get students eligible for evaluation
 * Returns students with Confirmed applications at this company
 */
function get_evaluable_students($company_id) {
    try {
        $db = getDB();
        
        // Get applications that are Confirmed and haven't been evaluated yet
        $sql = "SELECT 
                    a.id AS application_id,
                    a.student_id,
                    s.name AS student_name,
                    s.email AS student_email,
                    s.profile_pic AS student_profile_pic,
                    i.title AS internship_position,
                    i.id AS internship_id,
                    a.status
                FROM applications a
                JOIN students s ON a.student_id = s.id
                JOIN internships i ON a.internship_id = i.id
                WHERE i.company_id = :company_id
                AND a.status IN ('Confirmed', 'Confirmed_By_Student', 'confirmed')
                AND NOT EXISTS (
                    SELECT 1 FROM evaluations e 
                    WHERE e.application_id = a.id
                )
                ORDER BY s.name";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':company_id' => $company_id]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'status' => 'success',
            'data' => $students
        ];
    } catch (PDOException $e) {
        error_log("Error getting evaluable students: " . $e->getMessage());
        return [
            'status' => 'error',
            'error' => 'Failed to load students'
        ];
    }
}

/**
 * Submit a company evaluation for a student
 */
function submit_company_evaluation($company_id, $application_id, $evaluation_data) {
    try {
        $db = getDB();
        
        // Verify the application belongs to this company and is confirmed
        $sql = "SELECT a.*, i.company_id, a.student_id
                FROM applications a
                JOIN internships i ON a.internship_id = i.id
                WHERE a.id = :application_id 
                AND i.company_id = :company_id
                AND a.status IN ('Confirmed', 'Confirmed_By_Student', 'confirmed')";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':application_id' => $application_id,
            ':company_id' => $company_id
        ]);
        
        $application = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$application) {
            return [
                'status' => 'error',
                'error' => 'Invalid application or not confirmed for evaluation'
            ];
        }
        
        // Check if already evaluated
        $checkSql = "SELECT id FROM evaluations WHERE application_id = :application_id";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->execute([':application_id' => $application_id]);
        
        if ($checkStmt->fetch()) {
            return [
                'status' => 'error', 
                'error' => 'This student has already been evaluated'
            ];
        }
        
        // Map frontend fields to database columns
        // Main rating fields (5 mapped to DB columns)
        $program_satisfaction = intval($evaluation_data['program_satisfaction'] ?? 0);
        $student_impact = intval($evaluation_data['student_impact'] ?? 0);
        $motivation = intval($evaluation_data['motivation'] ?? 0);
        $communication = intval($evaluation_data['communication'] ?? 0);
        $teamwork_rating = intval($evaluation_data['teamwork'] ?? 0);
        
        // Additional ratings stored in comments JSON
        $extra_ratings = [
            'timeliness' => intval($evaluation_data['timeliness'] ?? 0),
            'positive_attitude' => intval($evaluation_data['positive_attitude'] ?? 0),
            'adaptation' => intval($evaluation_data['adaptation'] ?? 0),
            'digital_tools' => intval($evaluation_data['digital_tools'] ?? 0),
            'participate_again' => $evaluation_data['participate_again'] ?? 'no',
            'recommend_program' => $evaluation_data['recommend_program'] ?? 'no',
            'future_internship' => $evaluation_data['future_internship'] ?? 'no',
            'interview' => $evaluation_data['interview'] ?? 'no',
            'redesign' => $evaluation_data['redesign'] ?? ''
        ];
        
        // Calculate overall rating from ALL 9 numeric ratings
        $all_ratings = [
            $program_satisfaction,
            $student_impact,
            $motivation,
            $communication,
            $teamwork_rating,
            $extra_ratings['timeliness'],
            $extra_ratings['positive_attitude'],
            $extra_ratings['adaptation'],
            $extra_ratings['digital_tools']
        ];
        
        $valid_ratings = array_filter($all_ratings, function($r) { return $r > 0; });
        $overall_rating = count($valid_ratings) > 0 ? array_sum($valid_ratings) / count($valid_ratings) : 0;
        
        // Build recommendation string from yes/no answers
        $recommendations = [];
        if ($extra_ratings['participate_again'] === 'yes') $recommendations[] = 'participate_again';
        if ($extra_ratings['recommend_program'] === 'yes') $recommendations[] = 'recommend_program';
        if ($extra_ratings['future_internship'] === 'yes') $recommendations[] = 'future_internship';
        if ($extra_ratings['interview'] === 'yes') $recommendations[] = 'interview';
        $recommendation_str = implode(',', $recommendations);
        
        // Store extra data as JSON in comments
        $comments_json = json_encode($extra_ratings);
        
        // Generate unique evaluation ID
        $eval_id = 'CEVAL' . date('Ymd') . rand(1000, 9999);
        
        // Insert using existing database columns
        $insertSql = "INSERT INTO evaluations (
                        evaluation_id,
                        application_id,
                        student_id,
                        company_id,
                        technical_skills,
                        communication_skills,
                        teamwork,
                        problem_solving,
                        overall_performance,
                        rating,
                        comments,
                        recommendation,
                        evaluator_name
                      ) VALUES (
                        :evaluation_id,
                        :application_id,
                        :student_id,
                        :company_id,
                        :technical_skills,
                        :communication_skills,
                        :teamwork,
                        :problem_solving,
                        :overall_performance,
                        :rating,
                        :comments,
                        :recommendation,
                        :evaluator_name
                      )";
        
        $stmt = $db->prepare($insertSql);
        $stmt->execute([
            ':evaluation_id' => $eval_id,
            ':application_id' => $application_id,
            ':student_id' => $application['student_id'],
            ':company_id' => $company_id,
            ':technical_skills' => $student_impact,           // Student Impact → technical_skills
            ':communication_skills' => $communication,        // Communication → communication_skills
            ':teamwork' => $teamwork_rating,                  // Teamwork → teamwork
            ':problem_solving' => $motivation,                // Motivation → problem_solving
            ':overall_performance' => $program_satisfaction,  // Program Satisfaction → overall_performance
            ':rating' => round($overall_rating, 2),
            ':comments' => $comments_json,
            ':recommendation' => $recommendation_str,
            ':evaluator_name' => 'Company Evaluation'
        ]);
        
        return [
            'status' => 'success',
            'message' => 'Evaluation submitted successfully',
            'evaluation_id' => $eval_id
        ];
        
    } catch (PDOException $e) {
        error_log("Error submitting company evaluation: " . $e->getMessage());
        return [
            'status' => 'error',
            'error' => 'Failed to submit evaluation: ' . $e->getMessage()
        ];
    }
}

/**
 * Get all evaluations submitted by a company
 */
function get_company_evaluations($company_id) {
    try {
        $db = getDB();
        
        $sql = "SELECT 
                    e.id,
                    e.application_id,
                    e.rating,
                    e.technical_skills,
                    e.communication_skills,
                    e.teamwork,
                    e.problem_solving,
                    e.overall_performance,
                    e.comments,
                    e.recommendation,
                    e.created_at,
                    s.name AS student_name,
                    s.email AS student_email,
                    s.profile_pic AS student_profile_pic,
                    i.title AS internship_position
                FROM evaluations e
                JOIN applications a ON e.application_id = a.id
                JOIN students s ON e.student_id = s.id
                JOIN internships i ON a.internship_id = i.id
                WHERE e.company_id = :company_id
                ORDER BY e.created_at DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':company_id' => $company_id]);
        $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'status' => 'success',
            'data' => $evaluations
        ];
    } catch (PDOException $e) {
        error_log("Error getting company evaluations: " . $e->getMessage());
        return [
            'status' => 'error',
            'error' => 'Failed to load evaluations'
        ];
    }
}

/**
 * Get details of a specific evaluation
 */
function get_company_evaluation_details($evaluation_id, $company_id) {
    try {
        $db = getDB();
        
        $sql = "SELECT 
                    e.*,
                    s.name AS student_name,
                    s.email AS student_email,
                    i.title AS internship_position
                FROM evaluations e
                JOIN applications a ON e.application_id = a.id
                JOIN students s ON e.student_id = s.id
                JOIN internships i ON a.internship_id = i.id
                WHERE e.id = :evaluation_id
                AND e.company_id = :company_id";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':evaluation_id' => $evaluation_id,
            ':company_id' => $company_id
        ]);
        
        $evaluation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$evaluation) {
            return [
                'status' => 'error',
                'error' => 'Evaluation not found'
            ];
        }
        
        return [
            'status' => 'success',
            'data' => $evaluation
        ];
    } catch (PDOException $e) {
        error_log("Error getting evaluation details: " . $e->getMessage());
        return [
            'status' => 'error',
            'error' => 'Failed to load evaluation details'
        ];
    }
}
