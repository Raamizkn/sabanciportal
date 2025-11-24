<?php
/**
 * Migration Script: Update Application Statuses
 * 
 * This script ensures the applications table has the correct ENUM values
 * and normalizes existing data to match our finalized workflow.
 * 
 * Usage: php migrate_application_statuses.php
 */

require_once __DIR__ . '/database.php';

try {
    $db = getDB();
    
    echo "Starting application status migration...\n\n";
    
    // Step 1: Check current status values
    echo "Step 1: Checking current status values...\n";
    $stmt = $db->query("SELECT DISTINCT status, COUNT(*) as count FROM applications GROUP BY status");
    $currentStatuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current status distribution:\n";
    foreach ($currentStatuses as $row) {
        echo "  - {$row['status']}: {$row['count']} applications\n";
    }
    echo "\n";
    
    // Step 2: Modify ENUM column
    echo "Step 2: Updating ENUM column definition...\n";
    $sql = "ALTER TABLE applications 
            MODIFY COLUMN status ENUM(
                'Pending',
                'Under Review',
                'Shortlisted',
                'Interview Scheduled',
                'Offered',
                'Accepted',
                'Confirmed_By_Student',
                'Approved_By_Company',
                'Rejected',
                'Rejected_By_Company',
                'Declined',
                'Withdrawn'
            ) DEFAULT 'Pending'";
    
    $db->exec($sql);
    echo "✓ ENUM column updated successfully\n\n";
    
    // Step 3: Normalize existing data
    echo "Step 3: Normalizing existing data...\n";
    
    // Map "Pending Review" to "Pending" if it exists
    $stmt = $db->prepare("UPDATE applications SET status = 'Pending' WHERE status = 'Pending Review'");
    $stmt->execute();
    $affected = $stmt->rowCount();
    if ($affected > 0) {
        echo "  ✓ Updated {$affected} applications from 'Pending Review' to 'Pending'\n";
    }
    
    // Optional: Map "Under Review" to "Pending" (uncomment if needed)
    // $stmt = $db->prepare("UPDATE applications SET status = 'Pending' WHERE status = 'Under Review'");
    // $stmt->execute();
    // $affected = $stmt->rowCount();
    // if ($affected > 0) {
    //     echo "  ✓ Updated {$affected} applications from 'Under Review' to 'Pending'\n";
    // }
    
    echo "\n";
    
    // Step 4: Verify final state
    echo "Step 4: Verifying final status distribution...\n";
    $stmt = $db->query("SELECT DISTINCT status, COUNT(*) as count FROM applications GROUP BY status ORDER BY count DESC");
    $finalStatuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Final status distribution:\n";
    foreach ($finalStatuses as $row) {
        echo "  - {$row['status']}: {$row['count']} applications\n";
    }
    echo "\n";
    
    // Show ENUM definition
    echo "Step 5: Verifying ENUM definition...\n";
    $stmt = $db->query("SHOW COLUMNS FROM applications LIKE 'status'");
    $columnInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Status column definition:\n";
    echo "  Type: {$columnInfo['Type']}\n";
    echo "  Default: {$columnInfo['Default']}\n";
    echo "\n";
    
    echo "✓ Migration completed successfully!\n";
    echo "\n";
    echo "Status Mapping Reference:\n";
    echo "  Database Value          -> Display Name\n";
    echo "  'Pending'              -> 'Pending Review'\n";
    echo "  'Accepted'             -> 'Accepted'\n";
    echo "  'Confirmed_By_Student' -> 'Confirmed'\n";
    echo "  'Approved_By_Company'  -> 'Finalized'\n";
    echo "  'Rejected'             -> 'Rejected'\n";
    echo "  'Withdrawn'            -> 'Withdrawn'\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

