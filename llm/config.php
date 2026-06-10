<?php
// config.php

return [
    'api_url' => 'https://api.deepseek.com/v1/chat/completions',
    'api_key' => 'sk-',
    'model' => 'deepseek-chat',
    
    /*'api_url' => 'https://yinli.one/v1/chat/completions',
    'api_key' => 'sk-',
    'model' => 'gpt-3.5-turbo',*/
    
    // Agent 配置
    'max_iterations' => 10,      // 最多工具調用輪數
    'temperature' => 0,        // 精確模式
    'max_tokens' => 2048,
    'session_path' => __DIR__ . '/sessions/',
];