<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load Composer packages
require_once __DIR__ . '/../vendor/autoload.php';

// ── Helper: Validate Redis session ────────────────────────────────────
function validateSession($token) {
    if (empty($token)) return false;
    try {
        $redis = new Predis\Client([
            'scheme'   => 'tcp',
            'host'     => 'ballast.proxy.rlwy.net',
            'port'     => 54155,
            'password' => 'nbFMhWITclrhrFQtoFwFNDuRpxrZgDvv',
        ]);
        $sessionData = $redis->get('session:' . $token);
        return $sessionData ? json_decode($sessionData, true) : false;
    } catch (Exception $e) {
        return ['user_id' => 0, 'email' => ''];
    }
}

// ── Helper: MongoDB connection ────────────────────────────────────────
function getMongoCollection() {
    $client = new MongoDB\Client(
        "mongodb+srv://rufusrajith_db_user:zWey7hY8TB37aTtl@cluster0.eo3mzuf.mongodb.net/?appName=Cluster0",
        [],
        [
            'driver' => [
                'name' => 'mongo-php-driver',
            ],
            'ssl' => true,
            'tls' => true,
            'tlsAllowInvalidCertificates' => true,
        ]
    );
    return $client->guvi_internship->user_profiles;
}

// ── GET: Load profile ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $token = $_GET['token'] ?? '';
    $email = $_GET['email'] ?? '';

    $session = validateSession($token);
    if (!$session) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    try {
        $collection = getMongoCollection();
        $profile = $collection->findOne(['email' => $email]);

        if ($profile) {
            echo json_encode([
                'status'  => 'success',
                'profile' => [
                    'age'     => $profile['age']     ?? '',
                    'dob'     => $profile['dob']     ?? '',
                    'contact' => $profile['contact'] ?? '',
                    'city'    => $profile['city']    ?? '',
                    'bio'     => $profile['bio']     ?? '',
                ]
            ]);
        } else {
            echo json_encode(['status' => 'success', 'profile' => null]);
        }

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'MongoDB error: ' . $e->getMessage()]);
    }
    exit;
}

// ── POST: Save/Update profile ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input']);
        exit;
    }

    $token   = $input['token']   ?? '';
    $email   = $input['email']   ?? '';
    $age     = $input['age']     ?? null;
    $dob     = $input['dob']     ?? '';
    $contact = $input['contact'] ?? '';
    $city    = $input['city']    ?? '';
    $bio     = $input['bio']     ?? '';

    // Validate session
    $session = validateSession($token);
    if (!$session) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    // Validation
    if (empty($email) || empty($dob) || empty($contact)) {
        echo json_encode(['status' => 'error', 'message' => 'Email, DOB and contact are required']);
        exit;
    }

    try {
        $collection = getMongoCollection();

        // Upsert profile in MongoDB
        $collection->updateOne(
            ['email' => $email],
            [
                '$set' => [
                    'email'      => $email,
                    'age'        => (int) $age,
                    'dob'        => $dob,
                    'contact'    => $contact,
                    'city'       => $city,
                    'bio'        => $bio,
                    'updated_at' => new MongoDB\BSON\UTCDateTime()
                ]
            ],
            ['upsert' => true]
        );

        echo json_encode([
            'status'  => 'success',
            'message' => 'Profile updated successfully'
        ]);

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'MongoDB error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
?>