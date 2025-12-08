<?php
/**
 * Quick Script: Create an Active Application Round
 * Run this script to automatically create an active application round
 * Usage: php backend/config/create_active_round.php
 */

require_once __DIR__ . '/database.php';

try {
    $db = getDB();
    
    // First, check what terms exist
    echo "Checking available terms...\n";
    $stmt = $db->query("SELECT id, name, start_date, end_date, is_active FROM terms ORDER BY start_date DESC");
    $terms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $targetTerm = null;
    
    if (empty($terms)) {
        echo "⚠ No terms found. Creating a new active term...\n";
        
        // Create a term for the current academic year
        $currentYear = date('Y');
        $nextYear = $currentYear + 1;
        $termName = "{$currentYear}-{$nextYear} Fall";
        $startDate = date('Y-m-d', strtotime('first day of September'));
        $endDate = date('Y-m-d', strtotime('last day of January next year'));
        
        // If we're past September, adjust dates
        if (date('m') > 9) {
            $startDate = date('Y-09-01');
            $endDate = date('Y-m-d', strtotime('+5 months', strtotime($startDate)));
        }
        
        $stmt = $db->prepare("
            INSERT INTO terms (name, start_date, end_date, is_active)
            VALUES (?, ?, ?, TRUE)
        ");
        $stmt->execute([$termName, $startDate, $endDate]);
        $termId = $db->lastInsertId();
        
        echo "✓ Created term: {$termName} (ID: {$termId})\n";
        echo "  Dates: {$startDate} to {$endDate}\n";
        
        $targetTerm = [
            'id' => $termId,
            'name' => $termName,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_active' => TRUE
        ];
    } else {
        echo "\nAvailable terms:\n";
        foreach ($terms as $term) {
            echo "  ID: {$term['id']} | {$term['name']} | {$term['start_date']} to {$term['end_date']} | Active: " . ($term['is_active'] ? 'Yes' : 'No') . "\n";
        }
        
        // Find active term, or use most recent term
        foreach ($terms as $term) {
            if ($term['is_active']) {
                $targetTerm = $term;
                break;
            }
        }
        
        if (!$targetTerm) {
            $targetTerm = $terms[0]; // Use most recent term
            echo "\n⚠ No active term found. Activating most recent term: {$targetTerm['name']}\n";
            
            // Activate the term
            $stmt = $db->prepare("UPDATE terms SET is_active = TRUE WHERE id = ?");
            $stmt->execute([$targetTerm['id']]);
            echo "✓ Activated term: {$targetTerm['name']}\n";
        } else {
            echo "\n✓ Using active term: {$targetTerm['name']}\n";
        }
    }
    
    // Check if there's already an active round
    $stmt = $db->prepare("SELECT * FROM application_rounds WHERE is_active = TRUE AND CURDATE() BETWEEN start_date AND end_date");
    $stmt->execute();
    $existingActiveRound = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingActiveRound) {
        echo "\n⚠ An active round already exists:\n";
        echo "  ID: {$existingActiveRound['id']} | {$existingActiveRound['name']} | {$existingActiveRound['start_date']} to {$existingActiveRound['end_date']}\n";
        echo "\nDo you want to create another round anyway? (This will create a new round but won't deactivate the existing one)\n";
        echo "To activate this new round, you'll need to deactivate the existing one via the admin interface.\n";
    }
    
    // Create the round
    $roundName = 'Application Round - ' . date('Y-m-d');
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime('+3 months'));
    
    echo "\nCreating application round...\n";
    echo "  Term: {$targetTerm['name']}\n";
    echo "  Name: {$roundName}\n";
    echo "  Start Date: {$startDate}\n";
    echo "  End Date: {$endDate}\n";
    echo "  Max Applications per Student: 3\n";
    echo "  Default Company Quota: 10\n";
    echo "  Active: Yes\n";
    
    $stmt = $db->prepare("
        INSERT INTO application_rounds 
        (term_id, name, start_date, end_date, max_applications_per_student, default_company_quota, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $targetTerm['id'],
        $roundName,
        $startDate,
        $endDate,
        3, // max_applications_per_student
        10, // default_company_quota
        TRUE // is_active
    ]);
    
    $roundId = $db->lastInsertId();
    echo "\n✓ Round created successfully! ID: {$roundId}\n";
    echo "\nYou can now apply for internships. The round is active and will accept applications until {$endDate}.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

