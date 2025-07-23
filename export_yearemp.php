<?php
include 'db.php';

// Set headers for Excel file download
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=Employee_Yearly_Revenue_Details.xls");
header("Pragma: no-cache");
header("Expires: 0");

// Fetch selected year from URL parameters
$selected_year = isset($_GET['filter_year']) ? $_GET['filter_year'] : date('Y');

// Query to get all data for export
$emp_yearly_sql = "SELECT employee_name,
                SUM(CASE WHEN status IN ('Completed','Dispatched') THEN total_amount ELSE 0 END) AS total_yearly_revenue,
                COUNT(*) as order_count,
                SUM(CASE WHEN is_repeated_order = 'yes' AND status IN ('Completed','Dispatched') THEN total_amount ELSE 0 END) as repeated_revenue,
                SUM(CASE WHEN is_repeated_order = 'no' AND status IN ('Completed','Dispatched') THEN total_amount ELSE 0 END) as new_revenue,
                SUM(CASE WHEN is_repeated_order = 'yes' THEN 1 ELSE 0 END) as repeated_orders,
                SUM(CASE WHEN is_repeated_order = 'no' THEN 1 ELSE 0 END) as new_orders,
                SUM(CASE WHEN status = 'Returned' THEN total_amount ELSE 0 END) as returned_amount,
                SUM(CASE WHEN status = 'Returned' THEN 1 ELSE 0 END) as returned_orders
                FROM invoices
                WHERE status IN ('Completed','Dispatched','Returned')
                AND YEAR(created_at) = '$selected_year'
                GROUP BY employee_name
                ORDER BY total_yearly_revenue DESC";

$emp_yearly_result = $conn->query($emp_yearly_sql);

// Create Excel content
echo "<table border='1'>";
echo "<tr><th colspan='10'>Employee Yearly Revenue Report - $selected_year</th></tr>";
echo "<tr>
        <th>Employee Name</th>
        <th>Total Orders</th>
        <th>New Orders</th>
        <th>Repeat Orders</th>
        <th>Returned Orders</th>
        <th>Total Revenue (₹)</th>
        <th>New Revenue (₹)</th>
        <th>Repeat Revenue (₹)</th>
        <th>Returned Amount (₹)</th>
        <th>Avg/Order (₹)</th>
      </tr>";

if ($emp_yearly_result->num_rows > 0) {
    while ($row = $emp_yearly_result->fetch_assoc()) {
        $avg_order = $row['order_count'] > 0 ? $row['total_yearly_revenue'] / $row['order_count'] : 0;
        
        echo "<tr>";
        echo "<td>".htmlspecialchars($row['employee_name'])."</td>";
        echo "<td>".$row['order_count']."</td>";
        echo "<td>".$row['new_orders']."</td>";
        echo "<td>".$row['repeated_orders']."</td>";
        echo "<td>".$row['returned_orders']."</td>";
        echo "<td>".number_format($row['total_yearly_revenue'], 2)."</td>";
        echo "<td>".number_format($row['new_revenue'], 2)."</td>";
        echo "<td>".number_format($row['repeated_revenue'], 2)."</td>";
        echo "<td>".number_format($row['returned_amount'], 2)."</td>";
        echo "<td>".number_format($avg_order, 2)."</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='10'>No data found for selected year.</td></tr>";
}

echo "</table>";
exit();
?>