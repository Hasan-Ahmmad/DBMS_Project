<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        $search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';
        
        $stmt = $pdo->prepare("SELECT * FROM reports WHERE title LIKE ? OR description LIKE ? ORDER BY generated_at DESC");
        $stmt->execute([$search, $search]);
        jsonResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['title']) || !isset($data['type'])) {
            jsonResponse(['error' => 'Title and type are required'], 400);
        }
        
        $title = sanitize($data['title']);
        $type = sanitize($data['type']);
        $description = isset($data['description']) ? sanitize($data['description']) : '';
        $status = isset($data['status']) ? sanitize($data['status']) : 'pending';
        
        $stmt = $pdo->prepare("INSERT INTO reports (title, type, description, status) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$title, $type, $description, $status])) {
            $id = $pdo->lastInsertId();
            
            $logStmt = $pdo->prepare("INSERT INTO activities (type, title, description) VALUES ('report', ?, ?)");
            $logStmt->execute(["New report: $title", "Report type: $type"]);
            
            jsonResponse(['success' => true, 'id' => $id, 'message' => 'Report created']);
        } else {
            jsonResponse(['error' => 'Failed to create report'], 500);
        }
        break;
        
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($_GET['id'])) {
            jsonResponse(['error' => 'Report ID required'], 400);
        }
        
        $id = $_GET['id'];
        $title = sanitize($data['title']);
        $type = sanitize($data['type']);
        $description = sanitize($data['description']);
        $status = sanitize($data['status']);
        
        $stmt = $pdo->prepare("UPDATE reports SET title = ?, type = ?, description = ?, status = ? WHERE id = ?");
        if ($stmt->execute([$title, $type, $description, $status, $id])) {
            jsonResponse(['success' => true, 'message' => 'Report updated']);
        } else {
            jsonResponse(['error' => 'Failed to update report'], 500);
        }
        break;
        
    case 'DELETE':
        if (!isset($_GET['id'])) {
            jsonResponse(['error' => 'Report ID required'], 400);
        }
        
        $stmt = $pdo->prepare("DELETE FROM reports WHERE id = ?");
        if ($stmt->execute([$_GET['id']])) {
            jsonResponse(['success' => true, 'message' => 'Report deleted']);
        } else {
            jsonResponse(['error' => 'Failed to delete report'], 500);
        }
        break;
}
?>