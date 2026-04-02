<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $pin = $_GET['pin'] ?? '';
    $questionIndex = $_GET['question_index'] ?? null;
    
    if (!$pin || $questionIndex === null) throw new Exception("PIN va question_index kerak");

    $db = getDB();

    // Auto-create table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS quiz_answers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_id INT NOT NULL,
        student_name VARCHAR(255) NOT NULL,
        question_index INT NOT NULL,
        answer_index INT NOT NULL,
        is_correct TINYINT(1) DEFAULT 0,
        time_spent FLOAT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(session_id, question_index)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $stmt = $db->prepare("SELECT id FROM quiz_sessions WHERE pin = ?");
    $stmt->execute([$pin]);
    $session = $stmt->fetch();
    if (!$session) throw new Exception("Sessiya topilmadi");

    // Get list of student names who answered this question
    $stmt = $db->prepare("SELECT DISTINCT student_name FROM quiz_answers WHERE session_id = ? AND question_index = ?");
    $stmt->execute([$session['id'], $questionIndex]);
    $students = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'students' => $students,
        'count' => count($students)
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
