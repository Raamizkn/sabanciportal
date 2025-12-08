<?php
/**
 * Migration: Add Term-Based Application Rounds and Company Quotas
 * This allows admin to control application windows and quotas per round
 */

require_once __DIR__ . '/database.php';

try {
    $db = getDB();
    $db->beginTransaction();
    
    // Step 1: Add term_id to applications
    echo "Step 1: Adding term_id to applications table...\n";
    try {
        $db->exec("ALTER TABLE applications 
            ADD COLUMN term_id INT NULL AFTER internship_id,
            ADD INDEX idx_term_id (term_id),
            ADD FOREIGN KEY (term_id) REFERENCES terms(id) ON DELETE SET NULL");
        echo "✓ Added term_id column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "⚠ term_id column already exists, skipping...\n";
        } else {
            throw $e;
        }
    }
    
    // Populate term_id for existing applications
    echo "Populating term_id for existing applications...\n";
    $db->exec("UPDATE applications a
        JOIN terms t ON a.applied_date >= t.start_date AND a.applied_date <= t.end_date
        SET a.term_id = t.id
        WHERE a.term_id IS NULL");
    echo "✓ Populated term_id for existing applications\n";
    
    // Step 2: Create application_rounds table
    echo "\nStep 2: Creating application_rounds table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS application_rounds (
        id INT AUTO_INCREMENT PRIMARY KEY,
        term_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        max_applications_per_student INT DEFAULT 3,
        default_company_quota INT DEFAULT 10,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (term_id) REFERENCES terms(id) ON DELETE CASCADE,
        INDEX idx_term_id (term_id),
        INDEX idx_dates (start_date, end_date),
        INDEX idx_is_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Created application_rounds table\n";
    
    // Step 3: Add round_id to applications
    echo "\nStep 3: Adding round_id to applications table...\n";
    try {
        $db->exec("ALTER TABLE applications 
            ADD COLUMN round_id INT NULL AFTER term_id,
            ADD INDEX idx_round_id (round_id),
            ADD FOREIGN KEY (round_id) REFERENCES application_rounds(id) ON DELETE SET NULL");
        echo "✓ Added round_id column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "⚠ round_id column already exists, skipping...\n";
        } else {
            throw $e;
        }
    }
    
    // Step 4: Create company_round_quotas table
    echo "\nStep 4: Creating company_round_quotas table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS company_round_quotas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        round_id INT NOT NULL,
        quota_override INT NULL COMMENT 'If NULL, use default_company_quota from application_rounds',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
        FOREIGN KEY (round_id) REFERENCES application_rounds(id) ON DELETE CASCADE,
        UNIQUE KEY unique_company_round (company_id, round_id),
        INDEX idx_company_id (company_id),
        INDEX idx_round_id (round_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Created company_round_quotas table\n";
    
    // Step 5: Add company_quota to companies
    echo "\nStep 5: Adding company_quota to companies table...\n";
    try {
        $db->exec("ALTER TABLE companies 
            ADD COLUMN company_quota INT NULL COMMENT 'Default quota for company (can be overridden per round)' AFTER is_active");
        echo "✓ Added company_quota column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "⚠ company_quota column already exists, skipping...\n";
        } else {
            throw $e;
        }
    }
    
    $db->commit();
    echo "\n✓ Migration completed successfully!\n";
    
    // Verification
    echo "\nVerification:\n";
    $stmt = $db->query("SELECT COUNT(*) as total, COUNT(term_id) as with_term FROM applications");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "  Applications: {$result['total']} total, {$result['with_term']} with term_id\n";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM application_rounds");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "  Application rounds: {$result['count']}\n";
    
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

