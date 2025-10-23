# Sabanci Internship Portal API Documentation

This document outlines the available endpoints for the backend. The API follows a centralized routing model where all requests are sent to `index.php`.

**Base URL:** `http://localhost:8001/`

## API Design

The API uses a **Front Controller** pattern. All requests are routed through `index.php`. The type of data you want to interact with is specified by the `entity` URL parameter, and the specific action is specified by the `action` parameter for POST requests.

---

## Student Endpoints

These endpoints are for actions a student would typically perform.

### 1. List All Available Internships
- **Method:** `GET`
- **URL:** `http://localhost:8001/index.php?entity=internships`

### 2. Get Details for a Specific Internship
- **Method:** `GET`
- **URL:** `http://localhost:8001/index.php?entity=internships&id={internship_id}`
- **URL Parameters:**
  - `id`: The ID of the internship (e.g., `INT001`).

### 3. List Your Applications
- **Method:** `GET`
- **URL:** `http://localhost:8001/index.php?entity=applications&student_id={student_id}`
- **URL Parameters:**
  - `student_id`: Your student ID (e.g., `1`).

### 4. Apply for an Internship
- **Method:** `POST`
- **URL:** `http://localhost:8001/index.php?entity=applications&action=apply`
- **Body (JSON):**
  ```json
  {
      "student_id": 1,
      "internship_id": "INT003",
      "cover_letter": "I am very excited about this opportunity."
  }
  ```

### 5. Withdraw an Application
- **Method:** `POST`
- **URL:** `http://localhost:8001/index.php?entity=applications&id={application_id}&action=withdraw`
- **URL Parameters:**
  - `id`: The ID of the application to withdraw (e.g., `APP001`).

### 6. Confirm an Internship Offer
- **Method:** `POST`
- **URL:** `http://localhost:8001/index.php?entity=applications&id={application_id}&action=confirm_offer`
- **URL Parameters:**
  - `id`: The ID of the application for which you are confirming the offer (e.g., `APP002`).

### 7. List Documents for an Application
- **Method:** `GET`
- **URL:** `http://localhost:8001/index.php?entity=documents&application_id={application_id}`
- **URL Parameters:**
  - `application_id`: The ID of the application (e.g., `APP002`).

### 8. Upload a Document
- **Method:** `POST`
- **URL:** `http://localhost:8001/index.php?entity=documents&action=upload&application_id={application_id}`
- **URL Parameters:**
  - `application_id`: The ID of the application (e.g., `APP002`).
- **Body (JSON):**
  ```json
  {
      "student_id": 1,
      "document_type": "CV",
      "file_name": "my_updated_cv.pdf"
  }
  ```

---

## Company Endpoints

These endpoints are for actions a company representative would perform.

### 1. List Your Company's Internships
- **Method:** `GET`
- **URL:** `http://localhost:8001/index.php?entity=internships&company_id={company_id}`
- **URL Parameters:**
  - `company_id`: Your company ID (e.g., `COMP001`).

### 2. Create a New Internship
- **Method:** `POST`
- **URL:** `http://localhost:8001/index.php?entity=internships&action=create`
- **Body (JSON):**
  ```json
  {
      "company_id": "COMP001",
      "company_name": "Tech Solutions Inc.",
      "position": "Frontend Developer Intern",
      "description": "Work with our amazing frontend team on a new product.",
      "location": "Remote"
  }
  ```

### 3. Update an Internship
- **Method:** `POST`
- **URL:** `http://localhost:8001/index.php?entity=internships&id={internship_id}&action=update`
- **URL Parameters:**
  - `id`: The ID of the internship to update.
- **Body (JSON):**
  ```json
  {
      "position": "Senior Frontend Developer Intern",
      "description": "An updated description for the role."
  }
  ```

### 4. List Applications for Your Company
- **Method:** `GET`
- **URL:** `http://localhost:8001/index.php?entity=applications&company_id={company_id}`
- **URL Parameters:**
  - `company_id`: Your company ID (e.g., `COMP001`).

### 5. List Applications for a Specific Internship
- **Method:** `GET`
- **URL:** `http://localhost:8001/index.php?entity=applications&internship_id={internship_id}`
- **URL Parameters:**
  - `internship_id`: The ID of the internship.

### 6. Update an Application's Status
- **Method:** `POST`
- **URL:** `http://localhost:8001/index.php?entity=applications&id={application_id}&action=update_status_company`
- **URL Parameters:**
  - `id`: The ID of the application to update.
- **Body (JSON):**
  ```json
  {
      "status": "Offered",
      "offer_details": "Your offer is valid until July 30, 2025."
  }
  ```
  *Other valid statuses include: `Rejected_By_Company`, `Interview_Scheduled`, `Approved_By_Company`.*

---

## Admin Endpoints

These endpoints provide administrative control over the platform's core data.

### 1. Get All Students
- **Method:** `GET`
- **URL:** `http://localhost:8001/index.php?entity=admin&resource=students`

### 2. Get a Specific Student
- **Method:** `GET`
- **URL:** `http://localhost:8001/index.php?entity=admin&resource=students&id={student_id}`

### 3. Add a New Student
- **Method:** `POST`
- **URL:** `http://localhost:8001/index.php?entity=admin&resource=students&action=add`
- **Body (JSON):**
  ```json
  {
    "name": "Jane Doe",
    "email": "jane.doe@example.com",
    "student_id": "98765",
    "major": "Computer Science"
  }
  ```

### 4. Update a Student
- **Method:** `POST`
- **URL:** `http://localhost:8001/index.php?entity=admin&resource=students&id={student_id}&action=update`
- **Body (JSON):**
  ```json
  {
    "major": "Electrical Engineering",
    "gpa": "3.8"
  }
  ```

### 5. Get All Companies
- **Method:** `GET`
- **URL:** `http://localhost:8001/index.php?entity=admin&resource=companies`

### 6. Add a New Company
- **Method:** `POST`
- **URL:** `http://localhost:8001/index.php?entity=admin&resource=companies&action=add`
- **Body (JSON):**
  ```json
  {
    "name": "New Innovators Inc.",
    "email": "contact@newinnovators.com",
    "industry": "Technology"
  }
  ```

### 7. Get All Terms
- **Method:** `GET`
- **URL:** `http://localhost:8001/index.php?entity=admin&resource=terms`

### 8. Get All Applications (Unfiltered)
- **Method:** `GET`
- **URL:** `http://localhost:8001/index.php?entity=applications`
*(This is an existing endpoint, but it is most useful in an Admin context).*
