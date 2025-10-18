# API Documentation & Postman Guide

This document provides a complete guide to testing the backend API endpoints using Postman. The backend server is running on `http://localhost:8001`.

## How to Use with Postman

You can easily import these examples into Postman:

1.  Copy the complete `curl` command provided for an endpoint.
2.  In Postman, click the **Import** button.
3.  Select the **Raw Text** tab.
4.  Paste the `curl` command and click **Continue**.
5.  Postman will automatically create the request for you.

---

## Student-Facing Endpoints

These endpoints are for student-related actions like viewing internships and managing applications.

### 1. List All Available Internships

- **Name**: `Get All Internships`
- **Description**: Retrieves a list of all internships currently marked as 'active'.
- **Method**: `GET`
- **URL**: `http://localhost:8001/index.php?entity=internships`
- **cURL Command for Postman**:
  ```bash
  curl --location 'http://localhost:8001/index.php?entity=internships'
  ```

### 2. Get Details of a Specific Internship

- **Name**: `Get Single Internship`
- **Description**: Retrieves the full details for a single internship by its ID.
- **Method**: `GET`
- **URL**: `http://localhost:8001/index.php?entity=internships&id=INT001`
- **cURL Command for Postman**:
  ```bash
  curl --location 'http://localhost:8001/index.php?entity=internships&id=INT001'
  ```

### 3. List a Student's Applications

- **Name**: `Get Student Applications`
- **Description**: Retrieves all applications submitted by a specific student.
- **Method**: `GET`
- **URL**: `http://localhost:8001/index.php?entity=applications&student_id=1`
- **cURL Command for Postman**:
  ```bash
  curl --location 'http://localhost:8001/index.php?entity=applications&student_id=1'
  ```

### 4. Get Details of a Specific Application

- **Name**: `Get Single Application`
- **Description**: Retrieves the details for a single application by its ID.
- **Method**: `GET`
- **URL**: `http://localhost:8001/index.php?entity=applications&id=APP001`
- **cURL Command for Postman**:
  ```bash
  curl --location 'http://localhost:8001/index.php?entity=applications&id=APP001'
  ```

### 5. Apply for an Internship

- **Name**: `Apply for Internship`
- **Description**: Submits a new application for an internship on behalf of a student.
- **Method**: `POST`
- **URL**: `http://localhost:8001/index.php?entity=applications&action=apply`
- **Body**:
  ```json
  {
      "student_id": 1,
      "internship_id": "INT003",
      "cover_letter": "I am very interested in the Marketing Intern position and believe my skills are a great fit."
  }
  ```
- **cURL Command for Postman**:
  ```bash
  curl --location --request POST 'http://localhost:8001/index.php?entity=applications&action=apply' \
  --header 'Content-Type: application/json' \
  --data-raw '{
      "student_id": 1,
      "internship_id": "INT003",
      "cover_letter": "I am very interested in the Marketing Intern position and believe my skills are a great fit."
  }'
  ```

### 6. Withdraw an Application

- **Name**: `Withdraw Application`
- **Description**: Withdraws a student's application.
- **Method**: `POST`
- **URL**: `http://localhost:8001/index.php?entity=applications&id=APP001&action=withdraw`
- **cURL Command for Postman**:
  ```bash
  curl --location --request POST 'http://localhost:8001/index.php?entity=applications&id=APP001&action=withdraw'
  ```

### 7. Confirm Internship Offer

- **Name**: `Confirm Offer`
- **Description**: Allows a student to confirm an offer they have received.
- **Method**: `POST`
- **URL**: `http://localhost:8001/index.php?entity=applications&id=APP002&action=confirm_offer`
- **cURL Command for Postman**:
  ```bash
  curl --location --request POST 'http://localhost:8001/index.php?entity=applications&id=APP002&action=confirm_offer'
  ```

### 8. List Documents for an Application

- **Name**: `Get Application Documents`
- **Description**: Retrieves a list of documents associated with a specific application.
- **Method**: `GET`
- **URL**: `http://localhost:8001/index.php?entity=documents&application_id=APP002`
- **cURL Command for Postman**:
  ```bash
  curl --location 'http://localhost:8001/index.php?entity=documents&application_id=APP002'
  ```

### 9. Upload a Document

- **Name**: `Upload Document`
- **Description**: Uploads a new document for an application. The application status must be `Confirmed_By_Student` or `Approved_By_Company`.
- **Method**: `POST`
- **URL**: `http://localhost:8001/index.php?entity=documents&action=upload&application_id=APP002`
- **Body**:
  ```json
  {
      "student_id": 1,
      "document_type": "SGK Form",
      "file_name": "student1_sgk_form.pdf"
  }
  ```
- **cURL Command for Postman**:
  ```bash
  curl --location --request POST 'http://localhost:8001/index.php?entity=documents&action=upload&application_id=APP002' \
  --header 'Content-Type: application/json' \
  --data-raw '{
      "student_id": 1,
      "document_type": "SGK Form",
      "file_name": "student1_sgk_form.pdf"
  }'
  ```

---

## Company-Facing Endpoints

These endpoints are for company-related actions like managing internship postings and reviewing applications.

### 1. List a Company's Internships

- **Name**: `Get Company Internships`
- **Description**: Retrieves all internships posted by a specific company.
- **Method**: `GET`
- **URL**: `http://localhost:8001/index.php?entity=internships&company_id=COMP001`
- **cURL Command for Postman**:
  ```bash
  curl --location 'http://localhost:8001/index.php?entity=internships&company_id=COMP001'
  ```

### 2. Create a New Internship

- **Name**: `Create Internship`
- **Description**: Creates a new internship posting for a company.
- **Method**: `POST`
- **URL**: `http://localhost:8001/index.php?entity=internships&action=create`
- **Body**:
  ```json
  {
      "company_id": "COMP001",
      "company_name": "Tech Solutions Inc.",
      "position": "Frontend Developer Intern",
      "description": "Work with our team on a new React-based UI.",
      "location": "Remote"
  }
  ```
- **cURL Command for Postman**:
  ```bash
  curl --location --request POST 'http://localhost:8001/index.php?entity=internships&action=create' \
  --header 'Content-Type: application/json' \
  --data-raw '{
      "company_id": "COMP001",
      "company_name": "Tech Solutions Inc.",
      "position": "Frontend Developer Intern",
      "description": "Work with our team on a new React-based UI.",
      "location": "Remote"
  }'
  ```

### 3. Update an Internship

- **Name**: `Update Internship`
- **Description**: Updates the details of an existing internship.
- **Method**: `POST`
- **URL**: `http://localhost:8001/index.php?entity=internships&id=INT001&action=update`
- **Body**:
  ```json
  {
      "description": "Work on exciting new software projects, now with a focus on backend services.",
      "location": "New York, NY"
  }
  ```
- **cURL Command for Postman**:
  ```bash
  curl --location --request POST 'http://localhost:8001/index.php?entity=internships&id=INT001&action=update' \
  --header 'Content-Type: application/json' \
  --data-raw '{
      "description": "Work on exciting new software projects, now with a focus on backend services.",
      "location": "New York, NY"
  }'
  ```

### 4. Delete an Internship

- **Name**: `Delete Internship`
- **Description**: Deletes an internship posting.
- **Method**: `POST`
- **URL**: `http://localhost:8001/index.php?entity=internships&id=INT004&action=delete`
- **cURL Command for Postman**:
  ```bash
  curl --location --request POST 'http://localhost:8001/index.php?entity=internships&id=INT004&action=delete'
  ```

### 5. List Applications for a Company

- **Name**: `Get Company Applications`
- **Description**: Retrieves all applications for all internships at a specific company.
- **Method**: `GET`
- **URL**: `http://localhost:8001/index.php?entity=applications&company_id=COMP001`
- **cURL Command for Postman**:
  ```bash
  curl --location 'http://localhost:8001/index.php?entity=applications&company_id=COMP001'
  ```

### 6. Update Application Status

- **Name**: `Update Application Status (Company)`
- **Description**: Allows a company to change the status of an application (e.g., to make an offer).
- **Method**: `POST`
- **URL**: `http://localhost:8001/index.php?entity=applications&id=APP001&action=update_status_company`
- **Body**:
  ```json
  {
      "status": "Offered"
  }
  ```
- **cURL Command for Postman**:
  ```bash
  curl --location --request POST 'http://localhost:8001/index.php?entity=applications&id=APP001&action=update_status_company' \
  --header 'Content-Type: application/json' \
  --data-raw '{
      "status": "Offered"
  }'
  ```