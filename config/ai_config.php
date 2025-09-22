<?php
return [
    'providers' => [
        'primary' => [
            'type' => 'claude',
            'api_key' => getenv('CLAUDE_API_KEY') ?: '',
            'endpoint' => getenv('CLAUDE_API_ENDPOINT') ?: 'https://api.anthropic.com/v1/messages',
            'model' => getenv('CLAUDE_MODEL') ?: 'claude-3-sonnet-20240229',
            'max_tokens' => getenv('CLAUDE_MAX_TOKENS') ?: 2000,
            'temperature' => getenv('CLAUDE_TEMPERATURE') ?: 0.6,
        ],
        'fallback' => [
            'type' => 'qwen',
            'api_key' => getenv('QWEN_API_KEY') ?: '',
            'endpoint' => getenv('QWEN_API_ENDPOINT') ?: 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text-generation/generation',
            'model' => getenv('QWEN_MODEL') ?: 'qwen-plus',
            'max_tokens' => getenv('QWEN_MAX_TOKENS') ?: 1800,
            'temperature' => getenv('QWEN_TEMPERATURE') ?: 0.7,
        ],
        'mock' => [
            'type' => 'mock',
            'model' => 'mock-strategy-writer',
        ],
    ],
    'encryption' => [
        'method' => 'AES-256-CBC',
        'key' => getenv('ENCRYPTION_KEY') ?: '',
    ],
];
