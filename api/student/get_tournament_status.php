<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $pin = $_GET['pin'] ?? '';
    $name = $_GET['name'] ?? '';
    if (!$pin || !$name) throw new Exception("PIN yoki ism kiritilmadi.");

    $db = getDB();

    // Get tournament session
    $stmt = $db->prepare("SELECT * FROM tournament_sessions WHERE pin = ?");
    $stmt->execute([$pin]);
    $session = $stmt->fetch();
    if (!$session) throw new Exception("Turnir topilmadi.");

    $tournamentId = $session['id'];

    // Check if participant is eliminated
    $stmt = $db->prepare("SELECT * FROM tournament_participants WHERE tournament_id = ? AND student_name = ?");
    $stmt->execute([$tournamentId, $name]);
    $participant = $stmt->fetch();

    if (!$participant) throw new Exception("Ishtirokchi topilmadi.");

    $responseData = [
        'success' => true,
        'tournament_status' => $session['status'],
        'current_round' => (int)$session['current_round'],
        'is_eliminated' => (bool)$participant['is_eliminated'],
        'champion' => $session['champion'],
        'my_score' => (int)$participant['current_score'],
        'time_limit' => isset($session['time_limit']) ? (int)$session['time_limit'] : 15,
        'match' => null
    ];

    if ($session['status'] === 'active' && !$participant['is_eliminated']) {
        // Find current active match for this player
        $round = (int)$session['current_round'];
        $stmt = $db->prepare("SELECT * FROM tournament_matches WHERE tournament_id = ? AND round_number = ? AND (player1_name = ? OR player2_name = ?)");
        $stmt->execute([$tournamentId, $round, $name, $name]);
        $match = $stmt->fetch();

        if ($match) {
            $isPlayer1 = ($match['player1_name'] === $name);
            $opponent = $isPlayer1 ? $match['player2_name'] : $match['player1_name'];
            $myScore = $isPlayer1 ? (int)$match['player1_score'] : (int)$match['player2_score'];
            $oppScore = $isPlayer1 ? (int)$match['player2_score'] : (int)$match['player1_score'];
            $questions = json_decode($match['questions_used'], true);
            $currentQ = (int)$match['current_question'];

            // Get my answers
            $myAnswersJson = $isPlayer1 ? $match['player1_answers'] : $match['player2_answers'];
            $myAnswers = $myAnswersJson ? json_decode($myAnswersJson, true) : [];

            $responseData['match'] = [
                'id' => (int)$match['id'],
                'status' => $match['status'],
                'opponent' => $opponent,
                'my_score' => $myScore,
                'opponent_score' => $oppScore,
                'current_question' => $currentQ,
                'total_questions' => count($questions),
                'winner' => $match['winner_name'],
                'is_player1' => $isPlayer1,
                'my_answers' => $myAnswers,
                'round' => (int)$match['round_number']
            ];

            // Send question data if match is active and student hasn't answered this question yet
            if ($match['status'] === 'active' && $currentQ < count($questions)) {
                $alreadyAnswered = isset($myAnswers[$currentQ]);
                $responseData['match']['question_data'] = $questions[$currentQ];
                $responseData['match']['already_answered'] = $alreadyAnswered;
            }
        }
    }

    // Get total participants count and alive count
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM tournament_participants WHERE tournament_id = ?");
    $stmt->execute([$tournamentId]);
    $responseData['total_participants'] = (int)$stmt->fetch()['total'];

    $stmt = $db->prepare("SELECT COUNT(*) as alive FROM tournament_participants WHERE tournament_id = ? AND is_eliminated = 0");
    $stmt->execute([$tournamentId]);
    $responseData['alive_count'] = (int)$stmt->fetch()['alive'];

    echo json_encode($responseData);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
