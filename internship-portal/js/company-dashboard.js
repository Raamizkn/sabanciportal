document.addEventListener('DOMContentLoaded', function() {
    const api = new APIService();

    const companyId = localStorage.getItem('userId');
    const companyName = localStorage.getItem('userName');

    if (!companyId || localStorage.getItem('userRole') !== 'company') {
        window.location.href = '../index.html';
        return;
    }

    // Set company name in the welcome message
    const welcomeMessage = document.querySelector('.card.bg-primary h5.mb-1');
    if (welcomeMessage) {
        welcomeMessage.textContent = `Welcome, ${companyName}!`;
    }
    const companyNameNavbar = document.querySelector('.navbar .d-none.d-lg-inline-block.mx-lg-2');
    if (companyNameNavbar) {
        companyNameNavbar.textContent = companyName;
    }


    // Fetch data and populate the dashboard
    Promise.all([
        api.getCompanyInternships(companyId),
        api.getCompanyApplications(companyId)
    ]).then(([internships, applications]) => {
        populateCompanyInfo(internships, applications);
        populateRecentApplications(applications);
        populateActiveInternships(internships);
        populatePendingTasks(applications);
    }).catch(error => {
        console.error('Error fetching company data:', error);
    });

    function populateCompanyInfo(internships, applications) {
        const activeInternships = internships.filter(internship => internship.status === 'Active').length;
        const totalApplications = applications.length;
        const positionsFilled = applications.filter(app => app.status === 'Accepted' || app.status === 'Confirmed_By_Student').length;

        const stats = document.querySelectorAll('.card.bg-primary .row.text-center h5.mb-0');
        if (stats.length === 3) {
            stats[0].textContent = activeInternships;
            stats[1].textContent = totalApplications;
            stats[2].textContent = positionsFilled;
        }
    }

    function populateRecentApplications(applications) {
        const tbody = document.querySelector('.card .table-responsive tbody');
        if (!tbody) return;

        tbody.innerHTML = ''; // Clear existing rows

        applications.slice(0, 5).forEach(app => {
            const row = `
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <div>${app.student_name}</div>
                        </div>
                    </td>
                    <td>${app.internship_position}</td>
                    <td>${app.student_major || 'N/A'}</td>
                    <td>${new Date(app.applied_date).toLocaleDateString()}</td>
                    <td><span class="badge bg-secondary">${app.status}</span></td>
                    <td class="text-center">
                        <div class="d-inline-flex gap-1">
                            <a href="company-applications.html?application_id=${app.application_id}" class="btn btn-outline-primary btn-sm btn-icon rounded-pill" data-bs-popup="tooltip" title="View Profile">
                                <i class="ph-eye"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            `;
            tbody.insertAdjacentHTML('beforeend', row);
        });
    }

    function populateActiveInternships(internships) {
        const container = document.querySelector('.card .card-body .row');
        if (!container) return;

        const activeInternships = internships.filter(internship => internship.status === 'Active');
        container.innerHTML = ''; // Clear existing content

        activeInternships.forEach(internship => {
            const internshipCard = `
                <div class="col-lg-6">
                    <div class="card card-body border-start-primary border-start-5">
                        <div class="d-sm-flex align-items-start mb-3">
                            <div class="flex-fill">
                                <h5 class="mb-1">${internship.title}</h5>
                                <ul class="list-inline list-inline-bullet text-muted mb-0">
                                    <li class="list-inline-item">${internship.location}</li>
                                    <li class="list-inline-item">${internship.dates}</li>
                                </ul>
                            </div>
                            <div class="d-flex align-items-center ms-sm-3">
                                <span class="badge bg-primary rounded-pill">${internship.application_count || 0} applicants</span>
                            </div>
                        </div>
                        <div class="d-flex">
                            <a href="company-internships.html#edit-${internship.id}" class="text-body me-3">
                                <i class="ph-pencil me-1"></i>
                                Edit
                            </a>
                            <a href="company-applications.html?internship_id=${internship.id}" class="text-body">
                                <i class="ph-users me-1"></i>
                                View Applicants
                            </a>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', internshipCard);
        });
    }

    function populatePendingTasks(applications) {
        const pendingTasksList = document.querySelector('.card .list-group');
        if (!pendingTasksList) return;

        pendingTasksList.innerHTML = ''; // Clear existing tasks

        const pendingApplications = applications.filter(app => app.status === 'Pending').length;
        if (pendingApplications > 0) {
            const task = `
                <li class="list-group-item d-flex flex-wrap align-items-center py-2">
                    <a href="company-applications.html" class="d-flex align-items-center me-3">
                        <i class="ph-users ph-lg me-2"></i>
                        <span>Review ${pendingApplications} pending applications</span>
                    </a>
                </li>
            `;
            pendingTasksList.insertAdjacentHTML('beforeend', task);
        }
    }
});
