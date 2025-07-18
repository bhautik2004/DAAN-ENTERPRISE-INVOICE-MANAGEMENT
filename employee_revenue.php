<?php
    include 'db.php'; // Database Connection

    // Ensure employee is logged in
    if (! isset($_SESSION['user'])) {
        die("Access Denied");
    }
    $employee_name = $_SESSION['user'];

    // Fetch filter parameter
    $date_condition = "";
    if (! empty($_GET['selected_date'])) {
        $selected_date  = $_GET['selected_date'];
        $date_condition = "AND DATE(created_at) = '$selected_date'";
    }

    // Calculate revenues
    $monthly_sql = "SELECT SUM(total_amount) as monthly_revenue FROM invoices WHERE status='Completed'
                AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND employee_name = '$employee_name'";
    $monthly_result  = $conn->query($monthly_sql);
    $monthly_revenue = $monthly_result->fetch_assoc()['monthly_revenue'] ?? 0;

    $last_month_sql = "SELECT SUM(total_amount) as last_month_revenue FROM invoices WHERE status='Completed'
                    AND MONTH(created_at) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) AND employee_name = '$employee_name'";
    $last_month_result  = $conn->query($last_month_sql);
    $last_month_revenue = $last_month_result->fetch_assoc()['last_month_revenue'] ?? 0;

    $today_sql = "SELECT SUM(total_amount) as today_revenue FROM invoices WHERE status='Completed'
              AND DATE(created_at) = CURDATE() AND employee_name = '$employee_name'";
    $today_result  = $conn->query($today_sql);
    $today_revenue = $today_result->fetch_assoc()['today_revenue'] ?? 0;

    // Fetch revenue for selected date
    $emp_sql = "SELECT DATE(created_at) as invoice_date, COALESCE(SUM(total_amount), 0) AS daily_revenue
            FROM invoices WHERE status='Completed' AND employee_name = '$employee_name' $date_condition
            GROUP BY invoice_date ORDER BY invoice_date DESC";
    $emp_result = $conn->query($emp_sql);
?>

<div class="p-6 bg-white rounded-lg shadow-md mt-4">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Revenue Report</h2>

    <!-- Revenue Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="p-4 bg-blue-100 border-l-4 border-blue-500 rounded-lg">
            <h3 class="text-lg font-semibold text-blue-800">Today's Revenue</h3>
            <p class="text-xl font-bold text-blue-600">₹ <?php echo number_format($today_revenue); ?></p>
        </div>
        <div class="p-4 bg-green-100 border-l-4 border-green-500 rounded-lg">
            <h3 class="text-lg font-semibold text-green-800">Monthly Revenue</h3>
            <p class="text-xl font-bold text-green-600">₹ <?php echo number_format($monthly_revenue); ?></p>
        </div>
        <div class="p-4 bg-yellow-100 border-l-4 border-yellow-500 rounded-lg">
            <h3 class="text-lg font-semibold text-yellow-800">Last Month Revenue</h3>
            <p class="text-xl font-bold text-yellow-600">₹ <?php echo number_format($last_month_revenue); ?></p>
        </div>
    </div>

    <!-- Date Filter Form -->
    <form method="GET" class="mb-6 flex flex-wrap gap-4">
        <div>
            <label for="selected_date" class="block text-gray-700 font-medium">Select Date</label>
            <input type="date" name="selected_date" required class="border rounded-lg px-4 py-2">
        </div>
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 self-end">
            Filter
        </button>
    </form>

    <!-- Revenue Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-300 rounded-lg">
            <thead class="bg-gray-200 text-gray-700">
                <tr>
                    <th class="py-3 px-6 text-left border-b">Date</th>
                    <th class="py-3 px-6 text-left border-b">Daily Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $emp_result->fetch_assoc()): ?>
                <tr class="border-b">
                    <td class="py-2 px-6"><?php echo date('d-m-Y', strtotime($row['invoice_date'])); ?></td>
                    <td class="py-2 px-6 font-semibold text-gray-800">₹
                        <?php echo number_format($row['daily_revenue']); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>