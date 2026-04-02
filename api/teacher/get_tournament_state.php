<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $pin = $_GET['pin'] ?? '';
    if (!$pin) throw new Exception("PIN kiritilmadi.");

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM tournament_sessions WHERE pin = ?");
    $stmt->execute([$pin]);
    $session = $stmt->fetch();

    if (!$session) throw new Exception("Turnir topilmadi.");

    // Get participants
    $stmt = $db->prepare("SELECT * FROM tournament_participants WHERE tournament_id = ? ORDER BY joined_at");
    $stmt->execute([$session['id']]);
    $participants = $stmt->fetchAll();

    // Get matches
    $stmt = $db->prepare("SELECT * FROM tournament_matches WHERE tournament_id = ? ORDER BY round_number, match_index");
    $stmt->execute([$session['id']]);
    $matches = $stmt->fetchAll();

    // Group matches by round
    $rounds = [];
    foreach ($matches as $m) {
        $rounds[$m['round_number']][] = $m;
    }

    echo json_encode([
        'success' => true,
        'status' => $session['status'],
        'current_round' => (int)$session['current_round'],
        'champion' => $session['champion'],
        'participants' => $participants,
        'participant_count' => count($participants),
        'matches' => $matches,
        'rounds' => $rounds,
        'subjects' => json_decode($session['subjects'], true)
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
