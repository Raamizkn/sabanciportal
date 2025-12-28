<?php
/**
 * Auto-close internships based on application deadlines
 * Run this via cron job daily
 * 
 * Usage: php backend/cron/auto_close_internships.php
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    
    // Close internships where application_deadline has passed and status is still Active
    $stmt = $db->prepare("
        UPDATE internships 
        SET status = 'Closed' 
        WHERE status = 'Active' 
        AND application_deadline IS NOT NULL 
        AND application_deadline < CURDATE()
    ");
    $stmt->execute();
    $closedCount = $stmt->rowCount();
    
    echo "Auto-closed {$closedCount} internship(s) based on application deadlines.\n";
    
} catch (PDOException $e) {
    error_log("Failed to auto-close internships: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

