# Sabancı University Internship Portal

This project is the frontend for the Sabancı University Internship Portal, designed to connect students with internship opportunities offered by various companies.

## Functionality

The portal provides distinct functionalities based on user roles:

### Common Features:
*   **Homepage:** Landing page with general information and navigation.
*   **User Authentication:** Secure login and registration for all user types.
*   **About/FAQ/Contact:** Informational pages.

### Student Role:
*   **Profile Management:** Create and update student profiles.
*   **Browse Internships:** Search and view available internship listings.
*   **Apply for Internships:** Submit applications to desired internships.
*   **View Application Status:** Track the status of submitted applications.
*   **Submit Evaluations:** Provide feedback or evaluations upon internship completion (if applicable).

### Company Role:
*   **Profile Management:** Create and update company profiles.
*   **Post Internships:** Create and publish new internship opportunities.
*   **Manage Listings:** Edit, view, or remove existing internship postings.
*   **Review Applications:** View and manage applications received from students.
*   **Manage Interns:** Track accepted interns and potentially manage evaluation processes.

### Administrator Role:
*   **User Management:** View, manage, and potentially approve/verify student and company accounts.
*   **Internship Oversight:** Monitor internship listings and applications.
*   **System Configuration:** (Potentially) Manage site settings or content.

## Technologies Used (Frontend)

*   HTML5
*   CSS3 (with custom Sabancı theme)
*   Bootstrap 5
*   JavaScript (Note: Currently uses `localStorage` for state management, needs modification for backend integration)

## Setup and Running

(Instructions for setting up and running the frontend project locally would go here - currently TBD based on further analysis of assets/scripts.)

## Backend Integration

This frontend requires a backend API to handle:
*   User authentication (login, registration)
*   Data storage and retrieval (user profiles, company profiles, internship listings, applications)
*   Business logic for application processing, status updates, etc.

A basic PHP backend is under development in the `/backend` directory.

**Current Backend Status:**
*   Uses a single `index.php` entry point.
*   Handles basic student application actions (`fetch`, `withdraw`, `confirm`) using **mock data**.
*   Next steps involve adding database integration, user authentication, and implementing other modules.

*(This README will be updated as the project evolves.)* 