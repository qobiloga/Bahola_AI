<?php
/**
 * Ustoz ko'makchi - Register API
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

$name = trim($input['full_name'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');
$password = $input['password'] ?? '';
$role = $input['role'] ?? 'student';
$group = $input['group'] ?? null;
$subject = $input['subject'] ?? null;

// Validation
$errors = [];
if (empty($name)) $errors[] = 'Ism kiritilishi shart';
if (empty($email)) $errors[] = 'Email kiritilishi shart';
if (strlen($password) < 6) $errors[] = 'Parol kamida 6 ta belgi';
if (!in_array($role, ['student', 'teacher'])) $errors[] = 'Noto\'g\'ri rol';

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['error' => implode(', ', $errors)]);
    exit;
}

try {
    $db = getDB();
    
    // Check if email exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Bu email allaqachon ro\'yxatdan o\'tgan']);
        exit;
    }

    // Create user
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $hemisId = 'SE_' . strtoupper(bin2hex(random_bytes(4)));

    $stmt = $db->prepare("INSERT INTO users (full_name, email, phone, password_hash, role, hemis_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $email, $phone, $passwordHash, $role, $hemisId]);

    $userId = $db->lastInsertId();

    // If teacher, create subject
    if ($role === 'teacher' && !empty($subject)) {
        $stmt = $db->prepare("INSERT INTO subjects (name, teacher_id) VALUES (?, ?)");
        $stmt->execute([$subject, $userId]);
    }

    // If student, assign group
    if ($role === 'student' && !empty($group)) {
        // Find or create group
        $stmt = $db->prepare("SELECT id FROM `groups` WHERE name = ?");
        $stmt->execute([$group]);
        $groupRow = $stmt->fetch();
        
        if (!$groupRow) {
            $stmt = $db->prepare("INSERT INTO `groups` (name) VALUES (?)");
            $stmt->execute([$group]);
            $groupId = $db->lastInsertId();
        } else {
            $groupId = $groupRow['id'];
        }

        $stmt = $db->prepare("UPDATE users SET group_id = ? WHERE id = ?");
        $stmt->execute([$groupId, $userId]);
    }

    $token = bin2hex(random_bytes(32));

    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $userId,
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'hemis_id' => $hemisId,
        ],
        'token' => $token
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server xatosi: ' . $e->getMessage()]);
}
