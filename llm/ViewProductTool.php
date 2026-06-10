<?php
//ViewProductTool.php

require_once 'Tool.php';
require_once 'MockDatabase.php';

class ViewProductTool extends Tool
{
    public function name(): string
    {
        return 'view_products';
    }
    
    public function description(): string
    {
        return '根據商品名稱查詢產品信息，返回名稱、產地、描述、價格。適用場景：用戶詢問商品價格、規格、介紹。';
    }
    
    public function parameters(): array
    {
        return [
            'product_name' => [
                'type' => 'string',
                'description' => '商品名稱或關鍵詞',
            ],
        ];
    }
    
    public function execute(array $arguments): string
    {
        $productName = $arguments['product_name'] ?? '';
        
        if (empty($productName)) {
            return json_encode(['error' => '請提供商品名稱']);
        }
        
        $products = MockDatabase::queryProductByKeywords($productName);
        
        if (empty($products)) {
            return json_encode(['error' => "未找到「{$productName}」相關商品"]);
        }
        
        // 如果找到多個，返回選項讓 AI 繼續問
        if (count($products) > 1) {
            $options = [];
            foreach ($products as $p) {
                $options[] = ['title' => $p['title'], 'origin' => $p['origin']];
            }
            return json_encode(['multiple_matches' => true, 'options' => $options]);
        }
        
        // 只有一個，返回詳情
        $p = $products[0];
        return json_encode([
            'name' => $p['title'],
            'origin' => $p['origin'],
            'description' => $p['description'],
            'price' => $p['price_currency'] . ' ' . number_format($p['price']) . '/' . $p['price_unit'],
        ]);
    }
}