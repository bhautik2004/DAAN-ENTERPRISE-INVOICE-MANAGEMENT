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
        <!-- <?php include 'header-bar.php'; ?> -->
        <?php include 'add_employee_from.php'; ?>
    </main>
    <?php include 'scripts.php'; ?>
</body>

</html>
