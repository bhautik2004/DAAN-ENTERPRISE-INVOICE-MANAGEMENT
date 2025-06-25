<?php
    include 'db.php';
    include 'header.php';
    include 'head.php';
?>

<body class="bg-gray-100 flex">
    <?php include 'sidebar.php'; ?>
    <main class="flex-1 pl-56 m-4">
        <!-- Message Container -->
        <div id="message-container" class="fixed top-20 right-4 z-50"></div>

        <h2 class="text-2xl font-bold text-[var(--primary-color)] mb-4 text-center">Orders</h2>

        <?php
            // Handle update or delete actions
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (isset($_POST['id'])) {
                    $id = $_POST['id'];

                    if (isset($_POST['update'])) {
                        $errorks = [];

                        // Validate inputs
                        if (empty($_POST['full_name'])) {
                            $errors[] = "Full Name is required.";
                        }
                        if (empty($_POST['barcode_number'])) {
                            $errors[] = "Barcode Number is required.";
                        }

                        if (! empty($errors)) {
                            echo "<script>showErrorMessages(" . json_encode($errors) . ");</script>";
                        } else {
                            // Start transaction
                            $conn->begin_transaction();

                            try {
                                // Update invoice header with corrected bind_param
                                $updateSql = "UPDATE invoices SET
                full_name = ?, mobile = ?, address1 = ?, address2 = ?, pincode = ?,
                district = ?, sub_district = ?, village = ?, post_name = ?, mobile2 = ?,
                barcode_number = ?, employee_name = ?, advanced_payment = ?, status = ?,
                total_amount = ?
                WHERE id = ?";

                                $stmt = $conn->prepare($updateSql);
                                $stmt->bind_param("ssssssssssssdsdi",
                                    $_POST['full_name'],
                                    $_POST['mobile'],
                                    $_POST['address1'],
                                    $_POST['address2'],
                                    $_POST['pincode'],
                                    $_POST['district'],
                                    $_POST['sub_district'],
                                    $_POST['village'],
                                    $_POST['post_name'],
                                    $_POST['mobile2'],
                                    $_POST['barcode_number'],
                                    $_POST['employee_name'],
                                    $_POST['advanced_payment'],
                                    $_POST['status'],
                                    $_POST['total_amount'],
                                    $id
                                );

                                if (! $stmt->execute()) {
                                    throw new Exception("Error updating invoice: " . $stmt->error);
                                }

                                // Update invoice items
                                if (isset($_POST['item_id'])) {
                                    $item_ids    = $_POST['item_id'] ?? [];
                                    $product_ids = $_POST['product_id'] ?? [];
                                    $quantities  = $_POST['quantity'] ?? [];
                                    $discounts   = $_POST['discount'] ?? [];
                                    $prices      = $_POST['price'] ?? [];

                                    // First delete items that were removed
                                    $existingItems = [];
                                    $itemsResult   = $conn->query("SELECT id FROM invoice_items WHERE invoice_id = $id");
                                    while ($item = $itemsResult->fetch_assoc()) {
                                        $existingItems[] = $item['id'];
                                    }

                                    $deletedItems = array_diff($existingItems, $item_ids);
                                    if (! empty($deletedItems)) {
                                        $deleteSql = "DELETE FROM invoice_items WHERE id IN (" . implode(',', $deletedItems) . ")";
                                        if (! $conn->query($deleteSql)) {
                                            throw new Exception("Error deleting items: " . $conn->error);
                                        }
                                    }

                                    // Update or insert items
                                    $total_amount = 0;
                                    for ($i = 0; $i < count($product_ids); $i++) {
                                        $item_id    = $item_ids[$i] ?? null; // Handle new items that don't have an ID yet
                                        $product_id = $product_ids[$i] ?? null;
                                        $quantity   = $quantities[$i] ?? 0;
                                        $price      = $prices[$i] ?? 0;
                                        $discount   = $discounts[$i] ?? 0;
                                        $row_total  = ($price * $quantity) - $discount;
                                        $total_amount += $row_total;

                                        // Validate required fields
                                        if (empty($product_id) || empty($quantity) || empty($price)) {
                                            continue; // Skip invalid rows
                                        }

                                        if (! empty($item_id)) {
                                            // Update existing item
                                            $updateItem = "UPDATE invoice_items SET
                product_id = ?, quantity = ?, price = ?, discount = ?
                WHERE id = ?";
                                            $stmt = $conn->prepare($updateItem);
                                            $stmt->bind_param("iiddi", $product_id, $quantity, $price, $discount, $item_id);
                                        } else {
                                            // Insert new item
                                            $insertItem = "INSERT INTO invoice_items
                (invoice_id, product_id, quantity, price, discount)
                VALUES (?, ?, ?, ?, ?)";
                                            $stmt = $conn->prepare($insertItem);
                                            $stmt->bind_param("iiidd", $id, $product_id, $quantity, $price, $discount);
                                        }

                                        if (! $stmt->execute()) {
                                            throw new Exception("Error updating items: " . $stmt->error);
                                        }
                                    }

                                    // Subtract advanced payment from the total
                                    $advanced_payment = $_POST['advanced_payment'] ?? 0;
                                    $total_amount -= $advanced_payment;

                                    // Update the invoice total with this adjusted amount
                                    $updateTotal = "UPDATE invoices SET total_amount = ? WHERE id = ?";
                                    $stmt        = $conn->prepare($updateTotal);
                                    $stmt->bind_param("di", $total_amount, $id);
                                    $stmt->execute();
                                }
                                $conn->commit();
                                echo "<script>showMessage('Invoice updated successfully!', 'success'); setTimeout(() => { window.location.reload(); }, 1500);</script>";
                            } catch (Exception $e) {
                                $conn->rollback();
                                echo "<script>showMessage('Error updating invoice: " . addslashes($e->getMessage()) . "', 'error');</script>";
                            }
                        }
                    } elseif (isset($_POST['delete_invoice'])) {
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
                                echo "<script>showMessage('Invoice deleted successfully!', 'success'); setTimeout(() => { window.location.href = window.location.pathname; }, 1500);</script>";
                            } else {
                                throw new Exception($stmt->error);
                            }
                        } catch (Exception $e) {
                            $conn->rollback();
                            echo "<script>showMessage('Error deleting invoice: " . addslashes($e->getMessage()) . "', 'error');</script>";
                        }
                    }
                    // elseif (isset($_POST['delete_product'])) {
                    //     $item_id       = $_POST['item_id'];
                    //     $deleteItemSql = "DELETE FROM invoice_items WHERE id = ?";
                    //     $stmt          = $conn->prepare($deleteItemSql);
                    //     $stmt->bind_param("i", $item_id);
                    //     if ($stmt->execute()) {
                    //         echo "<script>showMessage('Product removed successfully!', 'success'); setTimeout(() => { window.location.reload(); }, 1500);</script>";
                    //     } else {
                    //         echo "<script>showMessage('Error removing product: " . addslashes($stmt->error) . "', 'error');</script>";
                    //     }
                    //     $stmt->close();
                    // }
                }
            }

            // Search and pagination logic
            $limit         = 10;
            $page          = isset($_GET['page']) ? (int) $_GET['page'] : 1;
            $start         = ($page - 1) * $limit;
            $search        = isset($_GET['search']) ? trim($_GET['search']) : '';
            $status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
            $start_date    = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
            $end_date      = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

            $role = $_SESSION['role'] ?? null;
            $user = $_SESSION['user'] ?? null;

            // Base query
            $sql = "SELECT * FROM invoices";

            $conditions  = [];
            $paramTypes  = '';
            $paramValues = [];

            if ($role === "Employee") {
                $conditions[] = "employee_name = ?";
                $paramTypes .= 's';
                $paramValues[] = $user;
            }

            if (! empty($search)) {
                $conditions[] = "(full_name LIKE ? OR mobile LIKE ? OR barcode_number LIKE ?)";
                $paramTypes .= 'sss';
                $paramValues[] = "%$search%";
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
            <input type="text" name="search" placeholder="Search by Name, Mobile or Barcode..."
                class="w-full p-2 ml-2 border border-gray-300 rounded-l-md"
                value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="bg-[var(--primary-color)] text-white px-2 py-2 rounded-r-md">
                Search
            </button>
        </form>

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
                        <option value="Canceled" <?php echo $status_filter === 'Canceled' ? 'selected' : ''; ?>>Canceled
                        </option>
                        <option value="Returned" <?php echo $status_filter === 'Returned' ? 'selected' : ''; ?>>Returned
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
                            <th class="p-2 border min-w-[100px] bg-gray-200">Select</th>
                            <th class="p-2 border min-w-[150px] text-center bg-gray-200">Actions</th>
                            <th class="p-2 border min-w-[150px] bg-gray-200">Status</th>
                            <th class="p-2 border min-w-[100px] bg-gray-200">Mobile</th>
                            <th class="p-2 border min-w-[200px] bg-gray-200">Name</th>
                            <th class="p-2 border min-w-[150px] bg-gray-200">Barcode</th>
                            <th class="p-2 border min-w-[250px] bg-gray-200">Address 1</th>
                            <th class="p-2 border min-w-[250px] bg-gray-200">Address 2</th>
                            <th class="p-2 border min-w-[100px] bg-gray-200">Pincode</th>
                            <th class="p-2 border min-w-[150px] bg-gray-200">District</th>
                            <th class="p-2 border min-w-[150px] bg-gray-200">Sub District</th>
                            <th class="p-2 border min-w-[150px] bg-gray-200">Village</th>
                            <th class="p-2 border min-w-[150px] bg-gray-200">Post</th>
                            <th class="p-2 border min-w-[150px] bg-gray-200">Mobile 2</th>
                            <th class="p-2 border min-w-[600px] bg-gray-200">Products</th>
                            <th class="p-2 border min-w-[150px] bg-gray-200">Employee</th>
                            <th class="p-2 border min-w-[120px] bg-gray-200">Total Amount</th>
                            <th class="p-2 border min-w-[120px] bg-gray-200">Advanced Payment</th>
                            <th class="p-2 border min-w-[160px] bg-gray-200">Created At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-300">
                        <?php while ($row = $result->fetch_assoc()):
                                // Fetch items for this invoice
                                $items_sql = "SELECT ii.*, p.product_name, p.price
															                                         FROM invoice_items ii
															                                         JOIN products p ON ii.product_id = p.id
															                                         WHERE ii.invoice_id = ?";
                                $items_stmt = $conn->prepare($items_sql);
                                $items_stmt->bind_param("i", $row['id']);
                                $items_stmt->execute();
                                $items_result  = $items_stmt->get_result();
                                $invoice_items = $items_result->fetch_all(MYSQLI_ASSOC);
                            ?>
                        <tr class="text-left bg-gray-50 hover:bg-gray-100" id="row_<?php echo $row['id']; ?>">
                            <form method="POST" id="form_<?php echo $row['id']; ?>"
                                onsubmit="return validateForm(<?php echo $row['id']; ?>)">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">

                                <td class="p-2 border"><input type="checkbox" name="selected[]"
                                        value="<?php echo $row['id']; ?>"></td>
                                <td class="p-2 border text-center flex space-x-2">
                                    <button type="button"
                                        class="edit-btn bg-yellow-500 text-white px-3 py-1 text-xs rounded"
                                        onclick="enableEdit(<?php echo $row['id']; ?>)">Edit</button>
                                    <button type="submit" name="update"
                                        class="save-btn bg-green-500 text-white px-3 py-1 text-xs rounded hidden">Save</button>
                                    <?php if ($_SESSION['role'] == 'Admin'): ?>
                                    <button type="submit" name="delete_invoice"
                                        class="bg-red-500 text-white px-3 py-1 text-xs rounded"
                                        onclick="return confirm('Are you sure you want to delete this invoice?');">Delete</button>
                                    <?php endif; ?>
                                    <button type="button" class="bg-blue-500 text-white px-3 py-1 text-xs rounded"
                                        onclick="printInvoice(<?php echo $row['id']; ?>)">Print</button>
                                </td>

                                <td class="p-2 border">
                                    <select name="status"
                                        class="editable w-full p-1 border border-gray-300 text-xs rounded" disabled>
                                        <option value="Pending"
                                            <?php echo $row['status'] == 'Pending' ? 'selected' : ''; ?>>Pending
                                        </option>
                                        <option value="Completed"
                                            <?php echo $row['status'] == 'Completed' ? 'selected' : ''; ?>>Completed
                                        </option>
                                        <option value="Canceled"
                                            <?php echo $row['status'] == 'Canceled' ? 'selected' : ''; ?>>Canceled
                                        </option>
                                        <option value="Returned"
                                            <?php echo $row['status'] == 'Returned' ? 'selected' : ''; ?>>Returned
                                        </option>
                                    </select>
                                </td>

                                <td class="p-2 border"><input type="text" name="mobile"
                                        value="<?php echo htmlspecialchars($row['mobile']); ?>"
                                        class="editable w-full p-1 border border-gray-300 text-xs rounded" disabled>
                                </td>

                                <td class="p-2 border"><input type="text" name="full_name"
                                        value="<?php echo htmlspecialchars($row['full_name']); ?>"
                                        class="editable w-full p-1 border border-gray-300 text-xs rounded" disabled
                                        required></td>

                                <td class="p-2 border"><input type="text" name="barcode_number"
                                        value="<?php echo htmlspecialchars($row['barcode_number']); ?>"
                                        class="editable w-full p-1 border border-gray-300 text-xs rounded" disabled
                                        required></td>

                                <td class="p-2 border"><input type="text" name="address1"
                                        value="<?php echo htmlspecialchars($row['address1']); ?>"
                                        class="editable w-full p-1 border border-gray-300 text-xs rounded" disabled>
                                </td>

                                <td class="p-2 border"><input type="text" name="address2"
                                        value="<?php echo htmlspecialchars($row['address2']); ?>"
                                        class="editable w-full p-1 border border-gray-300 text-xs rounded" disabled>
                                </td>

                                <td class="p-2 border"><input type="text" name="pincode"
                                        value="<?php echo htmlspecialchars($row['pincode']); ?>"
                                        class="editable w-full p-1 border border-gray-300 text-xs rounded" disabled>
                                </td>

                                <td class="p-2 border"><input type="text" name="district"
                                        value="<?php echo htmlspecialchars($row['district']); ?>"
                                        class="editable w-full p-1 border border-gray-300 text-xs rounded" disabled>
                                </td>

                                <td class="p-2 border"><input type="text" name="sub_district"
                                        value="<?php echo htmlspecialchars($row['sub_district']); ?>"
                                        class="editable w-full p-1 border border-gray-300 text-xs rounded" disabled>
                                </td>

                                <td class="p-2 border"><input type="text" name="village"
                                        value="<?php echo htmlspecialchars($row['village']); ?>"
                                        class="editable w-full p-1 border border-gray-300 text-xs rounded" disabled>
                                </td>

                                <td class="p-2 border"><input type="text" name="post_name"
                                        value="<?php echo htmlspecialchars($row['post_name']); ?>"
                                        class="editable w-full p-1 border border-gray-300 text-xs rounded" disabled>
                                </td>

                                <td class="p-2 border"><input type="text" name="mobile2"
                                        value="<?php echo htmlspecialchars($row['mobile2']); ?>"
                                        class="editable w-full p-1 border border-gray-300 text-xs rounded" disabled>
                                </td>

                                <!-- In your table row where you display the product items, modify the edit section like this: -->
                                <td class="p-2 border">
                                    <!-- View mode -->
                                    <div id="items-display-<?php echo $row['id']; ?>">
                                        <?php foreach ($invoice_items as $item): ?>
                                        <div class="text-xs mb-1">
                                            <?php echo htmlspecialchars($item['product_name']); ?>
                                            (Qty:<?php echo $item['quantity']; ?>,
                                            ₹<?php echo number_format($item['price'], 2); ?>)
                                            <?php if ($item['discount'] > 0): ?>
                                            - Discount: ₹<?php echo number_format($item['discount'], 2); ?>
                                            <?php endif; ?>
                                            <input type="hidden" name="item_id[]" value="<?php echo $item['id']; ?>">
                                        </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <!-- Edit mode -->
                                    <div id="items-edit-<?php echo $row['id']; ?>" class="hidden space-y-2 text-xs">
                                        <!-- Headings -->
                                        <div class="grid grid-cols-5 font-semibold text-gray-600">
                                            <div>Product</div>
                                            <div>Qty</div>
                                            <div>Price</div>
                                            <div>Discount</div>
                                            <div>Action</div>
                                        </div>

                                        <!-- Editable rows -->
                                        <?php foreach ($invoice_items as $item): ?>
                                        <div class="item-row grid grid-cols-5 gap-2">
                                            <input type="hidden" name="item_id[]" value="<?php echo $item['id']; ?>">
                                            <select name="product_id[]"
                                                class="product-select editable p-1 border rounded" required
                                                onchange="updatePrice(this,                                                                                                                                                                                                                                                                                                                                                                                                                                                                   <?php echo $row['id']; ?>)">
                                                <option value="">Select Product</option>
                                                <?php foreach ($products as $product): ?>
                                                <option value="<?php echo $product['id']; ?>"
                                                    data-price="<?php echo $product['price']; ?>"
                                                    <?php echo $product['id'] == $item['product_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($product['product_name']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>

                                            <input type="number" name="quantity[]"
                                                value="<?php echo $item['quantity']; ?>"
                                                class="quantity-input editable p-1 border rounded"
                                                onchange="calculateRowTotal(this,                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       <?php echo $row['id']; ?>)"
                                                min="1" required>

                                            <input type="number" name="price[]" value="<?php echo $item['price']; ?>"
                                                class="price-input editable p-1 border rounded bg-gray-100"
                                                onchange="calculateRowTotal(this,                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       <?php echo $row['id']; ?>)"
                                                min="0" step="0.01" required readonly>

                                            <input type="number" name="discount[]"
                                                value="<?php echo $item['discount']; ?>"
                                                class="discount-input editable p-1 border rounded"
                                                onchange="calculateRowTotal(this,                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       <?php echo $row['id']; ?>)"
                                                min="0" step="0.01">

                                            <button type="button" class="remove-item-btn text-red-500 text-lg"
                                                onclick="removeItemRow(this,                                                                                                                                                                                                                                                                                                                                                                                                                                                                         <?php echo $row['id']; ?>)">×</button>
                                        </div>
                                        <?php endforeach; ?>

                                        <button type="button" class="add-item-btn text-xs text-blue-500 mt-1"
                                            onclick="addItemRow(<?php echo $row['id']; ?>)">+ Add Product</button>
                                    </div>
                                </td>

                                <td class="p-2 border"><input type="text" name="employee_name" readonly
                                        value="<?php echo htmlspecialchars($row['employee_name']); ?>"
                                        class="editable w-full p-1 border border-gray-300 text-xs rounded" disabled>
                                </td>

                                <td class="p-2 border">
                                    ₹<span id="total-display-<?php echo $row['id']; ?>">
                                        <?php echo number_format($row['total_amount'], 2); ?>
                                    </span>
                                    <input type="hidden" name="total_amount" id="total-input-<?php echo $row['id']; ?>"
                                        value="<?php echo $row['total_amount']; ?>">
                                </td>

                                <td class="p-2 border">
                                    <input type="number" name="advanced_payment"
                                        value="<?php echo htmlspecialchars($row['advanced_payment']); ?>"
                                        class="editable w-full p-1 border border-gray-300 text-xs rounded"
                                        onchange="calculateInvoiceTotal(<?php echo $row['id']; ?>)" disabled>
                                </td>

                                <td class="p-2 border">
                                    <?php echo htmlspecialchars(date("d-m-Y h:i A", strtotime($row['created_at']))); ?>
                                </td>
                            </form>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Bulk Actions -->
        <div class="mt-4 flex justify-between w-full">
            <button type="button" class="bg-blue-500 text-white px-4 py-2 rounded"
                onclick="printSelectedInvoices()">Print Selected</button>
            <button onclick="printCombinedInvoices()"
                class="bg-[var(--primary-color)] text-white px-4 py-2 rounded mr-8">Print Combined Invoices</button>
        </div>

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
                class="px-3 py-1 border rounded                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  <?php echo($i == $page) ? 'bg-gray-300 font-bold' : 'bg-white hover:bg-gray-100'; ?>">
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
    </main>


    <script>
    // Enable edit mode with proper initialization

    function enableEdit(invoiceId) {
        const row = document.getElementById(`row_${invoiceId}`);
        const form = document.getElementById(`form_${invoiceId}`);

        // Enable all editable fields except price
        row.querySelectorAll('.editable').forEach(field => {
            if (!field.classList.contains('price-input')) {
                field.disabled = false;
                field.classList.remove('bg-gray-100');
            }
        });

        // Show product edit section
        const displaySection = row.querySelector(`#items-display-${invoiceId}`);
        const editSection = row.querySelector(`#items-edit-${invoiceId}`);
        if (displaySection && editSection) {
            displaySection.classList.add('hidden');
            editSection.classList.remove('hidden');
        }

        // Toggle buttons
        row.querySelector('.edit-btn')?.classList.add('hidden');
        row.querySelector('.save-btn')?.classList.remove('hidden');

        // Initialize price fields as readonly
        row.querySelectorAll('.price-input').forEach(input => {
            input.readOnly = true;
            input.classList.add('bg-gray-100');
        });

        // Get the original values from the form
        const originalTotal = parseFloat(form.querySelector('input[name="total_amount"]').value) || 0;
        const advancedPayment = parseFloat(form.querySelector('input[name="advanced_payment"]').value) || 0;

        // Calculate the correct subtotal (original total + advanced payment)
        const subtotal = originalTotal + advancedPayment;

        // Update all relevant displays
        document.getElementById(`total-display-${invoiceId}`).textContent = subtotal.toFixed(2);
        document.getElementById(`total-input-${invoiceId}`).value = subtotal.toFixed(2);

        // Set up advanced payment event listener
        const advancedPaymentInput = form.querySelector('input[name="advanced_payment"]');
        if (advancedPaymentInput) {
            advancedPaymentInput.addEventListener('input', () => {
                calculateInvoiceTotal(invoiceId);
            });
        }

        // Initial calculation
        calculateInvoiceTotal(invoiceId);
    }
    // Add new item row
    function addItemRow(invoiceId) {
        const container = document.getElementById(`items-edit-${invoiceId}`);
        const newRow = document.createElement('div');
        newRow.className = 'item-row grid grid-cols-5 gap-2 text-xs';
        newRow.innerHTML = `
        <select name="product_id[]" class="p-1 border rounded product-select"
                onchange="updatePrice(this, ${invoiceId})" required>
            <option value="">Select Product</option>
            <?php foreach ($products as $product): ?>
            <option value="<?php echo $product['id']; ?>"
                    data-price="<?php echo $product['price']; ?>">
                <?php echo htmlspecialchars($product['product_name']); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="quantity[]" value="1"
               class="editable p-1 border rounded quantity-input"
               onchange="calculateRowTotal(this, ${invoiceId})"
               min="1" required>
        <input type="number" name="price[]" value="<?php echo $products[0]['price'] ?? 0; ?>"
               class="editable p-1 border rounded bg-gray-100 price-input"
               onchange="calculateRowTotal(this, ${invoiceId})"
               min="0" step="0.01" required readonly>
        <input type="number" name="discount[]" value="0"
               class="editable p-1 border rounded discount-input"
               onchange="calculateRowTotal(this, ${invoiceId})"
               min="0" step="0.01">
        <button type="button" class="remove-item-btn text-red-500"
                onclick="removeItemRow(this, ${invoiceId})">×</button>
    `;

        // Insert before the "Add Product" button
        const addButton = container.querySelector('.add-item-btn');
        container.insertBefore(newRow, addButton);

        // Calculate the new total
        calculateInvoiceTotal(invoiceId);
    }

    // Calculate total for a single row
    function calculateRowTotal(input, invoiceId) {
        calculateInvoiceTotal(invoiceId);
    }
    // Update price when product changes
    function updatePrice(select, invoiceId) {
        const price = select.options[select.selectedIndex].getAttribute('data-price');
        if (price) {
            const row = select.closest('.item-row');
            const priceInput = row.querySelector('.price-input');
            priceInput.value = price;
            calculateInvoiceTotal(invoiceId);
        }
    }

    function calculateInvoiceTotal(invoiceId) {
        try {
            const form = document.getElementById(`form_${invoiceId}`);
            if (!form) {
                console.error(`Form with ID form_${invoiceId} not found`);
                return;
            }

            const container = document.getElementById(`items-edit-${invoiceId}`);
            // If not in edit mode, use the display container
            const itemsContainer = container || document.getElementById(`items-display-${invoiceId}`);
            if (!itemsContainer) {
                console.error(`Items container for invoice ${invoiceId} not found`);
                return;
            }

            let subtotal = 0;
            const rows = itemsContainer.querySelectorAll('.item-row');

            // Calculate subtotal from all items
            rows.forEach(row => {
                const quantityInput = row.querySelector('.quantity-input');
                const priceInput = row.querySelector('.price-input');
                const discountInput = row.querySelector('.discount-input');

                // Skip if any required input is missing
                if (!quantityInput || !priceInput || !discountInput) return;

                const quantity = parseFloat(quantityInput.value) || 0;
                const price = parseFloat(priceInput.value) || 0;
                const discount = parseFloat(discountInput.value) || 0;

                subtotal += (price * quantity) - discount;
            });

            // Get advanced payment
            const advancedPaymentInput = form.querySelector('input[name="advanced_payment"]');
            const advancedPayment = advancedPaymentInput ? (parseFloat(advancedPaymentInput.value) || 0) : 0;

            // Calculate final total (ensure it doesn't go negative)
            const finalTotal = Math.max(0, subtotal - advancedPayment);

            // Update displays if they exist
            const totalDisplay = document.getElementById(`total-display-${invoiceId}`);
            const totalInput = document.getElementById(`total-input-${invoiceId}`);

            if (totalDisplay) totalDisplay.textContent = finalTotal.toFixed(2);
            if (totalInput) totalInput.value = finalTotal.toFixed(2);

        } catch (error) {
            console.error('Error calculating total:', error);
            showMessage('Error calculating total. Please check all fields.', 'error');
        }
    }

    function removeItemRow(button, invoiceId) {
        // First check if the button and its parent row exist
        if (!button || !button.closest) return;

        const row = button.closest('.item-row');
        if (!row) return;

        // Get the item ID if it exists (for existing items)
        const itemIdInput = row.querySelector('input[name="item_id[]"]');
        const itemId = itemIdInput ? itemIdInput.value : null;

        if (!confirm('Are you sure you want to remove this product?')) {
            return;
        }

        if (itemId) {
            // For existing items - delete from database first
            fetch('delete_item.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `item_id=${itemId}`
                })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Only remove from UI after successful deletion
                        row.remove();
                        calculateInvoiceTotal(invoiceId);
                        showMessage('Product removed successfully', 'success');
                    } else {
                        throw new Error(data.message || 'Failed to delete product');
                    }
                })
                .catch(error => {
                    showMessage('Error removing product: ' + error.message, 'error');
                    console.error('Error:', error);
                });
        } else {
            // For new items - just remove from UI
            try {
                row.remove();
                calculateInvoiceTotal(invoiceId);
                showMessage('Product removed successfully', 'success');
            } catch (error) {
                showMessage('Error removing product: ' + error.message, 'error');
                console.error('Error:', error);
            }
        }
    }
    // Validate form before submission
    function validateForm(invoiceId) {
        const form = document.getElementById(`form_${invoiceId}`);
        const fullName = form.querySelector('input[name="full_name"]').value.trim();
        const barcode = form.querySelector('input[name="barcode_number"]').value.trim();

        if (!fullName) {
            showMessage('Full Name is required', 'error');
            return false;
        }

        if (!barcode) {
            showMessage('Barcode Number is required', 'error');
            return false;
        }

        // Validate product items
        const container = document.getElementById(`items-edit-${invoiceId}`);
        const rows = container.querySelectorAll('.item-row');

        if (rows.length === 0) {
            showMessage('At least one product is required', 'error');
            return false;
        }

        let isValid = true;
        rows.forEach(row => {
            const productSelect = row.querySelector('.product-select');
            const quantity = row.querySelector('.quantity-input');
            const price = row.querySelector('.price-input');

            if (!productSelect.value) {
                productSelect.style.borderColor = 'red';
                isValid = false;
            } else {
                productSelect.style.borderColor = '';
            }

            if (!quantity.value || parseFloat(quantity.value) <= 0) {
                quantity.style.borderColor = 'red';
                isValid = false;
            } else {
                quantity.style.borderColor = '';
            }

            if (!price.value || parseFloat(price.value) <= 0) {
                price.style.borderColor = 'red';
                isValid = false;
            } else {
                price.style.borderColor = '';
            }
        });

        if (!isValid) {
            showMessage('Please fill all required fields for products', 'error');
        }

        return isValid;
    }

    // Show multiple error messages
    function showErrorMessages(messages) {
        const container = document.getElementById('message-container');
        container.innerHTML = '';

        messages.forEach(message => {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'p-3 mb-2 rounded-md bg-red-100 text-red-800';
            messageDiv.textContent = message;
            container.appendChild(messageDiv);
        });

        setTimeout(() => {
            container.innerHTML = '';
        }, 5000);
    }

    // Show a single message
    function showMessage(message, type) {
        const container = document.getElementById('message-container');
        const messageDiv = document.createElement('div');
        messageDiv.className =
            `p-3 mb-2 rounded-md ${type === 'error' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'}`;
        messageDiv.textContent = message;
        container.appendChild(messageDiv);

        setTimeout(() => {
            messageDiv.remove();
        }, 5000);
    }
    </script>

    <?php include 'scripts.php'; ?>
</body>

</html>