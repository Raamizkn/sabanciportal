-- =============================================
-- STUDENT EVALUATIONS TABLE
-- Students evaluate their internship experience at companies
-- Run this to add student evaluation functionality
-- =============================================

CREATE TABLE IF NOT EXISTS student_evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evaluation_id VARCHAR(20) UNIQUE NOT NULL,
    application_id INT NOT NULL,
    student_id INT NOT NULL,
    company_id INT NOT NULL,
    
    -- Program satisfaction ratings (1-5 scale)
    program_satisfaction INT DEFAULT 0,
    future_participation INT DEFAULT 0,
    consultant_care VARCHAR(10),  -- 'yes' or 'no'
    consultant_satisfaction INT DEFAULT 0,
    institution_selection INT DEFAULT 0,
    institution_recommendation INT DEFAULT 0,
    
    -- Benefits (checkboxes)
    benefits TEXT,  -- Comma-separated list of selected benefits
    
    -- Open-ended responses
    department TEXT,
    problems_encountered TEXT,
    additional_feedback TEXT,
    
    -- Overall rating (calculated from individual ratings)
    overall_rating DECIMAL(3,2),
    
    -- Timestamps
    submitted_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    INDEX idx_application_id (application_id),
    INDEX idx_student_id (student_id),
    INDEX idx_company_id (company_id),
    UNIQUE KEY unique_student_application (student_id, application_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

