<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$conn = getDBConnection();

$plan_id = isset($data['plan_id']) ? (int)$data['plan_id'] : null;

if (!$plan_id) {
    echo json_encode(['success' => false, 'message' => 'Plan ID is required']);
    closeDBConnection($conn);
    exit;
}

$stmt = $conn->prepare("DELETE FROM subscription_plans WHERE plan_id = ?");
$stmt->bind_param("i", $plan_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Plan deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
}

$stmt->close();
closeDBConnection($conn);
?>

