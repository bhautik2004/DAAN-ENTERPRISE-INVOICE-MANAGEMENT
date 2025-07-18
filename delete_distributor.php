<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];

    $stmt = $conn->prepare("DELETE FROM distributors WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: distributers.php?success=Distributor+deleted+successfully");
    } else {
        header("Location: distributers.php?error=Error+deleting+distributor");
    }
    $stmt->close();
}
$conn->close();
