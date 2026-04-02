<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $pin = $data['pin'] ?? '';
    $name = $data['name'] ?? '';
    $matchId = $data['match_id'] ?? 0;
    $questionIndex = $data['question_index'] ?? 0;
    $answerIndex = $data['answer_index'] ?? -1;
    $timeTaken = $data['time_taken'] ?? 0; // milliseconds

    if (!$pin || !$name || !$matchId) throw new Exception("Ma'lumot yetarli emas.");

    $db = getDB();

    // Get match
    $stmt = $db->prepare("SELECT * FROM tournament_matches WHERE id = ?");
    $stmt->execute([$matchId]);
    $match = $stmt->fetch();

    if (!$match) throw new Exception("Match topilmadi.");
    if ($match['status'] !== 'active') throw new Exception("Match hali boshlanmagan yoki allaqachon tugagan.");

    $isPlayer1 = ($match['player1_name'] === $name);
    if (!$isPlayer1 && $match['player2_name'] !== $name) {
        throw new Exception("Siz bu matchning ishtirokchisi emassiz.");
    }

    // Get questions
    $questions = json_decode($match['questions_used'], true);
    if ($questionIndex >= count($questions)) throw new Exception("Savol indeksi noto'g'ri.");

    // Check correct answer
    $correctIdx = $questions[$questionIndex]['correct'] ?? -1;
    $isCorrect = ($answerIndex == $correctIdx);

    // Get current answers
    $answersField = $isPlayer1 ? 'player1_answers' : 'player2_answers';
    $scoreField = $isPlayer1 ? 'player1_score' : 'player2_score';
    $timeField = $isPlayer1 ? 'player1_time' : 'player2_time';

    $currentAnswers = $match[$answersField] ? json_decode($match[$answersField], true) : [];

    // Check if already answered
    if (isset($currentAnswers[$questionIndex])) {
        echo json_encode(['success' => true, 'already_answered' => true, 'correct' => $currentAnswers[$questionIndex]['correct']]);
        return;
    }

    // Save answer
    $currentAnswers[$questionIndex] = [
        'answer' => $answerIndex,
        'correct' => $isCorrect,
        'time' => $timeTaken
    ];

    $scoreIncr = $isCorrect ? 100 : 0;

    // Update match
    $stmt = $db->prepare("UPDATE tournament_matches SET {$answersField} = ?, {$scoreField} = {$scoreField} + ?, {$timeField} = {$timeField} + ? WHERE id = ?");
    $stmt->execute([json_encode($currentAnswers), $scoreIncr, $timeTaken, $matchId]);

    // Update participant total
    if ($isCorrect) {
        $db->prepare("SELECT id FROM tournament_sessions WHERE pin = ?")->execute([$pin]);
        $tournamentId = $db->prepare("SELECT id FROM tournament_sessions WHERE pin = ?")->execute([$pin]);
        $sess = $db->prepare("SELECT id FROM tournament_sessions WHERE pin = ?");
        $sess->execute([$pin]);
        $sessRow = $sess->fetch();
        if ($sessRow) {
            $db->prepare("UPDATE tournament_participants SET current_score = current_score + 100, total_correct = total_correct + 1 WHERE tournament_id = ? AND student_name = ?")
                ->execute([$sessRow['id'], $name]);
        }
    }

    echo json_encode([
        'success' => true,
        'correct' => $isCorrect,
        'correct_answer' => $correctIdx,
        'score_added' => $scoreIncr
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
