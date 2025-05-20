# Sabancı University Internship Portal - Backend

This directory contains the PHP backend code for the Sabancı University Internship Portal frontend.

## Structure

*   `index.php`: Main router. Parses request and includes the appropriate handler.
*   `handlers/`: Directory containing specific logic for each data entity.
    *   `internships_handler.php`: Manages internship listings.
    *   `applications_handler.php`: Manages student applications to internships.
    *   `documents_handler.php`: Manages document uploads related to applications.

## Progress Log

*   **Initial Setup & Refactor:** 
    *   Created `index.php` as main entry point.
    *   Refactored into a router (`index.php`) and entity-specific handlers (`handlers/*.php`).
*   **Student Application Flow (Mock Data):**
    *   Data: `$internships`, `$applications`, `$documents` defined in `index.php`.
    *   **Internships (Student Perspective):**
        *   `GET /index.php?entity=internships`: List all available internships.
        *   `GET /index.php?entity=internships&id={internship_id}`: Get details of a specific internship.
    *   **Applications (Student Actions):**
        *   `GET /index.php?entity=applications&student_id={id}`: List applications for a specific student.
        *   `GET /index.php?entity=applications&id={app_id}`: Get details for a specific application.
        *   `POST /index.php?entity=applications&action=apply` (Body: `{"student_id": ..., "internship_id": ..., "cover_letter": "..."}`)
        *   `POST /index.php?entity=applications&id={app_id}&action=withdraw`
        *   `POST /index.php?entity=applications&id={app_id}&action=confirm_offer` (Changes status to `Confirmed_By_Student`)
    *   **Documents (Student Actions):**
        *   `GET /index.php?entity=documents&application_id={app_id}`: List uploaded documents for an application.
        *   `POST /index.php?entity=documents&action=upload&application_id={app_id}` (Body: `{"student_id": ..., "document_type": "...", "file_name": "..."}`). Requires application status `Confirmed_By_Student` or `Approved_By_Company`.
*   **Company Flow - Internship Management (Mock Data):**
    *   **Internships (Company Actions - `internships_handler.php`):**
        *   `GET /index.php?entity=internships&company_id={id}`: List internships posted by a specific company.
        *   `POST /index.php?entity=internships&action=create` (Body: `{"company_id": ..., "position": ..., "description": ..., "company_name": ...}`)
        *   `POST /index.php?entity=internships&id={internship_id}&action=update` (Body: fields to update)
        *   `POST /index.php?entity=internships&id={internship_id}&action=set_status` (Body: `{"status": "active" | "inactive"}`)
        *   `POST /index.php?entity=internships&id={internship_id}&action=delete`
        *   `POST /index.php?entity=internships&id={internship_id}&action=duplicate`
    *   **Applications (Company Perspective - `applications_handler.php`):**
        *   `GET /index.php?entity=applications&company_id={comp_id}`: List applications for all internships of a company.
        *   `GET /index.php?entity=applications&internship_id={internship_id}`: List applications for a specific internship.
        *   `POST /index.php?entity=applications&id={app_id}&action=update_status_company` (Body: `{"status": "Offered" | "Rejected_By_Company" | ...}`)

## Next Steps

*   Test the new Company Flow endpoints.
*   Implement User Authentication (Login/Registration) - crucial for company_id/student_id context.
*   Set up a database connection (e.g., MySQL/PostgreSQL via PDO) and replace mock data.
*   Develop modules for Admin functionalities.
*   Integrate backend endpoints with the frontend JavaScript.

## Running the Backend (Development)

1.  Navigate to the `backend` directory.
2.  Run the built-in PHP server: `php -S localhost:8000` 