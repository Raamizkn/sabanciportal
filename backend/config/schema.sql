-- Sabanci Internship Portal Database Schema
-- Database: shadowing

-- Drop existing tables if they exist (for clean setup)
DROP TABLE IF EXISTS evaluations;
DROP TABLE IF EXISTS documents;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS internships;
DROP TABLE IF EXISTS companies;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS terms;
DROP TABLE IF EXISTS admin_users;

-- =============================================
-- TERMS TABLE
-- =============================================
CREATE TABLE terms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ADMIN USERS TABLE
-- =============================================
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- STUDENTS TABLE
-- =============================================
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255),
    major VARCHAR(100),
    gpa DECIMAL(3,2),
    phone VARCHAR(20),
    address TEXT,
    bio TEXT,
    profile_pic VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student_id (student_id),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- COMPANIES TABLE
-- =============================================
CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255),
    industry VARCHAR(100),
    website VARCHAR(255),
    phone VARCHAR(20),
    address TEXT,
    description TEXT,
    logo VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- INTERNSHIPS TABLE
-- =============================================
CREATE TABLE internships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    company_name VARCHAR(200) NOT NULL,
    title VARCHAR(200) NOT NULL,
    position VARCHAR(200),
    description TEXT NOT NULL,
    location VARCHAR(200),
    dates VARCHAR(100),
    requirements TEXT,
    salary VARCHAR(100),
    type VARCHAR(50) DEFAULT 'Full-time',
    status ENUM('Active', 'Inactive', 'Closed', 'Deleted') DEFAULT 'Active',
    application_deadline DATE,
    posted_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    INDEX idx_company_id (company_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- APPLICATIONS TABLE
-- =============================================
CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id VARCHAR(20) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    internship_id INT NOT NULL,
    status ENUM('Pending', 'Under Review', 'Shortlisted', 'Interview Scheduled', 
                'Offered', 'Rejected', 'Accepted', 'Declined', 'Withdrawn',
                'Approved_By_Company', 'Confirmed_By_Student', 'Rejected_By_Company') DEFAULT 'Pending',
    cover_letter TEXT,
    offer_details TEXT,
    applied_date DATE NOT NULL,
    status_updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (internship_id) REFERENCES internships(id) ON DELETE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_internship_id (internship_id),
    INDEX idx_status (status),
    UNIQUE KEY unique_student_internship (student_id, internship_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- DOCUMENTS TABLE
-- =============================================
CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id VARCHAR(20) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    application_id INT,
    document_type ENUM('CV', 'Transcript', 'Portfolio', 'Cover Letter', 'Certificate', 'Other') NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500),
    file_size INT,
    upload_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE SET NULL,
    INDEX idx_student_id (student_id),
    INDEX idx_application_id (application_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- EVALUATIONS TABLE
-- =============================================
CREATE TABLE evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evaluation_id VARCHAR(20) UNIQUE NOT NULL,
    application_id INT NOT NULL,
    company_id INT NOT NULL,
    student_id INT NOT NULL,
    rating DECIMAL(3,2),
    technical_skills INT DEFAULT 0,
    communication_skills INT DEFAULT 0,
    teamwork INT DEFAULT 0,
    problem_solving INT DEFAULT 0,
    overall_performance INT DEFAULT 0,
    comments TEXT,
    recommendation TEXT,
    evaluator_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_application_id (application_id),
    INDEX idx_company_id (company_id),
    INDEX idx_student_id (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- INSERT SAMPLE DATA
-- =============================================

-- Insert Terms
INSERT INTO terms (name, start_date, end_date, is_active) VALUES
('2023-2024 Fall', '2023-09-15', '2024-01-15', FALSE),
('2023-2024 Spring', '2024-02-01', '2024-06-15', FALSE),
('2024-2025 Fall', '2024-09-15', '2025-01-15', TRUE);

-- Insert Students
INSERT INTO students (student_id, name, email, major, gpa, phone, address, bio, profile_pic) VALUES
('20001', 'John Doe', 'student@example.com', 'Computer Science', 3.5, '123-456-7890', '123 University Ave, Istanbul', 'A passionate computer science student interested in web development and AI.', '/assets/images/demo/users/face1.jpg'),
('20002', 'Jane Smith', 'jane.smith@example.com', 'Electrical Engineering', 3.8, '234-567-8901', '456 Tech Street, Istanbul', 'Electrical engineering student specializing in embedded systems.', '/assets/images/demo/users/face2.jpg'),
('20003', 'Ahmet Yılmaz', 'ahmet.yilmaz@example.com', 'Industrial Engineering', 3.6, '345-678-9012', '789 Business Ave, Istanbul', 'Industrial engineering student with interest in operations research.', '/assets/images/demo/users/face3.jpg');

-- Insert Companies
INSERT INTO companies (name, email, industry, website, phone, address, description) VALUES
('ABC Technologies', 'company@example.com', 'Information Technology', 'http://abctech.example.com', '987-654-3210', '456 Tech Park, Istanbul', 'Leading technology solutions provider'),
('Global Innovations', 'innovate@example.com', 'Research & Development', 'http://globalinnovations.example.com', '876-543-2109', '789 Innovation Hub, Remote', 'Innovative R&D company focusing on cutting-edge technologies'),
('Tech Solutions Inc.', 'techsolutions@example.com', 'Software Development', 'http://techsolutions.example.com', '765-432-1098', '321 Silicon Valley, Istanbul', 'Software development company specializing in web applications');

-- Insert Internships
INSERT INTO internships (company_id, company_name, title, position, description, location, dates, requirements, salary, type, status, application_deadline, posted_date) VALUES
(1, 'ABC Technologies', 'Software Engineer Intern', 'Software Engineer Intern', 'Work on exciting projects in the web development team. Required skills: PHP, JavaScript, HTML, CSS.', 'Istanbul, Turkey', 'June 2024 - August 2024', 'PHP, JavaScript, HTML, CSS experience preferred', '3500 TL/month', 'Full-time', 'Active', '2024-12-31', '2024-05-01'),
(2, 'Global Innovations', 'Data Analyst Intern', 'Data Analyst Intern', 'Analyze large datasets and generate reports. Required skills: Python, SQL, Tableau.', 'Remote', 'July 2024 - September 2024', 'Python, SQL, Tableau knowledge required', '4000 TL/month', 'Full-time', 'Active', '2024-12-31', '2024-05-05'),
(3, 'Tech Solutions Inc.', 'Frontend Developer Intern', 'Frontend Developer Intern', 'Build responsive web interfaces using modern frameworks.', 'Istanbul, Turkey', 'August 2024 - October 2024', 'React, Vue.js, or Angular experience preferred', '3800 TL/month', 'Full-time', 'Active', '2024-12-31', '2024-05-10');

-- Insert Applications
INSERT INTO applications (application_id, student_id, internship_id, status, cover_letter, applied_date) VALUES
('APP001', 1, 1, 'Pending', 'I am very interested in this role. I have experience with PHP and JavaScript.', '2024-10-01'),
('APP002', 1, 2, 'Under Review', 'I am eager to analyze data and work with large datasets. I have Python and SQL experience.', '2024-10-05'),
('APP003', 2, 1, 'Shortlisted', 'I am passionate about software development and would love to contribute to your team.', '2024-10-08');

-- Insert Documents
INSERT INTO documents (document_id, student_id, application_id, document_type, file_name, upload_date) VALUES
('DOC001', 1, 1, 'CV', 'JohnDoe_CV.pdf', '2024-09-28'),
('DOC002', 1, 1, 'Transcript', 'Transcript_JohnDoe.pdf', '2024-09-28'),
('DOC003', 1, 2, 'CV', 'JohnDoe_CV.pdf', '2024-10-01'),
('DOC004', 2, 3, 'CV', 'JaneSmith_CV.pdf', '2024-10-05');

-- Insert Evaluations
INSERT INTO evaluations (evaluation_id, application_id, company_id, student_id, rating, technical_skills, communication_skills, teamwork, problem_solving, overall_performance, comments, recommendation, evaluator_name) VALUES
('EVAL001', 1, 1, 1, 4.5, 5, 4, 5, 4, 5, 'Excellent performance throughout the internship period. John showed great initiative and technical skills.', 'Highly recommend for future opportunities.', 'Manager ABC Technologies');

