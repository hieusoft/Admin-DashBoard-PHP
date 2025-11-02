<?php
require_once __DIR__ . '/../config/db.php';

$conn = getDBConnection();
$data = json_decode(file_get_contents('php://input'), true);

$payment_id = $data['payment_id'];
$plan_id = $data['plan_id'];
$amount = $data['amount'];
$currency = $data['currency'];
$method = $data['method'];
$status = $data['status'];

$sql = "UPDATE payments SET plan_id=?, amount=?, currency=?, method=?, status=? WHERE payment_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('idsssi', $plan_id, $amount, $currency, $method, $status, $payment_id);

if($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}

$stmt->close();
closeDBConnection($conn);
