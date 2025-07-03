<?php include 'header.php'; ?>
<?php include 'head.php'; ?>

<body class="bg-gray-100 flex">
    <?php include 'sidebar.php'; ?>
    <main class="flex-1 ml-64 p-8">
        <h2 class="text-2xl font-bold text-[var(--primary-color)] mb-4 text-center">Employee List</h2>

        <?php
        include 'db.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['update'])) {
                $id = $_POST['id'];
                $employee_name = $_POST['employee_name'];
                $username = $_POST['username'];
                $mobile_no = $_POST['mobile_no'];
                $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : null;

                if ($password) {
                    $updateSql = "UPDATE employees SET employee_name = ?, username = ?, mobile_no = ?, password = ? WHERE id = ?";
                    $stmt = $conn->prepare($updateSql);
                    $stmt->bind_param("ssssi", $employee_name, $username, $mobile_no, $password, $id);
                } else {
                    $updateSql = "UPDATE employees SET employee_name = ?, username = ?, mobile_no = ? WHERE id = ?";
                    $stmt = $conn->prepare($updateSql);
                    $stmt->bind_param("sssi", $employee_name, $username, $mobile_no, $id);
                }
                $stmt->execute();
            } elseif (isset($_POST['delete'])) {
                $id = $_POST['id'];

                $deleteSql = "DELETE FROM employees WHERE id = ?";
                $stmt = $conn->prepare($deleteSql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
            }
        }

        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $limit = 8;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;

        // **Get total records count**
        $countSql = "SELECT COUNT(*) AS total FROM employees WHERE employee_name LIKE ?";
        $stmt = $conn->prepare($countSql);
        $searchTerm = "%$search%";
        $stmt->bind_param("s", $searchTerm);
        $stmt->execute();
        $countResult = $stmt->get_result();
        $totalEmployees = $countResult->fetch_assoc()['total'];
        $totalPages = ceil($totalEmployees / $limit);

        // **Fetch paginated records**
        $sql = "SELECT id, employee_name, username, mobile_no FROM employees WHERE employee_name LIKE ? LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $searchTerm, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        ?>

        <form method="GET" class="mb-4 flex">
            <input type="text" name="search" placeholder="Search Employee..."
                class="w-full p-2 border border-gray-300 rounded-l-md" value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="bg-[var(--primary-color)] text-white px-4 py-2 rounded-r-md">
                Search
            </button>
            
        </form>
    <button class="bg-green-700 text-white mt-5 mb-5 rounded-md cursor-pointer">
        <a href="add_employee.php" class="flex items-center space-x-2 p-2 w-full block">
            <i class="fas fa-user"></i> <span>Add Employee</span>
        </a>
    </button>

        <div class="overflow-x-auto bg-white p-4 rounded-md shadow-md">
            <table class="w-full border-collapse border border-gray-300">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="p-2 border">ID</th>
                        <th class="p-2 border">Employee Name</th>
                        <th class="p-2 border">Username</th>
                        <th class="p-2 border">Mobile No</th>
                        <th class="p-2 border">New Password</th>
                        <th class="p-2 border">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) : ?>
                    <tr class="text-center" id="row-<?php echo $row['id']; ?>">
                        <td class="p-2 border"><?php echo $row['id']; ?></td>
                        <td class="p-2 border">
                            <span
                                id="name-display-<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['employee_name']); ?></span>
                            <input type="text" id="name-edit-<?php echo $row['id']; ?>"
                                value="<?php echo htmlspecialchars($row['employee_name']); ?>"
                                class="w-full p-1 border border-gray-300 hidden">
                        </td>
                        <td class="p-2 border">
                            <span
                                id="username-display-<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['username']); ?></span>
                            <input type="text" id="username-edit-<?php echo $row['id']; ?>"
                                value="<?php echo htmlspecialchars($row['username']); ?>"
                                class="w-full p-1 border border-gray-300 hidden">
                        </td>
                        <td class="p-2 border">
                            <span
                                id="mobile-display-<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['mobile_no']); ?></span>
                            <input type="text" id="mobile-edit-<?php echo $row['id']; ?>"
                                value="<?php echo htmlspecialchars($row['mobile_no']); ?>"
                                class="w-full p-1 border border-gray-300 hidden">
                        </td>
                        <td class="p-2 border">
                            <input type="password" id="password-edit-<?php echo $row['id']; ?>"
                                placeholder="New Password (Optional)" class="w-full p-1 border border-gray-300 hidden">
                        </td>
                        <td class="p-2 border flex justify-center space-x-2">
                            <button onclick="enableEdit(<?php echo $row['id']; ?>)"
                                class="bg-blue-500 text-white px-3 py-1 rounded">Edit</button>
                            <button onclick="saveEdit(<?php echo $row['id']; ?>)"
                                class="bg-green-500 text-white px-3 py-1 rounded hidden"
                                id="save-btn-<?php echo $row['id']; ?>">Save</button>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete?');">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="delete" value="1">
                                <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <div class="mt-4 flex justify-center space-x-2">
            <?php if ($page > 1) : ?>
            <a href="?search=<?php echo $search; ?>&page=<?php echo $page - 1; ?>"
                class="px-4 py-2 bg-gray-300 rounded">Previous</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++) : ?>
            <a href="?search=<?php echo $search; ?>&page=<?php echo $i; ?>"
                class="px-4 py-2 <?php echo $i == $page ? 'bg-blue-500 text-white' : 'bg-gray-300'; ?> rounded">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages) : ?>
            <a href="?search=<?php echo $search; ?>&page=<?php echo $page + 1; ?>"
                class="px-4 py-2 bg-gray-300 rounded">Next</a>
            <?php endif; ?>
        </div>
    </main>
   
</body>
<script>
function enableEdit(id) {
    document.getElementById(`name-display-${id}`).classList.add('hidden');
    document.getElementById(`name-edit-${id}`).classList.remove('hidden');

    document.getElementById(`username-display-${id}`).classList.add('hidden');
    document.getElementById(`username-edit-${id}`).classList.remove('hidden');

    document.getElementById(`mobile-display-${id}`).classList.add('hidden');
    document.getElementById(`mobile-edit-${id}`).classList.remove('hidden');

    document.getElementById(`password-edit-${id}`).classList.remove('hidden');

    document.getElementById(`save-btn-${id}`).classList.remove('hidden');
}

function saveEdit(id) {
    let name = document.getElementById(`name-edit-${id}`).value;
    let username = document.getElementById(`username-edit-${id}`).value;
    let mobile = document.getElementById(`mobile-edit-${id}`).value;
    let password = document.getElementById(`password-edit-${id}`).value;

    let form = document.createElement('form');
    form.method = 'POST';

    form.innerHTML = `
            <input type="hidden" name="id" value="${id}">
            <input type="hidden" name="employee_name" value="${name}">
            <input type="hidden" name="username" value="${username}">
            <input type="hidden" name="mobile_no" value="${mobile}">
            ${password ? `<input type="hidden" name="password" value="${password}">` : ''}
            <input type="hidden" name="update" value="1">
        `;

    document.body.appendChild(form);
    form.submit();
}
</script>

</html>