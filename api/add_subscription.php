<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$conn = getDBConnection();

$user_id = isset($data['user_id']) ? (int)$data['user_id'] : null;
$start_date = !empty($data['start_date']) ? $data['start_date'] : null;
$end_date = !empty($data['end_date']) ? $data['end_date'] : null;
$status = $data['status'] ?? 'pending';
$trial = isset($data['trial']) ? (int)$data['trial'] : 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    closeDBConnection($conn);
    exit;
}

// Check if user exists
$check = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
$check->bind_param("i", $user_id);
$check->execute();
if ($check->get_result()->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    closeDBConnection($conn);
    exit;
}

// Convert datetime-local format to MySQL datetime format
if ($start_date && $start_date !== '') {
    $start_date = str_replace('T', ' ', $start_date) . ':00';
}

if ($end_date && $end_date !== '') {
    $end_date = str_replace('T', ' ', $end_date) . ':00';
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

$sql = "INSERT INTO subscriptions SET " . implode(', ', $updates);
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Subscription added successfully', 'sub_id' => $conn->insert_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error preparing statement: ' . $conn->error]);
}

if ($stmt) $stmt->close();
closeDBConnection($conn);
?>

