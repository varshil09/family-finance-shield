<?php
require_once 'config.php';
checkAuth();
require_once 'models/Report.php';

$family_id = $_SESSION['family_id'];
$month = $_GET['month'] ?? date('Y-m');
$reportModel = new Report();

$monthly_report = $reportModel->getMonthlyReport($family_id, $month);
$category_report = $reportModel->getCategoryReport($family_id, $month);
$member_report = $reportModel->getMemberReport($family_id, $month);

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="family_finance_report_' . $month . '.xls"');

echo "Family Finance Report - " . date('F Y', strtotime($month . '-01')) . "\n\n";
echo "Monthly Summary\n";
echo "Total Expenses: ₹" . number_format($monthly_report['total_expenses']) . "\n";
echo "Number of Expenses: " . $monthly_report['expense_count'] . "\n";
echo "Average Expense: ₹" . number_format($monthly_report['average_expense']) . "\n\n";

echo "Expenses by Category\n";
echo "Category\tAmount\tCount\tPercentage\n";
$total_amount = array_sum(array_column($category_report, 'total_amount'));
foreach ($category_report as $category) {
    $percentage = $total_amount > 0 ? round(($category['total_amount'] / $total_amount) * 100, 1) : 0;
    echo $category['category'] . "\t₹" . number_format($category['total_amount']) . "\t" . $category['count'] . "\t" . $percentage . "%\n";
}

echo "\nExpenses by Family Member\n";
echo "Member\tAmount Spent\tExpense Count\tAverage\n";
foreach ($member_report as $member) {
    $average = $member['expense_count'] > 0 ? round($member['total_spent'] / $member['expense_count']) : 0;
    echo $member['member_name'] . "\t₹" . number_format($member['total_spent']) . "\t" . $member['expense_count'] . "\t₹" . number_format($average) . "\n";
}
?>