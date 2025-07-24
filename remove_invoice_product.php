<?php
include 'db.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['invoice_id']) || !isset($data['item_id'])) {
        throw new Exception('Missing required fields');
    }
    
    $invoice_id = $data['invoice_id'];
    $item_id = $data['item_id'];
    
    // Delete the item
    $delete_sql = "DELETE FROM invoice_items WHERE id = ? AND invoice_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("ii", $item_id, $invoice_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to remove product: ' . $stmt->error);
    }
    
    // Calculate new total
    $total_sql = "SELECT SUM((price * quantity) - discount) as total 
                  FROM invoice_items 
                  WHERE invoice_id = ?";
    $total_stmt = $conn->prepare($total_sql);
    $total_stmt->bind_param("i", $invoice_id);
    $total_stmt->execute();
    $total_result = $total_stmt->get_result();
    $total = $total_result->fetch_assoc()['total'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'new_total' => $total
    ]);
    $stmt->close();
    $total_stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}