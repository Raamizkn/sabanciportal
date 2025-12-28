<?php
/**
 * Terms Helper Functions
 * Replaces rounds_handler.php functionality - terms are now the supreme layer
 */

global $db;

/**
 * Get active term for a given date (or today)
 * Terms must be active (is_active = TRUE) and contain the date
 */
function get_active_term($date = null) {
    global $db;
    if (!$date) {
        $date = date('Y-m-d');
    }
    
    try {
        $stmt = $db->prepare("
            SELECT * FROM terms
            WHERE is_active = 1 
            AND start_date <= ? AND end_date >= ?
            ORDER BY start_date DESC
            LIMIT 1
        ");
        $stmt->execute([$date, $date]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Get term for a given date (or today)
 * Returns the term that contains the date, even if inactive
 */
function get_term_for_date($date = null) {
    global $db;
    if (!$date) {
        $date = date('Y-m-d');
    }
    
    try {
        // First, try to find an active term that contains the date
        $stmt = $db->prepare("
            SELECT * FROM terms
            WHERE is_active = 1 
            AND start_date <= ? AND end_date >= ?
            ORDER BY start_date DESC
            LIMIT 1
        ");
        $stmt->execute([$date, $date]);
        $term = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If no active term found for the date, try to find any term containing the date
        if (!$term) {
            $stmt = $db->prepare("
                SELECT * FROM terms
                WHERE start_date <= ? AND end_date >= ?
                ORDER BY start_date DESC
                LIMIT 1
            ");
            $stmt->execute([$date, $date]);
            $term = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // If still no term found, get the most recent active term
        if (!$term) {
            $stmt = $db->prepare("
                SELECT * FROM terms
                WHERE is_active = 1
                ORDER BY start_date DESC
                LIMIT 1
            ");
            $stmt->execute();
            $term = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return $term;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Get effective quota for a company in a term
 * Uses company.company_quota if set, otherwise defaults to 10
 */
function get_effective_quota_for_term($company_id, $term_id) {
    global $db;
    try {
        $stmt = $db->prepare("SELECT company_quota FROM companies WHERE id = ?");
        $stmt->execute([$company_id]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($company && $company['company_quota'] !== null) {
            return (int) $company['company_quota'];
        }
        
        // Default quota
        return 10;
    } catch (PDOException $e) {
        return 10; // Default on error
    }
}

/**
 * Get used quota for a company in a term
 * Counts confirmed applications (Confirmed_By_Student is the final status)
 */
function get_used_quota_for_term($company_id, $term_id) {
    global $db;
    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) as count
            FROM applications a
            JOIN internships i ON a.internship_id = i.id
            WHERE i.company_id = ?
            AND a.term_id = ?
            AND a.status = 'Confirmed_By_Student'
        ");
        $stmt->execute([$company_id, $term_id]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Get max applications per student for a term
 */
function get_max_applications_for_term($term_id) {
    global $db;
    try {
        $stmt = $db->prepare("SELECT max_applications_per_student FROM terms WHERE id = ?");
        $stmt->execute([$term_id]);
        $term = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($term && $term['max_applications_per_student'] !== null) {
            return (int) $term['max_applications_per_student'];
        }
        
        // Default
        return 3;
    } catch (PDOException $e) {
        return 3; // Default on error
    }
}

