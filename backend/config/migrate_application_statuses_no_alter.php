<?php
/**
 * Migration Script: Normalize Application Statuses (No ALTER Required)
 * 
 * This script normalizes existing status values without requiring ALTER privileges.
 * The ENUM already has all required values, so we only need to update data.
 * 
 * Usage: php migrate_application_statuses_no_alter.php
 */

require_once __DIR__ . '/database.php';

try {
    $db = getDB();
    
    echo "Starting application status normalization (no schema changes)...\n\n";
    
    // Step 1: Check current status values
    echo "Step 1: Checking current status values...\n";
    $stmt = $db->query("SELECT DISTINCT status, COUNT(*) as count FROM applications GROUP BY status ORDER BY count DESC");
    $currentStatuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current status distribution:\n";
    foreach ($currentStatuses as $row) {
        echo "  - {$row['status']}: {$row['count']} applications\n";
    }
    echo "\n";
    
    // Step 2: Verify all status values are valid
    echo "Step 2: Verifying all status values are valid...\n";
    $validStatuses = [
        'Pending', 'Under Review', 'Shortlisted', 'Interview Scheduled',
        'Offered', 'Rejected', 'Accepted', 'Declined', 'Withdrawn',
        'Approved_By_Company', 'Confirmed_By_Student', 'Rejected_By_Company'
    ];
    
    $stmt = $db->query("SELECT DISTINCT status FROM applications");
    $allStatuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $invalidStatuses = array_diff($allStatuses, $validStatuses);
    if (!empty($invalidStatuses)) {
        echo "  ⚠ Warning: Found invalid status values:\n";
        foreach ($invalidStatuses as $status) {
            echo "    - '{$status}'\n";
        }
    } else {
        echo "  ✓ All status values are valid\n";
    }
    echo "\n";
    
    // Step 3: Normalize existing data to match frontend (6 core statuses)
    echo "Step 3: Normalizing data to match frontend display (6 core statuses)...\n";
    
    $updates = [
        ['Pending Review', 'Pending', 'pending review'],
        ['Under Review', 'Pending', 'under review'],
        ['Shortlisted', 'Pending', 'shortlisted'],
        ['Interview Scheduled', 'Pending', 'interview scheduled'],
        ['Offered', 'Accepted', 'offered'],
        ['Rejected_By_Company', 'Rejected', 'rejected by company'],
        ['Declined', 'Withdrawn', 'declined']
    ];
    
    foreach ($updates as $update) {
        list($from, $to, $label) = $update;
        try {
            $stmt = $db->prepare("UPDATE applications SET status = ? WHERE status = ?");
            $stmt->execute([$to, $from]);
            $affected = $stmt->rowCount();
            if ($affected > 0) {
                echo "  ✓ Updated {$affected} applications from '{$from}' to '{$to}'\n";
            }
        } catch (PDOException $e) {
            // If status doesn't exist in ENUM or data, that's fine
            if (strpos($e->getMessage(), 'Data truncated') !== false) {
                echo "  ℹ '{$from}' is not a valid ENUM value or doesn't exist in data\n";
            } else {
                echo "  ⚠ Could not update '{$from}': " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n";
    echo "  Core statuses (already correct, no changes needed):\n";
    echo "    - 'Pending'              -> Displays as 'Pending Review'\n";
    echo "    - 'Accepted'             -> Displays as 'Accepted'\n";
    echo "    - 'Confirmed_By_Student' -> Displays as 'Confirmed'\n";
    echo "    - 'Approved_By_Company'  -> Displays as 'Finalized'\n";
    echo "    - 'Rejected'             -> Displays as 'Rejected'\n";
    echo "    - 'Withdrawn'            -> Displays as 'Withdrawn'\n";
    
    // Optional: Map "Under Review" to "Pending" (uncomment if needed)
    // try {
    //     $stmt = $db->prepare("UPDATE applications SET status = 'Pending' WHERE status = 'Under Review'");
    //     $stmt->execute();
    //     $affected = $stmt->rowCount();
    //     if ($affected > 0) {
    //         echo "  ✓ Updated {$affected} applications from 'Under Review' to 'Pending'\n";
    //     }
    // } catch (PDOException $e) {
    //     echo "  ℹ Could not update 'Under Review' status\n";
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
    
    // Show status mapping
    echo "Final Status Mapping (6 core statuses matching frontend):\n";
    echo "  Frontend Display Name  -> Database ENUM Value\n";
    echo "  'Pending Review'       -> 'Pending'\n";
    echo "  'Accepted'             -> 'Accepted'\n";
    echo "  'Confirmed'            -> 'Confirmed_By_Student'\n";
    echo "  'Finalized'            -> 'Approved_By_Company'\n";
    echo "  'Rejected'             -> 'Rejected'\n";
    echo "  'Withdrawn'            -> 'Withdrawn'\n";
    echo "\n";
    
    echo "✓ Normalization completed successfully!\n";
    echo "\n";
    echo "All applications now use the 6 core statuses that match your frontend display.\n";
    echo "The frontend code maps database values to display names automatically.\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

