/**
 * Impersonation Banner - Shared component for admin impersonation UI
 * This script creates a standardized banner that appears when an admin is impersonating a user.
 * It also handles showing/hiding existing inline banners if present.
 */

(function() {
    'use strict';

    // Run when DOM is ready
    document.addEventListener('DOMContentLoaded', initImpersonationBanner);

    function initImpersonationBanner() {
        const impersonatedUser = sessionStorage.getItem('impersonatedUser');
        const impersonatedUserEmail = sessionStorage.getItem('impersonatedUserEmail');
        const impersonatedUserType = sessionStorage.getItem('impersonatedUserType');
        const impersonatedUserName = sessionStorage.getItem('impersonatedUserName');

        // Determine what type of page we're on
        const isStudentPage = window.location.pathname.includes('/student/');
        const isCompanyPage = window.location.pathname.includes('/company/');

        // Only show banner if impersonating the correct user type for this page
        const shouldShowBanner = (isStudentPage && impersonatedUserType === 'student') ||
                                  (isCompanyPage && impersonatedUserType === 'company');

        if (impersonatedUser && impersonatedUserEmail && shouldShowBanner) {
            // Check if there's an existing inline banner that we should use
            const existingBanner = document.getElementById('impersonation-banner');
            const existingUserBanner = document.getElementById('impersonated-user-banner');
            const existingEndButton = document.getElementById('end-impersonation-banner');
            
            if (existingBanner && existingUserBanner) {
                // Use existing inline banner
                existingUserBanner.textContent = impersonatedUserName || impersonatedUserEmail;
                existingBanner.classList.remove('d-none');
                
                // Attach end handler to existing button
                if (existingEndButton) {
                    existingEndButton.addEventListener('click', endImpersonation);
                }
            } else {
                // Create new standardized banner
                createBanner(impersonatedUserName || impersonatedUserEmail, impersonatedUserType);
            }
        }
    }

    function createBanner(userName, userType) {
        // Remove any existing standardized banner first
        const existingBanner = document.getElementById('impersonation-banner-standard');
        if (existingBanner) {
            existingBanner.remove();
        }

        // Also don't create if there's already an inline banner showing
        const inlineBanner = document.getElementById('impersonation-banner');
        if (inlineBanner && !inlineBanner.classList.contains('d-none')) {
            return;
        }

        // Create the banner
        const banner = document.createElement('div');
        banner.id = 'impersonation-banner-standard';
        banner.className = 'alert alert-warning mb-0 rounded-0';
        banner.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; z-index: 9999; padding: 10px 20px;';
        
        const userTypeLabel = userType === 'student' ? 'Student' : 'Company';
        const icon = userType === 'student' ? 'ph-student' : 'ph-buildings';
        
        banner.innerHTML = `
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <i class="${icon} me-2 fs-5"></i>
                    <strong>Admin Impersonation Mode:</strong>
                    <span class="ms-2">Viewing as <strong>${userName}</strong> (${userTypeLabel})</span>
                </div>
                <button type="button" class="btn btn-sm btn-warning" id="end-impersonation-standard-btn">
                    <i class="ph-sign-out me-1"></i>End Impersonation
                </button>
            </div>
        `;

        // Insert at the very beginning of body
        document.body.insertBefore(banner, document.body.firstChild);

        // Add padding to body to account for fixed banner
        document.body.style.paddingTop = '50px';

        // Attach end impersonation handler
        document.getElementById('end-impersonation-standard-btn').addEventListener('click', endImpersonation);
    }

    function endImpersonation() {
        // Clear all impersonation data
        sessionStorage.removeItem('impersonatedUser');
        sessionStorage.removeItem('impersonatedUserEmail');
        sessionStorage.removeItem('impersonatedUserType');
        sessionStorage.removeItem('impersonatedUserName');
        
        // Redirect back to admin dashboard
        window.location.href = '../admin/admin-dashboard.html';
    }

    // Expose functions globally if needed
    window.ImpersonationBanner = {
        init: initImpersonationBanner,
        end: endImpersonation
    };
})();
