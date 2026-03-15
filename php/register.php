<?php
/**
 * php/register.php
 * Handles user registration
 * Stores credentials in MySQL using Prepared Statements
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Read JSON body
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input']);
    exit;
}

$name     = trim($input['name']     ?? '');
$email    = trim($input['email']    ?? '');
$password =      $input['password'] ?? '';

// Validation
if (empty($name) || empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email address']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 6 characters']);
    exit;
}

// MySQL Connection
$host   = 'localhost';
$dbname = 'guvi_internship';
$dbuser = 'root';
$dbpass = '';

$conn = new mysqli($host, $dbuser, $dbpass, $dbname);

if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// Check if email exists (Prepared Statement)
$checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$checkStmt->bind_param("s", $email);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
    $checkStmt->close();
    $conn->close();
    echo json_encode(['status' => 'error', 'message' => 'Email already registered. Please login.']);
    exit;
}
$checkStmt->close();

// Hash password
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// Insert user (Prepared Statement)
$insertStmt = $conn->prepare("INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, NOW())");
$insertStmt->bind_param("sss", $name, $email, $hashedPassword);

if ($insertStmt->execute()) {
    $insertStmt->close();
    $conn->close();
    echo json_encode([
        'status'  => 'success',
        'message' => 'Registration successful'
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Registration failed']);
    $insertStmt->close();
    $conn->close();
}
?>