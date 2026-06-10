<?php
require_once 'config.php';
require_once 'ViewProductTool.php';
require_once 'SearchFaqTool.php';

class Agent
{
    private $config;
    private $tools = [];
    private $conversationId;
    private $maxIterations;
    
    public function __construct(string $conversationId = null)
    {
        $this->config = require __DIR__ . '/config.php';
        $this->conversationId = $conversationId ?? uniqid('session_');
        $this->maxIterations = $this->config['max_iterations'] ?? 10;
        
        // 注冊工具
        $this->registerTools();
    }
    
    /**
     * 注冊所有可用工具
     */
    private function registerTools(): void
    {
        $this->tools = [
            new ViewProductTool(),
            new SearchFaqTool(),
            // 添加更多工具...
        ];
    }
    
    /**
     * 獲取工具定義（給 AI 看）
     */
    private function getToolsDefinition(): array
    {
        $definitions = [];
        foreach ($this->tools as $tool) {
            $definitions[] = $tool->toDefinition();
        }
        return $definitions;
    }
    
    /**
     * 根據名稱執行工具
     */
    private function executeTool(string $toolName, array $arguments): string
    {
        foreach ($this->tools as $tool) {
            if ($tool->name() === $toolName) {
                return $tool->execute($arguments);
            }
        }
        return json_encode(['error' => "沒有工具"]);
    }
    
    /**
     * 調用 LLM API
     */
    private function callApi(array $messages, ?array $tools = null): array
    {
        $data = [
            'model' => $this->config['model'],
            'messages' => $messages,
            'temperature' => $this->config['temperature'],
            'max_tokens' => $this->config['max_tokens'],
        ];
        
        if ($tools) {
            $data['tools'] = $tools;
            $data['tool_choice'] = 'auto';
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->config['api_url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->config['api_key'],
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 60,
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("API Error: {$error}");
        }
        
        return json_decode($response, true);
    }
    
    /**
     * 加載對話歷史
     */
    private function loadHistory(): array
    {
        $file = $this->config['session_path'] . $this->conversationId . '.json';
        
        if (!file_exists($file)) {
            return [];
        }
        
        $data = json_decode(file_get_contents($file), true);
        
        // 只保留最近 20 條消息（10 輪對話）
        $messages = $data['messages'] ?? [];
        if (count($messages) > 20) {
            $messages = array_slice($messages, -20);
        }
        
        return $messages;
    }
    
    /**
     * 保存對話歷史
     */
    private function saveHistory(array $messages): void
    {
        $file = $this->config['session_path'] . $this->conversationId . '.json';
        
        // 確保目錄存在
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($file, json_encode([
            'conversation_id' => $this->conversationId,
            'messages' => $messages,
            'last_activity' => time(),
        ], JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * Agent 系統提示詞
     */
    private function getSystemPrompt(): string
    {
        return <<<'SYSTEM'
        你是悠悠水果客服，負責處理客戶查詢：
        1. 查詢商品價格、產地、介紹（使用 view_products）
        2. 查詢退換貨、運費、出貨時間（使用 search_faq）

        ## 回覆規則

        ### 當用戶查詢商品或常見問題時：
        - 使用對應工具查詢，如沒有工具則直接回答：「抱歉，只提供查詢商品資訊和常見問題。」
        - 如工具返回的結果，必須真實回答，禁止編造。

        ### 回覆風格：
        - 專業，親切，友善
        - 使用與用戶相同的語言（繁體中文）
        
        SYSTEM;
    }
    
    /**
     * 主入口：發送消息，獲取回覆
     */
    public function chat(string $userMessage): string
    {
        // 1. 加載歷史
        $history = $this->loadHistory();
        
        // 2. 構建初始消息
        $messages = [
            ['role' => 'system', 'content' => $this->getSystemPrompt()],
        ];
        $messages = array_merge($messages, $history);
        $messages[] = ['role' => 'user', 'content' => $userMessage];
        
        $tools = $this->getToolsDefinition();
        $iteration = 0;
        
        // 3. ReAct 循環
        while ($iteration < $this->maxIterations) {
            $iteration++;
            
            // 調用 API
            $response = $this->callApi($messages, $tools);
            $assistantMessage = $response['choices'][0]['message'] ?? null;
            
            if (!$assistantMessage) {
                return "AI 響應異常，請稍後重試。";
            }
            
            // 添加到消息歷史
            $messages[] = $assistantMessage;
            
            // 檢查是否有工具調用
            if (empty($assistantMessage['tool_calls'])) {
                // 沒有工具調用，返回最終答案
                $answer = $assistantMessage['content'] ?? '抱歉，我無法回答這個問題。';
                
                // 保存完整對話歷史
                $allMessages = array_merge($history, [
                    ['role' => 'user', 'content' => $userMessage],
                    ['role' => 'assistant', 'content' => $answer],
                ]);
                $this->saveHistory($allMessages);
                
                return $answer;
            }
            
            // 有工具調用，執行每個工具
            foreach ($assistantMessage['tool_calls'] as $toolCall) {
                $toolName = $toolCall['function']['name'];
                $arguments = json_decode($toolCall['function']['arguments'], true);
                
                // 執行工具
                $result = $this->executeTool($toolName, $arguments);
                
                // 添加工具結果到消息歷史
                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $toolCall['id'],
                    'content' => $result,
                ];
            }
            
            // 繼續循環，讓 AI 處理工具結果
        }
        
        return "處理超時，請簡化您的問題後重試。";
    }
    
    /**
     * 獲取會話 ID
     */
    public function getConversationId(): string
    {
        return $this->conversationId;
    }
    
    /**
     * 清理過期會話
     */
    public static function cleanupOldSessions(int $maxAgeHours = 24): void
    {
        $config = require __DIR__ . '/config.php';
        $path = $config['session_path'];
        $maxAge = $maxAgeHours * 3600;
        
        foreach (glob($path . '*.json') as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (time() - ($data['last_activity'] ?? 0) > $maxAge) {
                unlink($file);
            }
        }
    }
}