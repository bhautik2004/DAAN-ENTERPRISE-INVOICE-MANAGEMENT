<?php
include 'db.php'; // Include the database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_name = $_POST['product_name'];
    $price        = $_POST['price'];

    // Validate inputs
    if (empty($product_name) || empty($price)) {
        echo "All fields are required!";
    } else {
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO products (product_name, price) VALUES (?, ?)");
        $stmt->bind_param("sd", $product_name, $price); // "sd" means string and double

        if ($stmt->execute()) {
            echo "Product added successfully!";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}
$conn->close();
