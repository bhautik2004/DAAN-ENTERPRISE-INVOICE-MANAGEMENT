<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $product_name = trim($_POST['product_name']);
    $price = $_POST['price'];
    $weight = $_POST['weight'];

    if (!empty($product_name) && !empty($price) && !empty($weight)) {
        $stmt = $conn->prepare("UPDATE products SET product_name = ?, price = ?, weight = ? WHERE id = ?");
        $stmt->bind_param("sdsi", $product_name, $price, $weight, $id);

        if ($stmt->execute()) {
            header("Location: products.php?success=Product updated successfully!");
            exit();
        } else {
            header("Location: products.php?error=Error updating product: " . $conn->error);
            exit();
        }
    } else {
        header("Location: products.php?error=All fields are required!");
        exit();
    }
}