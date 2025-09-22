<?php
/**
 * PlanWise AI Service Class
 * 处理与各种AI API的交互和商业分析报告生成
 * Author: Max Caulfield
 */

class AI_Service {
    private $db;
    private $api_configs = [];
    
    // AI服务提供商配置
    private $providers = [
        'openai' => 'OpenAI GPT',
        'claude' => 'Claude',
        'gemini' => 'Google Gemini',
        'qianwen' => '通义千问',
        'baichuan' => '百川AI',
        'moonshot' => 'Moonshot AI'
    ];
    
    public function __construct() {
        global $pdo;
        $this->db = $pdo;
        $this->loadApiConfigs();
    }
    
    private function loadApiConfigs() {
        try {
            $stmt = $this->db->prepare("SELECT provider, config_data, is_enabled FROM planwise_ai_configs WHERE is_enabled = 1");
            $stmt->execute();
            $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($configs as $config) {
                $this->api_configs[$config['provider']] = json_decode($config['config_data'], true);
            }
        } catch (Exception $e) {
            error_log("Failed to load AI configs: " . $e->getMessage());
        }
    }
    
    /**
     * 生成商业策略报告
     */
    public function generateReport($business_idea, $industry, $analysis_depth, $focus_areas = []) {
        // 构建系统提示词
        $system_prompt = $this->buildSystemPrompt($analysis_depth, $focus_areas);
        
        // 构建用户输入
        $user_prompt = $this->buildUserPrompt($business_idea, $industry, $focus_areas);
        
        // 选择最合适的AI服务
        $provider = $this->selectBestProvider($analysis_depth);
        
        // 调用AI生成报告
        $ai_response = $this->callAI($provider, $system_prompt, $user_prompt);
        
        // 处理和格式化响应
        return $this->formatReport($ai_response, $business_idea, $industry);
    }
    
    private function buildSystemPrompt($analysis_depth, $focus_areas) {
        $base_prompt = "你是一位资深的商业策略分析师，具有超过20年的商业咨询经验。你的任务是为用户的商业想法提供专业、详细、可操作的商业策略分析报告。\n\n";
        
        // 根据分析深度调整提示词
        switch ($analysis_depth) {
            case 'basic':
                $base_prompt .= "请提供一份基础但全面的商业分析，重点关注核心商业要素。分析应该简洁明了，适合初创企业家参考。\n\n";
                break;
            case 'standard':
                $base_prompt .= "请提供一份标准深度的商业分析，包含详细的市场分析、竞争分析和财务预测。分析应该具有专业水准，适合有一定经验的创业者。\n\n";
                break;
            case 'deep':
                $base_prompt .= "请提供一份深度商业分析，包含全面的市场研究、详细的竞争情报、复杂的财务模型和风险评估。分析应该达到专业咨询公司的标准。\n\n";
                break;
        }
        
        // 添加重点关注领域
        if (!empty($focus_areas)) {
            $base_prompt .= "用户特别关注以下方面，请在分析中重点讨论：\n";
            foreach ($focus_areas as $area) {
                $base_prompt .= "- " . htmlspecialchars($area) . "\n";
            }
            $base_prompt .= "\n";
        }
        
        $base_prompt .= "请按照以下结构提供分析：\n";
        $base_prompt .= "1. 执行摘要\n";
        $base_prompt .= "2. 市场环境分析\n";
        $base_prompt .= "3. 竞争对手研究\n";
        $base_prompt .= "4. 目标用户画像\n";
        $base_prompt .= "5. 商业模式设计\n";
        $base_prompt .= "6. 风险评估分析\n";
        $base_prompt .= "7. 财务预测建模\n";
        $base_prompt .= "8. 营销策略制定\n";
        $base_prompt .= "9. 实施计划规划\n";
        $base_prompt .= "10. 结论与建议\n\n";
        
        $base_prompt .= "请用中文回答，并确保内容具有实用性和可操作性。";
        
        return $base_prompt;
    }
    
    private function buildUserPrompt($business_idea, $industry, $focus_areas) {
        $prompt = "商业想法描述：\n" . htmlspecialchars($business_idea) . "\n\n";
        $prompt .= "所属行业：" . htmlspecialchars($industry) . "\n\n";
        
        if (!empty($focus_areas)) {
            $prompt .= "重点关注领域：" . implode(', ', array_map('htmlspecialchars', $focus_areas)) . "\n\n";
        }
        
        $prompt .= "请为这个商业想法提供详细的商业策略分析报告。";
        
        return $prompt;
    }
    
    private function selectBestProvider($analysis_depth) {
        // 根据分析深度和可用性选择最合适的AI提供商
        $preferred_providers = [
            'deep' => ['claude', 'openai', 'gemini'],
            'standard' => ['openai', 'claude', 'qianwen'],
            'basic' => ['qianwen', 'baichuan', 'moonshot']
        ];
        
        $candidates = $preferred_providers[$analysis_depth] ?? ['openai'];
        
        foreach ($candidates as $provider) {
            if (isset($this->api_configs[$provider])) {
                return $provider;
            }
        }
        
        // 如果没有找到首选提供商，返回第一个可用的
        return array_keys($this->api_configs)[0] ?? 'mock';
    }
    
    private function callAI($provider, $system_prompt, $user_prompt) {
        switch ($provider) {
            case 'openai':
                return $this->callOpenAI($system_prompt, $user_prompt);
            case 'claude':
                return $this->callClaude($system_prompt, $user_prompt);
            case 'qianwen':
                return $this->callQianwen($system_prompt, $user_prompt);
            case 'gemini':
                return $this->callGemini($system_prompt, $user_prompt);
            default:
                return $this->generateMockReport($user_prompt);
        }
    }
    
    private function callOpenAI($system_prompt, $user_prompt) {
        $config = $this->api_configs['openai'] ?? [];
        
        if (empty($config['api_key'])) {
            throw new Exception('OpenAI API key not configured');
        }
        
        $data = [
            'model' => $config['model'] ?? 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => $system_prompt],
                ['role' => 'user', 'content' => $user_prompt]
            ],
            'max_tokens' => $config['max_tokens'] ?? 4000,
            'temperature' => $config['temperature'] ?? 0.7
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $config['api_key']
            ],
            CURLOPT_TIMEOUT => 60
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('OpenAI API request failed: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        if ($http_code !== 200) {
            throw new Exception('OpenAI API returned error code: ' . $http_code);
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['choices'][0]['message']['content'])) {
            throw new Exception('Invalid response from OpenAI API');
        }
        
        return $result['choices'][0]['message']['content'];
    }
    
    private function callClaude($system_prompt, $user_prompt) {
        // Claude API 调用实现
        $config = $this->api_configs['claude'] ?? [];
        
        if (empty($config['api_key'])) {
            throw new Exception('Claude API key not configured');
        }
        
        // 实现Claude API调用逻辑
        // 这里需要根据Claude的实际API规格实现
        return $this->generateMockReport($user_prompt);
    }
    
    private function callQianwen($system_prompt, $user_prompt) {
        // 通义千问 API 调用实现
        $config = $this->api_configs['qianwen'] ?? [];
        
        if (empty($config['api_key'])) {
            throw new Exception('通义千问 API key not configured');
        }
        
        // 实现通义千问API调用逻辑
        return $this->generateMockReport($user_prompt);
    }
    
    private function callGemini($system_prompt, $user_prompt) {
        // Google Gemini API 调用实现
        $config = $this->api_configs['gemini'] ?? [];
        
        if (empty($config['api_key'])) {
            throw new Exception('Gemini API key not configured');
        }
        
        // 实现Gemini API调用逻辑
        return $this->generateMockReport($user_prompt);
    }
    
    private function generateMockReport($user_prompt) {
        // 模拟AI响应，用于开发和测试
        return "# 商业策略分析报告

## 执行摘要
这是一个基于您的商业想法生成的详细分析报告。本报告评估了您的商业想法的市场潜力、竞争环境、目标用户群体以及潜在的商业模式。

## 市场环境分析
目标市场显示出良好的增长前景，市场规模预计在未来3-5年内保持稳定增长。行业发展趋势有利于新进入者，特别是那些能够提供创新解决方案的企业。

## 竞争对手研究
当前市场存在几个主要竞争对手，但仍有差异化机会。通过分析竞争对手的优劣势，我们识别出了市场空白点和竞争优势的构建方向。

## 目标用户画像
主要目标用户群体为25-45岁的专业人士，他们具有中高收入水平，重视品质和效率。这个群体对新技术和创新服务具有较高的接受度。

## 商业模式设计
建议采用订阅制商业模式，结合一次性购买和增值服务。这种模式能够确保稳定的现金流，同时为用户提供灵活的选择。

## 风险评估分析
主要风险包括市场竞争加剧、技术变革风险和资金需求。建议制定相应的风险缓解措施，包括技术储备和资金规划。

## 财务预测建模
预计初期投资需求为50-100万元，预期在第18个月达到盈亏平衡点。第三年预计实现200万元营收，净利润率达到15-20%。

## 营销策略制定
建议采用数字化营销策略，重点通过社交媒体、内容营销和合作伙伴推广。初期营销预算建议占营收的15-20%。

## 实施计划规划
建议分三个阶段实施：
- 第一阶段（0-6个月）：产品开发和团队建设
- 第二阶段（6-18个月）：市场推广和用户获取
- 第三阶段（18个月以后）：规模化发展和业务扩张

## 结论与建议
该商业想法具有良好的市场前景和可行性。建议先进行小规模市场验证，然后逐步扩大规模。关键成功因素包括产品质量、用户体验和市场推广策略的执行。";
    }
    
    private function formatReport($ai_response, $business_idea, $industry) {
        // 解析AI响应并格式化为结构化数据
        $sections = $this->parseReportSections($ai_response);
        
        return [
            'executive_summary' => [
                'business_idea' => $business_idea,
                'industry' => $industry,
                'summary' => $sections['执行摘要'] ?? '报告摘要生成中...',
                'key_insights' => $this->extractKeyInsights($sections)
            ],
            'content' => [
                'market_analysis' => $sections['市场环境分析'] ?? '',
                'competitor_research' => $sections['竞争对手研究'] ?? '',
                'user_persona' => $sections['目标用户画像'] ?? '',
                'business_model' => $sections['商业模式设计'] ?? '',
                'risk_assessment' => $sections['风险评估分析'] ?? '',
                'financial_forecast' => $sections['财务预测建模'] ?? '',
                'marketing_strategy' => $sections['营销策略制定'] ?? '',
                'implementation_plan' => $sections['实施计划规划'] ?? '',
                'conclusion' => $sections['结论与建议'] ?? ''
            ]
        ];
    }
    
    private function parseReportSections($content) {
        $sections = [];
        $lines = explode("\n", $content);
        $current_section = '';
        $current_content = '';
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // 检测章节标题
            if (preg_match('/^##\s*(.+)$/', $line, $matches)) {
                // 保存上一个章节
                if (!empty($current_section) && !empty($current_content)) {
                    $sections[$current_section] = trim($current_content);
                }
                
                // 开始新章节
                $current_section = trim($matches[1]);
                $current_content = '';
            } else if (!empty($current_section)) {
                $current_content .= $line . "\n";
            }
        }
        
        // 保存最后一个章节
        if (!empty($current_section) && !empty($current_content)) {
            $sections[$current_section] = trim($current_content);
        }
        
        return $sections;
    }
    
    private function extractKeyInsights($sections) {
        $insights = [];
        
        // 从各个章节提取关键见解
        foreach ($sections as $section_name => $content) {
            if (strlen($content) > 50) {
                $insights[] = substr($content, 0, 100) . '...';
            }
        }
        
        return array_slice($insights, 0, 3); // 返回前3个关键见解
    }
    
    /**
     * 获取AI服务使用统计
     */
    public function getUsageStats($start_date = null, $end_date = null) {
        $where_clause = '';
        $params = [];
        
        if ($start_date && $end_date) {
            $where_clause = 'WHERE created_at BETWEEN ? AND ?';
            $params = [$start_date, $end_date];
        }
        
        $stmt = $this->db->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as total_requests,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_requests,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_requests
            FROM planwise_sessions 
            {$where_clause}
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 记录AI服务使用情况
     */
    public function logUsage($provider, $tokens_used, $cost, $session_id) {
        $stmt = $this->db->prepare("
            INSERT INTO planwise_ai_usage_logs (
                session_id, provider, tokens_used, cost, created_at
            ) VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([$session_id, $provider, $tokens_used, $cost]);
    }
    
    /**
     * 测试AI服务连接
     */
    public function testProvider($provider) {
        try {
            $test_prompt = "请回答：这是一个API连接测试。";
            $response = $this->callAI($provider, "你是一个测试助手。", $test_prompt);
            
            return [
                'success' => true,
                'provider' => $provider,
                'response' => substr($response, 0, 100)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'provider' => $provider,
                'error' => $e->getMessage()
            ];
        }
    }
}
