# Complete Application Workflow Test

This document demonstrates the complete workflow from Admin creating a company account through to Company finalizing an internship.

## Prerequisites

1. Backend server running: `php -S localhost:8001` in `backend/` directory
2. Database set up with sample data (run `php config/create_tables.php`)
3. Frontend API service loaded (`internship-portal/js/api.js`)

## Complete Workflow Steps

### Step 1: Admin Creates Company Account

**API Call:**
```bash
curl -X POST 'http://localhost:8001/index.php?entity=admin&resource=companies&action=add' \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "Tech Startup Inc",
    "email": "techstartup@example.com",
    "industry": "Technology",
    "website": "https://techstartup.com",
    "phone": "555-0100",
    "address": "123 Tech Street, Istanbul",
    "description": "A fast-growing technology startup"
  }'
```

**Expected Response:**
```json
{
  "status": "success",
  "message": "Company added successfully.",
  "data": {
    "id": 4,
    "name": "Tech Startup Inc",
    "email": "techstartup@example.com",
    ...
  }
}
```

**Result:** New company with ID 4 created in database ✓

---

### Step 2: Company Creates Internship Posting

**API Call:**
```bash
curl -X POST 'http://localhost:8001/index.php?entity=internships&action=create' \
  -H 'Content-Type: application/json' \
  -d '{
    "company_id": 4,
    "position": "Software Developer Intern",
    "description": "We are looking for a motivated software developer intern to join our team.",
    "location": "Istanbul, Turkey",
    "dates": "June 2025 - August 2025",
    "requirements": "PHP, JavaScript, HTML, CSS experience",
    "salary": "5000 TL/month",
    "type": "Full-time",
    "application_deadline": "2025-05-31"
  }'
```

**Expected Response:**
```json
{
  "id": 4,
  "company_id": 4,
  "company_name": "Tech Startup Inc",
  "title": "Software Developer Intern",
  "position": "Software Developer Intern",
  "description": "We are looking for a motivated software developer intern...",
  "status": "Active",
  ...
}
```

**Result:** New internship created and persisted in database ✓

---

### Step 3: Student Browses Available Internships

**API Call:**
```bash
curl 'http://localhost:8001/index.php?entity=internships'
```

**Expected Response:**
```json
[
  {
    "id": 1,
    "company_name": "ABC Technologies",
    "title": "Software Engineer Intern",
    ...
  },
  {
    "id": 2,
    "company_name": "Global Innovations",
    "title": "Data Analyst Intern",
    ...
  },
  {
    "id": 3,
    "company_name": "Tech Solutions Inc.",
    "title": "Frontend Developer Intern",
    ...
  },
  {
    "id": 4,
    "company_name": "Tech Startup Inc",
    "title": "Software Developer Intern",
    ...
  }
]
```

**Result:** Student can see all active internships including the new one ✓

---

### Step 4: Student Applies for Internship

**API Call:**
```bash
curl -X POST 'http://localhost:8001/index.php?entity=applications&action=apply' \
  -H 'Content-Type: application/json' \
  -d '{
    "student_id": 3,
    "internship_id": 4,
    "cover_letter": "I am very interested in this position and would like to contribute to your team."
  }'
```

**Expected Response:**
```json
{
  "id": "APP104",
  "student_id": 3,
  "internship_id": 4,
  "company_name": "Tech Startup Inc",
  "position": "Software Developer Intern",
  "status": "Pending",
  "applied_date": "2025-10-26",
  "cover_letter": "I am very interested in this position..."
}
```

**Result:** Application created and persisted in database with status "Pending" ✓

---

### Step 5: Company Reviews Applications

**API Call:**
```bash
curl 'http://localhost:8001/index.php?entity=applications&company_id=4'
```

**Expected Response:**
```json
[
  {
    "id": 4,
    "application_id": "APP104",
    "student_id": 3,
    "internship_id": 4,
    "status": "Pending",
    "cover_letter": "I am very interested in this position...",
    ...
  }
]
```

**Result:** Company can see all applications for their internships ✓

---

### Step 6: Company Accepts Application (Offers Position)

**API Call:**
```bash
curl -X POST 'http://localhost:8001/index.php?entity=applications&id=APP104&action=update_status_company' \
  -H 'Content-Type: application/json' \
  -d '{
    "status": "Offered",
    "offer_details": "Congratulations! We are pleased to offer you this position. Start date: June 1, 2025. Salary: 5000 TL/month."
  }'
```

**Expected Response:**
```json
{
  "message": "Application APP104 status updated to Offered by company.",
  "application": {
    "id": 4,
    "application_id": "APP104",
    "status": "Offered",
    "offer_details": "Congratulations! We are pleased to offer you this position...",
    ...
  }
}
```

**Result:** Application status updated to "Offered" in database ✓

---

### Step 7: Student Confirms Offer

**API Call:**
```bash
curl -X POST 'http://localhost:8001/index.php?entity=applications&id=APP104&action=confirm_offer'
```

**Expected Response:**
```json
{
  "message": "Application APP104 offer confirmed successfully by student.",
  "application": {
    "id": 4,
    "application_id": "APP104",
    "status": "Confirmed_By_Student",
    ...
  }
}
```

**Result:** Application status updated to "Confirmed_By_Student" in database ✓

---

### Step 8: Student Views Updated Application Status

**API Call:**
```bash
curl 'http://localhost:8001/index.php?entity=applications&student_id=3'
```

**Expected Response:**
```json
[
  {
    "id": 4,
    "application_id": "APP104",
    "student_id": 3,
    "internship_id": 4,
    "status": "Confirmed_By_Student",
    "offer_details": "Congratulations! We are pleased to offer you this position...",
    ...
  }
]
```

**Result:** Student can see their application is confirmed ✓

---

### Step 9: Company Views Finalized Applications

**API Call:**
```bash
curl 'http://localhost:8001/index.php?entity=applications&company_id=4'
```

**Expected Response:**
```json
[
  {
    "id": 4,
    "application_id": "APP104",
    "student_id": 3,
    "internship_id": 4,
    "status": "Confirmed_By_Student",
    ...
  }
]
```

**Result:** Company can see confirmed applications ✓

---

## Application Status Flow

```
Pending
  ↓
Under Review
  ↓
Shortlisted
  ↓
Interview Scheduled
  ↓
Offered ← Student confirms →
  ↓                           ↓
Approved_By_Company    Confirmed_By_Student ← Finalized
  ↓                           ↓
Rejected_By_Company    Withdrawn
```

## Frontend Integration

To integrate this workflow in the frontend:

1. **Include API Service:** Add `api.js` to your HTML pages
```html
<script src="../js/api.js"></script>
```

2. **Use API Methods:** Call API methods from your JavaScript
```javascript
// Admin creates company
const company = await api.createCompany({
  name: "Tech Startup Inc",
  email: "techstartup@example.com",
  industry: "Technology"
});

// Company creates internship
const internship = await api.createInternship(company.data.id, {
  position: "Software Developer Intern",
  description: "...",
  location: "Istanbul, Turkey"
});

// Student applies
const application = await api.applyForInternship(3, internship.id, "Cover letter...");

// Company offers
await api.updateApplicationStatus("APP104", "Offered", "Offer details...");

// Student confirms
await api.confirmOffer("APP104");
```

## Testing Checklist

- [x] Admin can create company accounts
- [x] Companies can create internship postings
- [x] Students can browse internships
- [x] Students can apply for internships
- [x] Companies can view applications
- [x] Companies can offer positions
- [x] Students can confirm offers
- [x] All changes persist in database
- [x] Status transitions work correctly

## Database Verification

After running the workflow, verify in database:

```sql
-- Check companies
SELECT * FROM companies WHERE id = 4;

-- Check internships  
SELECT * FROM internships WHERE company_id = 4;

-- Check applications
SELECT * FROM applications WHERE internship_id = 4;

-- Check application status changes
SELECT application_id, status, status_updated_date FROM applications WHERE application_id = 'APP104';
```

## Next Steps

1. Integrate frontend pages with API service
2. Add authentication to determine company_id/student_id automatically
3. Add error handling and user feedback
4. Add email notifications for status changes
5. Add ability for company to finalize/complete internships

