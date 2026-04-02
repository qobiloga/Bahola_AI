<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $pin = $data['pin'] ?? '';
    $results = $data['results'] ?? null; // JSON object with scores/winner
    
    if (!$pin) throw new Exception("PIN topilmadi");

    $db = getDB();
    $stmt = $db->prepare("UPDATE quiz_sessions SET status = 'finished', results_summary = ? WHERE pin = ?");
    $stmt->execute([json_encode($results), $pin]);

    echo json_encode(['success' => true, 'message' => 'Natijalar muvaffaqiyatli saqlandi']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
