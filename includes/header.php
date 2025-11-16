<?php if (isset($_SESSION['message'])): ?>
    <div class="alert-toast alert-success">
        <div class="toast-content">
            <i class="fas fa-check-circle"></i>
            <span><?= $_SESSION['message']; ?></span>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
        <?php unset($_SESSION['message']); ?>
    </div>
<?php endif; ?>
<?php if (isset($_SESSION['error'])): ?>
    <div class="alert-toast alert-danger">
        <div class="toast-content">
            <i class="fas fa-exclamation-circle"></i>
            <span><?= $_SESSION['error']; ?></span>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
        <?php unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<!-- SIMPLIFIED BREADCRUMB WITH SINGLE CLICKABLE ELEMENT -->
<div class="breadcrumb">
    <div class="breadcrumb-mobile">
        <div class="breadcrumb-main">
            <div class="breadcrumb-current" id="breadcrumbCurrent">
                <i class="fas fa-bars"></i>
                <span id="breadcrumbCurrentText"><?= getCurrentPageTitle() ?></span>
                <i class="fas fa-chevron-down" id="breadcrumbArrow"></i>
            </div>
        </div>
    </div>
    
    <!-- Navigation Menu -->
    <div class="breadcrumb-nav-menu" id="breadcrumbNavMenu">
        <div class="breadcrumb-nav-header">
            <i class="fas fa-compass"></i>
            <span>Quick Navigation</span>
        </div>
        <div class="breadcrumb-nav-items">
            <a href="dashboard.php" class="breadcrumb-nav-item <?= getCurrentPage() == 'dashboard.php' ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-home"></i>
                </div>
                <span class="breadcrumb-nav-text">Dashboard</span>
            </a>
            
            <a href="expenses.php" class="breadcrumb-nav-item <?= getCurrentPage() == 'expenses.php' ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-receipt"></i>
                </div>
                <span class="breadcrumb-nav-text">Expenses</span>
            </a>
            
            <?php if ($_SESSION['user_role'] == 'admin'): ?>
            <a href="approvals.php" class="breadcrumb-nav-item <?= getCurrentPage() == 'approvals.php' ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-check-circle"></i>
                    <?php 
                    require_once 'models/Expense.php';
                    $expenseModel = new Expense();
                    $pending_count = $expenseModel->getPendingApprovalsCount($_SESSION['family_id']);
                    if ($pending_count > 0): ?>
                    <span class="breadcrumb-nav-badge"><?= $pending_count ?></span>
                    <?php endif; ?>
                </div>
                <span class="breadcrumb-nav-text">Approvals</span>
            </a>
            <?php endif; ?>
            
            <a href="family.php" class="breadcrumb-nav-item <?= getCurrentPage() == 'family.php' ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-users"></i>
                </div>
                <span class="breadcrumb-nav-text">Family</span>
            </a>
            
            <a href="reports.php" class="breadcrumb-nav-item <?= getCurrentPage() == 'reports.php' ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <span class="breadcrumb-nav-text">Reports</span>
            </a>
            
            <a href="profile.php" class="breadcrumb-nav-item <?= getCurrentPage() == 'profile.php' ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-user"></i>
                </div>
                <span class="breadcrumb-nav-text">Profile</span>
            </a>
        </div>
    </div>
</div>

<!-- Overlay for closing menu -->
<div class="breadcrumb-overlay" id="breadcrumbOverlay"></div>

<?php
// Helper function to get current page title
function getCurrentPageTitle() {
    $page = getCurrentPage();
    $titles = [
        'dashboard.php' => 'Dashboard',
        'expenses.php' => 'Expenses', 
        'approvals.php' => 'Approvals',
        'family.php' => 'Family',
        'reports.php' => 'Reports',
        'profile.php' => 'Profile',
        'settings.php' => 'Settings'
    ];
    return $titles[$page] ?? 'Dashboard';
}
?>