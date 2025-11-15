<?php
require_once 'config.php';
checkAuth();
require_once 'models/User.php';
require_once 'models/Family.php';

$userModel = new User();
$familyModel = new Family();

$user_id = $_SESSION['user_id'];
$family_id = $_SESSION['family_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        if (!empty($name) && !empty($email)) {
            $result = $userModel->updateProfile($user_id, $name, $email);
            if ($result) {
                $_SESSION['user_name'] = $name;
                $_SESSION['message'] = 'Profile updated successfully!';
                header('Location: settings.php');
                exit;
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
                    header('Location: settings.php');
                    exit;
                }
            }
        }
    }
}

$user_details = $userModel->getUserById($user_id);
$family_details = $familyModel->getFamilyDetails($family_id);

// Get current user with avatar
$current_user = $userModel->getUserById($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Family Finance Shield</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        <div class="main-content">
            <div class="header">
                <h2>Account Settings</h2>
                <div class="header-actions">
                    <div class="user-profile" onclick="toggleProfileDropdown(event)">
                        <div class="user-avatar"><?= substr($_SESSION['user_name'], 0, 2) ?></div>
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
            <div class="card">
                <div class="card-header">
                    <h3>Profile Settings</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="fullName">Full Name</label>
                                    <input type="text" class="form-control" id="fullName" name="name" value="<?= htmlspecialchars($user_details['name']) ?>" required>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="userEmail">Email</label>
                                    <input type="email" class="form-control" id="userEmail" name="email" value="<?= htmlspecialchars($user_details['email']) ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3>Change Password</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="change_password" value="1">
                        <div class="form-group">
                            <label for="currentPassword">Current Password</label>
                            <input type="password" class="form-control" id="currentPassword" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label for="newPassword">New Password</label>
                            <input type="password" class="form-control" id="newPassword" name="new_password" required>
                        </div>
                        <div class="form-group">
                            <label for="confirmPassword">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/script.js"></script>
</body>
</html>