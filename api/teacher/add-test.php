<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// Accept JSON payload since we will send questions array
$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$teacher_id = $input['teacher_id'] ?? 1;
$title = $input['title'] ?? '';
$subject_id = $input['subject_id'] ?? 1;
$time_limit = $input['time_limit'] ?? 30; // minutes
$questions = $input['questions'] ?? []; // Array of questions

$result = ['success' => false];

try {
    if (empty($title)) throw new Exception("Test nomi bo'sh!");
    if (empty($questions)) throw new Exception("Kamida bitta savol qo'shishingiz kerak!");

    $db = getDB();
    $db->beginTransaction();

    // 1. Create Test
    $stmt = $db->prepare("INSERT INTO tests (title, subject_id, teacher_id, time_limit, status) VALUES (?, ?, ?, ?, 'active')");
    $stmt->execute([$title, $subject_id, $teacher_id, $time_limit]);
    $test_id = $db->lastInsertId();

    // 2. Insert Questions & Options
    foreach ($questions as $qIndex => $q) {
        $qText = $q['question_text'] ?? '';
        $options = $q['options'] ?? [];
        $correctIndex = $q['correct_index'] ?? 0;
        
        if (empty($qText) || count($options) < 2) {
            throw new Exception("Savol matni va kamida 2 ta variant bo'lishi shart!");
        }

        $stmtQ = $db->prepare("INSERT INTO questions (test_id, question_text, question_type, order_num) VALUES (?, ?, 'single', ?)");
        $stmtQ->execute([$test_id, $qText, $qIndex]);
        $question_id = $db->lastInsertId();

        // 3. Insert Options
        $stmtOpt = $db->prepare("INSERT INTO answer_options (question_id, option_text, is_correct, option_order) VALUES (?, ?, ?, ?)");
        foreach ($options as $optIndex => $optText) {
            $is_correct = ($optIndex == $correctIndex) ? 1 : 0;
            $stmtOpt->execute([$question_id, $optText, $is_correct, $optIndex]);
        }
    }

    $db->commit();
    $result['success'] = true;
    $result['message'] = "Test muvaffaqiyatli yaratildi!";
    
} catch (Exception $e) {
    if (isset($db)) $db->rollBack();
    $result['error'] = $e->getMessage();
}

echo json_encode($result);
