# Term Switching - Logical Behavior Plan

## Date: December 29, 2025

## Expected Behavior When Terms Switch

### 1. Student Dashboard & Views

#### When Active Term Changes:
- **Internships Browse Page**: Should ONLY show internships from the active term
- **Application History**: Should show applications from ALL terms (or filterable by term)
- **Application Limits**: Should reset per term
  - If student used 3/3 applications in Fall 2025, they should have 3/3 available in Spring 2026
  - Limits are per-term, not global
- **Dashboard Metrics**: Should reflect only the active term:
  - Active applications count (for active term only)
  - Accepted applications (for active term only)
  - Confirmed applications (for active term only)
- **Cannot Apply**: Should not be able to apply to internships from inactive terms
- **Cannot See**: Should not see internships from inactive terms in browse page

#### Edge Cases:
- If no active term exists: Should show message "No active term. Please contact administrator."
- If term becomes inactive while student has pending applications: Applications should remain but student cannot apply to new internships

### 2. Company Dashboard & Views

#### When Active Term Changes:
- **My Internships**: Should show internships from ALL terms (company owns them)
  - OR: Should filter by active term by default, with option to view all
- **Applications**: Should show applications for internships in the active term
  - Applications are tied to internships, which are tied to terms
- **Dashboard Metrics**: Should reflect only the active term:
  - Active internships (for active term)
  - Total applications (for active term internships)
  - Positions filled (for active term)
- **Create Internship**: Should automatically assign to active term
- **Seats/Quota**: Should be per-term
  - If company has 10 seats in Fall 2025, they should have 10 seats again in Spring 2026
- **Cannot See**: Should not see applications for internships from inactive terms (unless viewing all)

#### Edge Cases:
- If no active term exists: Cannot create new internships
- If term becomes inactive: Existing internships remain but no new applications can be received

### 3. Admin Dashboard & Views

#### Term Management:
- **Can See**: All terms (active and inactive)
- **Can Switch**: Can activate/deactivate terms
- **Can View**: All data across all terms
- **Dashboard**: Should show aggregate data or allow filtering by term

### 4. Data Isolation

#### Per-Term Data:
- **Internships**: Each internship belongs to ONE term (`term_id`)
- **Applications**: Each application belongs to ONE term (via `term_id` on internship)
- **Application Limits**: Tracked per student per term
- **Company Quotas**: Per term (using `default_company_quota` from terms table)

#### Cross-Term Behavior:
- Students can have applications in multiple terms
- Companies can have internships in multiple terms
- Switching terms should NOT affect historical data
- Historical data should remain accessible but filtered by term

## Test Plan

### Test 1: Student View - Active Term Filtering
1. Login as student
2. Verify only active term internships are shown
3. Switch active term (as admin)
4. Refresh student dashboard
5. Verify new internships appear, old ones disappear

### Test 2: Student Application Limits Per Term
1. Login as student
2. Apply to 3 internships (max limit) in Fall 2025
3. Verify cannot apply to more
4. Switch to Spring 2026 (as admin)
5. Verify student can apply to 3 more internships in Spring 2026

### Test 3: Company View - Term Filtering
1. Login as company
2. Create internship in Fall 2025
3. Switch to Spring 2026
4. Verify company sees Spring 2026 internships
5. Verify can create new internship (assigned to Spring 2026)

### Test 4: Company Seats Per Term
1. Login as company
2. Check seats available in Fall 2025
3. Fill some seats (confirm applications)
4. Switch to Spring 2026
5. Verify seats reset for Spring 2026

### Test 5: Application History Across Terms
1. Login as student
2. Apply to internships in Fall 2025
3. Switch to Spring 2026
4. Apply to internships in Spring 2026
5. Check application history - should show both terms

### Test 6: No Active Term
1. Deactivate all terms (as admin)
2. Login as student
3. Verify appropriate message shown
4. Login as company
5. Verify cannot create internships

## Implementation Checkpoints

### Backend Verification:
- [ ] `get_active_term()` function works correctly
- [ ] Internships filtered by `term_id` in queries
- [ ] Applications filtered by term (via internship `term_id`)
- [ ] Application limits checked per term
- [ ] Company quotas calculated per term

### Frontend Verification:
- [ ] Student browse page filters by active term
- [ ] Student dashboard metrics reflect active term
- [ ] Company dashboard metrics reflect active term
- [ ] Application history shows all terms (or filterable)
- [ ] Term switching UI exists (if applicable)

