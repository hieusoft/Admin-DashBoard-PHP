<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$sub_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$sub_id) {
    echo json_encode(['success' => false, 'message' => 'Subscription ID is required']);
    exit;
}

$conn = getDBConnection();
$stmt = $conn->prepare("SELECT * FROM subscriptions WHERE sub_id = ?");
$stmt->bind_param("i", $sub_id);
$stmt->execute();
$result = $stmt->get_result();

if ($subscription = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'subscription' => $subscription]);
} else {
    echo json_encode(['success' => false, 'message' => 'Subscription not found']);
}

$stmt->close();
closeDBConnection($conn);
?>

