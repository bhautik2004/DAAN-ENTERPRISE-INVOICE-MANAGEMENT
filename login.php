<?php
    session_start(); // Start session

    // Redirect if user is already logged in
    if (isset($_SESSION['role'])) {
        if ($_SESSION['role'] === "Admin") {
            header("Location: index.php"); // Redirect admin to admin dashboard
            exit();
        } elseif ($_SESSION['role'] === "Employee") {
            header("Location: index.php"); // Redirect employee to employee dashboard
            exit();
        }
    }

    include 'db.php';

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['role'])) {
        $role            = $_POST['role'];
        $emailOrUsername = $_POST['email_or_username'];
        $password        = $_POST['password'];

        if ($role === "admin") {
            $stmt = $conn->prepare("SELECT * FROM admins WHERE email = ?");
        } else {
            $stmt = $conn->prepare("SELECT * FROM employees WHERE username = ?");
        }

        $stmt->bind_param("s", $emailOrUsername);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $_SESSION['user']  = ($role === "admin") ? $user['name'] : $user['employee_name'];
                $_SESSION['role']  = ucfirst($role);
                $_SESSION['email'] = ($role === "admin") ? $user['email'] : "";
                if ($role === "admin") {
                    $_SESSION['admin_id'] = $user['id']; // Store admin ID in session
                }

                $redirectPage = ($role === "admin") ? "index.php" : "index.php";
                header("Location: " . $redirectPage);
                exit();
            } else {
                $_SESSION[$role . '_message'] = "Invalid credentials. Please try again.";
                $_SESSION['login_role']       = $role; // Store role for showing correct form
            }
        } else {
            $_SESSION[$role . '_message'] = "No user found with these credentials.";
            $_SESSION['login_role']       = $role;
        }

        header("Location: login.php"); // Redirect back to show error message
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="x-icon" href="DE.png">
    <title>Homoeo Pharmacy - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
    <style>
    :root {
        --primary-color: #03262b;
        --sec-color: #326417;
    }
    </style>
</head>

<body class="bg-gray-100 flex flex-col min-h-screen">
    <nav class="bg-white shadow-md p-4 fixed w-full top-0 z-50 h-20 flex items-center">
        <div class="container mx-auto flex justify-between items-center">
            <img src="DE.png" alt="HomeoPharma Logo" class="h-48 ml-8 w-auto object-contain">
            <div class="relative flex items-center bg-gray-200 mr-8 p-1 rounded-full">
                <a href="#admin" id="adminBtn"
                    class="px-6 py-2 font-semibold transition-all duration-300 rounded-full relative z-10 text-gray-800">Admin</a>
                <a href="#employee" id="employeeBtn"
                    class="px-6 py-2 font-semibold transition-all duration-300 rounded-full relative z-10 text-gray-800">Employee</a>
                <div id="toggleIndicator"
                    class="absolute left-0 top-0 h-full w-1/2 bg-[var(--primary-color)] rounded-full transition-all duration-300">
                </div>
            </div>
        </div>
    </nav>
    <div class="flex justify-center items-center flex-grow mt-20">
       <!-- Admin Login Form -->
<div id="admin" class="bg-white p-8 rounded-xl shadow-xl w-96 border border-gray-300 fade-in">
    <h2 class="text-3xl font-bold text-center text-[var(--primary-color)] mb-4">Admin Login</h2>

    <!-- Display Error Message for Admin -->
    <?php
        if (isset($_SESSION['admin_message'])) {
            echo "<div class='bg-red-100 text-red-700 p-3 rounded-md mb-4'>{$_SESSION['admin_message']}</div>";
            unset($_SESSION['admin_message']); // Clear message after displaying
        }
    ?>

    <form method="POST" class="space-y-4">
        <input type="hidden" name="role" value="admin">
        <div class="relative">
            <i class="fas fa-envelope absolute left-3 top-4 text-gray-500"></i>
            <input type="email" name="email_or_username" placeholder="Email"
                class="w-full p-3 pl-10 border rounded-lg focus:ring-2 focus:ring-[var(--primary-color)] outline-none"
                required>
        </div>
        <div class="relative">
            <i class="fas fa-lock absolute left-3 top-4 text-gray-500"></i>
            <input type="password" name="password" placeholder="Password"
                class="w-full p-3 pl-10 border rounded-lg focus:ring-2 focus:ring-[var(--primary-color)] outline-none"
                required>
        </div>
        <button type="submit"
            class="bg-[var(--primary-color)] text-white w-full py-3 rounded-lg font-bold shadow-md hover:bg-opacity-80 transition">Login</button>
    </form>
</div>

       <!-- Employee Login Form -->
<div id="employee" class="hidden bg-white p-8 rounded-xl shadow-xl w-96 border border-gray-300">
    <h2 class="text-3xl font-bold text-center text-[var(--primary-color)] mb-4">Employee Login</h2>

    <!-- Display Error Message for Employee -->
    <?php
        if (isset($_SESSION['employee_message'])) {
            echo "<div class='bg-red-100 text-red-700 p-3 rounded-md mb-4'>{$_SESSION['employee_message']}</div>";
            unset($_SESSION['employee_message']); // Clear message after displaying
        }
    ?>

    <form method="POST" class="space-y-4">
        <input type="hidden" name="role" value="employee">
        <div class="relative">
            <i class="fas fa-user absolute left-3 top-4 text-gray-500"></i>
            <input type="text" name="email_or_username" placeholder="Username"
                class="w-full p-3 pl-10 border rounded-lg focus:ring-2 focus:ring-[var(--primary-color)] outline-none"
                required>
        </div>
        <div class="relative">
            <i class="fas fa-lock absolute left-3 top-4 text-gray-500"></i>
            <input type="password" name="password" placeholder="Password"
                class="w-full p-3 pl-10 border rounded-lg focus:ring-2 focus:ring-[var(--primary-color)] outline-none"
                required>
        </div>
        <button type="submit"
            class="bg-[var(--primary-color)] text-white w-full py-3 rounded-lg font-bold shadow-md hover:bg-opacity-80 transition">Login</button>
    </form>
</div>

    <script>
    const adminBtn = document.getElementById('adminBtn');
    const employeeBtn = document.getElementById('employeeBtn');
    const toggleIndicator = document.getElementById('toggleIndicator');
    const adminForm = document.getElementById('admin');
    const employeeForm = document.getElementById('employee');

    function updateToggle(active) {
        if (active === 'admin') {
            adminForm.classList.remove('hidden');
            employeeForm.classList.add('hidden');
            toggleIndicator.style.transform = 'translateX(0)';
            adminBtn.classList.add('text-white');
            employeeBtn.classList.remove('text-white');
        } else {
            employeeForm.classList.remove('hidden');
            adminForm.classList.add('hidden');
            toggleIndicator.style.transform = 'translateX(100%)';
            employeeBtn.classList.add('text-white');
            adminBtn.classList.remove('text-white');
        }
    }
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            updateToggle(this.getAttribute('href') === '#admin' ? 'admin' : 'employee');
        });
    });
    let loginRole = "<?php echo isset($_SESSION['login_role']) ? $_SESSION['login_role'] : 'admin'; ?>";
    updateToggle(loginRole);
    </script>
</body>

</html>