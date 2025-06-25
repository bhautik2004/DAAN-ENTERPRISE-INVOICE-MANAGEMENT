<?php
include 'db.php'; // Database connection

$type = $_GET['type']; // 'village', 'district', or 'sub_district'
$query = $_GET['query']; // User input

$suggestions = [];

if ($type === 'village') {
    $stmt = $conn->prepare("SELECT DISTINCT village FROM invoices WHERE village LIKE ?");
} elseif ($type === 'district') {
    $stmt = $conn->prepare("SELECT DISTINCT district FROM invoices WHERE district LIKE ?");
} elseif ($type === 'sub_district') {
    $stmt = $conn->prepare("SELECT DISTINCT sub_district FROM invoices WHERE sub_district LIKE ?");
}

$searchQuery = "%$query%";
$stmt->bind_param("s", $searchQuery);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $suggestions[] = $row[$type];
}

echo json_encode(['suggestions' => $suggestions]);

$stmt->close();
$conn->close();
?>