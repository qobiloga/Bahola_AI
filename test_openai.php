<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
if (function_exists('opcache_reset')) opcache_reset();

require_once __DIR__ . '/api/config/openai.php';

$res = callOpenAI([
    ['role' => 'user', 'content' => 'Say "Hello!" if you hear me.']
]);

header('Content-Type: application/json');
echo json_encode(['result' => $res]);
