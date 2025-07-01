<?php
include 'db.php';

// Set headers for Excel file download
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=PRS_Customer_Data_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Create a file handle to output
$output = fopen("php://output", "w");

// Add UTF-8 BOM for proper encoding
fwrite($output, "\xEF\xBB\xBF");

// Excel header with styling
$header = "<table border='1'>
    <tr style='background-color: #f2f2f2; font-weight: bold;'>
        <th width='50'>SL</th>
        <th width='120'>DATE</th>
        <th width='120'>BARCODE NUMBER</th>
        <th width='100'>CITY</th>
        <th width='80'>PINCODE</th>
        <th width='150'>NAME</th>
        <th width='200'>ADDRESS1</th>
        <th width='200'>ADDRESS2</th>
        <th width='120'>MOBILE NUMBER</th>
        <th width='120'>SENDER MOBILE NUMBER</th>
        <th width='80'>COD</th>
    </tr>";

fwrite($output, $header);

// Date filter
$from_date = $_POST['from_date'] ?? '';
$to_date = $_POST['to_date'] ?? '';

if (!empty($from_date) && !empty($to_date)) {
    $stmt = $conn->prepare("SELECT i.*, d.mobile AS sender_mobile 
                           FROM invoices i
                           LEFT JOIN distributors d ON i.customer_id = d.customer_id
                           WHERE DATE(i.created_at) BETWEEN ? AND ? 
                           ORDER BY i.created_at");
    $stmt->bind_param("ss", $from_date, $to_date);
} else {
    $stmt = $conn->prepare("SELECT i.*, d.mobile AS sender_mobile 
                           FROM invoices i
                           LEFT JOIN distributors d ON i.customer_id = d.customer_id
                           ORDER BY i.created_at");
}

$stmt->execute();
$result = $stmt->get_result();

$serial = 1;
while ($row = $result->fetch_assoc()) {
    // Format the date for better readability
    $formatted_date = date('d-m-Y H:i', strtotime($row['created_at']));
    
    // Prepare data with proper escaping
    $data = [
        $serial,
        htmlspecialchars($formatted_date),
        htmlspecialchars($row['id']),
        htmlspecialchars($row['district']),
        htmlspecialchars($row['pincode']),
        htmlspecialchars($row['full_name']),
        htmlspecialchars($row['address1']),
        htmlspecialchars($row['address2']),
        htmlspecialchars($row['mobile']),
        htmlspecialchars($row['sender_mobile'] ?? 'N/A'), // Using sender mobile from distributor
        number_format($row['total_amount'], 2)
    ];
    
    // Write row to Excel
    fwrite($output, "<tr>");
    foreach ($data as $value) {
        fwrite($output, "<td>" . $value . "</td>");
    }
    fwrite($output, "</tr>");
    
    $serial++;
}

// Close table
fwrite($output, "</table>");

fclose($output);
exit;
?>