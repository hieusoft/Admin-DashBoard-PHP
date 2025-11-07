<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$withdraw_id = (int)($data['withdraw_id'] ?? 0);
$status = $data['status'] ?? '';
$tx_hash = trim($data['tx_hash'] ?? '');

if ($withdraw_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid withdrawal ID']);
    exit;
}

if (!in_array($status, ['approved', 'rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status. Use "approved" or "rejected"']);
    exit;
}

if ($status === 'approved') {
    if (empty($tx_hash)) {
        echo json_encode(['success' => false, 'message' => 'TX Hash is required when approving']);
        exit;
    }
    if (!preg_match('/^[a-fA-F0-9]{64}$/', $tx_hash)) {
        echo json_encode(['success' => false, 'message' => 'Invalid TX Hash format (64 hex characters)']);
        exit;
    }
} else {
    $tx_hash = null;
}

$conn = getDBConnection();

// ✅ Lấy user_id của lệnh rút
$userQuery = $conn->prepare("SELECT user_id FROM affiliate_withdrawals WHERE withdraw_id = ?");
$userQuery->bind_param('i', $withdraw_id);
$userQuery->execute();
$userResult = $userQuery->get_result();
$user = $userResult->fetch_assoc();
$user_id = $user['user_id'] ?? null;
$userQuery->close();

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User not found for this withdrawal']);
    exit;
}

if ($status === 'approved') {
    $stmt = $conn->prepare("UPDATE affiliate_withdrawals SET status = ?, tx_hash = ? WHERE withdraw_id = ?");
    $stmt->bind_param('ssi', $status, $tx_hash, $withdraw_id);
} else {
    $stmt = $conn->prepare("UPDATE affiliate_withdrawals SET status = ?, tx_hash = NULL WHERE withdraw_id = ?");
    $stmt->bind_param('si', $status, $withdraw_id);
}

$success = $stmt->execute();


echo json_encode([
    'success' => $success,
    'message' => $success ? 'Withdrawal updated successfully' : 'Database error: ' . $stmt->error
]);

$stmt->close();
closeDBConnection($conn);
