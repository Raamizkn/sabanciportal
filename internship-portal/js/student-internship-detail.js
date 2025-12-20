let detailInternship = null;
let studentIdForApply = null;
let availableDocuments = [];
let coverLetterEditor = null;

function attachLogoutHandlers() {
    const logoutLinks = document.querySelectorAll('.logout-link');
    logoutLinks.forEach(link => {
        link.addEventListener('click', () => {
            localStorage.removeItem('userRole');
            localStorage.removeItem('userId');
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    attachLogoutHandlers();
    const storedRole = localStorage.getItem('userRole');
    const storedStudentId = localStorage.getItem('userId');
    const impersonatedType = sessionStorage.getItem('impersonatedUserType');
    const impersonatedId = sessionStorage.getItem('impersonatedUser');
    const isImpersonatedStudent = impersonatedType === 'student' && impersonatedId;
    const isAdmin = storedRole === 'admin';

    // When admin is impersonating a student, treat them as the student for application purposes
    const isActingAsStudent = storedRole === 'student' || isImpersonatedStudent;
    const isAdminViewOnly = isAdmin && !isImpersonatedStudent;

    // Allow access if: student user, impersonated student, or admin
    if (storedRole !== 'student' && !isImpersonatedStudent && !isAdmin) {
        alert('Access denied. Redirecting to login page.');
        window.location.href = '../index.html';
        return;
    }

    // Add admin navigation bar if accessed by admin WITHOUT impersonation
    if (isAdminViewOnly) {
        addAdminNavigationBar();
        // Hide apply button for admin view (not impersonating)
        const applyButtons = document.querySelectorAll('[data-bs-target="#apply_now_modal"]');
        applyButtons.forEach(btn => {
            btn.style.display = 'none';
        });
    }

    // Set student ID: use impersonated ID if impersonating, otherwise use logged-in student ID
    studentIdForApply = isImpersonatedStudent ? impersonatedId : storedStudentId;
    const params = new URLSearchParams(window.location.search);
    const internshipId = params.get('id');

    if (!internshipId) {
        showDetailAlert('Internship not specified. Please return to the listings and select an opportunity.', 'danger');
        disableApplyButton();
        return;
    }

    loadInternship(internshipId);

    // Load documents and student summary for students (including impersonated students)
    if (studentIdForApply && isActingAsStudent) {
        loadStudentDocuments(studentIdForApply);
        populateStudentSummary();
    } else if (isAdminViewOnly) {
        document.getElementById('documentHelper').textContent = 'Admin view mode - Application features disabled.';
    } else {
        document.getElementById('documentHelper').textContent = 'Login to attach your documents.';
    }

    // Allow form submission for students (including impersonated students)
    const applicationForm = document.getElementById('application-form');
    if (applicationForm && isActingAsStudent) {
        applicationForm.addEventListener('submit', submitApplication);
    }

    // Initialize Quill editor for cover letter
    const applyModal = document.getElementById('apply_now_modal');
    if (applyModal) {
        applyModal.addEventListener('shown.bs.modal', () => {
            if (!coverLetterEditor) {
                coverLetterEditor = new Quill('#applyCoverLetter', {
                    theme: 'snow',
                    modules: {
                        toolbar: [
                            [{ 'header': [1, 2, 3, false] }],
                            ['bold', 'italic', 'underline'],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            ['link'],
                            ['clean']
                        ]
                    },
                    placeholder: 'Write a short cover letter highlighting why you\'re a great fit...'
                });
                
                // Make the editor container resizable
                const editorContainer = document.querySelector('#applyCoverLetter .ql-container');
                if (editorContainer) {
                    editorContainer.style.minHeight = '300px';
                    editorContainer.style.resize = 'vertical';
                    editorContainer.style.overflow = 'auto';
                }
            }
        });
    }
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
                <span><strong>Admin View:</strong> Viewing internship details (Admin Mode - Read Only)</span>
            </div>
            <div>
                <a href="../admin/admin-internships.html" class="btn btn-sm btn-light me-2">
                    <i class="ph-arrow-left me-1"></i>Back to Internships
                </a>
                <a href="../admin/admin-dashboard.html" class="btn btn-sm btn-light">
                    <i class="ph-house me-1"></i>Admin Dashboard
                </a>
            </div>
        </div>
    `;
    document.body.insertBefore(adminNav, document.body.firstChild);
}

function showDetailAlert(message, state = 'danger') {
    const alertEl = document.getElementById('detail-alert');
    if (!alertEl) return;
    if (!message) {
        alertEl.classList.add('d-none');
        alertEl.textContent = '';
        return;
    }
    alertEl.className = `alert alert-${state}`;
    alertEl.textContent = message;
    alertEl.classList.remove('d-none');
}

function disableApplyButton() {
    const buttons = document.querySelectorAll('[data-bs-target="#apply_now_modal"]');
    buttons.forEach(btn => {
        btn.setAttribute('disabled', 'disabled');
        btn.classList.add('disabled');
        btn.title = 'You cannot apply right now.';
    });
}

async function loadInternship(internshipId) {
    try {
        const internship = await api.getInternshipDetails(internshipId);
        detailInternship = internship;
        populateInternshipDetails(internship);

        // Check application limits for student (including impersonated students)
        const userRole = localStorage.getItem('userRole');
        const impersonatedType = sessionStorage.getItem('impersonatedUserType');
        const impersonatedId = sessionStorage.getItem('impersonatedUser');
        const isImpersonatedStudent = impersonatedType === 'student' && impersonatedId;
        const studentId = isImpersonatedStudent ? impersonatedId : (userRole === 'student' ? localStorage.getItem('userId') : null);
        
        // Check limits for students and impersonated students
        if (studentId && (userRole === 'student' || isImpersonatedStudent)) {
            try {
                const limits = await api.getStudentApplicationLimits(studentId);
                if (limits && limits.active_round) {
                    const { active_count, remaining, limit_reached, active_round } = limits;
                    const max = active_round.max_applications;
                    
                    if (limit_reached) {
                        disableApplyButton();
                        showDetailAlert(`Application limit reached: ${active_count} / ${max} applications used for ${active_round.name}. Withdraw or wait for decisions to free slots.`, 'warning');
                    } else if (remaining <= 1) {
                        showDetailAlert(`Application limit: ${active_count} / ${max} used. Only ${remaining} slot${remaining === 1 ? '' : 's'} remaining for ${active_round.name}.`, 'info');
                    } else {
                        // Show limit info but don't disable button
                        const limitInfo = document.getElementById('applicationLimitInfo');
                        if (limitInfo) {
                            limitInfo.textContent = `${active_count} / ${max} applications used. ${remaining} remaining.`;
                            limitInfo.style.display = 'block';
                        }
                    }
                }
            } catch (e) {
                console.warn('Could not check application limits', e);
            }
        }
    } catch (error) {
        console.error('Failed to load internship detail:', error);
        showDetailAlert('Unable to load internship details. Please try again later.', 'danger');
        disableApplyButton();
    }
}

function populateInternshipDetails(data) {
    if (!data) return;
    const company = data.company || {};
    setText('pageInternshipTitle', data.position || data.title || 'Internship');
    setText('detailTitle', data.position || data.title || 'Internship');
    setText('detailCompanyName', company.name || data.company_name || 'Company');
    setImage('detailCompanyLogo', company.logo);
    setText('jobDescription', data.description || 'Description unavailable.');

    renderList('requirementsList', parseList(data.requirements));
    renderBadges('skillsBadges', parseList(data.requirements));

    setText('companyCardName', company.name || 'the company');
    setText('companyDescription', company.description || 'Company information is not available.');
    setText('companyIndustry', company.industry || '—');
    setLink('companyWebsite', company.website);
    setText('companyContact', company.phone || '—');
    setText('companyAddress', company.address || '—');

    setText('overviewLocation', data.location || 'Not specified');
    setText('overviewSchedule', data.dates || 'TBD');
    setText('overviewDeadline', data.application_deadline || 'Not provided');
    setText('overviewCompensation', data.salary || 'Not specified');
    setText('overviewType', data.type || 'Full-time');
    
    // Set seats left information
    const seatsLeftEl = document.getElementById('overviewSeatsLeft');
    if (seatsLeftEl) {
        if (data.seats_left !== null && data.seats_left !== undefined) {
            if (data.is_full) {
                seatsLeftEl.innerHTML = '<span class="text-danger fw-semibold">Full - No seats available</span>';
            } else {
                seatsLeftEl.innerHTML = `<span class="text-success fw-semibold">${data.seats_left} seat${data.seats_left === 1 ? '' : 's'} left</span>`;
            }
        } else {
            seatsLeftEl.innerHTML = '<span class="text-muted">Not available</span>';
        }
    }

    updateApplyModalHeading(data, company);
}

function parseList(value) {
    if (!value) return [];
    return value.split(/\r?\n|,/).map(item => item.trim()).filter(Boolean);
}

function renderList(elementId, items) {
    const list = document.getElementById(elementId);
    if (!list) return;
    list.innerHTML = '';
    if (!items.length) {
        const li = document.createElement('li');
        li.className = 'text-muted';
        li.textContent = 'No details provided.';
        list.appendChild(li);
        return;
    }
    items.forEach(item => {
        const li = document.createElement('li');
        li.innerHTML = `<i class="ph-check text-success me-2"></i>${item}`;
        list.appendChild(li);
    });
}

function renderBadges(elementId, items) {
    const container = document.getElementById(elementId);
    if (!container) return;
    container.innerHTML = '';
    if (!items.length) {
        container.innerHTML = '<span class="text-muted">No specific skills listed.</span>';
        return;
    }
    items.slice(0, 6).forEach(item => {
        const badge = document.createElement('span');
        badge.className = 'badge bg-light text-body me-1 mb-1';
        badge.textContent = item;
        container.appendChild(badge);
    });
}

function setText(elementId, value) {
    const el = document.getElementById(elementId);
    if (el) {
        el.textContent = value || '—';
    }
}

function setLink(elementId, url) {
    const el = document.getElementById(elementId);
    if (!el) return;
    if (url) {
        el.textContent = url;
        el.href = url;
    } else {
        el.textContent = '—';
        el.removeAttribute('href');
    }
}

function setImage(elementId, path) {
    const el = document.getElementById(elementId);
    if (!el) return;
    if (!path) {
        el.src = '../assets/images/logo.svg';
        return;
    }
    if (path.startsWith('http')) {
        el.src = path;
    } else if (path.startsWith('/')) {
        el.src = `${API_BASE_URL}${path}`;
    } else {
        el.src = path;
    }
}

function updateApplyModalHeading(internship, company) {
    const titleEl = document.getElementById('applyModalTitle');
    const introEl = document.getElementById('applyIntroText');
    if (titleEl) {
        titleEl.textContent = `Apply for ${internship.position || internship.title || 'Internship'}`;
    }
    if (introEl) {
        const companyName = company.name || internship.company_name || 'the company';
        introEl.innerHTML = `You are applying to <strong>${companyName}</strong>. Select the documents you want to include before submitting.`;
    }
}

async function loadStudentDocuments(studentId) {
    try {
        const docs = await api.getStudentDocuments(studentId);
        availableDocuments = Array.isArray(docs) ? docs : [];
        renderDocumentOptions();
    } catch (error) {
        console.error('Failed to load documents:', error);
        renderDocumentOptions([]);
    }
}

function renderDocumentOptions() {
    const container = document.getElementById('documentOptions');
    if (!container) return;
    container.innerHTML = '';
    if (!availableDocuments.length) {
        container.innerHTML = '<div class="text-muted">No uploaded documents found. Visit the Documents page to upload files.</div>';
        return;
    }
    availableDocuments.forEach(doc => {
        const item = document.createElement('label');
        item.className = 'list-group-item d-flex align-items-center gap-2';
        item.innerHTML = `
            <input type="checkbox" class="form-check-input document-checkbox" value="${doc.document_id}">
            <div>
                <div class="fw-semibold">${doc.file_name}</div>
                <div class="text-muted small">${doc.document_type || 'Document'} · ${formatFileSize(doc.file_size)} · ${doc.upload_date || ''}</div>
            </div>
        `;
        container.appendChild(item);
    });
}

function formatFileSize(size) {
    if (!size || isNaN(size)) return 'Unknown size';
    if (size < 1024) return `${size} B`;
    if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} KB`;
    return `${(size / (1024 * 1024)).toFixed(1)} MB`;
}

async function populateStudentSummary() {
    try {
        const profile = await api.getStudentProfile(studentIdForApply);
        const student = profile.student || profile;
        setText('applyStudentName', student.name || localStorage.getItem('userName') || 'Student');
        setText('applyStudentMajor', student.major || '—');
        setText('applyStudentGpa', student.gpa || '—');
    } catch (error) {
        console.warn('Unable to load student summary:', error);
        setText('applyStudentName', localStorage.getItem('userName') || 'Student');
    }
}

async function submitApplication(event) {
    event.preventDefault();
    if (!detailInternship) {
        showApplicationFeedback('Internship information is missing.', 'danger');
        return;
    }
    if (!studentIdForApply) {
        showApplicationFeedback('Student account missing. Please log in again.', 'danger');
        return;
    }
    const coverLetter = coverLetterEditor ? coverLetterEditor.root.innerHTML : '';
    const selectedDocIds = Array.from(document.querySelectorAll('.document-checkbox:checked')).map(input => input.value);
    const submitBtn = document.getElementById('applySubmitButton');
    submitBtn.disabled = true;
    showApplicationFeedback('Submitting your application…', 'info');
    try {
        const response = await api.applyForInternship(studentIdForApply, detailInternship.id, coverLetter, selectedDocIds);
        if (response && response.status === 'success') {
            showApplicationFeedback('Application submitted successfully!', 'success');
            setTimeout(() => {
                const modalElement = document.getElementById('apply_now_modal');
                if (modalElement) {
                    const modalInstance = bootstrap.Modal.getInstance(modalElement);
                    modalInstance?.hide();
                }
                showApplicationFeedback(null);
                if (coverLetterEditor) {
                    coverLetterEditor.setContents([]);
                }
                document.querySelectorAll('.document-checkbox').forEach(cb => cb.checked = false);
                showDetailAlert('Application submitted successfully.', 'success');
            }, 800);
        } else {
            throw new Error(response?.error || 'Submission failed');
        }
    } catch (error) {
        console.error('Failed to submit application:', error);
        showApplicationFeedback(error.message || 'Failed to submit application. Please try again.', 'danger');
    } finally {
        submitBtn.disabled = false;
    }
}

function showApplicationFeedback(message, state) {
    const feedbackEl = document.getElementById('applyModalFeedback');
    if (!feedbackEl) return;
    if (!message) {
        feedbackEl.classList.add('d-none');
        feedbackEl.textContent = '';
        return;
    }
    const stateClass = state === 'success' ? 'alert-success' : state === 'danger' ? 'alert-danger' : 'alert-info';
    feedbackEl.className = `alert ${stateClass}`;
    feedbackEl.textContent = message;
    feedbackEl.classList.remove('d-none');
}
