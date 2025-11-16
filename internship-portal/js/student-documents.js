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
    const userRole = localStorage.getItem('userRole');
    const storedStudentId = localStorage.getItem('userId');
    const impersonatedType = sessionStorage.getItem('impersonatedUserType');
    const impersonatedId = sessionStorage.getItem('impersonatedUser');
    const isImpersonatedStudent = impersonatedType === 'student' && impersonatedId;

    if (userRole !== 'student' && !isImpersonatedStudent) {
        alert('Access denied. Redirecting to login page.');
        window.location.href = '../index.html';
        return;
    }

    const studentId = userRole === 'student' ? storedStudentId : impersonatedId;
    if (!studentId) {
        renderDocuments([]);
        return;
    }

    loadDocuments(studentId);
});

async function loadDocuments(studentId) {
    try {
        const docs = await api.getStudentDocuments(studentId);
        renderDocuments(Array.isArray(docs) ? docs : []);
    } catch (error) {
        console.error('Failed to load documents:', error);
        renderDocuments([]);
    }
}

function renderDocuments(documents) {
    const tbody = document.getElementById('documentsTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (!documents.length) {
        const row = document.createElement('tr');
        row.innerHTML = '<td colspan="6" class="text-center text-muted">No documents uploaded yet. Use the Upload button to add files.</td>';
        tbody.appendChild(row);
        return;
    }

    documents.forEach(doc => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <div class="d-flex align-items-center">
                    <i class="ph-file-text ph-lg me-2 text-primary"></i>
                    <div>${doc.file_name || 'Document'}</div>
                </div>
            </td>
            <td>${doc.document_type || 'General'}</td>
            <td>${formatFileSize(doc.file_size)}</td>
            <td>${doc.upload_date || ''}</td>
            <td><span class="badge bg-success">Uploaded</span></td>
            <td class="text-center">
                <div class="d-inline-flex">
                    ${doc.download_url ? `<a class="btn btn-light btn-icon btn-sm" href="${doc.download_url}" target="_blank" title="Download"><i class="ph-download-simple"></i></a>` : ''}
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function formatFileSize(size) {
    if (!size || isNaN(size)) return 'Unknown';
    if (size < 1024) return `${size} B`;
    if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} KB`;
    return `${(size / (1024 * 1024)).toFixed(1)} MB`;
}
