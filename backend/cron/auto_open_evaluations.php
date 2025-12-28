<?php
/**
 * Auto-open evaluations after internship end date
 * This marks applications as eligible for evaluation
 * Run this via cron job daily
 * 
 * Usage: php backend/cron/auto_open_evaluations.php
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    
    // Get applications with confirmed status where internship end date has passed
    // We need to parse the dates field from internships table
    // For now, we'll check if the term's end_date has passed for confirmed applications
    $stmt = $db->prepare("
        UPDATE applications a
        JOIN internships i ON a.internship_id = i.id
        JOIN terms t ON i.term_id = t.id
        SET a.evaluation_eligible = 1
        WHERE a.status = 'Confirmed_By_Student'
        AND a.evaluation_eligible IS NULL
        AND t.end_date < CURDATE()
    ");
    
    // First check if evaluation_eligible column exists
    $colCheck = $db->query("SHOW COLUMNS FROM applications LIKE 'evaluation_eligible'");
    if ($colCheck->rowCount() === 0) {
        // Add column if it doesn't exist
        $db->exec("ALTER TABLE applications ADD COLUMN evaluation_eligible BOOLEAN DEFAULT 0");
    }
    
    $stmt->execute();
    $openedCount = $stmt->rowCount();
    
    echo "Auto-opened evaluations for {$openedCount} confirmed application(s).\n";
    
} catch (PDOException $e) {
    error_log("Failed to auto-open evaluations: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

