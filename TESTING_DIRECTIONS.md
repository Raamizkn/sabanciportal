# Testing Directions
## Sabancı University Internship Portal

**Last Updated**: December 29, 2025

---

## Prerequisites

### Setup
1. **Start Backend Server**:
   ```bash
   cd backend
   php -S localhost:8001
   ```

2. **Start Frontend Server**:
   ```bash
   cd internship-portal
   python3 -m http.server 8000
   ```

3. **Database Setup**:
   - Ensure MySQL is running
   - Run migrations: `backend/config/migrate_remove_rounds_make_terms_supreme.sql`
   - Verify database connection in `backend/config/database.php`

### Test Accounts

**Admin**:
- Email: `admin@example.com`
- Password: `password123`

**Student**:
- Email: `student@example.com`
- Password: `password123`

**Company**:
- Email: `company@example.com`
- Password: `password123`

---

## Test Scenarios

### 1. Term Management (Admin)

#### Test: Create Term
1. Login as admin
2. Navigate to "Academic Terms"
3. Click "Add New Term"
4. Enter:
   - Name: "Spring 2026"
   - Start Date: 2026-02-01
   - End Date: 2026-06-15
   - Max Applications: 3
   - Status: Active
5. Click "Save"
6. **Expected**: Term appears in terms table with "Active" status

#### Test: Activate/Deactivate Term
1. Login as admin
2. Navigate to "Academic Terms"
3. Click "Edit" on a term
4. Toggle `is_active` status
5. Click "Update"
6. **Expected**: Term status updates, all views filter by new active term

#### Test: Term Switching
1. Create Spring 2026 term and activate it
2. Login as student
3. **Expected**: Student dashboard shows 0 applications (empty term)
4. Switch back to Fall 2025 (as admin)
5. Refresh student dashboard
6. **Expected**: Student dashboard shows Fall 2025 applications

---

### 2. Student Workflow

#### Test: Browse Internships
1. Login as student
2. Navigate to "Browse Internships"
3. **Expected**: Only internships from active term are shown
4. Test filters: search, location, mode, duration
5. **Expected**: Filters work in combination (AND logic)

#### Test: Apply to Internship
1. Login as student
2. Navigate to "Browse Internships"
3. Click on an internship
4. Click "Apply Now"
5. Select documents from uploaded files
6. Write cover letter
7. Click "Submit"
8. **Expected**: 
   - Application created with status "Pending"
   - Application appears in "My Applications"
   - Application count increases

#### Test: Application Limits
1. Login as student
2. Apply to 3 internships (max limit)
3. Try to apply to 4th internship
4. **Expected**: Error message "You have reached the maximum number of applications (3) for this term."
5. Withdraw 1 application
6. **Expected**: Can now apply to 1 more internship

#### Test: Confirm Offer (Auto-Withdrawal)
1. Login as student
2. Have 3 applications, all with status "Accepted"
3. Confirm 1 application
4. **Expected**: 
   - Confirmed application status changes to "Confirmed"
   - Other 2 applications automatically set to "Withdrawn"
   - Application count shows 1 Confirmed

#### Test: Withdraw Application
1. Login as student
2. Navigate to "My Applications"
3. Click "Withdraw" on a Pending application
4. **Expected**: 
   - Application status changes to "Withdrawn"
   - Application slot is freed (can apply to new internship)

---

### 3. Company Workflow

#### Test: Update Profile
1. Login as company
2. Navigate to "Company Profile"
3. Update: name, industry, website, phone, address, description
4. Upload logo (max 2MB)
5. Click "Save"
6. **Expected**: Profile updates, logo displays correctly

#### Test: Post Internship
1. Login as company
2. Navigate to "My Internships"
3. Click "Post New Internship"
4. Fill: position, description, location, dates, requirements, type
5. Set application deadline (optional)
6. Click "Submit"
7. **Expected**: 
   - Internship created and assigned to active term
   - Internship appears in "My Internships"
   - Internship visible to students in browse page

#### Test: Review Applications
1. Login as company
2. Navigate to "Applications"
3. **Expected**: Only applications for active term internships are shown
4. Click "View Application" on an application
5. **Expected**: 
   - Modal shows student details, cover letter, documents
   - Can download resume and documents
   - Seats/quota information visible (hidden from students)

#### Test: Accept Application
1. Login as company
2. Navigate to "Applications"
3. Click "View Application"
4. Click "Accept"
5. Enter offer details (optional)
6. Click "Confirm"
7. **Expected**: 
   - Application status changes to "Accepted"
   - Student can see offer details and confirm

#### Test: Reject Application
1. Login as company
2. Navigate to "Applications"
3. Click "View Application"
4. Click "Reject"
5. Click "Confirm"
6. **Expected**: 
   - Application status changes to "Rejected"
   - Student's application slot is freed

---

### 4. Term-Based Filtering

#### Test: Empty Term Scenario
1. Activate Spring 2026 term (no internships/applications)
2. Login as student
3. **Expected**: 
   - Dashboard shows 0 applications
   - Browse page shows empty state
   - Application limits show 3/3 available
4. Login as company
5. **Expected**: 
   - Dashboard shows 0 internships, 0 applications
   - Can create new internships (assigned to Spring 2026)

#### Test: Term Switching Data Visibility
1. Have data in Fall 2025 term
2. Switch to Spring 2026 (empty)
3. **Expected**: All views show empty/zero
4. Switch back to Fall 2025
5. **Expected**: All Fall 2025 data reappears

---

### 5. Automated Actions

#### Test: Auto-Close Internships
1. Create internship with `application_deadline = yesterday`
2. Run cron job: `php backend/cron/auto_close_internships.php`
3. **Expected**: Internship status changes to "Closed"
4. Students cannot apply to closed internships

#### Test: Auto-Open Evaluations
1. Have confirmed application with `evaluation_open_date = today`
2. Run cron job: `php backend/cron/auto_open_evaluations.php`
3. **Expected**: Application status changes to "Evaluation_Open"

---

### 6. Edge Cases

#### Test: Duplicate Application Prevention
1. Login as student
2. Apply to an internship
3. Try to apply to the same internship again
4. **Expected**: Error "You have already applied for this internship."

#### Test: No Active Term
1. Deactivate all terms (as admin)
2. Login as student
3. **Expected**: Appropriate message shown, cannot apply
4. Login as company
5. **Expected**: Cannot create internships

#### Test: Seed Internships Fix
1. Login as company that hasn't created internships
2. Navigate to "My Internships"
3. **Expected**: Shows empty state, not seed internships
4. Create an internship
5. **Expected**: Only own internship appears

---

## Backend Testing

### Test Scripts

**Application Limits**:
```bash
php backend/config/test_remaining_scenarios.php
```

**Verify Setup**:
```bash
php backend/config/verify_setup.php
```

### Database Checks

**Check Active Term**:
```sql
SELECT * FROM terms WHERE is_active = 1;
```

**Check Application Limits**:
```sql
SELECT 
  s.id, 
  s.name, 
  COUNT(a.id) as active_applications,
  t.max_applications_per_student
FROM students s
JOIN terms t ON t.is_active = 1
LEFT JOIN applications a ON a.student_id = s.id 
  AND a.term_id = t.id 
  AND a.status NOT IN ('Rejected', 'Withdrawn', 'Confirmed')
GROUP BY s.id, t.max_applications_per_student;
```

**Check Company Quota**:
```sql
SELECT 
  c.id,
  c.name,
  COUNT(a.id) as confirmed_applications,
  t.default_company_quota
FROM companies c
JOIN terms t ON t.is_active = 1
LEFT JOIN applications a ON a.company_id = c.id 
  AND a.term_id = t.id 
  AND a.status = 'Confirmed'
GROUP BY c.id, t.default_company_quota;
```

---

## API Testing

### Using cURL

**Login**:
```bash
curl -X POST 'http://localhost:8001/index.php?entity=auth&action=login' \
  -H 'Content-Type: application/json' \
  -c cookies.txt \
  -d '{"email":"student@example.com","password":"password123","role":"student"}'
```

**Get Applications**:
```bash
curl -b cookies.txt 'http://localhost:8001/index.php?entity=applications&student_id=1&term_id=4'
```

**Apply to Internship**:
```bash
curl -X POST 'http://localhost:8001/index.php?entity=applications&action=apply' \
  -b cookies.txt \
  -H 'Content-Type: application/json' \
  -d '{
    "internship_id": 15,
    "cover_letter": "I am excited to apply...",
    "document_ids": ["DOC123456"]
  }'
```

### Using Postman

Import collections:
- `Admin_API.postman_collection.json`
- `Company_API.postman_collection.json`
- `Student_API.postman_collection.json`

---

## Browser Testing

### Chrome DevTools

**Check API Calls**:
1. Open DevTools (F12)
2. Go to Network tab
3. Filter by "XHR"
4. Check request/response for each API call

**Check Console Errors**:
1. Open DevTools (F12)
2. Go to Console tab
3. Look for JavaScript errors

**Check Application State**:
1. Open DevTools (F12)
2. Go to Application tab
3. Check Local Storage / Session Storage
4. Check Cookies

### Test Checklist

- [ ] Login works for all roles
- [ ] Dashboards load correctly
- [ ] Term filtering works
- [ ] Application limits enforced
- [ ] Status transitions work
- [ ] Auto-withdrawal works
- [ ] File uploads work
- [ ] Modals display correctly
- [ ] Error messages display correctly
- [ ] Empty states display correctly

---

## Regression Testing

### Critical Paths

1. **Student Application Flow**:
   - Browse → Apply → Withdraw → Apply Again → Confirm

2. **Company Review Flow**:
   - Post Internship → Review Applications → Accept → Student Confirms

3. **Term Switching**:
   - Switch Active Term → Verify Views Update → Switch Back → Verify Data Returns

4. **Application Limits**:
   - Apply to Max → Try to Apply More → Withdraw → Apply Again

---

## Performance Testing

### Load Testing

**Test with Multiple Users**:
1. Open multiple browser windows
2. Login as different users
3. Perform actions simultaneously
4. **Expected**: No conflicts, correct data isolation

**Test with Large Datasets**:
1. Create 100+ internships
2. Create 100+ applications
3. Test filtering and pagination
4. **Expected**: Performance remains acceptable

---

## Security Testing

### Authorization Tests

1. **Cross-User Access**:
   - Login as Student A
   - Try to access Student B's applications
   - **Expected**: 403 Forbidden

2. **Role-Based Access**:
   - Login as Student
   - Try to access admin endpoints
   - **Expected**: 403 Forbidden

3. **Session Management**:
   - Login and get session cookie
   - Logout
   - Try to use old session cookie
   - **Expected**: 401 Unauthorized

---

## Reporting Issues

When reporting test failures, include:

1. **Test Scenario**: Which test failed
2. **Steps to Reproduce**: Exact steps taken
3. **Expected Behavior**: What should happen
4. **Actual Behavior**: What actually happened
5. **Browser/Environment**: Browser version, OS, server logs
6. **Screenshots**: If applicable
7. **Console Errors**: JavaScript errors from DevTools
8. **Network Errors**: Failed API calls from Network tab

---

**This document provides testing directions. For test results, see `TEST_RESULTS.md`.**

