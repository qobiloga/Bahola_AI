<?php
header('Content-Type: application/json');
require_once '../config/openai.php';

$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$wrong_answers = $input['wrong_answers'] ?? [];
$score = $input['score'] ?? 0;
$total = $input['total'] ?? 0;

if (empty($wrong_answers) && $score == $total) {
    echo json_encode([
        'success' => true,
        'feedback' => "<ul><li><strong>Ajoyib natija!</strong> Barcha savollarga to'g'ri javob berdingiz. Informatika bo'yicha bilim darajangiz juda yuqori! 🌟</li></ul>"
    ]);
    exit;
}

// Build the prompt
$prompt = "
I am a student who just finished an Informatics test. Here are the questions I answered incorrectly:

";

foreach ($wrong_answers as $w) {
    $prompt .= "Question: {$w['question_text']}\n";
    $prompt .= "My Incorrect Answer: {$w['user_answer']}\n";
    $prompt .= "Correct Answer: {$w['correct_answer']}\n\n";
}

$prompt .= "
Sizning vazifangiz FAqatgina mening XATO qilgan javoblarimni tahlil qilish.
To'g'ri javoblar yoki mening kuchli tomonlarim haqida UMUMAN yozmang (ular shart emas).
Faqat qaysi mavzularda o'zlashtirishim past ekanini va yana qanday tavsiyalar berishingiz mumkinligini qisqacha o'zbek tilida HTML formatida yozing (HTML taglari: <ul>, <li>, <strong>). Markdown (```html) ishlatmang.
";

try {
    $ai_response = callOpenAI([
        ['role' => 'system', 'content' => "You are an expert Informatics teacher analyzing test results. Return only HTML tags without markdown block formatting."],
        ['role' => 'user', 'content' => $prompt]
    ]);

    // Cleanup potential markdown blocks if AI puts them
    $ai_response = str_replace(['```html', '```'], '', $ai_response);

    echo json_encode([
        'success' => true,
        'feedback' => trim($ai_response)
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'AI bilan bog\'lanishda xatolik: ' . $e->getMessage()]);
}
