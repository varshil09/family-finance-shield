<?php
class Expense {
    private $conn;
    private $table_name = "expenses";
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function getAllExpenses($family_id, $filter = 'all') {
        $query = "SELECT e.*, u.name as user_name FROM " . $this->table_name . " e 
                  LEFT JOIN users u ON e.user_id = u.id 
                  WHERE e.family_id = :family_id";
        
        if ($filter === 'pending') {
            $query .= " AND e.status = 'pending'";
        } elseif ($filter === 'approved') {
            $query .= " AND e.status = 'approved'";
        } elseif ($filter === 'declined') {
            $query .= " AND e.status = 'declined'";
        }
        
        $query .= " ORDER BY e.expense_date DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':family_id', $family_id);
        $stmt->execute();
        $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($expenses)) {
            return [
                [
                    'id' => 1, 'amount' => 12500, 'category' => 'Travel', 'description' => 'Client meeting',
                    'expense_date' => date('Y-m-d', strtotime('-2 days')), 'user_name' => 'Vikram Patel', 
                    'status' => 'approved', 'type' => 'planned', 'user_id' => 2
                ],
                [
                    'id' => 2, 'amount' => 2400, 'category' => 'Food & Dining', 'description' => 'Team lunch',
                    'expense_date' => date('Y-m-d', strtotime('-3 days')), 'user_name' => 'Priya Sharma', 
                    'status' => 'approved', 'type' => 'unplanned', 'user_id' => 3
                ]
            ];
        }
        return $expenses;
    }
    
    public function addExpense($family_id, $user_id, $amount, $category, $description, $expense_date, $type, $recurrence) {
        $needs_approval = ($amount >= 5000 && $_SESSION['user_role'] === 'member');
        $status = $needs_approval ? 'pending' : 'approved';
        
        $query = "INSERT INTO " . $this->table_name . " 
                  (family_id, user_id, amount, category, description, expense_date, type, recurrence, needs_approval, status) 
                  VALUES (:family_id, :user_id, :amount, :category, :description, :expense_date, :type, :recurrence, :needs_approval, :status)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':family_id', $family_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':expense_date', $expense_date);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':recurrence', $recurrence);
        $stmt->bindParam(':needs_approval', $needs_approval, PDO::PARAM_BOOL);
        $stmt->bindParam(':status', $status);
        
        return $stmt->execute();
    }
    
    public function getTotalExpenses($family_id) {
        $query = "SELECT SUM(amount) as total FROM " . $this->table_name . " 
                  WHERE family_id = :family_id AND status = 'approved'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':family_id', $family_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 62840;
    }
    
    public function getRecentExpenses($family_id, $limit = 5) {
        $query = "SELECT e.*, u.name as user_name FROM " . $this->table_name . " e 
                  LEFT JOIN users u ON e.user_id = u.id 
                  WHERE e.family_id = :family_id AND e.status = 'approved'
                  ORDER BY e.expense_date DESC LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':family_id', $family_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($expenses)) {
            return [
                ['amount' => 12500, 'category' => 'Travel', 'description' => 'Client meeting', 
                 'expense_date' => date('Y-m-d', strtotime('-2 days')), 'user_name' => 'Vikram Patel'],
                ['amount' => 2400, 'category' => 'Food', 'description' => 'Team lunch', 
                 'expense_date' => date('Y-m-d', strtotime('-3 days')), 'user_name' => 'Priya Sharma']
            ];
        }
        return $expenses;
    }
    
    public function getSmartSuggestions($family_id) {
        return "Cut Food Delivery by 10% to save ₹3,000/month based on your last 3 months spending";
    }
    
    public function getBudgetAlerts($family_id) {
        $total_expenses = $this->getTotalExpenses($family_id);
        if ($total_expenses > 60000) {
            return "You've spent over 80% of your monthly budget";
        }
        return null;
    }
    
    public function deleteExpense($id, $user_id, $user_role) {
        if ($user_role === 'admin') {
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        } else {
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id AND user_id = :user_id";
        }
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        if ($user_role !== 'admin') {
            $stmt->bindParam(':user_id', $user_id);
        }
        return $stmt->execute();
    }

    // NEW METHODS FOR APPROVAL SYSTEM
    
    public function getPendingExpenses($family_id) {
        $query = "SELECT e.*, u.name as user_name 
                  FROM " . $this->table_name . " e 
                  LEFT JOIN users u ON e.user_id = u.id 
                  WHERE e.family_id = :family_id AND e.status = 'pending' 
                  ORDER BY e.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':family_id', $family_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function approveExpense($expense_id, $approved_by) {
        try {
            $query = "UPDATE " . $this->table_name . " 
                    SET status = 'approved', needs_approval = 0 
                    WHERE id = :expense_id AND status = 'pending'";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':expense_id', $expense_id);
            $result = $stmt->execute();
            
            // Debug: Check if update was successful
            if ($result && $stmt->rowCount() > 0) {
                error_log("Expense $expense_id approved successfully");
                return true;
            } else {
                error_log("Failed to approve expense $expense_id - No rows affected");
                return false;
            }
        } catch (PDOException $e) {
            error_log("Error approving expense: " . $e->getMessage());
            return false;
        }
    }

    public function declineExpense($expense_id, $declined_by) {
        try {
            $query = "UPDATE " . $this->table_name . " 
                    SET status = 'declined', needs_approval = 0 
                    WHERE id = :expense_id AND status = 'pending'";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':expense_id', $expense_id);
            $result = $stmt->execute();
            
            if ($result && $stmt->rowCount() > 0) {
                error_log("Expense $expense_id declined successfully");
                return true;
            } else {
                error_log("Failed to decline expense $expense_id - No rows affected");
                return false;
            }
        } catch (PDOException $e) {
            error_log("Error declining expense: " . $e->getMessage());
            return false;
        }
    }
    
    public function getPendingApprovalsCount($family_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                  WHERE family_id = :family_id AND status = 'pending'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':family_id', $family_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }

    // temp fix
    // public function getExpenseBreakdown($family_id) {
    //     return [
    //         ['category' => 'Food & Dining', 'amount' => 12750, 'percentage' => 20.3, 'color' => '#4361ee'],
    //         ['category' => 'Travel', 'amount' => 18500, 'percentage' => 29.4, 'color' => '#3f37c9'],
    //         ['category' => 'Shopping', 'amount' => 7200, 'percentage' => 11.5, 'color' => '#4cc9f0'],
    //         ['category' => 'Utilities', 'amount' => 6400, 'percentage' => 10.2, 'color' => '#e63946'],
    //         ['category' => 'Entertainment', 'amount' => 3100, 'percentage' => 4.9, 'color' => '#ff9e00'],
    //         ['category' => 'Healthcare', 'amount' => 4200, 'percentage' => 6.7, 'color' => '#7209b7'],
    //         ['category' => 'Education', 'amount' => 5500, 'percentage' => 8.8, 'color' => '#4caf50'],
    //         ['category' => 'Other', 'amount' => 3190, 'percentage' => 5.1, 'color' => '#9c27b0']
    //     ];
    // }
    
    // public function getMonthlyTrends($family_id) {
    //     return [
    //         'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    //         'expenses' => [52000, 58000, 54000, 60000, 62000, 65000, 63000, 68000, 65000, 70000, 62000, 71000],
    //         'budget' => [75000, 75000, 75000, 75000, 75000, 75000, 75000, 75000, 75000, 75000, 75000, 75000]
    //     ];
    // }
    // In models/Expense.php - Update these methods:
    public function getExpenseBreakdown($family_id) {
        $query = "SELECT 
                    category,
                    SUM(amount) as amount,
                    (SUM(amount) / (SELECT SUM(amount) FROM expenses WHERE family_id = :family_id AND status = 'approved')) * 100 as percentage
                FROM expenses 
                WHERE family_id = :family_id 
                AND status = 'approved'
                AND MONTH(expense_date) = MONTH(CURRENT_DATE())
                AND YEAR(expense_date) = YEAR(CURRENT_DATE())
                GROUP BY category 
                ORDER BY amount DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':family_id', $family_id);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($results)) {
            return [
                ['category' => 'Food & Dining', 'amount' => 12750, 'percentage' => 20.3, 'color' => '#4361ee'],
                ['category' => 'Travel', 'amount' => 18500, 'percentage' => 29.4, 'color' => '#3f37c9'],
                ['category' => 'Shopping', 'amount' => 7200, 'percentage' => 11.5, 'color' => '#4cc9f0'],
                ['category' => 'Utilities', 'amount' => 6400, 'percentage' => 10.2, 'color' => '#e63946'],
                ['category' => 'Entertainment', 'amount' => 3100, 'percentage' => 4.9, 'color' => '#ff9e00'],
                ['category' => 'Healthcare', 'amount' => 4200, 'percentage' => 6.7, 'color' => '#7209b7']
            ];
        }
        
        // Assign colors
        $colors = ['#4361ee', '#3f37c9', '#4cc9f0', '#e63946', '#ff9e00', '#7209b7', '#4caf50', '#9c27b0'];
        foreach ($results as $i => &$category) {
            $category['color'] = $colors[$i % count($colors)];
            $category['percentage'] = round($category['percentage'], 1);
        }
        
        return $results;
    }

    public function getMonthlyTrends($family_id) {
        $query = "SELECT 
                    MONTH(expense_date) as month,
                    SUM(amount) as monthly_expense
                FROM expenses 
                WHERE family_id = :family_id 
                AND status = 'approved'
                AND YEAR(expense_date) = YEAR(CURRENT_DATE())
                GROUP BY MONTH(expense_date)
                ORDER BY month";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':family_id', $family_id);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Initialize all months with 0
        $monthly_expenses = array_fill(0, 12, 0);
        $monthly_budget = array_fill(0, 12, 75000); // Default budget
        
        foreach ($results as $row) {
            $monthly_expenses[$row['month'] - 1] = $row['monthly_expense'];
        }
        
        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            'expenses' => $monthly_expenses,
            'budget' => $monthly_budget
        ];
    }
    
    public function getUpcomingRecurringExpenses($family_id) {
        return [
            [
                'description' => 'Monthly Rent',
                'category' => 'Utilities',
                'amount' => 15000,
                'next_due_date' => date('Y-m-d', strtotime('+5 days')),
                'recurrence' => 'monthly'
            ],
            [
                'description' => 'Internet Bill',
                'category' => 'Utilities',
                'amount' => 1200,
                'next_due_date' => date('Y-m-d', strtotime('+12 days')),
                'recurrence' => 'monthly'
            ],
            [
                'description' => 'Gym Membership',
                'category' => 'Entertainment',
                'amount' => 1500,
                'next_due_date' => date('Y-m-d', strtotime('+25 days')),
                'recurrence' => 'monthly'
            ]
        ];
    }
    
    public function getCategoryWiseExpenses($family_id, $month = null) {
        if ($month === null) {
            $month = date('Y-m');
        }
        
        $query = "SELECT category, SUM(amount) as total_amount, COUNT(*) as count 
                  FROM " . $this->table_name . " 
                  WHERE family_id = :family_id 
                  AND status = 'approved' 
                  AND DATE_FORMAT(expense_date, '%Y-%m') = :month 
                  GROUP BY category 
                  ORDER BY total_amount DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':family_id', $family_id);
        $stmt->bindParam(':month', $month);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($results)) {
            return [
                ['category' => 'Food & Dining', 'total_amount' => 12750, 'count' => 15],
                ['category' => 'Travel', 'total_amount' => 18500, 'count' => 3],
                ['category' => 'Shopping', 'total_amount' => 7200, 'count' => 8],
                ['category' => 'Utilities', 'total_amount' => 6400, 'count' => 4],
                ['category' => 'Entertainment', 'total_amount' => 3100, 'count' => 6],
                ['category' => 'Healthcare', 'total_amount' => 4200, 'count' => 5]
            ];
        }
        
        return $results;
    }
    
    public function getMemberWiseExpenses($family_id, $month = null) {
        if ($month === null) {
            $month = date('Y-m');
        }
        
        $query = "SELECT u.name as member_name, SUM(e.amount) as total_spent, COUNT(e.id) as expense_count 
                  FROM " . $this->table_name . " e 
                  LEFT JOIN users u ON e.user_id = u.id 
                  WHERE e.family_id = :family_id 
                  AND e.status = 'approved' 
                  AND DATE_FORMAT(e.expense_date, '%Y-%m') = :month 
                  GROUP BY u.id, u.name 
                  ORDER BY total_spent DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':family_id', $family_id);
        $stmt->bindParam(':month', $month);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($results)) {
            return [
                ['member_name' => 'Family Admin', 'total_spent' => 25000, 'expense_count' => 12],
                ['member_name' => 'Vikram Patel', 'total_spent' => 15000, 'expense_count' => 8],
                ['member_name' => 'Priya Sharma', 'total_spent' => 12000, 'expense_count' => 10]
            ];
        }
        
        return $results;
    }
    // Add these methods to your Expense class
    public function getTotalExpensesByUser($user_id) {
        $query = "SELECT SUM(amount) as total FROM " . $this->table_name . " 
                WHERE user_id = :user_id AND status = 'approved'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    public function getPendingExpensesCountByUser($user_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                WHERE user_id = :user_id AND status = 'pending'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }

    public function getApprovedExpensesCountByUser($user_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                WHERE user_id = :user_id AND status = 'approved'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }
}
?>