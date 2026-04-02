<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $pin = $data['pin'] ?? '';
    $name = $data['name'] ?? '';

    if (!$pin || !$name) throw new Exception("PIN yoki ism kiritilmadi.");

    $db = getDB();

    // Get tournament
    $stmt = $db->prepare("SELECT id FROM tournament_sessions WHERE pin = ?");
    $stmt->execute([$pin]);
    $session = $stmt->fetch();
    if (!$session) throw new Exception("Turnir topilmadi.");

    // Check if already joined
    $stmt = $db->prepare("SELECT id FROM tournament_participants WHERE tournament_id = ? AND student_name = ?");
    $stmt->execute([$session['id'], $name]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => true, 'message' => 'Siz allaqachon qo\'shilgansiz.']);
        return;
    }

    // Join
    $stmt = $db->prepare("INSERT INTO tournament_participants (tournament_id, student_name) VALUES (?, ?)");
    $stmt->execute([$session['id'], $name]);

    echo json_encode(['success' => true, 'message' => 'Turnirga qo\'shildingiz!']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
