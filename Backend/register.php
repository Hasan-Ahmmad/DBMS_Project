<?php
require_once 'config.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['email']) || !isset($data['password']) || !isset($data['name'])) {
    jsonResponse(['error' => 'Email, password and name are required'], 400);
}

$email = sanitize($data['email']);
$password = $data['password'];
$name = sanitize($data['name']);
$phone = isset($data['phone']) ? sanitize($data['phone']) : '';

// Validate email
if (!validateEmail($email)) {
    jsonResponse(['error' => 'Invalid email format. Please use a valid email like user@gmail.com'], 400);
}

// Validate password
if (!validatePassword($password)) {
    jsonResponse(['error' => 'Password must be at least 6 characters and contain both letters and numbers'], 400);
}

// Validate phone (optional but if provided, validate)
if (!empty($phone) && !validatePhone($phone)) {
    jsonResponse(['error' => 'Invalid phone number. Must be 11 digits starting with 013-019 (e.g., 017XXXXXXXX)'], 400);
}

// Check if user exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->rowCount() > 0) {
    jsonResponse(['error' => 'Email already registered'], 409);
}

// Hash password and insert user
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (email, password, name, phone) VALUES (?, ?, ?, ?)");
if ($stmt->execute([$email, $hashedPassword, $name, $phone])) {
    jsonResponse(['success' => true, 'message' => 'Registration successful']);
} else {
    jsonResponse(['error' => 'Registration failed'], 500);
}
?>