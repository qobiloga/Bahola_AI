<?php
require_once __DIR__ . '/api/config/database.php';

try {
    $db = getDB();
    
    // Check if game_type exists
    $stmt = $db->query("SHOW COLUMNS FROM quiz_sessions LIKE 'game_type'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE quiz_sessions ADD COLUMN game_type ENUM('team_quiz', 'individual_test', 'true_false', 'duel') DEFAULT 'team_quiz'");
    }

    // Check if results_summary exists
    $stmt = $db->query("SHOW COLUMNS FROM quiz_sessions LIKE 'results_summary'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE quiz_sessions ADD COLUMN results_summary JSON DEFAULT NULL");
    }
    
    echo json_encode(['success' => true, 'message' => 'Migration successful']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
