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

    // Get totals for this filter
    $total_sql = "SELECT 
                SUM(total_amount) as total,
                SUM(CASE WHEN is_repeated_order = 'yes' THEN total_amount ELSE 0 END) as repeated_revenue,
                SUM(CASE WHEN is_repeated_order = 'no' THEN total_amount ELSE 0 END) as new_revenue,
                COUNT(*) as total_orders,
                SUM(CASE WHEN is_repeated_order = 'yes' THEN 1 ELSE 0 END) as repeated_orders,
                SUM(CASE WHEN is_repeated_order = 'no' THEN 1 ELSE 0 END) as new_orders
                FROM invoices 
                WHERE status='Completed' 
                AND MONTH(created_at) = '$selected_month' 
                AND YEAR(created_at) = '$selected_year'";
    $total_result = $conn->query($total_sql);
    $total_data = $total_result->fetch_assoc();
    
    $total_revenue = $total_data['total'] ?? 0;
    $repeated_revenue = $total_data['repeated_revenue'] ?? 0;
    $new_revenue = $total_data['new_revenue'] ?? 0;
    $total_orders = $total_data['total_orders'] ?? 0;
    $repeated_orders = $total_data['repeated_orders'] ?? 0;
    $new_orders = $total_data['new_orders'] ?? 0;
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

    <!-- Revenue Summary Cards -->
    <div class="mb-4 p-4 bg-gray-100 rounded-lg">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-3 bg-white rounded shadow">
                <h4 class="font-bold text-gray-700">Total Revenue</h4>
                <p class="text-xl text-green-600">₹ <?php echo number_format($total_revenue, 2); ?></p>
                <p class="text-sm text-gray-500"><?php echo $total_orders; ?> orders</p>
            </div>
            <div class="p-3 bg-white rounded shadow">
                <h4 class="font-bold text-gray-700">New Customer Revenue</h4>
                <p class="text-xl text-blue-600">₹ <?php echo number_format($new_revenue, 2); ?></p>
                <p class="text-sm text-gray-500"><?php echo $new_orders; ?> orders (<?php echo $total_orders > 0 ? round(($new_orders/$total_orders)*100, 1) : 0; ?>%)</p>
            </div>
            <div class="p-3 bg-white rounded shadow">
                <h4 class="font-bold text-gray-700">Repeat Customer Revenue</h4>
                <p class="text-xl text-purple-600">₹ <?php echo number_format($repeated_revenue, 2); ?></p>
                <p class="text-sm text-gray-500"><?php echo $repeated_orders; ?> orders (<?php echo $total_orders > 0 ? round(($repeated_orders/$total_orders)*100, 1) : 0; ?>%)</p>
            </div>
        </div>
    </div>

    <!-- Employee-wise Monthly Revenue Table -->
    <table class="w-full border-collapse border border-gray-300 text-sm">
        <thead class="bg-gray-200">
            <tr class="whitespace-nowrap text-left">
                <th class="p-2 border">Employee Name</th>
                <th class="p-2 border">Total Orders</th>
                <th class="p-2 border">New Orders</th>
                <th class="p-2 border">Repeat Orders</th>
                <th class="p-2 border">Total Revenue (₹)</th>
                <th class="p-2 border">New Revenue (₹)</th>
                <th class="p-2 border">Repeat Revenue (₹)</th>
                <th class="p-2 border">Avg/Order (₹)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // Query to get employee-wise total monthly revenue with repeated order analysis
            $emp_monthly_sql = "SELECT employee_name, 
                                COALESCE(SUM(total_amount), 0) AS total_monthly_revenue,
                                COUNT(*) as order_count,
                                SUM(CASE WHEN is_repeated_order = 'yes' THEN total_amount ELSE 0 END) as repeated_revenue,
                                SUM(CASE WHEN is_repeated_order = 'no' THEN total_amount ELSE 0 END) as new_revenue,
                                SUM(CASE WHEN is_repeated_order = 'yes' THEN 1 ELSE 0 END) as repeated_orders,
                                SUM(CASE WHEN is_repeated_order = 'no' THEN 1 ELSE 0 END) as new_orders
                                FROM invoices 
                                WHERE status='Completed' 
                                AND MONTH(created_at) = '$selected_month' 
                                AND YEAR(created_at) = '$selected_year'
                                GROUP BY employee_name 
                                ORDER BY total_monthly_revenue DESC";

            $emp_monthly_result = $conn->query($emp_monthly_sql);
            if ($emp_monthly_result->num_rows > 0):
                while ($row = $emp_monthly_result->fetch_assoc()): 
                    $avg_order = $row['order_count'] > 0 ? $row['total_monthly_revenue'] / $row['order_count'] : 0;
                ?>
                <tr class="text-left bg-gray-50 hover:bg-gray-100">
                    <td class="p-2 border"><?php echo htmlspecialchars($row['employee_name']); ?></td>
                    <td class="p-2 border"><?php echo $row['order_count']; ?></td>
                    <td class="p-2 border"><?php echo $row['new_orders']; ?></td>
                    <td class="p-2 border"><?php echo $row['repeated_orders']; ?></td>
                    <td class="p-2 border text-green-600 font-bold">₹ <?php echo number_format($row['total_monthly_revenue'], 2); ?></td>
                    <td class="p-2 border text-blue-600">₹ <?php echo number_format($row['new_revenue'], 2); ?></td>
                    <td class="p-2 border text-purple-600">₹ <?php echo number_format($row['repeated_revenue'], 2); ?></td>
                    <td class="p-2 border">₹ <?php echo number_format($avg_order, 2); ?></td>
                </tr>
                <?php endwhile;
            else: ?>
                <tr>
                    <td class="p-2 border text-center text-gray-500" colspan="8">No data found for selected month.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

    </main>
</body>
</html>