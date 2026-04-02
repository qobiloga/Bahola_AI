<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $pin = $data['pin'] ?? '';
    $name = $data['name'] ?? '';
    $questionIndex = $data['question_index'] ?? 0;
    $answerIndex = $data['answer_index'] ?? 0;

    if (!$pin || !$name) throw new Exception("Ma'lumot yetarli emas.");

    $db = getDB();

    // Auto-create quiz_answers table
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

    // Get session and questions
    $stmt = $db->prepare("SELECT id, questions FROM quiz_sessions WHERE pin = ?");
    $stmt->execute([$pin]);
    $session = $stmt->fetch();
    if (!$session) throw new Exception("Sessiya topilmadi.");

    $questions = json_decode($session['questions'], true);
    $correctIdx = $questions[$questionIndex]['correct'] ?? -1;
    $isCorrect = ($answerIndex == $correctIdx);
    $timeSpent = floatval($data['time_spent'] ?? 3);
    if ($timeSpent > 3) $timeSpent = 3;

    // Save individual answer to quiz_answers
    $stmt = $db->prepare("INSERT INTO quiz_answers (session_id, student_name, question_index, answer_index, is_correct, time_spent) 
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$session['id'], $name, $questionIndex, $answerIndex, $isCorrect ? 1 : 0, $timeSpent]);

    // Update participant stats
    if ($isCorrect) {
        $bonus = max(0, (3 - $timeSpent) * 50);
        $points = 100 + round($bonus);

        $stmt = $db->prepare("UPDATE quiz_participants SET 
            score = score + ?, 
            correct_answers = correct_answers + 1,
            total_time = total_time + ?
            WHERE session_id = ? AND student_name = ?");
        $stmt->execute([$points, $timeSpent, $session['id'], $name]);
    } else {
        $stmt = $db->prepare("UPDATE quiz_participants SET 
            total_time = total_time + ?
            WHERE session_id = ? AND student_name = ?");
        $stmt->execute([$timeSpent, $session['id'], $name]);
    }

    echo json_encode([
        'success' => true,
        'correct' => $isCorrect,
        'correct_answer' => $correctIdx
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
