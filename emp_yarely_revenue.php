<?php 
include 'header.php'; 
include 'head.php'; 
include 'db.php'; // Database Connection

$current_year = date('Y'); // Ensure current year is set
?>

<body class="bg-gray-100 flex">
    <?php include 'sidebar.php'; ?>
    <main class="flex-1 pl-56 m-4">

        <h2 class="text-2xl font-bold text-[var(--primary-color)] mb-4 text-center">Employee Yearly Revenue Report</h2>

        <!-- Employee-wise Yearly Revenue -->
        <div class="bg-white p-4 rounded-md shadow-md mt-4">
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Employee-wise Yearly Revenue</h3>

            <!-- Year Filter -->
            <form method="GET" class="mb-4 flex flex-wrap items-center gap-4">
                <label for="year_filter" class="font-semibold">Select Year:</label>
                <select name="year_filter" id="year_filter" class="border p-2 rounded-md">
                    <?php 
                    for ($y = $current_year; $y >= ($current_year - 5); $y--) {
                        $selected = (isset($_GET['year_filter']) && $_GET['year_filter'] == $y) ? "selected" : "";
                        echo "<option value='$y' $selected>$y</option>";
                    }
                    ?>
                </select>

                <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">Filter</button>
                <!-- Export to Excel Button -->
                <a href="export_yareemp.php?year_filter=<?= isset($_GET['year_filter']) ? $_GET['year_filter'] : $current_year; ?>" class="bg-yellow-500 text-white px-4 py-2 rounded-md hover:bg-yellow-600">
                    Export to Excel
                </a>
            </form>

            <!-- Employee-wise Yearly Revenue Table -->
            <table class="w-full border-collapse border border-gray-300 text-sm">
                <thead class="bg-gray-200">
                    <tr class="whitespace-nowrap text-left">
                        <th class="p-2 border">Employee Name</th>
                        <th class="p-2 border">Total Yearly Revenue (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Get selected year from filter
                    $selected_yearly = isset($_GET['year_filter']) ? $_GET['year_filter'] : $current_year;

                    // Query to fetch employee-wise yearly revenue
                    $emp_yearly_sql = "SELECT employee_name, 
                                        COALESCE(SUM(total_amount), 0) AS total_yearly_revenue
                                        FROM invoices 
                                        WHERE status='Completed' 
                                        AND YEAR(created_at) = '$selected_yearly'
                                        GROUP BY employee_name 
                                        ORDER BY total_yearly_revenue DESC";

                    $emp_yearly_result = $conn->query($emp_yearly_sql);

                    if (!$emp_yearly_result) {
                        die("Query Error: " . mysqli_error($conn)); // Debugging line
                    }

                    if ($emp_yearly_result->num_rows > 0):
                        while ($row = $emp_yearly_result->fetch_assoc()): ?>
                        <tr class="text-left bg-gray-50 hover:bg-gray-100">
                            <td class="p-2 border"> <?php echo htmlspecialchars($row['employee_name']); ?> </td>
                            <td class="p-2 border text-purple-600 font-bold">₹ <?php echo number_format($row['total_yearly_revenue']); ?> </td>
                        </tr>
                        <?php endwhile;
                    else: ?>
                        <tr>
                            <td class="p-2 border text-center text-gray-500" colspan="2">No data found for selected year.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>
</body>
</html>
