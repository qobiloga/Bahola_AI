<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$teacher_id = $_GET['teacher_id'] ?? 1;
$result = ['success' => true, 'students' => []];

try {
    $db = getDB();
    // Hozirda barcha talabalarni olamiz, keyinchalik faqat o'z guruhidan olish mumkin.
    $stmt = $db->prepare("SELECT u.id, u.full_name, u.email, u.hemis_id, g.name as group_name,
                          (SELECT COUNT(*) FROM test_attempts WHERE student_id = u.id) as tests_completed,
                          (SELECT COUNT(*) FROM submissions WHERE student_id = u.id) as tasks_completed
                          FROM users u 
                          LEFT JOIN `groups` g ON u.group_id = g.id 
                          WHERE u.role = 'student' 
                          ORDER BY g.name, u.full_name ASC");
    $stmt->execute();
    $result['students'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $result['success'] = false;
    $result['error'] = $e->getMessage();
}

echo json_encode($result);
