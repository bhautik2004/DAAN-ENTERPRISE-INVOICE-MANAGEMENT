<?php
include 'db.php';
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (empty($data['invoice_id'])) throw new Exception('Invoice ID required');
    if (empty($data['product_id'])) throw new Exception('Product ID required');
    if (empty($data['quantity']) || $data['quantity'] < 1) throw new Exception('Invalid quantity');
    if (empty($data['price']) || $data['price'] <= 0) throw new Exception('Invalid price');

    // Start transaction
    $conn->begin_transaction();

    // Insert new item
    $stmt = $conn->prepare("INSERT INTO invoice_items 
        (invoice_id, product_id, quantity, price, discount) 
        VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiidd", 
        $data['invoice_id'], 
        $data['product_id'], 
        $data['quantity'], 
        $data['price'], 
        $data['discount']);
    $stmt->execute();
    
    // Get product name
    $product = $conn->query("SELECT product_name FROM products WHERE id = " . $data['product_id'])->fetch_assoc();
    
    // Update invoice total
    updateInvoiceTotal($conn, $data['invoice_id']);
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'item_id' => $stmt->insert_id,
        'product_name' => $product['product_name']
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function updateInvoiceTotal($conn, $invoiceId) {
    // Calculate new total from all items
    $result = $conn->query("
        SELECT SUM((price * quantity) - discount) as subtotal 
        FROM invoice_items 
        WHERE invoice_id = $invoiceId
    ");
    $subtotal = $result->fetch_assoc()['subtotal'] ?? 0;
    
    // Get advanced payment
    $advanced = $conn->query("
        SELECT advanced_payment FROM invoices WHERE id = $invoiceId
    ")->fetch_assoc()['advanced_payment'] ?? 0;
    
    $total = max(0, $subtotal - $advanced);
    
    // Update invoice
    $conn->query("UPDATE invoices SET total_amount = $total WHERE id = $invoiceId");
}
?>