<?php
/**
 * Ustoz ko'makchi - HEMIS Integration API (Mock)
 * 
 * Bu mock API haqiqiy HEMIS tizimini simulyatsiya qiladi.
 * Hackathon uchun demo ma'lumotlar qaytaradi.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'student':
        // HEMIS: Talaba ma'lumotlari
        $hemisId = $_GET['hemis_id'] ?? '';
        echo json_encode([
            'success' => true,
            'data' => [
                'hemis_id' => $hemisId ?: 'H12345',
                'full_name' => 'Jasur Karimov',
                'group' => 'CS-21',
                'course' => 3,
                'faculty' => 'Kompyuter ilmlari',
                'gpa' => 3.8,
                'subjects' => [
                    ['id' => 1, 'name' => 'Matematika', 'teacher' => 'Sardor Raximov'],
                    ['id' => 2, 'name' => 'Fizika', 'teacher' => 'Aziz Yusupov'],
                    ['id' => 3, 'name' => 'O\'zbekiston tarixi', 'teacher' => 'Nilufar Qodirova'],
                ]
            ]
        ]);
        break;

    case 'teacher':
        // HEMIS: O'qituvchi ma'lumotlari
        $hemisId = $_GET['hemis_id'] ?? '';
        echo json_encode([
            'success' => true,
            'data' => [
                'hemis_id' => $hemisId ?: 'T67890',
                'full_name' => 'Sardor Raximov',
                'department' => 'Matematika kafedrasi',
                'position' => 'Dotsent',
                'subjects' => [
                    ['id' => 1, 'name' => 'Matematika', 'code' => 'MATH101'],
                    ['id' => 2, 'name' => 'Oliy matematika', 'code' => 'MATH201'],
                ],
                'groups' => [
                    ['id' => 1, 'name' => 'CS-21', 'students_count' => 40],
                    ['id' => 2, 'name' => 'CS-22', 'students_count' => 38],
                    ['id' => 3, 'name' => 'IT-21', 'students_count' => 35],
                    ['id' => 4, 'name' => 'IT-22', 'students_count' => 42],
                ]
            ]
        ]);
        break;

    case 'groups':
        // HEMIS: Guruh talabalari
        $groupId = $_GET['group_id'] ?? '';
        $students = [];
        $names = [
            'Jasur Karimov', 'Nilufar Azimova', 'Bobur Toshmatov',
            'Dilnoza Karimova', 'Sardor Aliyev', 'Madina Yusupova',
            'Bekzod Rahimov', 'Zulfiya Norova', 'Javlon Abdullayev',
            'Feruza Islomova'
        ];
        foreach ($names as $i => $name) {
            $students[] = [
                'hemis_id' => 'H' . (10000 + $i),
                'full_name' => $name,
                'group' => $groupId ?: 'CS-21',
            ];
        }
        echo json_encode(['success' => true, 'data' => $students]);
        break;

    case 'subjects':
        // HEMIS: Fanlar ro'yxati
        echo json_encode([
            'success' => true,
            'data' => [
                ['id' => 1, 'name' => 'Matematika', 'code' => 'MATH101'],
                ['id' => 2, 'name' => 'Fizika', 'code' => 'PHYS101'],
                ['id' => 3, 'name' => 'O\'zbekiston tarixi', 'code' => 'HIST101'],
                ['id' => 4, 'name' => 'Ingliz tili', 'code' => 'ENG101'],
                ['id' => 5, 'name' => 'Dasturlash', 'code' => 'CS101'],
            ]
        ]);
        break;

    default:
        echo json_encode([
            'success' => true,
            'message' => 'HEMIS Integration API (Mock)',
            'version' => '1.0',
            'endpoints' => [
                'student' => '?action=student&hemis_id=H12345',
                'teacher' => '?action=teacher&hemis_id=T67890',
                'groups' => '?action=groups&group_id=CS-21',
                'subjects' => '?action=subjects',
            ]
        ]);
}
