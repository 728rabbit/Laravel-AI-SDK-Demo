<?php
require_once 'Agent.php';

$sessionId = 'test_session_001';
$agent = new Agent($sessionId);

// 模擬對話數列
$conversations = [
    "你好，我想買水果",
    "蘋果怎麼賣？",
    "那奇異果呢？",
    "幫我把蘋果加到購物車，要2盒",
    "日本富士蘋果",
    "看一下我的購物車有什麼",
    "把蘋果改成3盒",
    "我要結帳，地址是香港中環德輔道中151號",
    "可以幫我查一下訂單嗎？",
    "算了，我不買了，取消訂單",
    "謝謝你，再見"
];

$step = 1;
foreach ($conversations as $userMessage) {
    echo "────────────────────────────────────────────────────────<br/><br/>";
    echo "第 " . $step . " 輪對話<br/><br/>";
    echo "────────────────────────────────────────────────────────<br/><br/>";
    echo "👤 用戶: {$userMessage}<br/><br/>";
    
    try {
        $reply = $agent->chat($userMessage);
        $reply = nl2br($reply);
        echo "🤖 AI  : {$reply}<br/><br/>";
    } catch (Exception $e) {
        echo "❌ 錯誤: " . $e->getMessage() . "<br/><br/>";
    }
    
    echo "<br/><br/>";
    $step++;
}