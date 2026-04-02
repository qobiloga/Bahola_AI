<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$teacher_id = $_GET['teacher_id'] ?? 1;
$result = ['success' => true, 'subjects' => [], 'groups' => []];

try {
    $db = getDB();
    
    // Fetch teacher's subjects
    $stmt = $db->prepare("SELECT id, name FROM subjects WHERE teacher_id = ? ORDER BY name ASC");
    $stmt->execute([$teacher_id]);
    $result['subjects'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch groups (all groups for now, since groups are universal)
    $stmt = $db->prepare("SELECT id, name, course FROM `groups` ORDER BY course ASC, name ASC");
    $stmt->execute();
    $result['groups'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $result['success'] = false;
    $result['error'] = $e->getMessage();
}

echo json_encode($result);
