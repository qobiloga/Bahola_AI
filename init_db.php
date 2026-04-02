<?php
require_once 'api/config/database.php';

try {
    $db = getDB();
    
    // Read and execute seed.sql
    echo "Executing seed.sql...\n";
    $sql1 = file_get_contents('database/seed.sql');
    $db->exec($sql1);
    
    // Read and execute seed_test.sql
    echo "Executing seed_test.sql...\n";
    $sql2 = file_get_contents('database/seed_test.sql');
    $db->exec($sql2);
    
    echo "Barchasi muvaffaqiyatli saqlandi va bazaga yuklandi!\n";

} catch (PDOException $e) {
    echo "Baza xatosi: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Xato: " . $e->getMessage() . "\n";
}
