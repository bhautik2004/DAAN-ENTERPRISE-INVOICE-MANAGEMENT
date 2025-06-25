<?php
$host = "localhost";  // Change if your database is on a different host
$user = "root";       // Your database username
$pass = "";           // Your database password
$dbname = "homeopharmacy"; // Your database name

$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
