# API Documentation & Postman Guide

This document provides a complete guide to testing the backend API endpoints using Postman. The backend server is running on `http://localhost:8001`.

## 🚀 Quick Start with Postman Collection

### Option 1: Import Complete Collection (Recommended)

**The easiest way to get started is by importing our complete Postman collection:**

1. Download the collection file: `Sabanci_Internship_Portal_API.postman_collection.json`
2. Open Postman and click **Import**
3. Select **Upload Files** and choose the collection file
4. The entire API collection will be imported with organized folders and examples
5. Set the `base_url` environment variable to `http://localhost:8001`

### Option 2: Import Individual cURL Commands

You can also import individual endpoints using cURL commands:

1. Copy the complete `curl` command provided for an endpoint
2. In Postman, click the **Import** button
3. Select the **Raw Text** tab
4. Paste the `curl` command and click **Continue**
5. Postman will automatically create the request for you

## 📋 API Overview

The Sabanci Internship Portal API provides three main user interfaces:

- **🎓 Student API** (`/backend/api/student.php`) - Profile management, internship browsing, applications
- **🏢 Company API** (`/backend/api/company.php`) - Internship posting, application review, student evaluation
- **👨‍💼 Admin API** (`/backend/api/admin.php`) - System administration, user management, oversight

All APIs return JSON responses and support CORS for web integration.

---

## 🎓 Student API Endpoints

**Base URL:** `http://localhost:8001/backend/api/student.php`

These endpoints are for student-related actions like viewing internships and managing applications.

### 1. Get Student Profile

- **Name**: `Get Student Profile`
- **Description**: Retrieves detailed profile information for a specific student.
- **Method**: `GET`
- **URL**: `http://localhost:8001/backend/api/student.php?student_id=1&action=get_profile_info`
- **cURL Command for Postman**:
  ```bash
  curl --location 'http://localhost:8001/backend/api/student.php?student_id=1&action=get_profile_info'
  ```

### 2. Update Student Profile

- **Name**: `Update Student Profile`
- **Description**: Updates student profile information including contact details, bio, and academic information.
- **Method**: `POST`
- **URL**: `http://localhost:8001/backend/api/student.php?student_id=1&action=update_profile`
- **Body**:
  ```json
  {
      "name": "John Doe Updated",
      "email": "john.updated@student.sabanciuniv.edu",
      "phone": "555-123-4567",
      "address": "Updated Address, Istanbul",
      "bio": "Updated bio with new information about my interests and goals.",
      "major": "Computer Engineering",
      "gpa": "3.7"
  }
  ```
- **cURL Command for Postman**:
  ```bash
  curl --location --request POST 'http://localhost:8001/backend/api/student.php?student_id=1&action=update_profile' \
  --header 'Content-Type: application/json' \
  --data-raw '{
      "name": "John Doe Updated",
      "email": "john.updated@student.sabanciuniv.edu",
      "phone": "555-123-4567",
      "address": "Updated Address, Istanbul",
      "bio": "Updated bio with new information about my interests and goals.",
      "major": "Computer Engineering",
      "gpa": "3.7"
  }'
  ```

### 3. Get Available Internships

- **Name**: `Get Available Internships`
- **Description**: Retrieves all available internship opportunities for students to browse and apply to.
- **Method**: `GET`
- **URL**: `http://localhost:8001/backend/api/student.php?student_id=1&action=get_internships`
- **cURL Command for Postman**:
  ```bash
  curl --location 'http://localhost:8001/backend/api/student.php?student_id=1&action=get_internships'
  ```

### 4. Apply for Internship

- **Name**: `Apply for Internship`
- **Description**: Submits a new internship application for a student with optional cover letter.
- **Method**: `POST`
- **URL**: `http://localhost:8001/backend/api/student.php?student_id=1&action=apply`
- **Body**:
  ```json
  {
      "internship_id": 102,
      "cover_letter": "I am very interested in this Data Analyst position and believe my skills in Python and SQL make me a great fit for this role."
  }
  ```
- **cURL Command for Postman**:
  ```bash
  curl --location --request POST 'http://localhost:8001/backend/api/student.php?student_id=1&action=apply' \
  --header 'Content-Type: application/json' \
  --data-raw '{
      "internship_id": 102,
      "cover_letter": "I am very interested in this Data Analyst position and believe my skills in Python and SQL make me a great fit for this role."
  }'
  ```

### 5. Get Student Applications

- **Name**: `Get Student Applications`
- **Description**: Retrieves all applications submitted by a specific student with their current status.
- **Method**: `GET`
- **URL**: `http://localhost:8001/backend/api/student.php?student_id=1&action=get_student_applications`
- **cURL Command for Postman**:
  ```bash
  curl --location 'http://localhost:8001/backend/api/student.php?student_id=1&action=get_student_applications'
  ```

### 6. View Application Details

- **Name**: `View Application Details`
- **Description**: Gets detailed information about a specific application including internship details and current status.
- **Method**: `GET`
- **URL**: `http://localhost:8001/backend/api/student.php?student_id=1&action=view_application&application_id=1001`
- **cURL Command for Postman**:
  ```bash
  curl --location 'http://localhost:8001/backend/api/student.php?student_id=1&action=view_application&application_id=1001'
  ```

### 7. Withdraw Application

- **Name**: `Withdraw Application`
- **Description**: Allows a student to withdraw their application for an internship.
- **Method**: `POST`
- **URL**: `http://localhost:8001/backend/api/student.php?student_id=1&action=withdraw&application_id=1001`
- **cURL Command for Postman**:
  ```bash
  curl --location --request POST 'http://localhost:8001/backend/api/student.php?student_id=1&action=withdraw&application_id=1001'
  ```

### 8. Get Student Documents

- **Name**: `Get Student Documents`
- **Description**: Retrieves all documents uploaded by a student including CV, transcripts, etc.
- **Method**: `GET`
- **URL**: `http://localhost:8001/backend/api/student.php?student_id=1&action=get_documents`
- **cURL Command for Postman**:
  ```bash
  curl --location 'http://localhost:8001/backend/api/student.php?student_id=1&action=get_documents'
  ```

### 9. Get Student Evaluations

- **Name**: `Get Student Evaluations`
- **Description**: Gets all evaluations and feedback received by the student from companies.
- **Method**: `GET`
- **URL**: `http://localhost:8001/backend/api/student.php?student_id=1&action=get_evaluations`
- **cURL Command for Postman**:
  ```bash
  curl --location 'http://localhost:8001/backend/api/student.php?student_id=1&action=get_evaluations'
  ```

---

## 🏢 Company API Endpoints

**Base URL:** `http://localhost:8001/backend/api/company.php`

All company endpoints require a `company_id` parameter. The company API provides comprehensive functionality for managing internship postings, reviewing applications, and evaluating students.

### Profile Management

#### 1. Get Company Profile

- **Name**: `Get Company Profile`
- **Description**: Retrieves detailed company profile information including contact details and business information.
- **Method**: `GET`
- **URL**: `http://localhost:8001/backend/api/company.php?company_id=1&action=get_profile`
- **cURL Command for Postman**:
  ```bash
  curl --location 'http://localhost:8001/backend/api/company.php?company_id=1&action=get_profile'
  ```

#### 2. Update Company Profile

- **Name**: `Update Company Profile`
- **Description**: Updates company profile information including business details and contact information.
- **Method**: `POST`
- **URL**: `http://localhost:8001/backend/api/company.php?company_id=1&action=update_profile`
- **Body**:
  ```json
  {
      "name": "ABC Technologies Inc.",
      "email": "contact@abctech.com",
      "industry": "Information Technology & Software",
      "website": "https://www.abctech.com",
      "phone": "+90 212 555 0123",
      "address": "Maslak Technology Park, Istanbul, Turkey",
      "description": "Leading technology company specializing in web development, mobile applications, and cloud solutions."
  }
  ```
- **cURL Command for Postman**:
  ```bash
  curl --location --request POST 'http://localhost:8001/backend/api/company.php?company_id=1&action=update_profile' \
  --header 'Content-Type: application/json' \
  --data-raw '{
      "name": "ABC Technologies Inc.",
      "email": "contact@abctech.com",
      "industry": "Information Technology & Software",
      "website": "https://www.abctech.com",
      "phone": "+90 212 555 0123",
      "address": "Maslak Technology Park, Istanbul, Turkey",
      "description": "Leading technology company specializing in web development, mobile applications, and cloud solutions."
  }'
  ```

### Internship Management

#### 3. Get Company Internships

- **Name**: `Get Company Internships`
- **Description**: Retrieves all internship postings created by the company.
- **Method**: `GET`
- **URL**: `http://localhost:8001/backend/api/company.php?company_id=1&action=get_internships`
- **cURL Command for Postman**:
  ```bash
  curl --location 'http://localhost:8001/backend/api/company.php?company_id=1&action=get_internships'
  ```

#### 4. Create New Internship

- **Name**: `Create New Internship`
- **Description**: Creates a new internship posting for the company with detailed job requirements and information.
- **Method**: `POST`
- **URL**: `http://localhost:8001/backend/api/company.php?company_id=1&action=create_internship`
- **Body**:
  ```json
  {
      "title": "Full Stack Developer Intern",
      "description": "Join our dynamic development team to work on cutting-edge web applications using React, Node.js, and MongoDB. You'll gain hands-on experience in full-stack development, API design, and cloud deployment.",
      "location": "Istanbul, Turkey (Hybrid)",
      "dates": "June 2024 - August 2024",
      "requirements": "JavaScript, React, Node.js knowledge preferred. Strong problem-solving skills and willingness to learn.",
      "salary": "4000 TL/month",
      "type": "Full-time",
      "application_deadline": "2024-05-15"
  }
  ```
- **cURL Command for Postman**:
  ```bash
  curl --location --request POST 'http://localhost:8001/backend/api/company.php?company_id=1&action=create_internship' \
  --header 'Content-Type: application/json' \
  --data-raw '{
      "title": "Full Stack Developer Intern",
      "description": "Join our dynamic development team to work on cutting-edge web applications using React, Node.js, and MongoDB. You will gain hands-on experience in full-stack development, API design, and cloud deployment.",
      "location": "Istanbul, Turkey (Hybrid)",
      "dates": "June 2024 - August 2024",
      "requirements": "JavaScript, React, Node.js knowledge preferred. Strong problem-solving skills and willingness to learn.",
      "salary": "4000 TL/month",
      "type": "Full-time",
      "application_deadline": "2024-05-15"
  }'
  ```

#### 5. Update Internship

- **Name**: `Update Internship`
- **Description**: Updates an existing internship posting with new information or requirements.
- **Method**: `POST`
- **URL**: `http://localhost:8001/backend/api/company.php?company_id=1&action=update_internship&internship_id=101`
- **Body**:
  ```json
  {
      "description": "Updated: Join our development team working on innovative web applications. Now including mobile app development with React Native.",
      "location": "Istanbul, Turkey (Remote Available)",
      "requirements": "JavaScript, React, Node.js. Mobile development experience is a plus.",
      "salary": "4500 TL/month",
      "application_deadline": "2024-05-20"
  }
  ```
- **cURL Command for Postman**:
  ```bash
  curl --location --request POST 'http://localhost:8001/backend/api/company.php?company_id=1&action=update_internship&internship_id=101' \
  --header 'Content-Type: application/json' \
  --data-raw '{
      "description": "Updated: Join our development team working on innovative web applications. Now including mobile app development with React Native.",
      "location": "Istanbul, Turkey (Remote Available)",
      "requirements": "JavaScript, React, Node.js. Mobile development experience is a plus.",
      "salary": "4500 TL/month",
      "application_deadline": "2024-05-20"
  }'
  ```

#### 6. Delete Internship

- **Name**: `Delete Internship`
- **Description**: Deletes an internship posting (marks as deleted in the system).
- **Method**: `POST`
- **URL**: `http://localhost:8001/backend/api/company.php?company_id=1&action=delete_internship&internship_id=101`
- **cURL Command for Postman**:
  ```bash
  curl --location --request POST 'http://localhost:8001/backend/api/company.php?company_id=1&action=delete_internship&internship_id=101'
  ```

### Application Management

#### 7. Get All Applications

- **Name**: `Get All Applications`
- **Description**: Retrieves all applications received for company's internship postings with student and internship details.
- **Method**: `GET`
- **URL**: `http://localhost:8001/backend/api/company.php?company_id=1&action=get_applications`
- **cURL Command for Postman**:
  ```bash
  curl --location 'http://localhost:8001/backend/api/company.php?company_id=1&action=get_applications'
  ```

#### 8. Get Single Application

- **Name**: `Get Single Application`
- **Description**: Gets detailed information about a specific application including student profile, documents, and internship details.
- **Method**: `GET`
- **URL**: `http://localhost:8001/backend/api/company.php?company_id=1&action=get_application&application_id=1001`
- **cURL Command for Postman**:
  ```bash
  curl --location 'http://localhost:8001/backend/api/company.php?company_id=1&action=get_application&application_id=1001'
  ```

#### 9. Update Application Status

- **Name**: `Update Application Status`
- **Description**: Updates the status of a student's application. Valid statuses: Pending, Under Review, Shortlisted, Interview Scheduled, Offered, Rejected, Accepted, Declined.
- **Method**: `POST`
- **URL**: `http://localhost:8001/backend/api/company.php?company_id=1&action=update_application_status&application_id=1001`
- **Body**:
  ```json
  {
      "status": "Interview Scheduled"
  }
  ```
- **cURL Command for Postman**:
  ```bash
  curl --location --request POST 'http://localhost:8001/backend/api/company.php?company_id=1&action=update_application_status&application_id=1001' \
  --header 'Content-Type: application/json' \
  --data-raw '{
      "status": "Interview Scheduled"
  }'
  ```

### Evaluation Management

#### 10. Create Student Evaluation

- **Name**: `Create Student Evaluation`
- **Description**: Creates a detailed evaluation for a student's internship performance including ratings and feedback.
- **Method**: `POST`
- **URL**: `http://localhost:8001/backend/api/company.php?company_id=1&action=create_evaluation&application_id=1001`
- **Body**:
  ```json
  {
      "rating": 4.5,
      "comments": "Excellent intern who showed great technical skills and professional attitude throughout the internship period.",
      "technical_skills": 4.0,
      "communication_skills": 4.5,
      "teamwork": 5.0,
      "problem_solving": 4.0,
      "overall_performance": 4.5,
      "recommendation": "Highly recommend for future full-time positions",
      "evaluator_name": "Jane Smith, Technical Lead"
  }
  ```
- **cURL Command for Postman**:
  ```bash
  curl --location --request POST 'http://localhost:8001/backend/api/company.php?company_id=1&action=create_evaluation&application_id=1001' \
  --header 'Content-Type: application/json' \
  --data-raw '{
      "rating": 4.5,
      "comments": "Excellent intern who showed great technical skills and professional attitude throughout the internship period.",
      "technical_skills": 4.0,
      "communication_skills": 4.5,
      "teamwork": 5.0,
      "problem_solving": 4.0,
      "overall_performance": 4.5,
      "recommendation": "Highly recommend for future full-time positions",
      "evaluator_name": "Jane Smith, Technical Lead"
  }'
  ```

#### 11. Get Company Evaluations

- **Name**: `Get Company Evaluations`
- **Description**: Retrieves all evaluations created by the company for their intern students.
- **Method**: `GET`
- **URL**: `http://localhost:8001/backend/api/company.php?company_id=1&action=get_evaluations`
- **cURL Command for Postman**:
  ```bash
  curl --location 'http://localhost:8001/backend/api/company.php?company_id=1&action=get_evaluations'
  ```

### Dashboard & Analytics

#### 12. Get Dashboard Statistics

- **Name**: `Get Dashboard Statistics`
- **Description**: Gets comprehensive dashboard statistics including internship counts, application statistics, and recent activity.
- **Method**: `GET`
- **URL**: `http://localhost:8001/backend/api/company.php?company_id=1&action=get_dashboard_stats`
- **cURL Command for Postman**:
  ```bash
  curl --location 'http://localhost:8001/backend/api/company.php?company_id=1&action=get_dashboard_stats'
  ```

---

## 👨‍💼 Admin API Endpoints

**Base URL:** `http://localhost:8001/backend/api/admin.php`

Admin endpoints provide system-wide management capabilities including user administration, term management, and system oversight.

### Term Management

#### 1. Get All Terms

- **Name**: `Get All Terms`
- **Description**: Retrieves all academic terms in the system.
- **Method**: `GET`
- **URL**: `http://localhost:8001/backend/api/admin.php?action=get_terms`
- **cURL Command for Postman**:
  ```bash
  curl --location 'http://localhost:8001/backend/api/admin.php?action=get_terms'
  ```

#### 2. Add New Term

- **Name**: `Add New Term`
- **Description**: Creates a new academic term with start and end dates.
- **Method**: `POST`
- **URL**: `http://localhost:8001/backend/api/admin.php?action=add_term`
- **Body**:
  ```json
  {
      "name": "2024-2025 Spring",
      "start_date": "2025-02-01",
      "end_date": "2025-06-15"
  }
  ```
- **cURL Command for Postman**:
  ```bash
  curl --location --request POST 'http://localhost:8001/backend/api/admin.php?action=add_term' \
  --header 'Content-Type: application/json' \
  --data-raw '{
      "name": "2024-2025 Spring",
      "start_date": "2025-02-01",
      "end_date": "2025-06-15"
  }'
  ```

### Student Management

#### 3. Get All Students

- **Name**: `Get All Students`
- **Description**: Retrieves all students registered in the system.
- **Method**: `GET`
- **URL**: `http://localhost:8001/backend/api/admin.php?action=get_students`
- **cURL Command for Postman**:
  ```bash
  curl --location 'http://localhost:8001/backend/api/admin.php?action=get_students'
  ```

#### 4. Add New Student

- **Name**: `Add New Student`
- **Description**: Adds a new student to the system with complete profile information.
- **Method**: `POST`
- **URL**: `http://localhost:8001/backend/api/admin.php?action=add_student`
- **Body**:
  ```json
  {
      "name": "Alice Johnson",
      "email": "alice.johnson@student.sabanciuniv.edu",
      "student_id": "20002",
      "major": "Industrial Engineering",
      "gpa": "3.8",
      "phone": "555-987-6543",
      "address": "Sabanci University Campus, Istanbul",
      "bio": "Industrial engineering student with interests in operations research and data analytics."
  }
  ```
- **cURL Command for Postman**:
  ```bash
  curl --location --request POST 'http://localhost:8001/backend/api/admin.php?action=add_student' \
  --header 'Content-Type: application/json' \
  --data-raw '{
      "name": "Alice Johnson",
      "email": "alice.johnson@student.sabanciuniv.edu",
      "student_id": "20002",
      "major": "Industrial Engineering",
      "gpa": "3.8",
      "phone": "555-987-6543",
      "address": "Sabanci University Campus, Istanbul",
      "bio": "Industrial engineering student with interests in operations research and data analytics."
  }'
  ```

### Company Management

#### 5. Get All Companies

- **Name**: `Get All Companies`
- **Description**: Retrieves all companies registered in the system.
- **Method**: `GET`
- **URL**: `http://localhost:8001/backend/api/admin.php?action=get_companies`
- **cURL Command for Postman**:
  ```bash
  curl --location 'http://localhost:8001/backend/api/admin.php?action=get_companies'
  ```

#### 6. Get System Statistics

- **Name**: `Get System Statistics`
- **Description**: Gets comprehensive system statistics including total users, applications, internships, and success rates.
- **Method**: `GET`
- **URL**: `http://localhost:8001/backend/api/admin.php?action=get_system_stats`
- **cURL Command for Postman**:
  ```bash
  curl --location 'http://localhost:8001/backend/api/admin.php?action=get_system_stats'
  ```

---

## 🔧 API Testing & Development

### Environment Setup

1. **Start PHP Server**: `php -S localhost:8001 -t /path/to/project`
2. **Test Base Endpoints**: Verify each API returns help information when called without parameters
3. **Set Postman Environment**: Create environment with `base_url = http://localhost:8001`

### Common Response Formats

**Success Response:**
```json
{
    "status": "success",
    "message": "Operation completed successfully",
    "data": { /* response data */ }
}
```

**Error Response:**
```json
{
    "error": "Error description",
    "details": "Additional error information (optional)"
}
```

### HTTP Status Codes

- `200 OK` - Successful GET/POST requests
- `400 Bad Request` - Invalid parameters or JSON
- `404 Not Found` - Resource not found
- `405 Method Not Allowed` - Wrong HTTP method
- `500 Internal Server Error` - Server-side errors

---

## 📝 Testing Workflow

### Recommended Testing Order

1. **Start with Admin API** - Set up terms, add students and companies
2. **Test Student API** - Profile management, browse internships
3. **Test Company API** - Create internships, manage applications
4. **Full Workflow Test** - Student applies → Company reviews → Evaluation process

### Sample Test Data

Use these IDs for testing:
- `student_id`: 1 (John Doe)
- `company_id`: 1 (ABC Technologies)
- `internship_id`: 101, 102
- `application_id`: 1001

---

## 🎯 API Summary

| API Type | Endpoints | Primary Functions |
|----------|-----------|-------------------|
| **Student** | 9 endpoints | Profile, applications, internship browsing |
| **Company** | 12 endpoints | Internship posting, application review, evaluations |
| **Admin** | 6+ endpoints | System management, user administration |

**Total**: 27+ fully documented API endpoints with complete Postman integration.

All endpoints include comprehensive error handling, validation, and detailed response formats for seamless integration.