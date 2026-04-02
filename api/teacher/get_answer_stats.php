<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $pin = $_GET['pin'] ?? '';
    $questionIndex = $_GET['question_index'] ?? null;
    
    if (!$pin) throw new Exception("PIN kerak");

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

    $sid = $session['id'];

    if ($questionIndex !== null) {
        // Get distribution for a specific question
        $stmt = $db->prepare("SELECT answer_index, COUNT(*) as cnt, 
            SUM(is_correct) as correct_cnt
            FROM quiz_answers 
            WHERE session_id = ? AND question_index = ?
            GROUP BY answer_index
            ORDER BY answer_index");
        $stmt->execute([$sid, $questionIndex]);
        $rows = $stmt->fetchAll();

        $total = 0;
        $distribution = [];
        foreach ($rows as $r) {
            $distribution[$r['answer_index']] = (int)$r['cnt'];
            $total += (int)$r['cnt'];
        }

        echo json_encode([
            'success' => true,
            'total_answers' => $total,
            'distribution' => $distribution
        ]);
    } else {
        // Get all answers summary per question
        $stmt = $db->prepare("SELECT question_index, answer_index, is_correct, COUNT(*) as cnt
            FROM quiz_answers 
            WHERE session_id = ?
            GROUP BY question_index, answer_index, is_correct
            ORDER BY question_index, answer_index");
        $stmt->execute([$sid]);
        $rows = $stmt->fetchAll();

        echo json_encode(['success' => true, 'answers' => $rows]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
