# Sabancı Internship Portal – API Guide (November 2025)

This is the single source of truth for the live REST-style API that powers the internship portal. Every request is routed through `backend/index.php` using the `entity` (resource) and optional `action` (operation) query parameters.

- **Base URL (local dev):** `http://localhost:8001/index.php`
- **Auth:** Session cookies. Always `POST /?entity=auth&action=login` first, store the cookie (`-c/-b cookies.txt`).
- **Roles:** `student`, `company`, `admin`. Each endpoint enforces role + ownership checks.

```bash
curl -X POST 'http://localhost:8001/index.php?entity=auth&action=login' \
  -H 'Content-Type: application/json' \
  -c cookies.txt \
  -d '{"email":"company@example.com","password":"secret","role":"company"}'
```

Use the same cookie jar for all subsequent calls: `curl -b cookies.txt …`.

---

## Key Entities & Endpoints

### Students

| Purpose | Method/Endpoint | Notes |
| --- | --- | --- |
| Fetch my profile | `GET ?entity=students` | Uses authenticated student ID. Admins can pass `&id=2` to impersonate. |
| Update profile | `POST ?entity=students&action=update` | Body can include `name`, `phone`, `major`, `gpa`, `bio`, `profile_pic`. |
| List my documents | `GET ?entity=students&action=documents` | Returns every upload with `document_id`, `download_url`, and `application_id` linkage. |
| Upload resume | `POST ?entity=students&action=upload_resume` | Multipart form field `resume`. Updates/inserts a `documents` row of type `CV`. |
| List my applications | `GET ?entity=applications&student_id={id}` | Requires the student to match `{id}`. Response includes `status`, `offer_details`, `internship_*` fields. |
| Withdraw application | `POST ?entity=applications&id=APP123&action=withdraw` | Allowed if status ∈ `Pending Review`, `Under Review`, `Shortlisted`, `Offered`. |
| Confirm offer | `POST ?entity=applications&id=APP123&action=confirm_offer` | Allowed when status = `Offered`; transitions to `Confirmed_By_Student`. |

**Applying with selected documents**
```bash
curl -X POST '...&entity=applications&action=apply' \
  -b cookies.txt \
  -H 'Content-Type: application/json' \
  -d '{
        "internship_id": 15,
        "cover_letter": "Excited to join the team!",
        "document_ids": ["DOC123456", "DOC654321"]
      }'
```
- The backend uses the authenticated student ID, generates a collision-safe `application_id`, saves status `Pending Review`, and links each `document_id` to the new `applications.id`.

### Internships

| Purpose | Method/Endpoint | Notes |
| --- | --- | --- |
| List all internships | `GET ?entity=internships` | Always returns live company metadata (`company.name`, `industry`, `phone`, `address`, `logo`). |
| Get detail | `GET ?entity=internships&id=15` | Used by `student-internship-detail.html`. |
| Company’s postings | `GET ?entity=internships&company_id=9` | Authenticated company only. |
| Create posting | `POST ?entity=internships&action=create` | Body requires `position` + `description`; other fields optional. Uses logged-in company ID/name. |

### Applications (Company view)

| Purpose | Method/Endpoint | Notes |
| --- | --- | --- |
| List applications | `GET ?entity=applications&company_id=9` | Requires company 9 (or admin). Include `&status=Offered` to filter. Each item contains student profile fields, internship summary, `documents[]`, and `resume_download_url`. |
| Single application | `GET ?entity=applications&id=APP115` | Authorizes student owner, company owner, or admin. Includes company + student + documents. |
| Update status | `POST ?entity=applications&id=APP115&action=update_status_company` | Body `{ "status": "Offered", "offer_details": "Start 1 June" }`. Valid statuses: `Pending Review`, `Under Review`, `Shortlisted`, `Interview Scheduled`, `Offered`, `Rejected`, `Rejected_By_Company`, `Approved_By_Company`. |

When status is set to `Offered`, include `offer_details`; students see the note inside their portal. Moving to `Approved_By_Company` surfaces the application on the Finalized page. Once a student hits `confirm_offer`, the status becomes `Confirmed_By_Student`.

### Companies

| Purpose | Method/Endpoint | Notes |
| --- | --- | --- |
| Fetch profile | `GET ?entity=companies&action=get_profile&company_id=9` | Company 9 or admin. Returns `name`, `industry`, `website`, `phone`, `address`, `description`, `logo`. |
| Update profile | `POST ?entity=companies&action=update` | Body with any editable fields above; automatically ties to logged-in company. |

---

## Application Status Flow

```
Pending Review (default) → Under Review → Shortlisted → Interview Scheduled
   → Offered → (student confirms) → Confirmed_By_Student
   → Approved_By_Company (company finalizes) → Finalized dashboards
   ↘ Rejected / Rejected_By_Company / Withdrawn (terminal)
```

- Duplicate submissions are blocked by a DB constraint on `(student_id, internship_id)` and handled as HTTP 409.
- Every application response now normalizes the historical `Pending` label to `Pending Review` so UI badges stay consistent.
- `documents[]` always includes `document_type`, `file_name`, `file_size`, and `download_url` (absolute path) so the frontend can build download buttons directly.

---

## Error Handling Cheatsheet

| Issue | Response |
| --- | --- |
| Missing auth / wrong role | `401/403` with `{ "error": "Access denied." }` |
| Duplicate application | `409` `{ "error": "You have already applied for this internship." }` |
| Invalid status transition | `400` `{ "error": "Invalid status 'Foo' for company update." }` |
| DB failure | `500` `{ "error": "Failed to ..." }` (message logged server-side) |

---

## Testing Tips

1. **Login once per role.** Maintain separate cookie jars: `student_cookies.txt`, `company_cookies.txt`, `admin_cookies.txt`.
2. **Use `?status=` filters** while testing the Company Applications grid to validate pending/offered/finalized counts.
3. **Check document linkage** by uploading from the student Documents page, applying with selected IDs, and verifying that `GET ?entity=applications&company_id=...` returns the same downloads.
4. **Finalization flow:**
   - Company `POST update_status_company` → `Approved_By_Company`
   - Student `POST confirm_offer` → `Confirmed_By_Student`
   - Refetch Finalized page (`company-finalized.html`) to see the record with attached documents.

This document replaces all previous API specs (`COMPANY_API_SUMMARY.md`, `API_Testing_Guide.md`, etc.). Keep it updated whenever an endpoint shape changes.

### Admin

Admin endpoints are primarily used via the web UI or Postman while impersonating. All of them require an authenticated admin session.

| Purpose | Method/Endpoint | Notes |
| --- | --- | --- |
| List companies | `GET ?entity=admin&resource=companies` | Returns every company record so admins can impersonate or audit. |
| Create company | `POST ?entity=admin&resource=companies&action=add` | Body `{ "name", "email", "industry", ... }`. Sets temporary password and inserts into `companies`. |
| List students | `GET ?entity=admin&resource=students` | Supports `&id=` to fetch a single student. |
| Create student | `POST ?entity=admin&resource=students&action=add` | Seeds `students` table with the provided profile. |
| List terms | `GET ?entity=admin&resource=terms` | Used for academic planning dashboards. |
| Create term | `POST ?entity=admin&resource=terms&action=add` | Body `{ "name", "start_date", "end_date" }`. |

Admin users can also impersonate via the UI: selecting a company or student writes `sessionStorage` keys (`impersonatedUser*`). The backend still enforces role checks, so impersonated requests go through the same company/student endpoints above.
