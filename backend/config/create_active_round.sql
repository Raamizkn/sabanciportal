-- Quick Script: Create an Active Application Round
-- Run this in phpMyAdmin or via MySQL command line to create an active round immediately

-- First, check what terms exist
-- SELECT id, name, start_date, end_date, is_active FROM terms ORDER BY start_date DESC;

-- Create an active round for the most recent active term (or adjust term_id as needed)
-- Replace the term_id with the actual ID of the term you want to use

INSERT INTO application_rounds 
(term_id, name, start_date, end_date, max_applications_per_student, default_company_quota, is_active)
SELECT 
    id as term_id,
    'Fall 2025 Application Round' as name,
    CURDATE() as start_date,
    DATE_ADD(CURDATE(), INTERVAL 3 MONTH) as end_date,
    3 as max_applications_per_student,
    10 as default_company_quota,
    TRUE as is_active
FROM terms
WHERE is_active = TRUE
ORDER BY start_date DESC
LIMIT 1;

-- If no active term exists, create a round for the most recent term:
-- INSERT INTO application_rounds 
-- (term_id, name, start_date, end_date, max_applications_per_student, default_company_quota, is_active)
-- SELECT 
--     id as term_id,
--     'Fall 2025 Application Round' as name,
--     CURDATE() as start_date,
--     DATE_ADD(CURDATE(), INTERVAL 3 MONTH) as end_date,
--     3 as max_applications_per_student,
--     10 as default_company_quota,
--     TRUE as is_active
-- FROM terms
-- ORDER BY start_date DESC
-- LIMIT 1;

-- Verify the round was created:
-- SELECT r.*, t.name as term_name FROM application_rounds r JOIN terms t ON r.term_id = t.id ORDER BY r.created_at DESC LIMIT 1;

