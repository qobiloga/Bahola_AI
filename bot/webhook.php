<?php
/**
 * Telegram Bot Webhook - Ustoz Ko'makchi
 */

require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/config/telegram.php';

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update || !isset($update["message"])) {
    exit;
}

$message = $update["message"];
$chatId = $message["chat"]["id"];
$text = $message["text"] ?? "";
$from = $message["from"];

$db = getDB();

// Bot mantiqi
if ($text == "/start") {
    $welcome = "👋 ASSALOMU ALAYKUM! <b>USTOZ KO'MAKCHI</b> botiga xush kelibsiz.\n\n" .
               "Ushbu bot orqali dars jadvalini ko'rishingiz, test natijalarini olishingiz va viktorinalarda qatnashishingiz mumkin.\n\n" .
               "📱 Botdan to'liq foydalanish uchun telefon raqamingizni yuboring:";
    
    $keyboard = [
        'keyboard' => [
            [['text' => "📱 Telefon raqamni yuborish", 'request_contact' => true]]
        ],
        'resize_keyboard' => true,
        'one_time_keyboard' => true
    ];
    
    sendTelegramMessage($chatId, $welcome, $keyboard);
}
elseif (isset($message["contact"])) {
    $phone = $message["contact"]["phone_number"];
    $phone = str_replace(['+', ' '], '', $phone);
    
    // Foydalanuvchini bazadan qidirish
    $stmt = $db->prepare("SELECT id, full_name, role FROM users WHERE phone LIKE ?");
    $stmt->execute(['%' . substr($phone, -9)]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Chat ID ni saqlash
        $stmt = $db->prepare("UPDATE users SET telegram_chat_id = ? WHERE id = ?");
        $stmt->execute([$chatId, $user['id']]);
        
        $msg = "✅ Muvaffaqiyatli bog'landi!\n👤 <b>Foydalanuvchi:</b> " . $user['full_name'] . "\n🎭 <b>Rol:</b> " . ucfirst($user['role']) . "\n\nEndi bot orqali natijalarni olishingiz mumkin.";
        
        $keyboard = [
            'keyboard' => [
                [['text' => "📊 Test natijalarim"], ['text' => "🕒 Dars jadvali"]],
                [['text' => "❔ Yordam"]]
            ],
            'resize_keyboard' => true
        ];
        
        sendTelegramMessage($chatId, $msg, $keyboard);
    } else {
        $msg = "❌ Kechirasiz, ushbu telefon raqami platformada topilmadi. Avval platformadan ro'yxatdan oling yoki administratorga murojaat qiling.";
        sendTelegramMessage($chatId, $msg);
    }
}
elseif ($text == "📊 Test natijalarim") {
    // Foydalanuvchini chat_id orqali topish
    $stmt = $db->prepare("SELECT id FROM users WHERE telegram_chat_id = ?");
    $stmt->execute([$chatId]);
    $user = $stmt->fetch();
    
    if ($user) {
        $stmt = $db->prepare("SELECT t.title, a.score, a.total_points, a.percentage, a.finished_at 
                             FROM test_attempts a 
                             JOIN tests t ON a.test_id = t.id 
                             WHERE a.student_id = ? 
                             ORDER BY a.finished_at DESC LIMIT 5");
        $stmt->execute([$user['id']]);
        $attempts = $stmt->fetchAll();
        
        if ($attempts) {
            $msg = "🏆 <b>Oxirgi 5 ta test natijalaringiz:</b>\n\n";
            foreach ($attempts as $a) {
                $msg .= "📝 <b>" . $a['title'] . "</b>\n";
                $msg .= "📊 Ball: " . $a['score'] . "/" . $a['total_points'] . " (" . $a['percentage'] . "%)\n";
                $msg .= "🗓 Sana: " . date('d.m.Y H:i', strtotime($a['finished_at'])) . "\n\n";
            }
        } else {
            $msg = "📭 Hali test topshirmagansiz.";
        }
    } else {
        $msg = "⚠️ Iltimos, avval /start ni bosing va telefon raqamingizni yuboring.";
    }
    sendTelegramMessage($chatId, $msg);
}
else {
    $msg = "❓ Men hozircha bu komandani tushunmadim. Menyu orqali foydalaning.";
    sendTelegramMessage($chatId, $msg);
}
