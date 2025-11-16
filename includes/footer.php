</div> <!-- Close container -->

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        });

        // Global profile dropdown functionality
        function toggleProfileDropdown(event) {
            event.stopPropagation();
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

        // Mobile menu functionality
        function initializeMobileMenu() {
            const menuToggle = document.querySelector('.mobile-menu-toggle');
            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    const sidebar = document.getElementById('sidebar');
                    if (sidebar) {
                        sidebar.classList.toggle('mobile-open');
                    }
                });
            }
        }

        // Close mobile menu when clicking on a link
        document.addEventListener('click', function(e) {
            if (e.target.matches('.nav-link') || e.target.closest('.nav-link')) {
                const sidebar = document.getElementById('sidebar');
                if (sidebar && sidebar.classList.contains('mobile-open')) {
                    sidebar.classList.remove('mobile-open');
                }
            }
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            initializeMobileMenu();
            
            // Add touch support for mobile devices
            if ('ontouchstart' in window) {
                document.body.classList.add('touch-device');
            }
        });

        // Loading state helper
        function showLoading(message = 'Loading...') {
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

        // Make functions globally available
        window.showLoading = showLoading;
        window.hideLoading = hideLoading;
        window.toggleProfileDropdown = toggleProfileDropdown;
    </script>
</body>
</html>