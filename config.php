<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_NAME', 'family_finance');
define('DB_USER', 'root');
define('DB_PASS', '');
define('APP_NAME', 'Family Finance Shield');

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    public $conn;
    
    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
    
    public function initDatabase() {
        try {
            $conn = new PDO("mysql:host=" . $this->host, $this->username, $this->password);
            $conn->exec("CREATE DATABASE IF NOT EXISTS " . $this->db_name);
            $conn->exec("USE " . $this->db_name);
            
            // Updated users table with avatar and last_login columns
            $conn->exec("CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                role ENUM('admin','member','pending') DEFAULT 'member',
                family_id INT DEFAULT 1,
                avatar VARCHAR(255) NULL,
                last_login DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            
            // Updated families table with monthly_budget and family_code
            $conn->exec("CREATE TABLE IF NOT EXISTS families (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                admin_id INT,
                monthly_budget DECIMAL(10,2) DEFAULT 75000,
                family_code VARCHAR(8) UNIQUE NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            
            // Updated expenses table with approval tracking
            $conn->exec("CREATE TABLE IF NOT EXISTS expenses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                family_id INT NOT NULL,
                user_id INT NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                category VARCHAR(50) NOT NULL,
                description TEXT,
                expense_date DATE NOT NULL,
                type ENUM('planned','unplanned') DEFAULT 'unplanned',
                recurrence VARCHAR(20) DEFAULT 'one-time',
                needs_approval BOOLEAN DEFAULT FALSE,
                status ENUM('pending','approved','declined') DEFAULT 'pending',
                declined_reason TEXT NULL,
                approved_by INT NULL,
                declined_by INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            
            // Create default family with family code
            $stmt = $conn->prepare("SELECT id FROM families WHERE id = 1");
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                $default_family_code = strtoupper(substr(md5(uniqid()), 0, 8));
                $conn->exec("INSERT INTO families (id, name, admin_id, monthly_budget, family_code) VALUES (1, 'My Family', 1, 75000, '$default_family_code')");
            }
            
            // Create default admin user
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = 'admin@family.com'");
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
                $conn->exec("INSERT INTO users (name, email, password, role, family_id) VALUES 
                    ('Family Admin', 'admin@family.com', '$hashed_password', 'admin', 1)");
            }
            
            return true;
        } catch(PDOException $exception) {
            error_log("Database initialization error: " . $exception->getMessage());
            return false;
        }
    }
}

function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
}

function getCurrentPage() {
    return basename($_SERVER['PHP_SELF'], '.php');
}

// Enhanced security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// Session security
// ini_set('session.cookie_httponly', 1);
// ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
?>