<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $pin = $data['pin'] ?? '';
    $action = $data['action'] ?? ''; // 'start', 'next_round', 'start_matches'

    if (!$pin || !$action) throw new Exception("PIN yoki action kiritilmadi.");

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM tournament_sessions WHERE pin = ?");
    $stmt->execute([$pin]);
    $session = $stmt->fetch();

    if (!$session) throw new Exception("Turnir topilmadi.");

    $tournamentId = $session['id'];
    $allQuestions = json_decode($session['questions'], true);
    $subjects = json_decode($session['subjects'], true);

    if ($action === 'start') {
        // Start tournament — create Round 1 matches
        $stmt = $db->prepare("SELECT student_name FROM tournament_participants WHERE tournament_id = ? AND is_eliminated = 0 ORDER BY RAND()");
        $stmt->execute([$tournamentId]);
        $players = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (count($players) < 2) throw new Exception("Kamida 2 ta o'quvchi kerak.");

        // Create pairs
        $matchIndex = 0;
        $roundNumber = 1;
        $qPerRound = isset($session['questions_per_round']) ? (int)$session['questions_per_round'] : 3;
        $questionsForRound = getQuestionsForRound($allQuestions, $subjects, $roundNumber, $qPerRound);

        for ($i = 0; $i < count($players); $i += 2) {
            $p1 = $players[$i];
            $p2 = (isset($players[$i + 1])) ? $players[$i + 1] : null; // BYE case

            $stmt = $db->prepare("INSERT INTO tournament_matches (tournament_id, round_number, match_index, player1_name, player2_name, status, questions_used) VALUES (?, ?, ?, ?, ?, ?, ?)");

            if ($p2 === null) {
                // BYE — auto-win
                $stmt->execute([$tournamentId, $roundNumber, $matchIndex, $p1, null, 'completed', json_encode($questionsForRound)]);
                // Set winner
                $matchId = $db->lastInsertId();
                $db->prepare("UPDATE tournament_matches SET winner_name = ? WHERE id = ?")->execute([$p1, $matchId]);
            } else {
                $stmt->execute([$tournamentId, $roundNumber, $matchIndex, $p1, $p2, 'pending', json_encode($questionsForRound)]);
            }
            $matchIndex++;
        }

        // Update session
        $db->prepare("UPDATE tournament_sessions SET status = 'active', current_round = 1 WHERE id = ?")->execute([$tournamentId]);

        echo json_encode(['success' => true, 'action' => 'started', 'round' => 1, 'matches' => $matchIndex]);

    } elseif ($action === 'start_matches') {
        // Activate all pending matches in current round
        $round = (int)$session['current_round'];
        $stmt = $db->prepare("UPDATE tournament_matches SET status = 'active', current_question = 0 WHERE tournament_id = ? AND round_number = ? AND status = 'pending'");
        $stmt->execute([$tournamentId, $round]);

        echo json_encode(['success' => true, 'action' => 'matches_started', 'round' => $round]);

    } elseif ($action === 'next_question') {
        // Advance current question in all active matches
        $round = (int)$session['current_round'];
        $stmt = $db->prepare("SELECT * FROM tournament_matches WHERE tournament_id = ? AND round_number = ? AND status = 'active'");
        $stmt->execute([$tournamentId, $round]);
        $activeMatches = $stmt->fetchAll();

        foreach ($activeMatches as $match) {
            $nextQ = (int)$match['current_question'] + 1;
            $questions = json_decode($match['questions_used'], true);

            if ($nextQ >= count($questions)) {
                // Match finished — determine winner
                $winner = null;
                if ($match['player1_score'] > $match['player2_score']) {
                    $winner = $match['player1_name'];
                } elseif ($match['player2_score'] > $match['player1_score']) {
                    $winner = $match['player2_name'];
                } else {
                    // Tie — faster total time wins, or random
                    $winner = ($match['player1_time'] <= $match['player2_time']) ? $match['player1_name'] : $match['player2_name'];
                }

                $db->prepare("UPDATE tournament_matches SET status = 'completed', winner_name = ? WHERE id = ?")->execute([$winner, $match['id']]);

                // Eliminate loser
                $loser = ($winner === $match['player1_name']) ? $match['player2_name'] : $match['player1_name'];
                if ($loser) {
                    $db->prepare("UPDATE tournament_participants SET is_eliminated = 1 WHERE tournament_id = ? AND student_name = ?")->execute([$tournamentId, $loser]);
                }
            } else {
                $db->prepare("UPDATE tournament_matches SET current_question = ? WHERE id = ?")->execute([$nextQ, $match['id']]);
            }
        }

        echo json_encode(['success' => true, 'action' => 'next_question']);

    } elseif ($action === 'next_round') {
        // Check if current round is completed
        $round = (int)$session['current_round'];
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM tournament_matches WHERE tournament_id = ? AND round_number = ? AND status != 'completed'");
        $stmt->execute([$tournamentId, $round]);
        $pending = $stmt->fetch();

        if ($pending['cnt'] > 0) {
            throw new Exception("Hali tugallanmagan matchlar bor!");
        }

        // Get winners
        $stmt = $db->prepare("SELECT winner_name FROM tournament_matches WHERE tournament_id = ? AND round_number = ? AND winner_name IS NOT NULL");
        $stmt->execute([$tournamentId, $round]);
        $winners = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (count($winners) <= 1) {
            // Tournament finished!
            $champion = $winners[0] ?? 'Noma\'lum';
            $db->prepare("UPDATE tournament_sessions SET status = 'finished', champion = ? WHERE id = ?")->execute([$champion, $tournamentId]);

            echo json_encode(['success' => true, 'action' => 'finished', 'champion' => $champion]);
            return;
        }

        // Create next round matches
        $nextRound = $round + 1;
        shuffle($winners);
        $matchIndex = 0;
        $qPerRound = isset($session['questions_per_round']) ? (int)$session['questions_per_round'] : 3;
        $questionsForRound = getQuestionsForRound($allQuestions, $subjects, $nextRound, $qPerRound);

        for ($i = 0; $i < count($winners); $i += 2) {
            $p1 = $winners[$i];
            $p2 = (isset($winners[$i + 1])) ? $winners[$i + 1] : null;

            $stmt = $db->prepare("INSERT INTO tournament_matches (tournament_id, round_number, match_index, player1_name, player2_name, status, questions_used) VALUES (?, ?, ?, ?, ?, ?, ?)");

            if ($p2 === null) {
                $stmt->execute([$tournamentId, $nextRound, $matchIndex, $p1, null, 'completed', json_encode($questionsForRound)]);
                $matchId = $db->lastInsertId();
                $db->prepare("UPDATE tournament_matches SET winner_name = ? WHERE id = ?")->execute([$p1, $matchId]);
            } else {
                $stmt->execute([$tournamentId, $nextRound, $matchIndex, $p1, $p2, 'pending', json_encode($questionsForRound)]);
            }
            $matchIndex++;
        }

        $db->prepare("UPDATE tournament_sessions SET current_round = ? WHERE id = ?")->execute([$nextRound, $tournamentId]);

        echo json_encode(['success' => true, 'action' => 'next_round', 'round' => $nextRound, 'matches' => $matchIndex]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function getQuestionsForRound($allQuestions, $subjects, $roundNumber, $count) {
    $questions = [];
    $perSubject = max(1, intval(ceil($count / count($subjects))));
    $offset = ($roundNumber - 1) * $perSubject;

    foreach ($subjects as $sub) {
        $subQuestions = $allQuestions[$sub] ?? [];
        for ($i = 0; $i < $perSubject && count($questions) < $count; $i++) {
            $idx = $offset + $i;
            if (isset($subQuestions[$idx])) {
                $questions[] = $subQuestions[$idx];
            } elseif (count($subQuestions) > 0) {
                // Recycle
                $questions[] = $subQuestions[$idx % count($subQuestions)];
            }
        }
    }

    return $questions;
}
