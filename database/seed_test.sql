-- Demo Informatika Testini Qushish
USE ustoz_helper;

-- 1. Insert a demo test if it doesn't exist
INSERT INTO tests (id, title, description, subject_id, teacher_id, time_limit, status) 
VALUES (100, 'Informatika: Demo Namunaviy Test', 'Bu test bosh sahifada ko''rsatish uchun. Asosiy tushunchalar.', 1, 2, 30, 'active')
ON DUPLICATE KEY UPDATE title=title;

-- Toza boshlash uchun eski savollarni tozalaymiz
DELETE FROM questions WHERE test_id = 100;

-- 2. Savollar va variantlarni kiritamiz
-- Savol 1
INSERT INTO questions (id, test_id, question_text, question_type, order_num) VALUES (1001, 100, 'Axborot o''lchov birliklaridan qaysi biri eng katta?', 'single', 1);
INSERT INTO answer_options (question_id, option_text, is_correct, option_order) VALUES 
(1001, 'Megabayt (MB)', 0, 1), (1001, 'Gigabayt (GB)', 0, 2), (1001, 'Terabayt (TB)', 1, 3), (1001, 'Kilobayt (KB)', 0, 4);

-- Savol 2
INSERT INTO questions (id, test_id, question_text, question_type, order_num) VALUES (1002, 100, 'Protsessor qanday vazifani bajaradi?', 'single', 2);
INSERT INTO answer_options (question_id, option_text, is_correct, option_order) VALUES 
(1002, 'Malumotlarni doimiy saqlaydi', 0, 1), (1002, 'Ekranga tasvir uzatadi', 0, 2), (1002, 'Axborotni qayta ishlaydi va qurilmalarni boshqaradi', 1, 3), (1002, 'Sovutish tizimini ishlatadi', 0, 4);

-- Savol 3
INSERT INTO questions (id, test_id, question_text, question_type, order_num) VALUES (1003, 100, 'RAM qanday xotira turiga kiradi?', 'single', 3);
INSERT INTO answer_options (question_id, option_text, is_correct, option_order) VALUES 
(1003, 'Tashqi xotira', 0, 1), (1003, 'Asosiy (tezkor) xotira', 1, 2), (1003, 'Optik xotira', 0, 3), (1003, 'Magnit xotira', 0, 4);

-- Savol 4
INSERT INTO questions (id, test_id, question_text, question_type, order_num) VALUES (1004, 100, 'Kompyuter algoritmida shartni tekshirish qaysi blok bilan belgilanadi?', 'single', 4);
INSERT INTO answer_options (question_id, option_text, is_correct, option_order) VALUES 
(1004, 'To''rtburchak', 0, 1), (1004, 'Ellips', 0, 2), (1004, 'Romb', 1, 3), (1004, 'Parallelogramm', 0, 4);

-- Savol 5
INSERT INTO questions (id, test_id, question_text, question_type, order_num) VALUES (1005, 100, 'Web sahifalar yaratishda asosiy til nima?', 'single', 5);
INSERT INTO answer_options (question_id, option_text, is_correct, option_order) VALUES 
(1005, 'Python', 0, 1), (1005, 'C++', 0, 2), (1005, 'HTML', 1, 3), (1005, 'Pascal', 0, 4);

-- Savol 6
INSERT INTO questions (id, test_id, question_text, question_type, order_num) VALUES (1006, 100, 'Fayl kengaytmasi (extension) nimani anglatadi?', 'single', 6);
INSERT INTO answer_options (question_id, option_text, is_correct, option_order) VALUES 
(1006, 'Fayl hajmini', 0, 1), (1006, 'Fayl turini va qaysi dasturda ochilishini', 1, 2), (1006, 'Fayl nomini', 0, 3), (1006, 'Yaratilgan sanani', 0, 4);

-- Savol 7
INSERT INTO questions (id, test_id, question_text, question_type, order_num) VALUES (1007, 100, 'Axborotlarni himoya qilish maqsadida ularni shifrlash fani - ... deb ataladi.', 'single', 7);
INSERT INTO answer_options (question_id, option_text, is_correct, option_order) VALUES 
(1007, 'Kriptografiya', 1, 1), (1007, 'Steganografiya', 0, 2), (1007, 'Tipografiya', 0, 3), (1007, 'Topologiya', 0, 4);

-- Savol 8
INSERT INTO questions (id, test_id, question_text, question_type, order_num) VALUES (1008, 100, 'Bir bayt necha bitdan iborat?', 'single', 8);
INSERT INTO answer_options (question_id, option_text, is_correct, option_order) VALUES 
(1008, '10 bit', 0, 1), (1008, '8 bit', 1, 2), (1008, '16 bit', 0, 3), (1008, '2 bit', 0, 4);

-- Savol 9
INSERT INTO questions (id, test_id, question_text, question_type, order_num) VALUES (1009, 100, 'Qaysi biri dasturlash tili emas?', 'single', 9);
INSERT INTO answer_options (question_id, option_text, is_correct, option_order) VALUES 
(1009, 'Python', 0, 1), (1009, 'Java', 0, 2), (1009, 'Microsoft Excel', 1, 3), (1009, 'C#', 0, 4);

-- Savol 10
INSERT INTO questions (id, test_id, question_text, question_type, order_num) VALUES (1010, 100, 'Global kompyuter tarmog''i qanday nomlanadi?', 'single', 10);
INSERT INTO answer_options (question_id, option_text, is_correct, option_order) VALUES 
(1010, 'Internet', 1, 1), (1010, 'Intranet', 0, 2), (1010, 'LAN', 0, 3), (1010, 'Bluetooth', 0, 4);

-- Savol 11
INSERT INTO questions (id, test_id, question_text, question_type, order_num) VALUES (1011, 100, 'Brauzer nima?', 'single', 11);
INSERT INTO answer_options (question_id, option_text, is_correct, option_order) VALUES 
(1011, 'Matn muharriri', 0, 1), (1011, 'Web-sahifalarni ko''rish uchun dastur', 1, 2), (1011, 'Antivirus turi', 0, 3), (1011, 'Klaviatura', 0, 4);

-- Savol 12
INSERT INTO questions (id, test_id, question_text, question_type, order_num) VALUES (1012, 100, 'Kompyuterning asosiy platalari nima deb nomlanadi?', 'single', 12);
INSERT INTO answer_options (question_id, option_text, is_correct, option_order) VALUES 
(1012, 'Motherboard (Ona plata)', 1, 1), (1012, 'Hard disk', 0, 2), (1012, 'Power supply', 0, 3), (1012, 'Monitor', 0, 4);

-- Savol 13
INSERT INTO questions (id, test_id, question_text, question_type, order_num) VALUES (1013, 100, 'Ikkilik sanoq sistemasida qaysi raqamlar ishlatiladi?', 'single', 13);
INSERT INTO answer_options (question_id, option_text, is_correct, option_order) VALUES 
(1013, '1 dan 10 gacha', 0, 1), (1013, 'Faqat 0 va 1', 1, 2), (1013, '0 dan 9 gacha', 0, 3), (1013, '8 va 16', 0, 4);

-- Savol 14
INSERT INTO questions (id, test_id, question_text, question_type, order_num) VALUES (1014, 100, 'Python tilida matnni ekranga chiqaruvchi funksiya qaysi?', 'single', 14);
INSERT INTO answer_options (question_id, option_text, is_correct, option_order) VALUES 
(1014, 'echo()', 0, 1), (1014, 'cout <<', 0, 2), (1014, 'print()', 1, 3), (1014, 'display()', 0, 4);

-- Savol 15
INSERT INTO questions (id, test_id, question_text, question_type, order_num) VALUES (1015, 100, 'SQL qanday vazifada ishlatiladi?', 'single', 15);
INSERT INTO answer_options (question_id, option_text, is_correct, option_order) VALUES 
(1015, 'Operatsion tizim yaratishda', 0, 1), (1015, 'Ma''lumotlar bazasini boshqarishda', 1, 2), (1015, 'O''йинlar (3D) dasturlashda', 0, 3), (1015, 'Word hujjatlarni tahrirlashda', 0, 4);
