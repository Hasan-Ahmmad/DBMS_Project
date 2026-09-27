<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT t.*, a.account_number, a.customer_id FROM transactions t JOIN accounts a ON t.account_id = a.id WHERE t.id = ?");
            $stmt->execute([$_GET['id']]);
            jsonResponse($stmt->fetch(PDO::FETCH_ASSOC) ?: ['error' => 'Transaction not found']);
        } else {
            $search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';
            $status = isset($_GET['status']) && $_GET['status'] !== 'all' ? $_GET['status'] : null;
            
            $sql = "SELECT t.*, a.account_number, a.customer_id FROM transactions t JOIN accounts a ON t.account_id = a.id WHERE t.description LIKE ? OR a.account_number LIKE ?";
            $params = [$search, $search];
            
            if ($status) {
                $sql .= " AND t.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY t.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            jsonResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['account_id']) || !isset($data['type']) || !isset($data['amount'])) {
            jsonResponse(['error' => 'Account ID, type and amount are required'], 400);
        }
        
        $account_id = $data['account_id'];
        $type = sanitize($data['type']);
        $amount = floatval($data['amount']);
        $description = isset($data['description']) ? sanitize($data['description']) : '';
        $location = isset($data['location']) ? sanitize($data['location']) : '';
        $status = isset($data['status']) ? sanitize($data['status']) : 'pending';
        $ip_address = isset($data['ip_address']) ? sanitize($data['ip_address']) : '';
        $device_id = isset($data['device_id']) ? sanitize($data['device_id']) : '';
        
        if (empty($location)) {
            jsonResponse(['error' => 'Location is required'], 400);
        }
        
        $stmt = $pdo->prepare("INSERT INTO transactions (account_id, type, amount, description, location, status, ip_address, device_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$account_id, $type, $amount, $description, $location, $status, $ip_address, $device_id])) {
            $id = $pdo->lastInsertId();
            
            // Update account balance
            if ($type === 'deposit') {
                $updateStmt = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?");
                $updateStmt->execute([$amount, $account_id]);
            } elseif ($type === 'withdrawal' || $type === 'transfer') {
                $updateStmt = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
                $updateStmt->execute([$amount, $account_id]);
            }
            
            $logStmt = $pdo->prepare("INSERT INTO activities (type, title, description) VALUES ('transaction', ?, ?)");
            $logStmt->execute(["New $type: $$amount", "Transaction on account #$account_id"]);
            
            jsonResponse(['success' => true, 'id' => $id, 'message' => 'Transaction added']);
        } else {
            jsonResponse(['error' => 'Failed to add transaction'], 500);
        }
        break;
        
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($_GET['id'])) {
            jsonResponse(['error' => 'Transaction ID required'], 400);
        }
        
        $id = $_GET['id'];
        $type = sanitize($data['type']);
        $amount = floatval($data['amount']);
        $description = sanitize($data['description']);
        $location = sanitize($data['location']);
        $status = sanitize($data['status']);
        
        if (empty($location)) {
            jsonResponse(['error' => 'Location is required'], 400);
        }
        
        $stmt = $pdo->prepare("UPDATE transactions SET type = ?, amount = ?, description = ?, location = ?, status = ? WHERE id = ?");
        if ($stmt->execute([$type, $amount, $description, $location, $status, $id])) {
            jsonResponse(['success' => true, 'message' => 'Transaction updated']);
        } else {
            jsonResponse(['error' => 'Failed to update transaction'], 500);
        }
        break;
        
    case 'DELETE':
        if (!isset($_GET['id'])) {
            jsonResponse(['error' => 'Transaction ID required'], 400);
        }
        
        $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ?");
        if ($stmt->execute([$_GET['id']])) {
            jsonResponse(['success' => true, 'message' => 'Transaction deleted']);
        } else {
            jsonResponse(['error' => 'Failed to delete transaction'], 500);
        }
        break;
}
?>