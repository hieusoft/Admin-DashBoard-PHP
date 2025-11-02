<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$plan_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$plan_id) {
    echo json_encode(['success' => false, 'message' => 'Plan ID is required']);
    exit;
}

$conn = getDBConnection();
$stmt = $conn->prepare("SELECT * FROM subscription_plans WHERE plan_id = ?");
$stmt->bind_param("i", $plan_id);
$stmt->execute();
$result = $stmt->get_result();

if ($plan = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'plan' => $plan]);
} else {
    echo json_encode(['success' => false, 'message' => 'Plan not found']);
}

$stmt->close();
closeDBConnection($conn);
?>

