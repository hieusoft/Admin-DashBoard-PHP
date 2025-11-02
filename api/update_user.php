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
$username = $data['username'] ?? '';
$language = $data['language'] ?? 'en';
$ref_by = !empty($data['ref_by']) ? (int)$data['ref_by'] : null;
$wallet_address = $data['wallet_address'] ?? null;
$verified_kol = $data['verified_kol'] ?? 'not_submitted';
$commission_percent = isset($data['commission_percent']) ? (float)$data['commission_percent'] : 30.00;

if (!$user_id || !$username) {
    echo json_encode(['success' => false, 'message' => 'User ID and Username are required']);
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

// Update user - handle NULL values properly
// MySQLi doesn't handle NULL well with bind_param, so we'll build query dynamically
$ref_by = (!empty($data['ref_by'])) ? (int)$data['ref_by'] : null;
$wallet_address = (!empty($data['wallet_address'])) ? $data['wallet_address'] : null;

// Build the query with proper NULL handling
$updates = [];
$params = [];
$types = '';

$updates[] = "username = ?";
$params[] = $username;
$types .= 's';

$updates[] = "language = ?";
$params[] = $language;
$types .= 's';

if ($ref_by !== null) {
    $updates[] = "ref_by = ?";
    $params[] = $ref_by;
    $types .= 'i';
} else {
    $updates[] = "ref_by = NULL";
}

if ($wallet_address !== null) {
    $updates[] = "wallet_address = ?";
    $params[] = $wallet_address;
    $types .= 's';
} else {
    $updates[] = "wallet_address = NULL";
}

$updates[] = "verified_kol = ?";
$params[] = $verified_kol;
$types .= 's';

$updates[] = "commission_percent = ?";
$params[] = $commission_percent;
$types .= 'd';

$params[] = $user_id;
$types .= 'i';

$sql = "UPDATE users SET " . implode(', ', $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE user_id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param($types, ...$params);
} else {
    echo json_encode(['success' => false, 'message' => 'Error preparing statement: ' . $conn->error]);
    closeDBConnection($conn);
    exit;
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'User updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
}

$stmt->close();
closeDBConnection($conn);
?>

