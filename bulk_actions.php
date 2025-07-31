<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$action = $_POST['action'] ?? '';
$ids = json_decode($_POST['ids'] ?? '[]', true);
$status = $_POST['status'] ?? '';

if (empty($ids)) {
    echo json_encode(['success' => false, 'error' => 'No invoices selected']);
    exit;
}

try {
    $conn->begin_transaction();
    
    if ($action === 'delete') {
        // First delete items
        $deleteItems = "DELETE FROM invoice_items WHERE invoice_id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")";
        $stmt = $conn->prepare($deleteItems);
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt->execute();
        
        // Then delete invoices
        $deleteSql = "DELETE FROM invoices WHERE id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")";
        $stmt = $conn->prepare($deleteSql);
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt->execute();
        
        $message = 'Successfully deleted ' . $stmt->affected_rows . ' invoices';
    } 
    elseif ($action === 'status') {
        $updateSql = "UPDATE invoices SET status = ? WHERE id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")";
        $stmt = $conn->prepare($updateSql);
        $params = array_merge([$status], $ids);
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        $stmt->execute();
        
        $message = 'Successfully updated status for ' . $stmt->affected_rows . ' invoices';
    } 
    else {
        throw new Exception('Invalid action');
    }
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => $message]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>