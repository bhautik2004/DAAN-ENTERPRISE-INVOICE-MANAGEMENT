<?php
    include 'db.php'; // Database connection

    // Fetch products from the database
    $product_result = $conn->query("SELECT id, product_name, price FROM products");

    $current_user_name = $_SESSION['user'];
    $message           = "";
    $is_repeated_order = "no"; // Default value

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['Create_invoice'])) {
        // Customer details
        $mobile            = $_POST['mobile'];
        $full_name         = $_POST['full_name'];
        $address1          = $_POST['address1'];
        $address2          = $_POST['address2'];
        $pincode           = $_POST['pincode'];
        $district          = $_POST['district'];
        $sub_district      = $_POST['sub_district'];
        $village           = $_POST['village'];
        $post_name         = $_POST['post_name'];
        $mobile2           = $_POST['mobile2'];
        $barcode_number    = $_POST['barcode_number'];
        $employee_name     = $_POST['employee_name'];
        $advanced_payment  = (float) $_POST['advanced_payment']; // Convert to float
        $remark = $_POST['Remark'];
        $status            = "Pending";
        $is_repeated_order = $_POST['is_repeated_order'] ?? 'no'; // Get repeated order status
        date_default_timezone_set('Asia/Kolkata');
        $created_at = date("Y-m-d H:i:s");

        // Start transaction
        $conn->begin_transaction();

        try {
            // Insert invoice (without product-related fields)
            $query = "INSERT INTO invoices (mobile, full_name, address1, address2, pincode, district,
              sub_district, village, post_name, mobile2, barcode_number, employee_name,
              customer_id, advanced_payment, status, created_at, is_repeated_order,Remark)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)";

            $stmt        = $conn->prepare($query);
            $customer_id = $_POST['customer_id']; // Get from form
            $stmt->bind_param("sssssssssssssdssss", $mobile, $full_name, $address1, $address2, $pincode,
                $district, $sub_district, $village, $post_name, $mobile2, $barcode_number,
                $employee_name, $customer_id, $advanced_payment, $status, $created_at, $is_repeated_order,$remark);
            if (! $stmt->execute()) {
                throw new Exception("Error creating invoice: " . $stmt->error);
            }
            $invoice_id = $conn->insert_id;
            $stmt->close();

            // Process each product in invoice_items
            $total_amount = 0;
            foreach ($_POST['product_id'] as $key => $product_id) {
                $quantity = $_POST['quantity'][$key];
                $discount = isset($_POST['discount'][$key]) ? (float) $_POST['discount'][$key] : 0;

                // Get product price
                $product_query = "SELECT price FROM products WHERE id = ?";
                $stmt          = $conn->prepare($product_query);
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $result  = $stmt->get_result();
                $product = $result->fetch_assoc();
                $stmt->close();

                if (! $product) {
                    throw new Exception("Invalid product selected: ID $product_id");
                }

                $price      = $product['price'];
                $item_total = ($price * $quantity) - $discount;
                $total_amount += $item_total;

                // Insert invoice item without price
                $item_query = "INSERT INTO invoice_items (invoice_id, product_id, quantity, discount)
                           VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($item_query);
                $stmt->bind_param("iiid", $invoice_id, $product_id, $quantity, $discount);

                if (! $stmt->execute()) {
                    throw new Exception("Error adding invoice item: " . $stmt->error);
                }
                $stmt->close();
            }

            // Subtract advanced payment from total
            $final_amount = $total_amount - $advanced_payment;
            if ($final_amount < 0) {
                $final_amount = 0;
            }
            // Prevent negative total

            // Update invoice with total amount (excluding advanced payment)
            $update_query = "UPDATE invoices SET total_amount = ? WHERE id = ?";
            $stmt         = $conn->prepare($update_query);
            $stmt->bind_param("di", $final_amount, $invoice_id);

            if (! $stmt->execute()) {
                throw new Exception("Error updating invoice total: " . $stmt->error);
            }
            $stmt->close();

            $conn->commit();
            $message = "Invoice #$invoice_id created successfully with barcode: $barcode_number";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error: " . $e->getMessage();
        }
    }
?>
<?php
// At the top of createinvoice.php, after database connection
$is_repeated_order = "no"; // Default value
$prefilled_data = [];

// Handle clone request
if (isset($_GET['clone_id'])) {
    $clone_id = $_GET['clone_id'];
    $query = "SELECT * FROM invoices WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $clone_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $prefilled_data = $result->fetch_assoc();
        $is_repeated_order = "yes"; // Mark as repeated order
    }
    $stmt->close(); // <-- ADD THIS LINE
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Invoice</title>
    <style>
    .suggestion-dropdown {
        position: absolute;
        background-color: white;
        border: 1px solid #ccc;
        border-radius: 4px;
        max-height: 200px;
        overflow-y: auto;
        width: calc(100% - 2px);
        z-index: 1000;
        box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        display: none;
        /* Hide by default */
    }

    .suggestion-item {
        padding: 8px;
        cursor: pointer;
        border-bottom: 1px solid #eee;
    }

    .suggestion-item:last-child {
        border-bottom: none;
    }

    .suggestion-item:hover {
        background-color: #f0f0f0;
    }
    </style>
    <script>
    history.pushState(null, null, location.href);
    window.onpopstate = function() {
        window.location.href = "createinvoice.php"; // Redirect to createinvoice.php
    };



    function fetchCustomerData() {
        var mobileNumber = document.getElementById("mobileNumber").value;

        if (mobileNumber.trim() === "") {
            alert("Please enter a mobile number.");
            return;
        }

        fetch("invoice.php?mobile=" + mobileNumber)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert("Customer not found.");
                    document.getElementById("fullName").value = "";
                    document.getElementById("address1").value = "";
                    document.getElementById("address2").value = "";
                    document.getElementById("pincode").value = "";
                    document.getElementById("district").value = "";
                    document.getElementById("subDistrict").value = "";
                } else {
                    document.getElementById("fullName").value = data.full_name;
                    document.getElementById("address1").value = data.address1;
                    document.getElementById("address2").value = data.address2;
                    document.getElementById("pincode").value = data.pincode;
                    document.getElementById("district").value = data.district;
                    document.getElementById("subDistrict").value = data.sub_district;
                }
            })
            .catch(error => console.error("Error:", error));
    }

    function submitInvoice(event) {
        event.preventDefault(); // Prevent form submission

        let formData = new FormData(document.getElementById("invoiceForm"));

        fetch("invoice.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.success);
                    document.getElementById("invoiceForm").reset();
                } else {
                    alert(data.error);
                }
            })
            .catch(error => console.error("Error:", error));
    }
    document.addEventListener('DOMContentLoaded', function() {
        const villageInput = document.getElementById('village');
        const districtInput = document.getElementById('district');
        const subDistrictInput = document.getElementById('subDistrict');

        if (villageInput) {
            villageInput.addEventListener('input', (e) => {
                fetchSuggestions(e.target, 'village');
            });
        } else {
            console.error('Village input field not found');
        }

        if (districtInput) {
            districtInput.addEventListener('input', (e) => {
                fetchSuggestions(e.target, 'district');
            });
        } else {
            console.error('District input field not found');
        }

        if (subDistrictInput) {
            subDistrictInput.addEventListener('input', (e) => {
                fetchSuggestions(e.target, 'sub_district');
            });
        } else {
            console.error('Sub District input field not found');
        }
    });

    function fetchSuggestions(inputField, suggestionType) {
        const inputValue = inputField.value;
        const suggestionList = document.getElementById(`${suggestionType}-suggestions`); // Fix syntax

        if (inputValue.length >= 1) {
            fetch(`fetch_suggestions.php?type=${suggestionType}&query=${inputValue}`)
                .then(response => response.json())
                .then(data => {
                    const suggestions = data.suggestions;
                    suggestionList.innerHTML = ''; // Clear previous suggestions

                    if (suggestions.length > 0) {
                        suggestionList.style.display = "block"; // Show dropdown when suggestions exist
                    } else {
                        suggestionList.style.display = "none"; // Hide dropdown when empty
                    }

                    suggestions.forEach(suggestion => {
                        const option = document.createElement('div');
                        option.textContent = suggestion;
                        option.classList.add('suggestion-item');
                        option.addEventListener('click', () => {
                            inputField.value = suggestion;
                            suggestionList.innerHTML = ''; // Clear suggestions after selection
                            suggestionList.style.display = "none"; // Hide after selection
                        });
                        suggestionList.appendChild(option);
                    });
                })
                .catch(error => console.error('Error fetching suggestions:', error));
        } else {
            suggestionList.innerHTML = ''; // Clear suggestions if input is empty
            suggestionList.style.display = "none"; // Hide dropdown when input is empty
        }
    }
    </script>
</head>

<body>
    <section>
        <h3 class="text-xl font-bold text-[var(--primary-color)] mb-4 text-center">Create Invoice</h3>

        <div class="bg-white p-6 rounded-md shadow-md">
            <!-- Message Display -->
            <?php if (! empty($message)): ?>
            <div class="text-center mb-4 text-green-600 font-bold">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <!-- Customer Search Form -->
            <form method="POST" class="mb-6">
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-1" for="mobile">Enter Mobile No to Fetch Data</label>
                    <div class="flex items-center space-x-2">
                        <input type="tel" id="mobile" name="search_mobile"
                            class="w-80 p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            pattern="[6-9]{1}[0-9]{9}"
                            title="Please enter a valid 10-digit mobile number starting with 6, 7, 8, or 9" required>
                        <button type="submit" name="fetch_customer"
                            class="bg-[var(--primary-color)] hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors duration-200">
                            Fetch
                        </button>
                    </div>
                </div>
            </form>

            <!-- Main Invoice Form -->
            <form id="invoiceForm" method="POST">
                <!-- Customer Information Section -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Full Name</label>
                        <input type="text" id="fullName" name="full_name"
                            class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            value="<?php echo isset($prefilled_data['full_name']) ? htmlspecialchars($prefilled_data['full_name']) : ''; ?>"
                            required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Village</label>
                        <div class="relative">
                            <input type="text" id="village" name="village"
                                class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                value="<?php echo isset($prefilled_data['village']) ? htmlspecialchars($prefilled_data['village']) : ''; ?>">
                            <div id="village-suggestions" class="suggestion-dropdown"></div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Address 1</label>
                        <input type="text" id="address1" name="address1"
                            class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            value="<?php echo isset($prefilled_data['address1']) ? htmlspecialchars($prefilled_data['address1']) : ''; ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Address 2</label>
                        <input type="text" id="address2" name="address2"
                            class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            value="<?php echo isset($prefilled_data['address2']) ? htmlspecialchars($prefilled_data['address2']) : ''; ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Post</label>
                        <div class="relative">
                            <input type="text" id="post_name" name="post_name"
                                class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                value="<?php echo isset($prefilled_data['post_name']) ? htmlspecialchars($prefilled_data['post_name']) : ''; ?>">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Taluka</label>
                        <div class="relative">
                            <input type="text" id="subDistrict" name="sub_district"
                                class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                value="<?php echo isset($prefilled_data['sub_district']) ? htmlspecialchars($prefilled_data['sub_district']) : ''; ?>">
                            <div id="sub_district-suggestions" class="suggestion-dropdown"></div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">District</label>
                        <div class="relative">
                            <input type="text" id="district" name="district"
                                class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                value="<?php echo isset($prefilled_data['district']) ? htmlspecialchars($prefilled_data['district']) : ''; ?>">
                            <div id="district-suggestions" class="suggestion-dropdown"></div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Pincode</label>
                        <div class="flex items-center space-x-2">
                            <input type="text" id="pincode" name="pincode"
                                class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                pattern="[1-9]{1}[0-9]{5}" maxlength="6"
                                title="Pincode must be a 6-digit number starting from 1-9"
                                value="<?php echo isset($prefilled_data['pincode']) ? htmlspecialchars($prefilled_data['pincode']) : ''; ?>"
                                required>
                            <button type="button" onclick="checkPincode()"
                                class="bg-[var(--primary-color)] hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors duration-200">
                                Check
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Mobile No</label>
                        <input type="text" id="mobileNumber" name="mobile"
                            class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            pattern="[6-9]{1}[0-9]{9}" required maxlength="10"
                            title="Mobile number must be 10 digits and start with 6-9"
                            value="<?php echo isset($prefilled_data['mobile']) ? htmlspecialchars($prefilled_data['mobile']) : ''; ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Mobile No 2</label>
                        <input type="text" id="mobile2" name="mobile2"
                            class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            pattern="[6-9]{1}[0-9]{9}" maxlength="10"
                            title="Mobile number must be 10 digits and start with 6-9"
                            value="<?php echo isset($prefilled_data['mobile2']) ? htmlspecialchars($prefilled_data['mobile2']) : ''; ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Barcode Number</label>
                        <?php if ($_SESSION['role'] === 'Admin'): ?>
                        <input type="text" name="barcode_number"
                            class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            value="<?php echo isset($prefilled_data['barcode_number']) ? htmlspecialchars($prefilled_data['barcode_number']) : ''; ?>">
                        <?php else: ?>
                        <input type="text" name="barcode_number"
                            class="w-full p-2 border border-gray-300 rounded-md bg-gray-100"
                            value="<?php echo isset($prefilled_data['barcode_number']) ? htmlspecialchars($prefilled_data['barcode_number']) : ''; ?>"
                            readonly>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Customer ID</label>
                        <input type="text" name="customer_id" id="customer_id"
                            class="w-full p-2 border border-gray-300 rounded-md bg-gray-100" readonly required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Employee Name</label>
                        <input type="text" name="employee_name"
                            class="w-full p-2 border border-gray-300 rounded-md bg-gray-100"
                            value="<?php echo htmlspecialchars($current_user_name); ?>" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Repeated Order</label>
                        <input type="text" name="is_repeated_order" id="is_repeated_order"
                            class="w-full p-2 border border-gray-300 rounded-md bg-gray-100"
                            value="<?php echo $is_repeated_order; ?>" readonly>
                    </div>
                </div>

                <!-- Products Section -->
                <div class="mb-6">
                    <h4 class="text-lg font-semibold mb-3 text-gray-700 border-b pb-2">Products</h4>

                    <div id="products-container">
                        <!-- Product Row Template -->
                        <div class="product-row grid grid-cols-1 md:grid-cols-4 gap-4 mb-4 p-4 bg-gray-50 rounded-lg">
                            <div>
                                <label class="block text-sm font-semibold mb-1">Product</label>
                                <select name="product_id[]"
                                    class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    required>
                                    <?php
                                        // Run the query again if needed
                                        $product_result = $conn->query("SELECT id, product_name, price FROM products");
                                    ?>
                                    <option value="">Select Product</option>
                                    <?php while ($row = $product_result->fetch_assoc()): ?>
                                    <option value="<?php echo $row['id']; ?>" data-price="<?php echo $row['price']; ?>">
                                        <?php echo htmlspecialchars($row['product_name']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold mb-1">Quantity</label>
                                <input type="number" name="quantity[]"
                                    class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    min="1" step="1" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-1">Discount (₹)</label>
                                <input type="number" name="discount[]"
                                    class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    min="0" step="0.01" value="0">
                            </div>
                        </div>
                    </div>

                    <button type="button" id="add-product"
                        class="flex items-center justify-center bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md transition-colors duration-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                                clip-rule="evenodd" />
                        </svg>
                        Add Another Product
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <!-- Advanced Payment Section -->
                    <div class="mb-6">
                        <label class="block text-sm font-semibold mb-1">Advanced Payment (₹)</label>
                        <input type="number" name="advanced_payment"
                            class="w-full  p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Special Remark For Admin For This Invoice</label>
                        <input type="text" name="Remark"
                            class="w-full  p-2 border border-gray-300 rounded-md">
                    </div>
                </div>
                <!-- Submit Button -->
                <div class="flex justify-end">
                    <button type="submit" name="Create_invoice"
                        class="bg-[var(--primary-color)] hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-colors duration-200">
                        Create Invoice
                    </button>
                </div>
            </form>
        </div>
    </section>
    <style>
    .suggestion-dropdown {
        position: absolute;
        background-color: white;
        border: 1px solid #ccc;
        border-radius: 4px;
        max-height: 200px;
        overflow-y: auto;
        width: calc(100% - 2px);
        z-index: 1000;
        box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        display: none;
    }

    .suggestion-item {
        padding: 8px;
        cursor: pointer;
        border-bottom: 1px solid #eee;
    }

    .suggestion-item:last-child {
        border-bottom: none;
    }

    .suggestion-item:hover {
        background-color: #f0f0f0;
    }

    .product-row {
        transition: all 0.3s ease;
    }

    #add-product:hover {
        transform: translateY(-1px);
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        fetchActiveDistributorCustomerId();
        // Add product row functionality
        const addProductBtn = document.getElementById('add-product');
        const productsContainer = document.getElementById('products-container');


        addProductBtn.addEventListener('click', function() {
            const productRow = document.querySelector('.product-row').cloneNode(true);

            // Clear all input values in the new row
            productRow.querySelectorAll('input').forEach(input => input.value = '');
            productRow.querySelector('select').selectedIndex = 0;

            // Add remove button to new rows (except the first one)
            if (productsContainer.children.length > 0) {
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'mt-2 text-red-500 hover:text-red-700 text-sm font-medium';
                removeBtn.innerHTML = 'Remove Product';
                removeBtn.addEventListener('click', function() {
                    productRow.remove();
                });

                const actionDiv = document.createElement('div');
                actionDiv.className = 'md:col-span-4 flex justify-end';
                actionDiv.appendChild(removeBtn);

                productRow.appendChild(actionDiv);
            }

            productsContainer.appendChild(productRow);
        });

        // Calculate total when product details change
        document.addEventListener('change', function(e) {
            if (e.target.matches(
                    'select[name="product_id[]"], input[name="quantity[]"], input[name="discount[]"]'
                )) {
                calculateTotals();
            }

        });

        function fetchActiveDistributorCustomerId() {
            fetch('get_active_distributor.php')
                .then(response => response.json())
                .then(data => {
                    if (data.customer_id) {
                        document.getElementById('customer_id').value = data.customer_id;
                    }
                })
                .catch(error => console.error('Error fetching distributor:', error));
        }

        function calculateTotals() {
            let subtotal = 0;

            document.querySelectorAll('.product-row').forEach(row => {
                const select = row.querySelector('select[name="product_id[]"]');
                const quantityInput = row.querySelector('input[name="quantity[]"]');
                const discountInput = row.querySelector('input[name="discount[]"]');

                if (select.selectedIndex > 0 && quantityInput.value) {
                    const price = parseFloat(select.options[select.selectedIndex].dataset.price);
                    const quantity = parseFloat(quantityInput.value);
                    const discount = discountInput.value ? parseFloat(discountInput.value) : 0;

                    subtotal += (price * quantity) - discount;
                }
            });

            // You can update a total display element here if you add one
        }
    });
    async function checkPincode() {
        const pincode = document.getElementById('pincode').value;

        if (pincode) {
            try {
                const response = await fetch(`https://api.postalpincode.in/pincode/${pincode}`);
                const data = await response.json();

                if (data[0].Status === "Success") {
                    alert('Valid Pincode');
                } else {
                    alert('Invalid Pincode. Please enter a correct one.');
                }
            } catch (error) {
                alert('Error fetching pincode data. Please try again.');
            }
        } else {
            alert('Please enter a pincode.');
        }
    }
    </script>

    <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['fetch_customer'])) {
            $mobile = $_POST['search_mobile'];
            $query  = "SELECT * FROM invoices WHERE mobile = '$mobile'";
            $result = $conn->query($query);

            if ($result->num_rows > 0) {
                $customer = $result->fetch_assoc();
                echo "<script>
        document.getElementById('mobileNumber').value = '" . addslashes($customer['mobile'] ?? '') . "';
        document.getElementById('fullName').value = '" . addslashes($customer['full_name'] ?? '') . "';
        document.getElementById('address1').value = '" . addslashes($customer['address1'] ?? '') . "';
        document.getElementById('address2').value = '" . addslashes($customer['address2'] ?? '') . "';
        document.getElementById('pincode').value = '" . addslashes($customer['pincode'] ?? '') . "';
        document.getElementById('district').value = '" . addslashes($customer['district'] ?? '') . "';
        document.getElementById('subDistrict').value = '" . addslashes($customer['sub_district'] ?? '') . "';
        document.getElementById('village').value = '" . addslashes($customer['village'] ?? '') . "';
        document.getElementById('post_name').value = '" . addslashes($customer['post_name'] ?? '') . "';
        document.getElementById('mobile2').value = '" . addslashes($customer['mobile2'] ?? '') . "';
        document.getElementById('is_repeated_order').value = 'yes';
        </script>";
                $is_repeated_order = "yes";
            } else {
                echo "<script>
        alert('Customer not found!');
        document.getElementById('is_repeated_order').value = 'no';
        </script>";
                $is_repeated_order = "no";
            }
        }

    ?>

    <?php
        // Fetch customer details if mobile number is provided
        if (isset($_GET['mobile'])) {
            $mobile = $_GET['mobile'];
            $stmt   = $conn->prepare("SELECT * FROM invoice WHERE mobile_number = ?");
            $stmt->bind_param("s", $mobile);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                echo json_encode($row);
            } else {
                echo json_encode(['error' => 'Customer not found']);
            }
            $stmt->close();   // <-- ADD THIS LINE
    $conn->close();   // <-- ADD THIS LINE
            exit;
        }
        $conn->close();
    ?>
</body>

</html>