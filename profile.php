<?php
require_once 'config.php';
checkAuth();
require_once 'models/User.php';
require_once 'models/Expense.php';

$userModel = new User();
$expenseModel = new Expense();

$user_id = $_SESSION['user_id'];
$family_id = $_SESSION['family_id'];

// Get user details
$user_details = $userModel->getUserById($user_id);

// Get user statistics
$total_expenses = $expenseModel->getTotalExpensesByUser($user_id);
$pending_expenses = $expenseModel->getPendingExpensesCountByUser($user_id);
$approved_expenses = $expenseModel->getApprovedExpensesCountByUser($user_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        
        if (!empty($name) && !empty($email)) {
            $result = $userModel->updateProfile($user_id, $name, $email);
            if ($result) {
                $_SESSION['user_name'] = $name;
                $_SESSION['message'] = 'Profile updated successfully!';
                header('Location: profile.php');
                exit;
            } else {
                $_SESSION['error'] = 'Failed to update profile.';
            }
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (!empty($current_password) && !empty($new_password) && !empty($confirm_password)) {
            if ($new_password === $confirm_password) {
                $result = $userModel->changePassword($user_id, $current_password, $new_password);
                if ($result) {
                    $_SESSION['message'] = 'Password changed successfully!';
                    header('Location: profile.php');
                    exit;
                } else {
                    $_SESSION['error'] = 'Current password is incorrect.';
                }
            } else {
                $_SESSION['error'] = 'New passwords do not match.';
            }
        }
    }
}

// Handle avatar upload separately
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'assets/uploads/avatars/';
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_extension = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (in_array($file_extension, $allowed_types)) {
        // Generate unique filename
        $file_name = 'avatar_' . $user_id . '_' . time() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;
        
        // Check file size (max 2MB)
        if ($_FILES['avatar']['size'] <= 2 * 1024 * 1024) {
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $file_path)) {
                // Update user avatar in database
                if ($userModel->updateAvatar($user_id, $file_path)) {
                    $_SESSION['message'] = 'Profile picture updated successfully!';
                } else {
                    $_SESSION['error'] = 'Failed to update profile picture in database.';
                }
            } else {
                $_SESSION['error'] = 'Failed to upload profile picture.';
            }
        } else {
            $_SESSION['error'] = 'File size too large. Maximum 2MB allowed.';
        }
    } else {
        $_SESSION['error'] = 'Invalid file type. Please upload JPG, PNG, or GIF images.';
    }
    
    header('Location: profile.php');
    exit;
}

// Get updated user details
$user_details = $userModel->getUserById($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Family Finance Shield</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h2>My Profile</h2>
                <div class="header-actions">
                    <div class="user-profile">
                        <div class="user-avatar">
                            <?php if (!empty($user_details['avatar']) && file_exists($user_details['avatar'])): ?>
                                <img src="<?= $user_details['avatar'] ?>" alt="Profile Avatar" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                            <?php else: ?>
                                <?= substr($user_details['name'], 0, 2) ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div><?= $_SESSION['user_name'] ?></div>
                            <div style="font-size: 12px; color: var(--gray);"><?= ucfirst($_SESSION['user_role']) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="profile-container">
                <!-- Profile Header -->
                <div class="card slide-up">
                    <div class="card-body">
                        <div class="profile-header">
                            <div class="profile-avatar-upload">
                                <div class="profile-avatar" onclick="document.getElementById('avatarInput').click()">
                                    <?php if (!empty($user_details['avatar']) && file_exists($user_details['avatar'])): ?>
                                        <img src="<?= $user_details['avatar'] ?>" alt="Profile Avatar" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                                    <?php else: ?>
                                        <?= substr($user_details['name'], 0, 2) ?>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="avatar-upload-btn" onclick="document.getElementById('avatarInput').click()">
                                    <i class="fas fa-camera"></i>
                                </button>
                                <form method="POST" enctype="multipart/form-data" style="display: none;">
                                    <input type="file" id="avatarInput" name="avatar" accept="image/*" onchange="this.form.submit()">
                                </form>
                            </div>
                            <h2><?= htmlspecialchars($user_details['name']) ?></h2>
                            <p class="text-muted"><?= htmlspecialchars($user_details['email']) ?></p>
                            <span class="status status-<?= $user_details['role'] == 'admin' ? 'approved' : 'pending' ?>">
                                <i class="fas fa-<?= $user_details['role'] == 'admin' ? 'crown' : 'user' ?>"></i>
                                <?= ucfirst($user_details['role']) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Profile Stats -->
                <div class="profile-stats">
                    <div class="profile-stat-card slide-up">
                        <div class="profile-stat-icon">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <div class="profile-stat-value">₹<?= number_format($total_expenses) ?></div>
                        <div class="profile-stat-label">Total Expenses</div>
                    </div>
                    
                    <div class="profile-stat-card slide-up delayed-1">
                        <div class="profile-stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="profile-stat-value"><?= $approved_expenses ?></div>
                        <div class="profile-stat-label">Approved Expenses</div>
                    </div>
                    
                    <div class="profile-stat-card slide-up delayed-2">
                        <div class="profile-stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="profile-stat-value"><?= $pending_expenses ?></div>
                        <div class="profile-stat-label">Pending Approval</div>
                    </div>
                    
                    <div class="profile-stat-card slide-up delayed-3">
                        <div class="profile-stat-icon">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div class="profile-stat-value"><?= date('M Y', strtotime($user_details['created_at'])) ?></div>
                        <div class="profile-stat-label">Member Since</div>
                    </div>
                </div>

                <!-- Profile Information -->
                <div class="card slide-up">
                    <div class="card-header">
                        <h3><i class="fas fa-user-edit"></i> Profile Information</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="update_profile" value="1">
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group floating-label">
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?= htmlspecialchars($user_details['name']) ?>" required>
                                        <label for="name">Full Name</label>
                                        <div class="form-icon">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="form-helper">
                                            Your full name as displayed
                                        </div>
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group floating-label">
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?= htmlspecialchars($user_details['email']) ?>" required>
                                        <label for="email">Email Address</label>
                                        <div class="form-icon">
                                            <i class="fas fa-envelope"></i>
                                        </div>
                                        <div class="form-helper">
                                            Your email address for notifications
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary btn-animated">
                                    <i class="fas fa-save"></i> 
                                    <span>Update Profile</span>
                                    <div class="btn-spinner">
                                        <i class="fas fa-spinner fa-spin"></i>
                                    </div>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="card slide-up">
                    <div class="card-header">
                        <h3><i class="fas fa-lock"></i> Change Password</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="change_password" value="1">
                            <div class="form-group floating-label">
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                <label for="current_password">Current Password</label>
                                <div class="form-icon">
                                    <i class="fas fa-key"></i>
                                </div>
                                <div class="form-helper">
                                    Enter your current password
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group floating-label">
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        <label for="new_password">New Password</label>
                                        <div class="form-icon">
                                            <i class="fas fa-lock"></i>
                                        </div>
                                        <div class="form-helper">
                                            Enter your new password
                                        </div>
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group floating-label">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        <label for="confirm_password">Confirm New Password</label>
                                        <div class="form-icon">
                                            <i class="fas fa-lock"></i>
                                        </div>
                                        <div class="form-helper">
                                            Confirm your new password
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary btn-animated">
                                    <i class="fas fa-key"></i> 
                                    <span>Change Password</span>
                                    <div class="btn-spinner">
                                        <i class="fas fa-spinner fa-spin"></i>
                                    </div>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Account Information -->
                <div class="card slide-up">
                    <div class="card-header">
                        <h3><i class="fas fa-info-circle"></i> Account Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">User ID</div>
                                <div class="info-value">#<?= $user_details['id'] ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Account Role</div>
                                <div class="info-value">
                                    <span class="status status-<?= $user_details['role'] == 'admin' ? 'approved' : 'pending' ?>">
                                        <?= ucfirst($user_details['role']) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Member Since</div>
                                <div class="info-value"><?= date('F j, Y', strtotime($user_details['created_at'])) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Last Login</div>
                                <div class="info-value"><?= date('F j, Y g:i A') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        // Password confirmation validation
        const confirmPassword = document.getElementById('confirm_password');
        if (confirmPassword) {
            confirmPassword.addEventListener('input', function() {
                const password = document.getElementById('new_password');
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

        // Initialize floating labels
        document.addEventListener('DOMContentLoaded', function() {
            const floatingLabels = document.querySelectorAll('.floating-label input, .floating-label select, .floating-label textarea');
            
            floatingLabels.forEach(input => {
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
        });
    </script>
</body>
</html>