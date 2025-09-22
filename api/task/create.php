<?php
require_once __DIR__ . '/../../db_connect.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$csrfToken = $payload['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
if (!csrf_validate($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'CSRF token validation failed']);
    exit;
}

try {
    $pdo = planwise_pdo();

    $userId = get_current_user_id();
    $taskType = $payload['type'] ?? 'analyze_business_idea';
    $data = $payload['data'] ?? [];

    $businessIdea = trim((string) ($data['business_idea'] ?? $data['business_description'] ?? ''));
    if (mb_strlen($businessIdea) < 20) {
        throw new InvalidArgumentException('请提供至少20字的商业想法描述');
    }

    $businessName = trim((string) ($data['business_name'] ?? 'PlanWise 商业策略分析报告'));
    $industry = trim((string) ($data['industry'] ?? ''));
    $targetMarket = trim((string) ($data['target_market'] ?? ''));
    $analysisDepth = in_array($data['analysis_depth'] ?? 'standard', ['basic', 'standard', 'deep'], true)
        ? $data['analysis_depth']
        : 'standard';
    $focusArea = $data['focus_area'] ?? ($data['focus_areas'] ?? []);
    if (!is_array($focusArea)) {
        $focusArea = [$focusArea];
    }

    $reportTable = planwise_get_reports_table($pdo);
    $reportId = 'rep_' . bin2hex(random_bytes(8));
    $taskId = $payload['task_id'] ?? ('task_' . bin2hex(random_bytes(8)));

    $analysisPreferences = [
        'focus_areas' => array_values(array_filter($focusArea)),
        'requested_at' => date('c'),
        'source' => 'planwise_web',
    ];

    $stmt = $pdo->prepare("INSERT INTO {$reportTable} (
        report_id, user_id, title, business_idea, industry, target_market,
        analysis_depth, status, visibility, analysis_preferences, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'analyzing', 'private', ?, NOW())");

    $stmt->execute([
        $reportId,
        $userId,
        $businessName,
        $businessIdea,
        $industry,
        $targetMarket,
        $analysisDepth,
        json_encode($analysisPreferences, JSON_UNESCAPED_UNICODE),
    ]);


    $taskPayload = [
        'report_id' => $reportId,
        'business_name' => $businessName,
        'business_idea' => $businessIdea,
        'industry' => $industry,
        'target_market' => $targetMarket,
        'analysis_depth' => $analysisDepth,
        'focus_areas' => $analysisPreferences['focus_areas'],
        'current_step' => 0,

        'current_message' => '任务已提交，等待分配处理器',
    ];

    $queueStmt = $pdo->prepare("INSERT INTO planwise_task_queue (
        task_id, user_id, report_id, task_type, status, priority, payload, created_at
    ) VALUES (?, ?, ?, ?, 'pending', ?, ?, NOW())");

    $queueStmt->execute([
        $taskId,
        $userId,
        $reportId,
        $taskType,
        (int) ($payload['priority'] ?? 5),
        json_encode($taskPayload, JSON_UNESCAPED_UNICODE),
    ]);


    echo json_encode([
        'success' => true,
        'task_id' => $taskId,
        'report_id' => $reportId,
        'steps' => $steps,
        'next_csrf' => csrf_token(),
        'message' => '任务创建成功，正在排队等待处理'
    ], JSON_UNESCAPED_UNICODE);

} catch (InvalidArgumentException $e) {

    error_log('[PlanWise][task/create] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => '服务器内部错误'], JSON_UNESCAPED_UNICODE);
}
