# 簡單 Demo：一般問答 + 產品查詢

這是一個最精簡的 Demo，包含兩個核心功能：一般問題查詢和產品詳情查詢。

## 📁 文件結構

```text
app/Ai/
├── Agents/
│   └── SimpleAssistant.php      # 主 Agent
├── Tools/
│   ├── SearchFaqTool.php        # FAQ 查詢工具
│   └── ViewProductTool.php      # 產品查詢工具
└── Services/
    └── MockDatabase.php         # 模擬數據（覆用你原有的）
```

## 🤖 第一步：創建 Agent

```php
<?php
// app/Ai/Agents/SimpleAssistant.php

namespace App\Ai\Agents;

use App\Ai\Tools\SearchFaqTool;
use App\Ai\Tools\ViewProductTool;
use Laravel\Ai\Agent;
use Laravel\Ai\Promptable;
use Laravel\Ai\Concerns\RemembersConversations;

class SimpleAssistant extends Agent
{
    use Promptable, RemembersConversations;
    
    public function instructions(): string
    {
        return <<<'INSTRUCTIONS'
你是一個簡單的客服助手，只有兩個功能：

## 功能 1：FAQ 問答
- 當用戶詢問退換貨、運費、發貨時間、售後服務等問題時，使用 SearchFaqTool
- 直接輸出工具返回的答案，不要修改

## 功能 2：產品查詢
- 當用戶詢問產品信息（價格、規格、產地、描述）時，使用 ViewProductTool
- 如果找到產品，用友好的方式展示信息
- 如果找不到，告訴用戶沒有這個產品

## 行為規範
- 不要編造信息，始終使用工具
- 回覆語言與用戶保持一致
- 保持回覆簡潔友好
INSTRUCTIONS;
    }
    
    public function tools(): iterable
    {
        return [
            new SearchFaqTool(),
            new ViewProductTool(),
        ];
    }
}
```

## 🛠️ 第二步：創建 FAQ 查詢工具

```php
<?php
// app/Ai/Tools/SearchFaqTool.php

namespace App\Ai\Tools;

use App\Services\MockDatabase;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Laravel\Ai\ToolResult;

class SearchFaqTool implements Tool
{
    public function description(): string
    {
        return '搜索常見問題知識庫，返回官方答案。適用場景：退換貨政策、運費、發貨時間、售後服務等。';
    }

    public function schema($schema): array
    {
        return [
            'question' => $schema->string()
                ->description('用戶的問題或關鍵詞')
                ->required(),
        ];
    }

    public function handle(Request $request): string|ToolResult
    {
        $question = $request['question'] ?? '';
        
        if (empty($question)) {
            return '請提供您的問題。';
        }
        
        // 調用你原有的 MockDatabase 方法
        $faq = MockDatabase::queryFAQ($question);
        
        if (empty($faq)) {
            return '抱歉，暫時沒有找到相關的常見問題解答。';
        }
        
        // FAQ 答案已經是最終格式，不需要 AI 再潤色
        return ToolResult::from($faq['description'])->withoutRefinement();
    }
}
```

## 🛠️ 第三步：創建產品查詢工具

```php
<?php
// app/Ai/Tools/ViewProductTool.php

namespace App\Ai\Tools;

use App\Services\MockDatabase;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class ViewProductTool implements Tool
{
    public function description(): string
    {
        return '根據產品名稱查詢產品詳情，包括名稱、產地、描述、價格等信息。';
    }

    public function schema($schema): array
    {
        return [
            'product_name' => $schema->string()
                ->description('產品名稱或關鍵詞')
                ->required(),
        ];
    }

    public function handle(Request $request): string|array
    {
        $productName = $request['product_name'] ?? '';
        
        if (empty($productName)) {
            return '請提供產品名稱。';
        }
        
        // 調用你原有的 MockDatabase 方法
        $products = MockDatabase::queryProductByKeywords($productName);
        
        if (empty($products)) {
            return "抱歉，沒有找到「{$productName}」相關的產品。";
        }
        
        // 如果找到多個產品
        if (count($products) > 1) {
            $list = [];
            foreach ($products as $p) {
                $list[] = "- {$p['title']}（{$p['origin']}）";
            }
            return "找到多個相關產品：\n" . implode("\n", $list) . "\n\n請告訴我具體哪一個？";
        }
        
        // 只有一個產品，返回結構化數據讓 AI 潤色輸出
        $product = $products[0];
        
        return [
            'name' => $product['title'],
            'origin' => $product['origin'],
            'description' => $product['description'],
            'price' => $product['price_currency'] . ' ' . number_format($product['price']) . '/' . $product['price_unit'],
        ];
    }
}
```

## 🗄️ 第四步：MockDatabase（簡化版）

```php
<?php
// app/Services/MockDatabase.php

namespace App\Services;

class MockDatabase
{
    // FAQ 數據
    private static $faqs = [
        ['question' => '退貨', 'title' => '退貨政策', 'description' => '收到商品後7天內可申請退貨，需保持商品全新狀態及完整包裝。'],
        ['question' => '運費', 'title' => '運費計算', 'description' => '購物滿 HK$300 免運費，未滿需付 HK$50 運費。'],
        ['question' => '發貨', 'title' => '發貨時間', 'description' => '訂單確認後 1-2 個工作天內發貨。'],
        ['question' => '營業', 'title' => '營業時間', 'description' => '客服時間：週一至週五 9:00-18:00。'],
    ];
    
    // 產品數據
    private static $products = [
        [
            'product_id' => 1,
            'title' => '有機富士蘋果',
            'origin' => '日本青森',
            'description' => '甜度高、口感脆，通過有機認證。',
            'price_currency' => 'HK$',
            'price' => 68,
            'price_unit' => '盒',
        ],
        [
            'product_id' => 2,
            'title' => '紐西蘭奇異果',
            'origin' => '紐西蘭',
            'description' => '富含維生素C，金黃色果肉。',
            'price_currency' => 'HK$',
            'price' => 45,
            'price_unit' => '袋',
        ],
        [
            'product_id' => 3,
            'title' => '台灣愛文芒果',
            'origin' => '台灣台南',
            'description' => '香氣濃郁，果肉細緻多汁。',
            'price_currency' => 'HK$',
            'price' => 88,
            'price_unit' => '個',
        ],
    ];
    
    // 關鍵詞匹配 FAQ
    public static function queryFAQ($question)
    {
        foreach (self::$faqs as $faq) {
            if (mb_strpos($question, $faq['question']) !== false) {
                return $faq;
            }
        }
        return null;
    }
    
    // 關鍵詞匹配產品
    public static function queryProductByKeywords($keyword)
    {
        $results = [];
        foreach (self::$products as $product) {
            if (mb_strpos($product['title'], $keyword) !== false) {
                $results[] = $product;
            }
        }
        return $results;
    }
}
```

## 🎮 第五步：控制器

```php
<?php
// app/Http/Controllers/SimpleChatController.php

namespace App\Http\Controllers;

use App\Ai\Agents\SimpleAssistant;
use Illuminate\Http\Request;

class SimpleChatController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'session_id' => 'nullable|string'
        ]);
        
        $agent = new SimpleAssistant();
        
        // 設置會話 ID（用於記憶對話）
        $sessionId = $request->input('session_id', session()->getId());
        $agent->setConversationId($sessionId);
        
        // 調用 Agent
        $response = $agent->prompt($request->input('message'));
        
        return response()->json([
            'success' => true,
            'session_id' => $sessionId,
            'reply' => (string) $response,
        ]);
    }
}
```

## 🛣️ 第六步：路由

```php
// routes/api.php

use App\Http\Controllers\SimpleChatController;

Route::post('/simple-chat', [SimpleChatController::class, 'chat']);
```

## ✅ 測試示例

1.  一般問題查詢

    ```bash
    curl -X POST http://localhost/api/simple-chat \
      -H "Content-Type: application/json" \
      -d '{"message": "請問退貨政策是什麼？"}'
    ```

    返回：

    ```json
    {
        "success": true,
        "session_id": "abc123",
        "reply": "收到商品後7天內可申請退貨，需保持商品全新狀態及完整包裝。"
    }
    ```

2.  產品查詢

    ```bash
    curl -X POST http://localhost/api/simple-chat \
      -H "Content-Type: application/json" \
      -d '{"message": "蘋果多少錢？"}'
    ```

    返回：

    ```json
    {
        "success": true,
        "session_id": "abc123",
        "reply": "為您找到以下產品：\n\n🍎 有機富士蘋果（日本青森）\n甜度高、口感脆，通過有機認證。\n價格：HK$ 68/盒"
    }
    ```

3.  多輪對話（記憶）

    ```bash
    # 第一輪
    curl ... -d '{"message": "蘋果多少錢？"}'
    # 返回：蘋果 HK$68/盒

    # 第二輪（同一個 session_id）
    curl ... -d '{"message": "那奇異果呢？", "session_id": "abc123"}'
    # Agent 知道你在問價格，返回：奇異果 HK$45/袋
    ```

## 📊 功能總結

| 用戶問       | 觸發工具      | 行為           |
| :----------- | :------------ | :------------- |
| "怎麼退貨？" | SearchFaqTool | 直接返回 FAQ 答案 |
| "運費多少？" | SearchFaqTool | 直接返回 FAQ 答案 |
| "蘋果介紹"   | ViewProductTool | 搜索並返回產品信息 |
| "奇異果價格" | ViewProductTool | 搜索並返回價格 |
| "你好"       | 無（LLM 自己處理） | AI 自己回覆問候語 |

這個 Demo 只有 2 個工具，非常簡潔。
