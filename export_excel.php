<?php
include 'db.php';

// Set headers for Excel file download
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=DAN_Customer_Data_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Create a file handle to output
$output = fopen("php://output", "w");

// Add UTF-8 BOM for proper encoding
fwrite($output, "\xEF\xBB\xBF");

// Excel header with styling (added new columns at the end)
$header = "<table border='1'>
    <tr style='background-color: #f2f2f2; font-weight: bold;'>
        <th width='50'>SL</th>
        <th width='120'>DATE</th>
        <th width='120'>Barcode</th>
        <th width='10'>REF</th>
        <th width='150'>City</th>
        <th width='150'>Pincode</th>
        <th width='150'>Name</th>
        <th width='200'>Add1</th>
        <th width='200'>ADD2</th>
        <th width='200'>ADD3</th>
        <th width='200'>ADDR EMAIL</th>
        <th width='120'>ADDR MOBILE</th>
        <th width='120'>SENDER MOBILE NUMBER</th>
        <th width='200'>Weight</th>
        <th width='80'>COD</th>
        <th width='10'>InsVal</th>
        <th width='10'>VPP</th>
        <th width='200'>L</th>
        <th width='200'>B</th>
        <th width='200'>H</th>
        <th width='120'>Created By</th>
        <th width='100'>Status</th>
        <th width='120'>Repeat Order</th>
    </tr>";

fwrite($output, $header);

// Date filter
$from_date = $_POST['from_date'] ?? '';
$to_date   = $_POST['to_date'] ?? '';

if (! empty($from_date) && ! empty($to_date)) {
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
    // Get the total weight for this invoice
    $weight_stmt = $conn->prepare("SELECT SUM(ii.quantity * p.weight) AS total_weight
                                  FROM invoice_items ii
                                  JOIN products p ON ii.product_id = p.id
                                  WHERE ii.invoice_id = ?");
    $weight_stmt->bind_param("i", $row['id']);
    $weight_stmt->execute();
    $weight_result = $weight_stmt->get_result();
    $weight_row    = $weight_result->fetch_assoc();
    $total_weight  = $weight_row['total_weight'] ?? 0;
    $weight_stmt->close();

    // Format the date (only date, no time)
    $formatted_date = date('d-m-Y', strtotime($row['created_at']));

    // Prepare data (added new columns at the end)
    $data = [
        $serial, // SL (A)
        htmlspecialchars($formatted_date),
        htmlspecialchars($row['barcode_number']),
        '',
        htmlspecialchars($row['village']),
        htmlspecialchars($row['pincode']),
        htmlspecialchars($row['full_name']),
        htmlspecialchars(
            $row['address1'] .
            ' ' . $row['village'] .
            ' ' . $row['sub_district'] .
            ' ' . $row['district'] .
            ($row['pincode'] ? ' - ' . $row['pincode'] : '')
        ),
        htmlspecialchars($row['address2']),
        htmlspecialchars($row['district']),
        htmlspecialchars($row['district']),
        htmlspecialchars($row['mobile']),
        htmlspecialchars($row['sender_mobile'] ?? 'N/A'),
        htmlspecialchars($total_weight),
        htmlspecialchars($row['total_amount']),
        '',
        '',
        '10',
        '10',
        '5',
        htmlspecialchars($row['employee_name'] ?? 'N/A'),                    // Created By
        htmlspecialchars($row['status'] ?? ''),                              // Status
        htmlspecialchars($row['is_repeated_order'] == 'yes' ? 'Yes' : 'No'), // Repeat Order
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
