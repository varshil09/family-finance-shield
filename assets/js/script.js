// Family Finance Shield - Main JavaScript File
// Initialize all dashboard functionality

// Global variables
let currentCharts = {};

// Dashboard Charts Initialization
function initializeDashboardCharts() {
    console.log('Initializing dashboard charts...');
    
    // Mini Monthly Trend Chart
    const miniMonthlyCtx = document.getElementById('miniMonthlyChart');
    if (miniMonthlyCtx) {
        try {
            currentCharts.miniMonthly = new Chart(miniMonthlyCtx, {
                type: 'line',
                data: {
                    labels: ['J', 'F', 'M', 'A', 'M', 'J', 'J', 'A', 'S', 'O', 'N'],
                    datasets: [{
                        data: [52000, 58000, 54000, 60000, 62000, 65000, 63000, 68000, 65000, 70000, 62000],
                        borderColor: '#4361ee',
                        backgroundColor: 'rgba(67, 97, 238, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return '₹' + context.raw.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: { 
                            display: false,
                            beginAtZero: true
                        },
                        x: { 
                            display: false
                        }
                    },
                    elements: {
                        point: {
                            radius: 0,
                            hoverRadius: 4
                        }
                    }
                }
            });
            console.log('Mini monthly chart created successfully');
        } catch (error) {
            console.error('Error creating mini monthly chart:', error);
        }
    }

    // Mini Category Pie Chart
    const miniCategoryCtx = document.getElementById('miniCategoryChart');
    if (miniCategoryCtx) {
        try {
            currentCharts.miniCategory = new Chart(miniCategoryCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Food', 'Travel', 'Shop', 'Utils'],
                    datasets: [{
                        data: [12750, 18500, 7200, 6400],
                        backgroundColor: ['#4361ee', '#3f37c9', '#4cc9f0', '#e63946'],
                        borderWidth: 0,
                        cutout: '70%'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ₹${value.toLocaleString()} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
            console.log('Mini category chart created successfully');
        } catch (error) {
            console.error('Error creating mini category chart:', error);
        }
    }
}

// Main Dashboard Charts
function initializeMainCharts() {
    console.log('Initializing main charts...');
    
    // Spending Chart (Income vs Expenses)
    const spendingCtx = document.getElementById('spendingChart');
    if (spendingCtx) {
        try {
            currentCharts.spending = new Chart(spendingCtx, {
                type: 'line',
                data: {
                    labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov'],
                    datasets: [{
                        label: 'Income',
                        data: [65000,72000,68000,75000,78000,82000,80000,85000,83000,87000,85000],
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40,167,69,0.1)',
                        tension: 0.3,
                        fill: true,
                        borderWidth: 3
                    }, {
                        label: 'Expenses',
                        data: [52000,58000,54000,60000,62000,65000,63000,68000,65000,70000,62000],
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220,53,69,0.1)',
                        tension: 0.3,
                        fill: true,
                        borderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ₹' + context.raw.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₹' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
            console.log('Spending chart created successfully');
        } catch (error) {
            console.error('Error creating spending chart:', error);
        }
    }

    // Budget Chart
    const budgetCtx = document.getElementById('budgetChart');
    if (budgetCtx) {
        try {
            currentCharts.budget = new Chart(budgetCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Used', 'Remaining'],
                    datasets: [{
                        data: [62840, 12160],
                        backgroundColor: ['#dc3545','#28a745'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ₹${value.toLocaleString()} (${percentage}%)`;
                                }
                            }
                        }
                    },
                    cutout: '60%'
                }
            });
            console.log('Budget chart created successfully');
        } catch (error) {
            console.error('Error creating budget chart:', error);
        }
    }

    // Category Chart
    const categoryCtx = document.getElementById('categoryChart');
    if (categoryCtx) {
        try {
            currentCharts.category = new Chart(categoryCtx, {
                type: 'bar',
                data: {
                    labels: ['Food','Travel','Shopping','Utilities','Entertainment','Healthcare'],
                    datasets: [{
                        label: 'Amount (₹)',
                        data: [12750,18500,7200,6400,3100,4200],
                        backgroundColor: ['#4361ee','#3f37c9','#4cc9f0','#e63946','#ff9e00','#7209b7'],
                        borderWidth: 0,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return '₹' + context.raw.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₹' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
            console.log('Category chart created successfully');
        } catch (error) {
            console.error('Error creating category chart:', error);
        }
    }
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
    const currentPage = window.location.pathname.split('/').pop() || 'dashboard.php';
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPage || (currentPage === '' && href === 'dashboard.php')) {
            link.classList.add('active');
        }
        
        // Add click tracking for analytics
        link.addEventListener('click', function() {
            console.log('Navigation:', this.getAttribute('href'));
        });
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
                // Update aria-expanded for accessibility
                this.setAttribute('aria-expanded', 
                    sidebar.classList.contains('mobile-open').toString()
                );
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

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Family Finance Shield - Initializing...');
    
    // Initialize all components
    initializeDashboardCharts();
    initializeMainCharts();
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
    
    // Add CSS for enhanced animations
    const style = document.createElement('style');
    style.textContent = `
        .loading-spinner {
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translate(-50%, -40%); }
            to { opacity: 1; transform: translate(-50%, -50%); }
        }
        
        .mobile-menu-toggle {
            transition: all 0.3s ease;
        }
        
        .sidebar {
            transition: transform 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                height: 100vh;
                z-index: 999;
                transform: translateX(-100%);
            }
            
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0 !important;
            }
        }
        
        .alert-toast {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .alert-toast:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .chart-container {
            position: relative;
            height: 400px;
        }
        
        canvas {
            max-width: 100%;
            height: auto !important;
        }

        .expense-form.collapsed {
            display: none;
        }

        /* Enhanced toast animations */
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            to {
                opacity: 0;
                transform: translateX(100%);
            }
        }

        /* Touch device improvements */
        .touch-device .btn {
            min-height: 44px;
            min-width: 44px;
        }

        .touch-device .nav-link {
            padding: 20px 15px;
        }
    `;
    document.head.appendChild(style);
    
    console.log('Family Finance Shield - Initialization complete!');
});

// Export functions for global access
window.downloadPDFReport = downloadPDFReport;
window.printReport = printReport;
window.formatCurrency = formatCurrency;
window.formatDate = formatDate;
window.formatNumber = formatNumber;
window.toggleProfileDropdown = toggleProfileDropdown;
window.showLoading = showLoading;
window.hideLoading = hideLoading;

// Error handling for charts
window.addEventListener('error', function(e) {
    console.error('JavaScript Error:', e.error);
});

// Clean up charts on page unload to prevent memory leaks
window.addEventListener('beforeunload', function() {
    Object.values(currentCharts).forEach(chart => {
        if (chart && typeof chart.destroy === 'function') {
            chart.destroy();
        }
    });
});

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

// Enhanced mobile menu close on navigation
document.addEventListener('click', function(e) {
    if (e.target.matches('.nav-link') || e.target.closest('.nav-link')) {
        const sidebar = document.getElementById('sidebar');
        if (sidebar && sidebar.classList.contains('mobile-open')) {
            sidebar.classList.remove('mobile-open');
        }
    }
});

// Keyboard navigation support
document.addEventListener('keydown', function(e) {
    // Close dropdowns with Escape key
    if (e.key === 'Escape') {
        const dropdowns = document.querySelectorAll('.user-profile-dropdown.show');
        dropdowns.forEach(dropdown => {
            dropdown.classList.remove('show');
        });
        
        const sidebar = document.getElementById('sidebar');
        if (sidebar && sidebar.classList.contains('mobile-open')) {
            sidebar.classList.remove('mobile-open');
        }
    }
});