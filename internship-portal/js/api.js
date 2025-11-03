/**
 * API Service for Sabanci Internship Portal
 * Centralized API communication layer
 */

// Detect API base URL based on environment
function getAPIBaseURL() {
    const hostname = window.location.hostname;
    const protocol = window.location.protocol;
    
    // If running on localhost, use localhost:8001
    if (hostname === 'localhost' || hostname === '127.0.0.1') {
        return 'http://localhost:8001';
    }
    
    // On server, use relative path to backend
    // If we're at /shadowing/internship-portal/, backend is at /shadowing/backend/
    const pathname = window.location.pathname;
    if (pathname.includes('/shadowing/internship-portal') || pathname.includes('/shadowing/backend')) {
        return `${protocol}//${hostname}/shadowing/backend`;
    }
    
    // Fallback: try to find backend in parent directory
    return `${protocol}//${hostname}/backend`;
}

const API_BASE_URL = getAPIBaseURL();

// Debug: Log the API base URL
console.log('API Base URL:', API_BASE_URL);
console.log('Current URL:', window.location.href);

class APIService {
    constructor() {
        this.baseURL = API_BASE_URL;
    }

    /**
     * Generic fetch wrapper
     */
    async request(endpoint, options = {}) {
        try {
            const url = `${this.baseURL}${endpoint}`;
            console.log('Making request to:', url);
            console.log('With credentials:', 'include');
            
            const response = await fetch(url, {
                ...options,
                credentials: 'include', // Important for sending cookies with CORS
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                }
            });

            console.log('Response status:', response.status);
            console.log('Response headers:', [...response.headers.entries()]);

            const data = await response.json();
            
            if (!response.ok) {
                console.error('API Error:', data);
                throw new Error(data.error || 'API request failed');
            }
            
            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    // ==================== ADMIN APIs ====================

    /**
     * Create a new company
     */
    async createCompany(companyData) {
        return this.request('/index.php?entity=admin&resource=companies&action=add', {
            method: 'POST',
            body: JSON.stringify(companyData)
        });
    }

    /**
     * Get all companies
     */
    async getCompanies() {
        return this.request('/index.php?entity=admin&resource=companies');
    }

    /**
     * Create a new term
     */
    async createTerm(termData) {
        return this.request('/index.php?entity=admin&resource=terms&action=add', {
            method: 'POST',
            body: JSON.stringify(termData)
        });
    }

    // ==================== COMPANY APIs ====================

    /**
     * Get company profile
     */
    async getCompanyProfile(companyId) {
        return this.request(`/index.php?entity=companies&action=get_profile&company_id=${companyId}`);
    }

    /**
     * Get company internships
     */
    async getCompanyInternships(companyId) {
        return this.request(`/index.php?entity=internships&company_id=${companyId}`);
    }

    /**
     * Create new internship
     */
    async createInternship(companyId, internshipData) {
        return this.request('/index.php?entity=internships&action=create', {
            method: 'POST',
            body: JSON.stringify({
                company_id: companyId,
                ...internshipData
            })
        });
    }

    /**
     * Get company applications
     */
    async getCompanyApplications(companyId) {
        return this.request(`/index.php?entity=applications&company_id=${companyId}`);
    }

    /**
     * Update application status
     */
    async updateApplicationStatus(applicationId, status, offerDetails = null) {
        return this.request(`/index.php?entity=applications&id=${applicationId}&action=update_status_company`, {
            method: 'POST',
            body: JSON.stringify({
                status: status,
                offer_details: offerDetails
            })
        });
    }

    // ==================== STUDENT APIs ====================

    /**
     * Get all internships
     */
    async getInternships() {
        return this.request('/index.php?entity=internships');
    }

    /**
     * Get internship details
     */
    async getInternshipDetails(internshipId) {
        return this.request(`/index.php?entity=internships&id=${internshipId}`);
    }

    /**
     * Get application details
     */
    async getApplicationDetails(applicationId) {
        return this.request(`/index.php?entity=applications&id=${applicationId}`);
    }

    /**
     * Apply for internship
     */
    async applyForInternship(studentId, internshipId, coverLetter) {
        return this.request('/index.php?entity=applications&action=apply', {
            method: 'POST',
            body: JSON.stringify({
                student_id: studentId,
                internship_id: internshipId,
                cover_letter: coverLetter
            })
        });
    }

    /**
     * Get student applications
     */
    async getStudentApplications(studentId) {
        return this.request(`/index.php?entity=applications&student_id=${studentId}`);
    }

    /**
     * Confirm offer
     */
    async confirmOffer(applicationId) {
        return this.request(`/index.php?entity=applications&id=${applicationId}&action=confirm_offer`, {
            method: 'POST'
        });
    }

    /**
     * Withdraw application
     */
    async withdrawApplication(applicationId) {
        return this.request(`/index.php?entity=applications&id=${applicationId}&action=withdraw`, {
            method: 'POST'
        });
    }
}

// Export instance
const api = new APIService();

