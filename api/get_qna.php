<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$catId = (int)($_GET['category_id'] ?? 0);
if ($catId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

$conn = getDBConnection();
$stmt = $conn->prepare("SELECT qna_id, question, answer FROM qna WHERE category_id = ? ORDER BY created_at DESC");
$stmt->bind_param('i', $catId);
$stmt->execute();
$result = $stmt->get_result();

$qna = [];
while ($row = $result->fetch_assoc()) {
    $qna[] = $row;
}

echo json_encode(['success' => true, 'qna' => $qna]);
$stmt->close();
closeDBConnection($conn);