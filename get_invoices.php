<?php
include 'db.php';

header('Content-Type: application/json');

try {
    // Get the POST data
    $data       = json_decode(file_get_contents('php://input'), true);
    $invoiceIds = $data['invoice_ids'] ?? [];

    if (empty($invoiceIds)) {
        throw new Exception('No invoice IDs provided');
    }

    // Prepare the query with placeholders
    $placeholders = implode(',', array_fill(0, count($invoiceIds), '?'));
    $sql          = "SELECT * FROM invoices WHERE id IN ($placeholders)";
    $stmt         = $conn->prepare($sql);

    // Bind parameters
    $types = str_repeat('i', count($invoiceIds));
    $stmt->bind_param($types, ...$invoiceIds);
    $stmt->execute();
    $result = $stmt->get_result();

    $invoices = [];
    while ($row = $result->fetch_assoc()) {
        // For each invoice, fetch its items
        $items_sql = "SELECT ii.*, p.product_name, p.price, p.weight, p.sku
                      FROM invoice_items ii
                      JOIN products p ON ii.product_id = p.id
                      WHERE ii.invoice_id = ?";
        $items_stmt = $conn->prepare($items_sql);
        $items_stmt->bind_param("i", $row['id']);
        $items_stmt->execute();
        $items_result  = $items_stmt->get_result();
        $invoice_items = $items_result->fetch_all(MYSQLI_ASSOC);

        $row['invoice_items'] = $invoice_items;
        $invoices[]           = $row;
        $items_stmt->close();
    }
    $stmt->close();
    $conn->close();

    echo json_encode($invoices);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
