<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$conn = getDBConnection();

$category_id = isset($data['category_id']) ? (int)$data['category_id'] : null;

if (!$category_id) {
    echo json_encode(['success' => false, 'message' => 'Category ID is required']);
    closeDBConnection($conn);
    exit;
}

$stmt = $conn->prepare("DELETE FROM qna_category WHERE category_id = ?");
$stmt->bind_param("i", $category_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
}

$stmt->close();
closeDBConnection($conn);
?>

