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
if ($check->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'User ID already exists']);
    closeDBConnection($conn);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO users (user_id, username, language, ref_by, wallet_address, verified_kol, commission_percent)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param("ississs", $user_id, $username, $language, $ref_by, $wallet_address, $verified_kol, $commission_percent);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'User added successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
}

$stmt->close();
closeDBConnection($conn);
?>

