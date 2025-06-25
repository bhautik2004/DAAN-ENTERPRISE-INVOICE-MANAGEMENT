<?php
include('db.php'); // Include database connection

// Set headers for Excel file download
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=Employee_Yearly_Revenue.xls");
header("Pragma: no-cache");
header("Expires: 0");

// Fetch selected year from URL parameters
$selected_year = isset($_GET['filter_year']) ? $_GET['filter_year'] : date('Y');

// Query to get all data for export
$emp_yearly_sql = "SELECT employee_name, 
                    COALESCE(SUM(total_amount), 0) AS total_yearly_revenue
                    FROM invoices 
                    WHERE status='Completed' 
                    AND YEAR(created_at) = '$selected_year'
                    GROUP BY employee_name 
                    ORDER BY total_yearly_revenue DESC";

$emp_yearly_result = $conn->query($emp_yearly_sql);

// Create a table for the exported data
echo "<table border='1'>";
echo "<tr><th>Employee Name</th><th>Total Revenue</th><th>Year</th></tr>";

if ($emp_yearly_result->num_rows > 0) {
    while ($row = $emp_yearly_result->fetch_assoc()) {
        $employee_name = $row['employee_name'];
        $total_revenue = number_format($row['total_yearly_revenue'], 2);

        // Output table rows
        echo "<tr>";
        echo "<td>$employee_name</td>";
        echo "<td>$total_revenue</td>";
        echo "<td>$selected_year</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='3'>No data found for selected year.</td></tr>";
}

echo "</table>";
exit();
?>
