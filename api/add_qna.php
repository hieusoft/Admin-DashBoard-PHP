<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$catId = (int)($data['category_id'] ?? 0);
$question = trim($data['question'] ?? '');
$answer = trim($data['answer'] ?? '');

if ($catId <= 0 || $question === '' || $answer === '') {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

$conn = getDBConnection();
$stmt = $conn->prepare("INSERT INTO qna (category_id, question, answer) VALUES (?, ?, ?)");
$stmt->bind_param('iss', $catId, $question, $answer);

$success = $stmt->execute();
echo json_encode([
    'success' => $success,
    'message' => $success ? 'Thêm thành công' : 'Lỗi database'
]);

$stmt->close();
closeDBConnection($conn);