<?php
include 'db.php'; // Include database connection

// Set headers for Excel file download
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=Employee_Monthly_Revenue.xls");
header("Pragma: no-cache");
header("Expires: 0");

// Fetch selected month and year from URL parameters
$selected_month = isset($_GET['filter_month']) ? $_GET['filter_month'] : date('m');
$selected_year  = isset($_GET['filter_year']) ? $_GET['filter_year'] : date('Y');

// Convert month number to month name
$month_name = date("F", mktime(0, 0, 0, $selected_month, 1));

// Query to get all data for export
$emp_monthly_sql = "SELECT employee_name,
                    COALESCE(SUM(total_amount), 0) AS total_monthly_revenue
                    FROM invoices
                    WHERE status='Completed'
                    AND MONTH(created_at) = '$selected_month'
                    AND YEAR(created_at) = '$selected_year'
                    GROUP BY employee_name
                    ORDER BY total_monthly_revenue DESC";

$emp_monthly_result = $conn->query($emp_monthly_sql);

// Create a table for the exported data
echo "<table border='1'>";
echo "<tr><th>Employee Name</th><th>Total Revenue</th><th>Month</th><th>Year</th></tr>";

if ($emp_monthly_result->num_rows > 0) {
    while ($row = $emp_monthly_result->fetch_assoc()) {
        $employee_name = $row['employee_name'];
        $total_revenue = number_format($row['total_monthly_revenue'], 2);

        // Output table rows
        echo "<tr>";
        echo "<td>$employee_name</td>";
        echo "<td>$total_revenue</td>";
        echo "<td>$month_name</td>";
        echo "<td>$selected_year</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='4'>No data found for selected month.</td></tr>";
}

echo "</table>";
exit();
