<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $pin = $_GET['pin'] ?? '';
    if (!$pin) throw new Exception("PIN-kod kiritilmadi.");

    $db = getDB();
    
    // Auto-migrate if columns missing
    try {
        $db->query("SELECT correct_answers, total_time FROM quiz_participants LIMIT 1");
    } catch (Exception $e) {
        $db->exec("ALTER TABLE quiz_participants ADD COLUMN correct_answers INT DEFAULT 0 AFTER score");
        $db->exec("ALTER TABLE quiz_participants ADD COLUMN total_time FLOAT DEFAULT 0 AFTER correct_answers");
    }

    // Get session ID from PIN
    $stmt = $db->prepare("SELECT id FROM quiz_sessions WHERE pin = ?");
    $stmt->execute([$pin]);
    $session = $stmt->fetch();
    
    if (!$session) {
        echo json_encode(['success' => true, 'count' => 0, 'players' => []]);
        exit;
    }

    // Get participants with stats
    $stmt = $db->prepare("SELECT student_name, score, correct_answers, total_time FROM quiz_participants WHERE session_id = ? ORDER BY score DESC");
    $stmt->execute([$session['id']]);
    $players = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'count' => count($players),
        'players' => $players
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
