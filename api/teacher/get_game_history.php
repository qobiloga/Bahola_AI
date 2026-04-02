<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $teacherId = $_GET['teacher_id'] ?? 1;
    $db = getDB();

    // Get quizzes (including duel, tf, individual)
    $stmt1 = $db->prepare("SELECT id, game_type as type, topic, created_at, status, results_summary as summary 
                           FROM quiz_sessions 
                           WHERE teacher_id = ? 
                           ORDER BY created_at DESC");
    $stmt1->execute([$teacherId]);
    $quizzes = $stmt1->fetchAll(PDO::FETCH_ASSOC);

    // Get tournaments
    $stmt2 = $db->prepare("SELECT id, 'tournament' as type, json_unquote(subjects) as topic, created_at, status, champion as winner 
                           FROM tournament_sessions 
                           WHERE teacher_id = ? 
                           ORDER BY created_at DESC");
    $stmt2->execute([$teacherId]);
    $tournaments = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // Combine
    $history = array_merge($quizzes, $tournaments);
    
    // Sort combined by date
    usort($history, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    echo json_encode(['success' => true, 'history' => $history]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
