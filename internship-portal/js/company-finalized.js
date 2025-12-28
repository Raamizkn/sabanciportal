// Confirmed statuses - Confirmed is the final status (no Finalized anymore)
const CONFIRMED_STATUS_SET = new Set(['Confirmed', 'Confirmed_By_Student']);

let currentCompanyId = null;
let finalizedApplications = [];

function resolveProfileImage(path) {
    if (!path) {
        return '../bs5/template/assets/images/demo/users/face1.jpg';
    }
    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path;
    }
    if (path.startsWith('/')) {
        // API_BASE_URL is defined in api.js which is loaded before this file
        const apiBase = typeof API_BASE_URL !== 'undefined' ? API_BASE_URL : '';
        return `${apiBase}${path}`;
    }
    return path;
}

function formatStatusLabel(status) {
    if (!status) {
        return 'Unknown';
    }
    // Map database values to clean display names
    const map = {
        // Approved_By_Company removed - Confirmed is final
        'Confirmed_By_Student': 'Confirmed'
    };
    return map[status] || status;
}

function renderStatusBadge(status) {
    let badgeClass = 'badge bg-secondary bg-opacity-20 text-secondary';
    // Check both display and database values
    if (status === 'Confirmed' || status === 'Confirmed_By_Student') {
        badgeClass = 'badge bg-success bg-opacity-20 text-success';
    } else if (status === 'Confirmed' || status === 'Confirmed_By_Student') {
        badgeClass = 'badge bg-info bg-opacity-20 text-info';
    }
    return `<span class="${badgeClass}">${formatStatusLabel(status)}</span>`;
}

function splitTimeline(range) {
    if (!range || !range.includes('-')) {
        return { start: range || '—', end: '—' };
    }
    const parts = range.split('-');
    return {
        start: parts[0].trim(),
        end: parts.slice(1).join('-').trim() || '—'
    };
}

async function loadConfirmedApplications(companyId) {
    try {
        const applications = await api.getCompanyApplications(companyId);
        // Filter for confirmed applications (final status)
        finalizedApplications = applications.filter(app => {
            const status = app.status || '';
            return status === 'Confirmed' || status === 'Confirmed_By_Student';
        });
        populateFinalizedTable(finalizedApplications);
    } catch (error) {
        console.error('Failed to load confirmed applications:', error);
        const tbody = document.getElementById('finalizedApplicationsBody');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Unable to load applications.</td></tr>';
        }
    }
}

function populateFinalizedTable(applications) {
    const tbody = document.getElementById('finalizedApplicationsBody');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (!applications.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No confirmed internships yet.</td></tr>';
        return;
    }

    applications.forEach((app, index) => {
        const encodedApp = encodeURIComponent(JSON.stringify(app));
        const timeline = splitTimeline(app.internship_dates);
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${index + 1}</td>
            <td>
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <img src="${resolveProfileImage(app.student_profile_pic)}" class="rounded-circle" width="36" height="36" alt="">
                    </div>
                    <div>
                        <a href="javascript:void(0);" class="text-body fw-semibold">${app.student_name || 'Student'}</a>
                        <div class="text-muted fs-sm">${app.student_major || 'Major not set'}</div>
                    </div>
                </div>
            </td>
            <td>${app.internship_position || 'N/A'}</td>
            <td>${timeline.start}</td>
            <td>${timeline.end}</td>
            <td>${renderStatusBadge(app.status)}</td>
            <td class="text-center">
                <div class="dropdown">
                    <button type="button" class="btn btn-outline-light btn-icon btn-sm text-body border-transparent rounded-pill" data-bs-toggle="dropdown" data-bs-boundary="viewport">
                        <i class="ph-dots-three-vertical"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view_finalized_details_modal" data-application='${encodedApp}'><i class="ph-eye me-2"></i>View Details</a>
                        <a href="company-evaluations.html?application_id=${encodeURIComponent(app.application_id)}" class="dropdown-item"><i class="ph-star me-2"></i>Submit Evaluation</a>
                    </div>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function renderModal(app) {
    const modalBody = document.getElementById('finalizedModalBody');
    if (!modalBody) return;

    const studentProfileImg = resolveProfileImage(app.student_profile_pic);
    const statusBadge = renderStatusBadge(app.status);
    
    // Build documents HTML
    let documentsHTML = '';
    if (Array.isArray(app.documents) && app.documents.length > 0) {
        app.documents.forEach(doc => {
            if (doc.download_url) {
                const apiBase = typeof API_BASE_URL !== 'undefined' ? API_BASE_URL : '';
                const downloadUrl = doc.download_url.startsWith('http') ? doc.download_url : `${apiBase}${doc.download_url}`;
                documentsHTML += `
                    <a href="${downloadUrl}" target="_blank" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span>${doc.file_name || 'Document'} (${doc.document_type || 'File'})</span>
                        <i class="ph-download-simple"></i>
                    </a>
                `;
            }
        });
    } else {
        documentsHTML = '<div class="text-muted">No additional documents attached.</div>';
    }

    modalBody.innerHTML = `
        <div class="row">
            <div class="col-lg-8">
                <!-- Student Information Card -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Student Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="me-3">
                                <img src="${studentProfileImg}" class="rounded-circle" width="80" height="80" alt="Student Profile">
                            </div>
                            <div>
                                <h5 class="mb-0">${app.student_name || 'N/A'}</h5>
                                <p class="mb-0 text-muted">${app.student_major || 'N/A'}</p>
                                <p class="mb-0"><i class="ph-envelope me-1"></i> ${app.student_email || 'N/A'}</p>
                            </div>
                        </div>
                        <hr>
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Student ID:</dt>
                            <dd class="col-sm-8">${app.student_id || 'N/A'}</dd>
                            <dt class="col-sm-4">Phone:</dt>
                            <dd class="col-sm-8">${app.student_phone || 'N/A'}</dd>
                            <dt class="col-sm-4">GPA:</dt>
                            <dd class="col-sm-8">${app.student_gpa || 'N/A'}</dd>
                        </dl>
                    </div>
                </div>

                <!-- Internship Information Card -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Internship Information</h6>
                    </div>
                    <div class="card-body">
                        <h5 class="mb-1">${app.internship_position || 'Internship'}</h5>
                        <p class="text-muted mb-3"><i class="ph-building me-1"></i> ${app.company_name || 'Company'}</p>
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Location:</dt>
                            <dd class="col-sm-8">${app.internship_location || 'Not specified'}</dd>
                            <dt class="col-sm-4">Duration:</dt>
                            <dd class="col-sm-8">${app.internship_dates || 'Not specified'}</dd>
                        </dl>
                    </div>
                </div>

                <!-- Cover Letter Card -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Cover Letter</h6>
                    </div>
                    <div class="card-body">
                        <div style="margin-bottom: 0;">${app.cover_letter || '<p class="text-muted">No cover letter provided.</p>'}</div>
                    </div>
                </div>

                <!-- Documents Card -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Attached Documents</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6 class="fw-semibold mb-2">Resume/CV</h6>
                            ${app.resume_download_url ? `
                                <a href="${typeof API_BASE_URL !== 'undefined' ? API_BASE_URL : ''}${app.resume_download_url}" target="_blank" class="btn btn-outline-primary">
                                    <i class="ph-download-simple me-2"></i>${app.resume_file_name || 'Download Resume'}
                                </a>
                            ` : '<p class="text-muted mb-0">No resume uploaded.</p>'}
                        </div>
                        <hr>
                        <h6 class="fw-semibold mb-2">Additional Documents</h6>
                        <div class="list-group">
                            ${documentsHTML}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Application Status Card -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Application Status</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            ${statusBadge}
                        </div>
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Application ID:</dt>
                            <dd class="col-sm-7">${app.application_id || 'N/A'}</dd>
                            <dt class="col-sm-5">Applied On:</dt>
                            <dd class="col-sm-7">${app.applied_date ? new Date(app.applied_date).toLocaleDateString() : 'N/A'}</dd>
                            <dt class="col-sm-5">Confirmed On:</dt>
                            <dd class="col-sm-7">${app.status_updated_date ? new Date(app.status_updated_date).toLocaleDateString() : 'N/A'}</dd>
                        </dl>
                        ${app.offer_details ? `
                            <hr>
                            <h6 class="fw-semibold mb-2">Offer Details</h6>
                            <div class="alert alert-info mb-0">${app.offer_details}</div>
                        ` : ''}
                    </div>
                </div>

                <!-- Evaluation Card -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Evaluation</h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-0">
                            Evaluation not submitted yet. <a href="company-evaluations.html?application_id=${encodeURIComponent(app.application_id)}" class="alert-link">Submit Evaluation</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function attachModalHandler() {
    const modalEl = document.getElementById('view_finalized_details_modal');
    if (!modalEl) {
        return;
    }
    modalEl.addEventListener('show.bs.modal', event => {
        const trigger = event.relatedTarget;
        if (!trigger) {
            return;
        }
        const encodedApp = trigger.getAttribute('data-application');
        if (!encodedApp) {
            return;
        }
        try {
            const app = JSON.parse(decodeURIComponent(encodedApp));
            renderModal(app);
        } catch (error) {
            console.error('Failed to parse application payload:', error);
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    attachModalHandler();
    const userRole = localStorage.getItem('userRole');
    const storedCompanyId = localStorage.getItem('userId');
    const impersonatedType = sessionStorage.getItem('impersonatedUserType');
    const impersonatedId = sessionStorage.getItem('impersonatedUser');
    const isImpersonatedCompany = impersonatedType === 'company' && impersonatedId;

    if (userRole !== 'company' && !isImpersonatedCompany) {
        alert('Access denied. Redirecting to login page.');
        window.location.href = '../index.html';
        return;
    }

    currentCompanyId = userRole === 'company' ? storedCompanyId : impersonatedId;
    if (!currentCompanyId) {
        alert('Company account missing. Please login again.');
        window.location.href = '../index.html';
        return;
    }

    loadConfirmedApplications(currentCompanyId);
});
