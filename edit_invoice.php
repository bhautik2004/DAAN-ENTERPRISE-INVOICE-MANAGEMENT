<?php
    include 'db.php';
    include 'header.php';
    include 'head.php';

    // Check if invoice ID is provided
    if (! isset($_GET['id']) || ! is_numeric($_GET['id'])) {
        header("Location: orders.php");
        exit();
    }

    $invoice_id = $_GET['id'];

    // Fetch invoice data
    $invoice_sql  = "SELECT * FROM invoices WHERE id = ?";
    $invoice_stmt = $conn->prepare($invoice_sql);
    $invoice_stmt->bind_param("i", $invoice_id);
    $invoice_stmt->execute();
    $invoice_result = $invoice_stmt->get_result();

    if ($invoice_result->num_rows === 0) {
        header("Location: orders.php");
        exit();
    }

    $invoice = $invoice_result->fetch_assoc();

    // Fetch invoice items
    $items_sql = "SELECT ii.*, p.product_name, p.price as product_price
              FROM invoice_items ii
              JOIN products p ON ii.product_id = p.id
              WHERE ii.invoice_id = ?";
    $items_stmt = $conn->prepare($items_sql);
    $items_stmt->bind_param("i", $invoice_id);
    $items_stmt->execute();
    $items_result  = $items_stmt->get_result();
    $invoice_items = $items_result->fetch_all(MYSQLI_ASSOC);

    // Fetch all products for dropdown
    $products_sql    = "SELECT id, product_name, price FROM products ORDER BY product_name";
    $products_result = $conn->query($products_sql);
    $products        = $products_result->fetch_all(MYSQLI_ASSOC);

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $errors = [];

        if (! empty($errors)) {
            $_SESSION['errors'] = $errors;
        } else {
            // Start transaction
            $conn->begin_transaction();

            try {
                // Update invoice header
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
                    $invoice_id
                );
                if (! $stmt->execute()) {
                    throw new Exception("Error updating invoice: " . $stmt->error);
                }

                // Handle invoice items
                $item_ids    = $_POST['item_id'] ?? [];
                $product_ids = $_POST['product_id'] ?? [];
                $quantities  = $_POST['quantity'] ?? [];
                $discounts   = $_POST['discount'] ?? [];

                // First delete items that were removed
                $existing_items  = [];
                $existing_result = $conn->query("SELECT id FROM invoice_items WHERE invoice_id = $invoice_id");
                while ($item = $existing_result->fetch_assoc()) {
                    $existing_items[] = $item['id'];
                }

                $deleted_items = array_diff($existing_items, $item_ids);
                if (! empty($deleted_items)) {
                    $delete_sql = "DELETE FROM invoice_items WHERE id IN (" . implode(',', $deleted_items) . ")";
                    if (! $conn->query($delete_sql)) {
                        throw new Exception("Error deleting items: " . $conn->error);
                    }
                }

                // Update or insert items
                $total_amount = 0;
                for ($i = 0; $i < count($product_ids); $i++) {
                    $item_id    = $item_ids[$i] ?? null;
                    $product_id = $product_ids[$i] ?? null;
                    $quantity   = $quantities[$i] ?? 0;
                    $discount   = $discounts[$i] ?? 0;

                    // Get product price from products table
                    $price_sql  = "SELECT price FROM products WHERE id = ?";
                    $price_stmt = $conn->prepare($price_sql);
                    $price_stmt->bind_param("i", $product_id);
                    $price_stmt->execute();
                    $price_result = $price_stmt->get_result();
                    $product      = $price_result->fetch_assoc();
                    $price        = $product['price'];

                    $row_total = ($price * $quantity) - $discount;
                    $total_amount += $row_total;

                    if (empty($product_id) || empty($quantity)) {
                        continue;
                    }

                    if (! empty($item_id) && is_numeric($item_id)) {
                        // Update existing item
                        $updateItem = "UPDATE invoice_items SET
                            product_id = ?, quantity = ?, discount = ?
                            WHERE id = ?";
                        $stmt = $conn->prepare($updateItem);
                        $stmt->bind_param("iidi", $product_id, $quantity, $discount, $item_id);
                    } else {
                        // Insert new item
                        $insertItem = "INSERT INTO invoice_items
                            (invoice_id, product_id, quantity, discount)
                            VALUES (?, ?, ?, ?)";
                        $stmt = $conn->prepare($insertItem);
                        $stmt->bind_param("iiid", $invoice_id, $product_id, $quantity, $discount);
                    }

                    if (! $stmt->execute()) {
                        throw new Exception("Error updating items: " . $stmt->error);
                    }
                }

                // Reduce advanced payment from total
                $advanced_payment = floatval($_POST['advanced_payment']);
                $final_total      = $total_amount - $advanced_payment;
                if ($final_total < 0) {
                    $final_total = 0; // Prevent negative totals
                }

                // Update the invoice total
                $updateTotal = "UPDATE invoices SET total_amount = ? WHERE id = ?";
                $stmt        = $conn->prepare($updateTotal);
                $stmt->bind_param("di", $final_total, $invoice_id);
                $stmt->execute();

                $conn->commit();
                $_SESSION['success'] = "Invoice updated successfully!";
                header("Location: orders.php");
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['error'] = "Error updating invoice: " . $e->getMessage();
            }
        }
    }
?>

<body class="bg-gray-100 flex">
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 pl-56 m-4">
        <!-- Message Container -->
        <div id="message-container" class="fixed top-20 right-4 z-50">
            <?php if (isset($_SESSION['error'])): ?>
            <div class="p-3 mb-2 rounded-md bg-red-100 text-red-800">
                <?php echo $_SESSION['error'];unset($_SESSION['error']); ?>
            </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['errors'])): ?>
            <?php foreach ($_SESSION['errors'] as $error): ?>
            <div class="p-3 mb-2 rounded-md bg-red-100 text-red-800">
                <?php echo $error; ?>
            </div>
            <?php endforeach; ?>
            <?php unset($_SESSION['errors']); ?>
            <?php endif; ?>
        </div>

        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold text-[var(--primary-color)]">Edit Invoice #<?php echo $invoice_id; ?></h2>
            <a href="orders.php" class="bg-gray-500 text-white px-4 py-2 rounded-md">Back to Orders</a>
        </div>

        <form method="POST" id="invoice-form" class="bg-white p-6 rounded-lg shadow-md">
            <input type="hidden" name="id" value="<?php echo $invoice_id; ?>">

            <!-- Invoice Header Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                <div class="form-group">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name*</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($invoice['full_name']); ?>"
                        class="w-full p-2 border border-gray-300 rounded-md" required>
                </div>

                <div class="form-group">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mobile*</label>
                    <input type="text" name="mobile" value="<?php echo htmlspecialchars($invoice['mobile']); ?>"
                        class="w-full p-2 border border-gray-300 rounded-md" required>
                </div>

                <div class="form-group">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Customer Id</label>
                    <input type="text" name="barcode_number"
                        value="<?php echo htmlspecialchars($invoice['customer_id']); ?>"
                        class="w-full p-2 border border-gray-300 rounded-md" required disabled>
                </div>

                <div class="form-group">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Barcode Number*</label>
                    <input type="text" name="barcode_number"
                        value="<?php echo htmlspecialchars($invoice['barcode_number']); ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div class="form-group">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full p-2 border border-gray-300 rounded-md">
                        <option value="Pending" <?php echo $invoice['status'] === 'Pending' ? 'selected' : ''; ?>>
                            Pending</option>
                        <option value="Completed" <?php echo $invoice['status'] === 'Completed' ? 'selected' : ''; ?>>
                            Completed</option>
                        <option value="Canceled" <?php echo $invoice['status'] === 'Canceled' ? 'selected' : ''; ?>>
                            Canceled</option>
                        <option value="Returned" <?php echo $invoice['status'] === 'Returned' ? 'selected' : ''; ?>>
                            Returned</option>
                        <option value="Dispatched" <?php echo $invoice['status'] === 'Dispatched' ? 'selected' : ''; ?>>
                            Dispatched
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Employee Name</label>
                    <input type="text" name="employee_name"
                        value="<?php echo htmlspecialchars($invoice['employee_name']); ?>"
                        class="w-full p-2 border border-gray-300 rounded-md" readonly>
                </div>

                <div class="form-group">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Advanced Payment</label>
                    <input type="number" name="advanced_payment"
                        value="<?php echo htmlspecialchars($invoice['advanced_payment']); ?>"
                        class="w-full p-2 border border-gray-300 rounded-md" step="0.01" min="0">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Repeated Order</label>
                    <input type="text" name="is_repeated_order" id="is_repeated_order"
                        class="w-full p-2 border border-gray-300 rounded-md bg-gray-100"
                        value="<?php echo htmlspecialchars($invoice['is_repeated_order']); ?>" readonly>
                </div>
            </div>

            <!-- Address Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="form-group">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address 1</label>
                    <input type="text" name="address1" value="<?php echo htmlspecialchars($invoice['address1']); ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div class="form-group">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address 2</label>
                    <input type="text" name="address2" value="<?php echo htmlspecialchars($invoice['address2']); ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div class="form-group">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pincode</label>
                    <input type="text" name="pincode" value="<?php echo htmlspecialchars($invoice['pincode']); ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div class="form-group">
                    <label class="block text-sm font-medium text-gray-700 mb-1">District</label>
                    <input type="text" name="district" value="<?php echo htmlspecialchars($invoice['district']); ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div class="form-group">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sub District</label>
                    <input type="text" name="sub_district"
                        value="<?php echo htmlspecialchars($invoice['sub_district']); ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div class="form-group">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Village</label>
                    <input type="text" name="village" value="<?php echo htmlspecialchars($invoice['village']); ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div class="form-group">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Post Name</label>
                    <input type="text" name="post_name" value="<?php echo htmlspecialchars($invoice['post_name']); ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                </div>

                <div class="form-group">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mobile 2</label>
                    <input type="text" name="mobile2" value="<?php echo htmlspecialchars($invoice['mobile2']); ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                </div>
            </div>

            <!-- Invoice Items Section -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Products</h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="py-2 px-4 border">Product</th>
                                <th class="py-2 px-4 border">Quantity</th>
                                <th class="py-2 px-4 border">Price</th>
                                <th class="py-2 px-4 border">Discount</th>
                                <th class="py-2 px-4 border">Total</th>
                                <th class="py-2 px-4 border">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="items-table-body">
                            <?php foreach ($invoice_items as $index => $item): ?>
                            <tr class="item-row" data-index="<?php echo $index; ?>">
                                <td class="py-2 px-4 border">
                                    <input type="hidden" name="item_id[]" value="<?php echo $item['id']; ?>">
                                    <select name="product_id[]" class="w-full p-2 border rounded product-select"
                                        required>
                                        <option value="">Select Product</option>
                                        <?php foreach ($products as $product): ?>
                                        <option value="<?php echo $product['id']; ?>"
                                            data-price="<?php echo $product['price']; ?>"
                                            <?php echo $item['product_id'] == $product['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($product['product_name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="py-2 px-4 border">
                                    <input type="number" name="quantity[]" value="<?php echo $item['quantity']; ?>"
                                        class="w-full p-2 border rounded quantity-input" min="1" required>
                                </td>
                                <td class="py-2 px-4 border">
                                    <input type="number" name="price_display[]"
                                        value="<?php echo $item['product_price']; ?>"
                                        class="w-full p-2 border rounded price-display" step="0.01" min="0" readonly>
                                </td>
                                <td class="py-2 px-4 border">
                                    <input type="number" name="discount[]" value="<?php echo $item['discount']; ?>"
                                        class="w-full p-2 border rounded discount-input" step="0.01" min="0">
                                </td>
                                <td class="py-2 px-4 border row-total">
                                    <?php echo number_format(($item['product_price'] * $item['quantity']) - $item['discount'], 2); ?>
                                </td>
                                <td class="py-2 px-4 border">
                                    <button type="button" class="remove-item-btn text-red-500 hover:text-red-700">
                                        Remove
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    <button type="button" id="add-item-btn" class="bg-blue-500 text-white px-4 py-2 rounded-md">
                        Add Product
                    </button>
                </div>
            </div>

            <!-- Totals Section -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-gray-100 p-4 rounded-md">
                    <h4 class="font-semibold text-gray-800 mb-2">Invoice Summary</h4>

                    <div class="flex justify-between mb-1">
                        <span>Subtotal:</span>
                        <span id="subtotal-display"><?php echo number_format($invoice['total_amount'], 2); ?></span>
                    </div>

                    <div class="flex justify-between mb-1">
                        <span>Advanced Payment:</span>
                        <span id="advanced-display"><?php echo number_format($invoice['advanced_payment'], 2); ?></span>
                    </div>

                    <div class="flex justify-between mb-1">
                        <span>Balance Due:</span>
                        <span
                            id="balance-display"><?php echo number_format($invoice['total_amount'] - $invoice['advanced_payment'], 2); ?></span>
                    </div>

                    <input type="hidden" name="total_amount" id="total-amount"
                        value="<?php echo $invoice['total_amount']; ?>">
                </div>
            </div>
            <!-- Form Actions -->
            <div class="flex justify-end space-x-4">
                <button type="button" onclick="window.location.href='orders.php'"
                    class="bg-gray-500 text-white px-6 py-2 rounded-md">
                    Cancel
                </button>
                <button type="submit" class="bg-[var(--primary-color)] text-white px-6 py-2 rounded-md">
                    Save Invoice
                </button>
            </div>
        </form>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add new item row
        document.getElementById('add-item-btn').addEventListener('click', function() {
            const tbody = document.getElementById('items-table-body');
            const newRow = document.createElement('tr');
            newRow.className = 'item-row';
            newRow.innerHTML = `
                <td class="py-2 px-4 border">
                    <input type="hidden" name="item_id[]" value="new">
                    <select name="product_id[]" class="w-full p-2 border rounded product-select" required>
                        <option value="">Select Product</option>
                        <?php foreach ($products as $product): ?>
                        <option value="<?php echo $product['id']; ?>"
                            data-price="<?php echo $product['price']; ?>">
                            <?php echo htmlspecialchars($product['product_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td class="py-2 px-4 border">
                    <input type="number" name="quantity[]" value="1"
                        class="w-full p-2 border rounded quantity-input" min="1" required>
                </td>
                <td class="py-2 px-4 border">
                    <input type="number" name="price_display[]" value="0"
                        class="w-full p-2 border rounded price-display" step="0.01" min="0" readonly>
                </td>
                <td class="py-2 px-4 border">
                    <input type="number" name="discount[]" value="0"
                        class="w-full p-2 border rounded discount-input" step="0.01" min="0">
                </td>
                <td class="py-2 px-4 border row-total">0.00</td>
                <td class="py-2 px-4 border">
                    <button type="button" class="remove-item-btn text-red-500 hover:text-red-700">
                        Remove
                    </button>
                </td>
            `;
            tbody.appendChild(newRow);

            // Set up event listeners for the new row
            setupRowEventListeners(newRow);
        });

        // Set up event listeners for existing rows
        document.querySelectorAll('.item-row').forEach(row => {
            setupRowEventListeners(row);
        });

        // Set up event listener for advanced payment
        document.querySelector('input[name="advanced_payment"]').addEventListener('input', calculateTotals);

        // Initial calculation
        calculateTotals();
    });

    function setupRowEventListeners(row) {
        // Product select change
        row.querySelector('.product-select').addEventListener('change', function() {
            const price = this.options[this.selectedIndex]?.dataset.price;
            if (price) {
                row.querySelector('.price-display').value = price;
            }
            calculateRowTotal(row);
        });

        // Quantity, discount inputs
        row.querySelector('.quantity-input').addEventListener('input', () => calculateRowTotal(row));
        row.querySelector('.discount-input').addEventListener('input', () => calculateRowTotal(row));

        // Remove button
        row.querySelector('.remove-item-btn').addEventListener('click', function() {
            if (confirm('Are you sure you want to remove this product?')) {
                row.remove();
                calculateTotals();
            }
        });
    }

    function calculateRowTotal(row) {
        const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-display').value) || 0;
        const discount = parseFloat(row.querySelector('.discount-input').value) || 0;

        const rowTotal = (price * quantity) - discount;
        row.querySelector('.row-total').textContent = rowTotal.toFixed(2);

        calculateTotals();
    }

    function calculateTotals() {
        let subtotal = 0;

        document.querySelectorAll('.item-row').forEach(row => {
            const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
            const price = parseFloat(row.querySelector('.price-display').value) || 0;
            const discount = parseFloat(row.querySelector('.discount-input').value) || 0;

            const rowTotal = (price * quantity) - discount;
            subtotal += rowTotal;
            row.querySelector('.row-total').textContent = rowTotal.toFixed(2);
        });

        const advancedPayment = parseFloat(document.querySelector('input[name="advanced_payment"]').value) || 0;
        const balance = subtotal - advancedPayment;

        // Update displays
        document.getElementById('subtotal-display').textContent = subtotal.toFixed(2);
        document.getElementById('advanced-display').textContent = advancedPayment.toFixed(2);
        document.getElementById('balance-display').textContent = balance.toFixed(2);

        // Update the hidden field that will be submitted
        document.getElementById('total-amount').value = subtotal.toFixed(2);
    }
    </script>

    <?php include 'scripts.php'; ?>
</body>

</html>