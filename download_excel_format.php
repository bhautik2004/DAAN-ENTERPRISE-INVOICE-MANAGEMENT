<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Create new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set headers
$headers = [
    'A' => 'Invoice ID',
    'B' => 'Mobile',
    'C' => 'Full Name',
    'D' => 'Address 1',
    'E' => 'Address 2',
    'F' => 'Pincode',
    'G' => 'District',
    'H' => 'Sub District',
    'I' => 'Village',
    'J' => 'Post Name',
    'K' => 'Mobile 2',
    'L' => 'Barcode Number',
    'M' => 'Employee Name',
    'N' => 'Customer ID',
    'O' => 'Total Amount',
    'P' => 'Advanced Payment',
    'Q' => 'Created At',
    'R' => 'Status',
    // Products (5 sets of product_id, quantity, discount)
    'S' => 'Product 1 ID',
    'T' => 'Quantity 1',
    'U' => 'Discount 1',
    'V' => 'Product 2 ID',
    'W' => 'Quantity 2',
    'X' => 'Discount 2',
    'Y' => 'Product 3 ID',
    'Z' => 'Quantity 3',
    'AA' => 'Discount 3',
    'AB' => 'Product 4 ID',
    'AC' => 'Quantity 4',
    'AD' => 'Discount 4',
    'AE' => 'Product 5 ID',
    'AF' => 'Quantity 5',
    'AG' => 'Discount 5'
];

// Set header row
foreach ($headers as $col => $header) {
    $sheet->setCellValue($col . '1', $header);
    // Style header
    $sheet->getStyle($col . '1')->getFont()->setBold(true);
    $sheet->getStyle($col . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFDDDDDD');
}

// Set column widths
$sheet->getColumnDimension('A')->setWidth(15);
$sheet->getColumnDimension('B')->setWidth(20);
$sheet->getColumnDimension('C')->setWidth(25);
$sheet->getColumnDimension('D')->setWidth(30);
$sheet->getColumnDimension('E')->setWidth(30);
$sheet->getColumnDimension('F')->setWidth(10);
$sheet->getColumnDimension('G')->setWidth(20);
$sheet->getColumnDimension('H')->setWidth(20);
$sheet->getColumnDimension('I')->setWidth(20);
$sheet->getColumnDimension('J')->setWidth(20);
$sheet->getColumnDimension('K')->setWidth(20);
$sheet->getColumnDimension('L')->setWidth(20);
$sheet->getColumnDimension('M')->setWidth(20);
$sheet->getColumnDimension('N')->setWidth(15);
$sheet->getColumnDimension('O')->setWidth(15);
$sheet->getColumnDimension('P')->setWidth(15);
$sheet->getColumnDimension('Q')->setWidth(20);
$sheet->getColumnDimension('R')->setWidth(15);

foreach ($headers as $col => $header) {
    $sheet->setCellValue($col.'1', $header);
    $sheet->getStyle($col.'1')->getFont()->setBold(true);
    $sheet->getStyle($col.'1')->getFill()
          ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
          ->getStartColor()->setARGB('FFDDDDDD');
}

// Freeze header row
$sheet->freezePane('A2');

// Add some example data in row 2 (optional)
$exampleData = [
    'A' => '1',
    'B' => '9876543210',
    'C' => 'John Doe',
    'D' => '123 Main Street',
    'E' => 'Apartment 4B',
    'F' => '123456',
    'G' => 'Central District',
    'H' => 'Downtown',
    'I' => 'Metroville',
    'J' => 'Main Post',
    'K' => '9876543211',
    'L' => 'BARCODE123',
    'M' => 'Sales Rep 1',
    'N' => 'CUST001',
    'O' => '1000.00',
    'P' => '200.00',
    'Q' => date('Y-m-d H:i:s'),
    'R' => 'Pending',
    'S' => 'PROD001', // Product 1 ID
    'T' => '2',       // Quantity 1
    'U' => '10',      // Discount 1 (%)
    'V' => 'PROD002', // Product 2 ID
    'W' => '1',       // Quantity 2
    'X' => '5'        // Discount 2 (%)
];

foreach ($exampleData as $col => $value) {
    $sheet->setCellValue($col . '2', $value);
}

// Set protection on the sheet (optional)
$sheet->getProtection()->setSheet(true);
$sheet->getStyle('A2:AG1000')->getProtection()->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED);

// Create Excel file and force download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="invoice_import_template.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;