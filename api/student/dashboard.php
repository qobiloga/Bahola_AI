<?php
require_once '../config/database.php';
header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_GET['student_id'])) {
        throw new Exception("Student ID kiritilmagan");
    }

    $studentId = (int)$_GET['student_id'];
    $db = getDB();

    // 1. Get average score from test_attempts
    $stmt = $db->prepare("SELECT AVG(percentage) as avg_score, COUNT(*) as completed_tests FROM test_attempts WHERE student_id = ? AND status = 'completed'");
    $stmt->execute([$studentId]);
    $testStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $avgScore = $testStats['avg_score'] ? round($testStats['avg_score']) : 0;
    $completedTestsCount = $testStats['completed_tests'];

    // 2. Get completed assignments count
    $stmt = $db->prepare("SELECT COUNT(*) as completed_assignments FROM submissions WHERE student_id = ? AND status != 'pending'");
    $stmt->execute([$studentId]);
    $assignStats = $stmt->fetch(PDO::FETCH_ASSOC);
    $completedAssignmentsCount = $assignStats['completed_assignments'];

    // 3. Get student group ID to fetch unsubmitted assignments and tests
    $stmt = $db->prepare("SELECT group_id FROM users WHERE id = ?");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    $groupId = $student['group_id'] ?? 0;

    // 4. Pending assignments (assigned to group but no submission or pending submission)
    $stmt = $db->prepare("
        SELECT a.*, s.name as subject_name, t.full_name as teacher_name, sub.status as sub_status 
        FROM assignments a
        JOIN subjects s ON a.subject_id = s.id
        JOIN users t ON a.teacher_id = t.id
        LEFT JOIN submissions sub ON a.id = sub.assignment_id AND sub.student_id = ?
        WHERE a.group_id = ? AND (sub.id IS NULL OR sub.status = 'pending')
        ORDER BY a.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$studentId, $groupId]);
    $pendingAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Recent Results (Test attempts)
    $stmt = $db->prepare("
        SELECT ta.*, t.title as test_title, s.name as subject_name 
        FROM test_attempts ta
        JOIN tests t ON ta.test_id = t.id
        LEFT JOIN subjects s ON t.subject_id = s.id
        WHERE ta.student_id = ? AND ta.status = 'completed'
        ORDER BY ta.created_at DESC
        LIMIT 3
    ");
    $stmt->execute([$studentId]);
    $recentResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. Available Tests (tests without complete attempts)
    // In our seed tests don't have group_id but let's just show active tests without attempts
    $stmt = $db->prepare("
        SELECT t.*, s.name as subject_name, u.full_name as teacher_name
        FROM tests t
        LEFT JOIN subjects s ON t.subject_id = s.id
        LEFT JOIN users u ON t.teacher_id = u.id
        LEFT JOIN test_attempts ta ON t.id = ta.test_id AND ta.student_id = ?
        WHERE t.status = 'active' AND (ta.id IS NULL OR ta.status != 'completed')
        ORDER BY t.created_at DESC
        LIMIT 2
    ");
    $stmt->execute([$studentId]);
    $availableTests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // AI Recommendations Mock
    $recommendations = [
        [
            'title' => 'Trigonometriya — Yuqori ustuvorlik',
            'desc' => 'sin(90°) va cos(0°) qiymatlari xato. Asosiy trigonometrik qiymatlarni qayta o\'rganing.',
            'alert_score' => '40%',
            'type' => 'error'
        ],
        [
            'title' => 'Integral asoslari — O\'rta ustuvorlik',
            'desc' => '∫xⁿ dx formulasi noto\'g\'ri qo\'llanilgan. Qoidani mashq qiling.',
            'alert_score' => '55%',
            'type' => 'warning'
        ],
        [
            'title' => 'Geometrik shakllar — Past ustuvorlik',
            'desc' => 'Konus hajmi formulasida kichik xato. Formulani tekshiring.',
            'alert_score' => '70%',
            'type' => 'primary'
        ]
    ];

    echo json_encode([
        'success' => true,
        'stats' => [
            'avg_score' => $avgScore,
            'completed_tests' => $completedTestsCount,
            'completed_assignments' => $completedAssignmentsCount,
            'ai_tips' => count($recommendations)
        ],
        'pending_assignments' => $pendingAssignments,
        'recent_results' => $recentResults,
        'available_tests' => $availableTests,
        'recommendations' => $recommendations
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
