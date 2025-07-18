<?php
include 'db.php';

$sql    = "SELECT distributer_name, distributer_address, mobile, email, note FROM distributors WHERE status = 'active' LIMIT 1";
$result = mysqli_query($conn, $sql);
$data   = mysqli_fetch_assoc($result);

header('Content-Type: application/json');
echo json_encode($data);
