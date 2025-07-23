<?php
include 'db.php'; // Include database connection

// Set headers for Excel file download
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=Employee_Monthly_Revenue_Details.xls");
header("Pragma: no-cache");
header("Expires: 0");

// Fetch selected month and year from URL parameters
$selected_month = isset($_GET['filter_month']) ? $_GET['filter_month'] : date('m');
$selected_year  = isset($_GET['filter_year']) ? $_GET['filter_year'] : date('Y');

// Convert month number to month name
$month_name = date("F", mktime(0, 0, 0, $selected_month, 1));

// Query to get all data for export
$emp_monthly_sql = "SELECT employee_name,
                    SUM(CASE WHEN status IN ('Completed','Dispatched') THEN total_amount ELSE 0 END) AS total_monthly_revenue,
                    COUNT(*) as order_count,
                    SUM(CASE WHEN is_repeated_order = 'yes' AND status IN ('Completed','Dispatched') THEN total_amount ELSE 0 END) as repeated_revenue,
                    SUM(CASE WHEN is_repeated_order = 'no' AND status IN ('Completed','Dispatched') THEN total_amount ELSE 0 END) as new_revenue,
                    SUM(CASE WHEN is_repeated_order = 'yes' THEN 1 ELSE 0 END) as repeated_orders,
                    SUM(CASE WHEN is_repeated_order = 'no' THEN 1 ELSE 0 END) as new_orders,
                    SUM(CASE WHEN status = 'Returned' THEN total_amount ELSE 0 END) as returned_amount,
                    SUM(CASE WHEN status = 'Returned' THEN 1 ELSE 0 END) as returned_orders
                    FROM invoices
                    WHERE status IN ('Completed','Dispatched','Returned')
                    AND MONTH(created_at) = '$selected_month'
                    AND YEAR(created_at) = '$selected_year'
                    GROUP BY employee_name
                    ORDER BY total_monthly_revenue DESC";

$emp_monthly_result = $conn->query($emp_monthly_sql);

// Create a table for the exported data with all columns
echo "<table border='1'>";
echo "<tr><th colspan='9'>Employee Monthly Revenue Report - $month_name $selected_year</th></tr>";
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

if ($emp_monthly_result->num_rows > 0) {
    while ($row = $emp_monthly_result->fetch_assoc()) {
        $avg_order = $row['order_count'] > 0 ? round($row['total_monthly_revenue'] / $row['order_count']) : 0;
        
        echo "<tr>";
        echo "<td>".htmlspecialchars($row['employee_name'])."</td>";
        echo "<td>".$row['order_count']."</td>";
        echo "<td>".$row['new_orders']."</td>";
        echo "<td>".$row['repeated_orders']."</td>";
        echo "<td>".$row['returned_orders']."</td>";
        echo "<td>".round($row['total_monthly_revenue'])."</td>";
        echo "<td>".round($row['new_revenue'])."</td>";
        echo "<td>".round($row['repeated_revenue'])."</td>";
        echo "<td>".round($row['returned_amount'])."</td>";
        echo "<td>".$avg_order."</td>";
        echo "</tr>";
    }
    
    // Add summary row
    $summary_sql = "SELECT 
                    SUM(CASE WHEN status IN ('Completed','Dispatched') THEN total_amount ELSE 0 END) as total,
                    SUM(CASE WHEN is_repeated_order = 'yes' AND status IN ('Completed','Dispatched') THEN total_amount ELSE 0 END) as repeated_revenue,
                    SUM(CASE WHEN is_repeated_order = 'no' AND status IN ('Completed','Dispatched') THEN total_amount ELSE 0 END) as new_revenue,
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN is_repeated_order = 'yes' THEN 1 ELSE 0 END) as repeated_orders,
                    SUM(CASE WHEN is_repeated_order = 'no' THEN 1 ELSE 0 END) as new_orders,
                    SUM(CASE WHEN status = 'Returned' THEN total_amount ELSE 0 END) as returned_amount,
                    SUM(CASE WHEN status = 'Returned' THEN 1 ELSE 0 END) as returned_orders
                    FROM invoices
                    WHERE status IN ('Completed','Dispatched','Returned')
                    AND MONTH(created_at) = '$selected_month'
                    AND YEAR(created_at) = '$selected_year'";
    
    $summary_result = $conn->query($summary_sql);
    $summary = $summary_result->fetch_assoc();
    
    $avg_total = $summary['total_orders'] > 0 ? round($summary['total']/$summary['total_orders']) : 0;
    
    echo "<tr style='font-weight:bold; background-color:#f2f2f2;'>";
    echo "<td>TOTAL</td>";
    echo "<td>".$summary['total_orders']."</td>";
    echo "<td>".$summary['new_orders']."</td>";
    echo "<td>".$summary['repeated_orders']."</td>";
    echo "<td>".$summary['returned_orders']."</td>";
    echo "<td>".round($summary['total'])."</td>";
    echo "<td>".round($summary['new_revenue'])."</td>";
    echo "<td>".round($summary['repeated_revenue'])."</td>";
    echo "<td>".round($summary['returned_amount'])."</td>";
    echo "<td>".$avg_total."</td>";
    echo "</tr>";
} else {
    echo "<tr><td colspan='10'>No data found for selected month.</td></tr>";
}

echo "</table>";
exit();