<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $product_name = trim($_POST['product_name']);
    $price = $_POST['price'];

    if (!empty($product_name) && !empty($price)) {
        $stmt = $conn->prepare("UPDATE products SET product_name = ?, price = ? WHERE id = ?");
        $stmt->bind_param("sdi", $product_name, $price, $id);

        if ($stmt->execute()) {
            header("Location: products.php?success=Product updated successfully!");
            exit();
        } else {
            header("Location: products.php?error=Error updating product.");
            exit();
        }
    } else {
        header("Location: products.php?error=All fields are required!");
        exit();
    }
}
