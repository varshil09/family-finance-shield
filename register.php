<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $family_option = $_POST['family_option'] ?? 'join'; // 'join' or 'create'
    $new_family_name = $_POST['new_family_name'] ?? '';
    $family_code = $_POST['family_code'] ?? '';
    
    require_once 'models/User.php';
    require_once 'models/Family.php';
    
    $userModel = new User();
    $familyModel = new Family();
    
    if ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif ($userModel->emailExists($email)) {
        $error = "Email already exists";
    } else {
        // Handle family creation or joining
        $family_id = 1; // Default family
        
        if ($family_option === 'create' && !empty($new_family_name)) {
            // Create new family
            try {
                $database = new Database();
                $conn = $database->getConnection();
                
                // Create new family with unique family code
                $family_code = strtoupper(substr(md5(uniqid()), 0, 8));
                $stmt = $conn->prepare("INSERT INTO families (name, admin_id, monthly_budget, family_code) VALUES (?, ?, ?, ?)");
                $stmt->execute([$new_family_name, 0, 75000, $family_code]);
                $family_id = $conn->lastInsertId();
                
                // Set user as admin for the new family
                $role = 'admin';
            } catch (PDOException $e) {
                $error = "Failed to create new family. Please try again.";
            }
        } elseif ($family_option === 'join' && !empty($family_code)) {
            // Join existing family using family code
            $family = $familyModel->getFamilyByCode($family_code);
            if ($family) {
                $family_id = $family['id'];
                $role = 'pending'; // Set role as pending for approval
            } else {
                $error = "Invalid family code. Please check and try again.";
            }
        } else {
            // Join default family
            $role = 'member';
        }
        
        if (!isset($error)) {
            $user_id = $userModel->register($name, $email, $password, $role, $family_id);
            if ($user_id) {
                // If this is the first user in a new family, set them as admin
                if ($family_option === 'create' && !empty($new_family_name)) {
                    $conn->prepare("UPDATE families SET admin_id = ? WHERE id = ?")->execute([$user_id, $family_id]);
                    
                    // Store family code in session for display
                    $_SESSION['family_code'] = $family_code;
                    $_SESSION['message'] = "Family created successfully! Your Family Code: <strong>$family_code</strong> - Share this code with family members to join.";
                }
                
                if ($role === 'pending') {
                    $_SESSION['message'] = "Join request sent successfully! The family admin will approve your request soon.";
                    header('Location: index.php');
                    exit;
                } else {
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_role'] = $role;
                    $_SESSION['family_id'] = $family_id;
                    header('Location: dashboard.php');
                    exit;
                }
            } else {
                $error = "Registration failed";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Family Finance Shield</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Join Family Finance Shield</h1>
                <p>Create your family account</p>
            </div>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i><?= $error ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i><?= $_SESSION['message'] ?>
                    <?php unset($_SESSION['message']); ?>
                </div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                
                <!-- Family Option -->
                <div class="form-group">
                    <label>Family Option</label>
                    <div class="form-radio-group">
                        <label class="radio-label">
                            <input type="radio" name="family_option" value="join" <?= ($_POST['family_option'] ?? 'join') === 'join' ? 'checked' : '' ?> onchange="toggleFamilyOptions()">
                            <span class="radio-custom"></span>
                            Join Existing Family
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="family_option" value="create" <?= ($_POST['family_option'] ?? '') === 'create' ? 'checked' : '' ?> onchange="toggleFamilyOptions()">
                            <span class="radio-custom"></span>
                            Create New Family
                        </label>
                    </div>
                </div>
                
                <!-- Family Code Input (shown only when joining family) -->
                <div class="form-group" id="familyCodeGroup" style="display: none;">
                    <label for="family_code">Family Code</label>
                    <input type="text" class="form-control" id="family_code" name="family_code" value="<?= htmlspecialchars($_POST['family_code'] ?? '') ?>" placeholder="Enter family code provided by admin">
                    <small class="form-helper">Ask the family admin for the family code</small>
                </div>
                
                <!-- New Family Name (shown only when creating new family) -->
                <div class="form-group" id="newFamilyGroup" style="display: none;">
                    <label for="new_family_name">New Family Name</label>
                    <input type="text" class="form-control" id="new_family_name" name="new_family_name" value="<?= htmlspecialchars($_POST['new_family_name'] ?? '') ?>" placeholder="Enter your family name">
                    <small class="form-helper">You will be the administrator of this family</small>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-user-plus"></i> 
                        <span id="submitText">Create Account</span>
                    </button>
                </div>
            </form>
            <div style="text-align: center; margin-top: 20px;">
                <p>Already have an account? <a href="index.php">Sign in here</a></p>
            </div>
        </div>
    </div>

    <script>
        function toggleFamilyOptions() {
            const createFamily = document.querySelector('input[name="family_option"][value="create"]').checked;
            const joinFamily = document.querySelector('input[name="family_option"][value="join"]').checked;
            const newFamilyGroup = document.getElementById('newFamilyGroup');
            const familyCodeGroup = document.getElementById('familyCodeGroup');
            const submitText = document.getElementById('submitText');
            
            if (createFamily) {
                newFamilyGroup.style.display = 'block';
                familyCodeGroup.style.display = 'none';
                document.getElementById('new_family_name').required = true;
                document.getElementById('family_code').required = false;
                submitText.textContent = 'Create Family & Account';
            } else if (joinFamily) {
                newFamilyGroup.style.display = 'none';
                familyCodeGroup.style.display = 'block';
                document.getElementById('new_family_name').required = false;
                document.getElementById('family_code').required = true;
                submitText.textContent = 'Send Join Request';
            } else {
                newFamilyGroup.style.display = 'none';
                familyCodeGroup.style.display = 'none';
                document.getElementById('new_family_name').required = false;
                document.getElementById('family_code').required = false;
                submitText.textContent = 'Create Account';
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleFamilyOptions();
        });
    </script>
</body>
</html>