<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/openai.php';
require_once __DIR__ . '/../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $subjects = $data['subjects'] ?? ['math', 'english', 'russian'];
    $difficulty = $data['difficulty'] ?? 'medium';
    $teacherId = $data['teacher_id'] ?? 1;
    $questionsPerRound = isset($data['questions_per_round']) ? (int)$data['questions_per_round'] : 3;
    $timeLimit = isset($data['time_limit']) ? (int)$data['time_limit'] : 15;
    $totalRounds = 5; // enough for 32 players max
    $countPerSubject = max(1, ceil(($questionsPerRound * $totalRounds) / count($subjects))); // dynamically distribute

    $subjectNames = [
        'math' => 'Matematika',
        'english' => 'Ingliz tili',
        'russian' => 'Rus tili'
    ];

    $difficultyMap = [
        'easy' => 'oson',
        'medium' => "o'rtacha",
        'hard' => 'qiyin'
    ];
    $diffText = $difficultyMap[$difficulty] ?? "o'rtacha";

    $allQuestions = [];

    foreach ($subjects as $subject) {
        $subjectLabel = $subjectNames[$subject] ?? $subject;

        if ($subject === 'math') {
            $prompt = "Siz tajribali matematika o'qituvchisiz. $countPerSubject ta {$diffText} darajadagi matematik test savoli tuzing. Mavzular aralash bo'lsin: arifmetika, algebra, geometriya, mantiqiy masalalar. Har bir savolda 4 ta variant va 1 ta to'g'ri javob bo'lsin.
            Javobni FAQAT JSON formatida qaytaring:
            [{\"question\": \"Savol matni\", \"options\": [\"A\", \"B\", \"C\", \"D\"], \"correct\": 0, \"subject\": \"math\"}]";
        } elseif ($subject === 'english') {
            $prompt = "You are an experienced English teacher. Create $countPerSubject {$difficulty} level English language test questions. Mix of grammar, vocabulary, and reading comprehension. Each question must have 4 options with 1 correct answer. Write questions in English but make them suitable for Uzbek students.
            Return ONLY JSON format:
            [{\"question\": \"Question text\", \"options\": [\"A\", \"B\", \"C\", \"D\"], \"correct\": 0, \"subject\": \"english\"}]";
        } elseif ($subject === 'russian') {
            $prompt = "Вы опытный учитель русского языка. Составьте $countPerSubject тестовых вопросов {$diffText} уровня сложности по русскому языку. Смешайте грамматику, лексику и орфографию. Каждый вопрос должен иметь 4 варианта ответа с 1 правильным.
            Верните ТОЛЬКО в формате JSON:
            [{\"question\": \"Текст вопроса\", \"options\": [\"A\", \"B\", \"C\", \"D\"], \"correct\": 0, \"subject\": \"russian\"}]";
        }

        $messages = [
            ['role' => 'system', 'content' => "Siz faqat JSON formatida javob beradigan AI yordamchisiz. Hech qanday qo'shimcha izoh yoki matn qo'shmang."],
            ['role' => 'user', 'content' => $prompt]
        ];

        $result = callOpenAI($messages, null, 4000);

        if (is_array($result) && isset($result['error'])) {
            throw new Exception("AI xatosi ($subjectLabel): " . ($result['error'] ?? 'Noma\'lum'));
        }

        if (!$result) {
            throw new Exception("AI dan bo'sh javob qaytdi ($subjectLabel).");
        }

        // Clean markdown
        $result = preg_replace('/```json\n?/', '', $result);
        $result = preg_replace('/```\n?/', '', $result);
        $result = trim($result);

        $questions = json_decode($result, true);
        if (!$questions || !is_array($questions)) {
            throw new Exception("AI format xatosi ($subjectLabel): " . substr($result, 0, 100));
        }

        // Ensure subject field
        foreach ($questions as &$q) {
            $q['subject'] = $subject;
        }

        $allQuestions[$subject] = $questions;
    }

    // Generate PIN
    $pin = (string)rand(100000, 999999);

    // Save to database
    $db = getDB();

    // Create tournament tables if not exist
    $db->exec("CREATE TABLE IF NOT EXISTS tournament_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pin VARCHAR(6) NOT NULL UNIQUE,
        teacher_id INT NOT NULL,
        subjects JSON NOT NULL,
        questions JSON NOT NULL,
        status ENUM('waiting','active','finished') DEFAULT 'waiting',
        current_round INT DEFAULT 0,
        bracket JSON,
        champion VARCHAR(255) DEFAULT NULL,
        time_limit INT DEFAULT 15,
        questions_per_round INT DEFAULT 3,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Try adding the columns if they don't exist (in case table was already created)
    try {
        $db->exec("ALTER TABLE tournament_sessions ADD COLUMN time_limit INT DEFAULT 15");
        $db->exec("ALTER TABLE tournament_sessions ADD COLUMN questions_per_round INT DEFAULT 3");
    } catch(PDOException $e) { /* ignore if columns exist */ }

    $db->exec("CREATE TABLE IF NOT EXISTS tournament_participants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tournament_id INT NOT NULL,
        student_name VARCHAR(255) NOT NULL,
        is_eliminated TINYINT(1) DEFAULT 0,
        current_score INT DEFAULT 0,
        total_correct INT DEFAULT 0,
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS tournament_matches (
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
        current_question INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $stmt = $db->prepare("INSERT INTO tournament_sessions (pin, teacher_id, subjects, questions, status, time_limit, questions_per_round) VALUES (?, ?, ?, ?, 'waiting', ?, ?)");
    $stmt->execute([$pin, $teacherId, json_encode($subjects), json_encode($allQuestions), $timeLimit, $questionsPerRound]);

    echo json_encode([
        'success' => true,
        'pin' => $pin,
        'subjects' => $subjects,
        'time_limit' => $timeLimit,
        'questions_per_round' => $questionsPerRound,
        'question_counts' => array_map(function($qs) { return count($qs); }, $allQuestions)
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
