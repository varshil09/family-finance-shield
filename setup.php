<?php
require_once 'config.php';
$database = new Database();
$success = $database->initDatabase();

// Test database connection and tables
$test_results = [];
try {
    $conn = $database->getConnection();
    
    // Test users table
    $stmt = $conn->query("SHOW COLUMNS FROM users");
    $user_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $test_results['users_table'] = in_array('role', $user_columns) ? '✅ Complete' : '❌ Missing role column';
    
    // Test families table
    $stmt = $conn->query("SHOW COLUMNS FROM families");
    $family_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $test_results['families_table'] = in_array('family_code', $family_columns) ? '✅ Complete' : '❌ Missing family_code column';
    
    // Test expenses table
    $stmt = $conn->query("SHOW COLUMNS FROM expenses");
    $expense_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $test_results['expenses_table'] = in_array('declined_reason', $expense_columns) ? '✅ Complete' : '❌ Missing approval columns';
    
    // Check default data
    $stmt = $conn->query("SELECT COUNT(*) as user_count FROM users");
    $user_count = $stmt->fetch(PDO::FETCH_ASSOC)['user_count'];
    $test_results['default_data'] = $user_count > 0 ? '✅ Loaded' : '❌ No users found';
    
    // Check family code
    $stmt = $conn->query("SELECT family_code FROM families WHERE id = 1");
    $family_code = $stmt->fetch(PDO::FETCH_ASSOC)['family_code'];
    $test_results['family_code'] = $family_code ? '✅ Generated: ' . $family_code : '❌ Not generated';
    
} catch (PDOException $e) {
    $test_results['connection'] = '❌ Failed: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Family Finance Shield - Setup</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Family Finance Shield</h1>
                <p>Application Setup & Database Configuration</p>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <h3>✅ Database Initialized Successfully!</h3>
                        <p>All tables and default data have been created.</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-database"></i> Database Status</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <?php foreach ($test_results as $test => $result): ?>
                            <div class="info-item">
                                <div class="info-label"><?= ucfirst(str_replace('_', ' ', $test)) ?></div>
                                <div class="info-value"><?= $result ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <h3>📋 Default Login Credentials</h3>
                        <p><strong>Email:</strong> admin@family.com</p>
                        <p><strong>Password:</strong> admin123</p>
                        <p><strong>Role:</strong> Administrator</p>
                        <?php if (isset($family_code)): ?>
                        <p><strong>Family Code:</strong> <?= $family_code ?> (Share this with family members)</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <a href="index.php" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-rocket"></i> Launch Application
                    </a>
                    <p style="margin-top: 15px; font-size: 14px; color: var(--gray);">
                        <i class="fas fa-lightbulb"></i> 
                        Delete the setup.php file for security after installation
                    </p>
                </div>
                
            <?php else: ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <h3>❌ Setup Failed</h3>
                        <p>Unable to initialize database. Please check:</p>
                        <ul style="margin: 10px 0; padding-left: 20px;">
                            <li>Database server is running</li>
                            <li>MySQL credentials in config.php are correct</li>
                            <li>Database user has create table privileges</li>
                        </ul>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button onclick="window.location.reload()" class="btn btn-primary">
                        <i class="fas fa-redo"></i> Retry Setup
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>