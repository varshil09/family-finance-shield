<?php
class User {
    private $conn;
    private $table_name = "users";
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function authenticate($email, $password) {
        $query = "SELECT id, name, email, password, role, family_id FROM " . $this->table_name . " WHERE email = :email AND role != 'pending'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password'])) {
                // Update last login
                $this->updateLastLogin($user['id']);
                return $user;
            }
        }
        return false;
    }
    
    private function updateLastLogin($user_id) {
        try {
            $query = "UPDATE " . $this->table_name . " SET last_login = NOW() WHERE id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
        } catch (PDOException $e) {
            // Silently fail if last_login column doesn't exist
            error_log("Last login update failed: " . $e->getMessage());
        }
    }
    
    public function emailExists($email) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function updateProfile($user_id, $name, $email) {
        $query = "UPDATE " . $this->table_name . " SET name = :name, email = :email WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':user_id', $user_id);
        return $stmt->execute();
    }
    
    public function changePassword($user_id, $current_password, $new_password) {
        $query = "SELECT password FROM " . $this->table_name . " WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($current_password, $user['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_query = "UPDATE " . $this->table_name . " SET password = :password WHERE id = :user_id";
                $update_stmt = $this->conn->prepare($update_query);
                $update_stmt->bindParam(':password', $hashed_password);
                $update_stmt->bindParam(':user_id', $user_id);
                return $update_stmt->execute();
            }
        }
        return false;
    }
    
    public function register($name, $email, $password, $role = 'member', $family_id = 1) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO " . $this->table_name . " (name, email, password, role, family_id) 
                VALUES (:name, :email, :password, :role, :family_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':family_id', $family_id);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
    
    // NEW METHOD: Add family member directly by admin
    public function addFamilyMember($name, $email, $family_id) {
        $temp_password = password_hash('user@123', PASSWORD_DEFAULT);
        $query = "INSERT INTO " . $this->table_name . " (name, email, password, role, family_id) 
                VALUES (:name, :email, :password, 'member', :family_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $temp_password);
        $stmt->bindParam(':family_id', $family_id);
        
        return $stmt->execute();
    }

    // UPDATED METHOD: Get family members with proper avatar handling
    public function getFamilyMembers($family_id) {
        $query = "SELECT id, name, email, role, created_at FROM " . $this->table_name . " 
                WHERE family_id = :family_id AND role != 'pending'
                ORDER BY 
                    CASE 
                        WHEN role = 'admin' THEN 1
                        WHEN role = 'member' THEN 2
                        ELSE 3
                    END, 
                    name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':family_id', $family_id);
        $stmt->execute();
        
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If no members found, return at least the current user
        if (empty($members)) {
            return [
                [
                    'id' => $_SESSION['user_id'],
                    'name' => $_SESSION['user_name'],
                    'email' => 'admin@family.com',
                    'role' => $_SESSION['user_role'],
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ];
        }
        
        return $members;
    }

    // UPDATED METHOD: Get user avatar with proper path handling
    public function getUserAvatar($user_id) {
        try {
            // Check if avatar column exists
            $check_query = "SHOW COLUMNS FROM " . $this->table_name . " LIKE 'avatar'";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                $query = "SELECT avatar FROM " . $this->table_name . " WHERE id = :user_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result && !empty($result['avatar']) && file_exists($result['avatar'])) {
                    return $result['avatar'];
                }
            }
            return null;
        } catch (PDOException $e) {
            error_log("Get avatar error: " . $e->getMessage());
            return null;
        }
    }

    // NEW METHOD: Remove family member (admin only)
    public function removeFamilyMember($user_id, $family_id) {
        // Only allow removing members, not admins
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :user_id AND family_id = :family_id AND role = 'member'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':family_id', $family_id);
        return $stmt->execute();
    }

    // NEW METHOD: Leave family (for members)
    public function leaveFamily($user_id, $family_id) {
        // Members can leave their family
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :user_id AND family_id = :family_id AND role = 'member'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':family_id', $family_id);
        return $stmt->execute();
    }

    // NEW METHOD: Get member details with avatar
    public function getMemberWithAvatar($user_id) {
        try {
            $query = "SELECT id, name, email, role, family_id, avatar, created_at 
                      FROM " . $this->table_name . " 
                      WHERE id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $member = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($member) {
                // Ensure avatar path is correct
                if (!empty($member['avatar']) && !file_exists($member['avatar'])) {
                    $member['avatar'] = null;
                }
            }
            
            return $member;
        } catch (PDOException $e) {
            error_log("Get member with avatar error: " . $e->getMessage());
            return null;
        }
    }

    public function updateAvatar($user_id, $avatar_path) {
        try {
            // Check if avatar column exists
            $check_query = "SHOW COLUMNS FROM " . $this->table_name . " LIKE 'avatar'";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() == 0) {
                // Avatar column doesn't exist, create it
                $alter_query = "ALTER TABLE " . $this->table_name . " ADD COLUMN avatar VARCHAR(255) NULL AFTER family_id";
                $alter_stmt = $this->conn->prepare($alter_query);
                $alter_stmt->execute();
            }
            
            $query = "UPDATE " . $this->table_name . " SET avatar = :avatar WHERE id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':avatar', $avatar_path);
            $stmt->bindParam(':user_id', $user_id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Avatar update error: " . $e->getMessage());
            return false;
        }
    }

    public function getUserById($user_id) {
        try {
            // Check if avatar column exists
            $check_query = "SHOW COLUMNS FROM " . $this->table_name . " LIKE 'avatar'";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                $query = "SELECT *, avatar as avatar_path FROM " . $this->table_name . " WHERE id = :user_id";
            } else {
                $query = "SELECT *, NULL as avatar_path FROM " . $this->table_name . " WHERE id = :user_id";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return $this->getDefaultUserData($user_id);
            }
            
            // Ensure avatar field exists in result and path is correct
            if (!isset($user['avatar']) && isset($user['avatar_path'])) {
                $user['avatar'] = $user['avatar_path'];
            }
            
            // Validate avatar path
            if (!empty($user['avatar']) && !file_exists($user['avatar'])) {
                $user['avatar'] = null;
            }
            
            return $user;
        } catch (PDOException $e) {
            error_log("Get user error: " . $e->getMessage());
            return $this->getDefaultUserData($user_id);
        }
    }
    
    private function getDefaultUserData($user_id) {
        return [
            'id' => $user_id,
            'name' => $_SESSION['user_name'] ?? 'User',
            'email' => $_SESSION['user_email'] ?? 'user@family.com',
            'role' => $_SESSION['user_role'] ?? 'member',
            'avatar' => null,
            'family_id' => $_SESSION['family_id'] ?? 1,
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
}
?>