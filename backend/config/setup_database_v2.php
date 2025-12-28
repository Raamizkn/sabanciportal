<?php

/**
 * Database Setup Script V2
 * Properly executes the schema.sql file
 */

require_once __DIR__ . '/database.php';

try {
    // Connect to MySQL server
    $pdo = new PDO(
        "mysql:host=pro2-dev.sabanciuniv.edu;charset=utf8mb4",
        'shadowing',
        'QT8rvzZF'
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS shadowing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database 'shadowing' ready\n";
    
    // Now connect to the shadowing database
    $pdo = new PDO(
        "mysql:host=pro2-dev.sabanciuniv.edu;dbname=shadowing;charset=utf8mb4",
        'shadowing',
        'QT8rvzZF'
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // First, drop all existing tables to clean the database
    echo "Cleaning existing tables...\n";
    $existingTables = ['STUDENTS', 'activity_logs', 'messages', 'shadowing_opportunities', 'users', 'test_table', 'evaluations', 'documents', 'applications', 'internships', 'companies', 'students', 'terms', 'admin_users'];
    foreach ($existingTables as $table) {
        try {
            $pdo->exec("DROP TABLE IF EXISTS `$table`");
            echo "  Dropped table: $table\n";
        } catch(PDOException $e) {
            echo "  Warning: Could not drop $table: " . $e->getMessage() . "\n";
        }
    }
    
    // Read and execute schema file - execute it as a whole block
    echo "Creating schema...\n";
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    
    // Remove the INSERT statements at the end for now
    $schema = preg_replace('/-- =============================================.*/s', '', $schema);
    
    // Execute the schema
    $pdo->exec($schema);
    
    echo "✓ Database schema created successfully\n";
    
    // Now insert sample data
    echo "Inserting sample data...\n";
    
    // Insert Terms
    $pdo->exec("INSERT INTO terms (name, start_date, end_date, is_active) VALUES
    ('2023-2024 Fall', '2023-09-15', '2024-01-15', FALSE),
    ('2023-2024 Spring', '2024-02-01', '2024-06-15', FALSE),
    ('2024-2025 Fall', '2024-09-15', '2025-01-15', TRUE)");
    
    // Insert Students
    $pdo->exec("INSERT INTO students (student_id, name, email, major, gpa, phone, address, bio, profile_pic) VALUES
    ('20001', 'John Doe', 'student@example.com', 'Computer Science', 3.5, '123-456-7890', '123 University Ave, Istanbul', 'A passionate computer science student interested in web development and AI.', '/assets/images/demo/users/face1.jpg'),
    ('20002', 'Jane Smith', 'jane.smith@example.com', 'Electrical Engineering', 3.8, '234-567-8901', '456 Tech Street, Istanbul', 'Electrical engineering student specializing in embedded systems.', '/assets/images/demo/users/face2.jpg'),
    ('20003', 'Ahmet Yılmaz', 'ahmet.yilmaz@example.com', 'Industrial Engineering', 3.6, '345-678-9012', '789 Business Ave, Istanbul', 'Industrial engineering student with interest in operations research.', '/assets/images/demo/users/face3.jpg')");
    
    // Insert Companies
    $pdo->exec("INSERT INTO companies (name, email, industry, website, phone, address, description) VALUES
    ('ABC Technologies', 'company@example.com', 'Information Technology', 'http://abctech.example.com', '987-654-3210', '456 Tech Park, Istanbul', 'Leading technology solutions provider'),
    ('Global Innovations', 'innovate@example.com', 'Research & Development', 'http://globalinnovations.example.com', '876-543-2109', '789 Innovation Hub, Remote', 'Innovative R&D company focusing on cutting-edge technologies'),
    ('Tech Solutions Inc.', 'techsolutions@example.com', 'Software Development', 'http://techsolutions.example.com', '765-432-1098', '321 Silicon Valley, Istanbul', 'Software development company specializing in web applications')");
    
    // Insert Internships - REMOVED seed internships to prevent them appearing for companies that didn't create them
    // Companies should create their own internships through the portal
    // If you need test data, create internships manually through the UI for specific test companies
    
    // Insert Applications
    $pdo->exec("INSERT INTO applications (application_id, student_id, internship_id, status, cover_letter, applied_date) VALUES
    ('APP001', 1, 1, 'Pending', 'I am very interested in this role. I have experience with PHP and JavaScript.', '2024-10-01'),
    ('APP002', 1, 2, 'Under Review', 'I am eager to analyze data and work with large datasets. I have Python and SQL experience.', '2024-10-05'),
    ('APP003', 2, 1, 'Shortlisted', 'I am passionate about software development and would love to contribute to your team.', '2024-10-08')");
    
    // Insert Documents
    $pdo->exec("INSERT INTO documents (document_id, student_id, application_id, document_type, file_name, upload_date) VALUES
    ('DOC001', 1, 1, 'CV', 'JohnDoe_CV.pdf', '2024-09-28'),
    ('DOC002', 1, 1, 'Transcript', 'Transcript_JohnDoe.pdf', '2024-09-28'),
    ('DOC003', 1, 2, 'CV', 'JohnDoe_CV.pdf', '2024-10-01'),
    ('DOC004', 2, 3, 'CV', 'JaneSmith_CV.pdf', '2024-10-05')");
    
    // Insert Evaluations
    $pdo->exec("INSERT INTO evaluations (evaluation_id, application_id, company_id, student_id, rating, technical_skills, communication_skills, teamwork, problem_solving, overall_performance, comments, recommendation, evaluator_name) VALUES
    ('EVAL001', 1, 1, 1, 4.5, 5, 4, 5, 4, 5, 'Excellent performance throughout the internship period. John showed great initiative and technical skills.', 'Highly recommend for future opportunities.', 'Manager ABC Technologies')");
    
    echo "✓ Sample data inserted\n";
    echo "\n🎉 Database setup complete!\n";
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nPlease check:\n";
    echo "1. Database credentials in config/database.php\n";
    echo "2. MySQL server is running\n";
    echo "3. Network connectivity to pro2-dev.sabanciuniv.edu\n";
}

?>

