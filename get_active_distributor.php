<?php
include 'db.php';

// Fetch first active distributor's customer ID
$query  = "SELECT customer_id FROM distributors WHERE status = 'active' LIMIT 1";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode(['customer_id' => $row['customer_id']]);
} else {
    echo json_encode(['error' => 'No active distributor found']);
}

$conn->close();
