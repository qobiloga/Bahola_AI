<?php
header('Content-Type: application/json');
require_once '../config/openai.php'; // or whatever API config we use

$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$question = $input['question_text'] ?? '';
$user_answer = $input['user_answer'] ?? '';
$correct_answer = $input['correct_answer'] ?? '';

if (empty($question) || empty($user_answer) || empty($correct_answer)) {
    echo json_encode(['success' => false, 'error' => 'Malumotlar yetarli emas.']);
    exit;
}

$prompt = "
I am an Informatics teacher analyzing a student's incorrect test answer. Please explain why their answer is wrong and the concept behind the correct answer in exactly 2-3 short sentences. Respond in Uzbek language.

Question: {$question}
Student's Incorrect Answer: {$user_answer}
Actual Correct Answer: {$correct_answer}

Provide your feedback starting with: 'Xato sababi: ' and clearly explain it. Be highly encouraging!
";

try {
    // Generate AI feedback
    $ai_response = callOpenAI([
        ['role' => 'system', 'content' => "You are an expert Informatics teacher giving helpful, short feedback."],
        ['role' => 'user', 'content' => $prompt]
    ]);

    // Parse Markdown to simple HTML if any (just bold text)
    $ai_html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $ai_response);

    echo json_encode([
        'success' => true,
        'feedback' => $ai_html
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'AI bilan bog\'lanishda xatolik: ' . $e->getMessage()]);
}
