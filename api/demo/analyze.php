<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/openai.php';

try {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'essay') {
        $essay = $_POST['essay'] ?? '';
        if (!$essay) throw new Exception("Esse matni kiritilmadi.");
        
        $prompt = "Siz o'qituvchisiz. O'quvchi yozgan ushbu esseni grammatik, uslubiy va mazmun jihatdan baholang. Xatolarni ko'rsating, 100 ballik tizimda baho bering va xulosangizni yozing. Matnni qisqa va aniq HTML formatda qaytaring (faqat <strong>, <em>, <br> kabi sodda teglardan foydalaning).\n\nEsse: " . $essay;
        
        $messages = [
            ['role' => 'user', 'content' => $prompt]
        ];
        
        $result = callOpenAI($messages);
        
    } elseif ($action === 'test') {
        $question = $_POST['question'] ?? '';
        $answer = $_POST['answer'] ?? '';
        
        if (!$question || !$answer) throw new Exception("Savol yoki javob kiritilmadi.");
        
        $prompt = "Siz tajribali ustozsiz. Quyida berilgan savol va o'quvchining javobini tekshiring. Javob to'g'rimi yoki noto'g'rimi? Asoslab tushuntirib bering va xatosi bo'lsa tavsiya yozing. Natijani sodda HTML formatda qaytaring (<strong>, <br> lardan foydalanib).\n\nSavol: $question\nJavob: $answer";
        
        $messages = [
            ['role' => 'user', 'content' => $prompt]
        ];
        
        $result = callOpenAI($messages);
        
    } elseif ($action === 'upload') {
        if (!isset($_FILES['file'])) throw new Exception("Fayl topilmadi.");
        
        $file = $_FILES['file'];
        $mime = $file['type'];
        
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
            throw new Exception("Hozircha faqat rasm formatlari qo'llab-quvvatlanadi (JPG, PNG).");
        }
        
        $base64 = base64_encode(file_get_contents($file['tmp_name']));
        $prompt = "Ushbu rasm o'quvchining bajargan mustaqil ishi (Informatika fani, mavzu: Antiviruslar turlari). 
Quyidagi nizom asosida tekshiring va HTML formatda chiroyli qilib xulosa bering:
1) Mavzuga oid malumotlar 3 ta reja asosida yoritilganmi?
2) Xulosa qismi mavjudmi?
3) Yozilgan matn hajmi kamida 150 so'zdan iboratmi?
Shularni chuqur tahlil qilib, kamchiliklarni ko'rsating va 100 ballik tizimda adolatli baho bering (Javob HTML formatida <strong>, <ul>, <li> kabi teglardan iborat bo'lsin).";
        
        $result = callOpenAIVision($base64, $prompt, $mime);
        
    } elseif ($action === 'essay_image') {
        if (!isset($_FILES['file'])) throw new Exception("Rasm fayli kiritilmadi.");
        
        $file = $_FILES['file'];
        $mime = $file['type'];
        
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
            throw new Exception("Hozircha faqat rasm formatlari qo'llab-quvvatlanadi (JPG, PNG).");
        }
        
        $base64 = base64_encode(file_get_contents($file['tmp_name']));
        $prompt = "Siz tajribali ustozsiz. Talaba tomonidan berilgan mavzuda yozilgan quyidagi qog'ozdagi esseni o'qing va tekshiring. 
Mavzu: Sun’iy intellekt va uning imkoniyatlari
Tekshirish namunalari (Rubrika):
1) Kirish (Introduction): Mavzuga kirish va tezis mavjudmi?
2) Asosiy qism (Body): Mavzu keng yoritilganmi, dalillar va misollar keltirilganmi?
3) Xulosa (Conclusion): Asosiy fikrlar qisqacha xulosalanganmi?
Ushbu shartlarga javob bersa va kamida 200 so'zdan iborat bo'lsa adolatli 100 ballik tizimda baholang.
Xatolarni alohida ko'rsating va qisqa, tushunarli HTML formatda (<strong>, <ul>, <li> yordamida) xulosa qaytaring.";
        
        $result = callOpenAIVision($base64, $prompt, $mime);
        
    } else {
        throw new Exception("Noto'g'ri so'rov turi.");
    }
    
    // Check if result is array (error from callOpenAI)
    if (is_array($result) && isset($result['error'])) {
        $decoded = @json_decode($result['response'], true);
        $errMsg = $decoded['error']['message'] ?? $result['error'];
        throw new Exception("OpenAI API Xatosi: " . $errMsg);
    }
    
    $result = preg_replace('/```html\n?/', '', $result);
    $result = preg_replace('/```\n?/', '', $result);
    
    echo json_encode(['success' => true, 'html' => $result]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
