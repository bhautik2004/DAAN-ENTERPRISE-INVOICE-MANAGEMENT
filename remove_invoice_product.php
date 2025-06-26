<?php
// Ensure no output before headers
if (ob_get_level()) ob_end_clean();

// Strict error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Database connection
require 'db.php';

// Set JSON header
header('Content-Type: application/json');

try {
    // Get and validate input
    $jsonInput = file_get_contents('php://input');
    if (empty($jsonInput)) {
        throw new Exception('No input data received');
    }

    $data = json_decode($jsonInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }

    // Validate required fields
    $required = ['invoice_id', 'item_id'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
        if (!is_numeric($data[$field])) {
            throw new Exception("Invalid $field format");
        }
    }

    // Start transaction
    $conn->begin_transaction();

    // 1. Delete the item
    $stmt = $conn->prepare("DELETE FROM invoice_items WHERE id = ? AND invoice_id = ?");
    $stmt->bind_param("ii", $data['item_id'], $data['invoice_id']);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception('No item found with that ID');
    }

    // 2. Recalculate invoice total
    // Get subtotal from remaining items
    $subtotalQuery = $conn->query("
        SELECT COALESCE(SUM((price * quantity) - discount), 0) AS subtotal 
        FROM invoice_items 
        WHERE invoice_id = {$data['invoice_id']}
    ");
    $subtotal = $subtotalQuery->fetch_assoc()['subtotal'];

    // Get advanced payment
    $advancedQuery = $conn->query("
        SELECT advanced_payment FROM invoices WHERE id = {$data['invoice_id']}
    ");
    $advanced = $advancedQuery->fetch_assoc()['advanced_payment'] ?? 0;

    // Calculate new total
    $newTotal = max(0, $subtotal - $advanced);

    // Update invoice
    $conn->query("UPDATE invoices SET total_amount = $newTotal WHERE id = {$data['invoice_id']}");

    // Commit transaction
    $conn->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Product removed successfully',
        'new_total' => $newTotal
    ]);
    exit;

} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($conn) && $conn->in_transaction) {
        $conn->rollback();
    }

    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>