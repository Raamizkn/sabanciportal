# Sabancı University Internship Portal - Backend

This directory contains the PHP backend code for the Sabancı University Internship Portal frontend.

## Progress Log

*   **Initial Setup:** Created `backend/index.php` as the main entry point.
*   **Student Application Module (Mock Data):**
    *   Implemented basic routing in `index.php` based on `$_GET` parameters (`entity`, `action`).
    *   Added mock data for student applications.
    *   Created API endpoints (using mock data):
        *   `GET /index.php?entity=applications&student_id={id}`: Fetch applications for a student.
        *   `POST /index.php?action=withdraw&application_id={id}`: Withdraw an application.
        *   `POST /index.php?action=confirm&application_id={id}`: Confirm an accepted application.
    *   Tested endpoints using `curl`.

## Next Steps

*   Implement User Authentication (Login/Registration).
*   Set up a database connection (e.g., MySQL/PostgreSQL via PDO).
*   Replace mock data with actual database queries.
*   Develop modules for Company and Admin functionalities.
*   Integrate backend endpoints with the frontend JavaScript.

## Running the Backend (Development)

1.  Navigate to the `backend` directory.
2.  Run the built-in PHP server: `php -S localhost:8000` 