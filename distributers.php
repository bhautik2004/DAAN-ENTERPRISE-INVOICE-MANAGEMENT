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
        <h2 class="text-2xl font-bold text-[var(--primary-color)] mb-4 text-center">Distributors List</h2>

        <?php
        include 'db.php';

        $search = isset($_GET['search']) ? trim($_GET['search']) : '';

        $sql = "SELECT id, customer_id, distributer_name, distributer_address, mobile, email, note, status 
                FROM distributors 
                WHERE distributer_name LIKE ? OR customer_id LIKE ?";
        $stmt = $conn->prepare($sql);
        $searchTerm = "%$search%";
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        ?>

        <form method="GET" class="mb-4 flex">
            <input type="text" name="search" placeholder="Search Distributor..."
                class="w-full p-2 border border-gray-300 rounded-l-md" value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="bg-[var(--primary-color)] text-white px-4 py-2 rounded-r-md">
                Search
            </button>
        </form>

        <div class="overflow-x-auto bg-white p-4 rounded-md shadow-md">
            <table class="w-full border-collapse border border-gray-300">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="p-2 border ">ID</th>
                        <th class="p-2 border min-w-[150px]">Customer ID</th>
                        <th class="p-2 border min-w-[200px]">Name</th>
                        <th class="p-2 border min-w-[200px]">Address</th>
                        <th class="p-2 border min-w-[150px]">Mobile</th>
                        <th class="p-2 border min-w-[200px]">Email</th>
                        <th class="p-2 border min-w-[250px]">Note</th>
                        <th class="p-2 border min-w-[150px]">Status</th>
                        <th class="p-2 border min-w-[150px]">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) : ?>
                    <tr class="text-center" id="row-<?php echo $row['id']; ?>">
                        <td class="p-2 border"><?php echo $row['id']; ?></td>
                        <td class="p-2 border">
                            <span id="customer_id-display-<?php echo $row['id']; ?>">
                                <?php echo htmlspecialchars($row['customer_id']); ?>
                            </span>
                            <input type="text" id="customer_id-edit-<?php echo $row['id']; ?>"
                                value="<?php echo htmlspecialchars($row['customer_id']); ?>"
                                class="w-full p-1 border border-gray-300 hidden">
                        </td>
                        <td class="p-2 border">
                            <span id="name-display-<?php echo $row['id']; ?>">
                                <?php echo htmlspecialchars($row['distributer_name']); ?>
                            </span>
                            <input type="text" id="name-edit-<?php echo $row['id']; ?>"
                                value="<?php echo htmlspecialchars($row['distributer_name']); ?>"
                                class="w-full p-1 border border-gray-300 hidden">
                        </td>
                        <td class="p-2 border">
                            <span id="address-display-<?php echo $row['id']; ?>">
                                <?php echo htmlspecialchars($row['distributer_address']); ?>
                            </span>
                            <textarea id="address-edit-<?php echo $row['id']; ?>"
                                class="w-full p-1 border border-gray-300 hidden"><?php echo htmlspecialchars($row['distributer_address']); ?></textarea>
                        </td>
                        <td class="p-2 border">
                            <span id="mobile-display-<?php echo $row['id']; ?>">
                                <?php echo htmlspecialchars($row['mobile']); ?>
                            </span>
                            <input type="text" id="mobile-edit-<?php echo $row['id']; ?>"
                                value="<?php echo htmlspecialchars($row['mobile']); ?>"
                                class="w-full p-1 border border-gray-300 hidden">
                        </td>
                        <td class="p-2 border">
                            <span id="email-display-<?php echo $row['id']; ?>">
                                <?php echo htmlspecialchars($row['email']); ?>
                            </span>
                            <input type="email" id="email-edit-<?php echo $row['id']; ?>"
                                value="<?php echo htmlspecialchars($row['email']); ?>"
                                class="w-full p-1 border border-gray-300 hidden">
                        </td>
                        <td class="p-2 border">
                            <span id="note-display-<?php echo $row['id']; ?>">
                                <?php echo htmlspecialchars($row['note']); ?>
                            </span>
                            <textarea id="note-edit-<?php echo $row['id']; ?>"
                                class="w-full p-1 border border-gray-300 hidden"><?php echo htmlspecialchars($row['note']); ?></textarea>
                        </td>
                        <td class="p-2 border">
                            <span id="status-display-<?php echo $row['id']; ?>">
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                            <select id="status-edit-<?php echo $row['id']; ?>" class="w-full p-1 border border-gray-300 hidden">
                                <option value="active" <?php echo $row['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $row['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </td>
                        <td class="p-2 border flex justify-center space-x-2">
                            <button onclick="enableEdit(<?php echo $row['id']; ?>)"
                                class="bg-blue-500 text-white px-3 py-1 rounded">Edit</button>
                            <button onclick="saveEdit(<?php echo $row['id']; ?>)"
                                class="bg-green-500 text-white px-3 py-1 rounded hidden"
                                id="save-btn-<?php echo $row['id']; ?>">Save</button>
                            <form method="POST" action="delete_distributor.php" onsubmit="return confirm('Are you sure?');">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
         <button class="bg-green-700 text-white mt-5 rounded-md cursor-pointer">
                <a href="adddistributer.php" class="flex items-center space-x-2 p-2 w-full block">
                    <i class="fas fa-plus-circle"></i> <span>Add Distributor</span>
                </a>
            </button>

        <script>
        function enableEdit(id) {
            // Hide display spans
            document.getElementById(`customer_id-display-${id}`).classList.add('hidden');
            document.getElementById(`name-display-${id}`).classList.add('hidden');
            document.getElementById(`address-display-${id}`).classList.add('hidden');
            document.getElementById(`mobile-display-${id}`).classList.add('hidden');
            document.getElementById(`email-display-${id}`).classList.add('hidden');
            document.getElementById(`note-display-${id}`).classList.add('hidden');
            document.getElementById(`status-display-${id}`).classList.add('hidden');
            
            // Show edit inputs
            document.getElementById(`customer_id-edit-${id}`).classList.remove('hidden');
            document.getElementById(`name-edit-${id}`).classList.remove('hidden');
            document.getElementById(`address-edit-${id}`).classList.remove('hidden');
            document.getElementById(`mobile-edit-${id}`).classList.remove('hidden');
            document.getElementById(`email-edit-${id}`).classList.remove('hidden');
            document.getElementById(`note-edit-${id}`).classList.remove('hidden');
            document.getElementById(`status-edit-${id}`).classList.remove('hidden');
            
            // Toggle buttons
            document.querySelector(`#row-${id} button.bg-blue-500`).classList.add('hidden');
            document.getElementById(`save-btn-${id}`).classList.remove('hidden');
        }

        function saveEdit(id) {
            let customer_id = document.getElementById(`customer_id-edit-${id}`).value;
            let name = document.getElementById(`name-edit-${id}`).value;
            let address = document.getElementById(`address-edit-${id}`).value;
            let mobile = document.getElementById(`mobile-edit-${id}`).value;
            let email = document.getElementById(`email-edit-${id}`).value;
            let note = document.getElementById(`note-edit-${id}`).value;
            let status = document.getElementById(`status-edit-${id}`).value;

            let form = document.createElement('form');
            form.method = 'POST';
            form.action = 'update_distributor.php';

            let inputId = document.createElement('input');
            inputId.type = 'hidden';
            inputId.name = 'id';
            inputId.value = id;

            let inputCustomerId = document.createElement('input');
            inputCustomerId.type = 'hidden';
            inputCustomerId.name = 'customer_id';
            inputCustomerId.value = customer_id;

            let inputName = document.createElement('input');
            inputName.type = 'hidden';
            inputName.name = 'distributer_name';
            inputName.value = name;

            let inputAddress = document.createElement('input');
            inputAddress.type = 'hidden';
            inputAddress.name = 'distributer_address';
            inputAddress.value = address;

            let inputMobile = document.createElement('input');
            inputMobile.type = 'hidden';
            inputMobile.name = 'mobile';
            inputMobile.value = mobile;

            let inputEmail = document.createElement('input');
            inputEmail.type = 'hidden';
            inputEmail.name = 'email';
            inputEmail.value = email;

            let inputNote = document.createElement('input');
            inputNote.type = 'hidden';
            inputNote.name = 'note';
            inputNote.value = note;

            let inputStatus = document.createElement('input');
            inputStatus.type = 'hidden';
            inputStatus.name = 'status';
            inputStatus.value = status;

            form.appendChild(inputId);
            form.appendChild(inputCustomerId);
            form.appendChild(inputName);
            form.appendChild(inputAddress);
            form.appendChild(inputMobile);
            form.appendChild(inputEmail);
            form.appendChild(inputNote);
            form.appendChild(inputStatus);

            document.body.appendChild(form);
            form.submit();
        }
        </script>
    </main>
</body>
</html>