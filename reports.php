<?php
require_once 'config.php';
checkAuth();
require_once 'models/Report.php';
require_once 'models/Expense.php';
require_once 'models/User.php';

$reportModel = new Report();
$expenseModel = new Expense();
$userModel = new User();

$family_id = $_SESSION['family_id'];
$month = $_GET['month'] ?? date('Y-m');
$year = $_GET['year'] ?? date('Y');

// Auto-refresh after report generation
if (isset($_GET['refresh'])) {
    header('Location: reports.php?month=' . $month . '&year=' . $year);
    exit;
}

// Get real data from models
$monthly_report = $reportModel->getMonthlyReport($family_id, $month);
$category_report = $reportModel->getCategoryReport($family_id, $month);
$member_report = $reportModel->getMemberReport($family_id, $month);
$monthly_trends = $reportModel->getMonthlyTrends($family_id, $year);

// Calculate additional metrics
$total_amount = array_sum(array_column($category_report, 'total_amount'));
$budget_utilization = $monthly_report['total_expenses'] > 0 ? round(($monthly_report['total_expenses'] / 75000) * 100) : 0;
$savings_potential = 75000 - $monthly_report['total_expenses'];

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

function getAvatarColor($name) {
    $colors = ['#4361ee', '#3f37c9', '#4cc9f0', '#e63946', '#ff9e00', '#7209b7'];
    $hash = crc32($name);
    return $colors[abs($hash) % count($colors)];
}

// Get current user with avatar
$current_user = $userModel->getUserById($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Family Finance Shield</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h2>Financial Reports</h2>
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

            <!-- Report Period Selector -->
            <div class="card">
                <div class="card-header">
                    <h3>Report Period</h3>
                    <span class="status <?= $budget_utilization > 80 ? 'status-pending' : 'status-approved' ?>">
                        <?= $budget_utilization ?>% Budget Used
                    </span>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group floating-label">
                                <input type="month" name="month" value="<?= $month ?>" class="form-control" 
                                       onchange="window.location.href='reports.php?month=' + this.value + '&year=<?= $year ?>'">
                                <label for="month">Select Month</label>
                                <div class="form-icon">
                                    <i class="fas fa-calendar"></i>
                                </div>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group floating-label">
                                <select name="year" class="form-control" onchange="window.location.href='reports.php?year=' + this.value + '&month=<?= $month ?>'">
                                    <?php for ($y = 2022; $y <= date('Y'); $y++): ?>
                                        <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                                <label for="year">Select Year</label>
                                <div class="form-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <div class="quick-stats">
                                <div class="quick-stat">
                                    <i class="fas fa-calendar" style="color: #4361ee;"></i>
                                    <div>
                                        <div class="quick-stat-value"><?= date('F Y', strtotime($month . '-01')) ?></div>
                                        <div class="quick-stat-label">Reporting Period</div>
                                    </div>
                                </div>
                                <div class="quick-stat">
                                    <i class="fas fa-receipt" style="color: #4cc9f0;"></i>
                                    <div>
                                        <div class="quick-stat-value"><?= $monthly_report['expense_count'] ?></div>
                                        <div class="quick-stat-label">Total Transactions</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats Overview -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon icon-expense">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-value">₹<?= number_format($monthly_report['total_expenses']) ?></div>
                    <div class="stat-title">Total Expenses</div>
                    <div class="stat-trend">
                        <span class="trend-<?= $monthly_report['total_expenses'] > 60000 ? 'down' : 'up' ?>">
                            <i class="fas fa-<?= $monthly_report['total_expenses'] > 60000 ? 'chart-line' : 'check-circle' ?>"></i>
                            <?= $monthly_report['total_expenses'] > 60000 ? 'Above Average' : 'On Track' ?>
                        </span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon icon-income">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <div class="stat-value">₹<?= number_format($monthly_report['average_expense']) ?></div>
                    <div class="stat-title">Average per Expense</div>
                    <div class="stat-trend">
                        <span class="trend-up">
                            <i class="fas fa-balance-scale"></i>
                            Per Transaction
                        </span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon icon-savings">
                        <i class="fas fa-piggy-bank"></i>
                    </div>
                    <div class="stat-value">₹<?= number_format($savings_potential) ?></div>
                    <div class="stat-title">Budget Remaining</div>
                    <div class="stat-trend">
                        <span class="trend-<?= $savings_potential < 15000 ? 'down' : 'up' ?>">
                            <i class="fas fa-<?= $savings_potential < 15000 ? 'exclamation-triangle' : 'coins' ?>"></i>
                            <?= $savings_potential < 15000 ? 'Low' : 'Good' ?>
                        </span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon icon-budget">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div class="stat-value"><?= count($category_report) ?></div>
                    <div class="stat-title">Categories Used</div>
                    <div class="stat-trend">
                        <span class="trend-up">
                            <i class="fas fa-tags"></i>
                            Spending Diversity
                        </span>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="form-row">
                <!-- Monthly Trends Line Chart -->
                <div class="form-col">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-chart-line"></i> Monthly Expense Trends - <?= $year ?></h3>
                            <div class="chart-actions">
                                <button class="btn btn-outline btn-sm" onclick="toggleChartType('monthlyTrendsChart')">
                                    <i class="fas fa-exchange-alt"></i> Toggle
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="monthlyTrendsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category Distribution -->
                <div class="form-col">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-chart-pie"></i> Spending Distribution - <?= date('M Y', strtotime($month . '-01')) ?></h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="categoryPieChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Export Actions -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-download"></i> Export Report - <?= date('F Y', strtotime($month . '-01')) ?></h3>
                </div>
                <div class="card-body">
                    <div class="export-actions">
                        <button class="btn btn-primary" onclick="generatePDFReport()">
                            <i class="fas fa-file-pdf"></i> Save as PDF
                        </button>
                        <button class="btn btn-outline" onclick="printEnhancedReport()">
                            <i class="fas fa-print"></i> Print Report
                        </button>
                        <a href="export-excel.php?month=<?= $month ?>&year=<?= $year ?>" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </a>
                        <button class="btn btn-info" onclick="exportChartData()">
                            <i class="fas fa-download"></i> Export Chart Data
                        </button>
                    </div>
                </div>
            </div>

            <!-- Financial Insights -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-lightbulb"></i> Financial Insights & Recommendations</h3>
                </div>
                <div class="card-body">
                    <div class="insights-grid">
                        <div class="insight-item insight-warning">
                            <div class="insight-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="insight-content">
                                <h4>Top Spending Category</h4>
                                <p><?= $category_report[0]['category'] ?? 'N/A' ?> accounts for 
                                ₹<?= number_format($category_report[0]['total_amount'] ?? 0) ?> 
                                (<?= $total_amount > 0 ? round(($category_report[0]['total_amount'] / $total_amount) * 100) : 0 ?>% of total spending)</p>
                            </div>
                        </div>
                        
                        <div class="insight-item insight-info">
                            <div class="insight-icon">
                                <i class="fas fa-user-chart"></i>
                            </div>
                            <div class="insight-content">
                                <h4>Highest Spender</h4>
                                <p><?= $member_report[0]['member_name'] ?? 'N/A' ?> spent ₹<?= number_format($member_report[0]['total_spent'] ?? 0) ?> 
                                across <?= $member_report[0]['expense_count'] ?? 0 ?> transactions</p>
                            </div>
                        </div>
                        
                        <div class="insight-item insight-success">
                            <div class="insight-icon">
                                <i class="fas fa-bullseye"></i>
                            </div>
                            <div class="insight-content">
                                <h4>Budget Utilization</h4>
                                <p>You've used <?= $budget_utilization ?>% of your monthly budget. 
                                <?= $savings_potential > 0 ? '₹' . number_format($savings_potential) . ' remaining.' : 'Budget exceeded!' ?></p>
                            </div>
                        </div>
                        
                        <div class="insight-item insight-primary">
                            <div class="insight-icon">
                                <i class="fas fa-rocket"></i>
                            </div>
                            <div class="insight-content">
                                <h4>Savings Opportunity</h4>
                                <p>Reduce <?= $category_report[0]['category'] ?? 'general' ?> spending by 15% to save approximately 
                                ₹<?= number_format(($category_report[0]['total_amount'] ?? 0) * 0.15) ?> next month</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Data Tables -->
            <div class="form-row">
                <div class="form-col">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-tags"></i> Expenses by Category</h3>
                            <span class="status status-approved"><?= count($category_report) ?> Categories</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th>Amount</th>
                                            <th>Count</th>
                                            <th>Percentage</th>
                                            <th>Trend</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $total_amount = array_sum(array_column($category_report, 'total_amount'));
                                        foreach ($category_report as $category): 
                                            $percentage = $total_amount > 0 ? round(($category['total_amount'] / $total_amount) * 100, 1) : 0;
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="category-with-color">
                                                    <span class="category-color" style="background-color: <?= getCategoryColor($category['category']) ?>"></span>
                                                    <?= $category['category'] ?>
                                                </div>
                                            </td>
                                            <td><strong>₹<?= number_format($category['total_amount']) ?></strong></td>
                                            <td><?= $category['count'] ?></td>
                                            <td>
                                                <div class="percentage-bar">
                                                    <div class="percentage-fill" style="width: <?= $percentage ?>%; background-color: <?= getCategoryColor($category['category']) ?>"></div>
                                                    <span class="percentage-text"><?= $percentage ?>%</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="trend-indicator <?= $percentage > 15 ? 'up' : 'down' ?>">
                                                    <i class="fas fa-<?= $percentage > 15 ? 'arrow-up' : 'arrow-down' ?>"></i>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td><strong>Total</strong></td>
                                            <td><strong>₹<?= number_format($total_amount) ?></strong></td>
                                            <td><strong><?= array_sum(array_column($category_report, 'count')) ?></strong></td>
                                            <td><strong>100%</strong></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-col">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-users"></i> Expenses by Family Member</h3>
                            <span class="status status-approved"><?= count($member_report) ?> Members</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Member</th>
                                            <th>Amount Spent</th>
                                            <th>Expense Count</th>
                                            <th>Average</th>
                                            <th>Share</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $total_spent = array_sum(array_column($member_report, 'total_spent'));
                                        foreach ($member_report as $member): 
                                            $average = $member['expense_count'] > 0 ? round($member['total_spent'] / $member['expense_count']) : 0;
                                            $share = $total_spent > 0 ? round(($member['total_spent'] / $total_spent) * 100, 1) : 0;
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="member-info">
                                                    <?php 
                                                    $member_avatar = $userModel->getUserAvatar($member['member_id'] ?? 0);
                                                    ?>
                                                    <div class="member-avatar-small" style="background-color: <?= getAvatarColor($member['member_name']) ?>">
                                                        <?php if (!empty($member_avatar) && file_exists($member_avatar)): ?>
                                                            <img src="<?= $member_avatar ?>" alt="<?= htmlspecialchars($member['member_name']) ?>">
                                                        <?php else: ?>
                                                            <?= substr($member['member_name'], 0, 2) ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?= $member['member_name'] ?>
                                                </div>
                                            </td>
                                            <td><strong>₹<?= number_format($member['total_spent']) ?></strong></td>
                                            <td><?= $member['expense_count'] ?></td>
                                            <td>₹<?= number_format($average) ?></td>
                                            <td>
                                                <span class="share-badge"><?= $share ?>%</span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td><strong>Family Total</strong></td>
                                            <td><strong>₹<?= number_format($total_spent) ?></strong></td>
                                            <td><strong><?= array_sum(array_column($member_report, 'expense_count')) ?></strong></td>
                                            <td><strong>₹<?= number_format($total_spent > 0 ? $total_spent / array_sum(array_column($member_report, 'expense_count')) : 0) ?></strong></td>
                                            <td><strong>100%</strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        // Chart instances storage
        let reportCharts = {};

        // Initialize Report Charts
        document.addEventListener('DOMContentLoaded', function() {
            initializeReportCharts();
        });

        function initializeReportCharts() {
            // Monthly Trends Line Chart
            const monthlyTrendsCtx = document.getElementById('monthlyTrendsChart');
            if (monthlyTrendsCtx) {
                const monthlyData = <?= json_encode($monthly_trends) ?>;
                
                reportCharts.monthlyTrends = new Chart(monthlyTrendsCtx, {
                    type: 'line',
                    data: {
                        labels: monthlyData.labels,
                        datasets: [{
                            label: 'Monthly Expenses (₹)',
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
                            tension: 0.4,
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Monthly Spending vs Budget - <?= $year ?>',
                                font: { size: 16 }
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
                                },
                                grid: {
                                    color: 'rgba(0,0,0,0.1)'
                                }
                            },
                            x: {
                                grid: {
                                    color: 'rgba(0,0,0,0.1)'
                                }
                            }
                        }
                    }
                });
            }

            // Category Distribution Pie Chart
            const categoryPieCtx = document.getElementById('categoryPieChart');
            if (categoryPieCtx) {
                const categoryData = <?= json_encode($category_report) ?>;
                const labels = categoryData.map(item => item.category);
                const data = categoryData.map(item => item.total_amount);
                
                reportCharts.categoryPie = new Chart(categoryPieCtx, {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: data,
                            backgroundColor: [
                                '#4361ee', '#3f37c9', '#4cc9f0', '#e63946', '#ff9e00', '#7209b7', '#4caf50', '#9c27b0'
                            ],
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
                            },
                            title: {
                                display: true,
                                text: 'Spending Distribution by Category',
                                font: { size: 16 }
                            }
                        }
                    }
                });
            }
        }

        // Enhanced PDF Report Generation with Auto-refresh
        function generatePDFReport() {
            showLoading();
            
            const reportWindow = window.open('', '_blank');
            const reportDate = new Date().toLocaleDateString();
            const reportTime = new Date().toLocaleTimeString();
            
            const pdfContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Family Finance Report - <?= date('F Y', strtotime($month . '-01')) ?></title>
                <style>
                    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
                    * { 
                        margin: 0; 
                        padding: 0; 
                        box-sizing: border-box; 
                        font-family: 'Inter', sans-serif;
                    }
                    body { 
                        margin: 25px; 
                        color: #333; 
                        line-height: 1.6;
                        background: #f8fafc;
                    }
                    .report-header { 
                        text-align: center; 
                        padding: 30px; 
                        background: linear-gradient(135deg, #4361ee, #3a0ca3); 
                        color: white; 
                        border-radius: 16px; 
                        margin-bottom: 30px; 
                        box-shadow: 0 10px 30px rgba(67, 97, 238, 0.3);
                    }
                    .report-header h1 { 
                        margin: 0 0 10px 0; 
                        font-size: 32px; 
                        font-weight: 700;
                    }
                    .report-header h2 { 
                        margin: 5px 0; 
                        font-size: 20px; 
                        font-weight: normal; 
                        opacity: 0.9; 
                    }
                    .report-meta {
                        display: flex;
                        justify-content: center;
                        gap: 20px;
                        margin-top: 15px;
                        font-size: 14px;
                        opacity: 0.8;
                    }
                    .section { 
                        margin: 30px 0; 
                        padding: 25px; 
                        background: white; 
                        border-radius: 12px; 
                        border-left: 4px solid #4361ee; 
                        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                        page-break-inside: avoid;
                    }
                    .section h3 { 
                        color: #4361ee; 
                        margin-top: 0; 
                        border-bottom: 2px solid #e9ecef; 
                        padding-bottom: 15px;
                        font-size: 20px;
                        font-weight: 600;
                    }
                    table { 
                        width: 100%; 
                        border-collapse: collapse; 
                        margin: 15px 0; 
                        background: white;
                        border-radius: 8px;
                        overflow: hidden;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                    }
                    th { 
                        background: linear-gradient(135deg, #4361ee, #3a0ca3); 
                        color: white; 
                        padding: 15px; 
                        text-align: left; 
                        font-weight: 600;
                        font-size: 14px;
                    }
                    td { 
                        padding: 12px 15px; 
                        border-bottom: 1px solid #dee2e6; 
                        font-size: 14px;
                    }
                    tr:nth-child(even) { 
                        background: #f8f9fa; 
                    }
                    .stats-grid { 
                        display: grid; 
                        grid-template-columns: repeat(2, 1fr); 
                        gap: 15px; 
                        margin: 20px 0; 
                    }
                    .stat-item { 
                        background: white; 
                        padding: 20px; 
                        border-radius: 10px; 
                        text-align: center; 
                        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                        border: 1px solid #e9ecef;
                    }
                    .stat-number { 
                        font-size: 28px; 
                        font-weight: 700; 
                        color: #4361ee; 
                        margin: 10px 0; 
                    }
                    .stat-label { 
                        color: #6c757d; 
                        font-size: 14px;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                    }
                    .insight-box { 
                        background: linear-gradient(135deg, #e7f3ff, #d1edff); 
                        padding: 20px; 
                        border-radius: 10px; 
                        border-left: 4px solid #4cc9f0; 
                        margin: 15px 0; 
                    }
                    .chart-container-pdf {
                        margin: 20px 0;
                        text-align: center;
                    }
                    .chart-container-pdf img {
                        max-width: 100%;
                        height: auto;
                        border-radius: 8px;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                    }
                    .footer { 
                        text-align: center; 
                        margin-top: 40px; 
                        padding-top: 20px; 
                        border-top: 2px solid #dee2e6; 
                        color: #6c757d; 
                        font-size: 12px; 
                    }
                    .category-color {
                        display: inline-block;
                        width: 12px;
                        height: 12px;
                        border-radius: 50%;
                        margin-right: 8px;
                    }
                    @media print { 
                        body { margin: 15px; }
                        .section { margin: 20px 0; }
                        .report-header { 
                            background: #4361ee !important; 
                            -webkit-print-color-adjust: exact; 
                        }
                        th { 
                            background: #4361ee !important; 
                            -webkit-print-color-adjust: exact; 
                        }
                    }
                </style>
            </head>
            <body>
                <div class="report-header">
                    <h1>Family Finance Shield</h1>
                    <h2>Comprehensive Financial Report - <?= date('F Y', strtotime($month . '-01')) ?></h2>
                    <div class="report-meta">
                        <span>Generated on: ${reportDate}</span>
                        <span>Time: ${reportTime}</span>
                        <span>Report ID: FFS-<?= strtoupper(date('M-Y', strtotime($month . '-01'))) ?>-<?= rand(1000, 9999) ?></span>
                    </div>
                </div>

                <div class="section">
                    <h3>📊 Executive Summary</h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-number">₹<?= number_format($monthly_report['total_expenses']) ?></div>
                            <div class="stat-label">Total Monthly Expenses</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?= $monthly_report['expense_count'] ?></div>
                            <div class="stat-label">Number of Transactions</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">₹<?= number_format($monthly_report['average_expense']) ?></div>
                            <div class="stat-label">Average per Expense</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?= count($member_report) ?></div>
                            <div class="stat-label">Active Family Members</div>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <h3>📈 Category Analysis</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Amount (₹)</th>
                                <th>Transactions</th>
                                <th>% of Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_amount = array_sum(array_column($category_report, 'total_amount'));
                            foreach ($category_report as $category): 
                                $percentage = $total_amount > 0 ? round(($category['total_amount'] / $total_amount) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td>
                                    <span class="category-color" style="background-color: <?= getCategoryColor($category['category']) ?>"></span>
                                    <?= $category['category'] ?>
                                </td>
                                <td>₹<?= number_format($category['total_amount']) ?></td>
                                <td><?= $category['count'] ?></td>
                                <td><?= $percentage ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="section">
                    <h3>👥 Member Spending</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Family Member</th>
                                <th>Total Spent (₹)</th>
                                <th>Expense Count</th>
                                <th>Average (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($member_report as $member): 
                                $average = $member['expense_count'] > 0 ? round($member['total_spent'] / $member['expense_count']) : 0;
                            ?>
                            <tr>
                                <td><?= $member['member_name'] ?></td>
                                <td>₹<?= number_format($member['total_spent']) ?></td>
                                <td><?= $member['expense_count'] ?></td>
                                <td>₹<?= number_format($average) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="section">
                    <h3>💡 Financial Insights</h3>
                    <div class="insight-box">
                        <strong>Top Spending Category:</strong> <?= $category_report[0]['category'] ?? 'N/A' ?> (₹<?= number_format($category_report[0]['total_amount'] ?? 0) ?>)<br>
                        <strong>Highest Spender:</strong> <?= $member_report[0]['member_name'] ?? 'N/A' ?> (₹<?= number_format($member_report[0]['total_spent'] ?? 0) ?>)<br>
                        <strong>Monthly Budget Utilization:</strong> <?= $budget_utilization ?>% of ₹75,000 budget<br>
                        <strong>Savings Potential:</strong> ₹<?= number_format($savings_potential) ?> remaining
                    </div>
                </div>

                <div class="footer">
                    <p><strong>Family Finance Shield Report</strong> | Confidential Financial Document</p>
                    <p>This report contains sensitive financial information. Please handle with care.</p>
                    <p>For family internal use only</p>
                </div>
            </body>
            </html>
            `;
            
            reportWindow.document.write(pdfContent);
            reportWindow.document.close();
            
            setTimeout(() => {
                reportWindow.print();
                const loadingSpinner = document.querySelector('.loading-spinner');
                if (loadingSpinner) {
                    loadingSpinner.remove();
                }
                // Auto-refresh after PDF generation
                setTimeout(() => {
                    window.location.href = 'reports.php?month=<?= $month ?>&year=<?= $year ?>&refresh=true';
                }, 1000);
            }, 1000);
        }

        // Enhanced Print Report with Auto-refresh
        function printEnhancedReport() {
            showLoading();
            
            const printWindow = window.open('', '_blank');
            const reportDate = new Date().toLocaleDateString();
            const reportTime = new Date().toLocaleTimeString();
            
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Family Finance Report - <?= date('F Y', strtotime($month . '-01')) ?></title>
                    <style>
                        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
                        * { 
                            margin: 0; 
                            padding: 0; 
                            box-sizing: border-box; 
                            font-family: 'Inter', sans-serif;
                        }
                        body { 
                            margin: 20px; 
                            color: #333; 
                            line-height: 1.5;
                            background: white;
                        }
                        .print-header { 
                            text-align: center; 
                            color: #4361ee; 
                            border-bottom: 3px solid #4361ee; 
                            padding-bottom: 20px; 
                            margin-bottom: 30px; 
                        }
                        .print-header h1 { 
                            margin: 0 0 10px 0; 
                            font-size: 28px; 
                            font-weight: 700;
                        }
                        .print-header h2 { 
                            margin: 5px 0; 
                            font-size: 18px; 
                            font-weight: normal; 
                            color: #666;
                        }
                        .print-meta {
                            display: flex;
                            justify-content: center;
                            gap: 15px;
                            margin-top: 10px;
                            font-size: 14px;
                            color: #666;
                        }
                        .section { 
                            margin: 25px 0; 
                            page-break-inside: avoid;
                            padding: 20px;
                            background: #f8f9fa;
                            border-radius: 8px;
                        }
                        .section h3 { 
                            color: #4361ee; 
                            margin-bottom: 15px;
                            font-size: 18px;
                            border-bottom: 1px solid #dee2e6;
                            padding-bottom: 10px;
                        }
                        table { 
                            width: 100%; 
                            border-collapse: collapse; 
                            margin: 10px 0; 
                            font-size: 12px;
                        }
                        th, td { 
                            border: 1px solid #ddd; 
                            padding: 10px; 
                            text-align: left; 
                        }
                        th { 
                            background-color: #4361ee; 
                            color: white; 
                            font-weight: 600;
                        }
                        .stats-container-print { 
                            display: grid; 
                            grid-template-columns: repeat(2, 1fr); 
                            gap: 10px; 
                            margin: 15px 0; 
                        }
                        .stat-card-print { 
                            background: white; 
                            padding: 15px; 
                            border-radius: 6px; 
                            text-align: center; 
                            border: 1px solid #dee2e6;
                        }
                        .stat-value-print { 
                            font-size: 20px; 
                            font-weight: bold; 
                            color: #4361ee; 
                            margin: 5px 0; 
                        }
                        .stat-title-print { 
                            color: #666; 
                            font-size: 12px; 
                            text-transform: uppercase;
                        }
                        .chart-placeholder {
                            background: #f8f9fa;
                            border: 2px dashed #dee2e6;
                            border-radius: 8px;
                            padding: 40px 20px;
                            text-align: center;
                            margin: 15px 0;
                            color: #666;
                        }
                        .footer { 
                            text-align: center; 
                            margin-top: 30px; 
                            padding-top: 15px; 
                            border-top: 2px solid #dee2e6; 
                            color: #666; 
                            font-size: 11px; 
                        }
                        @media print { 
                            body { margin: 15px; font-size: 12px; }
                            .section { margin: 15px 0; }
                            .print-header { color: #4361ee !important; }
                            th { background-color: #4361ee !important; color: white !important; }
                            .no-print { display: none !important; }
                        }
                    </style>
                </head>
                <body>
                    <div class="print-header">
                        <h1>Family Finance Report</h1>
                        <h2><?= date('F Y', strtotime($month . '-01')) ?></h2>
                        <div class="print-meta">
                            <span>Generated on: ${reportDate}</span>
                            <span>Time: ${reportTime}</span>
                        </div>
                    </div>
                    
                    <div class="section">
                        <h3>Executive Summary</h3>
                        <div class="stats-container-print">
                            <div class="stat-card-print">
                                <div class="stat-value-print">₹<?= number_format($monthly_report['total_expenses']) ?></div>
                                <div class="stat-title-print">Total Expenses</div>
                            </div>
                            <div class="stat-card-print">
                                <div class="stat-value-print"><?= $monthly_report['expense_count'] ?></div>
                                <div class="stat-title-print">Transactions</div>
                            </div>
                            <div class="stat-card-print">
                                <div class="stat-value-print">₹<?= number_format($monthly_report['average_expense']) ?></div>
                                <div class="stat-title-print">Average Expense</div>
                            </div>
                            <div class="stat-card-print">
                                <div class="stat-value-print"><?= count($member_report) ?></div>
                                <div class="stat-title-print">Family Members</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="section">
                        <h3>Category Analysis</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Amount (₹)</th>
                                    <th>Transactions</th>
                                    <th>% of Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_amount = array_sum(array_column($category_report, 'total_amount'));
                                foreach ($category_report as $category): 
                                    $percentage = $total_amount > 0 ? round(($category['total_amount'] / $total_amount) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td><?= $category['category'] ?></td>
                                    <td>₹<?= number_format($category['total_amount']) ?></td>
                                    <td><?= $category['count'] ?></td>
                                    <td><?= $percentage ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="section">
                        <h3>Member Spending</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Family Member</th>
                                    <th>Total Spent (₹)</th>
                                    <th>Expense Count</th>
                                    <th>Average (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($member_report as $member): 
                                    $average = $member['expense_count'] > 0 ? round($member['total_spent'] / $member['expense_count']) : 0;
                                ?>
                                <tr>
                                    <td><?= $member['member_name'] ?></td>
                                    <td>₹<?= number_format($member['total_spent']) ?></td>
                                    <td><?= $member['expense_count'] ?></td>
                                    <td>₹<?= number_format($average) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="footer">
                        <p><strong>Family Finance Shield Report</strong> | Confidential Financial Document</p>
                        <p>Report ID: FFS-<?= strtoupper(date('M-Y', strtotime($month . '-01'))) ?>-<?= rand(1000, 9999) ?></p>
                    </div>
                </body>
                </html>
            `);
            
            printWindow.document.close();
            
            setTimeout(() => {
                printWindow.print();
                const loadingSpinner = document.querySelector('.loading-spinner');
                if (loadingSpinner) {
                    loadingSpinner.remove();
                }
                // Auto-refresh after print
                setTimeout(() => {
                    window.location.href = 'reports.php?month=<?= $month ?>&year=<?= $year ?>&refresh=true';
                }, 1000);
            }, 500);
        }

        // Chart Interaction Functions
        function toggleChartType(chartId) {
            const chart = reportCharts[chartId];
            if (chart) {
                chart.config.type = chart.config.type === 'line' ? 'bar' : 'line';
                chart.update();
            }
        }

        // Export Chart Data as JSON
        function exportChartData() {
            const chartData = {
                monthlyTrends: reportCharts.monthlyTrends?.data,
                categoryPie: reportCharts.categoryPie?.data,
                exportDate: new Date().toISOString(),
                reportPeriod: '<?= $month ?>'
            };
            
            const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(chartData, null, 2));
            const downloadAnchor = document.createElement('a');
            downloadAnchor.setAttribute("href", dataStr);
            downloadAnchor.setAttribute("download", "chart_data_<?= $month ?>.json");
            document.body.appendChild(downloadAnchor);
            downloadAnchor.click();
            downloadAnchor.remove();
            
            // Auto-refresh after export
            setTimeout(() => {
                window.location.href = 'reports.php?month=<?= $month ?>&year=<?= $year ?>&refresh=true';
            }, 1000);
        }
    </script>
</body>
</html>