-- Create an active round for the active term
-- Fixed version with proper MySQL syntax

INSERT INTO application_rounds 
(term_id, name, start_date, end_date, max_applications_per_student, default_company_quota, is_active)
SELECT 
    t.id as term_id,
    CONCAT('Application Round - ', DATE_FORMAT(CURDATE(), '%Y-%m-%d')) as name,
    CURDATE() as start_date,
    DATE_ADD(CURDATE(), INTERVAL 3 MONTH) as end_date,
    3 as max_applications_per_student,
    10 as default_company_quota,
    TRUE as is_active
FROM terms t
WHERE t.is_active = TRUE
ORDER BY t.start_date DESC
LIMIT 1;

-- Verify it was created:
SELECT r.*, t.name as term_name 
FROM application_rounds r 
JOIN terms t ON r.term_id = t.id 
WHERE r.is_active = TRUE 
ORDER BY r.created_at DESC 
LIMIT 1;

