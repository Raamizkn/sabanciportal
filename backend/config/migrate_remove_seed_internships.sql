-- Migration: Remove seed internships that were auto-created
-- These internships (Software Engineer, Data Analyst, Frontend Developer) 
-- were created for company_id 1, 2, 3 and shouldn't appear for other companies
-- 
-- This script removes internships that match the seed data pattern:
-- - company_id IN (1, 2, 3)
-- - Specific titles/positions from seed data
-- - No term_id assigned (or assigned to inactive terms)

-- Option 1: Delete seed internships completely
DELETE FROM internships 
WHERE company_id IN (1, 2, 3) 
AND (
    position LIKE '%Software Engineer Intern%' 
    OR position LIKE '%Data Analyst Intern%' 
    OR position LIKE '%Frontend Developer Intern%'
    OR title LIKE '%Software Engineer Intern%'
    OR title LIKE '%Data Analyst Intern%'
    OR title LIKE '%Frontend Developer Intern%'
);

-- Option 2: If you want to keep them but mark as deleted instead:
-- UPDATE internships 
-- SET status = 'Deleted'
-- WHERE company_id IN (1, 2, 3) 
-- AND (
--     position LIKE '%Software Engineer Intern%' 
--     OR position LIKE '%Data Analyst Intern%' 
--     OR position LIKE '%Frontend Developer Intern%'
--     OR title LIKE '%Software Engineer Intern%'
--     OR title LIKE '%Data Analyst Intern%'
--     OR title LIKE '%Frontend Developer Intern%'
-- );

-- Note: Companies should create their own internships through the portal UI
-- Seed data internships cause confusion when they appear for companies that didn't create them

