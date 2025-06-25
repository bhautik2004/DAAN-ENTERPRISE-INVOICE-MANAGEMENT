<?php 
include 'header.php'; 
include 'head.php'; 
include 'db.php'; // Database Connection
?>

<body class="bg-gray-100 flex">
    <?php include 'sidebar.php'; ?>
    <main class="flex-1 pl-56 m-4">

        <h2 class="text-2xl font-bold text-[var(--primary-color)] mb-4 text-center">Employee Monthly Revenue Report</h2>

<!-- Employee-wise Monthly Revenue with Date Filter -->
<div class="bg-white p-4 rounded-md shadow-md mt-4">
    <h3 class="text-lg font-semibold text-gray-700 mb-2">Employee-wise Monthly Revenue</h3>

    <?php
    // Set default month and year to the current month and year
    $current_month = date('n'); // Numeric month (1-12)
    $current_year = date('Y');

    // Fetch selected month and year from filter, or use current values
    $selected_month = isset($_GET['filter_month']) ? $_GET['filter_month'] : $current_month;
    $selected_year = isset($_GET['filter_year']) ? $_GET['filter_year'] : $current_year;
    ?>

    <!-- Date Filter for Monthly Revenue -->
    <form method="GET" class="mb-4 flex flex-wrap items-center gap-4">
        <label for="filter_month" class="font-semibold">Month:</label>
        <select name="filter_month" id="filter_month" class="border p-2 rounded-md">
            <?php 
            for ($m = 1; $m <= 12; $m++) {
                $month_name = date('F', mktime(0, 0, 0, $m, 1));
                $selected = ($m == $selected_month) ? "selected" : "";
                echo "<option value='$m' $selected>$month_name</option>";
            }
            ?>
        </select>

        <label for="filter_year" class="font-semibold">Year:</label>
        <select name="filter_year" id="filter_year" class="border p-2 rounded-md">
            <?php 
            for ($y = $current_year; $y >= ($current_year - 5); $y--) {
                $selected = ($y == $selected_year) ? "selected" : "";
                echo "<option value='$y' $selected>$y</option>";
            }
            ?>
        </select>

        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Filter</button>

        <!-- Export to Excel Button -->
        <a href="export_monthemp.php" class="bg-yellow-500 text-white px-4 py-2 rounded-md hover:bg-yellow-600">
            Export to Excel
        </a>
    </form>

    <!-- Employee-wise Monthly Revenue Table -->
    <table class="w-full border-collapse border border-gray-300 text-sm">
        <thead class="bg-gray-200">
            <tr class="whitespace-nowrap text-left">
                <th class="p-2 border">Employee Name</th>
                <th class="p-2 border">Total Revenue (₹)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // Query to get employee-wise total monthly revenue based on filter
            $emp_monthly_sql = "SELECT employee_name, 
                                COALESCE(SUM(total_amount), 0) AS total_monthly_revenue
                                FROM invoices 
                                WHERE status='Completed' 
                                AND MONTH(created_at) = '$selected_month' 
                                AND YEAR(created_at) = '$selected_year'
                                GROUP BY employee_name 
                                ORDER BY total_monthly_revenue DESC";

            $emp_monthly_result = $conn->query($emp_monthly_sql);
            if ($emp_monthly_result->num_rows > 0):
                while ($row = $emp_monthly_result->fetch_assoc()): ?>
                <tr class="text-left bg-gray-50 hover:bg-gray-100">
                    <td class="p-2 border"> <?php echo htmlspecialchars($row['employee_name']); ?> </td>
                    <td class="p-2 border text-blue-600 font-bold">₹ <?php echo number_format($row['total_monthly_revenue']); ?> </td>
                </tr>
                <?php endwhile;
            else: ?>
                <tr>
                    <td class="p-2 border text-center text-gray-500" colspan="2">No data found for selected month.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

    </main>
</body>
</html>
