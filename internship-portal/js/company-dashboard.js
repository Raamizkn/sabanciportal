const CONFIRMED_STATUSES = ['Confirmed', 'Confirmed_By_Student']; // Confirmed applications (final status)
const PENDING_STATUSES = ['Pending']; // Pending applications
const CONFIRMED_STATUSES = ['Confirmed', 'Confirmed_By_Student']; // Student confirmed (database value included)

document.addEventListener('DOMContentLoaded', function() {
    const api = new APIService();

    // Check for impersonation first
    const isBeingImpersonated = sessionStorage.getItem('impersonatedUserType') === 'company';
    const impersonatedUserId = sessionStorage.getItem('impersonatedUser');
    const userRole = localStorage.getItem('userRole');
    const isAdmin = userRole === 'admin';
    
    // Get company ID from URL parameter (for admin viewing)
    const urlParams = new URLSearchParams(window.location.search);
    const urlCompanyId = urlParams.get('id');
    
    // Determine company ID: URL param > impersonated > stored (company only)
    let companyId = null;
    let companyName = null;
    let isAdminViewMode = false;
    
    if (urlCompanyId) {
        companyId = urlCompanyId;
        companyName = 'Company'; // Will be loaded from API
        // Admin with URL ID but NOT impersonating this company = view mode
        if (isAdmin && (!isBeingImpersonated || impersonatedUserId !== urlCompanyId)) {
            isAdminViewMode = true;
        }
    } else if (isBeingImpersonated) {
        companyId = impersonatedUserId;
        companyName = sessionStorage.getItem('impersonatedUserName');
    } else if (userRole === 'company') {
        companyId = localStorage.getItem('userId');
        companyName = localStorage.getItem('userName');
    }

    // Access control
    if (!companyId) {
        if (isAdmin) {
            // Admin without company context - redirect to admin dashboard
            window.location.href = '../admin/admin-dashboard.html';
            return;
        }
        window.location.href = '../index.html';
        return;
    }
    
    // Add admin view bar if in admin view mode
    if (isAdminViewMode) {
        addAdminViewBar();
    }

    const welcomeMessage = document.getElementById('dashboardWelcomeName');
    if (welcomeMessage) {
        welcomeMessage.textContent = companyName || 'Company';
    }
    const companyNameNavbar = document.querySelector('.navbar .d-none.d-lg-inline-block.mx-lg-2');
    if (companyNameNavbar) {
        companyNameNavbar.textContent = companyName || 'Company';
    }

    Promise.all([
        api.getCompanyInternships(companyId),
        api.getCompanyApplications(companyId)
    ]).then(([internships, applications]) => {
        populateCompanyInfo(internships, applications);
        populateRecentApplications(applications);
        populateActiveInternships(internships, applications);
        populatePendingTasks(applications);
    }).catch(error => {
        console.error('Error fetching company data:', error);
    });
});

function populateCompanyInfo(internships, applications) {
    const activeInternships = internships.filter(internship => internship.status === 'Active').length;
    const totalApplications = applications.length;
    const positionsFilled = applications.filter(app => CONFIRMED_STATUSES.includes(app.status)).length;

    setDashboardMetric('dashboardActiveCount', activeInternships);
    setDashboardMetric('dashboardApplicationsCount', totalApplications);
    setDashboardMetric('dashboardFilledCount', positionsFilled);
}

function populateRecentApplications(applications) {
    const tbody = document.getElementById('recentApplicationsBody');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (!applications.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No applications yet.</td></tr>';
        return;
    }

    applications
        .slice(0, 5)
        .forEach(app => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <div class="d-flex align-items-center">
                        <div class="fw-semibold">${app.student_name || 'N/A'}</div>
                    </div>
                </td>
                <td>${app.internship_position || 'N/A'}</td>
                <td>${app.student_major || 'N/A'}</td>
                <td>${app.applied_date ? new Date(app.applied_date).toLocaleDateString() : '—'}</td>
                <td>${renderStatusBadge(app.status)}</td>
                <td class="text-center">
                    <div class="d-inline-flex gap-1">
                        <a href="company-applications.html?application_id=${encodeURIComponent(app.application_id)}" class="btn btn-outline-primary btn-sm btn-icon rounded-pill" title="View Application">
                            <i class="ph-eye"></i>
                        </a>
                    </div>
                </td>
            `;
            tbody.appendChild(row);
        });
}

function populateActiveInternships(internships, applications) {
    const container = document.getElementById('activeInternshipsContainer');
    if (!container) return;

    const activeInternships = internships.filter(internship => internship.status === 'Active');
    container.innerHTML = '';

    if (!activeInternships.length) {
        container.innerHTML = '<div class="col-12"><div class="text-center text-muted py-5">You have no active internships. <a href="company-internships.html">Post a new opening</a>.</div></div>';
        return;
    }

    const applicantsByInternship = applications.reduce((acc, app) => {
        if (!app.internship_id) {
            return acc;
        }
        acc[app.internship_id] = (acc[app.internship_id] || 0) + 1;
        return acc;
    }, {});

    activeInternships.forEach(internship => {
        const applicantCount = applicantsByInternship[internship.id] || 0;
        const card = document.createElement('div');
        card.className = 'col-lg-6';
        card.innerHTML = `
            <div class="card card-body border-start-primary border-start-5 mb-3">
                <div class="d-sm-flex align-items-start mb-3">
                    <div class="flex-fill">
                        <h5 class="mb-1">${internship.title || internship.position || 'Internship'}</h5>
                        <ul class="list-inline list-inline-bullet text-muted mb-0">
                            <li class="list-inline-item">${internship.location || 'Location TBD'}</li>
                            <li class="list-inline-item">${internship.dates || 'Dates TBD'}</li>
                        </ul>
                    </div>
                    <div class="d-flex align-items-center ms-sm-3">
                        <span class="badge bg-primary rounded-pill">${applicantCount} applicant${applicantCount === 1 ? '' : 's'}</span>
                    </div>
                </div>
                <div class="d-flex">
                    <a href="company-internships.html#edit-${internship.id}" class="text-body me-3">
                        <i class="ph-pencil me-1"></i>
                        Edit
                    </a>
                    <a href="company-applications.html?internship_id=${internship.id}" class="text-body">
                        <i class="ph-users me-1"></i>
                        View Applicants
                    </a>
                </div>
            </div>
        `;
        container.appendChild(card);
    });
}

function populatePendingTasks(applications) {
    const pendingTasksList = document.getElementById('pendingTasksList');
    if (!pendingTasksList) return;
    pendingTasksList.innerHTML = '';

    const pendingCount = applications.filter(app => PENDING_STATUSES.includes(app.status)).length;
    const confirmedCount = applications.filter(app => CONFIRMED_STATUSES.includes(app.status)).length;
    const confirmedCount = applications.filter(app => CONFIRMED_STATUSES.includes(app.status)).length;

    if (pendingCount) {
        pendingTasksList.appendChild(createTaskItem('ph-users', `Review ${pendingCount} pending application${pendingCount === 1 ? '' : 's'}`, 'company-applications.html'));
    }

    if (confirmedCount > 0) {
        // Show confirmed applications waiting for finalization
        pendingTasksList.appendChild(createTaskItem('ph-check-square', `Finalize ${confirmedCount} confirmed application${confirmedCount === 1 ? '' : 's'}`, 'company-applications.html'));
    }

    if (confirmedCount) {
        pendingTasksList.appendChild(createTaskItem('ph-check-circle', `View ${confirmedCount} confirmed placement${confirmedCount === 1 ? '' : 's'}`, 'company-finalized.html'));
    }

    if (!pendingTasksList.children.length) {
        pendingTasksList.innerHTML = '<li class="list-group-item text-center text-muted">All caught up! No pending tasks.</li>';
    }
}

function setDashboardMetric(elementId, value) {
    const el = document.getElementById(elementId);
    if (el) {
        el.textContent = value;
    }
}

function createTaskItem(icon, text, href) {
    const li = document.createElement('li');
    li.className = 'list-group-item d-flex flex-wrap align-items-center py-2';
    li.innerHTML = `
        <a href="${href}" class="d-flex align-items-center me-3">
            <i class="${icon} ph-lg me-2"></i>
            <span>${text}</span>
        </a>
        <div class="d-flex align-items-center text-muted ms-auto">
            <i class="ph-calendar ph-sm me-1"></i>
            <span>Updated just now</span>
        </div>
    `;
    return li;
}

function renderStatusBadge(status) {
    let badgeClass = 'badge bg-secondary bg-opacity-20 text-secondary';
    const label = formatStatusLabel(status);
    if (CONFIRMED_STATUSES.includes(status)) {
        badgeClass = 'badge bg-success bg-opacity-20 text-success';
    } else if (PENDING_STATUSES.includes(status)) {
        badgeClass = 'badge bg-warning bg-opacity-20 text-warning';
    } else if (status === 'Accepted' || status === 'Confirmed' || status === 'Confirmed_By_Student') {
        badgeClass = 'badge bg-info bg-opacity-20 text-info';
    } else if (status && status.startsWith('Rejected')) {
        badgeClass = 'badge bg-danger bg-opacity-20 text-danger';
    }
    return `<span class="${badgeClass}">${label}</span>`;
}

function formatStatusLabel(status) {
    if (!status) {
        return 'Unknown';
    }
    // Statuses are now clean - return as-is
    return status;
}

// Add admin view bar at the top of the page
function addAdminViewBar() {
    const adminNav = document.createElement('div');
    adminNav.id = 'admin-view-bar';
    adminNav.style.cssText = 'position: sticky; top: 0; z-index: 1030; background: #0d6efd; color: white; padding: 10px 15px;';
    adminNav.innerHTML = `
        <div class="container-fluid d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center">
                <i class="ph-eye me-2"></i>
                <span><strong>Admin View:</strong> Viewing company dashboard (Read-Only)</span>
            </div>
            <div>
                <a href="../admin/admin-companies.html" class="btn btn-sm btn-light me-2">
                    <i class="ph-arrow-left me-1"></i>Back to Companies
                </a>
                <a href="../admin/admin-dashboard.html" class="btn btn-sm btn-light">
                    <i class="ph-house me-1"></i>Admin Dashboard
                </a>
            </div>
        </div>
    `;
    document.body.insertBefore(adminNav, document.body.firstChild);
}
