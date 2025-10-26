/**
 * API Service for Sabanci Internship Portal
 * Centralized API communication layer
 */

const API_BASE_URL = 'http://localhost:8001';

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
            const response = await fetch(url, {
                ...options,
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                }
            });

            const data = await response.json();
            
            if (!response.ok) {
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

