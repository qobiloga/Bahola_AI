<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/openai.php';
require_once __DIR__ . '/../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $topic = $data['topic'] ?? '';
    $count = $data['count'] ?? 10;

    if (!$topic) throw new Exception("Mavzu kiritilmadi.");

    $prompt = "Siz tajribali o'qituvchisiz. Quyidagi mavzu bo'yicha interaktiv True/False o'yini uchun $count ta fakt (rost yoki yolg'on) yozing: '$topic'. 
    Javobni FAQAT JSON formatida qaytaring. Har bir savol 'options' xossasida ['Rost', 'Yolg\'on'] massiviga ega bo'lsin.
    Agar fakt rost bo'lsa 'correct': 0, yolg'on bo'lsa 'correct': 1 bo'lsin.
    [
      {
        \"question\": \"Koinot cheksizdir\",
        \"options\": [\"Rost\", \"Yolg'on\"],
        \"correct\": 0
      }
    ]";

    $messages = [
        ['role' => 'system', 'content' => "Siz faqat JSON formatida javob beradigan AI yordamchisiz."],
        ['role' => 'user', 'content' => $prompt]
    ];

    $result = callOpenAI($messages);
    
    if (is_array($result) && isset($result['error'])) {
        $decoded = @json_decode($result['response'], true);
        $errMsg = $decoded['error']['message'] ?? $result['error'];
        throw new Exception("OpenAI API Xatosi: " . $errMsg);
    }

    if (!$result) throw new Exception("AI dan bo'sh javob qaytdi.");

    $result = preg_replace('/```json\n?/', '', $result);
    $result = preg_replace('/```\n?/', '', $result);
    $result = trim($result);

    $questions = json_decode($result, true);

    if (!$questions || !is_array($questions)) {
        throw new Exception("AI formatda xatolik qildi: " . substr($result, 0, 100));
    }

    $pin = (string)rand(100000, 999999);
    $teacherId = $data['teacher_id'] ?? 1;

    $db = getDB();
    $stmt = $db->prepare("INSERT INTO quiz_sessions (pin, teacher_id, topic, questions, status, game_type) VALUES (?, ?, ?, ?, 'waiting', 'true_false')");
    $stmt->execute([$pin, $teacherId, $topic, json_encode($questions)]);

    echo json_encode([
        'success' => true,
        'pin' => $pin,
        'topic' => $topic,
        'questions' => $questions
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
