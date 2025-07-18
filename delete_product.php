<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id"])) {
    $product_id = $_POST["id"];

    // Check if product is referenced in invoice_items
    $check_query = "SELECT COUNT(*) as count FROM invoice_items WHERE product_id = ?";
    $check_stmt  = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $product_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row    = $result->fetch_assoc();

    if ($row['count'] > 0) {
        echo "<script>alert('Cannot delete product - it is referenced in existing invoices.'); window.location.href='products.php';</script>";
    } else {
        // Proceed with deletion if no references exist
        $query = "DELETE FROM products WHERE id = ?";
        $stmt  = $conn->prepare($query);
        $stmt->bind_param("i", $product_id);

        if ($stmt->execute()) {
            echo "<script>alert('Product deleted successfully.'); window.location.href='products.php';</script>";
        } else {
            echo "<script>alert('Error deleting product.'); window.location.href='products.php';</script>";
        }
    }
}
