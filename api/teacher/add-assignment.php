<?php
header('Content-Type: application/json');
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Not a POST request']);
    exit;
}

$teacher_id = $_POST['teacher_id'] ?? 1;
$title = $_POST['title'] ?? '';
$subject_id = $_POST['subject_id'] ?? 1; // Default
$group_id = $_POST['group_id'] ?? 1;
$rubric = $_POST['rubric'] ?? '';
$deadline = $_POST['deadline'] ?? date('Y-m-d H:i:s', strtotime('+7 days'));

$result = ['success' => false];

try {
    if (empty($title)) throw new Exception("Topshiriq nomi bo'sh!");
    
    // File upload logic
    $filePath = '';
    if (isset($_FILES['task_file']) && $_FILES['task_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../uploads/tasks/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $fileName = time() . '_' . basename($_FILES['task_file']['name']);
        $destPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['task_file']['tmp_name'], $destPath)) {
            $filePath = '/uploads/tasks/' . $fileName;
        } else {
            throw new Exception("Fayl yuklashda xatolik yuz berdi.");
        }
    } else {
        throw new Exception("Fayl yuklanmadi yoki xato.");
    }

    $db = getDB();
    $stmt = $db->prepare("INSERT INTO assignments (title, subject_id, teacher_id, group_id, task_pdf_path, rubric, deadline) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $subject_id, $teacher_id, $group_id, $filePath, $rubric, $deadline]);

    $result['success'] = true;
    $result['message'] = "Topshiriq muvaffaqiyatli qo'shildi!";
    
} catch (Exception $e) {
    $result['error'] = $e->getMessage();
}

echo json_encode($result);
