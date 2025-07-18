<?php
include 'db.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (! isset($data['invoice_id']) || ! isset($data['product_id']) || ! isset($data['quantity'])) {
        throw new Exception('Missing required fields');
    }

    $invoice_id = $data['invoice_id'];
    $product_id = $data['product_id'];
    $quantity   = $data['quantity'];
    $price      = $data['price'] ?? 0;
    $discount   = $data['discount'] ?? 0;

    // Get product name and price if not provided
    if ($price <= 0) {
        $product_query = $conn->prepare("SELECT product_name, price FROM products WHERE id = ?");
        $product_query->bind_param("i", $product_id);
        $product_query->execute();
        $product_result = $product_query->get_result();

        if ($product_result->num_rows === 0) {
            throw new Exception('Product not found');
        }

        $product      = $product_result->fetch_assoc();
        $price        = $product['price'];
        $product_name = $product['product_name'];
    } else {
        $product_query = $conn->prepare("SELECT product_name FROM products WHERE id = ?");
        $product_query->bind_param("i", $product_id);
        $product_query->execute();
        $product_result = $product_query->get_result();
        $product_name   = $product_result->fetch_assoc()['product_name'];
    }

    // Insert the new item
    $insert_sql = "INSERT INTO invoice_items (invoice_id, product_id, quantity, price, discount)
                   VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("iiidd", $invoice_id, $product_id, $quantity, $price, $discount);

    if (! $stmt->execute()) {
        throw new Exception('Failed to add product: ' . $stmt->error);
    }

    $item_id = $stmt->insert_id;

    // Calculate new total
    $total_sql = "SELECT SUM((price * quantity) - discount) as total
                  FROM invoice_items
                  WHERE invoice_id = ?";
    $total_stmt = $conn->prepare($total_sql);
    $total_stmt->bind_param("i", $invoice_id);
    $total_stmt->execute();
    $total_result = $total_stmt->get_result();
    $total        = $total_result->fetch_assoc()['total'] ?? 0;

    echo json_encode([
        'success'      => true,
        'item_id'      => $item_id,
        'product_name' => $product_name,
        'total_amount' => $total,
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
