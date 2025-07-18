
<?php
    session_start();
    include 'db.php';
    require 'vendor/autoload.php';

    use PhpOffice\PhpSpreadsheet\IOFactory;
    use PhpOffice\PhpSpreadsheet\Shared\Date;

    // Initialize import status
    $_SESSION['import_status'] = 'failed'; // Default to failed

    if (isset($_POST['import_excel'])) {
        $file = $_FILES['excel_file']['tmp_name'];

        if (! $file || ! file_exists($file)) {
            header("Location: import_excel.php");
            exit;
        }

        try {
            // Load the Excel file
            $spreadsheet = IOFactory::load($file);
            $sheet       = $spreadsheet->getActiveSheet();
            $rows        = $sheet->toArray();

            // Start database transaction
            $conn->begin_transaction();
            $hasSuccessfulImport = false;

            foreach ($rows as $index => $row) {
                if ($index == 0) {
                    continue;
                }
                // Skip header row

                // Parse created_at date from Excel (column Q)
                $createdAt = null;
                if (! empty($row[16])) { // Column Q (index 16)
                    try {
                        // Handle both string dates and Excel date serial numbers
                        if (is_numeric($row[16])) {
                            $createdAt = Date::excelToDateTimeObject($row[16])->format('Y-m-d H:i:s');
                        } else {
                            $createdAt = date('Y-m-d H:i:s', strtotime($row[16]));
                        }
                    } catch (Exception $e) {
                        $createdAt = date('Y-m-d H:i:s'); // Fallback to current time
                    }
                } else {
                    $createdAt = date('Y-m-d H:i:s'); // Default to current time
                }

                // Extract data (adjust column indexes as needed)
                $data = [
                    'mobile'           => $row[1] ?? null,       // Column B
                    'full_name'        => $row[2] ?? '',         // Column C
                    'address1'         => $row[3] ?? '',         // Column D
                    'address2'         => $row[4] ?? '',         // Column E
                    'pincode'          => $row[5] ?? null,       // Column F
                    'district'         => $row[6] ?? '',         // Column G
                    'sub_district'     => $row[7] ?? '',         // Column H
                    'village'          => $row[8] ?? '',         // Column I
                    'post_name'        => $row[9] ?? '',         // Column J
                    'mobile2'          => $row[10] ?? null,      // Column K
                    'barcode_number'   => $row[11] ?? '',        // Column L
                    'employee_name'    => $row[12] ?? '',        // Column M
                    'customer_id'      => $row[13] ?? '',        // Column N
                    'total_amount'     => $row[14] ?? 0,         // Column O
                    'advanced_payment' => $row[15] ?? 0,         // Column P
                    'created_at'       => $createdAt,            // Processed date
                    'status'           => $row[17] ?? 'Pending', // Column R
                ];

                // Skip if required field (mobile) is empty
                if (empty($data['mobile'])) {
                    continue;
                }

                try {
                    // Insert invoice with created_at from Excel
                    $stmt = $conn->prepare("INSERT INTO invoices (
                    mobile, full_name, address1, address2, pincode, district,
                    sub_district, village, post_name, mobile2, barcode_number,
                    employee_name, customer_id, total_amount, advanced_payment,
                    created_at, status
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )");

                    $stmt->bind_param(
                        "ssssisssssssssdss",
                        $data['mobile'],
                        $data['full_name'],
                        $data['address1'],
                        $data['address2'],
                        $data['pincode'],
                        $data['district'],
                        $data['sub_district'],
                        $data['village'],
                        $data['post_name'],
                        $data['mobile2'],
                        $data['barcode_number'],
                        $data['employee_name'],
                        $data['customer_id'],
                        $data['total_amount'],
                        $data['advanced_payment'],
                        $data['created_at'], // From Excel
                        $data['status']
                    );

                    if ($stmt->execute()) {
                        $invoiceId           = $conn->insert_id;
                        $hasSuccessfulImport = true;

                        // Process products (5 possible products)
                        for ($i = 0; $i < 5; $i++) {
                            $colOffset = 18 + ($i * 3); // Columns S, V, Y, AB, AE
                            if (! empty($row[$colOffset])) {
                                $productData = [
                                    'id'       => $row[$colOffset] ?? '',
                                    'qty'      => $row[$colOffset + 1] ?? 1,
                                    'discount' => $row[$colOffset + 2] ?? 0,
                                ];

                                $itemStmt = $conn->prepare("INSERT INTO invoice_items
                                (invoice_id, product_id, quantity, discount)
                                VALUES (?, ?, ?, ?)");
                                $itemStmt->bind_param(
                                    "isid",
                                    $invoiceId,
                                    $productData['id'],
                                    $productData['qty'],
                                    $productData['discount']
                                );
                                $itemStmt->execute();
                                $itemStmt->close();
                            }
                        }
                    }
                    $stmt->close();
                } catch (mysqli_sql_exception $e) {
                    // Log error but continue
                    error_log("Import error on row {$index}: " . $e->getMessage());
                }
            }

            // Commit transaction if we had at least one successful import
            if ($hasSuccessfulImport) {
                $conn->commit();
                $_SESSION['import_status'] = 'success';
            } else {
                $conn->rollback();
            }
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Excel import error: " . $e->getMessage());
        }
    }

    header("Location: import_excel.php");
exit;
?>