<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load Composer packages (Predis)
require_once __DIR__ . '/../vendor/autoload.php';

// ── LOGOUT ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $token = $input['token'] ?? '';

    if ($token) {
        try {
            $redis = new Predis\Client([
                'scheme'   => 'tcp',
                'host'     => 'ballast.proxy.rlwy.net',
                'port'     => 54155,
                'password' => 'nbFMhWITclrhrFQtoFwFNDuRpxrZgDvv',
            ]);
            $redis->del('session:' . $token);
        } catch (Exception $e) {
            // Redis unavailable
        }
    }

    echo json_encode(['status' => 'success', 'message' => 'Logged out']);
    exit;
}

// ── LOGIN ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$input    = json_decode(file_get_contents('php://input'), true);
$email    = trim($input['email']    ?? '');
$password =      $input['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Email and password are required']);
    exit;
}

// MySQL Connection
$host   = 'shinkansen.proxy.rlwy.net';
$dbname = 'railway';
$dbuser = 'root';
$dbpass = 'wGgfwSfOKuMQPteqjXbEhMnksrzgkbNr';
$port   = 43353;

$conn = new mysqli($host, $dbuser, $dbpass, $dbname, $port);

if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// Fetch user by email (Prepared Statement)
$stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Verify password
if (!password_verify($password, $user['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
    exit;
}

// Generate session token
$sessionToken = bin2hex(random_bytes(32));

// Store session in Redis (24 hours)
try {
    $redis = new Predis\Client([
        'scheme'   => 'tcp',
        'host'     => 'ballast.proxy.rlwy.net',
        'port'     => 54155,
        'password' => 'nbFMhWITclrhrFQtoFwFNDuRpxrZgDvv',
    ]);

    $sessionData = json_encode([
        'user_id' => $user['id'],
        'email'   => $user['email'],
        'name'    => $user['name'],
        'login_time' => time()
    ]);

    $redis->setex('session:' . $sessionToken, 86400, $sessionData);

} catch (Exception $e) {
    error_log('Redis error: ' . $e->getMessage());
}

// Return token to frontend
echo json_encode([
    'status'        => 'success',
    'message'       => 'Login successful',
    'session_token' => $sessionToken,
    'email'         => $user['email'],
    'name'          => $user['name']
]);
?>