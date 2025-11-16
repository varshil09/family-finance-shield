// Family Finance Shield - Main JavaScript File
// Initialize all dashboard functionality

// Global variables - Check if already declared
if (typeof window.currentCharts === 'undefined') {
    window.currentCharts = {};
}

// Utility Functions
function getCurrentPage() {
    const path = window.location.pathname;
    const page = path.split('/').pop() || 'dashboard.php';
    return page;
}

function getCurrentPageTitle() {
    const currentPage = getCurrentPage();
    const titles = {
        'dashboard.php': 'Dashboard',
        'expenses.php': 'Expenses',
        'approvals.php': 'Approvals', 
        'family.php': 'Family',
        'reports.php': 'Reports',
        'profile.php': 'Profile',
        'settings.php': 'Settings'
    };
    return titles[currentPage] || 'Dashboard';
}

// SIMPLIFIED BREADCRUMB - SINGLE CLICKABLE ELEMENT
function initializeBreadcrumbs() {
    console.log('Initializing breadcrumb navigation...');
    
    const breadcrumbCurrent = document.getElementById('breadcrumbCurrent');
    
    if (breadcrumbCurrent) {
        breadcrumbCurrent.addEventListener('click', function(e) {
            e.stopPropagation(); // PREVENT EVENT BUBBLING
            e.preventDefault(); // PREVENT DEFAULT BEHAVIOR
            toggleBreadcrumbMenu();
        });
    }
    
    // Add click handlers for navigation items
    const navItems = document.querySelectorAll('.breadcrumb-nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.stopPropagation(); // PREVENT EVENT BUBBLING
            console.log('Navigating to:', this.href);
            if (typeof showLoading === 'function') {
                showLoading('Loading...');
            }
        });
    });
}

function toggleBreadcrumbMenu() {
    console.log('Toggle breadcrumb menu called');
    const menu = document.getElementById('breadcrumbNavMenu');
    const overlay = document.getElementById('breadcrumbOverlay');
    const arrow = document.getElementById('breadcrumbArrow');
    
    if (menu && overlay) {
        const isShowing = menu.classList.contains('show');
        
        if (isShowing) {
            closeBreadcrumbMenu();
        } else {
            // Use setTimeout to prevent immediate closing by global handler
            setTimeout(() => {
                menu.classList.add('show');
                overlay.classList.add('show');
                document.body.style.overflow = 'hidden';
                
                if (arrow) {
                    arrow.className = 'fas fa-chevron-up';
                }
                
                console.log('Breadcrumb menu opened successfully');
            }, 10);
        }
    }
}

function closeBreadcrumbMenu() {
    console.log('Close breadcrumb menu called');
    const menu = document.getElementById('breadcrumbNavMenu');
    const overlay = document.getElementById('breadcrumbOverlay');
    const arrow = document.getElementById('breadcrumbArrow');
    
    if (menu && overlay) {
        menu.classList.remove('show');
        overlay.classList.remove('show');
        document.body.style.overflow = '';
        
        // Update UI
        if (arrow) {
            arrow.className = 'fas fa-chevron-down';
        }
        
        console.log('Breadcrumb menu closed successfully');
    }
}

// Close menu when clicking outside
// In script.js - modify the click handler:
document.addEventListener('click', function(e) {
    const menu = document.getElementById('breadcrumbNavMenu');
    const breadcrumb = document.querySelector('.breadcrumb');
    
    // Check if click is outside both breadcrumb and menu
    if (menu && menu.classList.contains('show') && 
        !breadcrumb.contains(e.target) && 
        !menu.contains(e.target)) {
        closeBreadcrumbMenu();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeBreadcrumbMenu();
    }
});

// Loading state helper - MUST BE DEFINED
function showLoading(message = 'Loading...') {
    console.log('Show loading:', message);
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.style.opacity = '0.7';
        mainContent.style.pointerEvents = 'none';
    }
    
    // Remove existing loading spinner
    const existingSpinner = document.querySelector('.loading-spinner');
    if (existingSpinner) {
        existingSpinner.remove();
    }
    
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'loading-spinner';
    loadingDiv.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${message}`;
    loadingDiv.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 1000;
        background: white;
        padding: 20px 30px;
        border-radius: 12px;
        box-shadow: var(--shadow-lg);
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
    `;
    
    document.body.appendChild(loadingDiv);
}

function hideLoading() {
    console.log('Hide loading');
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.style.opacity = '1';
        mainContent.style.pointerEvents = 'auto';
    }
    
    const loadingSpinner = document.querySelector('.loading-spinner');
    if (loadingSpinner) {
        loadingSpinner.remove();
    }
}

// Global close all dropdowns function
function closeAllDropdowns() {
    // Close profile dropdown
    const profileDropdown = document.getElementById('profileDropdown');
    if (profileDropdown) {
        profileDropdown.classList.remove('show');
    }
    
    // Close breadcrumb menu
    closeBreadcrumbMenu();
}

// Initialize everything when DOM is loaded
function initializeApp() {
    console.log('Family Finance Shield - Initializing...');
    
    // Initialize all components
    initializeBreadcrumbs();
    initializeFormValidation();
    initializeSidebar();
    initializeMobileMenu();
    initializeLoadingStates();
    
    // Add enhanced toast handling
    const toasts = document.querySelectorAll('.alert-toast');
    toasts.forEach(toast => {
        toast.addEventListener('click', function(e) {
            if (e.target.classList.contains('toast-close') || 
                e.target.closest('.toast-close')) {
                this.remove();
            }
        });
    });
    
    console.log('Family Finance Shield - Initialization complete!');
}

// Only initialize if not already initialized
if (!window.appInitialized) {
    document.addEventListener('DOMContentLoaded', initializeApp);
    window.appInitialized = true;
}

// Form Validation and Enhancement
function initializeFormValidation() {
    console.log('Initializing form validation...');
    
    // Password confirmation validation
    const confirmPassword = document.getElementById('confirm_password');
    if (confirmPassword) {
        confirmPassword.addEventListener('input', function() {
            const password = document.getElementById('new_password') || document.getElementById('password');
            const confirm = this;
            
            if (password && password.value !== confirm.value) {
                confirm.style.borderColor = '#e63946';
                confirm.title = 'Passwords do not match';
            } else {
                confirm.style.borderColor = '#28a745';
                confirm.title = '';
            }
        });
    }

    // Amount formatting
    const amountInputs = document.querySelectorAll('input[type="number"][name="amount"]');
    amountInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value) {
                const formatted = parseFloat(this.value).toFixed(2);
                this.value = formatted;
            }
        });
    });

    // Date validation - cannot be future date
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        input.max = new Date().toISOString().split('T')[0];
    });

    // Enhanced form submission handling
    const forms = document.querySelectorAll('form:not(.no-validate)');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && submitBtn.classList.contains('btn-animated')) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                submitBtn.disabled = true;
                
                // Revert after 5 seconds (in case of error)
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 5000);
            }
        });
    });
}

// Sidebar Navigation Enhancement
function initializeSidebar() {
    console.log('Initializing sidebar...');
    
    const navLinks = document.querySelectorAll('.nav-link');
    const currentPage = getCurrentPage();
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPage || (currentPage === '' && href === 'dashboard.php')) {
            link.classList.add('active');
        }
    });
}

// Mobile Menu Toggle
function initializeMobileMenu() {
    console.log('Initializing mobile menu...');
    
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                sidebar.classList.toggle('mobile-open');
            }
        });
    }
    
    // Check screen size and show/hide toggle
    function checkScreenSize() {
        if (window.innerWidth <= 768) {
            if (menuToggle) menuToggle.style.display = 'flex';
        } else {
            if (menuToggle) menuToggle.style.display = 'none';
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                sidebar.classList.remove('mobile-open');
            }
        }
    }
    
    checkScreenSize();
    window.addEventListener('resize', checkScreenSize);
}

// Loading States for Better UX
function initializeLoadingStates() {
    console.log('Initializing loading states...');
    
    // Enhanced button loading states
    const buttons = document.querySelectorAll('.btn-animated');
    buttons.forEach(btn => {
        btn.addEventListener('click', function() {
            if (this.type === 'submit' || this.getAttribute('type') === 'submit') {
                this.classList.add('loading');
                setTimeout(() => {
                    this.classList.remove('loading');
                }, 3000);
            }
        });
    });
}

// Currency Formatting Helper
function formatCurrency(amount) {
    return '₹' + parseFloat(amount).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Date Formatting Helper
function formatDate(dateString) {
    const options = { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    };
    return new Date(dateString).toLocaleDateString('en-IN', options);
}

// Number formatting helper
function formatNumber(number) {
    return parseFloat(number).toLocaleString('en-IN');
}

// Export functions for global access
window.downloadPDFReport = downloadPDFReport;
window.printReport = printReport;
window.formatCurrency = formatCurrency;
window.formatDate = formatDate;
window.formatNumber = formatNumber;
window.toggleBreadcrumbMenu = toggleBreadcrumbMenu;
window.closeBreadcrumbMenu = closeBreadcrumbMenu;
window.showLoading = showLoading;
window.hideLoading = hideLoading;
window.closeAllDropdowns = closeAllDropdowns;

// Placeholder functions for export features
function downloadPDFReport() {
    showLoading('Generating PDF report...');
    setTimeout(() => {
        hideLoading();
        alert('PDF export feature would be implemented here');
    }, 1500);
}

function printReport() {
    showLoading('Preparing print...');
    setTimeout(() => {
        hideLoading();
        window.print();
    }, 1000);
}

// Error handling
window.addEventListener('error', function(e) {
    console.error('JavaScript Error:', e.error);
});

// Clean up on page unload
window.addEventListener('beforeunload', function() {
    // Clean up charts if they exist
    if (window.currentCharts) {
        Object.values(window.currentCharts).forEach(chart => {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
        });
    }
});