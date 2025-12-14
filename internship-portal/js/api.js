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

            const isFormData = options.body instanceof FormData;
            const headers = {};
            
            // Only set headers if not FormData (browser will set Content-Type with boundary for FormData)
            if (!isFormData) {
                headers['Content-Type'] = options.headers?.['Content-Type'] || 'application/json';
            }
            
            // Merge any other custom headers
            if (options.headers) {
                Object.keys(options.headers).forEach(key => {
                    if (key.toLowerCase() !== 'content-type' || !isFormData) {
                        headers[key] = options.headers[key];
                    }
                });
            }

            const fetchOptions = {
                ...options,
                credentials: 'include',
                headers
            };
            
            const response = await fetch(url, fetchOptions);

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
     * Get admin dashboard statistics
     */
    async getAdminDashboardStats() {
        return this.request('/index.php?entity=admin&resource=dashboard&action=stats');
    }

    /**
     * Get admin activity feed
     */
    async getAdminActivityFeed(limit = 10) {
        return this.request(`/index.php?entity=admin&resource=dashboard&action=activity&limit=${limit}`);
    }

    /**
     * Get all students (admin)
     */
    async getStudents() {
        return this.request('/index.php?entity=admin&resource=students');
    }

    /**
     * Get student details (admin)
     */
    async getStudentDetails(studentId) {
        return this.request(`/index.php?entity=admin&resource=students&id=${studentId}`);
    }

    /**
     * Create a new student (admin)
     */
    async createStudent(studentData) {
        return this.request('/index.php?entity=admin&resource=students&action=add', {
            method: 'POST',
            body: JSON.stringify(studentData)
        });
    }

    /**
     * Update student (admin)
     */
    async updateStudent(studentId, studentData) {
        return this.request(`/index.php?entity=admin&resource=students&action=update&id=${studentId}`, {
            method: 'POST',
            body: JSON.stringify(studentData)
        });
    }

    /**
     * Delete student (admin)
     */
    async deleteStudent(studentId) {
        return this.request(`/index.php?entity=admin&resource=students&action=delete&id=${studentId}`, {
            method: 'POST'
        });
    }

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
     * Get company details (admin)
     */
    async getCompanyDetails(companyId) {
        return this.request(`/index.php?entity=admin&resource=companies&id=${companyId}`);
    }

    /**
     * Update company (admin)
     */
    async updateCompany(companyId, companyData) {
        return this.request(`/index.php?entity=admin&resource=companies&action=update&id=${companyId}`, {
            method: 'POST',
            body: JSON.stringify(companyData)
        });
    }

    /**
     * Delete company (admin)
     */
    async deleteCompany(companyId) {
        return this.request(`/index.php?entity=admin&resource=companies&action=delete&id=${companyId}`, {
            method: 'POST'
        });
    }

    /**
     * Get all internships (admin)
     */
    async getAllInternships() {
        return this.request('/index.php?entity=admin&resource=internships');
    }

    /**
     * Get all applications (admin)
     */
    async getAllApplications() {
        return this.request('/index.php?entity=admin&resource=applications');
    }

    /**
     * Get application details (admin)
     */
    async getApplicationDetails(applicationId) {
        return this.request(`/index.php?entity=admin&resource=applications&id=${applicationId}`);
    }

    /**
     * Get all terms
     */
    async getTerms() {
        return this.request('/index.php?entity=admin&resource=terms');
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

    /**
     * Update term
     */
    async updateTerm(termId, termData) {
        return this.request(`/index.php?entity=admin&resource=terms&action=update&id=${termId}`, {
            method: 'POST',
            body: JSON.stringify(termData)
        });
    }

    /**
     * Delete term
     */
    async deleteTerm(termId) {
        return this.request(`/index.php?entity=admin&resource=terms&action=delete&id=${termId}`, {
            method: 'POST'
        });
    }

    /**
     * Generate report
     */
    async generateReport(reportType, params = {}) {
        return this.request('/index.php?entity=admin&resource=reports', {
            method: 'POST',
            body: JSON.stringify({
                type: reportType,
                params: params
            })
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
     * Update company profile
     */
    async updateCompanyProfile(profileData) {
        return this.request('/index.php?entity=companies&action=update', {
            method: 'POST',
            body: JSON.stringify(profileData)
        });
    }

    /**
     * Get company internships
     */
    async getCompanyInternships(companyId) {
        return this.request(`/index.php?entity=internships&company_id=${companyId}`);
    }

    /**
     * Get internship details by ID
     */
    async getInternshipDetails(internshipId) {
        return this.request(`/index.php?entity=internships&id=${internshipId}`);
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
     * Update existing internship
     */
    async updateInternship(internshipId, internshipData) {
        return this.request(`/index.php?entity=internships&id=${internshipId}&action=update`, {
            method: 'POST',
            body: JSON.stringify(internshipData)
        });
    }

    /**
     * Get company applications
     */
    async getCompanyApplications(companyId, status = null) {
        const statusParam = status ? `&status=${encodeURIComponent(status)}` : '';
        return this.request(`/index.php?entity=applications&company_id=${companyId}${statusParam}`);
    }

    /**
     * Update application status (company)
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

    /**
     * Accept application (company) - shortcut for Accept action
     */
    async acceptApplication(applicationId) {
        return this.request(`/index.php?entity=applications&id=${applicationId}&action=accept`, {
            method: 'POST'
        });
    }

    /**
     * Reject application (company) - shortcut for Reject action
     */
    async rejectApplication(applicationId) {
        return this.request(`/index.php?entity=applications&id=${applicationId}&action=reject`, {
            method: 'POST'
        });
    }

    /**
     * Finalize application (company) - after student confirms
     */
    async finalizeApplication(applicationId) {
        return this.request(`/index.php?entity=applications&id=${applicationId}&action=finalize`, {
            method: 'POST'
        });
    }

    /**
     * Confirm application (student) - after company accepts
     */
    async confirmApplication(applicationId) {
        return this.request(`/index.php?entity=applications&id=${applicationId}&action=confirm`, {
            method: 'POST'
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
     * Get student profile
     */
    async getStudentProfile(studentId) {
        const idParam = studentId ? `&id=${studentId}` : '';
        return this.request(`/index.php?entity=students${idParam}`);
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
    async applyForInternship(studentId, internshipId, coverLetter, documentIds = []) {
        return this.request('/index.php?entity=applications&action=apply', {
            method: 'POST',
            body: JSON.stringify({
                student_id: studentId,
                internship_id: internshipId,
                cover_letter: coverLetter,
                document_ids: documentIds
            })
        });
    }

    /**
     * Get student applications (optionally filtered by term_id)
     */
    async getStudentApplications(studentId, termId = null) {
        const termParam = termId ? `&term_id=${termId}` : '';
        return this.request(`/index.php?entity=applications&student_id=${studentId}${termParam}`);
    }

    /**
     * Get student documents
     */
    async getStudentDocuments(studentId) {
        const idParam = studentId ? `&id=${studentId}` : '';
        return this.request(`/index.php?entity=students${idParam}&action=documents`);
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

    /**
     * Upload student resume
     */
    async uploadStudentResume(file) {
        const formData = new FormData();
        formData.append('resume', file);
        return this.request('/index.php?entity=students&action=upload_resume', {
            method: 'POST',
            body: formData
        });
    }

    /**
     * Upload student document
     */
    async uploadStudentDocument(studentId, formData) {
        return this.request(`/index.php?entity=students&id=${studentId}&action=upload_doc`, {
            method: 'POST',
            body: formData
        });
    }

    /**
     * Delete student document
     */
    async deleteStudentDocument(studentId, documentId) {
        return this.request(`/index.php?entity=students&id=${studentId}&action=delete_doc`, {
            method: 'POST',
            body: JSON.stringify({ document_id: documentId })
        });
    }

    /**
     * Update student profile
     */
    async updateStudentProfile(profileData, studentId = null) {
        const idParam = studentId ? `&id=${studentId}` : '';
        return this.request(`/index.php?entity=students${idParam}&action=update`, {
            method: 'POST',
            body: JSON.stringify(profileData)
        });
    }

    // ==================== EVALUATION APIs ====================

    /**
     * Get company evaluations
     */
    async getCompanyEvaluations(companyId) {
        return this.request(`/index.php?entity=companies&action=get_evaluations&company_id=${companyId}`);
    }

    /**
     * Create evaluation
     */
    async createEvaluation(applicationId, evaluationData) {
        return this.request(`/index.php?entity=companies&action=create_evaluation&application_id=${applicationId}`, {
            method: 'POST',
            body: JSON.stringify(evaluationData)
        });
    }

    /**
     * Get applications for a student
     */
    async getApplications(studentId) {
        return this.request(`/index.php?entity=applications&student_id=${studentId}`);
    }

    /**
     * Get student evaluations (for student view)
     */
    async getStudentEvaluations(studentId) {
        return this.request(`/index.php?entity=student_evaluations&action=list&student_id=${studentId}`);
    }

    /**
     * Get details of a specific student evaluation.
     */
    async getStudentEvaluationDetails(evaluationId, studentId) {
        return this.request(`/index.php?entity=student_evaluations&action=details&id=${evaluationId}&student_id=${studentId}`);
    }

    /**
     * Submit student evaluation of a company for a specific application.
     */
    async submitStudentEvaluation(applicationId, evaluationData) {
        return this.request(`/index.php?entity=student_evaluations&action=submit&application_id=${applicationId}`, {
            method: 'POST',
            body: JSON.stringify(evaluationData)
        });
    }

    // ==================== TERMS & ROUNDS APIs ====================

    /**
     * Get all terms
     */
    async getTerms() {
        return this.request('/index.php?entity=admin&resource=terms');
    }

    /**
     * Get all application rounds (optionally filtered by term_id)
     */
    async getRounds(termId = null) {
        const termParam = termId ? `&term_id=${termId}` : '';
        return this.request(`/index.php?entity=rounds${termParam}`);
    }

    /**
     * Get specific round
     */
    async getRound(roundId) {
        return this.request(`/index.php?entity=rounds&id=${roundId}`);
    }

    /**
     * Create application round (admin only)
     */
    async createRound(roundData) {
        return this.request('/index.php?entity=rounds&action=create', {
            method: 'POST',
            body: JSON.stringify(roundData)
        });
    }

    /**
     * Update application round (admin only)
     */
    async updateRound(roundId, roundData) {
        return this.request(`/index.php?entity=rounds&id=${roundId}&action=update`, {
            method: 'POST',
            body: JSON.stringify(roundData)
        });
    }

    /**
     * Toggle round active status (admin only)
     */
    async toggleRoundActive(roundId) {
        return this.request(`/index.php?entity=rounds&id=${roundId}&action=toggle_active`, {
            method: 'POST'
        });
    }

    /**
     * Get company quota for a round
     */
    async getCompanyQuota(companyId, roundId) {
        return this.request(`/index.php?entity=quotas&company_id=${companyId}&round_id=${roundId}`);
    }

    /**
     * Set company quota for a round
     */
    async setCompanyQuota(companyId, roundId, quota) {
        return this.request('/index.php?entity=quotas&action=set', {
            method: 'POST',
            body: JSON.stringify({
                company_id: companyId,
                round_id: roundId,
                quota: quota
            })
        });
    }

    /**
     * Get all quotas for a company
     */
    async getCompanyQuotas(companyId) {
        return this.request(`/index.php?entity=quotas&company_id=${companyId}`);
    }

    // ==================== IMPERSONATION APIs ====================

    /**
     * Start impersonating a student (admin only)
     */
    async impersonateStudent(studentId) {
        return this.request(`/index.php?entity=admin&resource=students&action=impersonate&id=${studentId}`, {
            method: 'POST'
        });
    }

    /**
     * Start impersonating a company (admin only)
     */
    async impersonateCompany(companyId) {
        return this.request(`/index.php?entity=admin&resource=companies&action=impersonate&id=${companyId}`, {
            method: 'POST'
        });
    }

    /**
     * End current impersonation session (admin only)
     */
    async endImpersonation() {
        return this.request('/index.php?entity=admin&action=end_impersonation', {
            method: 'POST'
        });
    }
}

// Export instance
const api = new APIService();
