<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$teacher_id = $_GET['teacher_id'] ?? 1;
$result = ['success' => true, 'assignments' => []];

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT a.id, a.title, g.name as group_name, s.name as subject_name, 
                                 a.status, a.deadline, a.created_at, a.task_pdf_path,
                                 (SELECT COUNT(*) FROM users WHERE group_id = a.group_id AND role='student') as total_students,
                                 (SELECT COUNT(*) FROM submissions WHERE assignment_id = a.id) as submitted_count
                          FROM assignments a
                          LEFT JOIN `groups` g ON a.group_id = g.id
                          LEFT JOIN subjects s ON a.subject_id = s.id
                          WHERE a.teacher_id = ? 
                          ORDER BY a.created_at DESC");
    $stmt->execute([$teacher_id]);
    $result['assignments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $result['success'] = false;
    $result['error'] = $e->getMessage();
}

echo json_encode($result);
