<?php

    // Handle all POST actions before any output
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_invoice'])) {
        include 'db.php'; // Include DB connection here

        if (isset($_POST['id'])) {
            $id = $_POST['id'];

            // Start transaction for delete
            $conn->begin_transaction();

            try {
                // First delete items
                $deleteItems = "DELETE FROM invoice_items WHERE invoice_id = ?";
                $stmt        = $conn->prepare($deleteItems);
                $stmt->bind_param("i", $id);
                $stmt->execute();

                // Then delete invoice
                $deleteSql = "DELETE FROM invoices WHERE id = ?";
                $stmt      = $conn->prepare($deleteSql);
                $stmt->bind_param("i", $id);

                if ($stmt->execute()) {
                    $conn->commit();
                    $_SESSION['success'] = 'Invoice deleted successfully!';
                    header("Location: orders.php");
                    exit();
                } else {
                    throw new Exception($stmt->error);
                }
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['error'] = 'Error deleting invoice: ' . $e->getMessage();
                header("Location: orders.php");
                exit();
            }
        }
    }

    // Now include other files
    include 'db.php';
    include 'header.php';
    include 'head.php';
?>

<body class="bg-gray-100 flex">
    <?php include 'sidebar.php'; ?>
    <main class="flex-1 pl-56 m-4">
        <!-- Message Container -->
        <div id="message-container" class="fixed top-20 right-4 z-50">
            <?php if (isset($_SESSION['success'])): ?>
            <script>
            showMessage('<?php echo addslashes($_SESSION['success']); ?>', 'success');
            </script>
            <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
            <script>
            showMessage('<?php echo addslashes($_SESSION['error']); ?>', 'error');
            </script>
            <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
        </div>

        <h2 class="text-2xl font-bold text-[var(--primary-color)] mb-4 text-center">Orders</h2>

        <?php
            // Search and pagination logic
            $limit         = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 10;
            $page          = isset($_GET['page']) ? (int) $_GET['page'] : 1;
            $start         = ($page - 1) * $limit;
            $search        = isset($_GET['search']) ? trim($_GET['search']) : '';
            $status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
            $start_date    = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
            $end_date      = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

            $role = $_SESSION['role'] ?? null;
            $user = $_SESSION['user'] ?? null;

            // Base query
            $sql         = "SELECT * FROM invoices ";
            $conditions  = [];
            $paramTypes  = '';
            $paramValues = [];

            if ($role === "Employee") {
                $conditions[] = "employee_name = ?";
                $paramTypes .= 's';
                $paramValues[] = $user;
            }

            if (! empty($search)) {
                $conditions[] = "(full_name LIKE ? OR mobile LIKE ? OR mobile2 LIKE ? OR barcode_number LIKE ? OR customer_id LIKE ?)";
                $paramTypes .= 'sssss'; // Added one more 's' for mobile2
                $paramValues[] = "%$search%";
                $paramValues[] = "%$search%";
                $paramValues[] = "%$search%"; // Added for mobile2
                $paramValues[] = "%$search%";
                $paramValues[] = "%$search%";
            }
            if (! empty($status_filter)) {
                $conditions[] = "status = ?";
                $paramTypes .= 's';
                $paramValues[] = $status_filter;
            }

            if (! empty($start_date) && ! empty($end_date)) {
                $conditions[] = "DATE(created_at) BETWEEN ? AND ?";
                $paramTypes .= 'ss';
                $paramValues[] = $start_date;
                $paramValues[] = $end_date;
            }

            if (! empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }

            $sql .= " ORDER BY created_at DESC LIMIT ?, ?";
            $paramTypes .= 'ii';
            $paramValues[] = $start;
            $paramValues[] = $limit;

            $stmt = $conn->prepare($sql);
            if ($stmt) {
                if (! empty($paramTypes)) {
                    $stmt->bind_param($paramTypes, ...$paramValues);
                }
                $stmt->execute();
                $result = $stmt->get_result();
            }

            // Get total count
            $totalSql = "SELECT COUNT(*) as total FROM invoices";
            if (! empty($conditions)) {
                $totalSql .= " WHERE " . implode(" AND ", $conditions);
            }

            $totalStmt = $conn->prepare($totalSql);
            if ($totalStmt) {
                $countParamValues = array_slice($paramValues, 0, count($paramValues) - 2);
                $countParamTypes  = substr($paramTypes, 0, -2);

                if (! empty($countParamTypes)) {
                    $totalStmt->bind_param($countParamTypes, ...$countParamValues);
                }
                $totalStmt->execute();
                $totalResult = $totalStmt->get_result();
                $totalRow    = $totalResult->fetch_assoc();
                $total       = $totalRow['total'];
                $pages       = ($total > 0) ? ceil($total / $limit) : 1;
            }

            // Fetch all products for dropdowns
            $productsResult = $conn->query("SELECT id, product_name, price FROM products");
            $products       = [];
            while ($productRow = $productsResult->fetch_assoc()) {
                $products[] = $productRow;
            }
        ?>

        <!-- Search Form -->
        <form method="GET" class="mb-4 flex">
            <input type="text" name="search" placeholder="Search by Name, Mobile, Mobile2, Barcode or Customer Id ..."
                class="w-full p-2 ml-2 border border-gray-300 rounded-l-md"
                value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="bg-[var(--primary-color)] text-white px-2 py-2 rounded-r-md">
                Search
            </button>
        </form>

        <!-- Records per page selector -->
        <div class="mb-4 ml-4">
            <form method="GET" class="flex items-center">
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                <input type="hidden" name="status_filter" value="<?php echo htmlspecialchars($status_filter); ?>">
                <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">

                <label for="per_page" class="mr-2">Records per page:</label>
                <select name="per_page" id="per_page" class="p-2 border border-gray-300 rounded-md"
                    onchange="this.form.submit()">
                    <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                    <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                    <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                    <option value="500" <?php echo $limit == 500 ? 'selected' : ''; ?>>500</option>
                </select>
            </form>
        </div>

        <div class="relative bg-white p-4 rounded-md shadow-md max-w-[calc(100vw-250px)]">
            <div class="overflow-x-auto max-w-full">
                <!-- Filter Form -->
                <form method="GET" class="mb-4 flex flex-wrap gap-4">
                    <input type="text" name="search" placeholder="Search..."
                        class="w-full md:w-auto p-2 border border-gray-300 rounded-md"
                        value="<?php echo htmlspecialchars($search); ?>">

                    <select name="status_filter" class="p-2 border border-gray-300 rounded-md">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending
                        </option>
                        <option value="Completed" <?php echo $status_filter === 'Completed' ? 'selected' : ''; ?>>
                            Completed</option>
                        <option value="Incomplete" <?php echo $status_filter === 'Incomplete' ? 'selected' : ''; ?>>
                            Incomplete</option>
                        <option value="Cancelled" <?php echo $status_filter === 'Cancelled' ? 'selected' : ''; ?>>Cancelled
                        </option>
                        <option value="Returned" <?php echo $status_filter === 'Returned' ? 'selected' : ''; ?>>Returned
                        </option>
                        <option value="Dispatched" <?php echo $status_filter === 'Dispatched' ? 'selected' : ''; ?>>
                            Dispatched
                        </option>
                        <option value="Delay" <?php echo $status_filter === 'Delay' ? 'selected' : ''; ?>>
                            Delay
                        </option>
                    </select>

                    <input type="date" name="start_date" class="p-2 border border-gray-300 rounded-md"
                        value="<?php echo htmlspecialchars($start_date); ?>">
                    <input type="date" name="end_date" class="p-2 border border-gray-300 rounded-md"
                        value="<?php echo htmlspecialchars($end_date); ?>">

                    <button type="submit" class="bg-[var(--primary-color)] text-white px-4 py-2 rounded-md">
                        Apply Filters
                    </button>
                </form>

                <table class="w-full min-w-[900px] border-collapse border border-gray-300 text-sm">
                   <thead class="bg-gray-200 sticky top-0 z-10">
    <tr class="whitespace-nowrap text-left">
        <th class="p-2 border min-w-[30px] bg-gray-200"><input type="checkbox" id="select-all"></th>
        <th class="p-2 border min-w-[180px] text-center bg-gray-200">Actions</th>
        <th class="p-2 border min-w-[200px] bg-gray-200">Status</th>
        <th class="p-2 border min-w-[100px] bg-gray-200">Invoice Id</th>
        <th class="p-2 border min-w-[100px] bg-gray-200">Mobile</th>
        <th class="p-2 border min-w-[100px] bg-gray-200">Mobile2</th>
        <th class="p-2 border min-w-[150px] bg-gray-200">Name</th>
        <th class="p-2 border min-w-[200px] bg-gray-200">Barcode</th>
        <th class="p-2 border min-w-[100px] bg-gray-200">Customer ID</th>
        <th class="p-2 border min-w-[200px] bg-gray-200">Address 1</th>
        <th class="p-2 border min-w-[200px] bg-gray-200">Address 2</th>
        <th class="p-2 border min-w-[80px] bg-gray-200">Pincode</th>
        <th class="p-2 border min-w-[120px] bg-gray-200">District</th>
        <th class="p-2 border min-w-[120px] bg-gray-200">Sub District</th>
        <th class="p-2 border min-w-[120px] bg-gray-200">Village</th>
        <th class="p-2 border min-w-[350px] bg-gray-200">Products</th>
        <th class="p-2 border min-w-[120px] bg-gray-200">Employee</th>
        <th class="p-2 border min-w-[100px] bg-gray-200">Total</th>
        <th class="p-2 border min-w-[100px] bg-gray-200">Adv. Payment</th>
        <th class="p-2 border min-w-[100px] bg-gray-200">Repeated Order</th>
        <th class="p-2 border min-w-[140px] bg-gray-200">Created At</th>
    </tr>
</thead>
                    <tbody class="divide-y divide-gray-300">
                        <?php while ($row = $result->fetch_assoc()):
                                // Fetch items for this invoice
                                $items_sql = "SELECT ii.*, p.product_name, p.price,p.weight,p.sku
						                                         FROM invoice_items ii
						                                         JOIN products p ON ii.product_id = p.id
						                                         WHERE ii.invoice_id = ?";
                                $items_stmt = $conn->prepare($items_sql);
                                $items_stmt->bind_param("i", $row['id']);
                                $items_stmt->execute();
                                $items_result  = $items_stmt->get_result();
                                $invoice_items = $items_result->fetch_all(MYSQLI_ASSOC);
                            ?>
                        <?php
        // Fetch distributor data for this customer
        $distributor_data = [];
        if (! empty($row['customer_id'])) {
            $distributor_stmt = $conn->prepare("SELECT * FROM distributors WHERE customer_id = ?");
            $distributor_stmt->bind_param("s", $row['customer_id']);
            $distributor_stmt->execute();
            $distributor_result = $distributor_stmt->get_result();
            $distributor_data   = $distributor_result->fetch_assoc();
        }

        $row['invoice_items'] = $invoice_items; // Add product info into the row before sending to JS
    ?>
                        <tr class="text-left bg-gray-50 hover:bg-gray-100">
                            <td class="p-2 border"><input type="checkbox" name="selected[]"
                                    value="<?php echo $row['id']; ?>"></td>
                                    <td class="p-2 border text-center flex space-x-2">
                                <?php if ($_SESSION['role'] == 'Admin'): ?>
                                <a href="edit_invoice.php?id=<?php echo $row['id']; ?>"
                                    class="bg-yellow-500 text-white px-3 py-1 text-xs rounded">Edit</a>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="delete_invoice"
                                        class="bg-red-500 text-white px-3 py-1 text-xs rounded"
                                        onclick="return confirm('Are you sure you want to delete this invoice?');">Delete</button>
                                </form>
                                <button type="button" class="bg-blue-500 text-white px-3 py-1 text-xs rounded"
                                    onclick='printInvoice(<?php echo json_encode($row); ?>)'>Print</button>

                                <?php endif; ?>
                                <a href="createinvoice.php?clone_id=<?php echo $row['id']; ?>"
                                    class="bg-purple-500 text-white px-3 py-1 text-xs rounded">Clone</a>

                            </td>
                            <td class="p-2 border"><?php echo htmlspecialchars($row['status']); ?></td>
                            <td class="p-2 border"><?php echo htmlspecialchars($row['id']); ?></td>
                            <td class="p-2 border"><?php echo htmlspecialchars($row['mobile']); ?></td>
                            <td class="p-2 border"><?php echo htmlspecialchars($row['mobile2']); ?></td>
                            <td class="p-2 border"><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td class="p-2 border"><?php echo htmlspecialchars($row['barcode_number']); ?></td>
                            <td class="p-2 border"><?php echo htmlspecialchars($row['customer_id'] ?? ''); ?></td>
                            <td class="p-2 border"><?php echo htmlspecialchars($row['address1']); ?></td>
                            <td class="p-2 border"><?php echo htmlspecialchars($row['address2']); ?></td>
                            <td class="p-2 border"><?php echo htmlspecialchars($row['pincode']); ?></td>
                            <td class="p-2 border"><?php echo htmlspecialchars($row['district']); ?></td>
                            <td class="p-2 border"><?php echo htmlspecialchars($row['sub_district']); ?></td>
                            <td class="p-2 border"><?php echo htmlspecialchars($row['village']); ?></td>
                            <td class="p-2 border">
                                <?php foreach ($invoice_items as $item): ?>
                                <div class="text-xs mb-1">
                                    <?php echo htmlspecialchars($item['product_name']); ?>
                                    (Qty:<?php echo $item['quantity']; ?>,
                                    ₹<?php echo number_format($item['price'], 2); ?>)
                                    <?php if ($item['discount'] > 0): ?>
                                    - Discount: ₹<?php echo number_format($item['discount'], 2); ?>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </td>
                            <td class="p-2 border"><?php echo htmlspecialchars($row['employee_name']); ?></td>
                            <td class="p-2 border">₹<?php echo number_format($row['total_amount'], 2); ?></td>
                            <td class="p-2 border">₹<?php echo number_format($row['advanced_payment'], 2); ?></td>
                            <td class="p-2 border"><?php echo htmlspecialchars($row['is_repeated_order']); ?></td>

                            <td class="p-2 border"><?php echo date("d-m-Y h:i A", strtotime($row['created_at'])); ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($_SESSION['role'] == 'Admin'): ?>
        <!-- Bulk Actions -->
        <div class="mt-4 flex gap-4 w-full">
            <button type="button" class="bg-blue-500 text-white px-4 py-2 rounded"
                onclick="printSelectedInvoices()">Print Selected</button>

            <button type="button" class="bg-green-600 text-white px-4 py-2 rounded"
                onclick="printMahavirCourierInvoices()">Other Courier Print</button>
        </div>

        <?php endif; ?>
        <!-- Pagination -->
        <div class="mt-4 flex justify-center items-center space-x-2">
            <?php if ($page > 1): ?>
            <a href="?page=1&search=<?php echo urlencode($search); ?>&status_filter=<?php echo urlencode($status_filter); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>"
                class="px-3 py-1 border rounded bg-white hover:bg-gray-100">
                &laquo; First
            </a>
            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status_filter=<?php echo urlencode($status_filter); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>"
                class="px-3 py-1 border rounded bg-white hover:bg-gray-100">
                &lsaquo; Prev
            </a>
            <?php endif; ?>

            <?php
                $start_page = max(1, $page - 2);
                $end_page   = min($pages, $page + 2);

                if ($start_page > 1) {
                    echo '<span class="px-2">...</span>';
                }

            for ($i = $start_page; $i <= $end_page; $i++): ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status_filter=<?php echo urlencode($status_filter); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>"
                class="px-3 py-1 border rounded                                                                                                                                                                                                                                                                                           <?php echo($i == $page) ? 'bg-gray-300 font-bold' : 'bg-white hover:bg-gray-100'; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor;

                if ($end_page < $pages) {
                    echo '<span class="px-2">...</span>';
                }
            ?>

            <?php if ($page < $pages): ?>
            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status_filter=<?php echo urlencode($status_filter); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>"
                class="px-3 py-1 border rounded bg-white hover:bg-gray-100">
                Next &rsaquo;
            </a>
            <a href="?page=<?php echo $pages; ?>&search=<?php echo urlencode($search); ?>&status_filter=<?php echo urlencode($status_filter); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>"
                class="px-3 py-1 border rounded bg-white hover:bg-gray-100">
                Last &raquo;
            </a>
            <?php endif; ?>
        </div>

        <!-- Page Info -->
        <div class="text-center text-sm text-gray-600 mt-2">
            Page <?php echo $page; ?> of<?php echo $pages; ?> |
            Showing <?php echo($start + 1); ?>-<?php echo min($start + $limit, $total); ?> of<?php echo $total; ?>
            records
        </div>

        <?php if ($_SESSION['role'] == 'Admin'): ?>
        <!-- Export Form -->
        <div class="mt-6 flex justify-start">
            <form action="export_excel.php" method="post">
                <label for="from_date">From:</label>
                <input type="date" id="from_date" name="from_date" required>

                <label for="to_date">To:</label>
                <input type="date" id="to_date" name="to_date" required>

                <button type="submit" class="bg-[var(--primary-color)] text-white px-6 py-2 rounded-md">
                    Export To Excel
                </button>
            </form>
        </div>
        <?php endif; ?>
    </main>


    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAllMode = sessionStorage.getItem('selectAllMode') === 'true';
        const selectedIds = JSON.parse(sessionStorage.getItem('selectedInvoiceIds') || '[]');

        document.getElementById('select-all').checked = selectAllMode;

        if (selectAllMode) {
            // In select-all mode, check all checkboxes except those manually unchecked
            const uncheckedIds = selectedIds; // In this case, selectedIds contains manually unchecked IDs
            const checkboxes = document.querySelectorAll('input[name="selected[]"]');

            checkboxes.forEach(checkbox => {
                checkbox.checked = !uncheckedIds.includes(checkbox.value);
            });
        } else if (selectedIds.length > 0) {
            // In manual selection mode, check the stored IDs
            selectedIds.forEach(id => {
                const checkbox = document.querySelector(`input[name="selected[]"][value="${id}"]`);
                if (checkbox) checkbox.checked = true;
            });
            updateSelectAllCheckbox();
        }
    });
    // Select all checkboxes
    document.getElementById('select-all').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('input[name="selected[]"]');
        const isSelectAll = this.checked;

        checkboxes.forEach(checkbox => {
            checkbox.checked = isSelectAll;
        });

        // Store the selection state
        if (isSelectAll) {
            // When selecting all, store a special flag
            sessionStorage.setItem('selectAllMode', 'true');
            sessionStorage.removeItem('selectedInvoiceIds');
        } else {
            // When deselecting all, clear everything
            sessionStorage.removeItem('selectAllMode');
            sessionStorage.removeItem('selectedInvoiceIds');
        }
    });
    document.addEventListener('change', function(e) {
        if (e.target && e.target.name === 'selected[]') {
            // If we're in select-all mode and one is unchecked, switch to manual mode
            if (sessionStorage.getItem('selectAllMode') === 'true' && !e.target.checked) {
                sessionStorage.removeItem('selectAllMode');

                // Get all currently checked checkboxes
                const checkedBoxes = document.querySelectorAll('input[name="selected[]"]:checked');
                const selectedIds = Array.from(checkedBoxes).map(cb => cb.value);

                sessionStorage.setItem('selectedInvoiceIds', JSON.stringify(selectedIds));
            }
            // If not in select-all mode, update the selection normally
            else if (sessionStorage.getItem('selectAllMode') !== 'true') {
                let selectedIds = JSON.parse(sessionStorage.getItem('selectedInvoiceIds') || '[]');

                if (e.target.checked) {
                    if (!selectedIds.includes(e.target.value)) {
                        selectedIds.push(e.target.value);
                    }
                } else {
                    selectedIds = selectedIds.filter(id => id !== e.target.value);
                }

                sessionStorage.setItem('selectedInvoiceIds', JSON.stringify(selectedIds));
            }

            updateSelectAllCheckbox();
        }
    });

    function updateSelectAllCheckbox() {
        const checkboxes = document.querySelectorAll('input[name="selected[]"]');
        const selectAll = document.getElementById('select-all');
        const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);

        selectAll.checked = allChecked;
        sessionStorage.setItem('selectAllInvoices', allChecked);
    }




    // Show message function
    function showMessage(message, type = 'info') {
        const container = document.getElementById('message-container');
        if (!container) return;

        const colors = {
            success: 'bg-green-100 border-green-500 text-green-700',
            error: 'bg-red-100 border-red-500 text-red-700',
            info: 'bg-blue-100 border-blue-500 text-blue-700',
            warning: 'bg-yellow-100 border-yellow-500 text-yellow-700'
        };

        const messageDiv = document.createElement('div');
        messageDiv.className = `p-4 mb-2 border-l-4 rounded ${colors[type]} shadow-md flex items-center`;
        messageDiv.innerHTML = `
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                ${type === 'success' ?
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>' :
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>'}
            </svg>
            <span>${message}</span>
        `;

        container.appendChild(messageDiv);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            messageDiv.classList.add('opacity-0', 'transition-opacity', 'duration-300');
            setTimeout(() => messageDiv.remove(), 300);
        }, 5000);
    }
    </script>

    <?php include 'scripts.php'; ?>
</body>

</html>