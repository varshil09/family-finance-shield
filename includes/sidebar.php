<?php
$current_page = getCurrentPage();
$pending_count = 0;

// Only calculate pending count for admin users to avoid unnecessary queries
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin') {
    require_once 'models/Expense.php';
    $expenseModel = new Expense();
    $pending_count = $expenseModel->getPendingApprovalsCount($_SESSION['family_id']);
}

// Get user avatar for sidebar
require_once 'models/User.php';
$userModel = new User();
$current_user = $userModel->getUserById($_SESSION['user_id']);
?>
<div class="sidebar" id="sidebar">
    <div class="logo">
        <h1>FFS</h1>
        <div class="logo-full">FAMILY FINANCE SHIELD</div>
    </div>
    <ul class="nav-menu">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?= $current_page == 'dashboard' ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-home"></i>
                </div>
                <span class="nav-text">Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="expenses.php" class="nav-link <?= $current_page == 'expenses' ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-receipt"></i>
                </div>
                <span class="nav-text">Expenses</span>
            </a>
        </li>
        <?php if ($_SESSION['user_role'] == 'admin'): ?>
        <li class="nav-item">
            <a href="approvals.php" class="nav-link <?= $current_page == 'approvals' ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-check-circle"></i>
                    <?php if ($pending_count > 0): ?>
                    <span class="nav-badge"><?= $pending_count ?></span>
                    <?php endif; ?>
                </div>
                <span class="nav-text">Approvals</span>
            </a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
            <a href="family.php" class="nav-link <?= $current_page == 'family' ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-users"></i>
                </div>
                <span class="nav-text">Family</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="reports.php" class="nav-link <?= $current_page == 'reports' ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <span class="nav-text">Reports</span>
            </a>
        </li>
    </ul>
    
    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="user-profile-sidebar">
            <div class="user-avatar-sidebar">
                <?php if (!empty($current_user['avatar']) && file_exists($current_user['avatar'])): ?>
                    <img src="<?= $current_user['avatar'] ?>" alt="Profile Avatar">
                <?php else: ?>
                    <?= substr($_SESSION['user_name'], 0, 2) ?>
                <?php endif; ?>
            </div>
            <div class="user-info-sidebar">
                <div class="user-name"><?= $_SESSION['user_name'] ?></div>
                <div class="user-role"><?= ucfirst($_SESSION['user_role']) ?></div>
            </div>
        </div>
    </div>
</div>

<script>
    // Mobile menu functionality
    function initializeMobileMenu() {
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

    // Initialize mobile menu
    document.addEventListener('DOMContentLoaded', function() {
        initializeMobileMenu();
    });
</script>