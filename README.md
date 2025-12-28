# Sabancı University Internship Portal

A full-stack internship management platform connecting students, companies, and admins. Backend is PHP/MySQL (front controller); frontend is Bootstrap 5 with a shared `api.js` client.

## Documentation Map

We maintain **five** core documents:

1. `README.md` (this file) – project overview, setup, and structure.
2. `PRD_AND_WORKFLOWS.md` – product requirements, expected workflows, and business logic.
3. `API_DOCUMENTATION.md` – complete API reference with request/response payloads.
4. `learnings.md` – architecture lessons, debugging notes, and best practices.
5. `TESTING_DIRECTIONS.md` – how to test the system.
6. `TEST_RESULTS.md` – confirmed test results and verification.

Additional documentation:
- `SAML_*.md` – SAML SSO integration setup (for IT department)
- `STUDENT_EVALUATIONS_IMPLEMENTATION.md` – student evaluations feature documentation

---

## Project Status (December 2025)

| Layer | Status | Notes |
| --- | --- | --- |
| Backend | ✅ Production-ready | MySQL-backed, session auth, role/ownership checks, document linkage, standardized workflow statuses (6 core statuses). |
| API Client | ✅ Shared `internship-portal/js/api.js` | Auto-detects base URL, injects credentials, normalizes errors, handles status normalization. |
| Frontend | ✅ Wired to backend | Student/company dashboards, application grids, finalization modals all consume live data. Dynamic status cards, filter/search functionality, Bootstrap modals replace native dialogs. |
| Documentation | ✅ Consolidated | Only the four docs above remain and are up to date with latest learnings and workflow changes. |

---

## Stack Overview

- **Backend:** PHP 8+, MySQL (`shadowing` DB on `pro2-dev.sabanciuniv.edu`), front-controller router `backend/index.php`.
- **Frontend:** Static HTML/Bootstrap 5 under `internship-portal/`, modular JS per page, centralized API client.
- **Auth:** Session cookies (`auth/login`), role + ownership enforcement (`student`, `company`, `admin`).
- **Data flow:** Student uploads docs → applies with selected IDs → backend attaches them to application → company downloads from Applications/Finalized pages.

---

## Quick Start

### 1. Backend & Database

```bash
cd backend
php config/create_tables.php   # sets up schema + seed data
php -S localhost:8001          # run API server
```

- DB host: `pro2-dev.sabanciuniv.edu`
- DB name: `shadowing`
- DB user/pass: `shadowing` / `QT8rvzZF`
- All handlers use prepared statements + shared `config/database.php`.

### 2. Frontend

Open `internship-portal/index.html` via any static server (or VS Code Live Server). The `api.js` client automatically points to `http://localhost:8001` while developing.

### 3. Test Credentials

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@example.com` | `password123` |
| Company | `company@example.com` | `password123` |
| Student | `student@example.com` | `password123` |

Each login stores `userRole`/`userId` locally and session cookies server-side.

---

## Key Features

- **Company workflow:** Profile editing → internship posting → application review with document downloads → status updates (`Pending Review` → `Approved_By_Company`) → Finalized placements page.
- **Student workflow:** Document upload → internship detail page with “Apply Now” modal → choose uploaded docs per application → withdraw/confirm inside `student/student-applications.html`.
- **Dashboards:** Company dashboard cards (active internships, total apps, finalized placements) and student dashboard cards (total/pending/offered/finalized) read live API data.
- **Resilient UI:** Company applications grid fallbacks when DataTables is slow; all modals are fed via cached JSON rather than DOM scraping.

See `PRD_AND_WORKFLOWS.md` for complete workflow documentation.

---

## API Overview

The API is centralized at `http://localhost:8001/index.php`. Highlights:

- `entity=auth` – login/logout/check.
- `entity=students` – profile, documents, resume upload.
- `entity=companies` – profile fetch/update.
- `entity=internships` – CRUD for postings (company-only). Every response includes live company metadata.
- `entity=applications` – student lists, company lists (with `status` filter), apply/withdraw/confirm, update status.

Full request/response details and sample curl commands live in `API_DOCUMENTATION.md`.

---

## Testing Tips

1. Maintain separate cookie jars (`student_cookies.txt`, etc.) to switch roles quickly.
2. Use browser DevTools → Network to confirm `applications&action=apply` payloads include `document_ids`.
3. Hit `GET ?entity=applications&id=APP###` to confirm the API reflects whatever the UI shows (statuses, `offer_details`, documents).
4. Verify the Finalized page after approving/confirming to ensure attachments, offer notes, and evaluation links appear.

---

## Project Structure

```
sabanciportal-1/
├── backend/
│   ├── auth/           # session helpers / role guards
│   ├── config/         # DB connection + schema scripts
│   ├── handlers/       # entity-specific controllers (students, companies, etc.)
│   └── index.php       # single entry point / router
├── internship-portal/
│   ├── admin/
│   ├── company/
│   ├── student/
│   └── js/
│       └── api.js      # shared API client
├── README.md
├── PRD_AND_WORKFLOWS.md
├── API_DOCUMENTATION.md
├── learnings.md
├── TESTING_DIRECTIONS.md
└── TEST_RESULTS.md
```

---

## Contribution Checklist

- Update the relevant doc (API, workflow, or learnings) when behavior changes.
- Keep `api.js` as the single HTTP abstraction; page-specific scripts should not hardcode URLs.
- Run `php -l backend/handlers/*.php` before committing backend changes.
- Manually sanity check the offer → confirmation → finalized flow whenever touching applications/documents.

