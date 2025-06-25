<?php include 'header.php'; ?>
<?php
include 'db.php';
// session_start();

$admin_id = $_SESSION['admin_id']; // Ensure admin is logged in
$message = "";

if (!$admin_id) {
    header("Location: login.php"); // Redirect if not logged in
    exit;
}

// Fetch admin details
$query = $conn->prepare("SELECT name, email, password FROM admins WHERE id = ?");
$query->bind_param("i", $admin_id);
$query->execute();
$query->bind_result($name, $email, $hashed_password);
$query->fetch();
$query->close();

// Update Password
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $old_password = trim($_POST['old_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (password_verify($old_password, $hashed_password)) {
        if ($new_password === $confirm_password) {
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $update_query->bind_param("si", $new_hashed_password, $admin_id);

            if ($update_query->execute()) {
                $message = "<p class='text-green-500 text-center'>Password updated successfully!</p>";
            } else {
                $message = "<p class='text-red-500 text-center'>Error updating password.</p>";
            }
            $update_query->close();
        } else {
            $message = "<p class='text-red-500 text-center'>New passwords do not match!</p>";
        }
    } else {
        $message = "<p class='text-red-500 text-center'>Incorrect old password!</p>";
    }
}
?>

<?php include 'head.php'; ?>

<body class="bg-gray-100 flex">
    <?php include 'sidebar.php'; ?>
    <main class="flex-1 ml-64 p-8">
        <h3 class="text-xl font-bold text-[var(--primary-color)] mb-4 text-center">Admin Profile</h3>

        <?php if (!empty($message)) : ?>
            <div class="mb-4">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded-md shadow-md w-full max-w-lg mx-auto">
            <form method="POST">
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-semibold">Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($name); ?>" class="w-full p-2 mt-1 border border-gray-300 rounded-md bg-gray-100" readonly>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold">Email</label>
                        <input type="text" value="<?php echo htmlspecialchars($email); ?>" class="w-full p-2 mt-1 border border-gray-300 rounded-md bg-gray-100" readonly>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold">Old Password</label>
                        <input type="password" name="old_password" class="w-full p-2 mt-1 border border-gray-300 rounded-md" required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold">New Password</label>
                        <input type="password" name="new_password" class="w-full p-2 mt-1 border border-gray-300 rounded-md" required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="w-full p-2 mt-1 border border-gray-300 rounded-md" required>
                    </div>
                </div>

                <div class="mt-6 flex justify-center">
                    <button type="submit" class="bg-[var(--primary-color)] text-white px-6 py-2 rounded-md">
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </main>
    <?php include 'scripts.php'; ?>
</body>
</html>
        