<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$id = (int)($data['category_id'] ?? 0);
$name = trim($data['category_name'] ?? '');
$active = (int)($data['is_active'] ?? 1);

if ($id <= 0 || $name === '') {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

$conn = getDBConnection();
$stmt = $conn->prepare("UPDATE qna_category SET category_name = ?, is_active = ? WHERE category_id = ?");
$stmt->bind_param('sii', $name, $active, $id);

$success = $stmt->execute();
echo json_encode([
    'success' => $success,
    'message' => $success ? 'Cập nhật thành công' : 'Lỗi database'
]);

$stmt->close();
closeDBConnection($conn);