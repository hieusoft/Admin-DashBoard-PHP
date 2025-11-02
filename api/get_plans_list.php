<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$conn = getDBConnection();
$plans = $conn->query("SELECT plan_id, name FROM subscription_plans WHERE is_active = 1 ORDER BY name ASC");

$plansArray = [];
while ($plan = $plans->fetch_assoc()) {
    $plansArray[] = $plan;
}

echo json_encode(['success' => true, 'plans' => $plansArray]);

closeDBConnection($conn);
?>

