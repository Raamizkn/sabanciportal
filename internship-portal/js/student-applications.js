
document.addEventListener('DOMContentLoaded', () => {
    const studentId = localStorage.getItem('userId');
    const userRole = localStorage.getItem('userRole');

    if (userRole !== 'student') {
        alert('Access Denied. You must be logged in as a Student.');
        window.location.href = '../index.html';
        return;
    }

    if (!studentId) {
        alert('Student ID not found. Please log in again.');
        window.location.href = '../index.html';
        return;
    }

    loadStudentApplications(studentId);
});

async function loadStudentApplications(studentId) {
    try {
        const applications = await api.getStudentApplications(studentId);
        populateApplicationsTable(applications);
        updateStatusCards(applications);
    } catch (error) {
        console.error('Failed to load applications:', error);
        const tableBody = document.querySelector('.datatable-basic tbody');
        tableBody.innerHTML = '<tr><td colspan="7" class="text-center">Failed to load applications.</td></tr>';
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

function getStatusBadge(status) {
    let badgeClass = 'bg-secondary';
    switch (status) {
        case 'Confirmed_By_Student':
        case 'Accepted':
            badgeClass = 'bg-success';
            break;
        case 'Offered':
             badgeClass = 'bg-info';
            break;
        case 'Pending Review':
        case 'Under Review':
            badgeClass = 'bg-warning text-dark';
            break;
        case 'Rejected':
            badgeClass = 'bg-danger';
            break;
    }
    return `<span class="badge ${badgeClass}">${status}</span>`;
}

function getActionButtons(application) {
    let buttons = '';

    // Always show a view button
    buttons += `<a href="student-application-detail.html?id=${application.application_id}" class="btn btn-sm btn-icon btn-light view-button" data-bs-popup="tooltip" title="View Details"><i class="ph-eye"></i></a>`;

    if (application.status === 'Offered') {
        buttons += `<button class="btn btn-sm btn-icon btn-light confirm-offer-btn ms-1" data-application-id="${application.application_id}" data-bs-popup="tooltip" title="Confirm Offer"><i class="ph-check-circle text-success"></i></button>`;
    }

    const withdrawable_statuses = ['Pending Review', 'Under Review', 'Shortlisted', 'Offered'];
    if (withdrawable_statuses.includes(application.status)) {
        buttons += `<button class="btn btn-sm btn-icon btn-light withdraw-btn ms-1" data-application-id="${application.application_id}" data-bs-popup="tooltip" title="Withdraw Application"><i class="ph-x-circle text-danger"></i></button>`;
    }

    return buttons;
}

function addEventListeners() {
    // Withdraw buttons
    document.querySelectorAll('.withdraw-btn').forEach(button => {
        button.addEventListener('click', async (e) => {
            const appId = e.currentTarget.getAttribute('data-application-id');
            if (confirm('Are you sure you want to withdraw this application?')) {
                try {
                    const result = await api.withdrawApplication(appId);
                    if (result.status === 'success') {
                        alert('Application withdrawn.');
                        loadStudentApplications(localStorage.getItem('userId')); // Refresh list
                    } else {
                        alert('Error: ' + result.error);
                    }
                } catch (error) {
                    alert('An error occurred while withdrawing the application.');
                }
            }
        });
    });

    // Confirm offer buttons
    document.querySelectorAll('.confirm-offer-btn').forEach(button => {
        button.addEventListener('click', async (e) => {
            const appId = e.currentTarget.getAttribute('data-application-id');
            if (confirm('Are you sure you want to confirm this internship offer? This action cannot be undone.')) {
                try {
                    const result = await api.confirmOffer(appId);
                    if (result.status === 'success') {
                        alert('Offer confirmed! Congratulations!');
                        loadStudentApplications(localStorage.getItem('userId')); // Refresh list
                    } else {
                        alert('Error: ' + result.error);
                    }
                } catch (error) {
                    alert('An error occurred while confirming the offer.');
                }
            }
        });
    });
}

function updateStatusCards(applications) {
    const statusCounts = {
        total: applications.length,
        accepted: 0,
        pending: 0,
        rejected: 0,
        awaiting: 0,
        approved: 0
    };

    applications.forEach(app => {
        switch(app.status) {
            case 'Accepted':
            case 'Confirmed_By_Student':
                statusCounts.accepted++;
                break;
            case 'Pending Review':
            case 'Under Review':
                statusCounts.pending++;
                break;
            case 'Rejected':
                statusCounts.rejected++;
                break;
            case 'Offered':
                statusCounts.awaiting++;
                break;
        }
    });

    document.querySelector('.bg-primary h4').textContent = statusCounts.total;
    document.querySelector('.bg-success h4').textContent = statusCounts.accepted;
    document.querySelector('.bg-secondary h4').textContent = statusCounts.pending;
    document.querySelector('.bg-danger h4').textContent = statusCounts.rejected;
    document.querySelector('.bg-warning h4').textContent = statusCounts.awaiting;
    document.querySelector('.bg-info h4').textContent = statusCounts.approved; // This status is not yet handled by the API
}
