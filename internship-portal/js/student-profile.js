const profileDefaults = {
    avatar: '../bs5/template/assets/images/demo/users/face11.jpg',
    institution: 'Sabancı University',
    educationDates: 'Current Term'
};

document.addEventListener('DOMContentLoaded', () => {
    attachLogoutHandlers();
    handleImpersonationBanner();

    const storedRole = localStorage.getItem('userRole');
    const storedStudentId = localStorage.getItem('userId');
    const impersonatedType = sessionStorage.getItem('impersonatedUserType');
    const impersonatedId = sessionStorage.getItem('impersonatedUser');
    const isImpersonatingStudent = impersonatedType === 'student' && !!impersonatedId;
    const targetStudentId = storedRole === 'student' ? storedStudentId : impersonatedId;

    if (!targetStudentId) {
        alert('Student account not detected. Redirecting to login page.');
        window.location.href = '../index.html';
        return;
    }

    if (storedRole !== 'student' && !isImpersonatingStudent) {
        alert('Access denied. Redirecting to login page.');
        window.location.href = '../index.html';
        return;
    }

    loadStudentProfile(targetStudentId);
    setupResumeUpload();
});

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
    const alertEl = document.getElementById('profile-alert');
    if (alertEl) {
        alertEl.classList.add('d-none');
    }

    try {
        const profile = await api.getStudentProfile(studentId);
        populateProfile(profile);
    } catch (error) {
        console.error('Failed to load student profile:', error);
        if (alertEl) {
            alertEl.textContent = 'Unable to load profile information. Please refresh the page.';
            alertEl.classList.remove('d-none');
        }
    }
}

function populateProfile(profileResponse) {
    const student = profileResponse.student || profileResponse;
    if (!student) {
        return;
    }

    setTextContent('student-name', student.name || 'Student');
    setTextContent('student-full-name', student.name || 'Student');
    setTextContent('student-major', student.major || 'Student');
    setTextContent('student-location', student.address || profileDefaults.institution);
    setTextContent('student-bio', student.bio || 'Add a short bio to tell companies more about yourself.');
    setTextContent('student-email', student.email || 'Not provided');
    setTextContent('student-phone', student.phone || 'Not provided');
    setTextContent('student-address', student.address || 'Not provided');
    setTextContent('student-dob', student.date_of_birth || 'Not provided');

    setTextContent('student-degree', student.major ? `${student.major}` : 'Degree not specified');
    setTextContent('student-institution', profileDefaults.institution);
    setTextContent('student-education-dates', profileDefaults.educationDates);
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

    updateResumeUI(student.resume || profileResponse.resume || null);
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
