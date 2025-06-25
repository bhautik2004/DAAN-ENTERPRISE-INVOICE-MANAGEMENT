<?php
include 'db.php'; 

header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=PRS_Customer_Data.xls");
header("Pragma: no-cache");
header("Expires: 0");

// UTF-8 BOM for proper Unicode display
echo "\xEF\xBB\xBF";

// Column headers
echo "Mobile,Full Name,Address,Pincode,District,Sub District,Village,Mobile 2,Product,Quantity,Employee,Amount,Advanced Payment,Discount,Created At,Status\n";

// Get date range from form
$from_date = $_POST['from_date'] ?? '';
$to_date = $_POST['to_date'] ?? '';

// Validate dates and fetch data with product names
if (!empty($from_date) && !empty($to_date)) {
    $stmt = $conn->prepare("SELECT invoices.*, products.product_name FROM invoices 
                            LEFT JOIN products ON invoices.product_id = products.id 
                            WHERE DATE(invoices.created_at) BETWEEN ? AND ?");
    $stmt->bind_param("ss", $from_date, $to_date);
} else {
    $stmt = $conn->prepare("SELECT invoices.*, products.product_name FROM invoices 
                            LEFT JOIN products ON invoices.product_id = products.id");
}

$stmt->execute();
$result = $stmt->get_result();

// Fetch and display data
while ($row = $result->fetch_assoc()) {
    echo "\"{$row['mobile']}\",\"{$row['full_name']}\",\"{$row['address1']} {$row['address2']}\",\"{$row['pincode']}\",\"{$row['district']}\",\"{$row['sub_district']}\",\"{$row['village']}\",\"{$row['mobile2']}\",\"{$row['product_name']}\",\"{$row['quantity']}\",\"{$row['employee_name']}\",\"{$row['total_amount']}\",\"{$row['advanced_payment']}\",\"{$row['discount']}\",\"{$row['created_at']}\",\"{$row['status']}\"\n";
}
exit;
?>