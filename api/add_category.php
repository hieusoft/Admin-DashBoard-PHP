<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$name = trim($data['category_name'] ?? '');
$active = (int)($data['is_active'] ?? 1);

if ($name === '') {
    echo json_encode(['success' => false, 'message' => 'Tên category không được để trống']);
    exit;
}

$conn = getDBConnection();
$stmt = $conn->prepare("INSERT INTO qna_category (category_name, is_active) VALUES (?, ?)");
$stmt->bind_param('si', $name, $active);

$success = $stmt->execute();
echo json_encode([
    'success' => $success,
    'message' => $success ? 'Thêm thành công' : 'Lỗi database'
]);

$stmt->close();
closeDBConnection($conn);