<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $customer_id = trim($_POST['customer_id']);
    $distributer_name = trim($_POST['distributer_name']);
    $distributer_address = trim($_POST['distributer_address']);
    $mobile = trim($_POST['mobile']);
    $email = trim($_POST['email']);
    $note = trim($_POST['note']);
    $status = trim($_POST['status']);

    // Check if customer_id already exists for another distributor
    $check_stmt = $conn->prepare("SELECT id FROM distributors WHERE customer_id = ? AND id != ?");
    $check_stmt->bind_param("si", $customer_id, $id);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        header("Location: distributers.php?error=Customer+ID+already+exists");
        exit();
    }

    $stmt = $conn->prepare("UPDATE distributors SET 
                            customer_id = ?, 
                            distributer_name = ?, 
                            distributer_address = ?, 
                            mobile = ?, 
                            email = ?, 
                            note = ?, 
                            status = ? 
                            WHERE id = ?");
    $stmt->bind_param("sssssssi", 
        $customer_id, 
        $distributer_name, 
        $distributer_address, 
        $mobile, 
        $email, 
        $note, 
        $status, 
        $id);

    if ($stmt->execute()) {
        header("Location: distributers.php?success=Distributor+updated+successfully");
    } else {
        header("Location: distributers.php?error=Error+updating+distributor");
    }
    $stmt->close();
}
$conn->close();
?>