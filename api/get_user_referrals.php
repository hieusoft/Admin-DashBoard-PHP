<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

$conn = getDBConnection();

// Get referrals where this user is the referrer
$referrals = $conn->prepare("
    SELECT ar.*, 
           u1.username as referrer_name,
           u2.username as referred_name
    FROM affiliate_referrals ar
    LEFT JOIN users u1 ON ar.referrer_id = u1.user_id
    LEFT JOIN users u2 ON ar.referred_id = u2.user_id
    WHERE ar.referrer_id = ?
    ORDER BY ar.created_at DESC
");

$referrals->bind_param("i", $user_id);
$referrals->execute();
$result = $referrals->get_result();

$referralsArray = [];
while ($ref = $result->fetch_assoc()) {
    $referralsArray[] = $ref;
}

echo json_encode(['success' => true, 'referrals' => $referralsArray, 'count' => count($referralsArray)]);

$referrals->close();
closeDBConnection($conn);
?>

