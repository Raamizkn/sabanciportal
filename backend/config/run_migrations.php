<?php
/**
 * Migration Runner and Database Verification Script
 * Checks and applies all pending migrations
 * Usage: php backend/config/run_migrations.php
 */

require_once __DIR__ . '/database.php';

function runMigration($db, $sql, $description) {
    try {
        echo "Running: $description...\n";
        $db->exec($sql);
        echo "✓ Success: $description\n";
        return true;
    } catch (PDOException $e) {
        // Check if error is because column/table already exists
        if (strpos($e->getMessage(), 'Duplicate column') !== false || 
            strpos($e->getMessage(), 'already exists') !== false) {
            echo "⚠ Skipped: $description (already exists)\n";
            return true;
        }
        echo "❌ Error: $description - " . $e->getMessage() . "\n";
        return false;
    }
}

function checkColumnExists($db, $table, $column) {
    try {
        $stmt = $db->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

function checkTableExists($db, $table) {
    try {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

try {
    $db = getDB();
    echo "=== Database Migration and Verification ===\n\n";
    
    $migrationsRun = 0;
    $migrationsSkipped = 0;
    $errors = 0;
    
    // 1. Check and remove salary column from internships table
    echo "1. Checking internships table...\n";
    if (checkColumnExists($db, 'internships', 'salary')) {
        echo "   Removing salary column...\n";
        if (runMigration($db, "ALTER TABLE internships DROP COLUMN salary", "Remove salary column")) {
            $migrationsRun++;
        } else {
            $errors++;
        }
    } else {
        echo "   ✓ Salary column already removed\n";
        $migrationsSkipped++;
    }
    
    // 2. Check and add term_id to applications table
    echo "\n2. Checking applications table for term_id...\n";
    if (!checkColumnExists($db, 'applications', 'term_id')) {
        echo "   Adding term_id column...\n";
        $sql = "ALTER TABLE applications 
                ADD COLUMN term_id INT NULL AFTER internship_id,
                ADD INDEX idx_term_id (term_id)";
        
        // Check if terms table exists before adding foreign key
        if (checkTableExists($db, 'terms')) {
            $sql .= ",
                ADD FOREIGN KEY (term_id) REFERENCES terms(id) ON DELETE SET NULL";
        }
        
        if (runMigration($db, $sql, "Add term_id to applications")) {
            $migrationsRun++;
            
            // Populate term_id for existing applications
            if (checkTableExists($db, 'terms')) {
                echo "   Populating term_id for existing applications...\n";
                $populateSql = "UPDATE applications a
                                JOIN terms t ON a.applied_date >= t.start_date AND a.applied_date <= t.end_date
                                SET a.term_id = t.id
                                WHERE a.term_id IS NULL";
                runMigration($db, $populateSql, "Populate term_id");
            }
        } else {
            $errors++;
        }
    } else {
        echo "   ✓ term_id column already exists\n";
        $migrationsSkipped++;
    }
    
    // 3. Check and create application_rounds table
    echo "\n3. Checking application_rounds table...\n";
    if (!checkTableExists($db, 'application_rounds')) {
        echo "   Creating application_rounds table...\n";
        $sql = "CREATE TABLE application_rounds (
            id INT AUTO_INCREMENT PRIMARY KEY,
            term_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            max_applications_per_student INT DEFAULT 3,
            default_company_quota INT DEFAULT 10,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        
        if (checkTableExists($db, 'terms')) {
            $sql .= ",
            FOREIGN KEY (term_id) REFERENCES terms(id) ON DELETE CASCADE";
        }
        
        $sql .= ",
            INDEX idx_term_id (term_id),
            INDEX idx_dates (start_date, end_date),
            INDEX idx_is_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if (runMigration($db, $sql, "Create application_rounds table")) {
            $migrationsRun++;
        } else {
            $errors++;
        }
    } else {
        echo "   ✓ application_rounds table already exists\n";
        $migrationsSkipped++;
    }
    
    // 4. Check and add round_id to applications table
    echo "\n4. Checking applications table for round_id...\n";
    if (!checkColumnExists($db, 'applications', 'round_id')) {
        echo "   Adding round_id column...\n";
        $sql = "ALTER TABLE applications 
                ADD COLUMN round_id INT NULL AFTER term_id,
                ADD INDEX idx_round_id (round_id)";
        
        if (checkTableExists($db, 'application_rounds')) {
            $sql .= ",
                ADD FOREIGN KEY (round_id) REFERENCES application_rounds(id) ON DELETE SET NULL";
        }
        
        if (runMigration($db, $sql, "Add round_id to applications")) {
            $migrationsRun++;
        } else {
            $errors++;
        }
    } else {
        echo "   ✓ round_id column already exists\n";
        $migrationsSkipped++;
    }
    
    // 5. Check and create company_round_quotas table
    echo "\n5. Checking company_round_quotas table...\n";
    if (!checkTableExists($db, 'company_round_quotas')) {
        echo "   Creating company_round_quotas table...\n";
        $sql = "CREATE TABLE company_round_quotas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            round_id INT NOT NULL,
            quota_override INT NULL COMMENT 'If NULL, use default_company_quota from application_rounds',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        
        if (checkTableExists($db, 'companies')) {
            $sql .= ",
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE";
        }
        
        if (checkTableExists($db, 'application_rounds')) {
            $sql .= ",
            FOREIGN KEY (round_id) REFERENCES application_rounds(id) ON DELETE CASCADE";
        }
        
        $sql .= ",
            UNIQUE KEY unique_company_round (company_id, round_id),
            INDEX idx_company_id (company_id),
            INDEX idx_round_id (round_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if (runMigration($db, $sql, "Create company_round_quotas table")) {
            $migrationsRun++;
        } else {
            $errors++;
        }
    } else {
        echo "   ✓ company_round_quotas table already exists\n";
        $migrationsSkipped++;
    }
    
    // 6. Check and add company_quota to companies table
    echo "\n6. Checking companies table for company_quota column...\n";
    if (!checkColumnExists($db, 'companies', 'company_quota')) {
        echo "   Adding company_quota column...\n";
        if (runMigration($db, "ALTER TABLE companies ADD COLUMN company_quota INT NULL COMMENT 'Default quota for company (can be overridden per round)' AFTER is_active", "Add company_quota to companies")) {
            $migrationsRun++;
        } else {
            $errors++;
        }
    } else {
        echo "   ✓ company_quota column already exists\n";
        $migrationsSkipped++;
    }
    
    // 7. Verify active term exists
    echo "\n7. Verifying active term...\n";
    if (checkTableExists($db, 'terms')) {
        $stmt = $db->query("SELECT COUNT(*) FROM terms WHERE is_active = TRUE");
        $activeTermCount = $stmt->fetchColumn();
        
        if ($activeTermCount == 0) {
            echo "   ⚠ No active term found. Creating one...\n";
            $currentYear = date('Y');
            $nextYear = $currentYear + 1;
            $termName = "{$currentYear}-{$nextYear} Fall";
            $startDate = date('Y-m-d', strtotime('first day of September'));
            $endDate = date('Y-m-d', strtotime('last day of January next year'));
            
            if (date('m') > 9) {
                $startDate = date('Y-09-01');
                $endDate = date('Y-m-d', strtotime('+5 months', strtotime($startDate)));
            }
            
            $stmt = $db->prepare("INSERT INTO terms (name, start_date, end_date, is_active) VALUES (?, ?, ?, TRUE)");
            $stmt->execute([$termName, $startDate, $endDate]);
            echo "   ✓ Created active term: $termName\n";
            $migrationsRun++;
        } else {
            echo "   ✓ Active term exists\n";
        }
    } else {
        echo "   ⚠ Terms table does not exist. Run create_tables.php first.\n";
    }
    
    // 8. Verify active round exists
    echo "\n8. Verifying active application round...\n";
    if (checkTableExists($db, 'application_rounds')) {
        $stmt = $db->query("SELECT COUNT(*) FROM application_rounds WHERE is_active = TRUE AND CURDATE() BETWEEN start_date AND end_date");
        $activeRoundCount = $stmt->fetchColumn();
        
        if ($activeRoundCount == 0) {
            echo "   ⚠ No active round found. Run create_active_round.php to create one.\n";
            echo "   Or use: php backend/config/create_active_round.php\n";
        } else {
            echo "   ✓ Active round exists\n";
        }
    } else {
        echo "   ⚠ application_rounds table does not exist yet.\n";
    }
    
    // Summary
    echo "\n=== Migration Summary ===\n";
    echo "Migrations run: $migrationsRun\n";
    echo "Migrations skipped (already applied): $migrationsSkipped\n";
    if ($errors > 0) {
        echo "Errors: $errors\n";
    }
    echo "\n✓ Database verification complete!\n";
    
} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    exit(1);
}

