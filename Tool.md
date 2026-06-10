## Tool 默认结构模板
  
	 <?php
    
    namespace App\Ai\Tools;
    
    use App\Services\MockDatabase;  // 根据实际调整
    use Laravel\Ai\Contracts\Tool;
    use Laravel\Ai\Tools\Request;
    
    class [ToolName]Tool implements Tool
    {
        /**
         * 工具描述 - 告诉 AI 这个工具是做什么的，何时应该调用
         * 
         * @return string
         */
        public function description(): string
        {
            return '【一句话说明工具功能】。适用场景：【什么时候应该调用这个工具】';
        }
    
        /**
         * 参数定义 - 定义 AI 需要提供哪些参数才能调用这个工具
         * 
         * @param mixed $schema Schema 构建器
         * @return array
         */
        public function schema($schema): array
        {
            return [
                // 参数1：必填
                'param_name' => $schema->string()
                    ->description('参数说明')
                    ->required(),
                
                // 参数2：可选，有默认值
                'optional_param' => $schema->integer()
                    ->description('可选参数说明')
                    ->default(1)
                    ->min(1)
                    ->max(100),
                
                // 参数3：枚举类型
                'status' => $schema->string()
                    ->description('状态筛选')
                    ->enum(['pending', 'completed', 'cancelled']),
                
                // 参数4：数组类型
                'items' => $schema->array()
                    ->description('商品列表')
                    ->items(
                        $schema->object()
                            ->properties([
                                'name' => $schema->string()->required(),
                                'qty' => $schema->integer()->default(1),
                            ])
                    ),
            ];
        }
    
        /**
         * 执行逻辑 - 工具被调用时实际执行的代码
         * 
         * @param Request $request 包含 AI 提供的参数
         * @return string|array|ToolResult
         */
        public function handle(Request $request): string|array|ToolResult
        {
            // 1. 获取参数（带默认值）
            $param1 = $request['param_name'] ?? null;
            $optional = $request['optional_param'] ?? 1;
            
            // 2. 参数验证
            if (empty($param1)) {
                return '错误：缺少必要参数 param_name';
            }
            
            // 3. 调用业务逻辑（复用你原有的 MockDatabase 方法）
            $result = MockDatabase::someMethod($param1, $optional);
            
            // 4. 处理空结果
            if (empty($result)) {
                return '未找到相关数据';
            }
            
            // 5. 返回结果
            // 方式 A：返回纯文本（简单场景）
            return "操作成功：{$result}";
            
            // 方式 B：返回数组（复杂数据，让 AI 润色）
            // return ['data' => $result, 'count' => count($result)];
        }
    }


| 類型 | 方法 | 示例 |
| :--- | :--- | :--- |
| **字串** | `$schema->string()` | `$schema->string()->description('商品名稱')->required()` |
| **整數** | `$schema->integer()` | `$schema->integer()->min(1)->max(999)->default(1)` |
| **浮點數** | `$schema->number()` | `$schema->number()->min(0.01)->description('價格')` |
| **布爾值** | `$schema->boolean()` | `$schema->boolean()->default(false)` |
| **陣列** | `$schema->array()` | `$schema->array()->items($schema->string())` |
| **物件** | `$schema->object()` | `$schema->object()->properties([...])` |
| **枚舉** | `$schema->->enum()` | `->enum(['pending', 'done'])` |


### 每個 Tool 必須包含的 3 個部分

| 方法 | 作用 | 必須返回 |
| :--- | :--- | :--- |
| `description()` | 告訴 AI 何時調用 | `string` |
| `schema()` | 定義需要哪些參數 | `array` |
| `handle()` | 執行實際邏輯 | `string` 或 `array` 或 `ToolResult` |
