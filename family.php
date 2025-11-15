<?php
require_once 'config.php';
checkAuth();
require_once 'models/Family.php';
require_once 'models/User.php';

$familyModel = new Family();
$userModel = new User();
$family_id = $_SESSION['family_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_member'])) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    
    if (!empty($name) && !empty($email)) {
        // Check if user already exists
        if (!$userModel->emailExists($email)) {
            // Add member directly with default password
            $result = $userModel->addFamilyMember($name, $email, $family_id);
            if ($result) {
                $_SESSION['message'] = "Family member '$name' added successfully! Default password: <strong>user@123</strong>";
            } else {
                $_SESSION['error'] = "Failed to add family member.";
            }
        } else {
            $_SESSION['error'] = "User with this email already exists.";
        }
        header('Location: family.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_family'])) {
    $family_name = $_POST['family_name'] ?? '';
    $monthly_budget = floatval($_POST['monthly_budget'] ?? 0);
    if (!empty($family_name) && $monthly_budget > 0) {
        $familyModel->updateFamilyName($family_id, $family_name);
        $familyModel->updateMonthlyBudget($family_id, $monthly_budget);
        $_SESSION['message'] = 'Family settings updated!';
        header('Location: family.php');
        exit;
    }
}

// Handle join request approvals
if (isset($_GET['approve_user'])) {
    $user_id = $_GET['approve_user'];
    if ($familyModel->approveJoinRequest($user_id)) {
        $_SESSION['message'] = 'Join request approved successfully!';
    } else {
        $_SESSION['error'] = 'Failed to approve join request.';
    }
    header('Location: family.php');
    exit;
}

if (isset($_GET['reject_user'])) {
    $user_id = $_GET['reject_user'];
    if ($familyModel->rejectJoinRequest($user_id)) {
        $_SESSION['message'] = 'Join request rejected successfully!';
    } else {
        $_SESSION['error'] = 'Failed to reject join request.';
    }
    header('Location: family.php');
    exit;
}

// Handle leave family request
if (isset($_GET['leave_family'])) {
    if ($familyModel->canLeaveFamily($_SESSION['user_id'], $family_id)) {
        $result = $userModel->leaveFamily($_SESSION['user_id'], $family_id);
        if ($result) {
            session_destroy();
            $_SESSION['message'] = 'You have successfully left the family.';
            header('Location: index.php');
            exit;
        } else {
            $_SESSION['error'] = 'Failed to leave family. Please try again.';
        }
    } else {
        $_SESSION['error'] = 'You cannot leave the family as an administrator. Please transfer admin rights first.';
    }
    header('Location: family.php');
    exit;
}

// Handle remove member request (admin only)
if (isset($_GET['remove_member']) && $_SESSION['user_role'] == 'admin') {
    $member_id = $_GET['remove_member'];
    
    if ($familyModel->canRemoveMember($member_id, $family_id)) {
        $result = $userModel->removeFamilyMember($member_id, $family_id);
        if ($result) {
            $_SESSION['message'] = 'Family member removed successfully.';
        } else {
            $_SESSION['error'] = 'Failed to remove family member.';
        }
    } else {
        $_SESSION['error'] = 'Cannot remove this member. You can only remove regular members, not administrators or yourself.';
    }
    header('Location: family.php');
    exit;
}

$family_details = $familyModel->getFamilyDetails($family_id);
$family_members = $userModel->getFamilyMembers($family_id);
$pending_requests = $familyModel->getPendingJoinRequests($family_id);

function getAvatarColor($id) {
    $colors = ['#4361ee', '#3f37c9', '#4cc9f0', '#e63946', '#ff9e00', '#7209b7', '#4caf50', '#9c27b0'];
    return $colors[$id % count($colors)];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Family - Family Finance Shield</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        <div class="main-content">
            <div class="header">
                <h2>Family Management</h2>
                <div class="header-actions">
                    <div class="user-profile" onclick="toggleProfileDropdown(event)">
                        <div class="user-avatar">
                            <?php 
                            $current_user = $userModel->getUserById($_SESSION['user_id']);
                            if (!empty($current_user['avatar']) && file_exists($current_user['avatar'])): ?>
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

            <?php if ($_SESSION['user_role'] == 'admin'): ?>
            <!-- Pending Join Requests -->
            <?php if (!empty($pending_requests)): ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-clock"></i> Pending Join Requests</h3>
                    <span class="status status-pending"><?= count($pending_requests) ?> Requests</span>
                </div>
                <div class="card-body">
                    <div class="members-grid">
                        <?php foreach ($pending_requests as $index => $request): ?>
                        <div class="member-card">
                            <div class="member-avatar" style="background-color: <?= getAvatarColor($request['id']) ?>;">
                                <?= substr($request['name'], 0, 2) ?>
                            </div>
                            <div class="member-info">
                                <h4 class="member-name"><?= htmlspecialchars($request['name']) ?></h4>
                                <p class="member-email"><?= htmlspecialchars($request['email']) ?></p>
                                <div class="member-role">
                                    <span class="status status-pending">
                                        <i class="fas fa-clock"></i>
                                        Waiting Approval
                                    </span>
                                </div>
                                <div class="action-buttons" style="margin-top: 15px; justify-content: center;">
                                    <a href="family.php?approve_user=<?= $request['id'] ?>" class="btn btn-success btn-sm"
                                       onclick="return confirm('Approve <?= htmlspecialchars($request['name']) ?> to join the family?')">
                                        <i class="fas fa-check"></i> Approve
                                    </a>
                                    <a href="family.php?reject_user=<?= $request['id'] ?>" class="btn btn-danger btn-sm"
                                       onclick="return confirm('Reject <?= htmlspecialchars($request['name']) ?> join request?')">
                                        <i class="fas fa-times"></i> Reject
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Add Family Member -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user-plus"></i> Add Family Member</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="add_member" value="1">
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group floating-label">
                                    <input type="text" class="form-control" id="name" name="name" required>
                                    <label for="name">Full Name</label>
                                    <div class="form-icon">
                                        <i class="fas fa-user"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group floating-label">
                                    <input type="email" class="form-control" id="email" name="email" required>
                                    <label for="email">Email Address</label>
                                    <div class="form-icon">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i> 
                                <span>Add Family Member</span>
                            </button>
                            <small class="text-muted" style="margin-left: 15px;">Default password: <strong>user@123</strong></small>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Family Members -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-users"></i> Family Members</h3>
                    <span class="status status-approved"><?= count($family_members) ?> Members</span>
                </div>
                <div class="card-body">
                    <div class="members-grid">
                        <?php foreach ($family_members as $index => $member): 
                            $member_details = $userModel->getUserById($member['id']);
                            $member_avatar = $userModel->getUserAvatar($member['id']);
                        ?>
                        <div class="member-card">
                            <div class="member-avatar" style="background-color: <?= getAvatarColor($member['id']) ?>;">
                                <?php if (!empty($member_avatar) && file_exists($member_avatar)): ?>
                                    <img src="<?= $member_avatar ?>" alt="<?= htmlspecialchars($member['name']) ?>">
                                <?php else: ?>
                                    <?= substr($member['name'], 0, 2) ?>
                                <?php endif; ?>
                            </div>
                            <div class="member-info">
                                <h4 class="member-name"><?= htmlspecialchars($member['name']) ?></h4>
                                <p class="member-email"><?= htmlspecialchars($member['email']) ?></p>
                                <div class="member-role">
                                    <span class="status <?= $member['role'] == 'admin' ? 'status-approved' : 'status-pending' ?>">
                                        <i class="fas fa-<?= $member['role'] == 'admin' ? 'crown' : 'user' ?>"></i>
                                        <?= ucfirst($member['role']) ?>
                                    </span>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="action-buttons" style="margin-top: 15px; justify-content: center;">
                                    <?php if ($_SESSION['user_role'] == 'admin' && $member['role'] == 'member' && $member['id'] != $_SESSION['user_id']): ?>
                                        <!-- Remove Member Button (Admin only for regular members, not self) -->
                                        <a href="family.php?remove_member=<?= $member['id'] ?>" 
                                           class="btn btn-danger btn-sm remove-member-btn"
                                           onclick="return confirm('Are you sure you want to remove <?= htmlspecialchars($member['name']) ?> from the family?')">
                                            <i class="fas fa-user-times"></i> Remove
                                        </a>
                                    <?php elseif ($_SESSION['user_role'] == 'member' && $member['id'] == $_SESSION['user_id']): ?>
                                        <!-- Leave Family Button (Member only for themselves) -->
                                        <a href="family.php?leave_family=1" 
                                           class="btn btn-warning btn-sm leave-family-btn"
                                           onclick="return confirm('Are you sure you want to leave the family? This action cannot be undone.')">
                                            <i class="fas fa-sign-out-alt"></i> Leave Family
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($member['id'] == $_SESSION['user_id']): ?>
                                    <div class="member-badge">
                                        <span class="badge">You</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Leave Family Notice for Members -->
                    <?php if ($_SESSION['user_role'] == 'member'): ?>
                    <div class="alert alert-info" style="margin-top: 20px;">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <strong>Family Membership:</strong> As a member, you can leave the family at any time using the "Leave Family" button on your profile card above.
                            Please note that this action cannot be undone and you will lose access to all family data.
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Family Settings -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-cog"></i> Family Settings</h3>
                    <?php if ($_SESSION['user_role'] == 'admin'): ?>
                    <div class="family-code-display">
                        <span class="status status-approved">
                            <i class="fas fa-key"></i>
                            Family Code: <strong><?= $family_details['family_code'] ?? 'NOTSET' ?></strong>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="update_family" value="1">
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group floating-label">
                                    <input type="text" class="form-control" id="family_name" name="family_name" 
                                           value="<?= htmlspecialchars($family_details['name']) ?>" required>
                                    <label for="family_name">Family Name</label>
                                    <div class="form-icon">
                                        <i class="fas fa-home"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group floating-label">
                                    <input type="number" class="form-control" id="monthly_budget" name="monthly_budget" 
                                           value="<?= $family_details['monthly_budget'] ?>" step="0.01" required>
                                    <label for="monthly_budget">Monthly Budget (₹)</label>
                                    <div class="form-icon">
                                        <i class="fas fa-rupee-sign"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary" <?= $_SESSION['user_role'] != 'admin' ? 'disabled' : '' ?>>
                                <i class="fas fa-save"></i> Update Family Settings
                            </button>
                            <?php if ($_SESSION['user_role'] != 'admin'): ?>
                            <small class="text-muted" style="margin-left: 10px;">Only administrators can update family settings.</small>
                            <?php endif; ?>
                        </div>
                    </form>
                    
                    <?php if ($_SESSION['user_role'] == 'admin'): ?>
                    <div class="family-info-card">
                        <h4>
                            <i class="fas fa-info-circle"></i> Family Code Information
                        </h4>
                        <p style="margin-bottom: 10px; color: var(--dark);">
                            Your Family Code: <strong style="font-size: 18px; color: var(--secondary);"><?= $family_details['family_code'] ?? 'NOTSET' ?></strong>
                        </p>
                        <p style="color: var(--gray); font-size: 14px; margin: 0;">
                            Share this code with family members so they can send join requests. 
                            You'll need to approve each request before they can access the family.
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/script.js"></script>
    <script>
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