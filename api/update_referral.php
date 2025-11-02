<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$conn = getDBConnection();

$ref_id = isset($data['ref_id']) ? (int)$data['ref_id'] : null;
$status = $data['status'] ?? 'pending';

if (!$ref_id) {
    echo json_encode(['success' => false, 'message' => 'Referral ID is required']);
    closeDBConnection($conn);
    exit;
}

$validStatuses = ['pending', 'approved', 'paid'];
if (!in_array($status, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    closeDBConnection($conn);
    exit;
}

$stmt = $conn->prepare("UPDATE affiliate_referrals SET status = ? WHERE ref_id = ?");
$stmt->bind_param("si", $status, $ref_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Referral status updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
}

$stmt->close();
closeDBConnection($conn);
?>

