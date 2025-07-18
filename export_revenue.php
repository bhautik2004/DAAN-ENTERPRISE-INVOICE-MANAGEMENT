<?php
include 'db.php';

date_default_timezone_set('Asia/Kolkata'); // Set timezone
$exportFileName = "Revenue_Report_" . date("Y-m-d_H-i-s") . ".xls";

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=$exportFileName");
header("Pragma: no-cache");
header("Expires: 0");

echo "<html><body><table border='1'>";

// Print table headers
echo "<tr>
        <th>Employee Name</th>
        <th>Monthly Revenue</th>
        <th>Last Month Revenue</th>
        <th>Yearly Revenue</th>
        <th>Total Revenue</th>
      </tr>";

// Fetch total revenue **year-wise**
$revenue_sql = "SELECT
    COALESCE(SUM(total_amount), 0) as total_revenue,
    COALESCE(SUM(CASE WHEN MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE()) THEN total_amount ELSE 0 END), 0) as monthly_revenue,
    COALESCE(SUM(CASE WHEN MONTH(created_at) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) AND YEAR(created_at) = YEAR(CURRENT_DATE()) THEN total_amount ELSE 0 END), 0) as last_month_revenue,
    COALESCE(SUM(CASE WHEN YEAR(created_at) = YEAR(CURRENT_DATE()) THEN total_amount ELSE 0 END), 0) as yearly_revenue
    FROM invoices WHERE status='Completed'";

$revenue_result = $conn->query($revenue_sql);
$revenue_data   = $revenue_result->fetch_assoc();

// Print total revenue row **year-wise**
echo "<tr>
        <td><strong>Overall</strong></td>
        <td><strong>" . number_format($revenue_data['monthly_revenue'], 2) . "</strong></td>
        <td><strong>" . number_format($revenue_data['last_month_revenue'], 2) . "</strong></td>
        <td><strong>" . number_format($revenue_data['yearly_revenue'], 2) . "</strong></td>
        <td><strong>" . number_format($revenue_data['total_revenue'], 2) . "</strong></td>
      </tr>";

// Fetch employee-wise revenue **year-wise**
$emp_sql = "SELECT
            employee_name,
            (SELECT COALESCE(SUM(total_amount), 0) FROM invoices WHERE status='Completed' AND employee_name=i.employee_name AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())) AS monthly_revenue,
            (SELECT COALESCE(SUM(total_amount), 0) FROM invoices WHERE status='Completed' AND employee_name=i.employee_name AND MONTH(created_at) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) AND YEAR(created_at) = YEAR(CURRENT_DATE())) AS last_month_revenue,
            (SELECT COALESCE(SUM(total_amount), 0) FROM invoices WHERE status='Completed' AND employee_name=i.employee_name AND YEAR(created_at) = YEAR(CURRENT_DATE())) AS yearly_revenue
            FROM invoices i
            WHERE status='Completed'
            GROUP BY employee_name
            ORDER BY employee_name ASC";

$emp_result = $conn->query($emp_sql);

// Print employee revenue data
while ($row = $emp_result->fetch_assoc()) {
    echo "<tr>
            <td>" . htmlspecialchars(trim($row['employee_name'])) . "</td>
            <td>" . number_format($row['monthly_revenue'], 2) . "</td>
            <td>" . number_format($row['last_month_revenue'], 2) . "</td>
            <td>" . number_format($row['yearly_revenue'], 2) . "</td>
            <td>" . number_format($revenue_data['total_revenue'], 2) . "</td>
          </tr>";
}

echo "</table></body></html>";
