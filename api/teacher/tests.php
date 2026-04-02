<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$teacher_id = $_GET['teacher_id'] ?? 1;
$result = ['success' => true, 'tests' => []];

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT t.id, t.title, s.name as subject_name, t.status, t.time_limit,
                                 (SELECT COUNT(*) FROM questions WHERE test_id = t.id) as question_count,
                                 (SELECT COUNT(DISTINCT student_id) FROM test_attempts WHERE test_id = t.id) as attempts_count,
                                 (SELECT ROUND(AVG(percentage)) FROM test_attempts WHERE test_id = t.id) as avg_score
                          FROM tests t
                          LEFT JOIN subjects s ON t.subject_id = s.id
                          WHERE t.teacher_id = ? OR t.id = 100
                          ORDER BY t.created_at DESC");
    $stmt->execute([$teacher_id]);
    $result['tests'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $result['success'] = false;
    $result['error'] = $e->getMessage();
}

echo json_encode($result);
