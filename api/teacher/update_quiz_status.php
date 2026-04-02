<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $pin = $data['pin'] ?? '';
    $status = $data['status'] ?? '';
    $currentQuestion = $data['current_question'] ?? null;

    if (!$pin || !$status) throw new Exception("PIN yoki status kiritilmadi.");

    $db = getDB();

    if ($currentQuestion !== null) {
        $stmt = $db->prepare("UPDATE quiz_sessions SET status = ?, current_question = ? WHERE pin = ?");
        $stmt->execute([$status, $currentQuestion, $pin]);
    } else {
        $stmt = $db->prepare("UPDATE quiz_sessions SET status = ? WHERE pin = ?");
        $stmt->execute([$status, $pin]);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
