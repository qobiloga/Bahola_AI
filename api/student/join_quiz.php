<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $pin = $data['pin'] ?? '';
    $name = $data['name'] ?? '';

    if (!$pin || !$name) throw new Exception("PIN-kod yoki ism kiritilmadi.");

    $db = getDB();
    
    $stmt = $db->prepare("SELECT * FROM quiz_sessions WHERE pin = ? AND (status = 'waiting' OR status = 'active')");
    $stmt->execute([$pin]);
    $session = $stmt->fetch();
    
    $mode = 'quiz';
    if ($session && isset($session['mode']) && $session['mode']) {
        $mode = $session['mode'];
    }

    if (!$session) {
        // Fallback: check tournament_sessions
        $stmtTourn = $db->prepare("SELECT id FROM tournament_sessions WHERE pin = ? AND (status = 'waiting' OR status = 'active')");
        $stmtTourn->execute([$pin]);
        $tournSession = $stmtTourn->fetch();

        if ($tournSession) {
            // Check if already joined
            $stmt = $db->prepare("SELECT id FROM tournament_participants WHERE tournament_id = ? AND student_name = ?");
            $stmt->execute([$tournSession['id'], $name]);
            if (!$stmt->fetch()) {
                $stmt = $db->prepare("INSERT INTO tournament_participants (tournament_id, student_name) VALUES (?, ?)");
                $stmt->execute([$tournSession['id'], $name]);
            }

            echo json_encode([
                'success' => true,
                'session_id' => $tournSession['id'],
                'student_name' => $name,
                'mode' => 'tournament'
            ]);
            return;
        }

        throw new Exception("Bunday dars topilmadi yoki dars yopilgan.");
    }

    // Add student to participants
    $stmt = $db->prepare("INSERT INTO quiz_participants (session_id, student_name) VALUES (?, ?)");
    $stmt->execute([$session['id'], $name]);

    echo json_encode([
        'success' => true,
        'session_id' => $session['id'],
        'student_name' => $name,
        'mode' => $mode
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
