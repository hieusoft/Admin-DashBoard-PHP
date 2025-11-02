<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$conn = getDBConnection();

// Lấy danh sách payments cùng order_id
$payments = $conn->query("
    SELECT payment_id,order_id, amount, currency, status 
    FROM payments 
    ORDER BY payment_id DESC 
    LIMIT 100
");

$paymentsArray = [];
while ($payment = $payments->fetch_assoc()) {
    $paymentsArray[] = $payment;
}

echo json_encode(['success' => true, 'payments' => $paymentsArray]);

closeDBConnection($conn);
?>
