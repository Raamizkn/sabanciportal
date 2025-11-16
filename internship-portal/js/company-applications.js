
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
    if (window.applicationsTable) {
        window.applicationsTable.clear();
        if (!applications.length) {
            window.applicationsTable.row.add([
                '',
                '<div class="text-center w-100">No applications found.</div>',
                '', '', '', ''
            ]);
            window.applicationsTable.draw();
            return;
        }

        applications.forEach((app, index) => {
            const encodedApp = encodeURIComponent(JSON.stringify(app));
            const studentCell = buildStudentCell(app);
            const actionsCell = buildActionsCell(app, encodedApp);
            window.applicationsTable.row.add([
                index + 1,
                studentCell,
                app.internship_position || 'N/A',
                app.applied_date ? new Date(app.applied_date).toLocaleDateString() : '—',
                getStatusBadge(app.status),
                actionsCell
            ]);
        });
        window.applicationsTable.draw();
        return;
    }

    const tableBody = document.getElementById('applicationsTable').querySelector('tbody');
    tableBody.innerHTML = '';

    if (applications.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center">No applications found.</td></tr>';
        return;
    }

    applications.forEach((app, index) => {
        const row = document.createElement('tr');
        const encodedApp = encodeURIComponent(JSON.stringify(app));
        row.innerHTML = `
            <td>${index + 1}</td>
            <td>${buildStudentCell(app)}</td>
            <td>${app.internship_position || 'N/A'}</td>
            <td>${app.applied_date ? new Date(app.applied_date).toLocaleDateString() : '—'}</td>
            <td>${getStatusBadge(app.status)}</td>
            <td class="text-center">${buildActionsCell(app, encodedApp)}</td>
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

function resolveProfileImage(path) {
    if (!path) {
        return '../bs5/template/assets/images/demo/users/face1.jpg';
    }
    if (path.startsWith('http')) {
        return path;
    }
    if (path.startsWith('/')) {
        return `${API_BASE_URL}${path}`;
    }
    return path;
}

function renderApplicationDocuments(documents) {
    const container = document.getElementById('applicationDocumentsContainer');
    if (!container) {
        return;
    }
    container.innerHTML = '';

    if (!Array.isArray(documents) || documents.length === 0) {
        container.innerHTML = '<div class="text-muted">No additional documents attached.</div>';
        return;
    }

    documents.forEach(doc => {
        const link = document.createElement('a');
        link.className = 'd-flex justify-content-between align-items-center list-group-item list-group-item-action mb-1';
        link.target = '_blank';
        link.href = doc.download_url ? `${API_BASE_URL}${doc.download_url}` : '#';
        link.rel = 'noopener noreferrer';
        link.textContent = `${doc.file_name || 'Document'} (${doc.document_type || 'File'})`;
        if (!doc.download_url) {
            link.classList.add('disabled', 'text-muted');
            link.removeAttribute('href');
        }
        container.appendChild(link);
    });
}

function buildStudentCell(app) {
    const profileImg = resolveProfileImage(app.student_profile_pic);
    return `
        <div class="d-flex align-items-center">
            <div class="me-3">
                <img src="${profileImg}" class="rounded-circle" width="36" height="36" alt="">
            </div>
            <div>
                <span class="text-body fw-semibold">${app.student_name || 'N/A'}</span>
                <div class="text-muted fs-sm">${app.student_major || 'N/A'}</div>
            </div>
        </div>`;
}

function buildActionsCell(app, encodedApp) {
    const resumeLink = app.resume_download_url
        ? `<a href="${API_BASE_URL}${app.resume_download_url}" target="_blank" class="dropdown-item"><i class="ph-download-simple me-2"></i>Download Resume</a>`
        : '<span class="dropdown-item disabled text-muted"><i class="ph-download-simple me-2"></i>No Resume</span>';

    return `
        <div class="dropdown">
            <button type="button" class="btn btn-outline-light btn-icon btn-sm text-body border-transparent rounded-pill" data-bs-toggle="dropdown">
                <i class="ph-dots-three-vertical"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end">
                <a href="#" class="dropdown-item view-application" data-bs-toggle="modal" data-bs-target="#view_application_modal" data-application='${encodedApp}'>
                    <i class="ph-eye me-2"></i>View Application
                </a>
                <a href="#" class="dropdown-item update-status" data-bs-toggle="modal" data-bs-target="#update_status_modal" data-application-id="${app.application_id}" data-current-status="${app.status}">
                    <i class="ph-pencil me-2"></i>Update Status
                </a>
                <div class="dropdown-divider"></div>
                ${resumeLink}
            </div>
        </div>`;
}
