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
$user_id = isset($data['user_id']) ? (int)$data['user_id'] : null;
$start_date = !empty($data['start_date']) ? $data['start_date'] : null;
$end_date = !empty($data['end_date']) ? $data['end_date'] : null;
$status = $data['status'] ?? 'pending';
$trial = isset($data['trial']) ? (int)$data['trial'] : 0;

if (!$sub_id || !$user_id) {
    echo json_encode(['success' => false, 'message' => 'Subscription ID and User ID are required']);
    closeDBConnection($conn);
    exit;
}

// Check if subscription exists
$check = $conn->prepare("SELECT sub_id FROM subscriptions WHERE sub_id = ?");
$check->bind_param("i", $sub_id);
$check->execute();
if ($check->get_result()->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Subscription not found']);
    closeDBConnection($conn);
    exit;
}

// Convert datetime-local format to MySQL datetime format
if ($start_date && $start_date !== '') {
    $start_date = str_replace('T', ' ', $start_date) . ':00';
} else {
    $start_date = null;
}

if ($end_date && $end_date !== '') {
    $end_date = str_replace('T', ' ', $end_date) . ':00';
} else {
    $end_date = null;
}

// Build query
$updates = [];
$params = [];
$types = '';

$updates[] = "user_id = ?";
$params[] = $user_id;
$types .= 'i';

if ($start_date !== null) {
    $updates[] = "start_date = ?";
    $params[] = $start_date;
    $types .= 's';
} else {
    $updates[] = "start_date = NULL";
}

if ($end_date !== null) {
    $updates[] = "end_date = ?";
    $params[] = $end_date;
    $types .= 's';
} else {
    $updates[] = "end_date = NULL";
}

$updates[] = "status = ?";
$params[] = $status;
$types .= 's';

$updates[] = "trial = ?";
$params[] = $trial;
$types .= 'i';

$params[] = $sub_id;
$types .= 'i';

$sql = "UPDATE subscriptions SET " . implode(', ', $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE sub_id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Subscription updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error preparing statement: ' . $conn->error]);
}

if ($stmt) $stmt->close();
closeDBConnection($conn);
?>

