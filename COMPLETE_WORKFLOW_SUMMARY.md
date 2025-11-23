# Workflow Playbook (November 2025)

This document captures the end-to-end flow of the Sabancı Internship Portal. It replaces `WORKFLOW_TEST.md`, `DOCUMENTATION_UPDATE_SUMMARY.md`, and every other workflow-related note. Keep it updated whenever the business flow changes.

---

## What Works Today

1. **Admin onboarding** – Admins can create company accounts and terms from the backend UI/API. Those records feed the company login flow.
2. **Company lifecycle** – Companies edit their profile, create internships, review candidates, move them through statuses, and finalize placements. Every internship payload contains the authoritative company profile fields.
3. **Student lifecycle** – Students maintain rich profiles, upload multiple documents, browse internships, pick files per application, withdraw, and confirm offers.
4. **Document fidelity** – When a student applies, the selected `document_ids` are attached to the new application row so companies download exactly what was submitted.
5. **Dashboards** – Student + company dashboards, the Company Applications grid, and the Finalized page all read live data (including offer notes and attached documents) via `internship-portal/js/api.js`.
6. **Status standardization** – All application statuses use 6 clean names (`Pending`, `Accepted`, `Confirmed`, `Finalized`, `Rejected`, `Withdrawn`) consistently across frontend and backend, with automatic normalization from database values.
7. **Dynamic UI** – Status cards, badges, and application counts update dynamically from API data. Filter and search functionality works across student and company pages.
8. **Modal improvements** – Application detail modals populate dynamically, action buttons (Accept/Reject/Finalize) work correctly, and Bootstrap modals replace native browser dialogs for consistent UX.

---

## Roles & Key Pages

| Role | Pages | Highlights |
| --- | --- | --- |
| Admin | `admin/admin-add-company.html`, Postman (`admin` endpoints) | Creates companies/terms, impersonates users for QA. |
| Company | `company/company-dashboard.html`, `company/company-applications.html`, `company/company-finalized.html`, `company/company-internships.html`, `company/company-profile.html` | Dashboard metrics, live applications grid with resume/doc links, finalized placements modal, profile editing, dynamic application modals with Accept/Reject/Finalize buttons. |
| Student | `student/student-dashboard.html`, `student/student-internships.html`, `student/student-internship-detail.html`, `student/student-applications.html`, `student/student-documents.html`, `student/student-profile.html` | Browse/apply with "Apply Now" modal, document picker, application table with withdraw/confirm buttons, dynamic status cards, filter/search functionality. |

---

## Workflow Walkthrough

### 1. Admin creates a company
```
curl -X POST '...&entity=admin&resource=companies&action=add' -d '{"name":"KFC","email":"kfc@example.com","industry":"Food"}'
```
The new record appears instantly on the login form and in the admin UI. No manual database tweaks required.

### 2. Company logs in & updates profile
- Page: `company/company-profile.html`
- API: `GET/POST ?entity=companies&action=...`
- Outcome: Student-facing internship cards inherit `name`, `industry`, `website`, `phone`, `address`, `description`, `logo` from this source of truth.

### 3. Company posts internships
- Page: `company/company-internships.html`
- API: `POST ?entity=internships&action=create`
- Notes: `company_id` / `company_name` come from the authenticated session. No payload hacks needed.

### 4. Student browses & applies
- Page: `student/student-internships.html` → `student/student-internship-detail.html`
- API: `GET ?entity=internships`, `POST ?entity=applications&action=apply`
- Student selects documents from `student/student-documents.html`. The modal sends `document_ids` along with the cover letter. The backend transaction creates the application, links the documents, and responds with `status: "Pending"`.

### 5. Company reviews applications
- Page: `company/company-applications.html`
- API: `GET ?entity=applications&company_id=9`
- Features: DataTable with retry fallback, document download list, resume download button, dynamic Application Details modal with Accept/Reject/Finalize buttons, Update Status modal (with `offer_details`). Filtering by `status` and keyword search work together seamlessly.

### 6. Offer → confirmation → finalization
1. Company sets status to `Accepted` (previously `Offered`) with an offer note. Student sees the note in their Applications page and can **Confirm Offer**.
2. Student confirming moves status to `Confirmed` (database stores as `Confirmed_By_Student`).
3. Company can finalize by setting status to `Finalized` (database stores as `Approved_By_Company`).
4. Finalized applications auto-populate `company/company-finalized.html`, where recruiters can download attachments again and jump to evaluations.

### 7. Dashboards auto-update
- Company dashboard counts: active internships, total applications, finalized placements.
- Student dashboard cards: totals by `Pending`, `Accepted`, `Confirmed`, `Finalized`, `Rejected`, `Withdrawn` (all dynamic from API).
- The Finalized modal shows offer notes and attached documents, so no data entry is required outside the flow above.
- Status cards and badges update in real-time as applications move through the workflow.

---

## Status Reference

**Standardized Status Names (December 2025):**

| Status | Set By | Meaning | Database Value |
| --- | --- | --- | --- |
| `Pending` | System | New application, awaiting company action. | `Pending` |
| `Accepted` | Company | Company accepts application; student can confirm. Include `offer_details`. | `Accepted` |
| `Confirmed` | Student | Student confirms acceptance; appears on Finalized page. | `Confirmed_By_Student` |
| `Finalized` | Company | Company finalizes placement; shows on Finalized page. | `Approved_By_Company` |
| `Rejected` | Company | Company rejects the application. | `Rejected` |
| `Withdrawn` | Student | Student withdraws the application. | `Withdrawn` |

**Status Workflow:**
- `Pending` → `Accepted` (company accepts) or `Rejected` (company rejects)
- `Accepted` → `Confirmed` (student confirms) or `Withdrawn` (student withdraws)
- `Confirmed` → `Finalized` (company finalizes)
- `Finalized`, `Rejected`, `Withdrawn` are terminal states

**Note:** The backend automatically normalizes database values (`Confirmed_By_Student` → `Confirmed`, `Approved_By_Company` → `Finalized`) in all API responses, so the frontend always receives clean status names. This allows us to maintain user-friendly labels while working within database ENUM constraints.

---

## How to Test the Flow Quickly

1. **Login** – Use `student_cookies.txt`, `company_cookies.txt`, `admin_cookies.txt` for parallel sessions.
2. **Add mock document** – Upload on `student/student-documents.html`, note the `document_id` via developer tools (`api.getStudentDocuments`).
3. **Apply** – From `student-student-internship-detail.html`, select the uploaded document and submit. Check DevTools → Network → `applications&action=apply` for success.
4. **Verify as company** – Open `company/company-applications.html` and confirm:
   - Resume/download links point to the selected document(s).
   - Offer modal pre-fills previous `offer_details` when re-opened.
5. **Finalize** – Update status to `Accepted` → Student confirms (`Confirmed`) → Company finalizes (`Finalized`) → refresh Finalized page.
6. **Regression** – Hit `GET ?entity=applications&id=<APP>` to ensure API returns the same data the UI shows.

---

## House Rules

- **One source per topic**: The only workflow doc is this file. If you need to describe a change to the flow, update this file and nowhere else.
- **Document status transitions**: Whenever a new status value is introduced, add it to the Status Reference table and to `api_documentation.md`.
- **Keep screenshots out**: Text + sample requests are easier to diff and reason about.

