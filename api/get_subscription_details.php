<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$sub_id = isset($_GET['sub_id']) ? (int)$_GET['sub_id'] : null;

if (!$sub_id) {
    echo json_encode(['success' => false, 'message' => 'Subscription ID is required']);
    exit;
}

$conn = getDBConnection();

// Get subscription details
$stmt = $conn->prepare("
    SELECT sd.*, 
           sp.name as plan_name,
           sp.price as plan_price,
           p.amount as payment_amount,
           p.currency as payment_currency
    FROM subscription_details sd
    LEFT JOIN subscription_plans sp ON sd.plan_id = sp.plan_id
    LEFT JOIN payments p ON sd.payment_id = p.payment_id
    WHERE sd.sub_id = ?
    ORDER BY COALESCE(sd.expired_at, '1970-01-01') DESC, sd.activated_at DESC
");

$stmt->bind_param("i", $sub_id);
$stmt->execute();
$result = $stmt->get_result();

$detailsArray = [];
while ($detail = $result->fetch_assoc()) {
    $detailsArray[] = $detail;
}

echo json_encode(['success' => true, 'details' => $detailsArray]);

$stmt->close();
closeDBConnection($conn);
?>

