<?php
include 'db.php'; // Database connection

$message = ""; // Variable to store success/error message

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $employee_name = trim($_POST['employee_name']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $mobile_no = trim($_POST['mobile_no']);

    if (empty($employee_name) || empty($username) || empty($password) || empty($mobile_no)) {
        $message = "<p class='text-red-500'>All fields are required!</p>";
    } else {
        // Check if username already exists
        $check_stmt = $conn->prepare("SELECT id FROM employees WHERE username = ?");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $message = "<p class='text-red-500'>Username already exists!</p>";
        } else {
            // Hash the password before storing
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Insert new employee
            $stmt = $conn->prepare("INSERT INTO employees (employee_name, username, password, mobile_no) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $employee_name, $username, $hashed_password, $mobile_no);

            if ($stmt->execute()) {
                $message = "<p class='text-green-500'>Employee added successfully!</p>";
            } else {
                $message = "<p class='text-red-500'>Error: " . $stmt->error . "</p>";
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}
$conn->close();
?>

<section class="mt-8">
    <h3 class="text-xl font-bold text-[var(--primary-color)] mb-4 text-center">Add Employee</h3>

    <!-- Message Display -->
    <?php if (!empty($message)) : ?>
        <div class="text-center mb-4">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-md shadow-md">
        <form action="" method="POST">
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-sm font-semibold">Employee Name</label>
                    <input type="text" name="employee_name" class="w-full p-2 mt-1 border border-gray-300 rounded-md" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold">Username</label>
                    <input type="text" name="username" class="w-full p-2 mt-1 border border-gray-300 rounded-md" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold">Password</label>
                    <input type="password" name="password" class="w-full p-2 mt-1 border border-gray-300 rounded-md" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold">Mobile No</label>
                    <input type="text" name="mobile_no" class="w-full p-2 mt-1 border border-gray-300 rounded-md" required>
                </div>
            </div>
            <div class="mt-6 flex justify-start">
                <button type="submit" class="bg-[var(--primary-color)] text-white px-6 py-2 rounded-md">
                    Add Employee
                </button>
            </div>
        </form>
    </div>
</section>
