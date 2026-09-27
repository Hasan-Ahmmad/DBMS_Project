<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT a.*, c.name as customer_name FROM accounts a JOIN customers c ON a.customer_id = c.id WHERE a.id = ?");
            $stmt->execute([$_GET['id']]);
            jsonResponse($stmt->fetch(PDO::FETCH_ASSOC) ?: ['error' => 'Account not found']);
        } else {
            $search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';
            $type = isset($_GET['type']) && $_GET['type'] !== 'all' ? $_GET['type'] : null;
            
            $sql = "SELECT a.*, c.name as customer_name FROM accounts a JOIN customers c ON a.customer_id = c.id WHERE a.account_number LIKE ? OR c.name LIKE ? OR c.phone LIKE ?";
            $params = [$search, $search, $search];
            
            if ($type) {
                $sql .= " AND a.type = ?";
                $params[] = $type;
            }
            
            $sql .= " ORDER BY a.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            jsonResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['customer_id']) || !isset($data['account_number']) || !isset($data['type'])) {
            jsonResponse(['error' => 'Customer ID, account number and type are required'], 400);
        }
        
        $customer_id = $data['customer_id'];
        $account_number = sanitize($data['account_number']);
        $type = sanitize($data['type']);
        $balance = isset($data['balance']) ? floatval($data['balance']) : 0;
        $status = isset($data['status']) ? sanitize($data['status']) : 'active';
        
        $stmt = $pdo->prepare("INSERT INTO accounts (customer_id, account_number, type, balance, status) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$customer_id, $account_number, $type, $balance, $status])) {
            $id = $pdo->lastInsertId();
            
            $logStmt = $pdo->prepare("INSERT INTO activities (type, title, description) VALUES ('user', ?, ?)");
            $logStmt->execute(["New account created: $account_number", "Account type: $type, Balance: $balance"]);
            
            jsonResponse(['success' => true, 'id' => $id, 'message' => 'Account added']);
        } else {
            jsonResponse(['error' => 'Failed to add account'], 500);
        }
        break;
        
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($_GET['id'])) {
            jsonResponse(['error' => 'Account ID required'], 400);
        }
        
        $id = $_GET['id'];
        $customer_id = $data['customer_id'];
        $account_number = sanitize($data['account_number']);
        $type = sanitize($data['type']);
        $balance = floatval($data['balance']);
        $status = sanitize($data['status']);
        
        $stmt = $pdo->prepare("UPDATE accounts SET customer_id = ?, account_number = ?, type = ?, balance = ?, status = ? WHERE id = ?");
        if ($stmt->execute([$customer_id, $account_number, $type, $balance, $status, $id])) {
            jsonResponse(['success' => true, 'message' => 'Account updated']);
        } else {
            jsonResponse(['error' => 'Failed to update account'], 500);
        }
        break;
        
    case 'DELETE':
        if (!isset($_GET['id'])) {
            jsonResponse(['error' => 'Account ID required'], 400);
        }
        
        $stmt = $pdo->prepare("DELETE FROM accounts WHERE id = ?");
        if ($stmt->execute([$_GET['id']])) {
            jsonResponse(['success' => true, 'message' => 'Account deleted']);
        } else {
            jsonResponse(['error' => 'Failed to delete account'], 500);
        }
        break;
}
?>
