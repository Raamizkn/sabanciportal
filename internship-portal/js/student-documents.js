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
    const tbody = document.getElementById('documentsTableBody');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Loading documents...</td></tr>';
    }
    
    try {
        const docs = await api.getStudentDocuments(studentId);
        console.log('Loaded documents:', docs);
        
        // Handle error response
        if (docs && docs.error) {
            console.error('API returned error:', docs.error);
            renderDocuments([]);
            return;
        }
        
        // Ensure we have an array
        const documentsArray = Array.isArray(docs) ? docs : [];
        console.log('Rendering documents:', documentsArray.length);
        renderDocuments(documentsArray);
    } catch (error) {
        console.error('Failed to load documents:', error);
        renderDocuments([]);
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Failed to load documents. Please refresh the page.</td></tr>';
        }
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
        const downloadUrl = resolveDownloadUrl(doc.download_url, doc.document_id);
        const row = document.createElement('tr');
        row.setAttribute('data-document-id', doc.document_id);
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
                <div class="d-inline-flex gap-1">
                    ${downloadUrl ? `<a class="btn btn-light btn-icon btn-sm" href="${downloadUrl}" target="_blank" rel="noopener noreferrer" title="Download" data-bs-popup="tooltip"><i class="ph-download-simple"></i></a>` : ''}
                    <button class="btn btn-light btn-icon btn-sm delete-document-btn" data-document-id="${doc.document_id}" data-document-name="${doc.file_name || 'Document'}" title="Delete" data-bs-popup="tooltip">
                        <i class="ph-trash text-danger"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
    
    // Initialize tooltips
    const tooltips = tbody.querySelectorAll('[data-bs-popup="tooltip"]');
    tooltips.forEach(tooltip => {
        new bootstrap.Tooltip(tooltip);
    });
    
    // Attach delete handlers
    attachDeleteHandlers();
}

function formatFileSize(size) {
    if (!size || isNaN(size)) return 'Unknown';
    if (size < 1024) return `${size} B`;
    if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} KB`;
    return `${(size / (1024 * 1024)).toFixed(1)} MB`;
}

function resolveDownloadUrl(path, documentId) {
    const userRole = localStorage.getItem('userRole');
    const storedStudentId = localStorage.getItem('userId');
    const impersonatedType = sessionStorage.getItem('impersonatedUserType');
    const impersonatedId = sessionStorage.getItem('impersonatedUser');
    const isImpersonatedStudent = impersonatedType === 'student' && impersonatedId;
    const studentId = userRole === 'student' ? storedStudentId : impersonatedId;

    // If path is absolute, use it as-is
    if (path && (path.startsWith('http://') || path.startsWith('https://'))) {
        return path;
    }

    // If we have a documentId and a studentId, build the download endpoint even when path is null
    if (documentId && studentId) {
        return `${API_BASE_URL}/index.php?entity=students&id=${studentId}&action=download_doc&document_id=${documentId}`;
    }

    // Otherwise, fall back to relative path if provided
    if (path) {
        return `${API_BASE_URL}${path}`;
    }

    return null;
}

// Document upload functionality
document.addEventListener('DOMContentLoaded', () => {
    const uploadBtn = document.getElementById('upload-document-btn');
    const documentNameInput = document.getElementById('document-name-input');
    const documentFileInput = document.getElementById('document-file-input');
    const uploadFeedback = document.getElementById('upload-feedback');
    const uploadModal = document.getElementById('upload_document');
    
    if (uploadBtn && documentNameInput && documentFileInput) {
        uploadBtn.addEventListener('click', async () => {
            const documentName = documentNameInput.value.trim();
            const file = documentFileInput.files[0];
            
            // Validation
            if (!documentName) {
                showUploadFeedback('Please enter a document name.', 'danger');
                return;
            }
            
            if (!file) {
                showUploadFeedback('Please select a file to upload.', 'danger');
                return;
            }
            
            // Check file size (2MB max to match server limit)
            const maxSize = 2 * 1024 * 1024; // 2MB
            if (file.size > maxSize) {
                showUploadFeedback(`File size exceeds 2MB limit. Your file is ${(file.size / (1024 * 1024)).toFixed(2)}MB.`, 'danger');
                return;
            }
            
            // Check file type
            const allowedTypes = ['application/pdf', 'application/msword', 
                                 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                 'image/jpeg', 'image/jpg', 'image/png'];
            if (!allowedTypes.includes(file.type)) {
                showUploadFeedback('Invalid file type. Please upload PDF, DOC, DOCX, JPG, or PNG files.', 'danger');
                return;
            }
            
            // Get student ID
            const userRole = localStorage.getItem('userRole');
            const storedStudentId = localStorage.getItem('userId');
            const impersonatedType = sessionStorage.getItem('impersonatedUserType');
            const impersonatedId = sessionStorage.getItem('impersonatedUser');
            const isImpersonatedStudent = impersonatedType === 'student' && impersonatedId;
            const studentId = userRole === 'student' ? storedStudentId : impersonatedId;
            
            if (!studentId) {
                showUploadFeedback('Student ID not found. Please log in again.', 'danger');
                return;
            }
            
            // Disable button during upload
            uploadBtn.disabled = true;
            uploadBtn.innerHTML = '<i class="ph-spinner ph-spin me-2"></i>Uploading...';
            
            try {
                // Create FormData for file upload
                const formData = new FormData();
                formData.append('file', file);
                formData.append('document_name', documentName);
                formData.append('document_type', 'Other'); // Must match database ENUM values
                
                // Upload document with file
                const result = await api.uploadStudentDocument(studentId, formData);
                
                if (result.error) {
                    showUploadFeedback(result.error, 'danger');
                    uploadBtn.disabled = false;
                    uploadBtn.innerHTML = '<i class="ph-upload-simple me-2"></i>Upload Document';
                } else {
                    showUploadFeedback('Document uploaded successfully!', 'success');
                    
                    // Clear form
                    documentNameInput.value = '';
                    documentFileInput.value = '';
                    
                    // Reload documents immediately
                    await loadDocuments(studentId);
                    
                    // Close modal after a short delay
                    setTimeout(() => {
                        const bootstrap = window.bootstrap;
                        if (bootstrap && uploadModal) {
                            const modalInstance = bootstrap.Modal.getInstance(uploadModal);
                            if (modalInstance) {
                                modalInstance.hide();
                            }
                        }
                    }, 1000);
                }
            } catch (error) {
                console.error('Upload error:', error);
                showUploadFeedback('An error occurred while uploading the document. Please try again.', 'danger');
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '<i class="ph-upload-simple me-2"></i>Upload Document';
            }
        });
    }
    
    // Reset form when modal is closed
    if (uploadModal) {
        uploadModal.addEventListener('hidden.bs.modal', () => {
            if (documentNameInput) documentNameInput.value = '';
            if (documentFileInput) documentFileInput.value = '';
            if (uploadFeedback) {
                uploadFeedback.classList.add('d-none');
                uploadFeedback.textContent = '';
            }
            if (uploadBtn) {
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '<i class="ph-upload-simple me-2"></i>Upload Document';
            }
        });
    }
});

function showUploadFeedback(message, type) {
    const uploadFeedback = document.getElementById('upload-feedback');
    if (uploadFeedback) {
        uploadFeedback.className = `alert alert-${type}`;
        uploadFeedback.textContent = message;
        uploadFeedback.classList.remove('d-none');
    }
}

function attachDeleteHandlers() {
    const deleteButtons = document.querySelectorAll('.delete-document-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', async function() {
            const documentId = this.getAttribute('data-document-id');
            const documentName = this.getAttribute('data-document-name');
            
            // Confirm deletion
            if (!confirm(`Are you sure you want to delete "${documentName}"? This action cannot be undone.`)) {
                return;
            }
            
            // Get student ID
            const userRole = localStorage.getItem('userRole');
            const storedStudentId = localStorage.getItem('userId');
            const impersonatedType = sessionStorage.getItem('impersonatedUserType');
            const impersonatedId = sessionStorage.getItem('impersonatedUser');
            const isImpersonatedStudent = impersonatedType === 'student' && impersonatedId;
            const studentId = userRole === 'student' ? storedStudentId : impersonatedId;
            
            if (!studentId) {
                alert('Student ID not found. Please log in again.');
                return;
            }
            
            // Disable button during deletion
            this.disabled = true;
            const originalHTML = this.innerHTML;
            this.innerHTML = '<i class="ph-spinner ph-spin"></i>';
            
            try {
                const result = await api.deleteStudentDocument(studentId, documentId);
                
                if (result.error) {
                    alert('Error: ' + result.error);
                    this.disabled = false;
                    this.innerHTML = originalHTML;
                } else {
                    // Remove the row from the table
                    const row = this.closest('tr');
                    if (row) {
                        row.style.transition = 'opacity 0.3s';
                        row.style.opacity = '0';
                        setTimeout(() => {
                            row.remove();
                            
                            // Check if table is now empty
                            const tbody = document.getElementById('documentsTableBody');
                            if (tbody && tbody.querySelectorAll('tr').length === 0) {
                                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No documents uploaded yet. Use the Upload button to add files.</td></tr>';
                            }
                        }, 300);
                    } else {
                        // Reload documents if row removal failed
                        await loadDocuments(studentId);
                    }
                }
            } catch (error) {
                console.error('Delete error:', error);
                alert('An error occurred while deleting the document. Please try again.');
                this.disabled = false;
                this.innerHTML = originalHTML;
            }
        });
    });
}
