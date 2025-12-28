-- Migration: Remove Application Rounds and Make Terms Supreme
-- Database: shadowing
-- Purpose: Remove application rounds, make terms the supreme filtering layer
-- Date: 2025-12-20

-- =============================================
-- STEP 1: Remove round_id from applications table
-- =============================================
-- Drop foreign key if it exists (check constraint name first)
SET @fk_name = (SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'applications' 
                AND COLUMN_NAME = 'round_id' 
                AND REFERENCED_TABLE_NAME = 'application_rounds' 
                LIMIT 1);
SET @sql = IF(@fk_name IS NOT NULL, CONCAT('ALTER TABLE applications DROP FOREIGN KEY ', @fk_name), 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Drop index if exists
SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = 'applications' 
                     AND INDEX_NAME = 'idx_round_id');
SET @sql = IF(@index_exists > 0, 'ALTER TABLE applications DROP INDEX idx_round_id', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Drop column if exists
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'applications' 
                   AND COLUMN_NAME = 'round_id');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE applications DROP COLUMN round_id', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =============================================
-- STEP 2: Ensure term_id exists and is populated
-- =============================================
-- term_id should already exist, but ensure it's populated for all applications
UPDATE applications a
LEFT JOIN terms t ON a.applied_date >= t.start_date AND a.applied_date <= t.end_date
SET a.term_id = t.id
WHERE a.term_id IS NULL;

-- Assign all applications without a term to Fall 2025 (or create it if needed)
-- First, ensure Fall 2025 term exists
INSERT INTO terms (name, start_date, end_date, is_active)
SELECT 'Fall 2025', '2025-09-15', '2026-01-15', TRUE
WHERE NOT EXISTS (SELECT 1 FROM terms WHERE name = 'Fall 2025');

-- Assign all orphaned applications to Fall 2025
UPDATE applications a
SET a.term_id = (SELECT id FROM terms WHERE name = 'Fall 2025' LIMIT 1)
WHERE a.term_id IS NULL;

-- =============================================
-- STEP 3: Drop application_rounds table
-- =============================================
DROP TABLE IF EXISTS company_round_quotas;
DROP TABLE IF EXISTS application_rounds;

-- =============================================
-- STEP 4: Update terms table to ensure is_active exists
-- =============================================
-- is_active should already exist, but ensure it's there
ALTER TABLE terms MODIFY COLUMN is_active BOOLEAN DEFAULT TRUE;

-- =============================================
-- STEP 5: Clean terms to only academic semesters
-- =============================================
-- Delete non-semester terms (keep only Fall, Spring, Summer patterns)
-- This will keep terms like "Fall 2025", "Spring 2026", "Summer 2025", etc.
-- Delete terms that don't match academic semester patterns
DELETE FROM terms 
WHERE name NOT REGEXP '^(Fall|Spring|Summer) [0-9]{4}$';

-- =============================================
-- STEP 6: Assign all internships to Fall 2025 term
-- =============================================
-- Add term_id to internships if it doesn't exist
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'internships' 
                   AND COLUMN_NAME = 'term_id');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE internships ADD COLUMN term_id INT NULL AFTER company_id',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index if it doesn't exist
SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = 'internships' 
                     AND INDEX_NAME = 'idx_term_id');
SET @sql = IF(@index_exists = 0, 
    'ALTER TABLE internships ADD INDEX idx_term_id (term_id)',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key if it doesn't exist
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'internships' 
                   AND COLUMN_NAME = 'term_id' 
                   AND REFERENCED_TABLE_NAME = 'terms');
SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE internships ADD FOREIGN KEY (term_id) REFERENCES terms(id) ON DELETE SET NULL',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Assign all internships to Fall 2025
UPDATE internships i
SET i.term_id = (SELECT id FROM terms WHERE name = 'Fall 2025' LIMIT 1)
WHERE i.term_id IS NULL;

-- =============================================
-- STEP 7: Update application status - remove Finalized, make Confirmed final
-- =============================================
-- Change all Finalized/Approved_By_Company to Confirmed
UPDATE applications 
SET status = 'Confirmed_By_Student'
WHERE status IN ('Finalized', 'Approved_By_Company');

-- =============================================
-- STEP 8: Add max_applications_per_student to terms table
-- =============================================
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'terms' 
                   AND COLUMN_NAME = 'max_applications_per_student');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE terms ADD COLUMN max_applications_per_student INT DEFAULT 3',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =============================================
-- STEP 9: Add company_quota to companies table (per term, not per round)
-- =============================================
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'companies' 
                   AND COLUMN_NAME = 'company_quota');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE companies ADD COLUMN company_quota INT DEFAULT 10',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

