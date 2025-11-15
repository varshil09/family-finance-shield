<?php
require_once 'config.php';
checkAuth();
require_once 'models/Expense.php';
require_once 'models/User.php';

$expenseModel = new Expense();
$userModel = new User();

$family_id = $_SESSION['family_id'];
$user_id = $_SESSION['user_id'];
$filter = $_GET['filter'] ?? 'all';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
    $amount = floatval($_POST['amount']);
    $category = $_POST['category'];
    $description = $_POST['description'];
    $expense_date = $_POST['date'];
    $type = $_POST['type'];
    $recurrence = $_POST['recurrence'];
    
    $result = $expenseModel->addExpense($family_id, $user_id, $amount, $category, $description, $expense_date, $type, $recurrence);
    
    if ($result) {
        $needs_approval = ($amount >= 5000 && $_SESSION['user_role'] === 'member');
        $message = $needs_approval ? 
            'Expense submitted for admin approval!' : 
            'Expense added successfully!';
        $_SESSION['message'] = $message;
        header('Location: expenses.php');
        exit;
    } else {
        $_SESSION['error'] = 'Failed to add expense.';
    }
}

if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $result = $expenseModel->deleteExpense($delete_id, $user_id, $_SESSION['user_role']);
    
    if ($result) {
        $_SESSION['message'] = 'Expense deleted successfully!';
        header('Location: expenses.php');
        exit;
    } else {
        $_SESSION['error'] = 'Failed to delete expense.';
    }
}

$expenses = $expenseModel->getAllExpenses($family_id, $filter);
$pending_count = $expenseModel->getPendingApprovalsCount($family_id);

// Get current user with avatar
$current_user = $userModel->getUserById($user_id);

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

function getCategoryIcon($category) {
    $icons = [
        'Food & Dining' => 'utensils',
        'Travel' => 'plane',
        'Shopping' => 'shopping-bag',
        'Utilities' => 'bolt',
        'Entertainment' => 'film',
        'Healthcare' => 'heartbeat',
        'Education' => 'graduation-cap',
        'Rent' => 'home',
        'Other' => 'receipt'
    ];
    return $icons[$category] ?? 'receipt';
}

function getAvatarColor($name) {
    $colors = ['#4361ee', '#3f37c9', '#4cc9f0', '#e63946', '#ff9e00', '#7209b7'];
    $hash = crc32($name);
    return $colors[abs($hash) % count($colors)];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses - Family Finance Shield</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h2>Family Expenses</h2>
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

            <!-- Add Expense Form -->
            <div class="card slide-up">
                <div class="card-header">
                    <h3><i class="fas fa-plus-circle"></i> Add New Expense</h3>
                    <button class="btn btn-outline" onclick="toggleExpenseForm()">
                        <i class="fas fa-chevron-down"></i> Toggle Form
                    </button>
                </div>
                <div class="card-body" id="expenseForm">
                    <form method="POST" class="expense-form" id="expenseFormElement">
                        <input type="hidden" name="add_expense" value="1">
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group floating-label">
                                    <input type="number" class="form-control" id="amount" name="amount" step="0.01" required 
                                           oninput="checkApprovalRequirement(this.value)">
                                    <label for="amount">Amount (₹)</label>
                                    <div class="form-icon">
                                        <i class="fas fa-rupee-sign"></i>
                                    </div>
                                    <div class="form-helper">
                                        Enter the expense amount
                                    </div>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group floating-label">
                                    <select class="form-control" id="category" name="category" required>
                                        <option value=""></option>
                                        <option value="Food & Dining">Food & Dining</option>
                                        <option value="Travel">Travel</option>
                                        <option value="Shopping">Shopping</option>
                                        <option value="Utilities">Utilities</option>
                                        <option value="Entertainment">Entertainment</option>
                                        <option value="Healthcare">Healthcare</option>
                                        <option value="Education">Education</option>
                                        <option value="Rent">Rent</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    <label for="category">Category</label>
                                    <div class="form-icon">
                                        <i class="fas fa-tag"></i>
                                    </div>
                                    <div class="form-helper">
                                        Select expense category
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group floating-label">
                                    <input type="date" class="form-control" id="date" name="date" value="<?= date('Y-m-d') ?>" required>
                                    <label for="date">Date</label>
                                    <div class="form-icon">
                                        <i class="fas fa-calendar"></i>
                                    </div>
                                    <div class="form-helper">
                                        Select expense date
                                    </div>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group floating-label">
                                    <select class="form-control" id="type" name="type" required>
                                        <option value=""></option>
                                        <option value="planned">Planned</option>
                                        <option value="unplanned">Unplanned</option>
                                    </select>
                                    <label for="type">Expense Type</label>
                                    <div class="form-icon">
                                        <i class="fas fa-chart-pie"></i>
                                    </div>
                                    <div class="form-helper">
                                        Planned or unplanned expense
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group floating-label">
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                            <label for="description">Description</label>
                            <div class="form-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="form-helper">
                                Brief description of the expense
                            </div>
                        </div>
                        <div class="form-group floating-label">
                            <select class="form-control" id="recurrence" name="recurrence">
                                <option value="one-time">One-time</option>
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                            <label for="recurrence">Recurrence</label>
                            <div class="form-icon">
                                <i class="fas fa-redo"></i>
                            </div>
                            <div class="form-helper">
                                How often this expense occurs
                            </div>
                        </div>
                        
                        <!-- Approval Notice -->
                        <div id="approvalNotice" class="approval-notice" style="display: none;">
                            <i class="fas fa-info-circle"></i>
                            <span>This expense requires admin approval (₹5,000+)</span>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-animated">
                                <i class="fas fa-check"></i> 
                                <span>Add Expense</span>
                                <div class="btn-spinner">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </div>
                            </button>
                            <button type="button" class="btn btn-outline" onclick="clearForm()">
                                <i class="fas fa-times"></i> Clear Form
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Expense History -->
            <div class="card slide-up delayed-1">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Expense History</h3>
                    <div class="filter-buttons">
                        <a href="expenses.php?filter=all" class="btn btn-outline <?= $filter === 'all' ? 'active' : '' ?>">
                            All Expenses
                        </a>
                        <?php if ($_SESSION['user_role'] == 'admin'): ?>
                        <a href="expenses.php?filter=pending" class="btn btn-outline <?= $filter === 'pending' ? 'active' : '' ?>">
                            Pending Approval
                            <?php if ($pending_count > 0): ?>
                            <span class="badge pulse"><?= $pending_count ?></span>
                            <?php endif; ?>
                        </a>
                        <?php endif; ?>
                        <a href="expenses.php?filter=approved" class="btn btn-outline <?= $filter === 'approved' ? 'active' : '' ?>">
                            Approved
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="table hover-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Member</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($expenses)): ?>
                                    <?php foreach ($expenses as $index => $expense): 
                                        $member_avatar = $userModel->getUserAvatar($expense['user_id']);
                                    ?>
                                    <tr class="slide-in delayed-<?= $index % 4 + 2 ?>">
                                        <td>
                                            <div class="date-cell">
                                                <div class="date-day"><?= date('d', strtotime($expense['expense_date'])) ?></div>
                                                <div class="date-month"><?= date('M', strtotime($expense['expense_date'])) ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="category-cell">
                                                <span class="category-icon" style="background-color: <?= getCategoryColor($expense['category']) ?>">
                                                    <i class="fas fa-<?= getCategoryIcon($expense['category']) ?>"></i>
                                                </span>
                                                <?= $expense['category'] ?>
                                            </div>
                                        </td>
                                        <td class="description-cell"><?= htmlspecialchars($expense['description']) ?></td>
                                        <td>
                                            <div class="amount-cell <?= $expense['amount'] > 5000 ? 'amount-high' : 'amount-normal' ?>">
                                                ₹<?= number_format($expense['amount']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="member-cell">
                                                <div class="member-avatar-small" style="background-color: <?= getAvatarColor($expense['user_name']) ?>">
                                                    <?php if (!empty($member_avatar) && file_exists($member_avatar)): ?>
                                                        <img src="<?= $member_avatar ?>" alt="<?= htmlspecialchars($expense['user_name']) ?>">
                                                    <?php else: ?>
                                                        <?= substr($expense['user_name'], 0, 2) ?>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="member-name"><?= $expense['user_name'] ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($expense['status'] == 'pending'): ?>
                                                <span class="status status-pending pulse">
                                                    <i class="fas fa-clock"></i> Pending
                                                </span>
                                            <?php elseif ($expense['status'] == 'approved'): ?>
                                                <span class="status status-approved">
                                                    <i class="fas fa-check-circle"></i> Approved
                                                </span>
                                            <?php else: ?>
                                                <span class="status status-declined">
                                                    <i class="fas fa-times-circle"></i> Declined
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <?php if ($_SESSION['user_role'] == 'admin' || $expense['user_id'] == $user_id): ?>
                                                    <a href="expenses.php?delete_id=<?= $expense['id'] ?>" 
                                                       class="btn btn-danger btn-sm btn-animated"
                                                       onclick="return confirm('Delete this expense?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($_SESSION['user_role'] == 'admin' && $expense['status'] == 'pending'): ?>
                                                    <a href="approvals.php" class="btn btn-success btn-sm btn-animated">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7">
                                            <div class="empty-state">
                                                <i class="fas fa-receipt"></i>
                                                <h3>No expenses found</h3>
                                                <p><?= $filter === 'pending' ? 'No pending expenses waiting for approval.' : 'Start adding expenses to see them here.' ?></p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/script.js"></script>
    <script>
        // Toggle expense form
        function toggleExpenseForm() {
            const form = document.getElementById('expenseForm');
            const button = event.target.closest('button');
            
            form.classList.toggle('collapsed');
            button.innerHTML = form.classList.contains('collapsed') ? 
                '<i class="fas fa-chevron-down"></i> Show Form' : 
                '<i class="fas fa-chevron-up"></i> Hide Form';
        }

        // Check approval requirement
        function checkApprovalRequirement(amount) {
            const approvalNotice = document.getElementById('approvalNotice');
            const userRole = '<?= $_SESSION['user_role'] ?>';
            
            if (amount >= 5000 && userRole === 'member') {
                approvalNotice.style.display = 'flex';
            } else {
                approvalNotice.style.display = 'none';
            }
        }

        // Clear form
        function clearForm() {
            document.getElementById('expenseFormElement').reset();
            document.getElementById('approvalNotice').style.display = 'none';
            
            // Reset floating labels
            const floatingLabels = document.querySelectorAll('.floating-label');
            floatingLabels.forEach(label => {
                label.classList.remove('focused');
            });
        }

        // Initialize form enhancements
        document.addEventListener('DOMContentLoaded', function() {
            // Floating label functionality
            const floatingLabels = document.querySelectorAll('.floating-label input, .floating-label select, .floating-label textarea');
            
            floatingLabels.forEach(input => {
                // Check if input has value on load
                if (input.value) {
                    input.parentElement.classList.add('focused');
                }
                
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    if (!this.value) {
                        this.parentElement.classList.remove('focused');
                    }
                });
            });

            // Form submission animation
            const form = document.getElementById('expenseFormElement');
            form.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.classList.add('loading');
                    setTimeout(() => {
                        submitBtn.classList.remove('loading');
                    }, 3000);
                }
            });

            // Auto-focus first input
            document.getElementById('amount').focus();
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