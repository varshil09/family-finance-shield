</div> <!-- Close container -->

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Load script only once -->
    <script src="assets/js/script.js"></script>
    
    <script>
        // Enhanced toast notification handling
        document.addEventListener('DOMContentLoaded', function() {
            const toasts = document.querySelectorAll('.alert-toast');
            toasts.forEach(function(toast) {
                // Set up manual close functionality
                const closeBtn = toast.querySelector('.toast-close');
                if (closeBtn) {
                    closeBtn.addEventListener('click', function() {
                        toast.style.animation = 'fadeOut 0.3s ease-in forwards';
                        setTimeout(() => {
                            if (toast.parentNode) {
                                toast.remove();
                            }
                        }, 300);
                    });
                }
                
                // Auto-hide after 5 seconds
                const autoHide = setTimeout(function() {
                    if (toast.parentNode) {
                        toast.style.animation = 'fadeOut 0.3s ease-in forwards';
                        setTimeout(() => {
                            if (toast.parentNode) {
                                toast.remove();
                            }
                        }, 300);
                    }
                }, 5000);

                // Clear timeout if user hovers over toast
                toast.addEventListener('mouseenter', function() {
                    clearTimeout(autoHide);
                });

                // Restart timeout when user leaves toast
                toast.addEventListener('mouseleave', function() {
                    setTimeout(() => {
                        if (toast.parentNode) {
                            toast.style.animation = 'fadeOut 0.3s ease-in forwards';
                            setTimeout(() => {
                                if (toast.parentNode) {
                                    toast.remove();
                                }
                            }, 300);
                        }
                    }, 3000);
                });
            });

            // Initialize breadcrumb functionality if function exists
            if (typeof initializeBreadcrumbs === 'function') {
                initializeBreadcrumbs();
            }
        });

        // Global close all dropdowns function
        function closeAllDropdowns() {
            // Close profile dropdown
            const profileDropdown = document.getElementById('profileDropdown');
            if (profileDropdown) {
                profileDropdown.classList.remove('show');
            }
            
            // Close breadcrumb menu
            if (typeof closeBreadcrumbMenu === 'function') {
                closeBreadcrumbMenu();
            }
        }

        // Global profile dropdown functionality
        function toggleProfileDropdown(event) {
            event.stopPropagation();
            const dropdown = document.getElementById('profileDropdown');
            if (dropdown) {
                dropdown.classList.toggle('show');
            }
        }
// 
        // Close all dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            const profileDropdown = document.getElementById('profileDropdown');
            const userProfile = document.querySelector('.user-profile');
            const breadcrumb = document.querySelector('.breadcrumb');
            const breadcrumbMenu = document.getElementById('breadcrumbNavMenu');
            
            // Close profile dropdown if clicking outside
            if (profileDropdown && profileDropdown.classList.contains('show') && 
                userProfile && !userProfile.contains(e.target) && 
                !profileDropdown.contains(e.target)) {
                profileDropdown.classList.remove('show');
            }
            
            // Close breadcrumb menu if clicking outside AND it's currently open
            if (breadcrumbMenu && breadcrumbMenu.classList.contains('show') && 
                breadcrumb && !breadcrumb.contains(e.target)) {
                closeBreadcrumbMenu();
            }
        });

        // Close dropdown when pressing escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAllDropdowns();
            }
        });

        // Make functions globally available
        window.showLoading = showLoading;
        window.hideLoading = hideLoading;
        window.toggleProfileDropdown = toggleProfileDropdown;
        window.toggleBreadcrumbMenu = toggleBreadcrumbMenu;
        window.closeBreadcrumbMenu = closeBreadcrumbMenu;
        window.closeAllDropdowns = closeAllDropdowns;
    </script>
</body>
</html>