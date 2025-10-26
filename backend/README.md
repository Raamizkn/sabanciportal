# Sabancı University Internship Portal - Backend

This directory contains the PHP backend code for the Sabancı University Internship Portal frontend.

## Structure

*   `index.php`: Main router. Parses request and includes the appropriate handler.
*   `handlers/`: Directory containing specific logic for each data entity.
    *   `internships_handler.php`: Manages internship listings.
    *   `applications_handler.php`: Manages student applications to internships.
    *   `documents_handler.php`: Manages document uploads related to applications.

## Progress Log

### ✅ Completed (October 2025)

*   **Database Integration:**
    *   Connected to MySQL database (`pro2-dev.sabanciuniv.edu`)
    *   Created complete schema with 8 tables
    *   All CRUD operations persist to database
    *   See `config/create_tables.php` for setup

*   **Authentication & Security:**
    *   Implemented session-based authentication
    *   Role-based access control (Admin, Company, Student)
    *   Password hashing with bcrypt
    *   Ownership verification for all operations
    *   See `auth/auth.php` for implementation

*   **Complete Workflow:**
    *   Admin creates company → Database ✅
    *   Company creates internship → Database ✅
    *   Student applies → Database ✅
    *   Company offers → Database update ✅
    *   Student confirms → Database update ✅

*   **API Endpoints (All Protected):**
    *   Authentication endpoints
    *   Internship management (CRUD)
    *   Application management (CRUD)
    *   Admin operations (Companies, Students, Terms)
    *   See `../api_documentation.md` for complete reference

## Current Architecture

```
backend/
├── auth/                    # Authentication & authorization
│   ├── auth.php            # Helper functions
│   └── login_handler.php   # Login/logout logic
├── config/                 # Configuration
│   ├── database.php        # Database connection
│   ├── schema.sql          # Database schema
│   └── create_tables.php  # Setup script
├── data/                   # Data loading
│   ├── data.php           # Load from database
│   └── mock_data.php      # Fallback data
├── handlers/              # API handlers
│   ├── admin_handler.php  # Admin operations
│   ├── applications_handler.php # Application CRUD
│   ├── internships_handler.php # Internship CRUD
│   └── ...
└── index.php              # Main router
```

## Security Features

✅ **Authentication Required** - All POST operations protected
✅ **Role-Based Access** - Admin, Company, Student roles enforced
✅ **Ownership Checks** - Users can only modify their own data
✅ **Automatic User ID** - Prevents impersonation
✅ **Database Persistence** - All operations tracked

## Testing

Test credentials (password: `password123`):
- Admin: `admin@example.com`
- Company: `company@example.com`
- Student: `student@example.com`

See `../API_TEST_RESULTS.md` for complete test results.

## Running the Backend (Development)

1. **Setup Database:**
   ```bash
   cd backend
   php config/create_tables.php
   ```

2. **Start Server:**
   ```bash
   php -S localhost:8001
   ```

3. **Test Authentication:**
   ```bash
   curl -X POST 'http://localhost:8001/index.php?entity=auth&action=login' \
     -H 'Content-Type: application/json' \
     -d '{"email": "company@example.com", "password": "password123", "role": "company"}'
   ```

## Documentation

- `../api_documentation.md` - Complete API reference
- `../database.md` - Database documentation
- `../AUTHENTICATION.md` - Authentication system
- `../WORKFLOW_TEST.md` - Complete workflow guide 