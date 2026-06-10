# 💡 Laravel AI SDK 核心 Trait 解析

在 Laravel AI SDK 中，`Promptable` 與 `RemembersConversations` 是最常搭配使用的核心組合。它們分別解決了 **「如何調用 AI」** 與 **「如何記住對話」** 這兩個根本問題。

---

## 📋 一句話總結

| Trait | 核心作用 | 缺失後的影響 |
| :--- | :--- | :--- |
| **`Promptable`** | 賦予 Agent 調用 AI 的能力（提供 `prompt()` 方法） | Agent 無法發送消息，完全失去功能 |
| **`RemembersConversations`** | 自動保存與加載對話歷史紀錄 | 每次對話都是全新的，AI 會遺忘上下文 |

---

## 🛠️ 深入解析與實戰應用

### 1. Promptable (發送能力)
此 Trait 是 Agent 的驅動引擎。它封裝了與大語言模型（LLM）通信的底層邏輯，讓你可以用極簡的語法向 AI 發送指令。

*   **核心方法**：`$agent->prompt('你的問題')`
*   **底層邏輯**：自動處理 API 請求、格式化 Prompt，並接收模型的結構化回覆。

### 2. RemembersConversations (記憶能力)
此 Trait 解決了無狀態（Stateless）API 的限制。它將每一次的對話內容（User Prompt 與 Assistant Response）自動持久化。

*   **儲存媒介**：通常依賴 Laravel 的 Cache、Database 或 Session。
*   **動態載入**：在下一次調用 `prompt()` 時，自動將歷史紀錄注入到 payload 中，確保 AI 擁有上下文記憶。

---

## 💻 程式碼實戰範例

以下展示如何在同一個 Agent 中結合這兩個 Trait：

```php
namespace App\Agents;

use LaravelAi\Sdk\Agents\BaseAgent;
use LaravelAi\Sdk\Traits\Promptable;
use LaravelAi\Sdk\Traits\RemembersConversations;

class CustomerSupportAgent extends BaseAgent
{
    use Promptable, RemembersConversations;

    /**
     * 自訂初始化配置
     */
    public function __construct()
    {
        // 定義此 Agent 的系統角色
        \$this->systemMessage('你是一位專業、有禮貌的客服助手。');
    }
}
```

### 執行效果演示

```php
\$agent = new CustomerSupportAgent();

// 第一次對話
response1 = agent->prompt('你好，我叫小明。');
// AI 回覆: "你好，小明！很高興為您服務。今天有什麼可以幫您的？"

// 第二次對話（依賴 RemembersConversations 記住名字）
response2 = agent->prompt('請教我剛才告訴你的名字是什麼？');
// AI 回覆: "您剛才說您叫小明。"
```
