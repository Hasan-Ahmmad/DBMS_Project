<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        $search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';
        $severity = isset($_GET['severity']) && $_GET['severity'] !== 'all' ? $_GET['severity'] : null;
        
        $sql = "SELECT * FROM fraud_alerts WHERE description LIKE ? OR type LIKE ?";
        $params = [$search, $search];
        
        if ($severity) {
            $sql .= " AND severity = ?";
            $params[] = $severity;
        }
        
        $sql .= " ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        jsonResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['type']) || !isset($data['description'])) {
            jsonResponse(['error' => 'Type and description are required'], 400);
        }
        
        $transaction_id = isset($data['transaction_id']) ? $data['transaction_id'] : null;
        $type = sanitize($data['type']);
        $severity = isset($data['severity']) ? sanitize($data['severity']) : 'medium';
        $description = sanitize($data['description']);
        $status = isset($data['status']) ? sanitize($data['status']) : 'pending';
        
        $stmt = $pdo->prepare("INSERT INTO fraud_alerts (transaction_id, type, severity, description, status) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$transaction_id, $type, $severity, $description, $status])) {
            $id = $pdo->lastInsertId();
            
            $logStmt = $pdo->prepare("INSERT INTO activities (type, title, description) VALUES ('fraud', ?, ?)");
            $logStmt->execute(["Fraud alert: $type", "Severity: $severity"]);
            
            jsonResponse(['success' => true, 'id' => $id, 'message' => 'Alert created']);
        } else {
            jsonResponse(['error' => 'Failed to create alert'], 500);
        }
        break;
        
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($_GET['id'])) {
            jsonResponse(['error' => 'Alert ID required'], 400);
        }
        
        $id = $_GET['id'];
        $status = sanitize($data['status']);
        
        $stmt = $pdo->prepare("UPDATE fraud_alerts SET status = ? WHERE id = ?");
        if ($stmt->execute([$status, $id])) {
            if ($status === 'resolved') {
                $logStmt = $pdo->prepare("INSERT INTO activities (type, title, description) VALUES ('fraud', ?, ?)");
                $logStmt->execute(["Alert #$id resolved", "Alert resolved successfully"]);
            }
            jsonResponse(['success' => true, 'message' => 'Alert updated']);
        } else {
            jsonResponse(['error' => 'Failed to update alert'], 500);
        }
        break;
        
    case 'DELETE':
        if (!isset($_GET['id'])) {
            jsonResponse(['error' => 'Alert ID required'], 400);
        }
        
        $stmt = $pdo->prepare("DELETE FROM fraud_alerts WHERE id = ?");
        if ($stmt->execute([$_GET['id']])) {
            jsonResponse(['success' => true, 'message' => 'Alert deleted']);
        } else {
            jsonResponse(['error' => 'Failed to delete alert'], 500);
        }
        break;
}
?>