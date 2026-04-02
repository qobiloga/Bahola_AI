<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    if (!isset($_FILES['wordfile']) || $_FILES['wordfile']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Fayl yuklanmadi yoki xatolik yuz berdi.");
    }

    $file = $_FILES['wordfile'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $topic = $_POST['topic'] ?? 'True/False O\'yin';
    $teacherId = $_POST['teacher_id'] ?? 1;

    $content = '';

    if ($ext === 'txt') {
        $content = file_get_contents($file['tmp_name']);
    } elseif ($ext === 'docx') {
        $zip = new ZipArchive();
        if ($zip->open($file['tmp_name']) === true) {
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            if ($xml) {
                $xml = str_replace('</w:p>', "\n", $xml);
                $content = strip_tags($xml);
            }
        }
        if (!$content) throw new Exception("DOCX faylni o'qib bo'lmadi.");
    } else {
        throw new Exception("Faqat .txt yoki .docx fayllar.");
    }
    
    $lines = array_map('trim', explode("\n", $content));
    $questions = [];
    $currentQ = null;

    foreach ($lines as $line) {
        if (empty($line)) continue;

        if (mb_substr($line, 0, 1) === '*') {
            if ($currentQ !== null) {
                $questions[] = $currentQ;
            }
            $currentQ = [
                'question' => mb_substr($line, 1),
                'options' => ["Rost", "Yolg'on"],
                'correct' => -1
            ];
        } elseif ($currentQ !== null) {
            if (mb_substr($line, 0, 1) === '+') {
                $currentQ['correct'] = 0; // Rost
            } elseif (mb_substr($line, 0, 1) === '-') {
                $currentQ['correct'] = 1; // Yolg'on
            }
        }
    }

    if ($currentQ !== null && $currentQ['correct'] !== -1) {
        $questions[] = $currentQ;
    }

    // Filter out questions without correct answer parsed properly
    $validQs = array_filter($questions, function($q) { return $q['correct'] !== -1; });

    if (empty($validQs)) {
        throw new Exception("Fayldan True/False savollar topilmadi. Format: *savol, pastdan + yoki - qo'ying.");
    }

    $pin = (string)rand(100000, 999999);
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO quiz_sessions (pin, teacher_id, topic, questions, status) VALUES (?, ?, ?, ?, 'waiting')");
    $stmt->execute([$pin, $teacherId, $topic, json_encode(array_values($validQs))]);

    echo json_encode([
        'success' => true,
        'pin' => $pin,
        'topic' => $topic,
        'questions' => array_values($validQs),
        'count' => count($validQs)
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
