<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $db = getDB();
    
    // Fetch test details
    $stmt = $db->prepare("SELECT title, description FROM tests WHERE id = 100");
    $stmt->execute();
    $test = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$test) {
        throw new Exception("Demo test topilmadi.");
    }

    // Fetch questions
    $stmt = $db->prepare("SELECT id, question_text FROM questions WHERE test_id = 100 ORDER BY order_num ASC");
    $stmt->execute();
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($questions as &$q) {
        // Fetch options for each question
        $optStmt = $db->prepare("SELECT id, option_text, is_correct FROM answer_options WHERE question_id = ? ORDER BY option_order ASC");
        $optStmt->execute([$q['id']]);
        $q['options'] = $optStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $test['questions'] = $questions;
    echo json_encode(['success' => true, 'data' => $test]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
