/**
 * Path Resolver - Dynamic asset path configuration
 * 
 * This script automatically detects the environment (localhost vs server)
 * and sets the correct base path for all assets (CSS, JS, images)
 * 
 * Usage: Include this script in the <head> BEFORE any other asset references
 */

(function() {
    'use strict';
    
    // Detect the base path from the current URL
    function getBasePath() {
        const pathname = window.location.pathname;
        
        // Check if we're on the server (contains /shadowing/internship-portal/)
        if (pathname.includes('/shadowing/internship-portal/')) {
            // Extract everything up to /internship-portal/
            const parts = pathname.split('/internship-portal/');
            return parts[0] + '/internship-portal/';
        }
        
        // Default to empty string for localhost (relative paths work)
        return '';
    }
    
    // Get the base path
    const basePath = getBasePath();
    
    // Store in window for access by other scripts
    window.BASE_PATH = basePath;
    
    // Function to resolve a path
    window.resolvePath = function(path) {
        // If path is already absolute or starts with http/https, return as is
        if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('//')) {
            return path;
        }
        
        // If basePath is empty (localhost), return path as is
        if (!basePath) {
            return path;
        }
        
        // Use the browser's URL resolution to properly handle relative paths like ../
        try {
            // Resolve the relative path against the current page URL
            const resolvedUrl = new URL(path, window.location.href);
            // Return the pathname
            return resolvedUrl.pathname;
        } catch (e) {
            // Fallback: just prepend basePath
            return basePath + path;
        }
    };
    
    // Apply path resolution to existing elements in the DOM
    function resolveExistingPaths() {
        // Resolve CSS links
        document.querySelectorAll('link[href]').forEach(function(link) {
            const href = link.getAttribute('href');
            if (href && !href.startsWith('http://') && !href.startsWith('https://') && !href.startsWith('//')) {
                link.setAttribute('href', window.resolvePath(href));
            }
        });
        
        // Resolve JS scripts
        document.querySelectorAll('script[src]').forEach(function(script) {
            const src = script.getAttribute('src');
            if (src && !src.startsWith('http://') && !src.startsWith('https://') && !src.startsWith('//')) {
                script.setAttribute('src', window.resolvePath(src));
            }
        });
        
        // Resolve images
        document.querySelectorAll('img[src]').forEach(function(img) {
            const src = img.getAttribute('src');
            if (src && !src.startsWith('http://') && !src.startsWith('https://') && !src.startsWith('//')) {
                img.setAttribute('src', window.resolvePath(src));
            }
        });
    }
    
    // If DOM is already loaded, apply immediately
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', resolveExistingPaths);
    } else {
        resolveExistingPaths();
    }
    
    // Also apply when new elements are added dynamically
    const observer = new MutationObserver(function(mutations) {
        resolveExistingPaths();
    });
    
    observer.observe(document.documentElement, {
        childList: true,
        subtree: true
    });
    
    console.log('Path Resolver initialized. Base path:', basePath || '(root)');
})();

