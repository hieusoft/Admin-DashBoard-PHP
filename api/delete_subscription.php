<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$conn = getDBConnection();

$sub_id = isset($data['sub_id']) ? (int)$data['sub_id'] : null;

if (!$sub_id) {
    echo json_encode(['success' => false, 'message' => 'Subscription ID is required']);
    closeDBConnection($conn);
    exit;
}

$stmt = $conn->prepare("DELETE FROM subscriptions WHERE sub_id = ?");
$stmt->bind_param("i", $sub_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Subscription deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
}

$stmt->close();
closeDBConnection($conn);
?>

