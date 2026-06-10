<?php
// Tool.php

abstract class Tool
{
    /**
     * 工具名稱
     */
    abstract public function name(): string;
    
    /**
     * 工具描述（告訴 AI 何時用）
     */
    abstract public function description(): string;
    
    /**
     * 參數 Schema（給 AI 看的）
     */
    abstract public function parameters(): array;
    
    /**
     * 執行邏輯
     */
    abstract public function execute(array $arguments): string;
    
    /**
     * 轉換為 OpenAI 格式的 tool 定義
     */
    public function toDefinition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name(),
                'description' => $this->description(),
                'parameters' => [
                    'type' => 'object',
                    'properties' => $this->parameters(),
                    'required' => array_keys($this->parameters()),
                ],
            ],
        ];
    }
}