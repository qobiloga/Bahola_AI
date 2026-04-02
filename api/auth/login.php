<?php
/**
 * Ustoz ko'makchi - Login API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once '../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';
$role = $input['role'] ?? 'student';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email va parol kiritilishi shart']);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE (email = ? OR hemis_id = ?) AND role = ?");
    $stmt->execute([$email, $email, $role]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Email yoki parol noto\'g\'ri']);
        exit;
    }

    // Generate simple token (in production use JWT)
    $token = bin2hex(random_bytes(32));

    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'hemis_id' => $user['hemis_id'],
            'avatar' => $user['avatar'],
        ],
        'token' => $token
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server xatosi']);
}
