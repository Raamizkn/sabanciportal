<?php

require_once __DIR__ . '/database.php';

$db = new Database();
$conn = $db->getConnection();
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Clean existing tables
echo "Cleaning existing tables...\n";
$tables = ['STUDENTS', 'activity_logs', 'messages', 'shadowing_opportunities', 'users', 'test_table', 'evaluations', 'documents', 'applications', 'internships', 'companies', 'students', 'terms', 'admin_users'];
foreach ($tables as $table) {
    try {
        $conn->exec("DROP TABLE IF EXISTS `$table`");
    } catch(PDOException $e) {
        // Ignore
    }
}

echo "Creating tables...\n";

// Create admin_users table
$conn->exec("CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "✓ Created admin_users\n";

// Create terms table
$conn->exec("CREATE TABLE terms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "✓ Created terms\n";

// Create students table
$conn->exec("CREATE TABLE students (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "✓ Created students\n";

// Create companies table
$conn->exec("CREATE TABLE companies (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "✓ Created companies\n";

// Create internships table
$conn->exec("CREATE TABLE internships (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "✓ Created internships\n";

// Create applications table
$conn->exec("CREATE TABLE applications (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "✓ Created applications\n";

// Create documents table
$conn->exec("CREATE TABLE documents (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "✓ Created documents\n";

// Create evaluations table
$conn->exec("CREATE TABLE evaluations (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "✓ Created evaluations\n";

echo "\n✓ All tables created successfully!\n";

// Insert sample data
echo "\nInserting sample data...\n";

// Insert Terms
$conn->exec("INSERT INTO terms (name, start_date, end_date, is_active) VALUES
('2023-2024 Fall', '2023-09-15', '2024-01-15', FALSE),
('2023-2024 Spring', '2024-02-01', '2024-06-15', FALSE),
('2024-2025 Fall', '2024-09-15', '2025-01-15', TRUE)");
echo "✓ Inserted terms\n";

// Insert Students
$conn->exec("INSERT INTO students (student_id, name, email, major, gpa, phone, address, bio, profile_pic) VALUES
('20001', 'John Doe', 'student@example.com', 'Computer Science', 3.5, '123-456-7890', '123 University Ave, Istanbul', 'A passionate computer science student interested in web development and AI.', '/assets/images/demo/users/face1.jpg'),
('20002', 'Jane Smith', 'jane.smith@example.com', 'Electrical Engineering', 3.8, '234-567-8901', '456 Tech Street, Istanbul', 'Electrical engineering student specializing in embedded systems.', '/assets/images/demo/users/face2.jpg'),
('20003', 'Ahmet Yılmaz', 'ahmet.yilmaz@example.com', 'Industrial Engineering', 3.6, '345-678-9012', '789 Business Ave, Istanbul', 'Industrial engineering student with interest in operations research.', '/assets/images/demo/users/face3.jpg')");
echo "✓ Inserted students\n";

// Insert Companies
$conn->exec("INSERT INTO companies (name, email, industry, website, phone, address, description) VALUES
('ABC Technologies', 'company@example.com', 'Information Technology', 'http://abctech.example.com', '987-654-3210', '456 Tech Park, Istanbul', 'Leading technology solutions provider'),
('Global Innovations', 'innovate@example.com', 'Research & Development', 'http://globalinnovations.example.com', '876-543-2109', '789 Innovation Hub, Remote', 'Innovative R&D company focusing on cutting-edge technologies'),
('Tech Solutions Inc.', 'techsolutions@example.com', 'Software Development', 'http://techsolutions.example.com', '765-432-1098', '321 Silicon Valley, Istanbul', 'Software development company specializing in web applications')");
echo "✓ Inserted companies\n";

// Insert Internships - REMOVED seed internships to prevent them appearing for companies that didn't create them
// Companies should create their own internships through the portal
// If you need test data, create internships manually through the UI for specific test companies
echo "✓ Skipped seed internships (companies should create their own)\n";

// Insert Applications
$conn->exec("INSERT INTO applications (application_id, student_id, internship_id, status, cover_letter, applied_date) VALUES
('APP001', 1, 1, 'Pending', 'I am very interested in this role. I have experience with PHP and JavaScript.', '2024-10-01'),
('APP002', 1, 2, 'Under Review', 'I am eager to analyze data and work with large datasets. I have Python and SQL experience.', '2024-10-05'),
('APP003', 2, 1, 'Shortlisted', 'I am passionate about software development and would love to contribute to your team.', '2024-10-08')");
echo "✓ Inserted applications\n";

// Insert Documents
$conn->exec("INSERT INTO documents (document_id, student_id, application_id, document_type, file_name, upload_date) VALUES
('DOC001', 1, 1, 'CV', 'JohnDoe_CV.pdf', '2024-09-28'),
('DOC002', 1, 1, 'Transcript', 'Transcript_JohnDoe.pdf', '2024-09-28'),
('DOC003', 1, 2, 'CV', 'JohnDoe_CV.pdf', '2024-10-01'),
('DOC004', 2, 3, 'CV', 'JaneSmith_CV.pdf', '2024-10-05')");
echo "✓ Inserted documents\n";

// Insert Evaluations
$conn->exec("INSERT INTO evaluations (evaluation_id, application_id, company_id, student_id, rating, technical_skills, communication_skills, teamwork, problem_solving, overall_performance, comments, recommendation, evaluator_name) VALUES
('EVAL001', 1, 1, 1, 4.5, 5, 4, 5, 4, 5, 'Excellent performance throughout the internship period. John showed great initiative and technical skills.', 'Highly recommend for future opportunities.', 'Manager ABC Technologies')");
echo "✓ Inserted evaluations\n";

echo "\n🎉 Database setup complete!\n";

// Set common passwords for all users
echo "\nSetting up common passwords...\n";
$common_password = 'password123';
$password_hash = password_hash($common_password, PASSWORD_DEFAULT);

// Update student passwords
$stmt = $conn->prepare("UPDATE students SET password_hash = ? WHERE email IN (?, ?, ?)");
$stmt->execute([$password_hash, 'student@example.com', 'jane.smith@example.com', 'ahmet.yilmaz@example.com']);
echo "✓ Set passwords for students\n";

// Update company passwords
$stmt = $conn->prepare("UPDATE companies SET password_hash = ? WHERE email IN (?, ?, ?)");
$stmt->execute([$password_hash, 'company@example.com', 'innovate@example.com', 'techsolutions@example.com']);
echo "✓ Set passwords for companies\n";

// Insert admin user with password
$stmt = $conn->prepare("INSERT INTO admin_users (username, email, password_hash, full_name) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE password_hash = ?");
$stmt->execute(['admin', 'admin@example.com', $password_hash, 'System Administrator', $password_hash]);
echo "✓ Set password for admin\n";

echo "\n✅ All passwords set to: $common_password\n";
echo "\nTest Credentials:\n";
echo "Admin: admin@example.com / $common_password\n";
echo "Company: company@example.com / $common_password\n";
echo "Student: student@example.com / $common_password\n";

?>

