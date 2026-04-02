<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $teacherId = $_GET['teacher_id'] ?? 1;
    $db = getDB();

    // ====== 1. UMUMIY KARTOCHKALAR ======

    // Jami savollar soni (quiz_sessions dagi barcha savollarni sanash)
    $stmt = $db->prepare("SELECT questions FROM quiz_sessions WHERE teacher_id = ?");
    $stmt->execute([$teacherId]);
    $totalQuestions = 0;
    while ($row = $stmt->fetch()) {
        $q = json_decode($row['questions'], true);
        if (is_array($q)) $totalQuestions += count($q);
    }

    // Turnir savollari
    $stmt2 = $db->prepare("SELECT questions FROM tournament_sessions WHERE teacher_id = ?");
    $stmt2->execute([$teacherId]);
    while ($row = $stmt2->fetch()) {
        $q = json_decode($row['questions'], true);
        if (is_array($q)) $totalQuestions += count($q);
    }

    // Faol o'quvchilar (quiz_participants dagi unique student_name lar)
    $stmt3 = $db->prepare("
        SELECT COUNT(DISTINCT qp.student_name) as cnt
        FROM quiz_participants qp
        JOIN quiz_sessions qs ON qs.id = qp.session_id
        WHERE qs.teacher_id = ?
    ");
    $stmt3->execute([$teacherId]);
    $activeStudents = (int)$stmt3->fetchColumn();

    // Turnir qatnashuvchilarini ham qo'shish
    $stmt3b = $db->prepare("
        SELECT COUNT(DISTINCT tp.student_name) as cnt
        FROM tournament_participants tp
        JOIN tournament_sessions ts ON ts.id = tp.tournament_id
        WHERE ts.teacher_id = ?
    ");
    $stmt3b->execute([$teacherId]);
    $activeStudents += (int)$stmt3b->fetchColumn();

    // Jami o'yinlar soni
    $stmt4 = $db->prepare("SELECT COUNT(*) FROM quiz_sessions WHERE teacher_id = ?");
    $stmt4->execute([$teacherId]);
    $totalGames = (int)$stmt4->fetchColumn();

    $stmt4b = $db->prepare("SELECT COUNT(*) FROM tournament_sessions WHERE teacher_id = ?");
    $stmt4b->execute([$teacherId]);
    $totalGames += (int)$stmt4b->fetchColumn();

    // O'rtacha ball (quiz_participants dagi score)
    $stmt5 = $db->prepare("
        SELECT AVG(qp.score) as avg_score
        FROM quiz_participants qp
        JOIN quiz_sessions qs ON qs.id = qp.session_id
        WHERE qs.teacher_id = ? AND qp.score > 0
    ");
    $stmt5->execute([$teacherId]);
    $avgScore = round((float)$stmt5->fetchColumn(), 1);

    // ====== 2. HAFTALIK FAOLLIK (oxirgi 7 kun) ======
    $weekDays = ['Dush', 'Sesh', 'Chor', 'Pay', 'Jum', 'Shan', 'Yak'];
    $quizWeekly = array_fill(0, 7, 0);
    $duelWeekly = array_fill(0, 7, 0);
    $tournamentWeekly = array_fill(0, 7, 0);

    // Quiz haftalik (oxirgi 7 kun)
    $stmt6 = $db->prepare("
        SELECT DAYOFWEEK(created_at) as dow, game_type, COUNT(*) as cnt
        FROM quiz_sessions
        WHERE teacher_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DAYOFWEEK(created_at), game_type
    ");
    $stmt6->execute([$teacherId]);
    while ($row = $stmt6->fetch()) {
        // MySQL DAYOFWEEK: 1=Sunday, 2=Monday... 7=Saturday  
        // Bizning massiv: 0=Dush, 1=Sesh... 6=Yak
        $idx = ($row['dow'] + 5) % 7; // Convert: Mon=0, Tue=1... Sun=6
        if ($row['game_type'] === 'duel') {
            $duelWeekly[$idx] += $row['cnt'];
        } else {
            $quizWeekly[$idx] += $row['cnt'];
        }
    }

    // Turnir haftalik
    $stmt7 = $db->prepare("
        SELECT DAYOFWEEK(created_at) as dow, COUNT(*) as cnt
        FROM tournament_sessions
        WHERE teacher_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DAYOFWEEK(created_at)
    ");
    $stmt7->execute([$teacherId]);
    while ($row = $stmt7->fetch()) {
        $idx = ($row['dow'] + 5) % 7;
        $tournamentWeekly[$idx] += $row['cnt'];
    }

    // ====== 3. TOP O'QUVCHILAR ======
    $stmt8 = $db->prepare("
        SELECT qp.student_name, SUM(qp.score) as total_score, COUNT(*) as games_played
        FROM quiz_participants qp
        JOIN quiz_sessions qs ON qs.id = qp.session_id
        WHERE qs.teacher_id = ?
        GROUP BY qp.student_name
        ORDER BY total_score DESC
        LIMIT 10
    ");
    $stmt8->execute([$teacherId]);
    $topStudents = $stmt8->fetchAll();

    // ====== 4. O'YIN TURLARI BO'YICHA NATIJALAR ======
    $stmt9 = $db->prepare("
        SELECT game_type, COUNT(*) as cnt
        FROM quiz_sessions
        WHERE teacher_id = ? AND status = 'finished'
        GROUP BY game_type
    ");
    $stmt9->execute([$teacherId]);
    $gameTypes = [];
    while ($row = $stmt9->fetch()) {
        $gameTypes[$row['game_type']] = (int)$row['cnt'];
    }

    // Turnirlar soni
    $stmt9b = $db->prepare("SELECT COUNT(*) FROM tournament_sessions WHERE teacher_id = ? AND status = 'finished'");
    $stmt9b->execute([$teacherId]);
    $gameTypes['tournament'] = (int)$stmt9b->fetchColumn();

    echo json_encode([
        'success' => true,
        'cards' => [
            'total_questions' => $totalQuestions,
            'active_students' => $activeStudents,  
            'total_games' => $totalGames,
            'avg_score' => $avgScore
        ],
        'weekly' => [
            'labels' => $weekDays,
            'quiz' => $quizWeekly,
            'duel' => $duelWeekly,
            'tournament' => $tournamentWeekly
        ],
        'top_students' => $topStudents,
        'game_types' => $gameTypes
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
