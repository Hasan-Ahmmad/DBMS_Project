<?php
require_once 'config.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['email']) || !isset($data['password'])) {
    jsonResponse(['error' => 'Email and password are required'], 400);
}

$email = sanitize($data['email']);
$password = $data['password'];

// Validate email
if (!validateEmail($email)) {
    jsonResponse(['error' => 'Invalid email format'], 400);
}

$stmt = $pdo->prepare("SELECT id, email, password, name, phone FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password'])) {
    // Remove password from response
    unset($user['password']);
    jsonResponse(['success' => true, 'user' => $user]);
} else {
    jsonResponse(['error' => 'Invalid email or password'], 401);
}
?>