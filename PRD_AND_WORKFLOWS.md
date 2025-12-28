# Product Requirements Document & Expected Workflows
## Sabancı University Internship Portal

**Last Updated**: December 29, 2025

---

## Product Overview

The Sabancı University Internship Portal connects students with companies for internship opportunities. The system operates on a **term-based model** where academic terms (Fall, Spring, Summer) are the supreme layer that determines what data students and companies see.

### Core Principles

1. **Terms are Supreme**: Terms determine what internships and applications are visible
2. **Term-Based Filtering**: All data is filtered by the currently active term
3. **Academic Semesters Only**: Terms follow academic semesters (Fall, Spring, Summer)
4. **Active/Inactive Terms**: Admins can activate/deactivate terms; inactive terms are view-only
5. **Status Simplification**: "Confirmed" is the final stage (replaces "Finalized")
6. **Auto-Withdrawal**: When a student confirms an offer, all other applications auto-withdraw
7. **Automated Actions**: Internships auto-close after deadline; evaluations auto-open after term end

---

## Database Schema

### Core Tables

**terms**
- `id`, `name`, `start_date`, `end_date`
- `max_applications_per_student` (default: 3)
- `is_active` (BOOLEAN, default: TRUE)
- `default_company_quota` (default: 10)

**internships**
- `id`, `company_id`, `term_id`, `position`, `description`, `location`, `dates`
- `status` (Active, Closed, Deleted)
- `application_deadline` (DATE)
- `evaluation_open_date` (DATE)
- `posted_date`

**applications**
- `id`, `student_id`, `internship_id`, `term_id`
- `status` (Pending, Under Review, Accepted, Rejected, Withdrawn, Confirmed)
- `cover_letter`, `applied_date`
- `offer_details` (TEXT)

**companies**
- `id`, `name`, `email`, `industry`, `website`, `phone`, `address`, `description`
- `logo` (TEXT - base64 data URL)

**students**
- `id`, `name`, `email`, `major`, `gpa`, `phone`, `bio`, `profile_pic`

**documents**
- `id`, `student_id`, `application_id`, `document_type`, `file_name`, `file_content` (LONGBLOB)

---

## User Roles & Permissions

### Admin
- Create/edit/delete terms
- Activate/deactivate terms
- Create companies and students
- View all data across all terms
- Impersonate users for QA

### Company
- View/edit own profile (including logo upload)
- Create internships (assigned to active term)
- View applications for own internships (filtered by active term)
- Accept/reject applications
- View confirmed applications
- See seats/quota (hidden from students)

### Student
- View/edit own profile
- Upload documents (resumes, cover letters)
- Browse internships (filtered by active term)
- Apply to internships (with document selection)
- View own applications (filtered by active term)
- Withdraw applications
- Confirm offers (auto-withdraws others)
- Submit evaluations

---

## Core Workflows

### 1. Term Management (Admin)

**Create Term:**
1. Admin navigates to "Academic Terms"
2. Clicks "Add New Term"
3. Enters: Name (e.g., "Fall 2025"), Start Date, End Date
4. Sets `max_applications_per_student` (default: 3)
5. Sets `is_active` (default: TRUE)
6. System creates term

**Activate/Deactivate Term:**
1. Admin edits term
2. Toggles `is_active` status
3. System immediately filters all views by active term
4. Students/companies see only active term data

**Term Rules:**
- Only one term should be active at a time
- Terms follow academic semesters (Fall, Spring, Summer)
- Inactive terms are view-only (no new applications/internships)

---

### 2. Company Workflow

**Profile Setup:**
1. Company logs in
2. Navigates to "Company Profile"
3. Updates: name, industry, website, phone, address, description
4. Uploads logo (max 2MB, converted to base64)
5. Profile updates immediately reflect in internship cards

**Post Internship:**
1. Company navigates to "My Internships"
2. Clicks "Post New Internship"
3. Fills: position, description, location, dates, requirements, type
4. Sets `application_deadline` (optional, defaults to 1 month after posted_date)
5. System assigns internship to active term automatically
6. Internship appears in student browse page

**Review Applications:**
1. Company navigates to "Applications"
2. Views applications filtered by active term
3. Clicks "View Application" to see student details, cover letter, documents
4. Can download resume and documents
5. Updates status: Accept (with offer_details) or Reject
6. Sees seats/quota information (hidden from students)

**Finalize Placement:**
1. Company accepts application (status: Accepted)
2. Student confirms offer (status: Confirmed)
3. Company can view confirmed applications
4. System tracks quota usage per term

---

### 3. Student Workflow

**Profile Setup:**
1. Student logs in
2. Navigates to "My Profile"
3. Updates: name, major, GPA, phone, bio, profile picture
4. Uploads documents (resumes, cover letters) - stored as BLOB in database

**Browse & Apply:**
1. Student navigates to "Browse Internships"
2. Sees internships filtered by active term only
3. Can filter by: search, location, mode (remote/on-site), duration
4. Clicks internship to view details
5. Clicks "Apply Now"
6. Selects documents from uploaded files
7. Writes cover letter (rich text with XSS protection)
8. Submits application
9. System checks: active term, application limit (max 3 per term), no duplicates
10. Application created with status: Pending

**Application Management:**
1. Student navigates to "My Applications"
2. Sees applications filtered by active term
3. Can filter by status and search
4. Views application details
5. If Accepted: Can confirm offer (auto-withdraws other applications)
6. Can withdraw applications (frees up application slot)

**Application Limits:**
- Max 3 applications per term
- Withdrawn/Rejected applications don't count toward limit
- Confirmed applications count toward limit
- Limits reset per term

---

### 4. Application Status Flow

**Standard Flow:**
```
Pending → Accepted → Confirmed → (Final State)
         ↘ Rejected (terminal)
         ↘ Withdrawn (terminal)
```

**Status Definitions:**
- **Pending**: Initial status when student applies
- **Accepted**: Company accepts application (can include offer_details)
- **Confirmed**: Student confirms acceptance (auto-withdraws other applications)
- **Rejected**: Company rejects application (terminal)
- **Withdrawn**: Student withdraws application (terminal)

**Status Transitions:**
- `Pending` → `Accepted` (company accepts) or `Rejected` (company rejects)
- `Accepted` → `Confirmed` (student confirms) or `Withdrawn` (student withdraws)
- `Confirmed` is final state (no further transitions)

**Auto-Withdrawal:**
- When student confirms an offer, all other `Pending` or `Accepted` applications are automatically set to `Withdrawn`
- This ensures students can only have one confirmed internship per term

---

### 5. Automated Actions

**Auto-Close Internships:**
- Cron job: `backend/cron/auto_close_internships.php`
- Runs daily
- Closes internships where `application_deadline < CURDATE()` and `status = 'Active'`
- Sets status to `Closed`

**Auto-Open Evaluations:**
- Cron job: `backend/cron/auto_open_evaluations.php`
- Runs daily
- Opens evaluations for applications where `evaluation_open_date <= CURDATE()` and `status = 'Confirmed'`
- Sets status to `Evaluation_Open`

---

## Term-Based Filtering Logic

### Active Term Detection

**Backend (`get_active_term()`):**
1. First checks for terms with `is_active = 1` (prioritizes admin activation over dates)
2. If no active term found, checks date range
3. Returns most recent active term

**Frontend:**
- Student/company dashboards fetch active term
- Pass `term_id` to all API calls
- Backend filters all queries by `term_id`

### Data Visibility Rules

**Students:**
- Browse Internships: Only active term internships
- My Applications: Only active term applications
- Application History: All terms (or filterable)
- Application Limits: Per term (resets each term)

**Companies:**
- My Internships: Filtered by active term (or view all)
- Applications: Only for active term internships
- Dashboard Metrics: Only active term data
- Seats/Quota: Per term

**Admins:**
- Can view all data across all terms
- Can switch active term
- Can manage terms

---

## Key Features

### Document Management
- Documents stored as BLOB in database (not filesystem)
- Students upload documents (resumes, cover letters)
- Students select documents when applying
- Companies can download selected documents
- Documents linked to applications via `application_id`

### Company Logo Upload
- Companies can upload logos (max 2MB)
- Converted to base64 data URL
- Stored in `companies.logo` (TEXT column)
- Displays on internship cards

### Seats/Quota Visibility
- Companies see seats/quota information
- Students do NOT see seats/quota (hidden)
- Quota calculated per term using `default_company_quota` from terms table
- Used quota counts `Confirmed` applications

### Application Limits
- Max 3 applications per student per term
- Configurable per term via `max_applications_per_student`
- Withdrawn/Rejected don't count toward limit
- Limits reset per term

---

## Edge Cases & Error Handling

### No Active Term
- Students see: "No active term. Please contact administrator."
- Companies cannot create internships
- Applications cannot be submitted

### Term Switching
- When term switches, all views update immediately
- Historical data remains accessible
- Application limits reset for new term
- Company quotas reset for new term

### Duplicate Applications
- Prevented by database constraint on `(student_id, internship_id)`
- Returns HTTP 409 error: "You have already applied for this internship."

### Application Limit Reached
- Student cannot apply when limit reached
- Error: "You have reached the maximum number of applications (3) for this term."
- Withdrawing an application frees up a slot

### Invalid Status Transitions
- Backend validates all status transitions
- Returns HTTP 400 with allowed transitions
- Frontend displays error message

---

## Security Considerations

### Authentication
- Session-based authentication (HTTP-only cookies)
- Role-based access control (student, company, admin)
- Authorization checks on all endpoints

### Data Protection
- XSS protection for cover letters (`sanitize_cover_letter_html()`)
- Prepared statements for SQL queries
- Password hashing (bcrypt)
- Session security (strict mode, regeneration)

### File Uploads
- File type validation (images for logos, PDFs for documents)
- File size limits (2MB for logos, configurable for documents)
- Files stored as BLOB in database (not filesystem)

---

## Migration Notes

### Removed Features
- **Application Rounds**: Removed entirely, replaced by term-based system
- **Company Round Quotas**: Removed, replaced by term-based quotas
- **Finalized Status**: Removed, "Confirmed" is final state
- **Approved_By_Company Status**: Removed, consolidated to "Confirmed"

### Database Changes
- Dropped tables: `application_rounds`, `company_round_quotas`
- Removed column: `applications.round_id`
- Added columns: `terms.max_applications_per_student`, `terms.is_active`
- Updated ENUM: `applications.status` (removed Finalized, Approved_By_Company)

---

## Testing Scenarios

### Term Switching
1. Admin activates Spring 2026 term
2. Student dashboard shows 0 applications (empty term)
3. Student browse page shows 0 internships
4. Admin switches back to Fall 2025
5. Student dashboard shows Fall 2025 applications
6. All views update correctly

### Application Limits
1. Student applies to 3 internships (max limit)
2. Student cannot apply to 4th internship
3. Student withdraws 1 application
4. Student can now apply to 1 more internship
5. Limits reset when term switches

### Auto-Withdrawal
1. Student has 3 applications (all Accepted)
2. Student confirms 1 application
3. Other 2 applications automatically set to Withdrawn
4. Student now has 1 Confirmed application

### Auto-Close Internships
1. Internship has `application_deadline = 2025-12-31`
2. Date passes (2026-01-01)
3. Cron job runs
4. Internship status changes to Closed
5. Students cannot apply to closed internships

---

**This document is the single source of truth for product requirements and workflows. Update it whenever business logic changes.**

