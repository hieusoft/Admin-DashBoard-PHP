<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$detail_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$detail_id) {
    echo json_encode(['success' => false, 'message' => 'Detail ID is required']);
    exit;
}

$conn = getDBConnection();
$stmt = $conn->prepare("SELECT * FROM subscription_details WHERE detail_id = ?");
$stmt->bind_param("i", $detail_id);
$stmt->execute();
$result = $stmt->get_result();

if ($detail = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'detail' => $detail]);
} else {
    echo json_encode(['success' => false, 'message' => 'Detail not found']);
}

$stmt->close();
closeDBConnection($conn);
?>

