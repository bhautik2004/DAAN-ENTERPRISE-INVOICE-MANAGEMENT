<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["pdf"])) {
    $uploadDir = "invoices/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = uniqid("invoice_") . ".pdf"; // Unique file name
    $filePath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES["pdf"]["tmp_name"], $filePath)) {
        $pdfUrl = "https://yourwebsite.com/" . $filePath; // Change to your domain
        echo json_encode(["success" => true, "pdf_url" => $pdfUrl]);
    } else {
        echo json_encode(["success" => false]);
    }
}
?>
