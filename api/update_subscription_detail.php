<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$conn = getDBConnection();

$detail_id = isset($data['detail_id']) ? (int)$data['detail_id'] : null;
$plan_id = isset($data['plan_id']) ? (int)$data['plan_id'] : null;
$payment_id = !empty($data['payment_id']) ? (int)$data['payment_id'] : null;
$expired_at = !empty($data['expired_at']) ? $data['expired_at'] : null;
$renewed = isset($data['renewed']) ? (int)$data['renewed'] : 0;

if (!$detail_id || !$plan_id) {
    echo json_encode(['success' => false, 'message' => 'Detail ID and Plan ID are required']);
    closeDBConnection($conn);
    exit;
}

// Check if detail exists and get sub_id
$check = $conn->prepare("SELECT detail_id, sub_id FROM subscription_details WHERE detail_id = ?");
$check->bind_param("i", $detail_id);
$check->execute();
$checkResult = $check->get_result();
if ($checkResult->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Detail not found']);
    closeDBConnection($conn);
    exit;
}
$detailInfo = $checkResult->fetch_assoc();
$sub_id = $detailInfo['sub_id'];

// Convert datetime-local format to MySQL datetime format
if ($expired_at && $expired_at !== '') {
    $expired_at = str_replace('T', ' ', $expired_at) . ':00';
} else {
    $expired_at = null;
}

// Build query
$updates = [];
$params = [];
$types = '';

$updates[] = "plan_id = ?";
$params[] = $plan_id;
$types .= 'i';

if ($payment_id !== null) {
    $updates[] = "payment_id = ?";
    $params[] = $payment_id;
    $types .= 'i';
} else {
    $updates[] = "payment_id = NULL";
}

if ($expired_at !== null) {
    $updates[] = "expired_at = ?";
    $params[] = $expired_at;
    $types .= 's';
} else {
    $updates[] = "expired_at = NULL";
}

$updates[] = "renewed = ?";
$params[] = $renewed;
$types .= 'i';

$params[] = $detail_id;
$types .= 'i';

$sql = "UPDATE subscription_details SET " . implode(', ', $updates) . " WHERE detail_id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        // After updating detail, find the maximum expired_at from all details
        // and update subscription.end_date to match the latest expired date
        $maxExpiredQuery = $conn->prepare("
            SELECT MAX(expired_at) as max_expired 
            FROM subscription_details 
            WHERE sub_id = ? AND expired_at IS NOT NULL
        ");
        $maxExpiredQuery->bind_param("i", $sub_id);
        $maxExpiredQuery->execute();
        $maxResult = $maxExpiredQuery->get_result();
        $maxData = $maxResult->fetch_assoc();
        
        // Update subscription.end_date to the maximum expired_at (or NULL if none)
        $newEndDate = $maxData && $maxData['max_expired'] ? $maxData['max_expired'] : null;
        $updateSub = $conn->prepare("
            UPDATE subscriptions 
            SET end_date = ?, updated_at = CURRENT_TIMESTAMP
            WHERE sub_id = ?
        ");
        $updateSub->bind_param("si", $newEndDate, $sub_id);
        $updateSub->execute();
        $updateSub->close();
        $maxExpiredQuery->close();
        
        echo json_encode(['success' => true, 'message' => 'Detail updated successfully. Subscription end date updated to match latest detail.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error preparing statement: ' . $conn->error]);
}

if ($stmt) $stmt->close();
closeDBConnection($conn);
?>

