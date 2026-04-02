<?php
require_once 'api/config/database.php';

try {
    $db = getDB();
    echo "Bazaga ulanish muvaffaqiyatli.\n";
    
    // Create quiz_sessions table
    $db->exec("CREATE TABLE IF NOT EXISTS quiz_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pin VARCHAR(10) NOT NULL UNIQUE,
        teacher_id INT NOT NULL,
        topic VARCHAR(255) NOT NULL,
        questions JSON NOT NULL,
        current_question INT DEFAULT 0,
        status ENUM('waiting', 'active', 'finished') DEFAULT 'waiting',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    echo "quiz_sessions jadvali tayyor.\n";

    // Create quiz_participants table
    $db->exec("CREATE TABLE IF NOT EXISTS quiz_participants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_id INT NOT NULL,
        student_name VARCHAR(255) NOT NULL,
        score INT DEFAULT 0,
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    echo "quiz_participants jadvali tayyor.\n";

    // Add current_question column if not exists
    try {
        $db->exec("ALTER TABLE quiz_sessions ADD COLUMN current_question INT DEFAULT 0 AFTER questions");
        echo "current_question ustuni qo'shildi.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "current_question ustuni allaqachon mavjud.\n";
        }
    }

    echo "\nBarcha jadvallar muvaffaqiyatli yaratildi/yangilandi!\n";

} catch (Exception $e) {
    echo "Xato: " . $e->getMessage() . "\n";
}
