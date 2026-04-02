<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// In a real app we check JWT token here. For hackathon we just take teacher_id from query.
$teacher_id = $_GET['teacher_id'] ?? 1; // Default to admin or mock teacher id
$dashboardData = [
    'stats' => [
        'total_tests' => 0,
        'checked_papers' => 0,
        'total_students' => 0,
        'time_saved_hours' => 0
    ],
    'recent_tests' => [],
    'pending_papers' => [],
    'success' => true
];

try {
    $db = getDB();

    // Stats
    $stmt = $db->prepare("SELECT COUNT(*) FROM tests WHERE teacher_id = ?");
    $stmt->execute([$teacher_id]);
    $dashboardData['stats']['total_tests'] = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(s.id) FROM submissions s 
                          JOIN assignments a ON s.assignment_id = a.id 
                          WHERE a.teacher_id = ? AND s.status IN ('ai_reviewed', 'teacher_approved')");
    $stmt->execute([$teacher_id]);
    $dashboardData['stats']['checked_papers'] = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(DISTINCT student_id) FROM test_attempts ta JOIN tests t ON ta.test_id = t.id WHERE t.teacher_id = ?");
    $stmt->execute([$teacher_id]);
    $dashboardData['stats']['total_students'] = $stmt->fetchColumn();

    $dashboardData['stats']['time_saved_hours'] = floor($dashboardData['stats']['checked_papers'] * 15 / 60);

    // Recent Tests
    $stmt = $db->prepare("SELECT t.title, s.name as subject, t.status, 
                          (SELECT COUNT(*) FROM test_attempts WHERE test_id = t.id) as attempts_count,
                          (SELECT ROUND(AVG(percentage)) FROM test_attempts WHERE test_id = t.id) as avg_score
                          FROM tests t 
                          JOIN subjects s ON t.subject_id = s.id 
                          WHERE t.teacher_id = ? 
                          ORDER BY t.created_at DESC LIMIT 4");
    $stmt->execute([$teacher_id]);
    $dashboardData['recent_tests'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pending Papers
    $stmt = $db->prepare("SELECT s.id, u.full_name, a.title, g.name as group_name, s.status, s.submitted_at 
                          FROM submissions s 
                          JOIN assignments a ON s.assignment_id = a.id 
                          JOIN users u ON s.student_id = u.id 
                          LEFT JOIN `groups` g ON u.group_id = g.id
                          WHERE a.teacher_id = ? AND s.status IN ('submitted', 'ai_processing', 'ai_reviewed')
                          ORDER BY s.submitted_at DESC LIMIT 3");
    $stmt->execute([$teacher_id]);
    $dashboardData['pending_papers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $dashboardData['success'] = false;
    $dashboardData['error'] = $e->getMessage();
}

echo json_encode($dashboardData);
