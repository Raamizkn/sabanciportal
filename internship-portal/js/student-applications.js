
document.addEventListener('DOMContentLoaded', () => {
    const studentId = localStorage.getItem('userId');
    const userRole = localStorage.getItem('userRole');
    const isAdmin = userRole === 'admin';
    const isImpersonating = sessionStorage.getItem('impersonatedUserType') === 'student';

    // Allow admin access or impersonated access
    if (userRole !== 'student' && !isImpersonating && !isAdmin) {
        alert('Access Denied. You must be logged in as a Student.');
        window.location.href = '../index.html';
        return;
    }

    // For admin viewing, get student ID from URL or use impersonated ID
    const urlParams = new URLSearchParams(window.location.search);
    const urlStudentId = urlParams.get('id');
    const targetStudentId = urlStudentId || (isImpersonating ? sessionStorage.getItem('impersonatedUser') : studentId);

    if (!targetStudentId || targetStudentId === 'undefined') {
        alert('Student ID not found. Please log in again.');
        window.location.href = '../index.html';
        return;
    }

    // Add admin navigation bar if accessed by admin
    if (isAdmin && !isImpersonating) {
        addAdminNavigationBar();
    }

    loadStudentApplications(targetStudentId);
    
    // Add filter functionality
    setupFilters();
});

// Store all applications for filtering
let allApplications = [];

// Setup filter functionality
function setupFilters() {
    const filterItems = document.querySelectorAll('[data-filter]');
    filterItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const filterValue = item.getAttribute('data-filter');
            filterApplications(filterValue);
            
            // Update active state
            filterItems.forEach(i => i.classList.remove('active'));
            item.classList.add('active');
        });
    });
    
    // Add search functionality - check both navbar and page search inputs
    const navbarSearchInput = document.getElementById('navbar-search-applications');
    const pageSearchInput = document.querySelector('input[placeholder="Search applications..."]');
    const searchInput = navbarSearchInput || pageSearchInput;
    
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const searchTerm = searchInput.value.toLowerCase().trim();
                if (searchTerm) {
                    searchApplications(searchTerm);
                } else {
                    // Reset to show all
                    filterApplications('all');
                }
            }, 300);
        });
    }
}

function filterApplications(statusFilter) {
    const tableBody = document.querySelector('.datatable-basic tbody');
    if (!tableBody) return;
    
    // Remove any existing "no results" messages
    const existingNoResults = tableBody.querySelector('tr[data-no-results]');
    if (existingNoResults) {
        existingNoResults.remove();
    }
    
    const rows = Array.from(tableBody.querySelectorAll('tr')).filter(row => !row.hasAttribute('data-no-results'));
    let visibleCount = 0;
    
    rows.forEach(row => {
        const statusBadge = row.querySelector('.badge');
        if (!statusBadge) {
            row.style.display = 'none';
            return;
        }
        
        const rowStatus = statusBadge.textContent.trim();
        const shouldShow = statusFilter === 'all' || rowStatus === statusFilter;
        
        if (shouldShow) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Show message if no results
    if (visibleCount === 0 && rows.length > 0) {
        const noResultsRow = document.createElement('tr');
        noResultsRow.setAttribute('data-no-results', 'true');
        noResultsRow.innerHTML = `<td colspan="7" class="text-center text-muted">No applications found with status "${statusFilter}"</td>`;
        tableBody.appendChild(noResultsRow);
    }
}

function searchApplications(searchTerm) {
    const tableBody = document.querySelector('.datatable-basic tbody');
    if (!tableBody) return;
    
    // Remove any existing "no results" messages
    const existingNoResults = tableBody.querySelector('tr[data-no-results]');
    if (existingNoResults) {
        existingNoResults.remove();
    }
    
    const rows = Array.from(tableBody.querySelectorAll('tr')).filter(row => !row.hasAttribute('data-no-results'));
    let visibleCount = 0;
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        let matches = false;
        
        cells.forEach(cell => {
            const text = cell.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                matches = true;
            }
        });
        
        if (matches) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Show message if no results
    if (visibleCount === 0 && rows.length > 0) {
        const noResultsRow = document.createElement('tr');
        noResultsRow.setAttribute('data-no-results', 'true');
        noResultsRow.innerHTML = `<td colspan="7" class="text-center text-muted">No applications found matching "${searchTerm}"</td>`;
        tableBody.appendChild(noResultsRow);
    }
}

// Add admin navigation bar at the top of the page
function addAdminNavigationBar() {
    const adminNav = document.createElement('div');
    adminNav.className = 'alert alert-info mb-0 rounded-0 border-0';
    adminNav.style.cssText = 'position: sticky; top: 0; z-index: 1030; background: #0d6efd !important; color: white;';
    adminNav.innerHTML = `
        <div class="container-fluid d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <i class="ph-arrow-left me-2" style="cursor: pointer;" onclick="window.history.back()"></i>
                <span><strong>Admin View:</strong> Viewing student applications (Admin Mode)</span>
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

async function loadStudentApplications(studentId) {
    try {
        const applications = await api.getStudentApplications(studentId);
        allApplications = applications; // Store for filtering
        populateApplicationsTable(applications);
        updateStatusCards(applications);
        updateApplicationsBadge(applications.length);
    } catch (error) {
        console.error('Failed to load applications:', error);
        const tableBody = document.querySelector('.datatable-basic tbody');
        tableBody.innerHTML = '<tr><td colspan="7" class="text-center">Failed to load applications.</td></tr>';
        updateApplicationsBadge(0);
    }
}

function updateApplicationsBadge(count) {
    // Update badge in sidebar navigation
    const badge = document.getElementById('studentApplicationsBadge');
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
    const applicationsNavLink = document.querySelector('.nav-link[href="student-applications.html"]');
    if (applicationsNavLink) {
        let badgeElement = applicationsNavLink.querySelector('.badge');
        if (!badgeElement) {
            badgeElement = document.createElement('span');
            badgeElement.className = 'badge bg-primary rounded-pill ms-auto';
            badgeElement.id = 'studentApplicationsBadge';
            applicationsNavLink.appendChild(badgeElement);
        }
        badgeElement.textContent = count;
        if (count === 0) {
            badgeElement.style.display = 'none';
        } else {
            badgeElement.style.display = '';
        }
    }
}

function populateApplicationsTable(applications) {
    console.log('Data received from API:', JSON.stringify(applications, null, 2));
    const tableBody = document.querySelector('.datatable-basic tbody');
    tableBody.innerHTML = ''; // Clear placeholder rows

    if (applications.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="7" class="text-center">You have not submitted any applications yet.</td></tr>';
        return;
    }

    applications.forEach(app => {
        const row = document.createElement('tr');
        row.setAttribute('data-application-id', app.application_id);

        const statusBadge = getStatusBadge(app.status);
        const actionButtons = getActionButtons(app);

        row.innerHTML = `
            <td>
                <div class="d-flex align-items-center">
                    <div>${app.company_name || 'N/A'}</div>
                </div>
            </td>
            <td>${app.internship_position || 'N/A'}</td>
            <td>${app.internship_location || 'N/A'}</td>
            <td>${new Date(app.created_at).toLocaleDateString()}</td>
            <td>${app.internship_dates || 'N/A'}</td>
            <td>${statusBadge}</td>
            <td class="text-center">
                <div class="d-inline-flex bg-light rounded p-1">
                    ${actionButtons}
                </div>
            </td>
        `;
        tableBody.appendChild(row);
    });

    // Add event listeners for the new buttons
    addEventListeners();
}

function formatStatusLabel(status) {
    if (!status) {
        return 'Unknown';
    }
    // Statuses are now clean - return as-is
    return status;
}

function getStatusBadge(status) {
    if (!status) return '<span class="badge bg-secondary">Pending</span>';
    
    let badgeClass = 'bg-secondary';
    
    // Handle 6 clean statuses
    switch (status) {
        case 'Pending':
            badgeClass = 'bg-warning text-dark';
            break;
        case 'Accepted':
            badgeClass = 'bg-success';
            break;
        case 'Confirmed':
            badgeClass = 'bg-info';
            break;
        case 'Finalized':
            badgeClass = 'bg-success';
            break;
        case 'Rejected':
            badgeClass = 'bg-danger';
            break;
        case 'Withdrawn':
            badgeClass = 'bg-secondary';
            break;
        default:
            badgeClass = 'bg-secondary';
    }
    
    return `<span class="badge ${badgeClass}">${status}</span>`;
}

function getActionButtons(application) {
    let buttons = '';

    // Always show a view button
    buttons += `<a href="student-application-detail.html?id=${application.application_id}" class="btn btn-sm btn-icon btn-light view-button" data-bs-popup="tooltip" title="View Details"><i class="ph-eye"></i></a>`;

    // Show confirm button if status is Accepted
    if (application.status === 'Accepted') {
        buttons += `<button class="btn btn-sm btn-icon btn-light confirm-application-btn ms-1" data-application-id="${application.application_id}" data-bs-popup="tooltip" title="Confirm Acceptance"><i class="ph-check-circle text-success"></i></button>`;
    }

    // Allow withdrawal if not finalized, rejected, or already withdrawn
    const finalStatuses = ['Finalized', 'Rejected', 'Withdrawn', 'Confirmed'];
    if (!finalStatuses.includes(status)) {
        buttons += `<button class="btn btn-sm btn-icon btn-light withdraw-btn ms-1" data-application-id="${application.application_id}" data-bs-popup="tooltip" title="Withdraw Application"><i class="ph-x-circle text-danger"></i></button>`;
    }

    return buttons;
}

function normalizeStatusForStudent(status) {
    if (!status) return 'Pending';
    // Map database values to clean display names
    // Since we can't ALTER ENUM, map database values for display
    const map = {
        'Confirmed_By_Student': 'Confirmed',
        'Approved_By_Company': 'Finalized'
    };
    return map[status] || status;
}

function addEventListeners() {
    // Withdraw buttons
    document.querySelectorAll('.withdraw-btn').forEach(button => {
        button.addEventListener('click', async (e) => {
            const appId = e.currentTarget.getAttribute('data-application-id');
            showConfirmModal(
                'Withdraw Application',
                'Are you sure you want to withdraw this application?',
                'Withdraw',
                'Cancel',
                async (confirmed) => {
                    if (confirmed) {
                        try {
                            const result = await api.withdrawApplication(appId);
                            if (result.error) {
                                showAlertModal('Error', result.error, 'error');
                                return;
                            }
                            showAlertModal('Application Withdrawn', result.message || 'Your application has been withdrawn.', 'info', () => {
                                loadStudentApplications(localStorage.getItem('userId'));
                            });
                        } catch (error) {
                            showAlertModal('Error', 'An error occurred while withdrawing the application.', 'error');
                        }
                    }
                }
            );
        });
    });

    // Confirm application buttons (when company accepts)
    document.querySelectorAll('.confirm-application-btn').forEach(button => {
        button.addEventListener('click', async (e) => {
            const appId = e.currentTarget.getAttribute('data-application-id');
            showConfirmModal(
                'Confirm Acceptance',
                'Confirm your acceptance of this internship offer? The company will then be able to finalize the placement.',
                'Confirm',
                'Cancel',
                async (confirmed) => {
                    if (confirmed) {
                        try {
                            const result = await api.confirmApplication(appId);
                            if (result.error) {
                                showAlertModal('Error', result.error, 'error');
                                return;
                            }
                            showAlertModal('Success', result.message || 'Application confirmed! Company can now finalize.', 'success', () => {
                                loadStudentApplications(localStorage.getItem('userId'));
                            });
                        } catch (error) {
                            showAlertModal('Error', 'An error occurred while confirming the application.', 'error');
                        }
                    }
                }
            );
        });
    });
}

function updateStatusCards(applications) {
    const statusCounts = {
        total: applications.length,
        pending: 0,
        accepted: 0,
        confirmed: 0,
        finalized: 0,
        rejected: 0
    };

    applications.forEach(app => {
        const status = app.status; // This is the database status value (6 core statuses after migration)
        
        // Check database values directly (all intermediate statuses consolidated to 6 core)
        if (status === 'Pending') {
            statusCounts.pending++;
        } else if (status === 'Accepted') {
            statusCounts.accepted++;
        } else if (status === 'Confirmed') {
            statusCounts.confirmed++;
        } else if (status === 'Finalized') {
            statusCounts.finalized++;
        } else if (status === 'Rejected') {
            statusCounts.rejected++;
        }
        // Note: Withdrawn applications are not counted in status cards
    });

    setCount('studentTotalApplicationsCount', statusCounts.total);
    setCount('studentPendingApplicationsCount', statusCounts.pending);
    setCount('studentAcceptedApplicationsCount', statusCounts.accepted);
    setCount('studentConfirmedApplicationsCount', statusCounts.confirmed);
    setCount('studentFinalizedApplicationsCount', statusCounts.finalized);
    setCount('studentRejectedApplicationsCount', statusCounts.rejected);
}

function setCount(elementId, value) {
    const el = document.getElementById(elementId);
    if (el) {
        el.textContent = value;
    }
}
