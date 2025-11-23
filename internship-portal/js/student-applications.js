
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
    } catch (error) {
        console.error('Failed to load applications:', error);
        const tableBody = document.querySelector('.datatable-basic tbody');
        tableBody.innerHTML = '<tr><td colspan="7" class="text-center">Failed to load applications.</td></tr>';
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
    let badgeClass = 'bg-secondary';
    switch (status) {
        case 'Confirmed_By_Student':
        case 'Approved_By_Company':
        case 'Accepted':
            badgeClass = 'bg-success';
            break;
        case 'Offered':
            badgeClass = 'bg-info';
            break;
        case 'Pending':
        case 'Pending Review':
        case 'Under Review':
            badgeClass = 'bg-warning text-dark';
            break;
        case 'Shortlisted':
        case 'Interview Scheduled':
            badgeClass = 'bg-primary';
            break;
        case 'Rejected':
        case 'Rejected_By_Company':
            badgeClass = 'bg-danger';
            break;
        case 'Withdrawn':
            badgeClass = 'bg-secondary';
            break;
    }
    return `<span class="badge ${badgeClass}">${formatStatusLabel(status)}</span>`;
}

function getActionButtons(application) {
    let buttons = '';

    // Always show a view button
    buttons += `<a href="student-application-detail.html?id=${application.application_id}" class="btn btn-sm btn-icon btn-light view-button" data-bs-popup="tooltip" title="View Details"><i class="ph-eye"></i></a>`;

    if (application.status === 'Offered') {
        buttons += `<button class="btn btn-sm btn-icon btn-light confirm-offer-btn ms-1" data-application-id="${application.application_id}" data-bs-popup="tooltip" title="Confirm Offer"><i class="ph-check-circle text-success"></i></button>`;
    }

    const withdrawable_statuses = ['Pending Review', 'Under Review', 'Shortlisted', 'Offered'];
    if (withdrawable_statuses.includes(application.status) || application.status === 'Pending') {
        buttons += `<button class="btn btn-sm btn-icon btn-light withdraw-btn ms-1" data-application-id="${application.application_id}" data-bs-popup="tooltip" title="Withdraw Application"><i class="ph-x-circle text-danger"></i></button>`;
    }

    return buttons;
}

function addEventListeners() {
    // Withdraw buttons
    document.querySelectorAll('.withdraw-btn').forEach(button => {
        button.addEventListener('click', async (e) => {
            const appId = e.currentTarget.getAttribute('data-application-id');
            if (confirm('Are you sure you want to withdraw this application?')) {
                try {
                    const result = await api.withdrawApplication(appId);
                    if (result.error) {
                        alert('Error: ' + result.error);
                        return;
                    }
                    alert(result.message || 'Application withdrawn.');
                    loadStudentApplications(localStorage.getItem('userId'));
                } catch (error) {
                    alert('An error occurred while withdrawing the application.');
                }
            }
        });
    });

    // Confirm offer buttons
    document.querySelectorAll('.confirm-offer-btn').forEach(button => {
        button.addEventListener('click', async (e) => {
            const appId = e.currentTarget.getAttribute('data-application-id');
            if (confirm('Are you sure you want to confirm this internship offer? This action cannot be undone.')) {
                try {
                    const result = await api.confirmOffer(appId);
                    if (result.error) {
                        alert('Error: ' + result.error);
                        return;
                    }
                    alert(result.message || 'Offer confirmed!');
                    loadStudentApplications(localStorage.getItem('userId'));
                } catch (error) {
                    alert('An error occurred while confirming the offer.');
                }
            }
        });
    });
}

function updateStatusCards(applications) {
    const statusCounts = {
        total: applications.length,
        accepted: 0,
        pending: 0,
        rejected: 0,
        awaiting: 0,
        approved: 0
    };

    applications.forEach(app => {
        const status = app.status;
        if (['Accepted', 'Confirmed_By_Student'].includes(status)) {
            statusCounts.accepted++;
        } else if (['Pending', 'Pending Review', 'Under Review'].includes(status)) {
            statusCounts.pending++;
        } else if (['Rejected', 'Rejected_By_Company'].includes(status)) {
            statusCounts.rejected++;
        } else if (status === 'Offered') {
            statusCounts.awaiting++;
        } else if (status === 'Approved_By_Company') {
            statusCounts.approved++;
        }
    });

    setCount('studentTotalApplicationsCount', statusCounts.total);
    setCount('studentAcceptedApplicationsCount', statusCounts.accepted);
    setCount('studentPendingApplicationsCount', statusCounts.pending);
    setCount('studentRejectedApplicationsCount', statusCounts.rejected);
    setCount('studentAwaitingApplicationsCount', statusCounts.awaiting);
    setCount('studentApprovedApplicationsCount', statusCounts.approved);
}

function setCount(elementId, value) {
    const el = document.getElementById(elementId);
    if (el) {
        el.textContent = value;
    }
}
