-- Migration: Add Term-Based Application Rounds and Company Quotas
-- Database: shadowing
-- Purpose: Enable term-based application history, application rounds, and company quotas
-- Date: 2025-12-08

-- =============================================
-- STEP 1: Add term_id to applications table
-- =============================================
ALTER TABLE applications 
ADD COLUMN term_id INT NULL AFTER internship_id,
ADD INDEX idx_term_id (term_id),
ADD FOREIGN KEY (term_id) REFERENCES terms(id) ON DELETE SET NULL;

-- Populate term_id for existing applications based on applied_date
UPDATE applications a
JOIN terms t ON a.applied_date >= t.start_date AND a.applied_date <= t.end_date
SET a.term_id = t.id
WHERE a.term_id IS NULL;

-- =============================================
-- STEP 2: Create application_rounds table
-- =============================================
CREATE TABLE application_rounds (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- STEP 3: Add round_id to applications table
-- =============================================
ALTER TABLE applications 
ADD COLUMN round_id INT NULL AFTER term_id,
ADD INDEX idx_round_id (round_id),
ADD FOREIGN KEY (round_id) REFERENCES application_rounds(id) ON DELETE SET NULL;

-- =============================================
-- STEP 4: Create company_round_quotas table
-- =============================================
CREATE TABLE company_round_quotas (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- STEP 5: Add company_quota to companies table (for self-configuration)
-- =============================================
ALTER TABLE companies 
ADD COLUMN company_quota INT NULL COMMENT 'Default quota for company (can be overridden per round)' AFTER is_active;

-- =============================================
-- STEP 6: Verify changes
-- =============================================
-- Check applications table structure
-- DESCRIBE applications;

-- Check new tables exist
-- SHOW TABLES LIKE 'application_rounds';
-- SHOW TABLES LIKE 'company_round_quotas';

-- Check term_id population
-- SELECT COUNT(*) as total_apps, COUNT(term_id) as apps_with_term FROM applications;

