# API Documentation
## Sabancı University Internship Portal

**Last Updated**: December 29, 2025  
**Base URL (local dev)**: `http://localhost:8001/index.php`  
**Base URL (production)**: `https://pro2-dev.sabanciuniv.edu/shadowing/backend/index.php`

---

## Authentication

All endpoints require session-based authentication. Login first to establish a session cookie.

### Login

**Endpoint**: `POST ?entity=auth&action=login`

**Request Body**:
```json
{
  "email": "student@example.com",
  "password": "password123",
  "role": "student"
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "student@example.com",
    "role": "student"
  }
}
```

**cURL Example**:
```bash
curl -X POST 'http://localhost:8001/index.php?entity=auth&action=login' \
  -H 'Content-Type: application/json' \
  -c cookies.txt \
  -d '{"email":"student@example.com","password":"password123","role":"student"}'
```

Use the same cookie jar for all subsequent calls: `curl -b cookies.txt ...`

---

## Students

### Get Student Profile
**Endpoint**: `GET ?entity=students`  
**Response**: Student profile object

### Update Student Profile
**Endpoint**: `POST ?entity=students&action=update`  
**Request Body**: `{ "name", "phone", "major", "gpa", "bio", "profile_pic" }`

### Get Student Documents
**Endpoint**: `GET ?entity=students&action=documents`  
**Response**: Array of document objects with download URLs

### Upload Document
**Endpoint**: `POST ?entity=students&action=upload_doc`  
**Request**: `multipart/form-data` with `file` and `document_type`

### Get Student Applications
**Endpoint**: `GET ?entity=applications&student_id=1&term_id=4`  
**Query Parameters**: `student_id` (required), `term_id` (optional, defaults to active term)  
**Response**: Array of application objects

### Apply to Internship
**Endpoint**: `POST ?entity=applications&action=apply`  
**Request Body**: `{ "internship_id", "cover_letter", "document_ids": [] }`  
**Response**: Created application object  
**Errors**: 409 if duplicate, 400 if limit reached

### Withdraw Application
**Endpoint**: `POST ?entity=applications&id=APP123&action=withdraw`  
**Response**: Success message

### Confirm Offer
**Endpoint**: `POST ?entity=applications&id=APP123&action=confirm_offer`  
**Response**: Success message  
**Note**: Auto-withdraws other Pending/Accepted applications

---

## Internships

### List Internships
**Endpoint**: `GET ?entity=internships`  
**Query Parameters**: `term_id` (optional), `company_id` (optional, auto-filtered for companies)  
**Response**: Array of internship objects  
**Note**: `seats_left` only visible to owning company

### Get Internship Details
**Endpoint**: `GET ?entity=internships&id=15`  
**Response**: Single internship object

### Create Internship
**Endpoint**: `POST ?entity=internships&action=create`  
**Request Body**: `{ "position", "description", "location", "dates", "requirements", "type", "application_deadline" }`  
**Response**: Created internship object  
**Note**: Automatically assigned to active term

---

## Applications (Company View)

### Get Company Applications
**Endpoint**: `GET ?entity=applications&company_id=1`  
**Query Parameters**: `company_id` (required), `term_id` (optional), `status` (optional)  
**Response**: Array of application objects with student details and documents

### Update Application Status
**Endpoint**: `POST ?entity=applications&id=APP123&action=update_status_company`  
**Request Body**: `{ "status": "Accepted", "offer_details": "..." }`  
**Valid Statuses**: `Pending`, `Under Review`, `Accepted`, `Rejected`

---

## Companies

### Get Company Profile
**Endpoint**: `GET ?entity=companies&action=get_profile&company_id=1`  
**Response**: Company profile object

### Update Company Profile
**Endpoint**: `POST ?entity=companies&action=update`  
**Request Body**: `{ "name", "industry", "website", "phone", "address", "description", "logo" }`  
**Note**: Logo should be base64 data URL (max 2MB)

---

## Terms

### Get All Terms
**Endpoint**: `GET ?entity=terms`  
**Response**: Array of term objects

### Get Active Term
**Endpoint**: `GET ?entity=terms&action=active`  
**Response**: Active term object

---

## Admin

### Create Term
**Endpoint**: `POST ?entity=admin&resource=terms&action=add`  
**Request Body**: `{ "name", "start_date", "end_date", "max_applications_per_student", "is_active", "default_company_quota" }`

### Update Term
**Endpoint**: `POST ?entity=admin&resource=terms&action=update`  
**Request Body**: Term object with updated fields

### Delete Term
**Endpoint**: `POST ?entity=admin&resource=terms&action=delete`  
**Request Body**: `{ "id" }`

---

## Status Reference

| Status | Description | Set By |
|--------|-------------|--------|
| `Pending` | Initial status | System |
| `Under Review` | Company reviewing | Company |
| `Accepted` | Company accepts | Company |
| `Rejected` | Company rejects | Company |
| `Confirmed` | Student confirms | Student |
| `Withdrawn` | Student withdraws | Student |

**Status Workflow**: `Pending → Under Review → Accepted → Confirmed` (final)  
**Auto-Withdrawal**: Confirming an offer auto-withdraws other Pending/Accepted applications

---

## Error Responses

- **401 Unauthorized**: `{ "error": "Authentication required" }`
- **403 Forbidden**: `{ "error": "Access denied" }`
- **400 Bad Request**: `{ "error": "Invalid request: [message]" }`
- **404 Not Found**: `{ "error": "Resource not found" }`
- **409 Conflict**: `{ "error": "You have already applied for this internship." }`

---

**For detailed request/response examples, see the full API documentation in the codebase.**
