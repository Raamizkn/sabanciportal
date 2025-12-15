function attachLogoutHandlers() {
    document.querySelectorAll('.logout-link').forEach(link => {
        link.addEventListener('click', () => {
            localStorage.removeItem('userRole');
            localStorage.removeItem('userId');
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    attachLogoutHandlers();
    const userRole = localStorage.getItem('userRole');
    const storedCompanyId = localStorage.getItem('userId');
    const impersonatedType = sessionStorage.getItem('impersonatedUserType');
    const impersonatedId = sessionStorage.getItem('impersonatedUser');
    const isImpersonatedCompany = impersonatedType === 'company' && impersonatedId;
    const isAdmin = userRole === 'admin';
    
    // Get company ID from URL parameter (for admin viewing)
    const urlParams = new URLSearchParams(window.location.search);
    const urlCompanyId = urlParams.get('id');

    // Determine which company ID to use and viewing mode
    let companyId = null;
    let isAdminViewMode = false; // Admin viewing (not impersonating)
    
    if (urlCompanyId) {
        // URL parameter - could be admin viewing OR anyone with a direct link
        companyId = urlCompanyId;
        // If admin has URL ID and is NOT impersonating this company, it's view mode
        if (isAdmin && (!isImpersonatedCompany || impersonatedId !== urlCompanyId)) {
            isAdminViewMode = true;
        }
    } else if (isImpersonatedCompany) {
        // Admin impersonating - they can edit
        companyId = impersonatedId;
        isAdminViewMode = false;
    } else if (userRole === 'company') {
        // Logged in company user - they can edit their own profile
        companyId = storedCompanyId;
        isAdminViewMode = false;
    }

    // Access check
    if (!companyId || companyId === 'undefined') {
        if (isAdmin) {
            showProfileAlert('Please select a company from the Companies page to view their profile.', 'info');
            addAdminNavigationBar();
        } else if (userRole !== 'company' && !isImpersonatedCompany) {
            alert('Access denied. Redirecting to login page.');
            window.location.href = '../index.html';
        } else {
            showProfileAlert('Unable to determine company account. Please login again.', 'danger');
        }
        return;
    }

    // Add admin navigation bar if admin is viewing (not impersonating)
    if (isAdminViewMode) {
        addAdminNavigationBar();
        disableEditing();
    }

    loadCompanyProfile(companyId);

    // Only setup save button if NOT in admin view mode
    if (!isAdminViewMode) {
        const saveButton = document.getElementById('saveCompanyProfile');
        if (saveButton) {
            saveButton.addEventListener('click', async () => {
                await saveCompanyProfile();
            });
        }
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
                <span><strong>Admin View:</strong> Viewing company profile (Read-Only)</span>
            </div>
            <div>
                <a href="../admin/admin-companies.html" class="btn btn-sm btn-light me-2">
                    <i class="ph-arrow-left me-1"></i>Back to Companies
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
    // Hide save button
    const saveButton = document.getElementById('saveCompanyProfile');
    if (saveButton) {
        saveButton.style.display = 'none';
    }
    
    // Make all form inputs read-only after page loads
    setTimeout(() => {
        const formInputs = document.querySelectorAll('input, textarea, select');
        formInputs.forEach(input => {
            input.setAttribute('readonly', true);
            input.setAttribute('disabled', true);
            input.style.pointerEvents = 'none';
            input.style.opacity = '0.7';
        });
        
        // Hide any edit buttons
        const editButtons = document.querySelectorAll('[data-bs-target="#edit_profile"], .edit-btn, [id*="edit"], [id*="Edit"]');
        editButtons.forEach(btn => {
            if (btn.id !== 'admin-view-bar') {
                btn.style.display = 'none';
            }
        });
    }, 500);
}

async function loadCompanyProfile(companyId) {
    try {
        showProfileAlert('Loading company profile…', 'info');
        const response = await api.getCompanyProfile(companyId);
        const company = response.company || response.data || response;
        populateCompanyProfile(company);
        showProfileAlert(null);
    } catch (error) {
        console.error('Failed to load company profile:', error);
        showProfileAlert(error.message || 'Unable to load company profile.', 'danger');
    }
}

async function saveCompanyProfile() {
    const payload = {
        name: getValue('companyNameInput'),
        industry: getValue('companyIndustrySelect'),
        website: getValue('companyWebsiteInput'),
        phone: getValue('companyPhoneInput'),
        address: getValue('companyAddressInput'),
        description: getValue('companyDescriptionInput')
    };

    try {
        showProfileAlert('Saving changes…', 'info');
        const response = await api.updateCompanyProfile(payload);
        const company = response.company || response.data || response;
        populateCompanyProfile(company);
        showProfileAlert('Profile updated successfully.', 'success');
        setTimeout(() => showProfileAlert(null), 3000);
    } catch (error) {
        console.error('Failed to update profile:', error);
        showProfileAlert(error.message || 'Failed to update profile. Please try again.', 'danger');
    }
}

function populateCompanyProfile(company) {
    if (!company) {
        return;
    }

    setValue('companyNameInput', company.name);
    selectIndustry(company.industry);
    setValue('companyWebsiteInput', company.website);
    setValue('companyPhoneInput', company.phone);
    setValue('companyAddressInput', company.address);
    setValue('companyDescriptionInput', company.description);
    setValue('companyEmailInput', company.email);

    setText('companySummaryName', company.name || 'Company');
    setText('companySummaryMeta', buildSummaryMeta(company));
    setText('navCompanyName', company.name || 'Company');

    const logoPath = resolveLogo(company.logo);
    setImage('companyLogo', logoPath);
    setImage('navCompanyLogo', logoPath, true);
}

function buildSummaryMeta(company) {
    const parts = [];
    if (company.industry) parts.push(company.industry);
    if (company.address) parts.push(company.address);
    return parts.length ? parts.join(' · ') : 'Update your company details';
}

function selectIndustry(value) {
    const select = document.getElementById('companyIndustrySelect');
    if (!select) return;
    const existing = Array.from(select.options).some(opt => opt.value.toLowerCase() === (value || '').toLowerCase());
    if (!existing && value) {
        const option = new Option(value, value, true, true);
        select.add(option);
    }
    select.value = value || '';
}

function setValue(elementId, value) {
    const el = document.getElementById(elementId);
    if (el) {
        el.value = value || '';
    }
}

function getValue(elementId) {
    const el = document.getElementById(elementId);
    return el ? el.value.trim() : '';
}

function setText(elementId, value) {
    const el = document.getElementById(elementId);
    if (el) el.textContent = value || '';
}

function setImage(elementId, src) {
    const el = document.getElementById(elementId);
    if (!el) return;
    el.src = src || '../bs5/template/assets/images/demo/logos/2.svg';
}

function resolveLogo(path) {
    if (!path) return '../bs5/template/assets/images/demo/logos/2.svg';
    if (path.startsWith('http')) return path;
    if (path.startsWith('/')) return `${API_BASE_URL}${path}`;
    return path;
}

function showProfileAlert(message, state = 'info') {
    const alertEl = document.getElementById('companyProfileAlert');
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
