// Finalized statuses - only 'Approved_By_Company' represents finalized (after student confirms and company finalizes)
const FINALIZED_STATUS_SET = new Set(['Approved_By_Company', 'Finalized']);

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
        return `${API_BASE_URL}${path}`;
    }
    return path;
}

function formatStatusLabel(status) {
    if (!status) {
        return 'Unknown';
    }
    const map = {
        'Approved_By_Company': 'Finalized',
        'Finalized': 'Finalized',
        'Confirmed_By_Student': 'Confirmed By Student',
        'Rejected_By_Company': 'Rejected (Internal)',
        'Pending': 'Pending Review'
    };
    return map[status] || status.replace(/_/g, ' ');
}

function renderStatusBadge(status) {
    let badgeClass = 'badge bg-secondary bg-opacity-20 text-secondary';
    if (status === 'Approved_By_Company' || status === 'Finalized') {
        badgeClass = 'badge bg-success bg-opacity-20 text-success';
    } else if (status === 'Confirmed_By_Student') {
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

async function loadFinalizedApplications(companyId) {
    try {
        const applications = await api.getCompanyApplications(companyId);
        // Filter for finalized applications - status should be 'Approved_By_Company' (database value)
        finalizedApplications = applications.filter(app => {
            const status = app.status || '';
            // Check both database value and normalized value
            return status === 'Approved_By_Company' || 
                   status === 'Finalized' ||
                   FINALIZED_STATUS_SET.has(status);
        });
        populateFinalizedTable(finalizedApplications);
    } catch (error) {
        console.error('Failed to load finalized applications:', error);
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
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No finalized internships yet.</td></tr>';
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
                    <button type="button" class="btn btn-outline-light btn-icon btn-sm text-body border-transparent rounded-pill" data-bs-toggle="dropdown">
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
    const studentHeading = document.getElementById('finalizedStudentHeading');
    if (studentHeading) {
        studentHeading.textContent = `Student: ${app.student_name || 'N/A'} (${app.student_major || 'Program N/A'})`;
    }
    const internshipTitle = document.getElementById('finalizedInternshipTitle');
    if (internshipTitle) {
        internshipTitle.textContent = app.internship_position || 'Internship';
    }
    const period = document.getElementById('finalizedPeriod');
    if (period) {
        period.textContent = app.internship_dates || '—';
    }
    const status = document.getElementById('finalizedStatus');
    if (status) {
        status.innerHTML = renderStatusBadge(app.status);
    }
    const offerBlock = document.getElementById('finalizedOfferDetails');
    if (offerBlock) {
        if (app.offer_details) {
            offerBlock.innerHTML = `<div class="alert alert-info">${app.offer_details}</div>`;
        } else {
            offerBlock.innerHTML = '';
        }
    }

    const docsContainer = document.getElementById('finalizedDocuments');
    if (docsContainer) {
        docsContainer.innerHTML = '';
        if (!Array.isArray(app.documents) || !app.documents.length) {
            docsContainer.innerHTML = '<div class="text-muted">No documents attached.</div>';
        } else {
            app.documents.forEach(doc => {
                if (!doc.download_url) {
                    return;
                }
                const downloadUrl = doc.download_url.startsWith('http') ? doc.download_url : `${API_BASE_URL}${doc.download_url}`;
                const item = document.createElement('a');
                item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
                item.href = downloadUrl;
                item.target = '_blank';
                item.rel = 'noopener noreferrer';
                item.innerHTML = `<span>${doc.file_name || 'Document'} (${doc.document_type || 'File'})</span><i class="ph-download-simple"></i>`;
                docsContainer.appendChild(item);
            });
        }
    }
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

    loadFinalizedApplications(currentCompanyId);
});
