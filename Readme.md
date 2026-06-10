# Laravel AI SDK: Complete Customer Service Agent (RAG Integrated)

This is a complete, production-ready structure for an AI Customer Service Agent using the **Laravel AI SDK**. It features **RAG** for both FAQ and Product inquiries, ensuring responses are grounded in real company data.

---

## 1. The Core Agent
The `CustomerServiceAgent` acts as the orchestrator, utilizing specialized tools to handle different customer needs.

```php
namespace App\Ai\Agents;

use App\Ai\Tools\KnowledgeBaseTool;
use App\Ai\Tools\OrderProductTool;
use App\Ai\Tools\TrackOrderTool;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Promptable;
use Stringable;

class CustomerServiceAgent implements Agent, HasTools, Conversational
{
    use Promptable;

    /**
     * The unique identifier for the conversation session.
     */
    public function __construct(protected string $sessionId) {}

    /**
     * Automatically retrieve conversation history from the database.
     * The AI SDK handles the storage/retrieval via its built-in migrations.
     */
    public function conversationId(): string
    {
        return $this->sessionId;
    }

    public function instructions(): Stringable|string
    {
        return <<<EOT
        You are the official AI Assistant for "Manus Tech". 
        Use the following tools to provide accurate, data-driven responses:
        
        1. **Knowledge Base Tool**: ALWAYS use this for any questions about products, specifications, shipping policies, returns, or general company info (RAG).
        2. **Order Product Tool**: Use this when a customer wants to buy something.
        3. **Track Order Tool**: Use this to check the status of an existing order.
        
        CRITICAL: Do not make up facts. If the Knowledge Base doesn't have the answer, ask the customer to contact human support at support@manus.com.
        EOT;
    }

    public function tools(): iterable
    {
        return [
            new KnowledgeBaseTool(), // Handles both FAQ and Product RAG
            new OrderProductTool(),
            new TrackOrderTool(),
        ];
    }
}
```

---

## 2. The RAG Tool (FAQ & Products)
Instead of hardcoding data, this tool queries a **Vector Store** containing your company's documentation.

```php
namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Facades\VectorStore;
use Laravel\Ai\Tools\Request;
use Stringable;

class KnowledgeBaseTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Search the company knowledge base for product specs, FAQs, and policies.';
    }

    public function handle(Request $request): Stringable|string
    {
        // Search across 'faq' and 'products' stores
        $results = VectorStore::search($request['query'], limit: 5);

        if ($results->isEmpty()) {
            return "No matching information found in the knowledge base.";
        }

        return $results->map(fn($res) => "[Source: {$res->metadata['type']}] {$res->content}")->implode("\n\n");
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('The search query for the knowledge base.')->required(),
        ];
    }
}
```

---

## 3. Transactional Tools (Ordering & Tracking)
These tools handle actions and real-time data lookups.

```php
namespace App\Ai\Tools;

// --- Order Tool ---
class OrderProductTool implements \Laravel\Ai\Contracts\Tool {
    public function description(): Stringable|string { return 'Place a new product order.'; }
    public function handle(\Laravel\Ai\Tools\Request $request): Stringable|string {
        // In a real app, you'd insert into the 'orders' table here
        $id = 'ORD-' . rand(1000, 9999);
        return "Order placed! Product: {$request['item']}, Qty: {$request['qty']}. Order ID: {$id}.";
    }
    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array {
        return [
            'item' => $schema->string()->required(),
            'qty' => $schema->integer()->min(1)->required(),
        ];
    }
}

// --- Tracking Tool ---
class TrackOrderTool implements \Laravel\Ai\Contracts\Tool {
    public function description(): Stringable|string { return 'Check order status by ID.'; }
    public function handle(\Laravel\Ai\Tools\Request $request): Stringable|string {
        $mockDB = ['ORD-123' => 'Shipped', 'ORD-456' => 'Processing'];
        $status = $mockDB[$request['order_id']] ?? 'Not Found';
        return "Status for {$request['order_id']}: {$status}.";
    }
    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array {
        return ['order_id' => $schema->string()->required()];
    }
}
```

---

## 4. Data Seeding (Populating the RAG)
Run this once to "teach" the AI your company data.

```php
use Laravel\Ai\Facades\VectorStore;

// Indexing FAQs
VectorStore::add([
    ['content' => 'Shipping takes 3-5 days.', 'metadata' => ['type' => 'faq', 'topic' => 'shipping']],
    ['content' => 'Returns are accepted within 30 days.', 'metadata' => ['type' => 'faq', 'topic' => 'returns']],
]);

// Indexing Products
VectorStore::add([
    ['content' => 'Manus Pro: 16-inch OLED, 64GB RAM.', 'metadata' => ['type' => 'product', 'name' => 'Manus Pro']],
    ['content' => 'Manus Air: Ultra-light, 18-hour battery.', 'metadata' => ['type' => 'product', 'name' => 'Manus Air']],
]);
```

---

## 5. Usage in Controller (with Context)
The SDK automatically saves every message to the `agent_conversations` table using the `sessionId`.

```php
public function __invoke(Request $request) {
    // Pass a unique session ID (e.g., from Auth or Session)
    $sessionId = $request->session()->getId();

    $response = CustomerServiceAgent::make(sessionId: $sessionId)
        ->prompt($request->message);

    return [
        'answer' => (string) $response
    ];
}
```

### How it works:
1. **`Conversational` Interface**: By implementing this, you tell the SDK to look for previous messages linked to the `conversationId`.
2. **Automatic Storage**: The SDK's `agent_conversations` and `agent_conversation_messages` tables (created during installation) store the history automatically.
3. **Context Window**: On every prompt, the SDK retrieves the history and includes it in the LLM call so the agent "remembers" what was said earlier.
