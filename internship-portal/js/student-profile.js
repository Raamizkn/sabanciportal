const profileDefaults = {
    avatar: '../bs5/template/assets/images/demo/users/face11.jpg'
};

let currentProfile = null;

document.addEventListener('DOMContentLoaded', () => {
    attachLogoutHandlers();
    handleImpersonationBanner();
    setupProfileForm();

    const storedRole = localStorage.getItem('userRole');
    const storedStudentId = localStorage.getItem('userId');
    const impersonatedType = sessionStorage.getItem('impersonatedUserType');
    const impersonatedId = sessionStorage.getItem('impersonatedUser');
    const isImpersonatingStudent = impersonatedType === 'student' && !!impersonatedId;
    const isAdmin = storedRole === 'admin';
    
    // Get student ID from URL parameter (for admin viewing)
    const urlParams = new URLSearchParams(window.location.search);
    const urlStudentId = urlParams.get('id');
    
    // Determine which student ID to use: URL param > impersonated > stored
    let targetStudentId = urlStudentId || (storedRole === 'student' ? storedStudentId : impersonatedId);

    if (!targetStudentId || targetStudentId === 'undefined') {
        alert('Student account not detected. Redirecting to login page.');
        window.location.href = '../index.html';
        return;
    }

    // Allow access if: student user, impersonated student, or admin
    if (storedRole !== 'student' && !isImpersonatingStudent && !isAdmin) {
        alert('Access denied. Redirecting to login page.');
        window.location.href = '../index.html';
        return;
    }

    // Admin viewing mode (not impersonating)
    const isAdminViewMode = isAdmin && !isImpersonatingStudent && urlStudentId;
    
    // Add admin navigation bar if accessed by admin (viewing, not impersonating)
    if (isAdminViewMode) {
        addAdminNavigationBar();
        disableEditing();
    }

    loadStudentProfile(targetStudentId);
    
    // Only setup resume upload if NOT in admin view mode
    if (!isAdminViewMode) {
        setupResumeUpload();
    }
});

// Add admin navigation bar at the top of the page
function addAdminNavigationBar() {
    const adminNav = document.createElement('div');
    adminNav.id = 'admin-view-bar';
    adminNav.style.cssText = 'position: sticky; top: 0; z-index: 1030; background: #0d6efd; color: white; padding: 10px 15px;';
    adminNav.innerHTML = `
        <div class="container-fluid d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <i class="ph-eye me-2"></i>
                <span><strong>Admin View:</strong> Viewing student profile (Read-Only)</span>
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

// Disable editing for admin view mode
function disableEditing() {
    // Hide edit profile button
    const editButtons = document.querySelectorAll('[data-bs-target="#edit_profile"], .edit-btn');
    editButtons.forEach(btn => {
        btn.style.display = 'none';
    });
    
    // Hide upload resume button
    const uploadButton = document.getElementById('uploadResumeButton');
    if (uploadButton) {
        uploadButton.style.display = 'none';
    }
    
    // Make inputs read-only after page loads
    setTimeout(() => {
        const formInputs = document.querySelectorAll('input, textarea, select');
        formInputs.forEach(input => {
            input.setAttribute('readonly', true);
            input.setAttribute('disabled', true);
            input.style.pointerEvents = 'none';
            input.style.opacity = '0.7';
        });
    }, 500);
}

function attachLogoutHandlers() {
    const logoutLinks = document.querySelectorAll('.logout-link');
    logoutLinks.forEach(link => {
        link.addEventListener('click', () => {
            localStorage.removeItem('userRole');
            localStorage.removeItem('userId');
        });
    });
}

function handleImpersonationBanner() {
    const impersonatedUser = sessionStorage.getItem('impersonatedUser');
    const impersonatedEmail = sessionStorage.getItem('impersonatedUserEmail');
    const impersonatedType = sessionStorage.getItem('impersonatedUserType');
    const impersonatedUserName = sessionStorage.getItem('impersonatedUserName');

    if (impersonatedUser && impersonatedEmail && impersonatedType === 'student') {
        const impersonationBanner = document.getElementById('impersonation-banner');
        const impersonatedUserBanner = document.getElementById('impersonated-user-banner');
        const endButton = document.getElementById('end-impersonation-banner');

        if (impersonationBanner && impersonatedUserBanner) {
            impersonatedUserBanner.textContent = impersonatedEmail;
            impersonationBanner.classList.remove('d-none');
        }

        if (impersonatedUserName) {
            const nameElement = document.getElementById('nav-student-name');
            if (nameElement) {
                nameElement.textContent = impersonatedUserName;
            }
        }

        if (endButton) {
            endButton.addEventListener('click', () => {
                sessionStorage.removeItem('impersonatedUser');
                sessionStorage.removeItem('impersonatedUserEmail');
                sessionStorage.removeItem('impersonatedUserType');
                sessionStorage.removeItem('impersonatedUserName');
                window.location.href = '../admin/admin-dashboard.html';
            });
        }
    }
}

async function loadStudentProfile(studentId) {
    showPageAlert(null);
    try {
        const profile = await api.getStudentProfile(studentId);
        currentProfile = profile.student || profile;
        populateProfile(currentProfile);
    } catch (error) {
        console.error('Failed to load student profile:', error);
        showPageAlert('Unable to load profile information. Please refresh the page.', 'danger');
    }
}

function populateProfile(student) {
    if (!student) {
        return;
    }

    setTextContent('student-name', student.name || 'Student');
    setTextContent('student-full-name', student.name || 'Student');
    setTextContent('student-major', student.major || 'Student');
    setTextContent('student-bio', student.bio || 'Add a short bio to tell companies more about yourself.');
    setTextContent('student-email', student.email || 'Not provided');
    setTextContent('student-phone', student.phone || 'Not provided');

    setTextContent('student-degree', student.major ? student.major : 'Degree not specified');
    setTextContent('student-gpa', student.gpa ? `GPA: ${student.gpa}` : 'GPA: Not provided');

    const emailLink = document.getElementById('contact-email');
    if (emailLink) {
        if (student.email) {
            emailLink.textContent = student.email;
            emailLink.href = `mailto:${student.email}`;
        } else {
            emailLink.textContent = 'Not provided';
            emailLink.removeAttribute('href');
        }
    }

    const phoneLink = document.getElementById('contact-phone');
    if (phoneLink) {
        if (student.phone) {
            phoneLink.textContent = student.phone;
            phoneLink.href = `tel:${student.phone.replace(/[^0-9+]/g, '')}`;
        } else {
            phoneLink.textContent = 'Not provided';
            phoneLink.removeAttribute('href');
        }
    }

    updateAvatar('profile-avatar', student.profile_pic);
    updateAvatar('nav-student-avatar', student.profile_pic, true);
    if (student.name) {
        setTextContent('nav-student-name', student.name);
    }

    updateResumeUI(student.resume || null);
    populateEditModal(student);
}

function setTextContent(elementId, value) {
    const el = document.getElementById(elementId);
    if (el) {
        el.textContent = value || 'Not provided';
    }
}

function updateAvatar(elementId, avatarPath, isSmall = false) {
    const img = document.getElementById(elementId);
    if (!img) {
        return;
    }
    const source = resolveMediaPath(avatarPath) || profileDefaults.avatar;
    img.src = source;
    if (isSmall) {
        img.alt = 'Student avatar';
    }
}

function updateResumeUI(resume) {
    const resumeNameEl = document.getElementById('resume-file-name');
    const resumeUpdatedEl = document.getElementById('resume-updated');
    const statusBadge = document.getElementById('resume-status');

    if (!resume) {
        if (resumeNameEl) {
            resumeNameEl.textContent = 'No resume uploaded';
            resumeNameEl.removeAttribute('href');
            resumeNameEl.classList.add('text-muted');
        }
        if (resumeUpdatedEl) {
            resumeUpdatedEl.textContent = 'Upload your resume to enhance your applications.';
        }
        resetDownloadButton(true);
        if (statusBadge) {
            statusBadge.textContent = 'Missing';
            statusBadge.classList.remove('bg-success');
            statusBadge.classList.add('bg-warning', 'text-dark');
        }
        return;
    }

    if (resumeNameEl) {
        resumeNameEl.textContent = resume.file_name || 'Resume';
        if (resume.download_url) {
            resumeNameEl.href = `${API_BASE_URL}${resume.download_url}`;
            resumeNameEl.classList.remove('text-muted');
        }
    }

    if (resumeUpdatedEl) {
        const uploadedDate = resume.upload_date ? new Date(resume.upload_date) : null;
        resumeUpdatedEl.textContent = uploadedDate ? `Uploaded ${uploadedDate.toLocaleDateString()}` : 'Uploaded recently';
    }

    const downloadButton = resetDownloadButton(!resume.download_url);
    if (downloadButton && resume.download_url) {
        downloadButton.addEventListener('click', () => {
            window.open(`${API_BASE_URL}${resume.download_url}`, '_blank');
        });
    }

    if (statusBadge) {
        statusBadge.textContent = 'Current';
        statusBadge.classList.remove('bg-warning', 'text-dark');
        statusBadge.classList.add('bg-success');
    }
}

function populateEditModal(profile) {
    setInputValue('profileNameInput', profile.name || '');
    setInputValue('profileMajorInput', profile.major || '');
    setInputValue('profileEmailInput', profile.email || '');
    setInputValue('profilePhoneInput', profile.phone || '');
    setInputValue('profileGpaInput', profile.gpa || '');
    setInputValue('profileBioInput', profile.bio || '');
}

function setInputValue(elementId, value) {
    const el = document.getElementById(elementId);
    if (el) {
        el.value = value ?? '';
    }
}

function getInputValue(elementId) {
    const el = document.getElementById(elementId);
    return el ? el.value.trim() : '';
}

function setupProfileForm() {
    const saveButton = document.getElementById('saveProfileButton');
    const editModal = document.getElementById('edit_profile');

    if (editModal) {
        editModal.addEventListener('show.bs.modal', () => {
            showProfileFormFeedback(null);
            if (currentProfile) {
                populateEditModal(currentProfile);
            }
        });
    }

    if (!saveButton) {
        return;
    }

    saveButton.addEventListener('click', async () => {
        if (!currentProfile) {
            showProfileFormFeedback('Profile has not finished loading yet.', 'danger');
            return;
        }

        const updates = collectProfileFormData();
        saveButton.disabled = true;
        showProfileFormFeedback('Saving changes...', 'info');

        try {
            const response = await api.updateStudentProfile(updates);
            const updatedProfile = response.data || response.student || response;
            currentProfile = updatedProfile;
            populateProfile(updatedProfile);
            showProfileFormFeedback('Profile updated successfully.', 'success');

            const modalInstance = (typeof bootstrap !== 'undefined' && editModal)
                ? bootstrap.Modal.getInstance(editModal)
                : null;
            if (modalInstance) {
                setTimeout(() => {
                    modalInstance.hide();
                    showProfileFormFeedback(null);
                }, 800);
            }
            showPageAlert('Profile updated successfully.', 'success');
            setTimeout(() => showPageAlert(null), 4000);
        } catch (error) {
            console.error('Failed to update profile:', error);
            showProfileFormFeedback(error.message || 'Failed to update profile. Please try again.', 'danger');
        } finally {
            saveButton.disabled = false;
        }
    });
}

function collectProfileFormData() {
    return {
        name: getInputValue('profileNameInput'),
        major: getInputValue('profileMajorInput'),
        email: getInputValue('profileEmailInput'),
        phone: getInputValue('profilePhoneInput'),
        gpa: getInputValue('profileGpaInput'),
        bio: getInputValue('profileBioInput')
    };
}

function showProfileFormFeedback(message, state) {
    const feedbackEl = document.getElementById('profileFormFeedback');
    if (!feedbackEl) {
        return;
    }

    if (!message) {
        feedbackEl.classList.add('d-none');
        feedbackEl.textContent = '';
        return;
    }

    const stateClass = state === 'success' ? 'alert-success' : state === 'danger' ? 'alert-danger' : 'alert-info';
    feedbackEl.className = `alert ${stateClass}`;
    feedbackEl.textContent = message;
}

function showPageAlert(message, state = 'danger') {
    const alertEl = document.getElementById('profile-alert');
    if (!alertEl) {
        return;
    }

    if (!message) {
        alertEl.classList.add('d-none');
        alertEl.textContent = '';
        return;
    }

    alertEl.textContent = message;
    alertEl.className = `alert alert-${state}`;
    alertEl.classList.remove('d-none');
}

function setupResumeUpload() {
    const uploadButton = document.getElementById('uploadResumeButton');
    const fileInput = document.getElementById('resumeFileInput');

    if (!uploadButton || !fileInput) {
        return;
    }

    uploadButton.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', async (event) => {
        const file = event.target.files[0];
        if (!file) {
            return;
        }

        if (!validateResumeFile(file)) {
            showResumeFeedback('Invalid file type. Upload a PDF, DOC, or DOCX file under 5MB.', 'danger');
            fileInput.value = '';
            return;
        }

        uploadButton.disabled = true;
        showResumeFeedback('Uploading resume...', 'info');

        try {
            const response = await api.uploadStudentResume(file);
            if (response && response.resume) {
                updateResumeUI(response.resume);
            }
            showResumeFeedback('Resume uploaded successfully.', 'success');
        } catch (error) {
            console.error('Resume upload failed:', error);
            showResumeFeedback(error.message || 'Failed to upload resume. Please try again.', 'danger');
        } finally {
            uploadButton.disabled = false;
            fileInput.value = '';
        }
    });
}

function validateResumeFile(file) {
    const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    const allowedExtensions = ['pdf', 'doc', 'docx'];
    const extension = file.name.split('.').pop().toLowerCase();
    const maxSize = 5 * 1024 * 1024;

    return file.size <= maxSize && (allowedTypes.includes(file.type) || allowedExtensions.includes(extension));
}

function showResumeFeedback(message, state) {
    const feedbackEl = document.getElementById('resume-upload-feedback');
    if (!feedbackEl) {
        return;
    }

    feedbackEl.textContent = message;
    feedbackEl.classList.remove('d-none', 'text-danger', 'text-success', 'text-info');
    if (state === 'danger') {
        feedbackEl.classList.add('text-danger');
    } else if (state === 'success') {
        feedbackEl.classList.add('text-success');
    } else {
        feedbackEl.classList.add('text-info');
    }
}

function resetDownloadButton(disabled) {
    const button = document.getElementById('downloadResumeButton');
    if (!button) {
        return null;
    }
    const clone = button.cloneNode(true);
    clone.disabled = disabled;
    button.replaceWith(clone);
    return clone;
}

function resolveMediaPath(path) {
    if (!path) {
        return null;
    }

    if (path.startsWith('http')) {
        return path;
    }

    if (path.startsWith('/assets')) {
        return `..${path}`;
    }

    return path.startsWith('/')
        ? `${API_BASE_URL}${path}`
        : `${API_BASE_URL}/${path}`;
}
