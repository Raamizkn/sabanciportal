
document.addEventListener('DOMContentLoaded', () => {
    const studentId = localStorage.getItem('userId');
    const userRole = localStorage.getItem('userRole');
    const isAdmin = userRole === 'admin';
    const isImpersonating = sessionStorage.getItem('impersonatedUserType') === 'student';

    // Allow admin access or impersonated access
    if (userRole !== 'student' && !isImpersonating && !isAdmin) {
        alert('Access Denied. You must be logged in as a Student.');
        window.location.href = '../index.html';
        return;
    }

    // For admin viewing, get student ID from URL or use impersonated ID
    const urlParams = new URLSearchParams(window.location.search);
    const urlStudentId = urlParams.get('id');
    const targetStudentId = urlStudentId || (isImpersonating ? sessionStorage.getItem('impersonatedUser') : studentId);

    if (!targetStudentId || targetStudentId === 'undefined') {
        alert('Student ID not found. Please log in again.');
        window.location.href = '../index.html';
        return;
    }

    // Add admin navigation bar if accessed by admin
    if (isAdmin && !isImpersonating) {
        addAdminNavigationBar();
    }

    loadStudentApplications(targetStudentId);
});

// Add admin navigation bar at the top of the page
function addAdminNavigationBar() {
    const adminNav = document.createElement('div');
    adminNav.className = 'alert alert-info mb-0 rounded-0 border-0';
    adminNav.style.cssText = 'position: sticky; top: 0; z-index: 1030; background: #0d6efd !important; color: white;';
    adminNav.innerHTML = `
        <div class="container-fluid d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <i class="ph-arrow-left me-2" style="cursor: pointer;" onclick="window.history.back()"></i>
                <span><strong>Admin View:</strong> Viewing student applications (Admin Mode)</span>
            </div>
            <div>
                <a href="../admin/admin-students.html" class="btn btn-sm btn-light me-2">
                    <i class="ph-arrow-left me-1"></i>Back to Students
                </a>
                <a href="../admin/admin-dashboard.html" class="btn btn-sm btn-light">
                    <i class="ph-house me-1"></i>Admin Dashboard
                </a>
            </div>
        </div>
    `;
    document.body.insertBefore(adminNav, document.body.firstChild);
}

async function loadStudentApplications(studentId) {
    try {
        const applications = await api.getStudentApplications(studentId);
        populateApplicationsTable(applications);
        updateStatusCards(applications);
        updateApplicationsBadge(applications.length);
    } catch (error) {
        console.error('Failed to load applications:', error);
        const tableBody = document.querySelector('.datatable-basic tbody');
        tableBody.innerHTML = '<tr><td colspan="7" class="text-center">Failed to load applications.</td></tr>';
        updateApplicationsBadge(0);
    }
}

function updateApplicationsBadge(count) {
    // Update badge in sidebar navigation
    const badge = document.getElementById('studentApplicationsBadge');
    if (badge) {
        badge.textContent = count;
        // Hide badge if count is 0
        if (count === 0) {
            badge.style.display = 'none';
        } else {
            badge.style.display = '';
        }
    }
    
    // Also update by selector as fallback
    const applicationsNavLink = document.querySelector('.nav-link[href="student-applications.html"]');
    if (applicationsNavLink) {
        let badgeElement = applicationsNavLink.querySelector('.badge');
        if (!badgeElement) {
            badgeElement = document.createElement('span');
            badgeElement.className = 'badge bg-primary rounded-pill ms-auto';
            badgeElement.id = 'studentApplicationsBadge';
            applicationsNavLink.appendChild(badgeElement);
        }
        badgeElement.textContent = count;
        if (count === 0) {
            badgeElement.style.display = 'none';
        } else {
            badgeElement.style.display = '';
        }
    }
}

function populateApplicationsTable(applications) {
    console.log('Data received from API:', JSON.stringify(applications, null, 2));
    const tableBody = document.querySelector('.datatable-basic tbody');
    tableBody.innerHTML = ''; // Clear placeholder rows

    if (applications.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="7" class="text-center">You have not submitted any applications yet.</td></tr>';
        return;
    }

    applications.forEach(app => {
        const row = document.createElement('tr');
        row.setAttribute('data-application-id', app.application_id);

        const statusBadge = getStatusBadge(app.status);
        const actionButtons = getActionButtons(app);

        row.innerHTML = `
            <td>
                <div class="d-flex align-items-center">
                    <div>${app.company_name || 'N/A'}</div>
                </div>
            </td>
            <td>${app.internship_position || 'N/A'}</td>
            <td>${app.internship_location || 'N/A'}</td>
            <td>${new Date(app.created_at).toLocaleDateString()}</td>
            <td>${app.internship_dates || 'N/A'}</td>
            <td>${statusBadge}</td>
            <td class="text-center">
                <div class="d-inline-flex bg-light rounded p-1">
                    ${actionButtons}
                </div>
            </td>
        `;
        tableBody.appendChild(row);
    });

    // Add event listeners for the new buttons
    addEventListeners();
}

function formatStatusLabel(status) {
    if (!status) {
        return 'Unknown';
    }
    const map = {
        'Pending': 'Pending Review',
        'Pending Review': 'Pending Review',
        'Rejected_By_Company': 'Rejected by Company',
        'Approved_By_Company': 'Finalized by Company'
    };
    return map[status] || status.replace(/_/g, ' ');
}

function getStatusBadge(status) {
    if (!status) return '<span class="badge bg-secondary">Pending Review</span>';
    
    const normalizedStatus = normalizeStatusForStudent(status);
    let badgeClass = 'bg-secondary';
    
    switch (normalizedStatus) {
        case 'Pending Review':
        case 'Pending':
            badgeClass = 'bg-warning text-dark';
            break;
        case 'Accepted':
            badgeClass = 'bg-success';
            break;
        case 'Confirmed':
        case 'Confirmed_By_Student':
            badgeClass = 'bg-info';
            break;
        case 'Finalized':
        case 'Approved_By_Company':
            badgeClass = 'bg-success';
            break;
        case 'Rejected':
        case 'Rejected_By_Company':
            badgeClass = 'bg-danger';
            break;
        case 'Withdrawn':
            badgeClass = 'bg-secondary';
            break;
        case 'Shortlisted':
        case 'Interview Scheduled':
            badgeClass = 'bg-primary';
            break;
        default:
            badgeClass = 'bg-secondary';
    }
    
    return `<span class="badge ${badgeClass}">${normalizedStatus}</span>`;
}

function getActionButtons(application) {
    let buttons = '';

    // Always show a view button
    buttons += `<a href="student-application-detail.html?id=${application.application_id}" class="btn btn-sm btn-icon btn-light view-button" data-bs-popup="tooltip" title="View Details"><i class="ph-eye"></i></a>`;

    // Show confirm button if status is Accepted
    const normalizedStatus = normalizeStatusForStudent(application.status);
    if (normalizedStatus === 'Accepted') {
        buttons += `<button class="btn btn-sm btn-icon btn-light confirm-application-btn ms-1" data-application-id="${application.application_id}" data-bs-popup="tooltip" title="Confirm Acceptance"><i class="ph-check-circle text-success"></i></button>`;
    }

    // Allow withdrawal if not finalized, rejected, or already withdrawn
    const finalStatuses = ['Finalized', 'Rejected', 'Withdrawn', 'Confirmed'];
    if (!finalStatuses.includes(normalizedStatus)) {
        buttons += `<button class="btn btn-sm btn-icon btn-light withdraw-btn ms-1" data-application-id="${application.application_id}" data-bs-popup="tooltip" title="Withdraw Application"><i class="ph-x-circle text-danger"></i></button>`;
    }

    return buttons;
}

function normalizeStatusForStudent(status) {
    if (!status) return 'Pending Review';
    const map = {
        'Pending': 'Pending Review',
        'Confirmed_By_Student': 'Confirmed',
        'Approved_By_Company': 'Finalized'
    };
    return map[status] || status;
}

function addEventListeners() {
    // Withdraw buttons
    document.querySelectorAll('.withdraw-btn').forEach(button => {
        button.addEventListener('click', async (e) => {
            const appId = e.currentTarget.getAttribute('data-application-id');
            showConfirmModal(
                'Withdraw Application',
                'Are you sure you want to withdraw this application?',
                'Withdraw',
                'Cancel',
                async (confirmed) => {
                    if (confirmed) {
                        try {
                            const result = await api.withdrawApplication(appId);
                            if (result.error) {
                                showAlertModal('Error', result.error, 'error');
                                return;
                            }
                            showAlertModal('Application Withdrawn', result.message || 'Your application has been withdrawn.', 'info', () => {
                                loadStudentApplications(localStorage.getItem('userId'));
                            });
                        } catch (error) {
                            showAlertModal('Error', 'An error occurred while withdrawing the application.', 'error');
                        }
                    }
                }
            );
        });
    });

    // Confirm application buttons (when company accepts)
    document.querySelectorAll('.confirm-application-btn').forEach(button => {
        button.addEventListener('click', async (e) => {
            const appId = e.currentTarget.getAttribute('data-application-id');
            showConfirmModal(
                'Confirm Acceptance',
                'Confirm your acceptance of this internship offer? The company will then be able to finalize the placement.',
                'Confirm',
                'Cancel',
                async (confirmed) => {
                    if (confirmed) {
                        try {
                            const result = await api.confirmApplication(appId);
                            if (result.error) {
                                showAlertModal('Error', result.error, 'error');
                                return;
                            }
                            showAlertModal('Success', result.message || 'Application confirmed! Company can now finalize.', 'success', () => {
                                loadStudentApplications(localStorage.getItem('userId'));
                            });
                        } catch (error) {
                            showAlertModal('Error', 'An error occurred while confirming the application.', 'error');
                        }
                    }
                }
            );
        });
    });
}

function updateStatusCards(applications) {
    const statusCounts = {
        total: applications.length,
        pending: 0,
        accepted: 0,
        confirmed: 0,
        finalized: 0,
        rejected: 0
    };

    applications.forEach(app => {
        const status = app.status;
        if (['Pending', 'Pending Review', 'Under Review'].includes(status)) {
            statusCounts.pending++;
        } else if (status === 'Accepted') {
            statusCounts.accepted++;
        } else if (status === 'Confirmed_By_Student') {
            statusCounts.confirmed++;
        } else if (status === 'Approved_By_Company') {
            statusCounts.finalized++;
        } else if (['Rejected', 'Rejected_By_Company'].includes(status)) {
            statusCounts.rejected++;
        }
    });

    setCount('studentTotalApplicationsCount', statusCounts.total);
    setCount('studentPendingApplicationsCount', statusCounts.pending);
    setCount('studentAcceptedApplicationsCount', statusCounts.accepted);
    setCount('studentConfirmedApplicationsCount', statusCounts.confirmed);
    setCount('studentFinalizedApplicationsCount', statusCounts.finalized);
    setCount('studentRejectedApplicationsCount', statusCounts.rejected);
}

function setCount(elementId, value) {
    const el = document.getElementById(elementId);
    if (el) {
        el.textContent = value;
    }
}
