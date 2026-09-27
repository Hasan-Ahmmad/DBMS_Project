<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            jsonResponse($stmt->fetch(PDO::FETCH_ASSOC) ?: ['error' => 'Customer not found']);
        } else {
            $search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';
            $status = isset($_GET['status']) && $_GET['status'] !== 'all' ? $_GET['status'] : null;
            
            $sql = "SELECT * FROM customers WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?";
            $params = [$search, $search, $search];
            
            if ($status) {
                $sql .= " AND status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            jsonResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['name']) || !isset($data['email']) || !isset($data['phone'])) {
            jsonResponse(['error' => 'Name, email and phone are required'], 400);
        }
        
        $name = sanitize($data['name']);
        $email = sanitize($data['email']);
        $phone = sanitize($data['phone']);
        $address = isset($data['address']) ? sanitize($data['address']) : '';
        $location = isset($data['location']) ? sanitize($data['location']) : '';
        $status = isset($data['status']) ? sanitize($data['status']) : 'active';
        
        // Validate email
        if (!validateEmail($email)) {
            jsonResponse(['error' => 'Invalid email format'], 400);
        }
        
        // Validate phone
        if (!validatePhone($phone)) {
            jsonResponse(['error' => 'Invalid phone number. Must be 11 digits starting with 013-019'], 400);
        }
        
        // Validate location
        if (empty($location)) {
            jsonResponse(['error' => 'Location is required'], 400);
        }
        
        $stmt = $pdo->prepare("INSERT INTO customers (name, email, phone, address, location, status) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $email, $phone, $address, $location, $status])) {
            $id = $pdo->lastInsertId();
            
            $logStmt = $pdo->prepare("INSERT INTO activities (type, title, description) VALUES ('user', ?, ?)");
            $logStmt->execute(["New customer registered: $name", "Customer added with email: $email"]);
            
            jsonResponse(['success' => true, 'id' => $id, 'message' => 'Customer added']);
        } else {
            jsonResponse(['error' => 'Failed to add customer'], 500);
        }
        break;
        
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($_GET['id'])) {
            jsonResponse(['error' => 'Customer ID required'], 400);
        }
        
        $id = $_GET['id'];
        $name = sanitize($data['name']);
        $email = sanitize($data['email']);
        $phone = sanitize($data['phone']);
        $address = isset($data['address']) ? sanitize($data['address']) : '';
        $location = isset($data['location']) ? sanitize($data['location']) : '';
        $status = sanitize($data['status']);
        
        // Validate email
        if (!validateEmail($email)) {
            jsonResponse(['error' => 'Invalid email format'], 400);
        }
        
        // Validate phone
        if (!validatePhone($phone)) {
            jsonResponse(['error' => 'Invalid phone number. Must be 11 digits starting with 013-019'], 400);
        }
        
        // Validate location
        if (empty($location)) {
            jsonResponse(['error' => 'Location is required'], 400);
        }
        
        $stmt = $pdo->prepare("UPDATE customers SET name = ?, email = ?, phone = ?, address = ?, location = ?, status = ? WHERE id = ?");
        if ($stmt->execute([$name, $email, $phone, $address, $location, $status, $id])) {
            jsonResponse(['success' => true, 'message' => 'Customer updated']);
        } else {
            jsonResponse(['error' => 'Failed to update customer'], 500);
        }
        break;
        
    case 'DELETE':
        if (!isset($_GET['id'])) {
            jsonResponse(['error' => 'Customer ID required'], 400);
        }
        
        $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
        if ($stmt->execute([$_GET['id']])) {
            jsonResponse(['success' => true, 'message' => 'Customer deleted']);
        } else {
            jsonResponse(['error' => 'Failed to delete customer'], 500);
        }
        break;
}
?>