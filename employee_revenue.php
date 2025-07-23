<?php
    include 'db.php'; // Database Connection

    // Ensure employee is logged in
    if (!isset($_SESSION['user'])) {
        die("Access Denied");
    }
    $employee_name = $_SESSION['user'];

    // Fetch filter parameter
    $date_condition = "";
    if (!empty($_GET['selected_date'])) {
        $selected_date  = $_GET['selected_date'];
        $date_condition = "AND DATE(created_at) = '$selected_date'";
    }

    // Calculate revenues - including Dispatched and excluding Returned from revenue
    $monthly_sql = "SELECT 
                    SUM(CASE WHEN status IN ('Completed','Dispatched') THEN total_amount ELSE 0 END) as monthly_revenue,
                    SUM(CASE WHEN status = 'Returned' THEN total_amount ELSE 0 END) as monthly_returns,
                    COUNT(*) as monthly_orders,
                    SUM(CASE WHEN status = 'Returned' THEN 1 ELSE 0 END) as monthly_return_orders,
                    SUM(CASE WHEN is_repeated_order = 'yes' THEN 1 ELSE 0 END) as monthly_repeat_orders
                    FROM invoices 
                    WHERE status IN ('Completed','Dispatched','Returned')
                    AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
                    AND YEAR(created_at) = YEAR(CURRENT_DATE())
                    AND employee_name = '$employee_name'";
    $monthly_result  = $conn->query($monthly_sql);
    $monthly_data = $monthly_result->fetch_assoc();
    $monthly_revenue = $monthly_data['monthly_revenue'] ?? 0;
    $monthly_returns = $monthly_data['monthly_returns'] ?? 0;
    $monthly_orders = $monthly_data['monthly_orders'] ?? 0;
    $monthly_return_orders = $monthly_data['monthly_return_orders'] ?? 0;
    $monthly_repeat_orders = $monthly_data['monthly_repeat_orders'] ?? 0;

    $last_month_sql = "SELECT 
                      SUM(CASE WHEN status IN ('Completed','Dispatched') THEN total_amount ELSE 0 END) as last_month_revenue,
                      SUM(CASE WHEN status = 'Returned' THEN total_amount ELSE 0 END) as last_month_returns,
                      SUM(CASE WHEN status = 'Returned' THEN 1 ELSE 0 END) as last_month_return_orders
                      FROM invoices 
                      WHERE status IN ('Completed','Dispatched','Returned')
                      AND MONTH(created_at) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) 
                      AND YEAR(created_at) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)
                      AND employee_name = '$employee_name'";
    $last_month_result  = $conn->query($last_month_sql);
    $last_month_data = $last_month_result->fetch_assoc();
    $last_month_revenue = $last_month_data['last_month_revenue'] ?? 0;
    $last_month_returns = $last_month_data['last_month_returns'] ?? 0;
    $last_month_return_orders = $last_month_data['last_month_return_orders'] ?? 0;

    $today_sql = "SELECT 
                 SUM(CASE WHEN status IN ('Completed','Dispatched') THEN total_amount ELSE 0 END) as today_revenue,
                 SUM(CASE WHEN status = 'Returned' THEN total_amount ELSE 0 END) as today_returns,
                 SUM(CASE WHEN status = 'Returned' THEN 1 ELSE 0 END) as today_return_orders,
                 COUNT(*) as today_orders
                 FROM invoices 
                 WHERE status IN ('Completed','Dispatched','Returned')
                 AND DATE(created_at) = CURDATE() 
                 AND employee_name = '$employee_name'";
    $today_result  = $conn->query($today_sql);
    $today_data = $today_result->fetch_assoc();
    $today_revenue = $today_data['today_revenue'] ?? 0;
    $today_returns = $today_data['today_returns'] ?? 0;
    $today_return_orders = $today_data['today_return_orders'] ?? 0;
    $today_orders = $today_data['today_orders'] ?? 0;

    // Fetch detailed revenue for selected date
    $emp_sql = "SELECT 
               DATE(created_at) as invoice_date, 
               SUM(CASE WHEN status IN ('Completed','Dispatched') THEN total_amount ELSE 0 END) AS daily_revenue,
               SUM(CASE WHEN status = 'Returned' THEN total_amount ELSE 0 END) as daily_returns,
               SUM(CASE WHEN status = 'Returned' THEN 1 ELSE 0 END) as daily_return_orders,
               COUNT(*) as total_orders,
               SUM(CASE WHEN is_repeated_order = 'yes' THEN 1 ELSE 0 END) as repeat_orders
               FROM invoices 
               WHERE status IN ('Completed','Dispatched','Returned') 
               AND employee_name = '$employee_name' $date_condition
               GROUP BY invoice_date 
               ORDER BY invoice_date DESC";
    $emp_result = $conn->query($emp_sql);
?>

<div class="p-6 bg-white rounded-lg shadow-md mt-4">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Revenue Report</h2>

    <!-- Revenue Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Today's Revenue Card -->
        <div class="p-4 bg-blue-100 border-l-4 border-blue-500 rounded-lg">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-lg font-semibold text-blue-800">Today's Revenue</h3>
                    <p class="text-xl font-bold text-blue-600">₹ <?php echo number_format($today_revenue); ?></p>
                    <p class="text-sm text-gray-600"><?php echo $today_orders; ?> orders</p>
                </div>
                <?php if ($today_return_orders > 0): ?>
                <div class="text-right">
                    <div class="text-sm text-red-600">
                        Returns: ₹<?php echo number_format($today_returns); ?>
                    </div>
                    <div class="text-xs text-red-500">
                        (<?php echo $today_return_orders; ?> orders)
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Monthly Revenue Card -->
        <div class="p-4 bg-green-100 border-l-4 border-green-500 rounded-lg">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-lg font-semibold text-green-800">Monthly Revenue</h3>
                    <p class="text-xl font-bold text-green-600">₹ <?php echo number_format($monthly_revenue); ?></p>
                    <p class="text-sm text-gray-600">
                        <?php echo $monthly_orders; ?> orders (<?php echo $monthly_repeat_orders; ?> repeats)
                    </p>
                </div>
                <?php if ($monthly_return_orders > 0): ?>
                <div class="text-right">
                    <div class="text-sm text-red-600">
                        Returns: ₹<?php echo number_format($monthly_returns); ?>
                    </div>
                    <div class="text-xs text-red-500">
                        (<?php echo $monthly_return_orders; ?> orders)
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Last Month Revenue Card -->
        <div class="p-4 bg-yellow-100 border-l-4 border-yellow-500 rounded-lg">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-lg font-semibold text-yellow-800">Last Month Revenue</h3>
                    <p class="text-xl font-bold text-yellow-600">₹ <?php echo number_format($last_month_revenue); ?></p>
                </div>
                <?php if ($last_month_return_orders > 0): ?>
                <div class="text-right">
                    <div class="text-sm text-red-600">
                        Returns: ₹<?php echo number_format($last_month_returns); ?>
                    </div>
                    <div class="text-xs text-red-500">
                        (<?php echo $last_month_return_orders; ?> orders)
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php if ($last_month_revenue > 0 && $monthly_revenue > 0): 
                $growth = (($monthly_revenue - $last_month_revenue) / $last_month_revenue) * 100;
            ?>
            <p class="text-sm mt-2 <?php echo $growth >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                <?php echo ($growth >= 0 ? '↑' : '↓') . ' ' . number_format(abs($growth), 1) . '% from last month' ?>
            </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Date Filter Form -->
    <form method="GET" class="mb-6 flex flex-wrap gap-4">
        <div>
            <label for="selected_date" class="block text-gray-700 font-medium">Select Date</label>
            <input type="date" name="selected_date" class="border rounded-lg px-4 py-2">
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
                    <th class="py-3 px-6 text-left border-b">Orders</th>
                    <th class="py-3 px-6 text-left border-b">Repeat Orders</th>
                    <th class="py-3 px-6 text-left border-b">Return Orders</th>
                    <th class="py-3 px-6 text-left border-b">Revenue</th>
                    <th class="py-3 px-6 text-left border-b">Returns</th>
                    
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $emp_result->fetch_assoc()): 
                    $net_revenue = $row['daily_revenue'] - $row['daily_returns'];
                    $repeat_percentage = $row['total_orders'] > 0 ? round(($row['repeat_orders'] / $row['total_orders']) * 100) : 0;
                    $return_percentage = $row['total_orders'] > 0 ? round(($row['daily_return_orders'] / $row['total_orders']) * 100) : 0;
                ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-2 px-6"><?php echo date('d-m-Y', strtotime($row['invoice_date'])); ?></td>
                    <td class="py-2 px-6"><?php echo $row['total_orders']; ?></td>
                    <td class="py-2 px-6">
                        <?php echo $row['repeat_orders']; ?>
                        <span class="text-xs text-gray-500">(<?php echo $repeat_percentage; ?>%)</span>
                    </td>
                    <td class="py-2 px-6">
                        <?php echo $row['daily_return_orders']; ?>
                        <span class="text-xs text-red-500">(<?php echo $return_percentage; ?>%)</span>
                    </td>
                    <td class="py-2 px-6 font-semibold text-green-600">₹ <?php echo number_format($row['daily_revenue']); ?></td>
                    <td class="py-2 px-6 font-semibold text-red-600">₹ <?php echo number_format($row['daily_returns']); ?></td>
                   
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>