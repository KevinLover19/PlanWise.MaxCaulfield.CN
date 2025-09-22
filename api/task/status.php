<?php
require_once __DIR__ . '/../../db_connect.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$taskId = $_GET['task_id'] ?? '';
if (!$taskId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'task_id required']);
    exit;
}

try {
    $pdo = planwise_pdo();
    $stmt = $pdo->prepare("SELECT * FROM planwise_task_queue WHERE task_id = ? LIMIT 1");
    $stmt->execute([$taskId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Task not found']);
        exit;
    }

    $userId = get_current_user_id();
    $sessionTasks = $_SESSION['planwise_tasks'] ?? [];
    if (!empty($task['user_id']) && $task['user_id'] !== $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }
    if (empty($task['user_id']) && !isset($sessionTasks[$taskId])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }

    $payload = json_decode($task['payload'] ?? '[]', true) ?: [];
    $currentStep = (int) ($payload['current_step'] ?? 0);
    $totalSteps = (int) ($payload['total_steps'] ?? 8);
    $currentMessage = (string) ($payload['current_message'] ?? '正在排队...');

    $reportData = null;
    $steps = [];
    $reportId = $task['report_id'] ?? null;
    if ($reportId) {
        $stepsStmt = $pdo->prepare("SELECT step_id, step_number, step_name, step_title, status, formatted_content, ai_model, completed_at
            FROM planwise_report_steps WHERE report_id = ? ORDER BY step_number ASC");
        $stepsStmt->execute([$reportId]);
        $steps = $stepsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $reportTable = planwise_get_reports_table($pdo);
        $reportStmt = $pdo->prepare("SELECT * FROM {$reportTable} WHERE report_id = ? LIMIT 1");
        $reportStmt->execute([$reportId]);
        $reportData = $reportStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    $partial = [];
    foreach ($steps as $step) {
        if (!empty($step['formatted_content'])) {
            $partial[] = [
                'step_id' => $step['step_id'],
                'step_number' => (int) $step['step_number'],
                'step_name' => $step['step_name'],
                'step_title' => $step['step_title'],
                'status' => $step['status'],
                'content' => $step['formatted_content'],
                'completed_at' => $step['completed_at'],
            ];
        }
    }

    $result = $task['result'] ? json_decode($task['result'], true) : null;

    $response = [
        'success' => true,
        'task' => [
            'task_id' => $task['task_id'],
            'status' => $task['status'],
            'current_step' => $currentStep,
            'total_steps' => $totalSteps,
            'current_message' => $currentMessage,
            'created_at' => $task['created_at'],
            'started_at' => $task['started_at'],
            'completed_at' => $task['completed_at'],
        ],
        'steps' => array_map(function ($step) {
            return [
                'step_id' => $step['step_id'],
                'step_number' => (int) $step['step_number'],
                'step_name' => $step['step_name'],
                'step_title' => $step['step_title'],
                'status' => $step['status'],
                'formatted_content' => $step['formatted_content'],
                'ai_model' => $step['ai_model'],
                'completed_at' => $step['completed_at'],
            ];
        }, $steps),
        'partial_result' => $partial,
        'result' => $result,
    ];

    if ($reportData) {
        $response['report'] = [
            'report_id' => $reportData['report_id'] ?? null,
            'title' => $reportData['title'] ?? '',
            'business_idea' => $reportData['business_idea'] ?? '',
            'industry' => $reportData['industry'] ?? '',
            'analysis_depth' => $reportData['analysis_depth'] ?? '',
            'status' => $reportData['status'] ?? '',
            'total_words' => (int) ($reportData['total_words'] ?? 0),
            'ai_tokens_used' => (int) ($reportData['ai_tokens_used'] ?? 0),
            'completed_at' => $reportData['completed_at'] ?? null,
            'visibility' => $reportData['visibility'] ?? 'private',
            'analysis_preferences' => $reportData['analysis_preferences']
                ? json_decode($reportData['analysis_preferences'], true)
                : null,
        ];
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('[PlanWise][task/status] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => '服务器内部错误'], JSON_UNESCAPED_UNICODE);
}
