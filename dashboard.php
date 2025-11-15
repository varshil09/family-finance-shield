<?php
require_once 'config.php';
checkAuth();
require_once 'models/Expense.php';
require_once 'models/User.php';
require_once 'models/Family.php';

$expenseModel = new Expense();
$userModel = new User();
$familyModel = new Family();

$family_id = $_SESSION['family_id'];
$user_id = $_SESSION['user_id'];

// Get data for dashboard
$total_expenses = $expenseModel->getTotalExpenses($family_id);
$recent_expenses = $expenseModel->getRecentExpenses($family_id, 5);
$smart_suggestions = $expenseModel->getSmartSuggestions($family_id);
$budget_alerts = $expenseModel->getBudgetAlerts($family_id);
$family_details = $familyModel->getFamilyDetails($family_id);
$pending_count = $expenseModel->getPendingApprovalsCount($family_id);

// Calculate budget utilization
$monthly_budget = $family_details['monthly_budget'] ?? 75000;
$budget_used = $total_expenses;
$budget_remaining = max(0, $monthly_budget - $budget_used);
$budget_percentage = $monthly_budget > 0 ? min(100, ($budget_used / $monthly_budget) * 100) : 0;

// Get expense breakdown
$expense_breakdown = $expenseModel->getExpenseBreakdown($family_id);

// Get monthly trends for chart
$monthly_trends = $expenseModel->getMonthlyTrends($family_id);

// Quick stats
$total_family_members = count($userModel->getFamilyMembers($family_id));

// Get current user with avatar
$current_user = $userModel->getUserById($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Family Finance Shield</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h2>Family Dashboard</h2>
                <div class="header-actions">
                    <div class="user-profile" onclick="toggleProfileDropdown(event)">
                        <div class="user-avatar">
                            <?php if (!empty($current_user['avatar']) && file_exists($current_user['avatar'])): ?>
                                <img src="<?= $current_user['avatar'] ?>" alt="Profile Avatar">
                            <?php else: ?>
                                <?= substr($_SESSION['user_name'], 0, 2) ?>
                            <?php endif; ?>
                        </div>
                        <div class="user-profile-info">
                            <div class="user-profile-name"><?= $_SESSION['user_name'] ?></div>
                            <div class="user-profile-role"><?= ucfirst($_SESSION['user_role']) ?></div>
                        </div>
                        <i class="fas fa-chevron-down profile-arrow"></i>
                        
                        <!-- Profile Dropdown -->
                        <div class="user-profile-dropdown" id="profileDropdown">
                            <a href="profile.php" class="dropdown-item">
                                <i class="fas fa-user"></i>
                                <span>Profile</span>
                            </a>
                            <a href="settings.php" class="dropdown-item">
                                <i class="fas fa-cog"></i>
                                <span>Settings</span>
                            </a>
                            <a href="logout.php" class="dropdown-item" onclick="return confirm('Are you sure you want to logout?')">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Smart Alerts Section -->
            <?php if (!empty($smart_suggestions)): ?>
            <div class="alert alert-info slide-up">
                <i class="fas fa-lightbulb"></i>
                <div><strong>Smart Suggestion:</strong> <?= $smart_suggestions ?></div>
            </div>
            <?php endif; ?>

            <?php if (!empty($budget_alerts)): ?>
            <div class="alert alert-warning slide-up">
                <i class="fas fa-exclamation-triangle"></i>
                <div><strong>Budget Alert:</strong> <?= $budget_alerts ?></div>
            </div>
            <?php endif; ?>

            <?php if ($pending_count > 0 && $_SESSION['user_role'] == 'admin'): ?>
            <div class="alert alert-warning slide-up">
                <i class="fas fa-clock"></i>
                <div>
                    <strong>Approval Needed:</strong> You have <?= $pending_count ?> expense(s) waiting for approval.
                    <a href="approvals.php" class="btn btn-outline" style="margin-left: 10px;">Review Now</a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Main Stats Grid -->
            <div class="stats-container">
                <div class="stat-card slide-up">
                    <div class="stat-icon icon-income">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-value">₹<?= number_format($monthly_budget) ?></div>
                    <div class="stat-title">Monthly Budget</div>
                    <div class="stat-trend">
                        <span class="trend-up"><i class="fas fa-wallet"></i> Family Budget</span>
                    </div>
                </div>
                
                <div class="stat-card slide-up delayed-1">
                    <div class="stat-icon icon-expense">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-value">₹<?= number_format($total_expenses) ?></div>
                    <div class="stat-title">Total Expenses</div>
                    <div class="stat-trend">
                        <span class="trend-<?= $budget_percentage > 80 ? 'down' : 'up' ?>">
                            <i class="fas fa-<?= $budget_percentage > 80 ? 'exclamation-triangle' : 'check-circle' ?>"></i>
                            <?= number_format($budget_percentage, 1) ?>% used
                        </span>
                    </div>
                </div>
                
                <div class="stat-card slide-up delayed-2">
                    <div class="stat-icon icon-savings">
                        <i class="fas fa-piggy-bank"></i>
                    </div>
                    <div class="stat-value">₹<?= number_format($budget_remaining) ?></div>
                    <div class="stat-title">Budget Remaining</div>
                    <div class="stat-trend">
                        <span class="trend-<?= $budget_remaining < ($monthly_budget * 0.2) ? 'down' : 'up' ?>">
                            <i class="fas fa-<?= $budget_remaining < ($monthly_budget * 0.2) ? 'exclamation-circle' : 'coins' ?>"></i>
                            <?= number_format(($budget_remaining / $monthly_budget) * 100, 1) ?>% left
                        </span>
                    </div>
                </div>
                
                <div class="stat-card slide-up delayed-3">
                    <div class="stat-icon icon-budget">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value"><?= $total_family_members ?></div>
                    <div class="stat-title">Family Members</div>
                    <div class="stat-trend">
                        <span class="trend-up">
                            <i class="fas fa-user-plus"></i>
                            <?= $total_family_members > 1 ? 'Active' : 'You only' ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="form-row">
                <!-- Budget Utilization Chart -->
                <div class="form-col">
                    <div class="card slide-up">
                        <div class="card-header">
                            <h3><i class="fas fa-chart-pie"></i> Budget Utilization</h3>
                            <span class="status <?= $budget_percentage > 80 ? 'status-pending' : 'status-approved' ?>">
                                <?= number_format($budget_percentage, 1) ?>% Used
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="budget-meter">
                                <div class="meter-bar">
                                    <div class="meter-fill" style="width: <?= $budget_percentage ?>%; 
                                        background: <?= $budget_percentage > 80 ? 'var(--danger)' : ($budget_percentage > 60 ? 'var(--warning)' : 'var(--success)') ?>;">
                                    </div>
                                </div>
                                <div class="meter-labels">
                                    <span>₹0</span>
                                    <span>₹<?= number_format($monthly_budget) ?></span>
                                </div>
                            </div>
                            <div class="budget-details">
                                <div class="budget-item">
                                    <span class="budget-label">Used:</span>
                                    <span class="budget-amount">₹<?= number_format($budget_used) ?></span>
                                </div>
                                <div class="budget-item">
                                    <span class="budget-label">Remaining:</span>
                                    <span class="budget-amount">₹<?= number_format($budget_remaining) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="form-col">
                    <div class="card slide-up delayed-1">
                        <div class="card-header">
                            <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                        </div>
                        <div class="card-body">
                            <div class="quick-actions">
                                <a href="expenses.php" class="quick-action-btn">
                                    <div class="action-icon">
                                        <i class="fas fa-plus-circle"></i>
                                    </div>
                                    <span>Add Expense</span>
                                </a>
                                <a href="reports.php" class="quick-action-btn">
                                    <div class="action-icon">
                                        <i class="fas fa-chart-pie"></i>
                                    </div>
                                    <span>View Reports</span>
                                </a>
                                <a href="family.php" class="quick-action-btn">
                                    <div class="action-icon">
                                        <i class="fas fa-user-plus"></i>
                                    </div>
                                    <span>Manage Family</span>
                                </a>
                                <?php if ($_SESSION['user_role'] == 'admin'): ?>
                                <a href="approvals.php" class="quick-action-btn">
                                    <div class="action-icon">
                                        <i class="fas fa-check-circle"></i>
                                        <?php if ($pending_count > 0): ?>
                                        <span class="badge pulse"><?= $pending_count ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <span>Pending Approvals</span>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="form-row">
                <!-- Monthly Trend Chart -->
                <div class="form-col">
                    <div class="card slide-up">
                        <div class="card-header">
                            <h3><i class="fas fa-chart-line"></i> Monthly Spending Trend</h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="monthlyTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category Distribution -->
                <div class="form-col">
                    <div class="card slide-up delayed-1">
                        <div class="card-header">
                            <h3><i class="fas fa-chart-pie"></i> Expense Categories</h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="categoryChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity & Top Categories -->
            <div class="form-row">
                <!-- Recent Expenses -->
                <div class="form-col">
                    <div class="card slide-up">
                        <div class="card-header">
                            <h3><i class="fas fa-history"></i> Recent Family Expenses</h3>
                            <a href="expenses.php" class="btn btn-outline">View All</a>
                        </div>
                        <div class="card-body">
                            <div class="recent-activities">
                                <?php if (!empty($recent_expenses)): ?>
                                    <?php foreach ($recent_expenses as $index => $expense): ?>
                                    <div class="activity-item slide-in delayed-<?= $index + 1 ?>">
                                        <div class="activity-icon">
                                            <i class="fas fa-receipt"></i>
                                        </div>
                                        <div class="activity-details">
                                            <div class="activity-title"><?= htmlspecialchars($expense['description']) ?></div>
                                            <div class="activity-meta">
                                                <span class="activity-category"><?= $expense['category'] ?></span>
                                                <span class="activity-date"><?= date('M j', strtotime($expense['expense_date'])) ?></span>
                                            </div>
                                        </div>
                                        <div class="activity-amount">
                                            ₹<?= number_format($expense['amount']) ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-receipt"></i>
                                        <p>No expenses recorded yet</p>
                                        <a href="expenses.php" class="btn btn-primary">Add Your First Expense</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Categories -->
                <div class="form-col">
                    <div class="card slide-up delayed-1">
                        <div class="card-header">
                            <h3><i class="fas fa-tags"></i> Top Spending Categories</h3>
                        </div>
                        <div class="card-body">
                            <div class="category-list">
                                <?php if (!empty($expense_breakdown)): ?>
                                    <?php foreach ($expense_breakdown as $index => $category): ?>
                                    <div class="category-item slide-in delayed-<?= $index + 2 ?>">
                                        <div class="category-info">
                                            <span class="category-name"><?= $category['category'] ?></span>
                                            <span class="category-amount">₹<?= number_format($category['amount']) ?></span>
                                        </div>
                                        <div class="category-bar">
                                            <div class="category-fill" 
                                                 style="width: <?= $category['percentage'] ?>%; 
                                                        background: <?= $category['color'] ?>;">
                                            </div>
                                        </div>
                                        <span class="category-percentage"><?= $category['percentage'] ?>%</span>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-chart-pie"></i>
                                        <p>No category data available</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Recurring Expenses -->
            <div class="card slide-up">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-alt"></i> Upcoming Recurring Expenses</h3>
                </div>
                <div class="card-body">
                    <div class="upcoming-expenses">
                        <?php
                        $recurring_expenses = $expenseModel->getUpcomingRecurringExpenses($family_id);
                        if (!empty($recurring_expenses)):
                        ?>
                            <table class="table hover-table">
                                <thead>
                                    <tr>
                                        <th>Description</th>
                                        <th>Category</th>
                                        <th>Amount</th>
                                        <th>Next Due</th>
                                        <th>Frequency</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recurring_expenses as $index => $expense): ?>
                                    <tr class="slide-in delayed-<?= $index + 1 ?>">
                                        <td><?= htmlspecialchars($expense['description']) ?></td>
                                        <td>
                                            <span class="category-tag" style="background-color: <?= getCategoryColor($expense['category']) ?>">
                                                <?= $expense['category'] ?>
                                            </span>
                                        </td>
                                        <td><strong>₹<?= number_format($expense['amount']) ?></strong></td>
                                        <td><?= date('M j, Y', strtotime($expense['next_due_date'])) ?></td>
                                        <td>
                                            <span class="status status-approved">
                                                <?= ucfirst($expense['recurrence']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-calendar-alt"></i>
                                <p>No recurring expenses set up</p>
                                <small>Add recurring expenses to see them here</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        // Initialize Dashboard Charts with Real Data
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Initializing dashboard charts...');
            
            // Monthly Trend Chart with Real Data
            const trendCtx = document.getElementById('monthlyTrendChart');
            if (trendCtx) {
                console.log('Found monthly trend chart element');
                
                const monthlyData = <?= json_encode($monthly_trends) ?>;
                console.log('Monthly data:', monthlyData);
                
                try {
                    new Chart(trendCtx, {
                        type: 'line',
                        data: {
                            labels: monthlyData.labels,
                            datasets: [{
                                label: 'Monthly Expenses',
                                data: monthlyData.expenses,
                                borderColor: '#4361ee',
                                backgroundColor: 'rgba(67, 97, 238, 0.1)',
                                tension: 0.4,
                                fill: true,
                                borderWidth: 3
                            }, {
                                label: 'Monthly Budget',
                                data: monthlyData.budget,
                                borderColor: '#ff9e00',
                                borderDash: [5, 5],
                                backgroundColor: 'transparent',
                                borderWidth: 2
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
                    console.log('Monthly trend chart created successfully');
                } catch (error) {
                    console.error('Error creating monthly trend chart:', error);
                }
            } else {
                console.log('Monthly trend chart element not found');
            }

            // Category Chart with Real Data
            const categoryCtx = document.getElementById('categoryChart');
            if (categoryCtx) {
                console.log('Found category chart element');
                
                const breakdown = <?= json_encode($expense_breakdown) ?>;
                console.log('Category breakdown:', breakdown);
                
                try {
                    new Chart(categoryCtx, {
                        type: 'doughnut',
                        data: {
                            labels: breakdown.map(item => item.category),
                            datasets: [{
                                data: breakdown.map(item => item.amount),
                                backgroundColor: breakdown.map(item => item.color),
                                borderWidth: 2,
                                borderColor: '#ffffff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right',
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
                            }
                        }
                    });
                    console.log('Category chart created successfully');
                } catch (error) {
                    console.error('Error creating category chart:', error);
                }
            } else {
                console.log('Category chart element not found');
            }
        });

        // Profile dropdown functionality
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
            if (dropdown && !dropdown.contains(e.target) && !e.target.closest('.user-profile')) {
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
    </script>
</body>
</html>

<?php
// Helper functions
function getCategoryColor($category) {
    $colors = [
        'Food & Dining' => '#4361ee',
        'Travel' => '#3f37c9', 
        'Shopping' => '#4cc9f0',
        'Utilities' => '#e63946',
        'Entertainment' => '#ff9e00',
        'Healthcare' => '#7209b7',
        'Education' => '#4caf50',
        'Rent' => '#ff6b6b',
        'Other' => '#9c27b0'
    ];
    return $colors[$category] ?? '#6c757d';
}
?>