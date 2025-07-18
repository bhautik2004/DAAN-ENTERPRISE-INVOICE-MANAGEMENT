<?php include 'header.php'; ?>
<?php include 'head.php'; ?>
<?php
    if ($_SESSION['role'] != "Admin") {
        header("Location: index.php");
        exit();
    }

?>

<body class="bg-gray-100 flex">
    <?php include 'sidebar.php'; ?>
    <main class="flex-1 ml-64 p-8">
        <h2 class="text-2xl font-bold text-[var(--primary-color)] mb-4 text-center">Product List</h2>

        <?php
            include 'db.php';

            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $limit  = 8;
            $page   = isset($_GET['page']) ? (int) $_GET['page'] : 1;
            $offset = ($page - 1) * $limit;

            $sql        = "SELECT id, product_name, sku, price, weight FROM products WHERE product_name LIKE ? LIMIT ? OFFSET ?";
            $stmt       = $conn->prepare($sql);
            $searchTerm = "%$search%";
            $limitInt   = (int) $limit;                                   // Convert to int and store in a variable
            $offsetInt  = (int) $offset;                                  // Convert to int and store in a variable
            $stmt->bind_param("sii", $searchTerm, $limitInt, $offsetInt); // Use the variables
            $stmt->execute();
            $result = $stmt->get_result();

            $countQuery = "SELECT COUNT(id) AS total FROM products WHERE product_name LIKE ?";
            $countStmt  = $conn->prepare($countQuery);
            $countStmt->bind_param("s", $searchTerm);
            $countStmt->execute();
            $countResult  = $countStmt->get_result();
            $totalRecords = $countResult->fetch_assoc()['total'];
            $totalPages   = ceil($totalRecords / $limit);
        ?>

        <form method="GET" class="mb-4 flex">
            <input type="text" name="search" placeholder="Search Product..."
                class="w-full p-2 border border-gray-300 rounded-l-md" value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="bg-[var(--primary-color)] text-white px-4 py-2 rounded-r-md">
                Search
            </button>
        </form>
        <button class="bg-green-700 text-white mt-5 mb-5 rounded-md cursor-pointer">
            <a href="addproduct.php" class="flex items-center space-x-2 p-2 w-full block">
                <i class="fas fa-plus-circle"></i> <span>Add Product</span>
            </a>
        </button>

        <div class="overflow-x-auto bg-white p-4 rounded-md shadow-md">
            <table class="w-full border-collapse border border-gray-300">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="p-2 border">Product ID</th>
                        <th class="p-2 border">Product Name</th>
                        <th class="p-2 border">SKU</th>
                        <th class="p-2 border">Price</th>
                        <th class="p-2 border">Weight</th>
                        <th class="p-2 border">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr class="text-center" id="row-<?php echo $row['id']; ?>">
                        <td class="p-2 border"> <?php echo $row['id']; ?> </td>
                        <td class="p-2 border">
                            <span id="name-display-<?php echo $row['id']; ?>">
                                <?php echo htmlspecialchars($row['product_name']); ?> </span>
                            <input type="text" id="name-edit-<?php echo $row['id']; ?>"
                                value="<?php echo htmlspecialchars($row['product_name']); ?>"
                                class="w-full p-1 border border-gray-300 hidden">
                        </td>
                        <td class="p-2 border">
                            <span id="sku-display-<?php echo $row['id']; ?>">
                                <?php echo htmlspecialchars($row['sku']); ?> </span>
                            <input type="text" id="sku-edit-<?php echo $row['id']; ?>"
                                value="<?php echo htmlspecialchars($row['sku']); ?>"
                                class="w-full p-1 border border-gray-300 hidden">
                        </td>
                        <td class="p-2 border">
                            <span id="price-display-<?php echo $row['id']; ?>"><?php echo $row['price']; ?> </span>
                            <input type="number" id="price-edit-<?php echo $row['id']; ?>"
                                value="<?php echo $row['price']; ?>" class="w-full p-1 border border-gray-300 hidden">
                        </td>
                        <td class="p-2 border">
                            <span id="weight-display-<?php echo $row['id']; ?>"><?php echo $row['weight']; ?> </span>
                            <input type="text" id="weight-edit-<?php echo $row['id']; ?>"
                                value="<?php echo $row['weight']; ?>" class="w-full p-1 border border-gray-300 hidden">
                        </td>
                        <td class="p-2 border flex justify-center space-x-2">
                            <button onclick="enableEdit(<?php echo $row['id']; ?>)"
                                class="bg-blue-500 text-white px-3 py-1 rounded">Edit</button>
                            <button onclick="saveEdit(<?php echo $row['id']; ?>)"
                                class="bg-green-500 text-white px-3 py-1 rounded hidden"
                                id="save-btn-<?php echo $row['id']; ?>">Save</button>
                            <form method="POST" action="delete_product.php" onsubmit="return confirm('Are you sure?');">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4 flex justify-center space-x-2">
            <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>"
                class="px-4 py-2 bg-gray-300 rounded">Previous</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"
                class="px-4 py-2                                                                    <?php echo($i == $page) ? 'bg-blue-500 text-white' : 'bg-gray-300'; ?> rounded">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>"
                class="px-4 py-2 bg-gray-300 rounded">Next</a>
            <?php endif; ?>
        </div>

        <script>
        function enableEdit(id) {
            document.getElementById(`name-display-${id}`).classList.add('hidden');
            document.getElementById(`sku-display-${id}`).classList.add('hidden');
            document.getElementById(`price-display-${id}`).classList.add('hidden');
            document.getElementById(`weight-display-${id}`).classList.add('hidden');
            document.getElementById(`name-edit-${id}`).classList.remove('hidden');
            document.getElementById(`sku-edit-${id}`).classList.remove('hidden');
            document.getElementById(`price-edit-${id}`).classList.remove('hidden');
            document.getElementById(`weight-edit-${id}`).classList.remove('hidden');
            document.querySelector(`#row-${id} button.bg-blue-500`).classList.add('hidden');
            document.getElementById(`save-btn-${id}`).classList.remove('hidden');
        }

        function saveEdit(id) {
            let name = document.getElementById(`name-edit-${id}`).value;
            let sku = document.getElementById(`sku-edit-${id}`).value;
            let price = document.getElementById(`price-edit-${id}`).value;
            let weight = document.getElementById(`weight-edit-${id}`).value;

            let form = document.createElement('form');
            form.method = 'POST';
            form.action = 'update_product.php';

            let inputId = document.createElement('input');
            inputId.type = 'hidden';
            inputId.name = 'id';
            inputId.value = id;

            let inputName = document.createElement('input');
            inputName.type = 'hidden';
            inputName.name = 'product_name';
            inputName.value = name;

            let inputSku = document.createElement('input');
            inputSku.type = 'hidden';
            inputSku.name = 'sku';
            inputSku.value = sku;

            let inputPrice = document.createElement('input');
            inputPrice.type = 'hidden';
            inputPrice.name = 'price';
            inputPrice.value = price;

            let inputWeight = document.createElement('input');
            inputWeight.type = 'hidden';
            inputWeight.name = 'weight';
            inputWeight.value = weight;

            form.appendChild(inputId);
            form.appendChild(inputName);
            form.appendChild(inputSku);
            form.appendChild(inputPrice);
            form.appendChild(inputWeight);

            document.body.appendChild(form);
            form.submit();
        }
        </script>
    </main>
</body>

</html>