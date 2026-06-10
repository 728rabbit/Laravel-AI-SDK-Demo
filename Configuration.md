## Agent Configuration

You may configure text generation options for an agent using PHP attributes. The following attributes are available:

-   `MaxSteps`: The maximum number of steps the agent may take when using tools.
-   `MaxTokens`: The maximum number of tokens the model may generate.
-   `Model`: The model the agent should use.
-   `Provider`: The AI provider (or providers for failover) to use for the agent.
-   `Temperature`: The sampling temperature to use for generation (0.0 to 1.0).
-   `Timeout`: The HTTP timeout in seconds for agent requests (default: 60).
-   `TopP`: The nucleus sampling probability to use for generation (0.0 to 1.0).
-   `UseCheapestModel`: Use the provider's cheapest text model for cost optimization.
-   `UseSmartestModel`: Use the provider's most capable text model for complex tasks.

---

- `MaxSteps`：智慧體使用工具時可執行的最大步數。

- `MaxTokens`：模型可產生的最大令牌數。

- `Model`：智能體應使用的模型。

- `Provider`：智能體使用的 AI 提供者（或稱故障轉移提供者）。

- `Temperature`：用於產生的採樣溫度（0.0 到 1.0）。

- `Timeout`：智能體請求的 HTTP 逾時時間（以秒為單位，預設值：60）。

- `TopP`：用於產生的內核採樣機率（0.0 到 1.0）。

- `UseCheapestModel`：使用提供者中最便宜的文字模型以優化成本。

- `UseSmartestModel`：使用提供者功能最強大的文字模型來處理複雜任務。

---

    namespace App\Ai\Agents;
    
    use Laravel\Ai\Attributes\MaxSteps;
    use Laravel\Ai\Attributes\MaxTokens;
    use Laravel\Ai\Attributes\Model;
    use Laravel\Ai\Attributes\Provider;
    use Laravel\Ai\Attributes\Temperature;
    use Laravel\Ai\Attributes\Timeout;
    use Laravel\Ai\Attributes\TopP;
    use Laravel\Ai\Contracts\Agent;
    use Laravel\Ai\Enums\Lab;
    use Laravel\Ai\Promptable;
    
    #[Provider(Lab::Anthropic)]
    #[Model('claude-haiku-4-5-20251001')]
    #[MaxSteps(10)]
    #[MaxTokens(4096)]
    #[Temperature(0.7)]
    #[Timeout(120)]
    #[TopP(0.9)]
    class SalesCoach implements Agent
    {
        use Promptable;
    }
