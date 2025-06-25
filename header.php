<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Get admin name from session
$admin_name = $_SESSION['user'];
$current_user_name =  $_SESSION['user'];



?>
