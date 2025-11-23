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

    // Allow access if: company user, impersonated company, admin, or viewing by URL ID
    if (userRole !== 'company' && !isImpersonatedCompany && !isAdmin && !urlCompanyId) {
        alert('Access denied. Redirecting to login page.');
        window.location.href = '../index.html';
        return;
    }

    // Determine which company ID to use: URL param > impersonated > stored
    let companyId = urlCompanyId || (userRole === 'company' ? storedCompanyId : impersonatedId);
    
    if (!companyId || companyId === 'undefined') {
        showProfileAlert('Unable to determine company account. Please login again.', 'danger');
        return;
    }

    loadCompanyProfile(companyId);

    const saveButton = document.getElementById('saveCompanyProfile');
    if (saveButton) {
        saveButton.addEventListener('click', async () => {
            await saveCompanyProfile();
        });
    }
});

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
