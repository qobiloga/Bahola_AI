-- Ustoz ko'makchi - Mock Data for Testing Teacher Dashboard
USE ustoz_helper;

-- 1. Insert a mock teacher
INSERT INTO users (id, full_name, email, password_hash, role, hemis_id) 
VALUES (2, 'Sardor Raximov', 'teacher@smarteducation.uz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'T123456')
ON DUPLICATE KEY UPDATE full_name='Sardor Raximov';

-- 2. Insert mock groups
INSERT INTO `groups` (id, name, course) VALUES 
(1, 'CS-21', 2), 
(2, 'CS-22', 2)
ON DUPLICATE KEY UPDATE name=name;

-- 3. Insert mock subjects
INSERT INTO subjects (id, name, teacher_id) VALUES 
(1, 'Informatika', 2),
(2, 'Obyektga Yo''naltirilgan Dasturlash', 2)
ON DUPLICATE KEY UPDATE name=name;

-- 4. Insert mock students
INSERT INTO users (id, full_name, email, password_hash, role, group_id) VALUES 
(3, 'Jasur Karimov', 'jasur@student.uz', 'hash', 'student', 1),
(4, 'Nilufar Azimova', 'nilu@student.uz', 'hash', 'student', 1),
(5, 'Bobur Toshmatov', 'bobur@student.uz', 'hash', 'student', 2)
ON DUPLICATE KEY UPDATE full_name=full_name;

-- 5. Insert some mock tests (Recent tests)
INSERT INTO tests (id, title, subject_id, teacher_id, status) VALUES 
(1, 'Kompyuter tarmoqlari', 1, 2, 'active'),
(2, 'C++ asoslari', 2, 2, 'closed'),
(3, 'OOP prinsiplari', 2, 2, 'active')
ON DUPLICATE KEY UPDATE title=title;

-- Note: In a real app test_attempts table will give the attempts_count and avg_score.
INSERT INTO test_attempts (test_id, student_id, score, total_points, percentage, status) VALUES 
(1, 3, 18, 20, 90, 'completed'),
(1, 4, 15, 20, 75, 'completed'),
(2, 5, 12, 20, 60, 'completed');

-- 6. Insert mock assignments and pending papers (Submissions)
INSERT INTO assignments (id, title, subject_id, teacher_id, group_id, task_pdf_path) VALUES 
(1, 'Antiviruslar haqida mustaqil ish', 1, 2, 1, '/uploads/tasks/task1.pdf'),
(2, 'Algoritmlash masalalari', 2, 2, 2, '/uploads/tasks/task2.pdf')
ON DUPLICATE KEY UPDATE title=title;

INSERT INTO submissions (assignment_id, student_id, submitted_pdf_path, status, submitted_at) VALUES 
(1, 3, '/uploads/subs/s3.jpg', 'submitted', NOW() - INTERVAL 2 HOUR),
(1, 4, '/uploads/subs/s4.jpg', 'ai_reviewed', NOW() - INTERVAL 5 HOUR),
(2, 5, '/uploads/subs/s5.jpg', 'submitted', NOW() - INTERVAL 1 DAY);
