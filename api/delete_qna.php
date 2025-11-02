<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$conn = getDBConnection();

$qna_id = isset($data['qna_id']) ? (int)$data['qna_id'] : null;

if (!$qna_id) {
    echo json_encode(['success' => false, 'message' => 'QnA ID is required']);
    closeDBConnection($conn);
    exit;
}

$stmt = $conn->prepare("DELETE FROM qna WHERE qna_id = ?");
$stmt->bind_param("i", $qna_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'QnA deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
}

$stmt->close();
closeDBConnection($conn);
?>

