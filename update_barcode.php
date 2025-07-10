<?php
include 'db.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['invoice_id']) || !isset($data['barcode'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$invoiceId = $data['invoice_id'];
$barcode = $data['barcode'];

// Update the barcode in the database
$stmt = $conn->prepare("UPDATE invoices SET barcode_number = ? WHERE id = ?");
$stmt->bind_param("si", $barcode, $invoiceId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>