<?php
/**
 * Advanced AI service wrapper for PlanWise asynchronous pipeline.
 * Provides resilient multi-provider orchestration with retry & fallback.
 */
class AI_Service_Enhanced
{
    private array $providers = [];
    private int $maxRetries = 3;
    private int $baseDelay = 1000; // milliseconds
    private int $maxDelay = 32000; // milliseconds


    public function __construct(?array $providers = null)
    {
        $config = $this->loadConfig();
        $configured = $providers ?? ($config['providers'] ?? []);

        // Normalise providers into sequential list to preserve priority order
        foreach ($configured as $key => $provider) {
            if (!is_array($provider)) {
                continue;
            }
            $provider['id'] = is_string($key) ? $key : ($provider['id'] ?? $key);
            $this->providers[] = $provider;
        }

        if (empty($this->providers)) {
            // Always include a mock provider as ultimate fallback
            $this->providers[] = [
                'id' => 'mock',
                'type' => 'mock',
                'model' => 'mock-strategy-writer',
            ];
        }
    }

    /**
     * Execute an AI call with automatic retry & provider fallback.
     *
     * @param string $prompt          User prompt / instruction
     * @param array  $options         Additional options: system_prompt, messages, context, temperature, max_tokens
     *
     * @throws Exception when every provider failed
     */
    public function callWithRetry(string $prompt, array $options = []): string
    {
        $lastError = null;

        foreach ($this->providers as $provider) {

            $attempt = 0;
            $providerId = $provider['id'] ?? ($provider['type'] ?? 'unknown');

            while ($attempt < $this->maxRetries) {
                try {
                    if ($attempt > 0) {
                        $delay = min(
                            (int) ($this->baseDelay * pow(2, $attempt - 1) + rand(0, 1000)),
                            $this->maxDelay
                        );
                        usleep($delay * 1000);
                        $this->logRetry($providerId, $attempt, $delay);
                    }

                    $response = $this->executeApiCall($provider, $prompt, $options);
                    if ($response !== '') {
                        $this->logSuccess($providerId, $attempt);
                        return $response;
                    }

                } catch (Throwable $e) {
                    $lastError = $e;

                    if (!$this->handleSpecificError($e, $attempt)) {
                        // break retry loop -> switch provider
                        break;
                    }
                }

                $attempt++;
            }
        }

        throw new Exception(
            'All AI providers failed. Last error: ' . ($lastError ? $lastError->getMessage() : 'Unknown error')
        );
    }

    /**
     * Handle retry conditions based on error type & response code.
     */
    private function handleSpecificError(Throwable $exception, int $attempt): bool
    {
        $code = (int) ($exception->getCode() ?: 0);
        $message = $exception->getMessage();

        // HTTP 429 -> respect Retry-After header when available
        if ($code === 429 && method_exists($exception, 'getResponseHeaders')) {
            $retryAfter = $this->extractRetryAfter($exception);
            if ($retryAfter > 0) {
                sleep($retryAfter);
                return true;
            }
        }

        // Temporary service unavailable -> retry until limit
        if (in_array($code, [502, 503, 504], true)) {
            return $attempt < $this->maxRetries - 1;
        }

        // Network timeouts -> limited retries
        if (stripos($message, 'timeout') !== false) {
            return $attempt < 2;
        }

        // Client errors (4xx other than 429) should not retry
        if ($code >= 400 && $code < 500 && $code !== 429) {
            return false;
        }

        return $attempt < $this->maxRetries - 1;
    }

    private function executeApiCall(array $provider, string $prompt, array $options): string
    {
        $type = strtolower($provider['type'] ?? 'mock');

        if ($type === 'mock') {

        }

        if (empty($provider['api_key'])) {
            throw new Exception(strtoupper($type) . ' API key missing for provider ' . ($provider['id'] ?? 'unknown'));
        }

        $headers = $this->buildHeaders($provider);
        $body = $this->buildRequestBody($provider, $prompt, $options);


        $responseHeaders = [];
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $provider['endpoint'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HEADERFUNCTION => function ($ch, $header) use (&$responseHeaders) {
                $len = strlen($header);
                $parts = explode(':', $header, 2);
                if (count($parts) === 2) {
                    $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
                return $len;
            },
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            throw new Exception('CURL Error: ' . $error, $httpCode ?: 0);
        }

        if ($httpCode >= 400) {
            $exception = new class('HTTP Error: ' . $httpCode . ' - ' . $response, $httpCode) extends Exception {
                public array $responseHeaders = [];

                public function setResponseHeaders(array $headers): void
                {
                    $this->responseHeaders = $headers;
                }

                public function getResponseHeaders(): array
                {
                    return $this->responseHeaders;
                }
            };
            $exception->setResponseHeaders($responseHeaders);
            throw $exception;
        }


    }

    private function buildHeaders(array $provider): array
    {
        $type = strtolower($provider['type'] ?? 'mock');
        $headers = ['Content-Type: application/json'];

        switch ($type) {
            case 'claude':
                $headers[] = 'x-api-key: ' . $provider['api_key'];
                $headers[] = 'anthropic-version: 2023-06-01';
                if (!empty($provider['anthropic_beta'])) {
                    $headers[] = 'anthropic-beta: ' . $provider['anthropic_beta'];
                }
                break;
            case 'qwen':
                $headers[] = 'Authorization: Bearer ' . $provider['api_key'];
                break;
            default:
                $headers[] = 'Authorization: Bearer ' . $provider['api_key'];
        }

        return $headers;
    }

    private function buildRequestBody(array $provider, string $prompt, array $options): array
    {
        $type = strtolower($provider['type'] ?? 'mock');
        $systemPrompt = $options['system_prompt'] ?? 'You are an expert business strategy analyst.';
        $messages = $options['messages'] ?? [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $prompt],
        ];
        $temperature = $options['temperature'] ?? ($provider['temperature'] ?? 0.7);
        $maxTokens = $options['max_tokens'] ?? ($provider['max_tokens'] ?? 2048);

        switch ($type) {
            case 'claude':
                return [
                    'model' => $provider['model'] ?? 'claude-3-sonnet-20240229',
                    'max_tokens' => (int) $maxTokens,
                    'temperature' => (float) $temperature,
                    'messages' => array_map(function ($message) {
                        if (is_string($message['content'])) {
                            $message['content'] = [
                                ['type' => 'text', 'text' => $message['content']],
                            ];
                        }
                        return $message;
                    }, $messages),
                ];
            case 'qwen':
                return [
                    'model' => $provider['model'] ?? 'qwen-plus',
                    'input' => [
                        'messages' => $messages,
                    ],
                    'parameters' => [
                        'temperature' => (float) $temperature,
                        'max_tokens' => (int) $maxTokens,
                    ],
                ];
            default:
                return [
                    'model' => $provider['model'] ?? 'gpt-4o-mini',
                    'messages' => $messages,
                    'max_tokens' => (int) $maxTokens,
                    'temperature' => (float) $temperature,
                ];
        }
    }

    private function parseResponse(array $provider, string $response): string
    {
        $type = strtolower($provider['type'] ?? 'mock');
        $data = json_decode($response, true);
        if (!is_array($data)) {
            throw new Exception('Invalid JSON response from provider: ' . ($provider['id'] ?? 'unknown'));
        }

        switch ($type) {
            case 'claude':
                if (!empty($data['content'][0]['text'])) {
                    return (string) $data['content'][0]['text'];
                }
                break;
            case 'qwen':
                if (!empty($data['output']['text'])) {
                    return (string) $data['output']['text'];
                }
                if (!empty($data['output']['choices'][0]['text'])) {
                    return (string) $data['output']['choices'][0]['text'];
                }
                break;
            default:
                if (!empty($data['choices'][0]['message']['content'])) {
                    return (string) $data['choices'][0]['message']['content'];
                }
        }

        throw new Exception('Unable to parse AI response for provider ' . ($provider['id'] ?? 'unknown'));
    }

    private function extractRetryAfter(Throwable $exception): int
    {
        if (!method_exists($exception, 'getResponseHeaders')) {
            return 0;
        }

        $headers = $exception->getResponseHeaders();
        if (empty($headers['retry-after'])) {
            return 0;
        }

        $value = trim($headers['retry-after']);
        if (is_numeric($value)) {
            return (int) $value;
        }

        $timestamp = strtotime($value);
        if ($timestamp) {
            $diff = $timestamp - time();
            return $diff > 0 ? $diff : 0;
        }

        return 0;
    }

    private function logRetry(string $providerId, int $attempt, int $delay): void
    {
        error_log(sprintf('[AI_Service_Enhanced] Provider %s retry #%d after %dms', $providerId, $attempt, $delay));
    }

    private function logSuccess(string $providerId, int $attempt): void
    {
        error_log(sprintf('[AI_Service_Enhanced] Provider %s succeeded after %d attempt(s)', $providerId, $attempt + 1));
    }

    private function loadConfig(): array
    {
        $path = __DIR__ . '/config/ai_config.php';
        if (file_exists($path)) {
            $config = require $path;
            if (is_array($config)) {
                return $config;
            }
        }
        return [];
    }


    {
        $hash = substr(md5($prompt), 0, 6);
        $system = $options['system_prompt'] ?? '资深商业策略顾问';


        return <<<TEXT
# 模拟商业策略分析（{$hash}）
基于系统指令「{$system}」，以下为针对该步骤的示例分析内容：
- 重点洞察：结合行业趋势与用户需求，识别可执行的机会窗口。
- 策略建议：从市场进入、差异化定位与商业模式优化三个角度提供行动路径。
- 风险提示：评估资源、竞争与合规风险，附带缓释建议。

（该内容为开发环境下的占位响应，用于在未配置真实模型密钥时保持功能可用。）
TEXT;
    }

}
