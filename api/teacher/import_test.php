<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    // Check for uploaded file
    if (!isset($_FILES['wordfile']) || $_FILES['wordfile']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Fayl yuklanmadi yoki xatolik yuz berdi.");
    }

    $file = $_FILES['wordfile'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $topic = $_POST['topic'] ?? 'Test';
    $teacherId = $_POST['teacher_id'] ?? 1;

    // Read file content
    $content = '';

    if ($ext === 'txt') {
        $content = file_get_contents($file['tmp_name']);
    } elseif ($ext === 'docx') {
        // Parse .docx (ZIP containing XML)
        $zip = new ZipArchive();
        if ($zip->open($file['tmp_name']) === true) {
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            if ($xml) {
                // Strip XML tags, keep text
                $xml = str_replace('</w:p>', "\n", $xml);
                $content = strip_tags($xml);
            }
        }
        if (!$content) throw new Exception("DOCX faylni o'qib bo'lmadi.");
    } else {
        throw new Exception("Faqat .txt yoki .docx fayl turlari qo'llab-quvvatlanadi.");
    }

    // Parse format:
    // *Savol matni
    // javob A
    // javob B
    // +to'g'ri javob C
    // javob D
    
    $lines = array_map('trim', explode("\n", $content));
    $questions = [];
    $currentQ = null;

    foreach ($lines as $line) {
        if (empty($line)) continue;

        if (mb_substr($line, 0, 1) === '*') {
            // New question
            if ($currentQ !== null) {
                $questions[] = $currentQ;
            }
            $currentQ = [
                'question' => mb_substr($line, 1),
                'options' => [],
                'correct' => 0
            ];
        } elseif ($currentQ !== null) {
            if (mb_substr($line, 0, 1) === '+') {
                // Correct answer
                $currentQ['correct'] = count($currentQ['options']);
                $currentQ['options'][] = mb_substr($line, 1);
            } else {
                // Wrong answer
                $currentQ['options'][] = $line;
            }
        }
    }

    // Don't forget last question
    if ($currentQ !== null && !empty($currentQ['options'])) {
        $questions[] = $currentQ;
    }

    if (empty($questions)) {
        throw new Exception("Fayldan savollar topilmadi. Format: *savol, javob, +to'g'ri javob");
    }

    // Generate PIN and save to DB
    $pin = (string)rand(100000, 999999);
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO quiz_sessions (pin, teacher_id, topic, questions, status) VALUES (?, ?, ?, ?, 'waiting')");
    $stmt->execute([$pin, $teacherId, $topic, json_encode($questions)]);

    echo json_encode([
        'success' => true,
        'pin' => $pin,
        'topic' => $topic,
        'questions' => $questions,
        'count' => count($questions)
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
