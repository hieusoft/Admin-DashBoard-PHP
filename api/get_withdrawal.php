<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

$conn = getDBConnection();
$stmt = $conn->prepare("
    SELECT aw.*, u.username 
    FROM affiliate_withdrawals aw
    LEFT JOIN users u ON aw.user_id = u.user_id
    WHERE aw.withdraw_id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => true, 'withdrawal' => $result->fetch_assoc()]);
} else {
    echo json_encode(['success' => false, 'message' => 'Not found']);
}

$stmt->close();
closeDBConnection($conn);