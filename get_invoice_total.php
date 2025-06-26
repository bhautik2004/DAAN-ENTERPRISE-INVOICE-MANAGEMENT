<?php
include 'db.php';
header('Content-Type: application/json');

$invoiceId = $_GET['invoice_id'] ?? 0;

try {
    $result = $conn->query("SELECT total_amount FROM invoices WHERE id = $invoiceId");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'total_amount' => (float)$row['total_amount']
        ]);
    } else {
        throw new Exception('Invoice not found');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>