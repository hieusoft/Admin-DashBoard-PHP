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
$name = $data['name'] ?? '';
$price = isset($data['price']) ? (float)$data['price'] : 0.00;
$sale_percent = isset($data['sale_percent']) ? (float)$data['sale_percent'] : 0.00;

// Convert datetime-local format (YYYY-MM-DDTHH:mm) to MySQL datetime format (YYYY-MM-DD HH:mm:ss)
$sale_start = null;
if (!empty($data['sale_start'])) {
    $sale_start = str_replace('T', ' ', $data['sale_start']) . ':00';
}

$sale_end = null;
if (!empty($data['sale_end'])) {
    $sale_end = str_replace('T', ' ', $data['sale_end']) . ':00';
}

$duration_days = isset($data['duration_days']) ? (int)$data['duration_days'] : 30;
$is_active = isset($data['is_active']) ? (int)$data['is_active'] : 1;

if (!$plan_id || !$name || $duration_days <= 0) {
    echo json_encode(['success' => false, 'message' => 'Plan ID, Name and Duration days are required']);
    closeDBConnection($conn);
    exit;
}

// Check if plan exists
$check = $conn->prepare("SELECT plan_id FROM subscription_plans WHERE plan_id = ?");
$check->bind_param("i", $plan_id);
$check->execute();
if ($check->get_result()->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Plan not found']);
    closeDBConnection($conn);
    exit;
}

// Build query with optional NULL handling
$updates = [];
$params = [];
$types = '';

$updates[] = "name = ?";
$params[] = $name;
$types .= 's';

$updates[] = "price = ?";
$params[] = $price;
$types .= 'd';

$updates[] = "sale_percent = ?";
$params[] = $sale_percent;
$types .= 'd';

if ($sale_start !== null && $sale_start !== '') {
    $updates[] = "sale_start = ?";
    $params[] = $sale_start;
    $types .= 's';
} else {
    $updates[] = "sale_start = NULL";
}

if ($sale_end !== null && $sale_end !== '') {
    $updates[] = "sale_end = ?";
    $params[] = $sale_end;
    $types .= 's';
} else {
    $updates[] = "sale_end = NULL";
}

$updates[] = "duration_days = ?";
$params[] = $duration_days;
$types .= 'i';

$updates[] = "is_active = ?";
$params[] = $is_active;
$types .= 'i';

$params[] = $plan_id;
$types .= 'i';

$sql = "UPDATE subscription_plans SET " . implode(', ', $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE plan_id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Plan updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error preparing statement: ' . $conn->error]);
}

if ($stmt) $stmt->close();
closeDBConnection($conn);
?>

