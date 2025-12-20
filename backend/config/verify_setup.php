<?php
/**
 * Setup Verification Script
 * Verifies database setup, migrations, and security configurations
 * Usage: php backend/config/verify_setup.php
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/security.php';

echo "=== Sabancı Internship Portal - Setup Verification ===\n\n";

$allGood = true;

// 1. Database Connection
echo "1. Testing database connection...\n";
try {
    $db = getDB();
    echo "   ✓ Database connection successful\n";
} catch (Exception $e) {
    echo "   ❌ Database connection failed: " . $e->getMessage() . "\n";
    $allGood = false;
    exit(1);
}

// 2. Check required tables
echo "\n2. Checking required tables...\n";
$requiredTables = [
    'students', 'companies', 'internships', 'applications', 
    'documents', 'terms', 'admin_users', 'evaluations'
];

$missingTables = [];
foreach ($requiredTables as $table) {
    try {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "   ✓ Table '$table' exists\n";
        } else {
            echo "   ❌ Table '$table' is missing\n";
            $missingTables[] = $table;
            $allGood = false;
        }
    } catch (PDOException $e) {
        echo "   ❌ Error checking table '$table': " . $e->getMessage() . "\n";
        $allGood = false;
    }
}

// 3. Check migrations (term_id, round_id columns)
echo "\n3. Checking migration status...\n";
try {
    $stmt = $db->query("SHOW COLUMNS FROM applications LIKE 'term_id'");
    if ($stmt->rowCount() > 0) {
        echo "   ✓ applications.term_id column exists\n";
    } else {
        echo "   ⚠ applications.term_id column missing - run migrations\n";
        $allGood = false;
    }
    
    $stmt = $db->query("SHOW COLUMNS FROM applications LIKE 'round_id'");
    if ($stmt->rowCount() > 0) {
        echo "   ✓ applications.round_id column exists\n";
    } else {
        echo "   ⚠ applications.round_id column missing - run migrations\n";
        $allGood = false;
    }
    
    $stmt = $db->query("SHOW COLUMNS FROM internships LIKE 'salary'");
    if ($stmt->rowCount() > 0) {
        echo "   ⚠ internships.salary column still exists - should be removed\n";
        $allGood = false;
    } else {
        echo "   ✓ internships.salary column removed\n";
    }
} catch (PDOException $e) {
    echo "   ❌ Error checking migrations: " . $e->getMessage() . "\n";
    $allGood = false;
}

// 4. Check application_rounds table
echo "\n4. Checking application rounds system...\n";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'application_rounds'");
    if ($stmt->rowCount() > 0) {
        echo "   ✓ application_rounds table exists\n";
        
        // Check for active round
        $stmt = $db->query("SELECT COUNT(*) FROM application_rounds WHERE is_active = TRUE AND CURDATE() BETWEEN start_date AND end_date");
        $activeRoundCount = $stmt->fetchColumn();
        if ($activeRoundCount > 0) {
            echo "   ✓ Active application round exists\n";
        } else {
            echo "   ⚠ No active application round found - run create_active_round.php\n";
        }
    } else {
        echo "   ⚠ application_rounds table missing - run migrations\n";
        $allGood = false;
    }
} catch (PDOException $e) {
    echo "   ⚠ Error checking application rounds: " . $e->getMessage() . "\n";
}

// 5. Check active term
echo "\n5. Checking terms...\n";
try {
    $stmt = $db->query("SELECT COUNT(*) FROM terms WHERE is_active = TRUE");
    $activeTermCount = $stmt->fetchColumn();
    if ($activeTermCount > 0) {
        echo "   ✓ Active term exists\n";
    } else {
        echo "   ⚠ No active term found - create one via admin interface or migration\n";
    }
} catch (PDOException $e) {
    echo "   ⚠ Error checking terms: " . $e->getMessage() . "\n";
}

// 6. Check security functions
echo "\n6. Checking security functions...\n";
if (function_exists('sanitize_cover_letter_html')) {
    echo "   ✓ sanitize_cover_letter_html() function available\n";
    
    // Test XSS protection
    $testHtml = '<script>alert("XSS")</script><p>Safe content</p>';
    $sanitized = sanitize_cover_letter_html($testHtml);
    if (strpos($sanitized, '<script>') === false && strpos($sanitized, '<p>Safe content</p>') !== false) {
        echo "   ✓ XSS protection working correctly\n";
    } else {
        echo "   ⚠ XSS protection may not be working correctly\n";
        $allGood = false;
    }
} else {
    echo "   ❌ sanitize_cover_letter_html() function missing\n";
    $allGood = false;
}

// 7. Check session security
echo "\n7. Checking session security...\n";
if (ini_get('session.cookie_httponly') == '1') {
    echo "   ✓ HTTP-only cookies enabled\n";
} else {
    echo "   ⚠ HTTP-only cookies not enabled\n";
}

if (ini_get('session.use_strict_mode') == '1') {
    echo "   ✓ Strict session mode enabled\n";
} else {
    echo "   ⚠ Strict session mode not enabled\n";
}

// 8. Check student_evaluations table
echo "\n8. Checking student evaluations...\n";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'student_evaluations'");
    if ($stmt->rowCount() > 0) {
        echo "   ✓ student_evaluations table exists\n";
    } else {
        echo "   ⚠ student_evaluations table missing - run migration\n";
    }
} catch (PDOException $e) {
    echo "   ⚠ Error checking student_evaluations: " . $e->getMessage() . "\n";
}

// Summary
echo "\n=== Verification Summary ===\n";
if ($allGood && empty($missingTables)) {
    echo "✓ All critical checks passed!\n";
    echo "\nNext steps:\n";
    echo "1. Ensure active term and round exist (run create_active_round.php if needed)\n";
    echo "2. Test application submission flow\n";
    echo "3. Test student evaluations\n";
    echo "4. Review security settings for production deployment\n";
} else {
    echo "⚠ Some issues found. Please review above and:\n";
    if (!empty($missingTables)) {
        echo "1. Run create_tables.php to create missing tables\n";
    }
    echo "2. Run run_migrations.php to apply pending migrations\n";
    echo "3. Run create_active_round.php to create active round\n";
}

echo "\n";

