<?php include 'header.php'; ?>
<?php include 'head.php'; ?>

<body class="bg-gray-100 flex">
    <?php include 'sidebar.php'; ?>
    <main id="mainContent" class="flex-1 ml-64 p-8 transition-all duration-300">
        <?php include 'header-bar.php'; ?>
<?php
    if (isset($_SESSION['role']) && $_SESSION['role'] == 'Admin') {
        include 'revenue.php'; // Show revenue report for admin
    } else {
        include 'employee_revenue.php'; // Show employee revenue report for employees
    }
?>
            </main>

    <?php include 'scripts.php'; ?>
</body>

</html>
