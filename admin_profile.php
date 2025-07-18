<?php include 'header.php'; ?>
<?php
    include 'db.php';
    // session_start();

    $admin_id = $_SESSION['admin_id']; // Ensure admin is logged in
    $message  = "";

    if (! $admin_id) {
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

    // Update Profile (Name, Email, Password)
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $new_name = trim($_POST['name']);
        $new_email = trim($_POST['email']);
        $old_password = isset($_POST['old_password']) ? trim($_POST['old_password']) : '';
        $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
        $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';
        
        // Initialize update flags
        $update_name = false;
        $update_email = false;
        $update_password = false;
        
        // Check if name is changed
        if ($new_name !== $name) {
            $update_name = true;
        }
        
        // Check if email is changed
        if ($new_email !== $email) {
            // Validate email format
            if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
                $message = "<p class='text-red-500 text-center'>Invalid email format!</p>";
            } else {
                // Check if email already exists
                $email_check = $conn->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
                $email_check->bind_param("si", $new_email, $admin_id);
                $email_check->execute();
                $email_check->store_result();
                
                if ($email_check->num_rows > 0) {
                    $message = "<p class='text-red-500 text-center'>Email already in use by another admin!</p>";
                } else {
                    $update_email = true;
                }
                $email_check->close();
            }
        }
        
        // Check if password is being changed
        if (!empty($old_password) || !empty($new_password)) {
            if (password_verify($old_password, $hashed_password)) {
                if ($new_password === $confirm_password) {
                    if (!empty($new_password)) {
                        $update_password = true;
                        $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    } else {
                        $message = "<p class='text-red-500 text-center'>New password cannot be empty!</p>";
                    }
                } else {
                    $message = "<p class='text-red-500 text-center'>New passwords do not match!</p>";
                }
            } else {
                $message = "<p class='text-red-500 text-center'>Incorrect old password!</p>";
            }
        }
        
        // Only proceed with updates if no error messages
        if (empty($message)) {
            $success = true;
            
            // Build the update query based on what needs to be updated
            if ($update_name || $update_email || $update_password) {
                $query_parts = [];
                $params = [];
                $types = '';
                
                if ($update_name) {
                    $query_parts[] = "name = ?";
                    $params[] = $new_name;
                    $types .= 's';
                }
                
                if ($update_email) {
                    $query_parts[] = "email = ?";
                    $params[] = $new_email;
                    $types .= 's';
                }
                
                if ($update_password) {
                    $query_parts[] = "password = ?";
                    $params[] = $new_hashed_password;
                    $types .= 's';
                }
                
                $types .= 'i'; // for admin_id parameter
                $params[] = $admin_id;
                
                $sql = "UPDATE admins SET " . implode(', ', $query_parts) . " WHERE id = ?";
                $update_query = $conn->prepare($sql);
                
                // Dynamic binding
                $update_query->bind_param($types, ...$params);
                
                if ($update_query->execute()) {
                    // Update session variables if name was changed
                    if ($update_name) {
                        $_SESSION['admin_name'] = $new_name;
                        $name = $new_name;
                    }
                    
                    if ($update_email) {
                        $email = $new_email;
                    }
                    
                    $message = "<p class='text-green-500 text-center'>Profile updated successfully!</p>";
                } else {
                    $message = "<p class='text-red-500 text-center'>Error updating profile: " . $conn->error . "</p>";
                }
                $update_query->close();
            } else {
                $message = "<p class='text-blue-500 text-center'>No changes were made.</p>";
            }
        }
    }
?>

<?php include 'head.php'; ?>

<body class="bg-gray-100 flex">
    <?php include 'sidebar.php'; ?>
    <main class="flex-1 ml-64 p-8">
        <h3 class="text-xl font-bold text-[var(--primary-color)] mb-4 text-center">Admin Profile</h3>

        <?php if (! empty($message)): ?>
            <div class="mb-4">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded-md shadow-md w-full max-w-lg mx-auto">
            <form method="POST">
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-semibold">Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" class="w-full p-2 mt-1 border border-gray-300 rounded-md" required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold">Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" class="w-full p-2 mt-1 border border-gray-300 rounded-md" required>
                    </div>

                    <div class="mt-6 border-t pt-4">
                        <h4 class="text-sm font-semibold text-gray-600 mb-4">Change Password (leave blank if not changing)</h4>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-semibold">Old Password</label>
                            <input type="password" name="old_password" class="w-full p-2 mt-1 border border-gray-300 rounded-md">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-semibold">New Password</label>
                            <input type="password" name="new_password" class="w-full p-2 mt-1 border border-gray-300 rounded-md">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="w-full p-2 mt-1 border border-gray-300 rounded-md">
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-center">
                    <button type="submit" class="bg-[var(--primary-color)] text-white px-6 py-2 rounded-md">
                        Update Profile
                    </button>
                </div>
            </form>
        </div>
    </main>
    <?php include 'scripts.php'; ?>
</body>
</html>