# Term-Based Application Rounds & Quotas Implementation

## Overview
This document describes the implementation of term-based application history, application rounds, and company quotas.

## Database Changes

### New Tables
1. **application_rounds** - Manages application windows per term
   - Fields: id, term_id, name, start_date, end_date, max_applications_per_student, default_company_quota, is_active
   
2. **company_round_quotas** - Company-specific quota overrides per round
   - Fields: id, company_id, round_id, quota_override

### Modified Tables
1. **applications** - Added term_id and round_id columns
2. **companies** - Added company_quota column (for self-configuration)

### Migration Files
- `backend/config/migrate_add_term_rounds_quotas.sql` - SQL migration
- `backend/config/migrate_add_term_rounds_quotas.php` - PHP migration script

## Backend Implementation

### New Handlers
1. **rounds_handler.php** - CRUD for application rounds
   - GET: List rounds (optionally filtered by term_id)
   - POST create: Create new round
   - POST update: Update round
   - POST toggle_active: Enable/disable round

2. **quotas_handler.php** - Company quota management
   - GET: Get quota for company/round
   - POST set: Set company quota for a round

### Updated Handlers
1. **applications_handler.php**
   - Apply action: Checks for active round, enforces max_applications_per_student
   - Finalize action: Checks company quota before finalizing
   - Student applications: Supports term_id filter, includes term/round info

### Helper Functions
- `get_active_round($date)` - Returns active round for a date
- `get_term_for_date($date)` - Returns term for a date
- `get_effective_quota($company_id, $round_id)` - Gets quota (override or default)
- `get_used_quota($company_id, $round_id)` - Counts finalized applications

## Frontend Implementation

### API Client Updates (`internship-portal/js/api.js`)
Added methods:
- `getTerms()` - Get all terms
- `getRounds(termId)` - Get rounds (optionally filtered)
- `getRound(roundId)` - Get specific round
- `createRound(roundData)` - Create round (admin)
- `updateRound(roundId, roundData)` - Update round (admin)
- `toggleRoundActive(roundId)` - Toggle round status (admin)
- `getCompanyQuota(companyId, roundId)` - Get quota
- `setCompanyQuota(companyId, roundId, quota)` - Set quota
- `getCompanyQuotas(companyId)` - Get all quotas for company
- `getStudentApplications(studentId, termId)` - Updated to support term filter

### Pages to Create
1. **student-application-history.html** - Student view of past applications by term
2. **admin-rounds.html** - Admin round management
3. **admin-terms.html** - Admin term management (if not exists)
4. **company-quotas.html** - Company quota configuration

## Workflow

### Student Application Flow
1. Student clicks "Apply" → System checks for active round
2. If no active round → Error: "Applications are currently closed"
3. If active round exists → Check student's active applications in that round
4. If at max → Error with current count and max
5. If under max → Create application with term_id and round_id

### Company Finalization Flow
1. Company clicks "Finalize" → System checks application status (must be Confirmed)
2. Get effective quota for company in that round (override or default)
3. Count finalized applications for company in that round
4. If quota reached → Error with quota info
5. If under quota → Finalize application

### Admin Round Management
1. Admin creates round → Sets term, dates, max_applications_per_student, default_company_quota
2. Admin can toggle round active/inactive
3. Admin can update round settings
4. Multiple rounds can exist per term, but only one active at a time

## Next Steps

1. Run migration: `php backend/config/migrate_add_term_rounds_quotas.php`
2. Create frontend pages (student history, admin rounds, company quotas)
3. Test round activation/deactivation
4. Test quota enforcement
5. Test term filtering on student applications

