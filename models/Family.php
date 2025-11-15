<?php
class Family {
    private $conn;
    private $table_name = "families";
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function getFamilyDetails($family_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :family_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':family_id', $family_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$result) {
            return ['id' => 1, 'name' => 'My Family', 'monthly_budget' => 75000, 'created_at' => date('Y-m-d H:i:s')];
        }
        return $result;
    }
    
    public function updateMonthlyBudget($family_id, $monthly_budget) {
        $query = "UPDATE " . $this->table_name . " SET monthly_budget = :monthly_budget WHERE id = :family_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':monthly_budget', $monthly_budget);
        $stmt->bindParam(':family_id', $family_id);
        return $stmt->execute();
    }
    
    public function updateFamilyName($family_id, $family_name) {
        $query = "UPDATE " . $this->table_name . " SET name = :name WHERE id = :family_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $family_name);
        $stmt->bindParam(':family_id', $family_id);
        return $stmt->execute();
    }
    
    // NEW METHOD FOR DASHBOARD
    public function getMonthlyBudget($family_id) {
        $query = "SELECT monthly_budget FROM " . $this->table_name . " WHERE id = :family_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':family_id', $family_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['monthly_budget'] ?? 75000;
    }
    
    // NEW METHOD: Get family by code
    public function getFamilyByCode($family_code) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE family_code = :family_code";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':family_code', $family_code);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // NEW METHOD: Get pending join requests
    public function getPendingJoinRequests($family_id) {
        $query = "SELECT u.id, u.name, u.email, u.created_at 
                  FROM users u 
                  WHERE u.family_id = :family_id AND u.role = 'pending' 
                  ORDER BY u.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':family_id', $family_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // NEW METHOD: Approve join request
    public function approveJoinRequest($user_id) {
        $query = "UPDATE users SET role = 'member' WHERE id = :user_id AND role = 'pending'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        return $stmt->execute();
    }
    
    // NEW METHOD: Reject join request
    public function rejectJoinRequest($user_id) {
        $query = "DELETE FROM users WHERE id = :user_id AND role = 'pending'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        return $stmt->execute();
    }

    // NEW METHOD: Get family members with avatar data for reports
    public function getFamilyMembersWithAvatars($family_id) {
        $query = "SELECT u.id, u.name, u.email, u.role, u.avatar, u.created_at 
                  FROM users u 
                  WHERE u.family_id = :family_id AND u.role != 'pending'
                  ORDER BY 
                    CASE 
                        WHEN u.role = 'admin' THEN 1
                        WHEN u.role = 'member' THEN 2
                        ELSE 3
                    END, 
                    u.name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':family_id', $family_id);
        $stmt->execute();
        
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process avatar paths
        foreach ($members as &$member) {
            if (!empty($member['avatar']) && !file_exists($member['avatar'])) {
                $member['avatar'] = null;
            }
        }
        
        return $members;
    }

    // NEW METHOD: Check if user can be removed (not admin and not current user)
    public function canRemoveMember($user_id, $family_id) {
        $query = "SELECT role FROM users WHERE id = :user_id AND family_id = :family_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':family_id', $family_id);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Can remove if user is a member (not admin) and not the current session user
        return $user && $user['role'] === 'member' && $user_id != $_SESSION['user_id'];
    }

    // NEW METHOD: Check if user can leave family (must be member, not admin)
    public function canLeaveFamily($user_id, $family_id) {
        $query = "SELECT role FROM users WHERE id = :user_id AND family_id = :family_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':family_id', $family_id);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Can leave if user is a member (not admin)
        return $user && $user['role'] === 'member';
    }

    // NEW METHOD: Get member expenses summary for reports
    public function getMemberExpensesSummary($family_id, $month = null) {
        if ($month === null) {
            $month = date('Y-m');
        }
        
        $query = "SELECT 
                    u.id as member_id,
                    u.name as member_name,
                    u.avatar,
                    COALESCE(SUM(e.amount), 0) as total_spent,
                    COUNT(e.id) as expense_count,
                    COALESCE(AVG(e.amount), 0) as average_expense
                  FROM users u
                  LEFT JOIN expenses e ON u.id = e.user_id 
                    AND e.family_id = :family_id 
                    AND e.status = 'approved'
                    AND DATE_FORMAT(e.expense_date, '%Y-%m') = :month
                  WHERE u.family_id = :family_id 
                    AND u.role != 'pending'
                  GROUP BY u.id, u.name, u.avatar
                  ORDER BY total_spent DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':family_id', $family_id);
        $stmt->bindParam(':month', $month);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process avatar paths
        foreach ($results as &$result) {
            if (!empty($result['avatar']) && !file_exists($result['avatar'])) {
                $result['avatar'] = null;
            }
        }
        
        return $results;
    }
}
?>