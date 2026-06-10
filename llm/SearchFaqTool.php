<?php
require_once 'Tool.php';
require_once 'MockDatabase.php';

class SearchFaqTool extends Tool
{
    public function name(): string
    {
        return 'search_faq';
    }
    
    public function description(): string
    {
        return '搜索常見問題知識庫，返回官方答案。適用場景：退換貨政策、運費、發貨時間、售後服務。';
    }
    
    public function parameters(): array
    {
        return [
            'question' => [
                'type' => 'string',
                'description' => '用戶的問題或關鍵詞',
            ],
        ];
    }
    
    public function execute(array $arguments): string
    {
        $question = $arguments['question'] ?? '';
        
        if (empty($question)) {
            return json_encode(['error' => '請提供問題']);
        }
        
        $faq = MockDatabase::queryFAQ($question);
        
        if (empty($faq)) {
            return json_encode(['error' => '未找到相關答案']);
        }
        
        return json_encode(['answer' => $faq['description']]);
    }
}