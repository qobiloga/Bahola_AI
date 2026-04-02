<?php
/**
 * Sample OpenAI Configuration
 */

define('OPENAI_API_KEY', 'YOUR_API_KEY_HERE');
define('OPENAI_MODEL', 'gpt-4o');
define('OPENAI_VISION_MODEL', 'gpt-4o');
define('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions');

define('UPLOAD_DIR', __DIR__ . '/../../uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

function callOpenAI($messages, $model = null, $maxTokens = 2000) {
    // Implement OpenAI call logic here
    return "API response";
}

function callOpenAIVision($imageBase64, $prompt, $mimeType = 'image/png') {
    // Implement Vision call logic here
    return "Vision API response";
}
