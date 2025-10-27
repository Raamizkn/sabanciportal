# Sabanci Internship Portal - Database Documentation

## Overview

The Sabanci Internship Portal uses a MySQL database hosted on the Sabanci University development server. This document provides complete information about the database setup, connection, schema, and management.

## Database Connection Details

### Host Information
- **Host**: `pro2-dev.sabanciuniv.edu`
- **Database Name**: `shadowing`
- **Port**: Default MySQL port (3306)
- **Charset**: `utf8mb4`
- **Collation**: `utf8mb4_unicode_ci`

### Credentials
- **Username**: `shadowing`
- **Password**: `QT8rvzZF`

### Configuration File
The database configuration is stored in `backend/config/database.php`:

```php
class Database {
    private $host = 'pro2-dev.sabanciuniv.edu';
    private $dbname = 'shadowing';
    private $username = 'shadowing';
    private $password = 'QT8rvzZF';
    private $charset = 'utf8mb4';
}
```

## Connection Methods

### 1. SSH Access
You can connect to the server using your SU-net account via SSH:
```bash
ssh your-su-net-username@pro2-dev.sabanciuniv.edu
```

Source files should be placed in: `/var/www/html/shadowing`

### 2. phpMyAdmin Access
Access the database via web interface:
```
http://pro2-dev.sabanciuniv.edu/seminar/phpmyadmin/index.php?route=/database/structure&db=shadowing
```

### 3. From PHP Code
```php
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();
```

### 4. Direct MySQL Connection
```bash
mysql -h pro2-dev.sabanciuniv.edu -u shadowing -pQT8rvzZF shadowing
```

## Database Schema

### Tables Overview

The database consists of 8 main tables:

1. **admin_users** - Administrator accounts
2. **terms** - Academic terms/periods
3. **students** - Student profiles and information
4. **companies** - Company profiles and information
5. **internships** - Internship postings
6. **applications** - Student applications to internships
7. **documents** - Student documents (CVs, transcripts, etc.)
8. **evaluations** - Internship evaluations and feedback

### Detailed Schema

#### 1. admin_users
```sql
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 2. terms
```sql
CREATE TABLE terms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 3. students
```sql
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
```

#### 4. companies
```sql
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
```

#### 5. internships
```sql
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
```

#### 6. applications
```sql
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
```

#### 7. documents
```sql
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
```

#### 8. evaluations
```sql
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
```

## Setting Up the Database

### Initial Setup Script

The easiest way to set up the database is using the provided script:

```bash
cd backend
php config/create_tables.php
```

This script will:
1. Clean existing tables (if any)
2. Create all tables with proper schema
3. Insert sample data for testing

### Manual Setup

If you need to set up manually:

1. **Connect to the database**:
```bash
mysql -h pro2-dev.sabanciuniv.edu -u shadowing -pQT8rvzZF shadowing
```

2. **Execute the schema file**:
```bash
mysql -h pro2-dev.sabanciuniv.edu -u shadowing -pQT8rvzZF shadowing < backend/config/schema.sql
```

3. **Insert sample data** (optional):
```sql
-- Insert sample terms
INSERT INTO terms (name, start_date, end_date, is_active) VALUES
('2023-2024 Fall', '2023-09-15', '2024-01-15', FALSE),
('2023-2024 Spring', '2024-02-01', '2024-06-15', FALSE),
('2024-2025 Fall', '2024-09-15', '2025-01-15', TRUE);

-- Insert sample students
INSERT INTO students (student_id, name, email, major, gpa, phone, address, bio, profile_pic) VALUES
('20001', 'John Doe', 'student@example.com', 'Computer Science', 3.5, '123-456-7890', '123 University Ave, Istanbul', 'A passionate computer science student interested in web development and AI.', '/assets/images/demo/users/face1.jpg'),
('20002', 'Jane Smith', 'jane.smith@example.com', 'Electrical Engineering', 3.8, '234-567-8901', '456 Tech Street, Istanbul', 'Electrical engineering student specializing in embedded systems.', '/assets/images/demo/users/face2.jpg'),
('20003', 'Ahmet Yılmaz', 'ahmet.yilmaz@example.com', 'Industrial Engineering', 3.6, '345-678-9012', '789 Business Ave, Istanbul', 'Industrial engineering student with interest in operations research.', '/assets/images/demo/users/face3.jpg');

-- Insert sample companies
INSERT INTO companies (name, email, industry, website, phone, address, description) VALUES
('ABC Technologies', 'company@example.com', 'Information Technology', 'http://abctech.example.com', '987-654-3210', '456 Tech Park, Istanbul', 'Leading technology solutions provider'),
('Global Innovations', 'innovate@example.com', 'Research & Development', 'http://globalinnovations.example.com', '876-543-2109', '789 Innovation Hub, Remote', 'Innovative R&D company focusing on cutting-edge technologies'),
('Tech Solutions Inc.', 'techsolutions@example.com', 'Software Development', 'http://techsolutions.example.com', '765-432-1098', '321 Silicon Valley, Istanbul', 'Software development company specializing in web applications');

-- Insert sample internships
INSERT INTO internships (company_id, company_name, title, position, description, location, dates, requirements, salary, type, status, application_deadline, posted_date) VALUES
(1, 'ABC Technologies', 'Software Engineer Intern', 'Software Engineer Intern', 'Work on exciting projects in the web development team. Required skills: PHP, JavaScript, HTML, CSS.', 'Istanbul, Turkey', 'June 2024 - August 2024', 'PHP, JavaScript, HTML, CSS experience preferred', '3500 TL/month', 'Full-time', 'Active', '2024-12-31', '2024-05-01'),
(2, 'Global Innovations', 'Data Analyst Intern', 'Data Analyst Intern', 'Analyze large datasets and generate reports. Required skills: Python, SQL, Tableau.', 'Remote', 'July 2024 - September 2024', 'Python, SQL, Tableau knowledge required', '4000 TL/month', 'Full-time', 'Active', '2024-12-31', '2024-05-05'),
(3, 'Tech Solutions Inc.', 'Frontend Developer Intern', 'Frontend Developer Intern', 'Build responsive web interfaces using modern frameworks.', 'Istanbul, Turkey', 'August 2024 - October 2024', 'React, Vue.js, or Angular experience preferred', '3800 TL/month', 'Full-time', 'Active', '2024-12-31', '2024-05-10');

-- Insert sample applications
INSERT INTO applications (application_id, student_id, internship_id, status, cover_letter, applied_date) VALUES
('APP001', 1, 1, 'Pending', 'I am very interested in this role. I have experience with PHP and JavaScript.', '2024-10-01'),
('APP002', 1, 2, 'Under Review', 'I am eager to analyze data and work with large datasets. I have Python and SQL experience.', '2024-10-05'),
('APP003', 2, 1, 'Shortlisted', 'I am passionate about software development and would love to contribute to your team.', '2024-10-08');

-- Insert sample documents
INSERT INTO documents (document_id, student_id, application_id, document_type, file_name, upload_date) VALUES
('DOC001', 1, 1, 'CV', 'JohnDoe_CV.pdf', '2024-09-28'),
('DOC002', 1, 1, 'Transcript', 'Transcript_JohnDoe.pdf', '2024-09-28'),
('DOC003', 1, 2, 'CV', 'JohnDoe_CV.pdf', '2024-10-01'),
('DOC004', 2, 3, 'CV', 'JaneSmith_CV.pdf', '2024-10-05');

-- Insert sample evaluations
INSERT INTO evaluations (evaluation_id, application_id, company_id, student_id, rating, technical_skills, communication_skills, teamwork, problem_solving, overall_performance, comments, recommendation, evaluator_name) VALUES
('EVAL001', 1, 1, 1, 4.5, 5, 4, 5, 4, 5, 'Excellent performance throughout the internship period. John showed great initiative and technical skills.', 'Highly recommend for future opportunities.', 'Manager ABC Technologies');
```

## Data Access Layer

### Current Implementation

The application uses a centralized data loading system in `backend/data/data.php`:

```php
<?php
require_once __DIR__ . '/../config/database.php';

$students = [];
$internships = [];
$applications = [];
$documents = [];
$terms = [];
$companies = [];

// Try to load from database
try {
    $db = getDB();
    
    // Load Students
    $stmt = $db->query("SELECT * FROM students WHERE is_active = 1");
    $studentsData = $stmt->fetchAll();
    $students = [];
    foreach ($studentsData as $row) {
        $students[$row['id']] = $row;
    }
    
    // Similar for other tables...
    
} catch(Exception $e) {
    // Fallback to mock data if database fails
    error_log("Database load failed: " . $e->getMessage());
    require_once __DIR__ . '/mock_data.php';
}
?>
```

### Key Features

1. **Graceful Fallback**: If database connection fails, the system falls back to mock data
2. **Indexed Arrays**: Data is indexed by primary keys for efficient access
3. **Separation of Concerns**: Data loading is separated from business logic

## Testing the Database

### Verify Connection

```bash
cd backend
php -r "require 'config/database.php'; \$db = new Database(); \$result = \$db->testConnection(); print_r(\$result);"
```

### Check Tables

```bash
php -r "require 'config/database.php'; \$db = new Database(); \$conn = \$db->getConnection(); \$stmt = \$conn->query('SHOW TABLES'); print_r(\$stmt->fetchAll(PDO::FETCH_COLUMN));"
```

### Count Records

```bash
php -r "require 'config/database.php'; \$db = new Database(); \$conn = \$db->getConnection(); \$stmt = \$conn->query('SELECT COUNT(*) as count FROM applications'); \$count = \$stmt->fetch()['count']; echo \"Total applications: \$count\n\";"
```

### Test API Endpoints

```bash
# Get all internships
curl 'http://localhost:8001/index.php?entity=internships'

# Get student applications
curl 'http://localhost:8001/index.php?entity=applications&student_id=1'

# Create new application
curl -X POST 'http://localhost:8001/index.php?entity=applications&action=apply' \
  -H 'Content-Type: application/json' \
  -d '{"student_id": 1, "internship_id": 1, "cover_letter": "I am interested"}'

# Get admin students
curl 'http://localhost:8001/index.php?entity=admin&resource=students'
```

## Troubleshooting

### Common Issues

#### 1. "Access denied" Error
```
SQLSTATE[HY000] [1045] Access denied for user 'shadowing'@'xxx' (using password: YES)
```

**Solution**: Verify credentials in `backend/config/database.php`

#### 2. "Table doesn't exist" Error
```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'shadowing.students' doesn't exist
```

**Solution**: Run the setup script:
```bash
cd backend
php config/create_tables.php
```

#### 3. Connection Timeout
```
SQLSTATE[HY000] [2002] Connection timed out
```

**Solution**: 
- Check network connectivity to `pro2-dev.sabanciuniv.edu`
- Verify firewall settings
- Check if you're on the university network or VPN

#### 4. Fallback to Mock Data
If you see old mock data (IDs like 101, 102 instead of 1, 2), the database connection is failing and falling back to mock data.

**Check logs**:
```bash
tail -f backend/error.log
```

**Solution**: Verify database connection and credentials

### Debug Mode

Enable detailed error reporting in PHP:

```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Database Backup

### Create Backup

```bash
mysqldump -h pro2-dev.sabanciuniv.edu -u shadowing -pQT8rvzZF shadowing > backup_$(date +%Y%m%d).sql
```

### Restore Backup

```bash
mysql -h pro2-dev.sabanciuniv.edu -u shadowing -pQT8rvzZF shadowing < backup_20241026.sql
```

## Security Considerations

1. **Credentials**: Never commit database passwords to version control
2. **SQL Injection**: All queries use prepared statements
3. **User Input**: Always validate and sanitize user input
4. **Error Messages**: Don't expose database structure in error messages
5. **Access Control**: Restrict database access to authorized users only

## File Locations

- **Database Config**: `backend/config/database.php`
- **Schema File**: `backend/config/schema.sql`
- **Setup Script**: `backend/config/create_tables.php`
- **Data Loader**: `backend/data/data.php`
- **Mock Data**: `backend/data/mock_data.php`

## Development Workflow

1. **Local Development**: Use mock data for fast iteration
2. **Testing**: Connect to remote database for integration testing
3. **Production**: Use remote database exclusively

### Switching Between Mock and Database

In `backend/config/database.php`:
```php
private $useMockData = false; // Set to true to use mock data
```

## API Integration Status

### ✅ Working with Database
- GET operations (READ)
- POST for creating applications
- Proper foreign key relationships

### ⚠️ Partial Implementation
- Some UPDATE operations still use in-memory arrays
- Some DELETE operations need database integration

### Future Enhancements
- Implement all CRUD operations in handlers
- Add database transactions for data integrity
- Implement connection pooling
- Add query optimization and caching

## Quick Reference

### Common Queries

```sql
-- List all active internships
SELECT * FROM internships WHERE status = 'Active';

-- Get applications for a student
SELECT * FROM applications WHERE student_id = 1;

-- Get applications for a company's internships
SELECT a.* FROM applications a
JOIN internships i ON a.internship_id = i.id
WHERE i.company_id = 1;

-- Count applications by status
SELECT status, COUNT(*) as count FROM applications GROUP BY status;
```

## Deployment to pro2-dev Server

### Prerequisites
- SSH access to pro2-dev.sabanciuniv.edu
- SU-net credentials (email: raamiz.niazi@sabanciuniv.edu)
- Source files ready locally

### Deployment Steps

1. **Connect to the server via SSH**:
```bash
ssh raamiz.niazi@sabanciuniv.edu@pro2-dev.sabanciuniv.edu
# Enter password when prompted: Fizmiz210104**
```

2. **Navigate to the web directory**:
```bash
cd /var/www/html/shadowing
```

3. **Check existing files**:
```bash
ls -la
# Look for existing internship-portal folder
```

4. **Backup existing files (if they exist)**:
```bash
# If internship-portal exists, create a backup
mv internship-portal internship-portal-backup-$(date +%Y%m%d-%H%M%S)
```

5. **Upload files from local machine** (run from your local terminal):
```bash
# From your local project directory
# Upload frontend
scp -r internship-portal raamiz.niazi@sabanciuniv.edu@pro2-dev.sabanciuniv.edu:/var/www/html/shadowing/

# Upload backend (IMPORTANT: backend must be deployed for API to work)
scp -r backend raamiz.niazi@sabanciuniv.edu@pro2-dev.sabanciuniv.edu:/var/www/html/shadowing/
```

6. **Verify upload**:
```bash
# On the server
cd /var/www/html/shadowing
ls -la internship-portal
```

7. **Set correct permissions**:
```bash
chmod -R 755 internship-portal
chown -R www-data:www-data internship-portal
chmod -R 755 backend
chown -R www-data:www-data backend
```

8. **Set up the database** (on the server):
```bash
cd /var/www/html/shadowing/backend
php config/create_tables.php
```

This will:
- Create all database tables
- Insert sample data
- Set all passwords to `password123`

9. **Test the deployment**:
Open in browser: `http://pro2-dev.sabanciuniv.edu/shadowing/internship-portal/`

Login credentials:
- **Admin:** `admin@example.com` / `password123`
- **Company:** `company@example.com` / `password123`
- **Student:** `student@example.com` / `password123`

### Alternative: Using rsync (Recommended)

If rsync is available, use it for faster, incremental sync:

```bash
# From your local machine
rsync -avz --delete internship-portal/ raamiz.niazi@sabanciuniv.edu@pro2-dev.sabanciuniv.edu:/var/www/html/shadowing/internship-portal/
```

### Note on Path Resolution

The internship-portal uses path-resolver.js which automatically detects the server environment and adjusts paths accordingly. Ensure:
- Path resolver is loaded first in all HTML files
- All paths use relative references (not absolute `/`)
- The resolver script is in: `internship-portal/js/path-resolver.js`

## Support

For issues or questions about the database:
1. Check this documentation
2. Review server logs
3. Contact the development team

---

**Last Updated**: October 26, 2025  
**Database Version**: 1.0  
**Schema Version**: 1.0

