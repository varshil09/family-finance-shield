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
            const password = document.getElementById('password');
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

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.style.display = 'none';
                }
            }, 300);
        }, 5000);
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
    });
}

// Mobile Menu Toggle
function initializeMobileMenu() {
    console.log('Initializing mobile menu...');
    
    const menuToggle = document.createElement('button');
    menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
    menuToggle.className = 'mobile-menu-toggle';
    menuToggle.setAttribute('aria-label', 'Toggle menu');
    
    document.body.appendChild(menuToggle);
    
    menuToggle.addEventListener('click', function() {
        const sidebar = document.getElementById('sidebar');
        if (sidebar) {
            sidebar.classList.toggle('mobile-open');
        }
    });
    
    // Check screen size and show/hide toggle
    function checkScreenSize() {
        if (window.innerWidth <= 768) {
            menuToggle.style.display = 'flex';
        } else {
            menuToggle.style.display = 'none';
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
    
    // Form submission loading
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                submitBtn.disabled = true;
                
                // Revert after 3 seconds (in case of error)
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 3000);
            }
        });
    });
}

function showLoading() {
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.style.opacity = '0.7';
        mainContent.style.pointerEvents = 'none';
    }
    
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'loading-spinner';
    loadingDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    loadingDiv.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 1000;
        background: white;
        padding: 20px 30px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
    `;
    
    document.body.appendChild(loadingDiv);
}

// Profile dropdown functionality
function toggleProfileDropdown() {
    const dropdown = document.getElementById('profileDropdown');
    if (dropdown) {
        dropdown.classList.toggle('show');
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('profileDropdown');
    const userProfile = document.querySelector('.user-profile');
    
    if (dropdown && userProfile && !userProfile.contains(e.target)) {
        dropdown.classList.remove('show');
    }
});

// Close dropdown when pressing escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const dropdown = document.getElementById('profileDropdown');
        if (dropdown) {
            dropdown.classList.remove('show');
        }
    }
});

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
    
    // Add CSS for loading states
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
        
        .alert {
            transition: opacity 0.3s ease;
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
    `;
    document.head.appendChild(style);
    
    console.log('Family Finance Shield - Initialization complete!');
});

// Export functions for global access
window.downloadPDFReport = downloadPDFReport;
window.printReport = printReport;
window.formatCurrency = formatCurrency;
window.formatDate = formatDate;
window.toggleProfileDropdown = toggleProfileDropdown;

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
    showLoading();
    setTimeout(() => {
        const loadingSpinner = document.querySelector('.loading-spinner');
        if (loadingSpinner) {
            loadingSpinner.remove();
        }
        alert('PDF export feature would be implemented here');
    }, 1500);
}

function printReport() {
    showLoading();
    setTimeout(() => {
        const loadingSpinner = document.querySelector('.loading-spinner');
        if (loadingSpinner) {
            loadingSpinner.remove();
        }
        window.print();
    }, 1000);
}