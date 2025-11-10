
document.addEventListener('DOMContentLoaded', () => {
    const companyId = localStorage.getItem('userId');
    const userRole = localStorage.getItem('userRole');

    if (userRole !== 'company') {
        alert('Access Denied. You must be logged in as a Company.');
        window.location.href = '../index.html';
        return;
    }

    if (!companyId) {
        alert('Company ID not found. Please log in again.');
        window.location.href = '../index.html';
        return;
    }

    loadApplications(companyId);
});

async function loadApplications(companyId) {
    try {
        // This function needs to be added to api.js
        const applications = await api.getCompanyApplications(companyId);
        populateApplicationsTable(applications);
    } catch (error) {
        console.error('Failed to load applications:', error);
        const tableBody = document.getElementById('applicationsTable').querySelector('tbody');
        tableBody.innerHTML = '<tr><td colspan="7" class="text-center">Failed to load applications.</td></tr>';
    }
}

function populateApplicationsTable(applications) {
    const tableBody = document.getElementById('applicationsTable').querySelector('tbody');
    tableBody.innerHTML = ''; // Clear existing placeholder rows

    if (applications.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center">No applications found.</td></tr>';
        return;
    }

    applications.forEach((app, index) => {
        const row = document.createElement('tr');
        row.setAttribute('data-application-id', app.application_id);

        const statusBadge = getStatusBadge(app.status);

        row.innerHTML = `
            <td>${index + 1}</td>
            <td>
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <img src="../bs5/template/assets/images/demo/users/face1.jpg" class="rounded-circle" width="36" height="36" alt="">
                    </div>
                    <div>
                        <a href="#" class="text-body fw-semibold">${app.student_name || 'N/A'}</a>
                        <div class="text-muted fs-sm">${app.student_major || 'N/A'}</div>
                    </div>
                </div>
            </td>
            <td>${app.internship_position || 'N/A'}</td>
            <td>${new Date(app.applied_date).toLocaleDateString()}</td>
            <td>${statusBadge}</td>
            <td class="text-center">
                <div class="dropdown">
                    <button type="button" class="btn btn-outline-light btn-icon btn-sm text-body border-transparent rounded-pill" data-bs-toggle="dropdown">
                        <i class="ph-dots-three-vertical"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a href="#" class="dropdown-item view-application" data-bs-toggle="modal" data-bs-target="#view_application_modal" data-application='${JSON.stringify(app)}'>
                            <i class="ph-eye me-2"></i>View Application
                        </a>
                        <a href="#" class="dropdown-item update-status" data-bs-toggle="modal" data-bs-target="#update_status_modal" data-application-id="${app.application_id}" data-current-status="${app.status}">
                            <i class="ph-pencil me-2"></i>Update Status
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item"><i class="ph-download-simple me-2"></i>Download Resume</a>
                    </div>
                </div>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

function getStatusBadge(status) {
    let badgeClass = 'bg-secondary bg-opacity-20 text-secondary'; // Default
    if (status === 'Offered' || status === 'Accepted') {
        badgeClass = 'bg-success bg-opacity-20 text-success';
    } else if (status === 'Pending Review' || status === 'Under Review') {
        badgeClass = 'bg-warning bg-opacity-20 text-warning';
    } else if (status === 'Rejected') {
        badgeClass = 'bg-danger bg-opacity-20 text-danger';
    }
    return `<span class="badge ${badgeClass}">${status}</span>`;
}

// Event listeners for modals will be added later
