<?php
require_once 'config.php';

$data = [];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM customers");
$data['totalCustomers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM accounts");
$data['totalAccounts'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM transactions");
$data['totalTransactions'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM fraud_alerts WHERE status = 'pending'");
$data['fraudAlerts'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM reports WHERE status = 'pending'");
$data['pendingReports'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM fraud_alerts WHERE status = 'resolved'");
$data['resolvedAlerts'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT * FROM activities ORDER BY created_at DESC LIMIT 6");
$data['recentActivities'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT DAYNAME(created_at) as day, COUNT(*) as count FROM fraud_alerts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DAYNAME(created_at)");
$weeklyData = $stmt->fetchAll(PDO::FETCH_ASSOC);

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$weeklyFraud = [];
foreach ($days as $day) {
    $found = false;
    foreach ($weeklyData as $row) {
        if ($row['day'] === $day) {
            $weeklyFraud[] = intval($row['count']);
            $found = true;
            break;
        }
    }
    if (!$found) {
        $weeklyFraud[] = 0;
    }
}
$data['weeklyFraud'] = $weeklyFraud;

jsonResponse($data);
?>