# API Documentation - Sabanci Internship Portal

This document outlines the implemented API endpoints for the internship portal. The backend is built with PHP and uses a mock data store.

## General Concepts

- **Base URL**: All API endpoints are relative to the server root.
- **Data Format**: All requests and responses are in JSON format.
- **Authentication**: Currently, authentication is not implemented. A `student_id` or `admin_id` is assumed or passed as a URL parameter for testing.

---

## Student API

**Endpoint File**: `/api/student.php`

### Profile Management

#### Get Profile Info
- **Action**: `get_profile_info`
- **Method**: `GET`
- **Description**: Retrieves the profile information for a specific student.
- **Parameters**: `student_id` (integer, URL parameter)
- **Example Request**:
  ```bash
  curl "http://localhost:8000/api/student.php?action=get_profile_info&student_id=1"
  ```

#### Update Profile
- **Action**: `update_profile`
- **Method**: `POST`
- **Description**: Updates a student's profile information.
- **Parameters**: `student_id` (integer, URL parameter), JSON body with fields to update (e.g., `{"phone": "555-123-4567", "bio": "New bio."}`).
- **Example Request**:
  ```bash
  curl -X POST -H "Content-Type: application/json" -d '{"phone": "555-123-4567"}' "http://localhost:8000/api/student.php?action=update_profile&student_id=1"
  ```

### Internship & Application Management

#### Get All Internships
- **Action**: `get_internships`
- **Method**: `GET`
- **Description**: Retrieves a list of all available internships.
- **Example Request**:
  ```bash
  curl "http://localhost:8000/api/student.php?action=get_internships"
  ```

#### Get Student's Applications
- **Action**: `get_student_applications`
- **Method**: `GET`
- **Description**: Retrieves all applications submitted by a specific student.
- **Parameters**: `student_id` (integer, URL parameter)
- **Example Request**:
  ```bash
  curl "http://localhost:8000/api/student.php?action=get_student_applications&student_id=1"
  ```

#### View Application Details
- **Action**: `view_application`
- **Method**: `GET`
- **Description**: Retrieves the full details of a single application.
- **Parameters**: `student_id`, `application_id` (integer, URL parameters)
- **Example Request**:
  ```bash
  curl "http://localhost:8000/api/student.php?action=view_application&student_id=1&application_id=1001"
  ```

#### Apply for Internship
- **Action**: `apply`
- **Method**: `POST`
- **Description**: Submits a new application for an internship.
- **Parameters**: `student_id` (URL parameter), JSON body with `internship_id`.
- **Example Request**:
  ```bash
  curl -X POST -H "Content-Type: application/json" -d '{"internship_id": 101}' "http://localhost:8000/api/student.php?action=apply&student_id=1"
  ```

#### Withdraw Application
- **Action**: `withdraw_application`
- **Method**: `POST`
- **Description**: Withdraws a previously submitted application.
- **Parameters**: `student_id` (URL parameter), JSON body with `application_id`.
- **Example Request**:
  ```bash
  curl -X POST -H "Content-Type: application/json" -d '{"application_id": 1001}' "http://localhost:8000/api/student.php?action=withdraw_application&student_id=1"
  ```

#### Accept Internship Offer
- **Action**: `accept_internship`
- **Method**: `POST`
- **Description**: Accepts an internship offer that has been approved by an admin.
- **Parameters**: `student_id` (URL parameter), JSON body with `application_id`.
- **Example Request**:
  ```bash
  curl -X POST -H "Content-Type: application/json" -d '{"application_id": 1001}' "http://localhost:8000/api/student.php?action=accept_internship&student_id=1"
  ```

### Document Management

#### Get Documents
- **Action**: `get_docs`
- **Method**: `GET`
- **Description**: Retrieves a list of a student's uploaded documents.
- **Parameters**: `student_id` (URL parameter)
- **Example Request**:
  ```bash
  curl "http://localhost:8000/api/student.php?action=get_docs&student_id=1"
  ```

#### Upload Document
- **Action**: `upload_doc`
- **Method**: `POST`
- **Description**: Simulates uploading a document.
- **Parameters**: `student_id` (URL parameter), JSON body with `document_type` and `file_name`.
- **Example Request**:
  ```bash
  curl -X POST -H "Content-Type: application/json" -d '{"document_type": "Cover Letter", "file_name": "my_cover_letter.pdf"}' "http://localhost:8000/api/student.php?action=upload_doc&student_id=1"
  ```

#### Delete Document
- **Action**: `delete_doc`
- **Method**: `POST`
- **Description**: Simulates deleting a document.
- **Parameters**: `student_id` (URL parameter), JSON body with `document_id`.
- **Example Request**:
  ```bash
  curl -X POST -H "Content-Type: application/json" -d '{"document_id": 2001}' "http://localhost:8000/api/student.php?action=delete_doc&student_id=1"
  ```

#### Download Document
- **Action**: `download_doc`
- **Method**: `GET`
- **Description**: Returns a simulated download URL for a document.
- **Parameters**: `student_id`, `document_id` (URL parameters)
- **Example Request**:
  ```bash
  curl "http://localhost:8000/api/student.php?action=download_doc&student_id=1&document_id=2001"
  ```

---

## Admin API

**Endpoint File**: `/api/admin.php`

### Term Management

- **Get All Terms**: `?action=get_terms` (GET)
- **Add Term**: `?action=add_term` (POST, Body: `{"name": "...", "start_date": "...", "end_date": "..."}`)
- **Update Term**: `?action=update_term` (POST, Body: `{"term_id": 1, "name": "..."}`)
- **Delete Term**: `?action=delete_term` (POST, Body: `{"term_id": 1}`)

### Student Management

- **Get All Students**: `?action=get_students` (GET)
- **Get Student Details**: `?action=get_student_details&student_id=1` (GET)
- **Add Student**: `?action=add_student` (POST, Body: `{"name": "...", "email": "...", ...}`)
- **Update Student**: `?action=update_student` (POST, Body: `{"student_id": 1, "name": "..."}`)
- **Delete Student**: `?action=delete_student` (POST, Body: `{"student_id": 1}`)
- **Impersonate Student**: `?action=impersonate_student&student_id=1` (GET)

### Company Management

- **Get All Companies**: `?action=get_companies` (GET)
- **Get Company Details**: `?action=get_company_details&company_id=1` (GET)
- **Add Company**: `?action=add_company` (POST, Body: `{"name": "...", "email": "...", ...}`)
- **Update Company**: `?action=update_company` (POST, Body: `{"company_id": 1, "name": "..."}`)
- **Delete Company**: `?action=delete_company` (POST, Body: `{"company_id": 1}`)
- **Impersonate Company**: `?action=impersonate_company&company_id=1` (GET)

### Internship Management

- **Get All Internships**: `?action=get_internships` (GET)
- **Get Internship Info**: `?action=get_internship_info&internship_id=101` (GET)
- **Edit Internship**: `?action=edit_internship` (POST, Body: `{"internship_id": 101, "title": "..."}`)
- **Delete Internship**: `?action=delete_internship` (POST, Body: `{"internship_id": 101}`)
- **Get Internship Applicants**: `?action=get_internship_applicants&internship_id=101` (GET)

### Application Management

- **Get Application Details**: `?action=get_application_details&application_id=1001` (GET)
- **Approve Application**: `?action=approve_application` (POST, Body: `{"application_id": 1001}`)
- **Reject Application**: `?action=reject_application` (POST, Body: `{"application_id": 1001}`)

### Reports & Tools

- **Generate Report**: `?action=generate_report` (POST, Body: `{"report_type": "..."}`)
- **Get Evaluation**: `?action=get_evaluation&evaluation_id=1` (GET)
- **Send Email**: `?action=send_email` (POST, Body: `{"to": "...", "subject": "...", "body": "..."}`)
