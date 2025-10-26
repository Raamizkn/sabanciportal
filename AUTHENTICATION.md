# Authentication & Authorization System

## Overview

The Sabanci Internship Portal now includes a complete authentication and role-based access control (RBAC) system to secure API endpoints.

## Security Features

### ✅ Role-Based Access Control
- **Admin**: Can create company accounts
- **Company**: Can create internships, manage applications
- **Student**: Can apply for internships, confirm offers

### ✅ Authentication Required
- All CREATE/UPDATE/DELETE operations require authentication
- User IDs are automatically retrieved from session
- Prevents unauthorized access to protected endpoints

## API Endpoints

### Authentication

#### Login
```bash
POST /index.php?entity=auth&action=login
Content-Type: application/json

{
  "email": "company@example.com",
  "password": "password123",
  "role": "company"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "ABC Technologies",
    "email": "company@example.com",
    "role": "company"
  }
}
```

#### Logout
```bash
POST /index.php?entity=auth&action=logout
```

#### Check Authentication Status
```bash
GET /index.php?entity=auth&action=check
```

**Response:**
```json
{
  "status": "success",
  "authenticated": true,
  "user": {
    "user_id": 1,
    "user_role": "company",
    "user_data": {...}
  }
}
```

## Protected Endpoints

### Admin Endpoints

#### Create Company
**Before (Insecure):**
```bash
POST /index.php?entity=admin&resource=companies&action=add
# Anyone could create companies
```

**After (Secure):**
```bash
POST /index.php?entity=admin&resource=companies&action=add
# Requires: Admin role
# Session must contain: user_role = 'admin'
```

**Error if unauthorized:**
```json
{
  "error": "Access denied. Required role: admin"
}
```

### Company Endpoints

#### Create Internship
**Before (Insecure):**
```bash
POST /index.php?entity=internships&action=create
{
  "company_id": 1,  # Anyone could specify any company_id
  "position": "...",
  "description": "..."
}
```

**After (Secure):**
```bash
POST /index.php?entity=internships&action=create
# Requires: Company role
# Company ID is automatically retrieved from session
{
  "position": "...",
  "description": "..."
  # No need to specify company_id
}
```

**Before (Insecure):**
```bash
POST /index.php?entity=applications&action=apply
{
  "student_id": 1,  # Anyone could apply as any student
  "internship_id": 2,
  "cover_letter": "..."
}
```

**After (Secure):**
```bash
POST /index.php?entity=applications&action=apply
# Requires: Student role
# Student ID is automatically retrieved from session
{
  "internship_id": 2,
  "cover_letter": "..."
  # No need to specify student_id
}
```

### Application Status Updates

#### Company Updates Application Status
**Before (Insecure):**
```bash
POST /index.php?entity=applications&id=APP001&action=update_status_company
# Anyone could update any application
```

**After (Secure):**
```bash
POST /index.php?entity=applications&id=APP001&action=update_status_company
# Requires: Company role
# Only the company that owns the internship can update applications
```

#### Student Confirms Offer
**Before (Insecure):**
```bash
POST /index.php?entity=applications&id=APP001&action=confirm_offer
# Anyone could confirm any offer
```

**After (Secure):**
```bash
POST /index.php?entity=applications&id=APP001&action=confirm_offer
# Requires: Student role
# Only the student who made the application can confirm it
```

## Implementation Details

### Session Management

Sessions are managed using PHP's built-in `session_start()` and store:
- `user_id`: The authenticated user's ID
- `user_role`: One of 'admin', 'company', or 'student'
- `user_data`: Additional user information

### Password Hashing

Passwords are hashed using PHP's `password_hash()` and verified with `password_verify()`.

### Role Constants

```php
define('ROLE_ADMIN', 'admin');
define('ROLE_COMPANY', 'company');
define('ROLE_STUDENT', 'student');
```

### Helper Functions

```php
// Check if user is logged in
isLoggedIn()

// Get current user ID
getCurrentUserId()

// Get current user role
getCurrentUserRole()

// Check if user has specific role
hasRole($role)

// Require authentication
requireAuth()

// Require specific role
requireRole($role)
```

## Usage Examples

### Frontend Integration

```javascript
// Login before making requests
const loginResponse = await fetch('http://localhost:8001/index.php?entity=auth&action=login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: 'company@example.com',
    password: 'password123',
    role: 'company'
  })
});

// After login, session cookie is automatically sent with requests
const createInternshipResponse = await fetch('http://localhost:8001/index.php?entity=internships&action=create', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    position: 'Software Developer Intern',
    description: 'Join our team...',
    location: 'Istanbul, Turkey'
  })
});
```

### Error Handling

```javascript
try {
  const response = await fetch('...');
  const data = await response.json();
  
  if (response.status === 401) {
    // Not authenticated, redirect to login
    window.location.href = '/login.html';
  } else if (response.status === 403) {
    // Access denied, show error
    alert(data.error);
  }
} catch (error) {
  console.error('Request failed:', error);
}
```

## Testing Scripts

### Test Without Authentication (Should Fail)
```bash
# Try to create internship without login
curl -X POST 'http://localhost:8001/index.php?entity=internships&action=create' \
  -H 'Content-Type: application/json' \
  -d '{"position": "Test", "description": "Test"}'

# Expected: {"error": "Authentication required"}
```

### Test With Authentication (Should Succeed)
```bash
# 1. Login
curl -X POST 'http://localhost:8001/index.php?entity=auth&action=login' \
  -H 'Content-Type: application/json' \
  -d '{"email": "company@example.com", "password": "password", "role": "company"}' \
  -c cookies.txt

# 2. Create internship (use session cookie)
curl -X POST 'http://localhost:8001/index.php?entity=internships&action=create' \
  -H 'Content-Type: application/json' \
  -d '{"position": "Test", "description": "Test"}' \
  -b cookies.txt
```

## Database Setup

For authentication to work, passwords must be hashed in the database:

```sql
-- Update company password
UPDATE companies SET password_hash = '$2y$10$...' WHERE id = 1;

-- Update student password
UPDATE students SET password_hash = '$2y$10$...' WHERE id = 1;

-- Create admin user
INSERT INTO admin_users (username, email, password_hash, full_name) VALUES
('admin', 'admin@example.com', '$2y$10$...', 'Admin User');
```

## Summary

✅ **Complete authentication system implemented**
✅ **Role-based access control enforced**
✅ **User IDs automatically retrieved from session**
✅ **Prevents unauthorized access**
✅ **Secure API endpoints**

The application is now secure and prevents unauthorized access to protected endpoints.

