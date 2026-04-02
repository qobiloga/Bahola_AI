<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $pin = $_GET['pin'] ?? '';
    if (!$pin) throw new Exception("PIN kiritilmadi.");

    $db = getDB();
    $stmt = $db->prepare("SELECT status, current_question, questions FROM quiz_sessions WHERE pin = ?");
    $stmt->execute([$pin]);
    $session = $stmt->fetch();

    if (!$session) throw new Exception("Sessiya topilmadi.");

    $questions = json_decode($session['questions'], true);

    echo json_encode([
        'success' => true,
        'status' => $session['status'],
        'current_question' => (int)($session['current_question'] ?? 0),
        'total_questions' => count($questions),
        'question_data' => $questions[(int)($session['current_question'] ?? 0)] ?? null
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
