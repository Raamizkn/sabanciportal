# Project Learnings and Best Practices

This document outlines key learnings from development and debugging sessions.

## 1. The Document Root is Critical

The primary issue was a mismatch between the web server's document root and the file paths used in the HTML.

- **Problem**: The server was initially started in the project's root directory (`sabanciportal-1/`), but the HTML files used absolute paths like `/assets/css/style.css`. These paths assumed the server's root was the `internship-portal/` directory.
- **Resolution**: The server must be started from the directory that the application's paths are relative to. In this case, running the server from within the `internship-portal/` directory solved the issue.
- **Lesson**: When assets fail to load (404 errors), always verify that the server's document root is correctly configured to match the expectations of the application's file paths.

## 2. Pay Attention to User Context

The user's feedback was a crucial turning point in the debugging process.

- **Hint**: The user mentioned, "you started it differently before on just 8000 and using python."
- **Insight**: This indicated that a simpler solution had worked in the past and that my more complex approach (using PHP from the root) was likely incorrect. It prompted a re-evaluation of the server configuration.
- **Lesson**: User feedback and historical context are valuable sources of information that can significantly speed up troubleshooting.

## 3. Process Management

It's important to manage server processes effectively to avoid conflicts.

- **Problem**: Running a new server on the same port as an existing one will fail.
- **Resolution**: Ensure any existing server process is stopped (`kill <PID>`) before starting a new one on the same port. Using a different port (e.g., 8080) is also a quick way to rule out port conflicts.
- **Lesson**: Always clean up old processes to ensure a clean environment for new commands.

---

## 4. Session Cookie Configuration for Cross-Directory Authentication (October 27, 2025)

### Problem
Authentication worked on localhost but failed on the server (`pro2-dev.sabanciuniv.edu`). Users saw "Authentication required" dialog even after logging in.

### Root Cause
PHP session cookies were configured with default path settings that didn't work across subdirectories:
- Frontend path: `/shadowing/internship-portal/`
- Backend path: `/shadowing/backend/`
- Cookies set on `/shadowing/internship-portal/` weren't sent to `/shadowing/backend/`

### Solution
Modified `backend/auth/auth.php` to configure session cookies properly:

```php
// Configure session cookies to work across subdirectories
if (session_status() === PHP_SESSION_NONE) {
    // Set cookie path to root so cookies work across all subdirectories
    ini_set('session.cookie_path', '/');
    // Use lax SameSite for better compatibility
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}
```

### Key Learnings
- **Cookie Path**: Setting `session.cookie_path` to `/` allows cookies to be shared across all subdirectories on the same domain
- **SameSite Attribute**: Using `Lax` provides security while allowing cookies on top-level navigations
- **Session Status Check**: Always check `session_status() === PHP_SESSION_NONE` before configuring to avoid double-session starts

---

## 5. Dynamic API URL Detection for Multi-Environment Deployment (October 27, 2025)

### Problem
Frontend pages had hardcoded `http://localhost:8001` URLs which failed on the server environment.

### Root Cause
Hardcoded URLs in HTML/JavaScript don't adapt to different environments:
- Local development: `http://localhost:8001`
- Server production: `http://pro2-dev.sabanciuniv.edu/shadowing/backend`

### Solution
Created a centralized API service in `internship-portal/js/api.js` with environment detection:

```javascript
function getAPIBaseURL() {
    const hostname = window.location.hostname;
    const protocol = window.location.protocol;
    
    // If running on localhost, use localhost:8001
    if (hostname === 'localhost' || hostname === '127.0.0.1') {
        return 'http://localhost:8001';
    }
    
    // On server, use relative path to backend
    const pathname = window.location.pathname;
    if (pathname.includes('/shadowing/internship-portal') || pathname.includes('/shadowing/backend')) {
        return `${protocol}//${hostname}/shadowing/backend`;
    }
    
    // Fallback: try to find backend in parent directory
    return `${protocol}//${hostname}/backend`;
}
```

### Files Fixed
Replaced hardcoded fetch calls with API service in:
- `admin/admin-add-company.html`
- `student/student-internships.html`
- `company/company-internships.html`

### Key Learnings
- **Centralized API**: One API service class handles all HTTP requests
- **Environment Detection**: Automatically detects environment based on hostname and pathname
- **Credentials Included**: Always set `credentials: 'include'` for CORS with cookies
- **Consistent Error Handling**: Centralized error handling provides better debugging

---

## 6. JavaScript Dependency Loading Order (October 27, 2025)

### Problem
DataTables library errors: `Uncaught ReferenceError: jQuery is not defined`

### Root Cause
jQuery must be loaded before DataTables, but some pages loaded Bootstrap before jQuery:

```html
<!-- WRONG ORDER -->
<script src="bootstrap.bundle.min.js"></script>
<script src="datatables.min.js"></script>
```

### Solution
Fixed script loading order in all affected pages:

```html
<!-- CORRECT ORDER -->
<script src="jquery/jquery.min.js"></script>
<script src="bootstrap/bootstrap.bundle.min.js"></script>
<script src="vendor/tables/datatables/datatables.min.js"></script>
```

### Files Fixed
- `admin/admin-companies.html`
- `admin/admin-add-company.html`
- `admin/admin-dashboard.html`
- `admin/admin-students.html`
- `admin/admin-applications.html`
- `admin/admin-internships.html`
- `company/company-internships.html`

### Key Learnings
- **Always check dependencies**: Libraries often depend on others
- **jQuery first**: jQuery is a dependency for Bootstrap JavaScript components and DataTables
- **Test after adding**: When adding new libraries, verify loading order
- **Console errors are helpful**: Browser errors clearly indicate missing dependencies

---

## 7. API Response Handling Consistency (October 27, 2025)

### Problem
Creating internships showed "Failed to post internship" error even though the internship was successfully created.

### Root Cause
Frontend expected a specific response format:
```javascript
if (data.status === 'success') {
    // Show success
}
```

But backend returned the created object directly:
```php
http_response_code(201);
echo json_encode($new_internship); // Returns {id: 123, position: "...", ...}
```

### Solution
Updated frontend to check for actual data fields instead of a status field:

```javascript
// Check if response contains expected data (like id field)
if (data && data.id) {
    alert('Internship posted successfully!');
    location.reload();
} else {
    alert(data.error || 'Failed to post internship.');
}
```

### Key Learnings
- **Know your API**: Always check backend response format before writing frontend code
- **HTTP Status Codes**: 201 means success, don't need extra status field
- **Check actual data**: Instead of checking for a "status" field, check for expected data fields
- **Backend-first documentation**: Document API responses before frontend implementation

---

## 8. Form Input Handling Improvements (October 27, 2025)

### Problem
Login form had hardcoded user type detection logic that failed for new companies with email addresses that didn't match patterns.

### Root Cause
Email-based user type inference was too restrictive:
```javascript
if (email.includes('admin')) userType = 'admin';
else if (email.includes('company') || email.includes('super')) userType = 'company';
else if (email.includes('student')) userType = 'student';
else alert('Please select a user type from the dropdown.');
```

### Solution
Two improvements:
1. Captured the user type dropdown value from the main form (not just modal)
2. Added fallback to try company role by default:

```javascript
const mainUserTypeSelect = mainLoginForm.querySelector('select');
handleLogin(email, password, mainUserTypeSelect ? mainUserTypeSelect.value : '');

// In handleLogin function:
if (!userType) {
    // Try to infer from email...
    if (/* patterns */) { userType = '...'; }
    else {
        // Default to trying company role if no pattern matches
        userType = 'company';
    }
}
```

### Key Learnings
- **Capture all inputs**: Make sure form inputs are properly captured
- **Graceful fallbacks**: Instead of blocking users, provide sensible defaults
- **Backend validation**: Let the backend reject wrong credentials rather than frontend blocking

---

## Summary of Deployment Checklist

When deploying to production server (`pro2-dev.sabanciuniv.edu`):

1. ✅ Deploy backend files (especially `auth/auth.php` for session cookies)
2. ✅ Deploy frontend files (especially `js/api.js` for URL detection)
3. ✅ Update all HTML pages that had hardcoded URLs
4. ✅ Fix jQuery loading order in all DataTables pages
5. ✅ Clear browser cache after deployment (Cmd+Shift+R)
6. ✅ Test login flow and API calls
7. ✅ Verify CORS and credentials settings

### Files Requiring Deployment

**Backend:**
- `backend/auth/auth.php` - Session cookie configuration

**Frontend:**
- `internship-portal/js/api.js` - API service with URL detection
- `internship-portal/index.html` - Login form improvements
- `internship-portal/admin/*.html` - jQuery loading fixes
- `internship-portal/company/company-internships.html` - API integration + jQuery fix
- `internship-portal/student/student-internships.html` - API integration

**Date**: October 27, 2025

## 9. Document Attachments & Status Normalization (November 7, 2025)

### Problem
Companies were receiving the wrong resumes (default profile CV) even when students picked other documents, and status labels differed between APIs (`Pending` vs `Pending Review`).

### Solution
- Application submission now runs in a DB transaction, generating a unique `application_id` and updating each selected `document_id` with the new primary key so company views always surface the chosen files.
- The backend normalizes historical statuses on every response, so old `Pending` rows show up as `Pending Review`, and downstream UIs can rely on a single workflow vocabulary.
- `offer_details` accompanies every application payload, allowing both company and student modals to show the same offer memo.

### Key Learnings
- **Link uploads immediately**: Attach documents to the new application row as soon as it exists to avoid mismatched resumes.
- **Normalize at the source**: Fix data once in the API instead of sprinkling conversions throughout the frontend.
- **Surface offer context everywhere**: Reusing the same `offer_details` message keeps company + student dashboards aligned.

---

## 10. Graceful DataTables Initialization (November 7, 2025)

### Problem
The Company Applications grid crashed on slower devices because we injected rows before DataTables finished initializing, resulting in `_DT_CellIndex` errors and empty screens.

### Solution
- Added a readiness check that retries for ~3 seconds until `window.applicationsTable` is available; if it never appears we fall back to a plain `<table>` render so recruiters still see the data.
- Cached the latest application payloads on `window.companyApplicationsCache` to keep modals, document download buttons, and status updates in sync with the table.

### Key Learnings
- **Retry with a timeout**: A short polling loop is safer than assuming every vendor script loads instantly.
- **Share one data source**: Modal handlers should read from a central object rather than scraping DOM fragments.

---

## 11. Documentation Consolidation (November 7, 2025)

We now maintain only four living docs: `README.md`, `api_documentation.md`, `learnings.md`, and `COMPLETE_WORKFLOW_SUMMARY.md`. Everything else was removed to prevent drift.

### Key Learnings
- **One doc per theme** keeps responsibilities clear (API reference vs. workflow vs. lessons learned).
- **README as the index** directs contributors to the other three docs, eliminating scavenger hunts for the latest info.

---

## 12. Student Dashboard UI Improvements & Filter Implementation (December 2025)

### Problem
- Account settings page was accessible but not needed
- Filters on student pages (internships, applications) were not functional
- Document upload modal had unnecessary fields and wasn't working
- Documents were stored in filesystem instead of database

### Solution

**UI Cleanup:**
- Removed "Account settings" links from all student-facing pages (navbar dropdowns and sidebars)
- Removed "Pending Uploads" section from documents page
- Simplified document upload modal: removed document type dropdown and description field

**Filter Implementation:**
- Created modern filter UI for Browse Internships page:
  - Horizontal filter bar with search, location, mode, duration filters
  - Real-time filtering with debouncing (300ms)
  - Clear filters button
  - Filters work in combination (AND logic)
- Updated Applications page filters:
  - Combined search input and status dropdown
  - Filters work together seamlessly

**Document Management:**
- Fixed document upload to use FormData (actual file upload, not just metadata)
- Added delete functionality with confirmation dialog
- Fixed download endpoint to serve files correctly (moved from POST to GET section)
- Changed storage from filesystem to database BLOB storage

**API Fixes:**
- Fixed `upload_doc` action handler to accept FormData file uploads
- Fixed `download_doc` action handler routing (moved to GET section)
- Fixed API endpoint mismatch (`get_docs` vs `documents`)
- Added proper error handling for file uploads
- Fixed PHP output buffering issues causing HTML errors in JSON responses

**Database Storage Migration:**
- Added `file_content` LONGBLOB column to documents table
- Updated upload function to store files in database instead of filesystem
- Updated download function to serve from database BLOB with filesystem fallback
- Created migration script: `backend/config/ALTER_documents_add_file_content.sql`

### Key Learnings
- **FormData handling**: When sending files via FormData, don't manually set Content-Type header - browser sets it with boundary automatically
- **PHP upload limits**: Check `upload_max_filesize` (default 2MB) - can't always increase programmatically if PHP is restricted
- **ENUM validation**: Database ENUM columns must match exact values - "General" not allowed, use "Other"
- **Output buffering**: Use `ob_start()` and `ob_end_clean()` to prevent PHP warnings from corrupting JSON responses
- **Function scope**: JavaScript variables declared inside event listeners aren't accessible to functions outside - move to global scope
- **Download endpoints**: File downloads should be GET requests, not POST - set proper headers and use `readfile()` with `exit`
- **Database vs Filesystem**: Storing files as BLOB in database simplifies deployment (no file permissions) but increases database size

### Files Modified
- `internship-portal/student/student-*.html` - Removed account settings links
- `internship-portal/student/student-internships.html` - New filter UI and logic
- `internship-portal/student/student-applications.html` - Updated filter UI
- `internship-portal/student/student-documents.html` - Simplified upload modal, removed pending section
- `internship-portal/js/student-applications.js` - Filter and search functionality
- `internship-portal/js/student-documents.js` - Upload, delete, download functionality
- `internship-portal/js/api.js` - Added upload/delete document methods, fixed FormData handling
- `backend/handlers/student_handler.php` - File upload/download handlers, database storage
- `backend/index.php` - Output buffering for clean JSON responses
- `backend/config/ALTER_documents_add_file_content.sql` - Database migration

---

## 13. Bootstrap Modal Display & Backdrop Cleanup (December 2025)

### Problem
- Application detail modals were not populating with data
- Dropdown menus inside modals were being clipped by `table-responsive` wrapper
- Page became unresponsive after closing modals (couldn't click anything until refresh)

### Root Cause
- Modal content was being rendered statically in HTML instead of dynamically via JavaScript
- `table-responsive` wrapper created overflow constraints that clipped dropdown menus
- Bootstrap modal backdrop elements were not being cleaned up properly, leaving multiple `.modal-backdrop` divs and preventing interactions

### Solution

**Modal Content Population:**
- Created `renderApplicationModal()` function to dynamically populate modal content from API data
- Used event delegation on "View Application" links to ensure modal populates before showing
- Removed `table-responsive` wrapper and used `card-body` with `p-0` for proper spacing

**Backdrop Cleanup:**
- Added `hidden.bs.modal` event listener to clean up lingering backdrop elements
- Removed all `.modal-backdrop` elements and reset `body` classes/styles (`modal-open`, `overflow`, `paddingRight`)
- Ensured proper modal lifecycle management

**Action Buttons:**
- Fixed Accept/Reject/Finalize buttons by calling `setupApplicationActionButtons()` from `renderApplicationModal()`
- Replaced native `confirm()` and `alert()` dialogs with Bootstrap modals (`showConfirmModal()`, `showAlertModal()`) for consistent UI

### Key Learnings
- **Dynamic Modal Content**: Always populate modal content via JavaScript before showing, don't rely on static HTML
- **Bootstrap Modal Lifecycle**: Always clean up backdrop elements on `hidden.bs.modal` event to prevent UI lockups
- **Event Delegation**: Use event delegation for dynamically created elements (like "View Application" links)
- **Consistent UI**: Replace native browser dialogs with Bootstrap modals for better UX
- **Overflow Handling**: Avoid `table-responsive` wrappers around dropdown menus - use proper padding/margin instead

### Files Modified
- `internship-portal/company/company-applications.html` - Modal structure, backdrop cleanup, event delegation
- `internship-portal/js/company-applications.js` - `renderApplicationModal()`, `setupApplicationActionButtons()`, modal utilities
- `internship-portal/js/modal-utils.js` - Bootstrap modal confirmation/alert utilities

---

## 14. Application Status Standardization (December 2025)

### Problem
- Status names were inconsistent across frontend, backend, and database
- Frontend used display names like `Pending Review`, `Approved_By_Company`, `Confirmed_By_Student`
- Database ENUM contained verbose values that didn't match user-facing labels
- Multiple status normalization functions scattered across codebase

### Root Cause
- Database ENUM was created with verbose status names (`Confirmed_By_Student`, `Approved_By_Company`)
- Frontend code had inconsistent normalization logic
- No single source of truth for status names

### Solution

**Standardized to 6 Core Statuses:**
1. `Pending` - Initial status when student applies
2. `Accepted` - Company accepts the application
3. `Confirmed` - Student confirms acceptance (database: `Confirmed_By_Student`)
4. `Finalized` - Company finalizes the placement (database: `Approved_By_Company`)
5. `Rejected` - Company rejects the application
6. `Withdrawn` - Student withdraws the application

**Backend Normalization:**
- Updated `normalize_status()` in `applications_handler.php` to map database values to clean display names
- Database continues to store `Confirmed_By_Student` and `Approved_By_Company` (can't ALTER ENUM without DBA privileges)
- All API responses normalize statuses before returning to frontend

**Frontend Updates:**
- Removed all status normalization functions from frontend JavaScript
- Frontend now uses clean status names directly (`Pending`, `Accepted`, `Confirmed`, `Finalized`, `Rejected`, `Withdrawn`)
- Updated all status badge rendering, conditional logic, and filter dropdowns to use clean names

**Status Workflow:**
- `Pending` → `Accepted` (company accepts) or `Rejected` (company rejects)
- `Accepted` → `Confirmed` (student confirms) or `Withdrawn` (student withdraws)
- `Confirmed` → `Finalized` (company finalizes)
- `Finalized`, `Rejected`, `Withdrawn` are terminal states

### Key Learnings
- **Database Constraints**: When ALTER privileges aren't available, normalize at the API layer instead of schema changes
- **Single Source of Truth**: Backend normalization ensures frontend always receives consistent status names
- **Clean Display Names**: Use user-friendly status names (`Confirmed` not `Confirmed_By_Student`) for better UX
- **Status Transitions**: Document valid status transitions and enforce them in backend logic
- **Backward Compatibility**: Normalization function handles legacy database values gracefully

### Files Modified
- `backend/handlers/applications_handler.php` - Status normalization, workflow logic
- `backend/handlers/admin_handler.php` - Status queries updated
- `backend/handlers/student_handler.php` - Status checks updated
- `backend/handlers/documents_handler.php` - Status checks updated
- `internship-portal/js/company-applications.js` - Removed normalization, use clean names
- `internship-portal/js/student-applications.js` - Removed normalization, use clean names
- `internship-portal/js/company-dashboard.js` - Updated status constants
- `internship-portal/js/company-finalized.js` - Updated status filtering
- `internship-portal/student/student-*.html` - Updated status card labels and filters
- `internship-portal/admin/admin-*.html` - Updated status badge rendering

---

## 15. UI Cleanup & Navigation Structure (December 2025)

### Problem
- Account Settings page was accessible but not needed for students
- Navigation structure had broken HTML after script-based cleanup (missing `<li class="nav-item">` wrappers)
- Filter and search functionality was not working on student pages

### Solution

**Removed Account Settings:**
- Deleted `student-settings.html` and `company-settings.html` files
- Removed all "Account Settings" links from student-facing pages (navbar dropdowns and sidebars)
- Cleaned up empty `<a>` tags and duplicate closing tags

**Fixed Navigation Structure:**
- Restored proper `<li class="nav-item">` wrappers for all navigation items
- Ensured proper HTML structure for Bootstrap navigation components

**Filter & Search Implementation:**
- Made status cards dynamic on student dashboard and applications pages
- Implemented filter dropdowns and keyword search for applications table
- Integrated DataTables.js custom filtering for real-time results
- Filters work in combination (AND logic) - status + keyword search together

### Key Learnings
- **HTML Structure**: Always maintain proper HTML structure when doing bulk replacements - scripts can break markup
- **Dynamic Content**: Status cards and badges should fetch counts from API, not be hardcoded
- **DataTables Filtering**: Use DataTables.js custom filtering API for advanced search/filter combinations
- **UI Consistency**: Remove unused pages and menu items to reduce confusion

### Files Modified
- `internship-portal/student/student-*.html` - Removed account settings links, fixed navigation structure
- `internship-portal/student/student-dashboard.html` - Dynamic status cards, removed "Upcoming Deadlines"
- `internship-portal/student/student-applications.html` - Dynamic status cards, filter/search functionality
- `internship-portal/js/student-applications.js` - Filter and search implementation
- Deleted: `internship-portal/student/student-settings.html`, `internship-portal/company/company-settings.html`

---
