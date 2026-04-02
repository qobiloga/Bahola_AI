-- Ustoz ko'makchi - AI Platform Database Schema
-- MySQL

CREATE DATABASE IF NOT EXISTS ustoz_helper CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ustoz_helper;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'teacher', 'admin') NOT NULL DEFAULT 'student',
    hemis_id VARCHAR(50) UNIQUE,
    avatar VARCHAR(255) DEFAULT NULL,
    group_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Groups table
CREATE TABLE `groups` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    hemis_group_id VARCHAR(50),
    course INT DEFAULT 1,
    faculty VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Subjects table
CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(20),
    teacher_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Topics table (for AI recommendation)
CREATE TABLE topics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    subject_id INT NOT NULL,
    order_num INT DEFAULT 0,
    description TEXT,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tests table
CREATE TABLE tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    time_limit INT DEFAULT 30 COMMENT 'minutes',
    total_points INT DEFAULT 0,
    status ENUM('draft', 'active', 'closed') DEFAULT 'draft',
    start_date DATETIME,
    end_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Questions table
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_id INT NOT NULL,
    topic_id INT,
    question_text TEXT NOT NULL,
    question_type ENUM('single', 'multiple', 'open', 'matching') DEFAULT 'single',
    difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
    points INT DEFAULT 1,
    explanation TEXT COMMENT 'Correct answer explanation for AI feedback',
    order_num INT DEFAULT 0,
    FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE,
    FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Answer options table
CREATE TABLE answer_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    option_text TEXT NOT NULL,
    is_correct TINYINT(1) DEFAULT 0,
    option_order INT DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Test attempts table
CREATE TABLE test_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_id INT NOT NULL,
    student_id INT NOT NULL,
    score INT DEFAULT 0,
    total_points INT DEFAULT 0,
    percentage FLOAT DEFAULT 0,
    status ENUM('in_progress', 'completed', 'expired') DEFAULT 'in_progress',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    finished_at TIMESTAMP NULL,
    ai_analysis TEXT COMMENT 'OpenAI analysis of wrong answers',
    ai_recommendations TEXT COMMENT 'JSON: recommended topics',
    FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Student answers table
CREATE TABLE student_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT NOT NULL,
    question_id INT NOT NULL,
    selected_option_id INT,
    open_answer TEXT,
    is_correct TINYINT(1) DEFAULT 0,
    points_earned INT DEFAULT 0,
    ai_feedback TEXT COMMENT 'AI feedback for this specific answer',
    FOREIGN KEY (attempt_id) REFERENCES test_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    FOREIGN KEY (selected_option_id) REFERENCES answer_options(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Assignments (Mustaqil ishlar - teacher uploads task PDF)
CREATE TABLE assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    group_id INT,
    task_pdf_path VARCHAR(500) NOT NULL COMMENT 'Teacher uploaded task PDF',
    rubric TEXT COMMENT 'JSON: grading criteria',
    max_score FLOAT DEFAULT 100,
    deadline DATETIME,
    status ENUM('active', 'closed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Student submissions (Talaba qo'lyozma PDF yuklaydi)
CREATE TABLE submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    submitted_pdf_path VARCHAR(500) NOT NULL COMMENT 'Student handwritten work PDF',
    ocr_text TEXT COMMENT 'Extracted text from student PDF via OpenAI Vision',
    ai_score FLOAT,
    ai_feedback TEXT COMMENT 'AI detailed feedback',
    ai_strengths TEXT COMMENT 'What student did well',
    ai_weaknesses TEXT COMMENT 'Areas for improvement',
    final_score FLOAT COMMENT 'Teacher approved score',
    teacher_comment TEXT,
    status ENUM('submitted', 'ai_processing', 'ai_reviewed', 'teacher_approved') DEFAULT 'submitted',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- AI Recommendations (for test error analysis)
CREATE TABLE ai_recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    topic_id INT,
    attempt_id INT,
    priority ENUM('high', 'medium', 'low') DEFAULT 'medium',
    reason TEXT NOT NULL,
    recommendation TEXT NOT NULL,
    status ENUM('new', 'in_progress', 'completed') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE SET NULL,
    FOREIGN KEY (attempt_id) REFERENCES test_attempts(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Quiz Sessions table (Real-time AI Quizzes)
CREATE TABLE quiz_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pin VARCHAR(10) NOT NULL UNIQUE,
    teacher_id INT NOT NULL,
    topic VARCHAR(255) NOT NULL,
    questions JSON NOT NULL COMMENT 'AI generated test questions',
    current_question INT DEFAULT 0,
    status ENUM('waiting', 'active', 'finished') DEFAULT 'waiting',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Quiz Participants table (Students joined via lobby)
CREATE TABLE quiz_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    student_name VARCHAR(255) NOT NULL,
    score INT DEFAULT 0,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES quiz_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insert default admin
INSERT INTO users (full_name, email, password_hash, role) 
VALUES ('Admin', 'admin@smarteducation.uz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- Default password: password
