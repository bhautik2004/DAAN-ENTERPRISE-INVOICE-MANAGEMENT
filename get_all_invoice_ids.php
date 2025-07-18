<?php
include 'db.php';

// Get parameters from POST
$data          = json_decode(file_get_contents('php://input'), true);
$excludeIds    = $data['exclude_ids'] ?? [];
$search        = $data['search'] ?? '';
$status_filter = $data['status_filter'] ?? '';
$start_date    = $data['start_date'] ?? '';
$end_date      = $data['end_date'] ?? '';

// Build query with the same filters as main page
$conditions = [];
$params     = [];
$types      = '';

if (! empty($search)) {
    $conditions[] = "(full_name LIKE ? OR mobile LIKE ? OR barcode_number LIKE ? OR customer_id LIKE ?)";
    $params[]     = "%$search%";
    $params[]     = "%$search%";
    $params[]     = "%$search%";
    $params[]     = "%$search%";
    $types .= 'ssss';
}

if (! empty($status_filter)) {
    $conditions[] = "status = ?";
    $params[]     = $status_filter;
    $types .= 's';
}

if (! empty($start_date) && ! empty($end_date)) {
    $conditions[] = "DATE(created_at) BETWEEN ? AND ?";
    $params[]     = $start_date;
    $params[]     = $end_date;
    $types .= 'ss';
}

$sql = "SELECT id FROM invoices";
if (! empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

// Prepare and execute query
$stmt = $conn->prepare($sql);
if (! empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$allIds = array_column($result->fetch_all(MYSQLI_ASSOC), 'id');

// Filter out excluded IDs
$filteredIds = array_diff($allIds, $excludeIds);

header('Content-Type: application/json');
echo json_encode(['ids' => array_values($filteredIds)]);
