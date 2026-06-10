安裝 **Laravel AI SDK** 非常簡單，只需遵循以下四個步驟：

### 1. 使用 Composer 安裝

在你的 Laravel 專案根目錄運行：

Bash

```
composer require laravel/ai

```

### 2. 發佈配置文件與數據庫遷移文件

這會生成 `config/ai.php` 配置文件，並將對話歷史所需的數據庫遷移文件放入 `database/migrations`：

Bash

```
php artisan vendor:publish --provider="Laravel\Ai\AiServiceProvider"

```

### 3. 運行數據庫遷移

這將創建 `agent_conversations` 和 `agent_conversation_messages` 表，用於自動存儲聊天上下文：

Bash

```
php artisan migrate

```

### 4. 配置環境變量

在你的 `.env` 文件中添加你使用的 AI 提供商的 API Key（例如 OpenAI）：

env

```
OPENAI_API_KEY=your_api_key_here

```

完成這些步驟後，你就可以開始編寫我之前提供給你的 Agent 和 Tool 代碼了！如果你需要使用 **RAG** 功能，建議安裝一個向量數據庫驅動（如 Pinecone 或 PostgreSQL 的 pgvector），並在 `config/ai.php` 中進行配置。
