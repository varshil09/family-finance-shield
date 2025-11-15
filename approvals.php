<?php
require_once 'config.php';
checkAuth();
require_once 'models/Expense.php';
require_once 'models/User.php';

// Only admin can access this page
if ($_SESSION['user_role'] !== 'admin') {
    $_SESSION['error'] = 'Access denied. Only administrators can approve expenses.';
    header('Location: dashboard.php');
    exit;
}

$expenseModel = new Expense();
$userModel = new User();

$family_id = $_SESSION['family_id'];

// Handle approval/decline actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_expense'])) {
        $expense_id = $_POST['expense_id'];
        $result = $expenseModel->approveExpense($expense_id, $_SESSION['user_id']);
        
        if ($result) {
            $_SESSION['message'] = 'Expense approved successfully!';
        } else {
            $_SESSION['error'] = 'Failed to approve expense. Expense may have been already processed.';
        }
    }
    
    if (isset($_POST['decline_expense'])) {
        $expense_id = $_POST['expense_id'];
        $reason = $_POST['decline_reason'] ?? 'No reason provided';
        $result = $expenseModel->declineExpense($expense_id, $_SESSION['user_id']);
        
        if ($result) {
            $_SESSION['message'] = 'Expense declined successfully!';
        } else {
            $_SESSION['error'] = 'Failed to decline expense. Expense may have been already processed.';
        }
    }
    
    header('Location: approvals.php');
    exit;
}

// Handle bulk actions
if (isset($_GET['bulk_approve'])) {
    $expense_ids = json_decode($_GET['expense_ids'] ?? '[]', true);
    if (!empty($expense_ids)) {
        $success_count = 0;
        foreach ($expense_ids as $expense_id) {
            if ($expenseModel->approveExpense($expense_id, $_SESSION['user_id'])) {
                $success_count++;
            }
        }
        $_SESSION['message'] = "Successfully approved $success_count expenses!";
    }
    header('Location: approvals.php');
    exit;
}

$pending_expenses = $expenseModel->getPendingExpenses($family_id);
$pending_count = $expenseModel->getPendingApprovalsCount($family_id);

// Get current user with avatar
$current_user = $userModel->getUserById($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Approvals - Family Finance Shield</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h2>Expense Approvals</h2>
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
                            <div class="user-profile-role">Administrator</div>
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

            <!-- Approval Stats -->
            <div class="stats-container">
                <div class="stat-card slide-up">
                    <div class="stat-icon icon-expense">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-value"><?= $pending_count ?></div>
                    <div class="stat-title">Pending Approvals</div>
                </div>
                
                <div class="stat-card slide-up delayed-1">
                    <div class="stat-icon icon-income">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="stat-value">₹<?= number_format(array_sum(array_column($pending_expenses, 'amount'))) ?></div>
                    <div class="stat-title">Total Pending Amount</div>
                </div>
                
                <div class="stat-card slide-up delayed-2">
                    <div class="stat-icon icon-savings">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value"><?= count(array_unique(array_column($pending_expenses, 'user_id'))) ?></div>
                    <div class="stat-title">Members Waiting</div>
                </div>
                
                <div class="stat-card slide-up delayed-3">
                    <div class="stat-icon icon-budget">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="stat-value"><?= count($pending_expenses) ?></div>
                    <div class="stat-title">Total Requests</div>
                </div>
            </div>

            <!-- Pending Expenses Table -->
            <div class="card slide-up">
                <div class="card-header">
                    <h3><i class="fas fa-list-alt"></i> Pending Expense Approvals</h3>
                    <div>
                        <?php if (!empty($pending_expenses)): ?>
                        <button class="btn btn-primary" onclick="bulkApprove()">
                            <i class="fas fa-check-double"></i> Approve All
                        </button>
                        <?php endif; ?>
                        <a href="expenses.php?filter=pending" class="btn btn-outline">
                            <i class="fas fa-list"></i> View All Expenses
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($pending_expenses)): ?>
                        <form id="bulkApprovalForm" method="GET" style="display: none;">
                            <input type="hidden" name="bulk_approve" value="1">
                            <input type="hidden" name="expense_ids" id="bulkExpenseIds">
                        </form>
                        
                        <div class="table-responsive">
                            <table class="table hover-table">
                                <thead>
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                                        </th>
                                        <th>Date</th>
                                        <th>Member</th>
                                        <th>Category</th>
                                        <th>Description</th>
                                        <th>Amount</th>
                                        <th>Type</th>
                                        <th>Submitted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_expenses as $index => $expense): 
                                        $member_avatar = $userModel->getUserAvatar($expense['user_id']);
                                    ?>
                                    <tr class="slide-in delayed-<?= $index + 1 ?>">
                                        <td>
                                            <input type="checkbox" class="expense-checkbox" value="<?= $expense['id'] ?>">
                                        </td>
                                        <td>
                                            <div class="date-cell">
                                                <div class="date-day"><?= date('d', strtotime($expense['expense_date'])) ?></div>
                                                <div class="date-month"><?= date('M', strtotime($expense['expense_date'])) ?></div>
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
                                            <span class="category-tag" style="background-color: <?= getCategoryColor($expense['category']) ?>">
                                                <?= $expense['category'] ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($expense['description']) ?></td>
                                        <td>
                                            <strong class="amount-highlight">₹<?= number_format($expense['amount']) ?></strong>
                                        </td>
                                        <td>
                                            <span class="status status-<?= $expense['type'] === 'planned' ? 'approved' : 'pending' ?>">
                                                <?= ucfirst($expense['type']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><?= date('d M, H:i', strtotime($expense['created_at'])) ?></small>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="expense_id" value="<?= $expense['id'] ?>">
                                                    <input type="hidden" name="approve_expense" value="1">
                                                    <button type="submit" name="approve_expense" class="btn btn-success btn-sm btn-animated" 
                                                            onclick="return confirm('Approve this expense?')">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                </form>
                                                
                                                <button type="button" class="btn btn-danger btn-sm btn-animated" 
                                                        onclick="showDeclineModal(<?= $expense['id'] ?>)">
                                                    <i class="fas fa-times"></i> Decline
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-check-circle" style="color: #28a745; font-size: 64px;"></i>
                            <h3>All Caught Up!</h3>
                            <p>No pending expenses waiting for approval.</p>
                            <a href="expenses.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add New Expense
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Approval Guidelines -->
            <div class="card slide-up">
                <div class="card-header">
                    <h3><i class="fas fa-info-circle"></i> Approval Guidelines</h3>
                </div>
                <div class="card-body">
                    <div class="guidelines">
                        <div class="guideline-item">
                            <i class="fas fa-check-circle" style="color: #28a745;"></i>
                            <div>
                                <strong>Auto-approval:</strong> Expenses under ₹5,000 are automatically approved for all members.
                            </div>
                        </div>
                        <div class="guideline-item">
                            <i class="fas fa-clock" style="color: #ff9e00;"></i>
                            <div>
                                <strong>Manual approval required:</strong> Expenses ₹5,000 and above require admin approval.
                            </div>
                        </div>
                        <div class="guideline-item">
                            <i class="fas fa-exclamation-triangle" style="color: #e63946;"></i>
                            <div>
                                <strong>High-value expenses:</strong> Expenses over ₹20,000 may require additional verification.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <!-- Decline Expense Modal -->
    <div id="declineModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Decline Expense</h3>
                <button type="button" class="close" onclick="closeDeclineModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="declineForm" method="POST">
                    <input type="hidden" name="expense_id" id="declineExpenseId">
                    <div class="form-group floating-label">
                        <textarea class="form-control" id="decline_reason" name="decline_reason" 
                                  rows="4" placeholder=" "></textarea>
                        <label for="decline_reason">Reason for declining (optional):</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeDeclineModal()">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="submitDecline()">
                    <i class="fas fa-times"></i> Decline Expense
                </button>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        // Bulk approval functionality
        function toggleSelectAll(checkbox) {
            const checkboxes = document.querySelectorAll('.expense-checkbox');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
        }
        
        function bulkApprove() {
            const selectedIds = Array.from(document.querySelectorAll('.expense-checkbox:checked'))
                                   .map(cb => cb.value);
            
            if (selectedIds.length === 0) {
                alert('Please select at least one expense to approve.');
                return;
            }
            
            if (confirm(`Approve ${selectedIds.length} selected expenses?`)) {
                document.getElementById('bulkExpenseIds').value = JSON.stringify(selectedIds);
                document.getElementById('bulkApprovalForm').submit();
            }
        }
        
        // Decline modal functionality
        function showDeclineModal(expenseId) {
            document.getElementById('declineExpenseId').value = expenseId;
            document.getElementById('declineModal').style.display = 'flex';
        }
        
        function closeDeclineModal() {
            document.getElementById('declineModal').style.display = 'none';
            document.getElementById('decline_reason').value = '';
        }
        
        function submitDecline() {
            if (confirm('Are you sure you want to decline this expense?')) {
                document.getElementById('declineForm').submit();
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('declineModal');
            if (event.target === modal) {
                closeDeclineModal();
            }
        }
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
        'Other' => '#9c27b0'
    ];
    return $colors[$category] ?? '#6c757d';
}

function getAvatarColor($name) {
    $colors = ['#4361ee', '#3f37c9', '#4cc9f0', '#e63946', '#ff9e00', '#7209b7'];
    $hash = crc32($name);
    return $colors[abs($hash) % count($colors)];
}