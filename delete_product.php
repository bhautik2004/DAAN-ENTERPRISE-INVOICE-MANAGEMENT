<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id"])) {
    $product_id = $_POST["id"];

    // Check if the product exists in invoices before deleting
    // $checkQuery = "SELECT COUNT(*) AS count FROM invoices WHERE product_id = ?";
    // $stmtCheck = $conn->prepare($checkQuery);
    // $stmtCheck->bind_param("i", $product_id);
    // $stmtCheck->execute();
    // $resultCheck = $stmtCheck->get_result();
    // $count = $resultCheck->fetch_assoc()["count"];

    // if ($count > 0) {
    //     echo "<script>alert('Error: Product cannot be deleted because it is linked to invoices.'); window.location.href='products.php';</script>";
    //     exit;
    // }

    // Proceed with deletion
    $query = "DELETE FROM products WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $product_id);

    if ($stmt->execute()) {
        echo "<script>alert('Product deleted successfully.'); window.location.href='products.php';</script>";
    } else {
        echo "<script>alert('Error deleting product.'); window.location.href='products.php';</script>";
    }
}
?>
