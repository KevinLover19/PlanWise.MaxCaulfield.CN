<?php
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../AI_Service_Enhanced.php';

class TaskProcessor
{
    private PDO $db;
    private AI_Service_Enhanced $aiService;
    private bool $running = true;
    private string $reportsTable;

    public function __construct()
    {
        $this->db = planwise_pdo();
        $this->aiService = new AI_Service_Enhanced();
        $this->reportsTable = planwise_get_reports_table($this->db);
    }

    public function run(): void
    {
        if (function_exists('pcntl_signal')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGTERM, fn() => $this->running = false);
            pcntl_signal(SIGINT, fn() => $this->running = false);
        }

        while ($this->running) {
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }

            $task = $this->getNextTask();
            if ($task) {
                $this->processTask($task);
            } else {
                usleep(500000); // 0.5s idle sleep
            }
        }
    }

    private function getNextTask(): ?array
    {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("SELECT * FROM planwise_task_queue WHERE status = 'pending' ORDER BY priority DESC, created_at ASC LIMIT 1 FOR UPDATE SKIP LOCKED");
            $stmt->execute();
            $task = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($task) {
                $update = $this->db->prepare("UPDATE planwise_task_queue SET status = 'processing', started_at = NOW() WHERE id = ?");
                $update->execute([$task['id']]);
                $this->db->commit();
                return $task;
            }

            $this->db->commit();
            return null;
        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log('[PlanWise][worker] Failed to fetch task: ' . $e->getMessage());
            return null;
        }
    }

    private function processTask(array $task): void
    {
        $payload = json_decode($task['payload'] ?? '{}', true) ?: [];
        try {
            switch ($task['task_type']) {
                case 'analyze_business_idea':
                default:
                    $result = $this->analyzeBusinessIdea($task, $payload);
                    $this->completeTask($task, $result);
                    break;
            }
        } catch (Throwable $e) {
            $this->failTask($task, $e);
        }
    }

    private function analyzeBusinessIdea(array $task, array $payload): array
    {
        $steps = [
            ['name' => 'market_analysis', 'title' => '市场环境分析', 'prompt' => '请从市场规模、增长趋势、用户需求和政策环境分析该项目。'],
            ['name' => 'competitor_research', 'title' => '竞争对手研究', 'prompt' => '识别主要竞争对手，比较产品/服务特点、市场份额、定价策略与优势劣势。'],
            ['name' => 'user_persona', 'title' => '目标用户画像', 'prompt' => '构建核心用户画像，描述人口属性、行为特征、痛点需求及典型使用场景。'],
            ['name' => 'business_model', 'title' => '商业模式设计', 'prompt' => '说明价值主张、收入来源、成本结构、关键合作伙伴及扩张策略。'],
            ['name' => 'risk_assessment', 'title' => '风险评估分析', 'prompt' => '识别市场、运营、财务、合规等风险，并给出缓释策略。'],
            ['name' => 'financial_forecast', 'title' => '财务预测建模', 'prompt' => '给出3年期的收入、成本、现金流预估以及盈亏平衡分析。'],
            ['name' => 'marketing_strategy', 'title' => '营销策略制定', 'prompt' => '制定品牌定位、渠道策略、推广活动和关键指标。'],
            ['name' => 'implementation_plan', 'title' => '实施计划规划', 'prompt' => '规划阶段性里程碑、资源配置、团队结构与时间表。'],
        ];

        $reportId = $task['report_id'];
        $context = [
            'business_name' => $payload['business_name'] ?? '',
            'business_idea' => $payload['business_idea'] ?? '',
            'industry' => $payload['industry'] ?? '',
            'target_market' => $payload['target_market'] ?? '',
            'analysis_depth' => $payload['analysis_depth'] ?? 'standard',
            'focus_areas' => $payload['focus_areas'] ?? [],
        ];

        $results = [];
        $stepCount = count($steps);

        foreach ($steps as $index => $step) {
            $this->markStepProcessing($task, $step);
            $this->updateTaskProgress($task['task_id'], $index + 1, $stepCount, '正在处理：' . $step['title']);

            try {
                $prompt = $this->buildStepPrompt($step, $context, $results);
                $start = microtime(true);
                $response = $this->aiService->callWithRetry($prompt, [
                    'system_prompt' => $this->buildSystemPrompt($context, $step['title']),
                    'temperature' => $context['analysis_depth'] === 'deep' ? 0.6 : 0.8,
                ]);
                $duration = (int) ((microtime(true) - $start) * 1000);
                $provider = $this->aiService->getLastProvider();
                $modelName = $provider['model'] ?? ($provider['type'] ?? null);

                $results[$step['name']] = $response;
                $this->saveStepResult($task, $step, $response, $duration, $modelName);
            } catch (Throwable $e) {
                $this->markStepFailed($task, $step, $e->getMessage());
                throw $e;
            }

            usleep(300000); // 0.3s pacing
        }

        $this->updateTaskProgress($task['task_id'], $stepCount, $stepCount, 'AI 已完成所有分析阶段');

        return $results;
    }

    private function buildSystemPrompt(array $context, string $stepTitle): string
    {
        $depth = $context['analysis_depth'] ?? 'standard';
        $focus = $context['focus_areas'] ?? [];
        $focusText = $focus ? '重点关注领域：' . implode('、', $focus) . '。' : '';

        return sprintf(
            '你是一位资深商业策略顾问，请基于提供的商业想法，完成章节《%s》的专业分析。分析深度：%s。%s答案必须结构化、包含可执行建议，并使用正式中文撰写。',
            $stepTitle,
            $depth,
            $focusText
        );
    }

    private function buildStepPrompt(array $step, array $context, array $previousResults): string
    {
        $history = '';
        foreach ($previousResults as $name => $content) {
            $history .= sprintf("\n【%s】\n%s\n", $this->translateStepName($name), $content);
        }

        return sprintf(
            "项目名称：%s\n行业：%s\n目标市场：%s\n商业想法描述：%s\n\n已有分析：%s\n请围绕《%s》给出详细分析：%s",
            $context['business_name'] ?: '未命名项目',
            $context['industry'] ?: '未指定',
            $context['target_market'] ?: '未指定',
            $context['business_idea'],
            $history ?: '暂无',
            $step['title'],
            $step['prompt']
        );
    }

    private function updateTaskProgress(string $taskId, int $currentStep, int $totalSteps, string $message): void
    {
        $stmt = $this->db->prepare("UPDATE planwise_task_queue SET payload = JSON_SET(COALESCE(payload, JSON_OBJECT()), '$.current_step', ?, '$.total_steps', ?, '$.current_message', ?) WHERE task_id = ?");
        $stmt->execute([$currentStep, $totalSteps, $message, $taskId]);
    }

    private function markStepProcessing(array $task, array $step): void
    {
        $stmt = $this->db->prepare("UPDATE planwise_report_steps SET status = 'processing', task_id = ?, started_at = COALESCE(started_at, NOW()), error_message = NULL WHERE step_id = ?");
        $stmt->execute([
            $task['task_id'],
            $this->buildStepId($task['report_id'], $step['name']),
        ]);

        if ($stmt->rowCount() === 0) {
            $insert = $this->db->prepare("INSERT INTO planwise_report_steps (step_id, report_id, step_number, step_name, step_title, task_id, status, started_at, created_at) VALUES (?, ?, ?, ?, ?, ?, 'processing', NOW(), NOW())");
            $insert->execute([
                $this->buildStepId($task['report_id'], $step['name']),
                $task['report_id'],
                $this->stepNumberFromName($step['name']),
                $step['name'],
                $step['title'],
                $task['task_id'],
            ]);
        }
    }

    private function markStepFailed(array $task, array $step, string $error): void
    {
        $stmt = $this->db->prepare("UPDATE planwise_report_steps SET status = 'failed', task_id = ?, error_message = ?, completed_at = NOW() WHERE step_id = ?");
        $stmt->execute([
            $task['task_id'],
            mb_substr($error, 0, 1000),
            $this->buildStepId($task['report_id'], $step['name']),
        ]);

        if ($stmt->rowCount() === 0) {
            $insert = $this->db->prepare("INSERT INTO planwise_report_steps (step_id, report_id, step_number, step_name, step_title, task_id, status, error_message, completed_at, created_at) VALUES (?, ?, ?, ?, ?, ?, 'failed', ?, NOW(), NOW())");
            $insert->execute([
                $this->buildStepId($task['report_id'], $step['name']),
                $task['report_id'],
                $this->stepNumberFromName($step['name']),
                $step['name'],
                $step['title'],
                $task['task_id'],
                mb_substr($error, 0, 1000),
            ]);
        }
    }

    private function saveStepResult(array $task, array $step, string $content, int $duration, ?string $model = null): void
    {
        $stepId = $this->buildStepId($task['report_id'], $step['name']);
        $wordCount = mb_strlen(strip_tags($content));

        $update = $this->db->prepare("UPDATE planwise_report_steps SET status = 'completed', formatted_content = ?, word_count = ?, processing_time = ?, completed_at = NOW(), task_id = ?, ai_model = ?, error_message = NULL WHERE step_id = ?");
        $update->execute([
            $content,
            $wordCount,
            $duration,
            $task['task_id'],
            $model,
            $stepId,
        ]);

        if ($update->rowCount() === 0) {
            $insert = $this->db->prepare("INSERT INTO planwise_report_steps (step_id, report_id, step_number, step_name, step_title, task_id, status, formatted_content, word_count, processing_time, ai_model, completed_at, created_at) VALUES (?, ?, ?, ?, ?, ?, 'completed', ?, ?, ?, ?, NOW(), NOW())");
            $insert->execute([
                $stepId,
                $task['report_id'],
                $this->stepNumberFromName($step['name']),
                $step['name'],
                $step['title'],
                $task['task_id'],
                $content,
                $wordCount,
                $duration,
                $model,
            ]);
        }
    }

    private function completeTask(array $task, array $results): void
    {
        $summary = $this->generateSummary($results);
        $sections = $results;

        $this->db->prepare("UPDATE planwise_task_queue SET status = 'completed', completed_at = NOW(), result = ? WHERE id = ?")
            ->execute([json_encode(['executive_summary' => $summary, 'sections' => $sections], JSON_UNESCAPED_UNICODE), $task['id']]);

        $totalWords = array_sum(array_map(fn($text) => mb_strlen(strip_tags($text)), $sections));
        $updateReport = $this->db->prepare("UPDATE {$this->reportsTable} SET status = 'completed', total_words = ?, ai_tokens_used = ?, completed_at = NOW(), updated_at = NOW(), last_error = NULL WHERE report_id = ?");
        $updateReport->execute([$totalWords, 0, $task['report_id']]);
    }

    private function failTask(array $task, Throwable $exception): void
    {
        $this->db->prepare("UPDATE planwise_task_queue SET status = 'failed', completed_at = NOW(), error_message = ?, retry_count = retry_count + 1 WHERE id = ?")
            ->execute([$exception->getMessage(), $task['id']]);

        $this->db->prepare("UPDATE planwise_task_queue SET payload = JSON_SET(COALESCE(payload, JSON_OBJECT()), '$.current_message', ?) WHERE id = ?")
            ->execute([mb_substr($exception->getMessage(), 0, 255), $task['id']]);

        $this->db->prepare("UPDATE {$this->reportsTable} SET status = 'failed', last_error = ? WHERE report_id = ?")
            ->execute([$exception->getMessage(), $task['report_id']]);

        error_log('[PlanWise][worker] Task failed: ' . $exception->getMessage());
    }

    private function buildStepId(string $reportId, string $stepName): string
    {
        return 'step_' . $reportId . '_' . $stepName;
    }

    private function stepNumberFromName(string $name): int
    {
        $map = [
            'market_analysis' => 1,
            'competitor_research' => 2,
            'user_persona' => 3,
            'business_model' => 4,
            'risk_assessment' => 5,
            'financial_forecast' => 6,
            'marketing_strategy' => 7,
            'implementation_plan' => 8,
        ];
        return $map[$name] ?? 0;
    }

    private function translateStepName(string $name): string
    {
        $map = [
            'market_analysis' => '市场环境分析',
            'competitor_research' => '竞争对手研究',
            'user_persona' => '目标用户画像',
            'business_model' => '商业模式设计',
            'risk_assessment' => '风险评估分析',
            'financial_forecast' => '财务预测建模',
            'marketing_strategy' => '营销策略制定',
            'implementation_plan' => '实施计划规划',
        ];
        return $map[$name] ?? $name;
    }

    private function generateSummary(array $results): string
    {
        $highlights = [];
        foreach ($results as $key => $content) {
            $snippet = mb_substr(trim(strip_tags($content)), 0, 80);
            $highlights[] = sprintf('%s：%s...', $this->translateStepName($key), $snippet);
        }

        return "本报告从市场、用户、模式、风险、财务与实施六大维度进行了系统化分析：\n- " . implode("\n- ", $highlights);
    }
}

if (php_sapi_name() === 'cli') {
    $processor = new TaskProcessor();
    $processor->run();
}
