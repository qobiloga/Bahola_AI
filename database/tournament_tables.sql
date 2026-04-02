-- Tournament O'yini uchun jadvallar
-- Bahola Ai Platform

CREATE TABLE IF NOT EXISTS tournament_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pin VARCHAR(6) NOT NULL UNIQUE,
    teacher_id INT NOT NULL,
    subjects JSON NOT NULL,
    questions JSON NOT NULL,
    status ENUM('waiting','active','finished') DEFAULT 'waiting',
    current_round INT DEFAULT 0,
    bracket JSON,
    champion VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tournament_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tournament_id INT NOT NULL,
    student_name VARCHAR(255) NOT NULL,
    is_eliminated TINYINT(1) DEFAULT 0,
    current_score INT DEFAULT 0,
    total_correct INT DEFAULT 0,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tournament_id) REFERENCES tournament_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tournament_matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tournament_id INT NOT NULL,
    round_number INT NOT NULL,
    match_index INT NOT NULL,
    player1_name VARCHAR(255),
    player2_name VARCHAR(255),
    player1_score INT DEFAULT 0,
    player2_score INT DEFAULT 0,
    player1_answers JSON,
    player2_answers JSON,
    player1_time INT DEFAULT 0,
    player2_time INT DEFAULT 0,
    winner_name VARCHAR(255) DEFAULT NULL,
    status ENUM('pending','active','completed') DEFAULT 'pending',
    questions_used JSON,
    current_question INT DEFAULT 0,
    FOREIGN KEY (tournament_id) REFERENCES tournament_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
