# Sabancı University Internship Portal

This project is the frontend for the Sabancı University Internship Portal, designed to connect students with internship opportunities offered by various companies.

## Functionality

The portal provides distinct functionalities based on user roles:

### Common Features:
*   **Homepage:** Landing page with general information and navigation.
*   **User Authentication:** Secure login and registration for all user types.
*   **About/FAQ/Contact:** Informational pages.

### Student Role:
*   **Profile Management:** Create and update student profiles.
*   **Browse Internships:** Search and view available internship listings.
*   **Apply for Internships:** Submit applications to desired internships.
*   **View Application Status:** Track the status of submitted applications.
*   **Submit Evaluations:** Provide feedback or evaluations upon internship completion (if applicable).

### Company Role:
*   **Profile Management:** Create and update company profiles.
*   **Post Internships:** Create and publish new internship opportunities.
*   **Manage Listings:** Edit, view, or remove existing internship postings.
*   **Review Applications:** View and manage applications received from students.
*   **Manage Interns:** Track accepted interns and potentially manage evaluation processes.

### Administrator Role:
*   **User Management:** View, manage, and potentially approve/verify student and company accounts.
*   **Internship Oversight:** Monitor internship listings and applications.
*   **System Configuration:** (Potentially) Manage site settings or content.

## Technologies Used (Frontend)

*   HTML5
*   CSS3 (with custom Sabancı theme)
*   Bootstrap 5
*   JavaScript with API service (`js/api.js`)

## Backend Integration

The frontend is ready to integrate with the backend API. An API service has been created at `js/api.js` that provides methods for all backend operations.

### API Service Usage

Include the API service in your HTML:
```html
<script src="../js/api.js"></script>
```

Then use API methods:
```javascript
// Login
const response = await api.login('company@example.com', 'password123', 'company');

// Create internship
const internship = await api.createInternship(companyId, {
  position: "Software Developer Intern",
  description: "Join our team...",
  location: "Istanbul, Turkey"
});

// Apply
const application = await api.applyForInternship(studentId, internshipId, "Cover letter...");
```

See `js/api.js` for all available methods.

## Setup and Running

(Instructions for setting up and running the frontend project locally would go here - currently TBD based on further analysis of assets/scripts.)

## Backend Status

✅ **Backend is fully functional!**

The PHP backend in `/backend` directory includes:
*   ✅ Complete database integration (MySQL)
*   ✅ Authentication & authorization
*   ✅ Role-based access control
*   ✅ Complete API endpoints
*   ✅ Session management

### Next Steps for Frontend Integration

1. **Update HTML pages** to use `js/api.js`
2. **Add login functionality** using API service
3. **Replace localStorage** with API calls
4. **Add error handling** for API responses
5. **Test end-to-end workflows**

### Example Integration

**Before (localStorage):**
```javascript
localStorage.setItem('applications', JSON.stringify(applications));
```

**After (API):**
```javascript
const applications = await api.getStudentApplications(studentId);
```

See `../WORKFLOW_TEST.md` for complete integration examples. 