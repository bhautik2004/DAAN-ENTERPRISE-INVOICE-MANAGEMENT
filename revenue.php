<?php
    include 'db.php'; // Database Connection

    // Set default limit for pagination
    $limit  = 50;
    $page   = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    // Get customer ID from filter if set
    $customer_id = isset($_GET['customer_id']) ? $_GET['customer_id'] : '';
?>

<!-- Customer Filter -->
<div class="p-6 bg-white rounded-lg shadow-md mb-4 mt-4">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Customer Id Filter</h2>
    <form method="GET" class="flex items-center gap-4">
        <div class="flex-1">
            <label class="block font-medium mb-1">Select Customer ID</label>
            <select name="customer_id" class="w-full p-2 border rounded-md">
                <option value="">All Customers</option>
                <?php
                    $customers_sql    = "SELECT DISTINCT customer_id FROM invoices WHERE customer_id IS NOT NULL AND customer_id != '' ORDER BY customer_id";
                    $customers_result = $conn->query($customers_sql);

                    if ($customers_result && $customers_result->num_rows > 0) {
                        while ($customer = $customers_result->fetch_assoc()) {
                            if (! empty($customer['customer_id'])) { // Check if customer_id is not empty
                                $selected = $customer_id == $customer['customer_id'] ? 'selected' : '';
                                echo "<option value='{$customer['customer_id']}' $selected>{$customer['customer_id']}</option>";
                            }
                        }
                    }
                ?>
            </select>
        </div>
        <div class="mt-5">
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                Filter
            </button>
            <?php if ($customer_id): ?>
            <a href="?" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 ml-2">
                Clear
            </a>
            <?php endif; ?>
        </div>
    </form>
</div>
<?php
            // Status conditions
            $completed_status = "('Completed', 'Dispatched')";
            $pending_status   = "('Dispatched')";

            // Add customer condition if filter is set
            $customer_condition = $customer_id ? " AND customer_id = '$customer_id'" : "";

            // Fetch revenue data that's always shown
            $revenue_sql    = "SELECT SUM(total_amount) as total_revenue FROM invoices WHERE status IN $completed_status $customer_condition";
            $revenue_result = $conn->query($revenue_sql);
            $total_revenue  = $revenue_result->fetch_assoc()['total_revenue'] ?? 0;

            $today_sql     = "SELECT SUM(total_amount) as today_revenue FROM invoices WHERE status IN $completed_status AND DATE(created_at) = CURDATE() $customer_condition";
            $today_result  = $conn->query($today_sql);
            $today_revenue = $today_result->fetch_assoc()['today_revenue'] ?? 0;

            // Pending revenue calculations
            $pending_revenue_sql = "SELECT SUM(total_amount) as pending_revenue FROM invoices WHERE status IN $pending_status $customer_condition";
            $pending_result      = $conn->query($pending_revenue_sql);
            $pending_revenue     = $pending_result->fetch_assoc()['pending_revenue'] ?? 0;

            // Order status counts
            $status_counts_sql = "SELECT
                                status,
                                COUNT(*) as count,
                                SUM(total_amount) as amount
                                FROM invoices
                                WHERE 1=1 $customer_condition
                                GROUP BY status";
            $status_counts_result = $conn->query($status_counts_sql);
            $status_counts        = [];
            $total_orders         = 0;
            $total_amount_all     = 0;

            while ($row = $status_counts_result->fetch_assoc()) {
                $status_counts[$row['status']] = [
                    'count'  => $row['count'],
                    'amount' => $row['amount'],
                ];
                $total_orders += $row['count'];
                $total_amount_all += $row['amount'];
            }
        ?>

<!-- Enhanced Revenue Summary -->
<div class="p-6 bg-white rounded-lg shadow-md">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">
        <?php echo $customer_id ? "Revenue Summary for Customer ID: $customer_id" : "Revenue Summary"; ?>
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Total Revenue -->
        <div class="p-4 bg-green-100 border-l-4 border-green-500 rounded-lg">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-lg font-semibold text-green-800">Total Revenue (Completed/Dispatched)</h3>
                    <p class="text-xl font-bold text-green-600">₹ <?php echo number_format($total_revenue); ?>
                    </p>
                </div>
                <span class="bg-green-200 text-green-800 text-xs px-2 py-1 rounded-full">All Time</span>
            </div>
            <p class="text-sm text-gray-500 mt-2">Since <?php
                                                                    $first_sale_sql    = "SELECT MIN(created_at) as first_sale FROM invoices WHERE status IN $completed_status $customer_condition";
                                                                    $first_sale_result = $conn->query($first_sale_sql);
                                                                    $first_sale        = $first_sale_result->fetch_assoc()['first_sale'];
                                                                echo $first_sale ? date('M Y', strtotime($first_sale)) : 'N/A';
                                                                ?></p>
        </div>

        <!-- Today's Revenue -->
        <div class="p-4 bg-red-100 border-l-4 border-red-500 rounded-lg">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-lg font-semibold text-red-800">Today's Revenue</h3>
                    <p class="text-xl font-bold text-red-600">₹ <?php echo number_format($today_revenue); ?></p>
                </div>
                <span
                    class="bg-red-200 text-red-800 text-xs px-2 py-1 rounded-full"><?php echo date('D, M j'); ?></span>
            </div>
            <?php
                        $yesterday_sql = "SELECT SUM(total_amount) as revenue FROM invoices
                            WHERE status IN $completed_status AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) $customer_condition";
                        $yesterday_result  = $conn->query($yesterday_sql);
                        $yesterday_revenue = $yesterday_result->fetch_assoc()['revenue'] ?? 0;
                        $today_diff        = $today_revenue - $yesterday_revenue;
                    ?>
            <p class="text-sm mt-2<?php echo $today_diff >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                <?php
                            if ($yesterday_revenue > 0) {
                                $percent = abs(($today_diff / $yesterday_revenue) * 100);
                                echo($today_diff >= 0 ? '↑' : '↓') . ' ' . number_format($percent, 1) . '% from yesterday';
                            } else {
                                echo 'No comparison data';
                            }
                        ?>
            </p>
        </div>

        <!-- Pending Revenue -->
        <div class="p-4 bg-orange-100 border-l-4 border-orange-500 rounded-lg">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-lg font-semibold text-orange-800">Pending Revenue</h3>
                    <p class="text-xl font-bold text-orange-600">₹ <?php echo number_format($pending_revenue); ?></p>
                </div>
                <span class="bg-orange-200 text-orange-800 text-xs px-2 py-1 rounded-full">Potential</span>
            </div>
            <p class="text-sm text-gray-500 mt-2">Dispatched orders</p>
        </div>
    </div>
</div>

<!-- Order Status Summary -->
<div class="p-6 bg-white rounded-lg shadow-md mt-4">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">
        <?php echo $customer_id ? "Order Status Summary for Customer ID: $customer_id" : "Order Status Summary"; ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Total Orders -->
            <div class="p-4 bg-gray-100 border-l-4 border-gray-500 rounded-lg">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Total Orders</h3>
                        <p class="text-xl font-bold text-gray-600"><?php echo number_format($total_orders); ?></p>
                    </div>
                    <span class="bg-gray-200 text-gray-800 text-xs px-2 py-1 rounded-full">All Statuses</span>
                </div>
                <p class="text-sm text-gray-500 mt-2">Total amount: ₹ <?php echo number_format($total_amount_all); ?>
                </p>
            </div>

            <?php
            // Define status colors and labels
            $status_config = [
                'Completed'  => [
                    'label'  => 'Completed',
                    'bg'     => 'bg-green-100',
                    'border' => 'border-green-500',
                    'text'   => 'text-green-800',
                    'icon'   => '✓',
                ],
                'Dispatched' => [
                    'label'  => 'Dispatched',
                    'bg'     => 'bg-blue-100',
                    'border' => 'border-blue-500',
                    'text'   => 'text-blue-800',
                    'icon'   => '🚚',
                ],
                'Pending'    => [
                    'label'  => 'Pending',
                    'bg'     => 'bg-yellow-100',
                    'border' => 'border-yellow-500',
                    'text'   => 'text-yellow-800',
                    'icon'   => '⏳',
                ],
                'Incomplete' => [
                    'label'  => 'Incomplete',
                    'bg'     => 'bg-amber-100',
                    'border' => 'border-amber-500',
                    'text'   => 'text-amber-800',
                    'icon'   => '⚠️',
                ],
                'Cancelled'  => [
                    'label'  => 'Cancelled',
                    'bg'     => 'bg-red-100',
                    'border' => 'border-red-500',
                    'text'   => 'text-red-800',
                    'icon'   => '✗',
                ],
                'Returned'   => [
                    'label'  => 'Returned',
                    'bg'     => 'bg-purple-100',
                    'border' => 'border-purple-500',
                    'text'   => 'text-purple-800',
                    'icon'   => '↩️',
                ],
                'Delay'      => [
                    'label'  => 'Delayed',
                    'bg'     => 'bg-pink-100',
                    'border' => 'border-pink-500',
                    'text'   => 'text-pink-800',
                    'icon'   => '⏱️',
                ],
            ];

            // Get counts for all statuses
            $status_counts_sql = "SELECT
                            status,
                            COUNT(*) as count,
                            SUM(total_amount) as amount
                            FROM invoices
                            WHERE 1=1 $customer_condition
                            GROUP BY status
                            ORDER BY FIELD(status, 'Completed', 'Dispatched', 'Processing', 'Pending', 'Incomplete', 'Delay', 'Cancelled', 'Returned')";
            $status_counts_result = $conn->query($status_counts_sql);
            $status_counts        = [];
            $total_orders         = 0;
            $total_amount_all     = 0;

            while ($row = $status_counts_result->fetch_assoc()) {
                $status_counts[$row['status']] = [
                    'count'  => $row['count'],
                    'amount' => $row['amount'],
                ];
                $total_orders += $row['count'];
                $total_amount_all += $row['amount'];
            }

       // Display each status card
        foreach ($status_config as $status => $config) {
            $data = $status_counts[$status] ?? ['count' => 0, 'amount' => 0];
            $percentage = $total_orders > 0 ? round(($data['count'] / $total_orders) * 100, 1) : 0;
        ?>
        <div class="p-4 <?php echo $config['bg']; ?> border-l-4 <?php echo $config['border']; ?> rounded-lg hover:shadow-md transition-shadow">
            <div class="flex justify-between items-start">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-lg"><?php echo $config['icon']; ?></span>
                        <h3 class="text-lg font-semibold <?php echo $config['text']; ?>"><?php echo $config['label']; ?></h3>
                    </div>
                    <p class="text-xl font-bold <?php echo $config['text']; ?> ml-6"><?php echo number_format($data['count']); ?></p>
                </div>
                <span class="<?php echo str_replace('100', '200', $config['bg']); ?> <?php echo $config['text']; ?> text-xs px-2 py-1 rounded-full">
                    <?php echo $percentage; ?>%
                </span>
            </div>
            <div class="mt-2 ml-6">
                <p class="text-sm text-gray-600">Amount: ₹ <?php echo number_format($data['amount']); ?></p>
            </div>
        </div>
        <?php } ?>
    </div>
</div>
<!-- Main Filter Section -->
<div class="bg-white p-4 rounded-md shadow-md mt-4">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Filter Revenue Data</h2>

    <form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Time Period Selector -->
        <div>
            <label class="block font-medium mb-1">View By</label>
            <select name="time_period" class="w-full p-2 border rounded-md" onchange="updateFilterFields(this.value)">
                <option value="day">Daily</option>
                <option value="month">Monthly</option>
                <option value="year">Yearly</option>
                <option value="custom">Custom Range</option>
            </select>
        </div>

        <!-- Day Selector -->
        <div id="day_field">
            <label class="block font-medium mb-1">Select Date</label>
            <input type="date" name="day_date" class="w-full p-2 border rounded-md"
                value="<?php echo date('Y-m-d'); ?>">
        </div>

        <!-- Month Selector -->
        <div id="month_field" class="hidden">
            <label class="block font-medium mb-1">Select Month</label>
            <input type="month" name="month_date" class="w-full p-2 border rounded-md"
                value="<?php echo date('Y-m'); ?>">
        </div>

        <!-- Year Selector -->
        <div id="year_field" class="hidden">
            <label class="block font-medium mb-1">Select Year</label>
            <select name="year_date" class="w-full p-2 border rounded-md">
                <?php
                            // Get available years from database
                            $years_sql    = "SELECT DISTINCT YEAR(created_at) as year FROM invoices ORDER BY year DESC";
                            $years_result = $conn->query($years_sql);

                            while ($year = $years_result->fetch_assoc()) {
                                echo "<option value='{$year['year']}'" . ($year['year'] == date('Y') ? ' selected' : '') . ">{$year['year']}</option>";
                            }
                        ?>
            </select>
        </div>

        <!-- Custom Date Range -->
        <div id="custom_range" class="hidden md:col-span-2">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-medium mb-1">From Date</label>
                    <input type="date" name="from_date" class="w-full p-2 border rounded-md">
                </div>
                <div>
                    <label class="block font-medium mb-1">To Date</label>
                    <input type="date" name="to_date" class="w-full p-2 border rounded-md">
                </div>
            </div>
        </div>

        <div class="md:col-span-4">
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                Filter Revenue
            </button>
        </div>
    </form>
</div>

<?php
            // Process filters and show results if submitted
            if (isset($_GET['time_period'])) {
                $time_period    = $_GET['time_period'];
                $date_condition = "";
                $title          = "";

                switch ($time_period) {
                    case 'day':
                        $day_date       = $_GET['day_date'];
                        $date_condition = "DATE(created_at) = '$day_date'";
                        $title          = "Daily Revenue for " . date('F j, Y', strtotime($day_date));
                        break;

                    case 'month':
                        $month_date     = $_GET['month_date'];
                        $date_condition = "DATE_FORMAT(created_at, '%Y-%m') = '$month_date'";
                        $title          = "Monthly Revenue for " . date('F Y', strtotime($month_date . '-01'));
                        break;

                    case 'year':
                        $year_date      = $_GET['year_date'];
                        $date_condition = "YEAR(created_at) = '$year_date'";
                        $title          = "Yearly Revenue for " . $year_date;
                        break;

                    case 'custom':
                        $from_date      = $_GET['from_date'];
                        $to_date        = $_GET['to_date'] ?? $from_date;
                        $date_condition = "DATE(created_at) BETWEEN '$from_date' AND '$to_date'";
                        $title          = "Revenue from " . date('M j, Y', strtotime($from_date)) . " to " . date('M j, Y', strtotime($to_date));
                        break;
                }

                // Get total revenue for the filtered period
                $filtered_revenue_sql = "SELECT SUM(total_amount) as filtered_revenue FROM invoices
                                    WHERE status in ('Completed','Dispatched')  AND $date_condition";
                $filtered_revenue_result = $conn->query($filtered_revenue_sql);
                $filtered_revenue        = $filtered_revenue_result->fetch_assoc()['filtered_revenue'] ?? 0;

                // Get detailed revenue data with pagination
                $revenue_data_sql = "SELECT
                                " . ($time_period == 'year' ? "MONTH(created_at) as period" : "DATE(created_at) as period") . ",
                                SUM(total_amount) as daily_revenue,
                                COUNT(*) as order_count
                                FROM invoices
                                WHERE status in ('Completed','Dispatched') AND $date_condition
                                GROUP BY period
                                ORDER BY period " . ($time_period == 'day' || $time_period == 'month' ? 'ASC' : 'DESC') . "
                                LIMIT $limit OFFSET $offset";

                $revenue_data_result = $conn->query($revenue_data_sql);

                // Get total count for pagination
                $count_sql = "SELECT COUNT(DISTINCT " . ($time_period == 'year' ? "MONTH(created_at)" : "DATE(created_at)") . ") as total
                         FROM invoices WHERE status in ('Completed','Dispatched') AND $date_condition";
                $count_result  = $conn->query($count_sql);
                $total_records = $count_result->fetch_assoc()['total'];
                $total_pages   = ceil($total_records / $limit);
            ?>

<!-- Filtered Results -->
<div class="bg-white p-4 rounded-md shadow-md mt-4">
    <h2 class="text-xl font-bold text-gray-800 mb-4"><?php echo $title; ?></h2>
    <div class="mb-4 p-4 bg-gray-100 rounded-lg">
        <p class="text-lg font-bold">Total Revenue: ₹ <?php echo number_format($filtered_revenue, 2); ?></p>
        <p class="text-sm text-gray-600">Showing
            <?php echo $offset + 1; ?>-<?php echo min($offset + $limit, $total_records); ?>
            of<?php echo $total_records; ?> records</p>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full border-collapse border border-gray-300 text-sm">
            <thead class="bg-gray-200">
                <tr class="whitespace-nowrap text-left">
                    <th class="p-2 border">
                        <?php
                                    echo $time_period == 'day' ? 'Date' :
                                        ($time_period == 'month' ? 'Date' :
                                            ($time_period == 'year' ? 'Month' : 'Date'));
                                    ?>
                    </th>
                    <th class="p-2 border">Orders</th>
                    <th class="p-2 border">Revenue</th>
                    <th class="p-2 border">Avg/Order</th>
                </tr>
            </thead>
            <tbody>
                <?php
                            if ($revenue_data_result->num_rows > 0) {
                                    while ($row = $revenue_data_result->fetch_assoc()) {
                                        echo "<tr class='text-left bg-gray-50 hover:bg-gray-100'>";

                                        // Format period column based on view
                                        if ($time_period == 'day' || $time_period == 'month') {
                                            echo "<td class='p-2 border'>" . date('M j, Y', strtotime($row['period'])) . "</td>";
                                        } elseif ($time_period == 'year') {
                                            echo "<td class='p-2 border'>" . date('F', mktime(0, 0, 0, $row['period'], 1)) . "</td>";
                                        } else {
                                            echo "<td class='p-2 border'>" . date('M j, Y', strtotime($row['period'])) . "</td>";
                                        }

                                        echo "<td class='p-2 border'>" . $row['order_count'] . "</td>";
                                        echo "<td class='p-2 border text-green-600 font-bold'>₹ " . number_format($row['daily_revenue'], 2) . "</td>";
                                        echo "<td class='p-2 border'>₹ " . number_format($row['daily_revenue'] / $row['order_count'], 2) . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4' class='p-4 text-center text-gray-500'>No revenue data found for selected filters</td></tr>";
                                }
                            ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="flex justify-between items-center mt-4">
            <div>
                <?php if ($page > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"
                    class="bg-gray-200 px-3 py-1 rounded-md hover:bg-gray-300">
                    Previous
                </a>
                <?php endif; ?>
            </div>
            <div class="text-sm text-gray-600">
                Page <?php echo $page; ?> of<?php echo $total_pages; ?>
            </div>
            <div>
                <?php if ($page < $total_pages): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>"
                    class="bg-gray-200 px-3 py-1 rounded-md hover:bg-gray-300">
                    Next
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php }?>

<!-- Employee Revenue Section -->
<div class="bg-white p-4 rounded-md shadow-md mt-4">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Employee Revenue</h2>

    <!-- Employee Daily Revenue -->
    <div class="mb-8">
        <h3 class="text-lg font-semibold mb-2">Daily Employee Revenue</h3>
        <form method="GET" class="mb-4 flex flex-wrap gap-4 items-center">
            <input type="date" name="emp_day_date"
                value="<?php echo isset($_GET['emp_day_date']) ? $_GET['emp_day_date'] : date('Y-m-d'); ?>"
                class="p-2 border rounded-md">

            <select name="emp_day_employee" class="p-2 border rounded-md">
                <option value="">All Employees</option>
                <?php
                    $emp_list_sql    = "SELECT DISTINCT employee_name FROM invoices WHERE status in ('Completed','Dispatched','Returned') ORDER BY employee_name";
                    $emp_list_result = $conn->query($emp_list_sql);
                    while ($emp = $emp_list_result->fetch_assoc()) {
                        $selected = (isset($_GET['emp_day_employee'])) && $_GET['emp_day_employee'] == $emp['employee_name'] ? 'selected' : '';
                        echo "<option value='{$emp['employee_name']}' $selected>{$emp['employee_name']}</option>";
                    }
                ?>
            </select>

            <button type="submit" class="p-2 bg-blue-500 text-white rounded-md">Filter</button>
        </form>

        <?php if (isset($_GET['emp_day_date'])): ?>
        <?php
    $emp_day_date     = $_GET['emp_day_date'];
    $emp_day_employee = isset($_GET['emp_day_employee']) ? $_GET['emp_day_employee'] : '';

    $emp_day_condition = "DATE(created_at) = '$emp_day_date'";
    if (! empty($emp_day_employee)) {
        $emp_day_condition .= " AND employee_name = '$emp_day_employee'";
    }

    // Get totals for this filter - EXCLUDE RETURNED ORDERS FROM TOTAL REVENUE
    $emp_day_total_sql = "SELECT
                            SUM(CASE WHEN status IN ('Completed','Dispatched') THEN total_amount ELSE 0 END) as total,
                            SUM(CASE WHEN is_repeated_order = 'yes' AND status IN ('Completed','Dispatched') THEN total_amount ELSE 0 END) as repeated_revenue,
                            SUM(CASE WHEN is_repeated_order = 'no' AND status IN ('Completed','Dispatched') THEN total_amount ELSE 0 END) as new_revenue,
                            COUNT(*) as total_orders,
                            SUM(CASE WHEN is_repeated_order = 'yes' THEN 1 ELSE 0 END) as repeated_orders,
                            SUM(CASE WHEN is_repeated_order = 'no' THEN 1 ELSE 0 END) as new_orders,
                            SUM(CASE WHEN status = 'Returned' THEN total_amount ELSE 0 END) as returned_amount,
                            SUM(CASE WHEN status = 'Returned' THEN 1 ELSE 0 END) as returned_orders
                            FROM invoices
                            WHERE status in ('Completed','Dispatched','Returned') AND $emp_day_condition";
    $emp_day_total_result = $conn->query($emp_day_total_sql);
    $emp_day_total_data   = $emp_day_total_result->fetch_assoc();

    $emp_day_total    = $emp_day_total_data['total'] ?? 0;
    $repeated_revenue = $emp_day_total_data['repeated_revenue'] ?? 0;
    $new_revenue      = $emp_day_total_data['new_revenue'] ?? 0;
    $total_orders     = $emp_day_total_data['total_orders'] ?? 0;
    $repeated_orders  = $emp_day_total_data['repeated_orders'] ?? 0;
    $new_orders       = $emp_day_total_data['new_orders'] ?? 0;
    $returned_amount  = $emp_day_total_data['returned_amount'] ?? 0;
    $returned_orders  = $emp_day_total_data['returned_orders'] ?? 0;

    // Get employee data with pagination - EXCLUDE RETURNED ORDERS FROM REVENUE CALCULATIONS
    $emp_day_sql = "SELECT employee_name,
                                SUM(CASE WHEN status IN ('Completed','Dispatched') THEN total_amount ELSE 0 END) as daily_revenue,
                                COUNT(*) as order_count,
                                SUM(CASE WHEN is_repeated_order = 'yes' AND status IN ('Completed','Dispatched') THEN total_amount ELSE 0 END) as repeated_revenue,
                                SUM(CASE WHEN is_repeated_order = 'no' AND status IN ('Completed','Dispatched') THEN total_amount ELSE 0 END) as new_revenue,
                                SUM(CASE WHEN is_repeated_order = 'yes' THEN 1 ELSE 0 END) as repeated_orders,
                                SUM(CASE WHEN is_repeated_order = 'no' THEN 1 ELSE 0 END) as new_orders,
                                SUM(CASE WHEN status = 'Returned' THEN total_amount ELSE 0 END) as returned_amount,
                                SUM(CASE WHEN status = 'Returned' THEN 1 ELSE 0 END) as returned_orders
                                FROM invoices
                                WHERE status in ('Completed','Dispatched','Returned') AND $emp_day_condition
                                GROUP BY employee_name
                                ORDER BY daily_revenue DESC
                                LIMIT $limit OFFSET $offset";

    $emp_day_result = $conn->query($emp_day_sql);

    // Get count for pagination
    $emp_day_count_sql     = "SELECT COUNT(DISTINCT employee_name) as total FROM invoices WHERE status in ('Completed','Dispatched','Returned') AND $emp_day_condition";
    $emp_day_count_result  = $conn->query($emp_day_count_sql);
    $emp_day_total_records = $emp_day_count_result->fetch_assoc()['total'];
    $emp_day_total_pages   = ceil($emp_day_total_records / $limit);
?>

        <div class="mb-4 p-4 bg-gray-100 rounded-lg">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="p-3 bg-white rounded shadow">
                    <h4 class="font-bold text-gray-700">Total Revenue</h4>
                    <p class="text-xl text-green-600">₹ <?php echo number_format($emp_day_total, 2); ?></p>
                    <p class="text-sm text-gray-500"><?php echo $total_orders; ?> orders</p>
                </div>
                <div class="p-3 bg-white rounded shadow">
                    <h4 class="font-bold text-gray-700">New Customer Revenue</h4>
                    <p class="text-xl text-blue-600">₹ <?php echo number_format($new_revenue, 2); ?></p>
                    <p class="text-sm text-gray-500"><?php echo $new_orders; ?> orders
                        (<?php echo $total_orders > 0 ? round(($new_orders / $total_orders) * 100, 1) : 0; ?>%)</p>
                </div>
                <div class="p-3 bg-white rounded shadow">
                    <h4 class="font-bold text-gray-700">Repeat Customer Revenue</h4>
                    <p class="text-xl text-purple-600">₹ <?php echo number_format($repeated_revenue, 2); ?></p>
                    <p class="text-sm text-gray-500"><?php echo $repeated_orders; ?> orders
                        (<?php echo $total_orders > 0 ? round(($repeated_orders / $total_orders) * 100, 1) : 0; ?>%)</p>
                </div>
                <div class="p-3 bg-white rounded shadow">
                    <h4 class="font-bold text-gray-700">Returned Orders</h4>
                    <p class="text-xl text-red-600">₹ <?php echo number_format($returned_amount, 2); ?></p>
                    <p class="text-sm text-gray-500"><?php echo $returned_orders; ?> orders
                        (<?php echo $total_orders > 0 ? round(($returned_orders / $total_orders) * 100, 1) : 0; ?>%)</p>
                </div>
            </div>
        </div>

        <div class="mb-2 p-2 bg-gray-100 rounded">
            <p class="text-sm text-gray-600">Showing
                <?php echo $offset + 1; ?>-<?php echo min($offset + $limit, $emp_day_total_records); ?> of
                <?php echo $emp_day_total_records; ?> employees</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-gray-300 text-sm">
                <thead class="bg-gray-200">
                    <tr class="whitespace-nowrap text-left">
                        <th class="p-2 border">Employee</th>
                        <th class="p-2 border">Total Orders</th>
                        <th class="p-2 border">New Orders</th>
                        <th class="p-2 border">Repeat Orders</th>
                        <th class="p-2 border">Returned Orders</th>
                        <th class="p-2 border">Total Revenue</th>
                        <th class="p-2 border">New Revenue</th>
                        <th class="p-2 border">Repeat Revenue</th>
                        <th class="p-2 border">Returned Amount</th>
                        <th class="p-2 border">Avg/Order</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($emp_day_result->num_rows > 0): ?>
                    <?php while ($row = $emp_day_result->fetch_assoc()): ?>
                    <tr class="text-left bg-gray-50 hover:bg-gray-100">
                        <td class="p-2 border"><?php echo htmlspecialchars($row['employee_name']); ?></td>
                        <td class="p-2 border"><?php echo $row['order_count']; ?></td>
                        <td class="p-2 border"><?php echo $row['new_orders']; ?></td>
                        <td class="p-2 border"><?php echo $row['repeated_orders']; ?></td>
                        <td class="p-2 border text-red-500"><?php echo $row['returned_orders']; ?></td>
                        <td class="p-2 border text-green-600 font-bold">₹
                            <?php echo number_format($row['daily_revenue'], 2); ?></td>
                        <td class="p-2 border text-blue-600">₹
                            <?php echo number_format($row['new_revenue'], 2); ?></td>
                        <td class="p-2 border text-purple-600">₹
                            <?php echo number_format($row['repeated_revenue'], 2); ?></td>
                        <td class="p-2 border text-red-600">₹
                            <?php echo number_format($row['returned_amount'], 2); ?></td>
                        <td class="p-2 border">₹
                            <?php echo number_format($row['daily_revenue'] / $row['order_count'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="10" class="p-4 text-center text-gray-500">No employee data found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($emp_day_total_pages > 1): ?>
            <div class="flex justify-between items-center mt-4">
                <div>
                    <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"
                        class="bg-gray-200 px-3 py-1 rounded-md hover:bg-gray-300">
                        Previous
                    </a>
                    <?php endif; ?>
                </div>
                <div class="text-sm text-gray-600">
                    Page <?php echo $page; ?> of<?php echo $emp_day_total_pages; ?>
                </div>
                <div>
                    <?php if ($page < $emp_day_total_pages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>"
                        class="bg-gray-200 px-3 py-1 rounded-md hover:bg-gray-300">
                        Next
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Employee Monthly Revenue -->
    <div>
        <h3 class="text-lg font-semibold mb-2">Monthly Employee Revenue</h3>
        <form method="GET" class="mb-4 flex flex-wrap gap-4 items-center">
            <input type="month" name="emp_month_date"
                value="<?php echo isset($_GET['emp_month_date']) ? $_GET['emp_month_date'] : date('Y-m'); ?>"
                class="p-2 border rounded-md">

            <select name="emp_month_employee" class="p-2 border rounded-md">
                <option value="">All Employees</option>
                <?php
                    $emp_list_result->data_seek(0); // Reset pointer
                    while ($emp = $emp_list_result->fetch_assoc()) {
                        $selected = (isset($_GET['emp_month_employee'])) && $_GET['emp_month_employee'] == $emp['employee_name'] ? 'selected' : '';
                        echo "<option value='{$emp['employee_name']}' $selected>{$emp['employee_name']}</option>";
                    }
                ?>
            </select>

            <button type="submit" class="p-2 bg-blue-500 text-white rounded-md">Filter</button>
        </form>

        <?php if (isset($_GET['emp_month_date'])): ?>
        <?php
    $emp_month_date     = $_GET['emp_month_date'];
    $emp_month_employee = isset($_GET['emp_month_employee']) ? $_GET['emp_month_employee'] : '';

    $emp_month_condition = "DATE_FORMAT(created_at, '%Y-%m') = '$emp_month_date'";
    if (! empty($emp_month_employee)) {
        $emp_month_condition .= " AND employee_name = '$emp_month_employee'";
    }

    // Get totals for this filter - EXCLUDE RETURNED ORDERS FROM TOTAL REVENUE
    $emp_month_total_sql = "SELECT
                            SUM(CASE WHEN status IN ('Completed','Dispatched') THEN total_amount ELSE 0 END) as total,
                            SUM(CASE WHEN is_repeated_order = 'yes' AND status IN ('Completed','Dispatched') THEN total_amount ELSE 0 END) as repeated_revenue,
                            SUM(CASE WHEN is_repeated_order = 'no' AND status IN ('Completed','Dispatched') THEN total_amount ELSE 0 END) as new_revenue,
                            COUNT(*) as total_orders,
                            SUM(CASE WHEN is_repeated_order = 'yes' THEN 1 ELSE 0 END) as repeated_orders,
                            SUM(CASE WHEN is_repeated_order = 'no' THEN 1 ELSE 0 END) as new_orders,
                            SUM(CASE WHEN status = 'Returned' THEN total_amount ELSE 0 END) as returned_amount,
                            SUM(CASE WHEN status = 'Returned' THEN 1 ELSE 0 END) as returned_orders
                            FROM invoices
                            WHERE status in ('Completed','Dispatched','Returned') AND $emp_month_condition";
    $emp_month_total_result = $conn->query($emp_month_total_sql);
    $emp_month_total_data   = $emp_month_total_result->fetch_assoc();

    $emp_month_total  = $emp_month_total_data['total'] ?? 0;
    $repeated_revenue = $emp_month_total_data['repeated_revenue'] ?? 0;
    $new_revenue      = $emp_month_total_data['new_revenue'] ?? 0;
    $total_orders     = $emp_month_total_data['total_orders'] ?? 0;
    $repeated_orders  = $emp_month_total_data['repeated_orders'] ?? 0;
    $new_orders       = $emp_month_total_data['new_orders'] ?? 0;
    $returned_amount  = $emp_month_total_data['returned_amount'] ?? 0;
    $returned_orders  = $emp_month_total_data['returned_orders'] ?? 0;

    // Get employee data with pagination - EXCLUDE RETURNED ORDERS FROM REVENUE CALCULATIONS
    $emp_month_sql = "SELECT employee_name,
                                SUM(CASE WHEN status IN ('Completed','Dispatched') THEN total_amount ELSE 0 END) as monthly_revenue,
                                COUNT(*) as order_count,
                                SUM(CASE WHEN is_repeated_order = 'yes' AND status IN ('Completed','Dispatched') THEN total_amount ELSE 0 END) as repeated_revenue,
                                SUM(CASE WHEN is_repeated_order = 'no' AND status IN ('Completed','Dispatched') THEN total_amount ELSE 0 END) as new_revenue,
                                SUM(CASE WHEN is_repeated_order = 'yes' THEN 1 ELSE 0 END) as repeated_orders,
                                SUM(CASE WHEN is_repeated_order = 'no' THEN 1 ELSE 0 END) as new_orders,
                                SUM(CASE WHEN status = 'Returned' THEN total_amount ELSE 0 END) as returned_amount,
                                SUM(CASE WHEN status = 'Returned' THEN 1 ELSE 0 END) as returned_orders
                                FROM invoices
                                WHERE status in ('Completed','Dispatched','Returned') AND $emp_month_condition
                                GROUP BY employee_name
                                ORDER BY monthly_revenue DESC
                                LIMIT $limit OFFSET $offset";

    $emp_month_result = $conn->query($emp_month_sql);

    // Get count for pagination
    $emp_month_count_sql     = "SELECT COUNT(DISTINCT employee_name) as total FROM invoices WHERE status in ('Completed','Dispatched') AND $emp_month_condition";
    $emp_month_count_result  = $conn->query($emp_month_count_sql);
    $emp_month_total_records = $emp_month_count_result->fetch_assoc()['total'];
    $emp_month_total_pages   = ceil($emp_month_total_records / $limit);
?>

        <div class="mb-4 p-4 bg-gray-100 rounded-lg">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="p-3 bg-white rounded shadow">
                    <h4 class="font-bold text-gray-700">Total Revenue</h4>
                    <p class="text-xl text-green-600">₹ <?php echo number_format($emp_month_total, 2); ?></p>
                    <p class="text-sm text-gray-500"><?php echo $total_orders; ?> orders</p>
                </div>
                <div class="p-3 bg-white rounded shadow">
                    <h4 class="font-bold text-gray-700">New Customer Revenue</h4>
                    <p class="text-xl text-blue-600">₹ <?php echo number_format($new_revenue, 2); ?></p>
                    <p class="text-sm text-gray-500"><?php echo $new_orders; ?> orders
                        (<?php echo $total_orders > 0 ? round(($new_orders / $total_orders) * 100, 1) : 0; ?>%)</p>
                </div>
                <div class="p-3 bg-white rounded shadow">
                    <h4 class="font-bold text-gray-700">Repeat Customer Revenue</h4>
                    <p class="text-xl text-purple-600">₹ <?php echo number_format($repeated_revenue, 2); ?></p>
                    <p class="text-sm text-gray-500"><?php echo $repeated_orders; ?> orders
                        (<?php echo $total_orders > 0 ? round(($repeated_orders / $total_orders) * 100, 1) : 0; ?>%)</p>
                </div>
                <div class="p-3 bg-white rounded shadow">
                    <h4 class="font-bold text-gray-700">Returned Orders</h4>
                    <p class="text-xl text-red-600">₹ <?php echo number_format($returned_amount, 2); ?></p>
                    <p class="text-sm text-gray-500"><?php echo $returned_orders; ?> orders
                        (<?php echo $total_orders > 0 ? round(($returned_orders / $total_orders) * 100, 1) : 0; ?>%)</p>
                </div>
            </div>
        </div>

        <div class="mb-2 p-2 bg-gray-100 rounded">
            <p class="text-sm text-gray-600">Showing
                <?php echo $offset + 1; ?>-<?php echo min($offset + $limit, $emp_month_total_records); ?> of
                <?php echo $emp_month_total_records; ?> employees</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-gray-300 text-sm">
                <thead class="bg-gray-200">
                    <tr class="whitespace-nowrap text-left">
                        <th class="p-2 border">Employee</th>
                        <th class="p-2 border">Total Orders</th>
                        <th class="p-2 border">New Orders</th>
                        <th class="p-2 border">Repeat Orders</th>
                        <th class="p-2 border">Returned Orders</th>
                        <th class="p-2 border">Total Revenue</th>
                        <th class="p-2 border">New Revenue</th>
                        <th class="p-2 border">Repeat Revenue</th>
                        <th class="p-2 border">Returned Amount</th>
                        <th class="p-2 border">Avg/Order</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($emp_month_result->num_rows > 0): ?>
                    <?php while ($row = $emp_month_result->fetch_assoc()): ?>
                    <tr class="text-left bg-gray-50 hover:bg-gray-100">
                        <td class="p-2 border"><?php echo htmlspecialchars($row['employee_name']); ?></td>
                        <td class="p-2 border"><?php echo $row['order_count']; ?></td>
                        <td class="p-2 border"><?php echo $row['new_orders']; ?></td>
                        <td class="p-2 border"><?php echo $row['repeated_orders']; ?></td>
                        <td class="p-2 border text-red-500"><?php echo $row['returned_orders']; ?></td>
                        <td class="p-2 border text-green-600 font-bold">₹
                            <?php echo number_format($row['monthly_revenue'], 2); ?></td>
                        <td class="p-2 border text-blue-600">₹
                            <?php echo number_format($row['new_revenue'], 2); ?></td>
                        <td class="p-2 border text-purple-600">₹
                            <?php echo number_format($row['repeated_revenue'], 2); ?></td>
                        <td class="p-2 border text-red-600">₹
                            <?php echo number_format($row['returned_amount'], 2); ?></td>
                        <td class="p-2 border">₹
                            <?php echo number_format($row['monthly_revenue'] / $row['order_count'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="10" class="p-4 text-center text-gray-500">No employee data found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($emp_month_total_pages > 1): ?>
            <div class="flex justify-between items-center mt-4">
                <div>
                    <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"
                        class="bg-gray-200 px-3 py-1 rounded-md hover:bg-gray-300">
                        Previous
                    </a>
                    <?php endif; ?>
                </div>
                <div class="text-sm text-gray-600">
                    Page <?php echo $page; ?> of<?php echo $emp_month_total_pages; ?>
                </div>
                <div>
                    <?php if ($page < $emp_month_total_pages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>"
                        class="bg-gray-200 px-3 py-1 rounded-md hover:bg-gray-300">
                        Next
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function updateFilterFields(period) {
    // Hide all filter fields first
    document.getElementById('day_field').classList.add('hidden');
    document.getElementById('month_field').classList.add('hidden');
    document.getElementById('year_field').classList.add('hidden');
    document.getElementById('custom_range').classList.add('hidden');

    // Show the selected one
    if (period === 'day') {
        document.getElementById('day_field').classList.remove('hidden');
    } else if (period === 'month') {
        document.getElementById('month_field').classList.remove('hidden');
    } else if (period === 'year') {
        document.getElementById('year_field').classList.remove('hidden');
    } else if (period === 'custom') {
        document.getElementById('custom_range').classList.remove('hidden');
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set default to day view
    updateFilterFields('day');
});
</script>
</main>
</body>

</html>