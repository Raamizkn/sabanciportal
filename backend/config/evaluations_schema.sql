-- Evaluations Tables Schema
-- Run this to create/update the evaluations tables

-- Student Evaluations (student → company)
-- Students evaluate their internship experience at companies
CREATE TABLE IF NOT EXISTS student_evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evaluation_id VARCHAR(50) UNIQUE NOT NULL,
    application_id INT NOT NULL,
    student_id INT NOT NULL,
    company_id INT NOT NULL,
    
    -- Rating fields (1-5 scale)
    program_satisfaction INT DEFAULT 0,
    future_participation INT DEFAULT 0,
    consultant_care ENUM('yes', 'no') DEFAULT 'no',
    consultant_satisfaction INT DEFAULT 0,
    institution_selection INT DEFAULT 0,
    institution_recommendation INT DEFAULT 0,
    
    -- Text fields
    benefits TEXT,
    department TEXT,
    problems_encountered TEXT,
    additional_feedback TEXT,
    
    -- Calculated overall rating
    overall_rating DECIMAL(3,2) DEFAULT 0,
    
    -- Timestamps
    submitted_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    
    -- Prevent duplicate evaluations
    UNIQUE KEY unique_student_app_eval (application_id, student_id)
);

-- Company Evaluations (company → student)
-- Companies evaluate students after internship completion
CREATE TABLE IF NOT EXISTS evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    student_id INT NOT NULL,
    company_id INT NOT NULL,
    
    -- Rating fields (1-5 scale)
    program_satisfaction INT DEFAULT 0,
    student_impact INT DEFAULT 0,
    motivation INT DEFAULT 0,
    communication INT DEFAULT 0,
    timeliness INT DEFAULT 0,
    positive_attitude INT DEFAULT 0,
    teamwork INT DEFAULT 0,
    adaptation INT DEFAULT 0,
    digital_tools INT DEFAULT 0,
    
    -- Yes/No questions
    participate_again ENUM('yes', 'no') DEFAULT 'no',
    recommend_program ENUM('yes', 'no') DEFAULT 'no',
    future_internship ENUM('yes', 'no') DEFAULT 'no',
    interview ENUM('yes', 'no') DEFAULT 'no',
    
    -- Text fields
    redesign_suggestions TEXT,
    program_type VARCHAR(50),
    
    -- Calculated overall rating
    overall_rating DECIMAL(3,2) DEFAULT 0,
    
    -- Timestamps
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    
    -- Prevent duplicate evaluations
    UNIQUE KEY unique_company_app_eval (application_id, company_id)
);

-- Indexes for faster queries
CREATE INDEX IF NOT EXISTS idx_student_eval_student ON student_evaluations(student_id);
CREATE INDEX IF NOT EXISTS idx_student_eval_company ON student_evaluations(company_id);
CREATE INDEX IF NOT EXISTS idx_company_eval_student ON evaluations(student_id);
CREATE INDEX IF NOT EXISTS idx_company_eval_company ON evaluations(company_id);

