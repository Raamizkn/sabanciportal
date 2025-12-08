
let currentCompanyId = null;
let latestApplications = [];
let tableInitAttempts = 0;

document.addEventListener('DOMContentLoaded', () => {
    const userRole = localStorage.getItem('userRole');
    const storedCompanyId = localStorage.getItem('userId');
    const impersonatedType = sessionStorage.getItem('impersonatedUserType');
    const impersonatedId = sessionStorage.getItem('impersonatedUser');
    const isImpersonatedCompany = impersonatedType === 'company' && impersonatedId;

    if (userRole !== 'company' && !isImpersonatedCompany) {
        showAlertModal('Access Denied', 'You must be logged in as a Company.', 'error', () => {
            window.location.href = '../index.html';
        });
        return;
    }

    currentCompanyId = userRole === 'company' ? storedCompanyId : impersonatedId;

    if (!currentCompanyId) {
        showAlertModal('Error', 'Company ID not found. Please log in again.', 'error', () => {
            window.location.href = '../index.html';
        });
        return;
    }

    loadApplications(currentCompanyId);
});

async function loadApplications(companyId) {
    try {
        const applications = await api.getCompanyApplications(companyId);
        latestApplications = Array.isArray(applications) ? applications : [];
        window.companyApplicationsCache = latestApplications.reduce((acc, app) => {
            acc[app.application_id] = app;
            return acc;
        }, {});
        populateApplicationsTable(latestApplications);
        updateApplicationsBadge(latestApplications.length);
    } catch (error) {
        console.error('Failed to load applications:', error);
        const tableBody = document.getElementById('applicationsTableBody');
        if (tableBody) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Failed to load applications.</td></tr>';
        }
        updateApplicationsBadge(0);
    }
}

function updateApplicationsBadge(count) {
    // Update badge in sidebar navigation by ID
    const badge = document.getElementById('applicationsBadge');
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
    const applicationsNavLink = document.querySelector('.nav-link[href="company-applications.html"]');
    if (applicationsNavLink) {
        let badgeElement = applicationsNavLink.querySelector('.badge');
        if (!badgeElement) {
            badgeElement = document.createElement('span');
            badgeElement.className = 'badge bg-primary rounded-pill ms-auto';
            badgeElement.id = 'applicationsBadge';
            applicationsNavLink.appendChild(badgeElement);
        }
        badgeElement.textContent = count;
        // Hide badge if count is 0
        if (count === 0) {
            badgeElement.style.display = 'none';
        } else {
            badgeElement.style.display = '';
        }
    }
}

function isDataTableReady() {
    return window.applicationsTable && typeof window.applicationsTable.clear === 'function';
}

function populateApplicationsTable(applications) {
    if (isDataTableReady()) {
        tableInitAttempts = 0;
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

    if (tableInitAttempts < 20) {
        tableInitAttempts++;
        setTimeout(() => populateApplicationsTable(applications), 150);
        return;
    }

    tableInitAttempts = 0;
    renderStaticApplications(applications);
}

function renderStaticApplications(applications) {
    const table = document.getElementById('applicationsTable');
    if (!table) {
        return;
    }
    const tableBody = table.querySelector('tbody');
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
    const normalized = formatStatusLabel(status);
    if (['Accepted', 'Confirmed'].includes(normalized)) {
        badgeClass = 'bg-success bg-opacity-20 text-success';
    } else if (normalized === 'Pending' || status === 'Pending') {
        badgeClass = 'bg-warning bg-opacity-20 text-warning';
    } else if (normalized.startsWith('Rejected')) {
        badgeClass = 'bg-danger bg-opacity-20 text-danger';
    } else if (normalized === 'Finalize Placement') {
        badgeClass = 'bg-primary bg-opacity-20 text-primary';
    }
    return `<span class="badge ${badgeClass}">${normalized}</span>`;
}

function formatStatusLabel(status) {
    if (!status) {
        return 'Unknown';
    }
    // Map database values to clean display names
    const map = {
        'Confirmed_By_Student': 'Confirmed',
        'Approved_By_Company': 'Finalized'
    };
    return map[status] || status;
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

    const apiBase = typeof API_BASE_URL !== 'undefined' ? API_BASE_URL : '';

    let resumeHTML = '<p class="text-muted mb-0">No resume uploaded.</p>';
    let additionalDocsHTML = '<div class="text-muted">No additional documents attached.</div>';

    if (Array.isArray(documents) && documents.length > 0) {
        const docs = documents.map(doc => {
            const downloadUrl = doc.download_url
                ? (doc.download_url.startsWith('http') ? doc.download_url : `${apiBase}${doc.download_url}`)
                : null;
            return { ...doc, resolved_url: downloadUrl };
        });
        const resumeDoc = docs.find(d => (d.document_type || '').toLowerCase() === 'cv');
        if (resumeDoc && resumeDoc.resolved_url) {
            resumeHTML = `
                <a href="${resumeDoc.resolved_url}" target="_blank" class="btn btn-outline-primary">
                    <i class="ph-download-simple me-2"></i>${resumeDoc.file_name || 'Download Resume'}
                </a>
            `;
        }
        const others = docs.filter(d => (d.document_type || '').toLowerCase() !== 'cv' && d.resolved_url);
        if (others.length > 0) {
            additionalDocsHTML = others.map(doc => `
                <a href="${doc.resolved_url}" target="_blank" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center mb-1">
                    <span>${doc.file_name || 'Document'} (${doc.document_type || 'File'})</span>
                    <i class="ph-download-simple"></i>
                </a>
            `).join('');
        }
    }

    container.innerHTML = `
        <div class="mb-3">
            <h6 class="fw-semibold mb-2">Resume/CV</h6>
            ${resumeHTML}
        </div>
        <hr>
        <h6 class="fw-semibold mb-2">Additional Documents</h6>
        <div class="list-group">
            ${additionalDocsHTML}
        </div>
    `;
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
            <button type="button" class="btn btn-outline-light btn-icon btn-sm text-body border-transparent rounded-pill" data-bs-toggle="dropdown" data-bs-boundary="viewport">
                <i class="ph-dots-three-vertical"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end">
                <a href="#" class="dropdown-item view-application" data-bs-toggle="modal" data-bs-target="#view_application_modal" data-application='${encodedApp}'>
                    <i class="ph-eye me-2"></i>View Application
                </a>
                <div class="dropdown-divider"></div>
                ${resumeLink}
            </div>
        </div>`;
}
