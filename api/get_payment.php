<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Lấy payment_id từ query string (?id=...) hoặc từ body JSON
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$payment_id = $_GET['id'] ?? ($input['payment_id'] ?? 0);
$payment_id = (int)$payment_id;

if ($payment_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Payment ID không hợp lệ']);
    exit;
}

$conn = getDBConnection();


$stmt = $conn->prepare("
    SELECT p.*, sp.name AS plan_name
    FROM payments p
    LEFT JOIN subscription_plans sp ON p.plan_id = sp.plan_id
    WHERE p.payment_id = ?
    LIMIT 1
");
$stmt->bind_param('i', $payment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $payment = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'payment' => $payment
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Không tìm thấy payment'
    ]);
}

$stmt->close();
closeDBConnection($conn);