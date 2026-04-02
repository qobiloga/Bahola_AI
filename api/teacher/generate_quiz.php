<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/openai.php';
require_once __DIR__ . '/../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $topic = $data['topic'] ?? '';
    $count = $data['count'] ?? 5; 
    $type = $data['game_type'] ?? 'team_quiz';

    if (!$topic) throw new Exception("Mavzu kiritilmadi.");

    $prompt = "Siz tajribali o'qituvchisiz. Quyidagi mavzu bo'yicha interaktiv viktorina uchun $count ta test savoli tuzing: '$topic'. 
    Javobni FAQAT JSON formatida qaytaring, hech qanday qo'shimcha matnsiz. 
    Format quyidagicha bo'lsin:
    [
      {
        \"question\": \"Savol matni\",
        \"options\": [\"Variant A\", \"Variant B\", \"Variant C\", \"Variant D\"],
        \"correct\": 0
      }
    ]";

    $messages = [
        ['role' => 'system', 'content' => "Siz faqat JSON formatida javob beradigan AI yordamchisiz."],
        ['role' => 'user', 'content' => $prompt]
    ];

    $result = callOpenAI($messages);
    
    if (is_array($result) && isset($result['error'])) {
        throw new Exception("OpenAI API Xatosi: " . $result['error']);
    }

    $result = preg_replace('/```json\n?/', '', $result);
    $result = preg_replace('/```\n?/', '', $result);
    $result = trim($result);

    $questions = json_decode($result, true);

    if (!$questions || !is_array($questions)) {
        throw new Exception("AI formatda xatolik qildi.");
    }

    $pin = (string)rand(100000, 999999);
    $teacherId = $data['teacher_id'] ?? 1;

    $db = getDB();
    $stmt = $db->prepare("INSERT INTO quiz_sessions (pin, teacher_id, topic, questions, status, game_type) VALUES (?, ?, ?, ?, 'waiting', ?)");
    $stmt->execute([$pin, $teacherId, $topic, json_encode($questions), $type]);

    echo json_encode([
        'success' => true,
        'pin' => $pin,
        'topic' => $topic,
        'questions' => $questions
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
