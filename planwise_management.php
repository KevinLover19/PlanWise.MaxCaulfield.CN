<?php
/**
 * PlanWise AI 商业策略智能体管理面板 - 无游客版本
 * Author: Max Caulfield
 * Created: 2024-09-21
 * Updated: 2024-09-21 (移除游客功能，增加公告系统，优化UI布局)
 */

require_once __DIR__ . '/../db.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 检查管理员是否已登录
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = getPDO();
$message = '';
$message_type = '';

// CSRF保护
if (!function_exists('generate_csrf_token')) {
    die('CSRF 防护函数缺失，请检查 db.php 文件。');
}
$csrf_token = generate_csrf_token();

// 处理各种操作
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // 先检查CSRF token是否存在和匹配，但暂不销毁
    $csrf_valid = isset($_POST['csrf_token']) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
    
    if (!$csrf_valid) {
        $message = "安全验证失败，操作已被取消。";
        $message_type = 'error';
        // 重新生成token供下次使用
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $csrf_token = $_SESSION['csrf_token'];
    } else {
        try {
            switch ($_POST['action']) {
                case 'update_ai_config':
                    updateAIConfig();
                    break;
                case 'update_system_config':
                    updateSystemConfig();
                    break;
                case 'update_user_quota':
                    updateUserQuota();
                    break;
                case 'test_ai_provider':
                    testAIProvider();
                    break;
                case 'toggle_ai_provider':
                    toggleAIProvider();
                    break;
                case 'batch_update_quotas':
                    batchUpdateQuotas();
                    break;
                case 'create_announcement':
                    createAnnouncement();
                    break;
                case 'update_announcement':
                    updateAnnouncement();
                    break;
                case 'delete_announcement':
                    deleteAnnouncement();
                    break;
                case 'toggle_announcement':
                    toggleAnnouncement();
                    break;
                default:
                    throw new Exception('未知操作');
            }
            // 操作成功，销毁CSRF token
            unset($_SESSION['csrf_token']);
            // 重新生成新的token
            $csrf_token = generate_csrf_token();
        } catch (Exception $e) {
            $message = $e->getMessage();
            $message_type = 'error';
        }
    }
}

// 获取统计数据
$stats = getSystemStats();
$ai_configs = getAIConfigs();
$system_configs = getSystemConfigs();
$recent_sessions = getRecentSessions();
$usage_stats = getUsageStats();
$user_quotas = getUserQuotas();
$announcements = getAnnouncements();

// 公告管理函数
function createAnnouncement() {
    global $pdo, $message, $message_type;
    
    $title = trim($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    $type = $_POST['announcement_type'] ?? 'info';
    $show_popup = isset($_POST['show_popup']) ? 1 : 0;
    $priority = (int)($_POST['priority'] ?? 0);
    $target_audience = $_POST['target_audience'] ?? 'all';
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    
    if (empty($title) || empty($content)) {
        throw new Exception('标题和内容不能为空');
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO planwise_announcements 
        (title, content, announcement_type, show_popup, priority, end_date, target_audience, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $title, $content, $type, $show_popup, $priority, $end_date, $target_audience, $_SESSION['admin_id']
    ]);
    
    if ($result) {
        $message = "公告创建成功";
        $message_type = 'success';
        logAdminAction('create_announcement', 'announcement', $pdo->lastInsertId(), [
            'title' => $title,
            'type' => $type
        ]);
    } else {
        throw new Exception('公告创建失败');
    }
}

function updateAnnouncement() {
    global $pdo, $message, $message_type;
    
    $id = (int)($_POST['announcement_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    $type = $_POST['announcement_type'] ?? 'info';
    $show_popup = isset($_POST['show_popup']) ? 1 : 0;
    $priority = (int)($_POST['priority'] ?? 0);
    $target_audience = $_POST['target_audience'] ?? 'all';
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    
    if ($id <= 0 || empty($title) || empty($content)) {
        throw new Exception('无效的公告ID或内容不能为空');
    }
    
    $stmt = $pdo->prepare("
        UPDATE planwise_announcements 
        SET title = ?, content = ?, announcement_type = ?, show_popup = ?, 
            priority = ?, end_date = ?, target_audience = ?, updated_at = NOW()
        WHERE id = ?
    ");
    
    $result = $stmt->execute([
        $title, $content, $type, $show_popup, $priority, $end_date, $target_audience, $id
    ]);
    
    if ($result) {
        $message = "公告更新成功";
        $message_type = 'success';
        logAdminAction('update_announcement', 'announcement', $id, ['title' => $title]);
    } else {
        throw new Exception('公告更新失败');
    }
}

function deleteAnnouncement() {
    global $pdo, $message, $message_type;
    
    $id = (int)($_POST['announcement_id'] ?? 0);
    
    if ($id <= 0) {
        throw new Exception('无效的公告ID');
    }
    
    $stmt = $pdo->prepare("DELETE FROM planwise_announcements WHERE id = ?");
    $result = $stmt->execute([$id]);
    
    if ($result) {
        $message = "公告删除成功";
        $message_type = 'success';
        logAdminAction('delete_announcement', 'announcement', $id, []);
    } else {
        throw new Exception('公告删除失败');
    }
}

function toggleAnnouncement() {
    global $pdo, $message, $message_type;
    
    $id = (int)($_POST['announcement_id'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if ($id <= 0) {
        throw new Exception('无效的公告ID');
    }
    
    $stmt = $pdo->prepare("UPDATE planwise_announcements SET is_active = ? WHERE id = ?");
    $result = $stmt->execute([$is_active, $id]);
    
    if ($result) {
        $status = $is_active ? '启用' : '禁用';
        $message = "公告已{$status}";
        $message_type = 'success';
        logAdminAction('toggle_announcement', 'announcement', $id, ['is_active' => $is_active]);
    } else {
        throw new Exception('操作失败');
    }
}

function getAnnouncements() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("
            SELECT a.*, u.username as creator_name,
                   (SELECT COUNT(*) FROM planwise_announcement_views WHERE announcement_id = a.id) as view_count
            FROM planwise_announcements a
            LEFT JOIN users u ON a.created_by = u.id
            ORDER BY a.priority DESC, a.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("获取公告列表失败: " . $e->getMessage());
        return [];
    }
}

// 其他现有函数（移除游客相关代码）
function updateAIConfig() {
    global $pdo, $message, $message_type;
    
    $provider = $_POST['provider'] ?? '';
    $config_data = [
        'api_key' => $_POST['api_key'] ?? '',
        'model' => $_POST['model'] ?? '',
        'max_tokens' => (int)($_POST['max_tokens'] ?? 4000),
        'temperature' => (float)($_POST['temperature'] ?? 0.7)
    ];
    
    if (empty($provider)) {
        throw new Exception('AI提供商不能为空');
    }
    
    $stmt = $pdo->prepare("
        UPDATE planwise_ai_configs 
        SET config_data = ?, updated_at = NOW()
        WHERE provider = ?
    ");
    
    $result = $stmt->execute([json_encode($config_data), $provider]);
    
    if ($result) {
        $message = "AI配置已更新: " . $provider;
        $message_type = 'success';
        logAdminAction('update_ai_config', 'ai_provider', $provider, ['config_data' => $config_data]);
    } else {
        throw new Exception('配置更新失败');
    }
}

function updateSystemConfig() {
    global $pdo, $message, $message_type;
    
    // 移除游客相关配置
    $configs = [
        'free_user_monthly_quota' => (int)($_POST['free_user_monthly_quota'] ?? 10),
        'basic_user_monthly_quota' => (int)($_POST['basic_user_monthly_quota'] ?? 50),
        'premium_user_monthly_quota' => (int)($_POST['premium_user_monthly_quota'] ?? 200),
        'enterprise_user_monthly_quota' => (int)($_POST['enterprise_user_monthly_quota'] ?? 1000),
        'max_business_idea_length' => (int)($_POST['max_business_idea_length'] ?? 2000),
        'analysis_timeout_seconds' => (int)($_POST['analysis_timeout_seconds'] ?? 300)
    ];
    
    foreach ($configs as $key => $value) {
        $stmt = $pdo->prepare("
            UPDATE planwise_config 
            SET config_value = ?, updated_at = NOW()
            WHERE config_key = ?
        ");
        $stmt->execute([$value, $key]);
    }
    
    $message = "系统配置已更新";
    $message_type = 'success';
    logAdminAction('update_system_config', 'config', null, $configs);
}

function updateUserQuota() {
    global $pdo, $message, $message_type;
    
    $user_id = (int)($_POST['user_id'] ?? 0);
    $remaining_quota = (int)($_POST['remaining_quota'] ?? 0);
    $total_quota = (int)($_POST['total_quota'] ?? 0);
    $membership_type = $_POST['membership_type'] ?? 'free';
    
    if ($user_id <= 0) {
        throw new Exception('无效的用户ID');
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO planwise_user_quotas (user_id, remaining_quota, total_quota, membership_type, last_reset)
        VALUES (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
            remaining_quota = VALUES(remaining_quota),
            total_quota = VALUES(total_quota),
            membership_type = VALUES(membership_type),
            updated_at = NOW()
    ");
    
    $result = $stmt->execute([$user_id, $remaining_quota, $total_quota, $membership_type]);
    
    if ($result) {
        $message = "用户配额已更新 (用户ID: $user_id)";
        $message_type = 'success';
        logAdminAction('update_user_quota', 'user', $user_id, [
            'remaining_quota' => $remaining_quota,
            'total_quota' => $total_quota,
            'membership_type' => $membership_type
        ]);
    } else {
        throw new Exception('配额更新失败');
    }
}

function batchUpdateQuotas() {
    global $pdo, $message, $message_type;
    
    $membership_type = $_POST['batch_membership_type'] ?? '';
    $action_type = $_POST['batch_action'] ?? '';
    
    if (empty($membership_type) || empty($action_type)) {
        throw new Exception('请选择会员类型和操作类型');
    }
    
    $affected_rows = 0;
    
    switch ($action_type) {
        case 'reset_quota':
            $stmt = $pdo->prepare("
                UPDATE planwise_user_quotas 
                SET remaining_quota = total_quota, last_reset = NOW()
                WHERE membership_type = ?
            ");
            $stmt->execute([$membership_type]);
            $affected_rows = $stmt->rowCount();
            break;
            
        case 'double_quota':
            $stmt = $pdo->prepare("
                UPDATE planwise_user_quotas 
                SET total_quota = total_quota * 2, remaining_quota = remaining_quota * 2
                WHERE membership_type = ?
            ");
            $stmt->execute([$membership_type]);
            $affected_rows = $stmt->rowCount();
            break;
    }
    
    $message = "批量操作完成，影响 {$affected_rows} 个用户";
    $message_type = 'success';
    logAdminAction('batch_update_quotas', 'quota', $membership_type, [
        'action' => $action_type,
        'affected_rows' => $affected_rows
    ]);
}

function testAIProvider() {
    global $message, $message_type;
    
    $provider = $_POST['provider'] ?? '';
    
    if (empty($provider)) {
        throw new Exception('请选择AI提供商');
    }
    
    require_once '/www/wwwroot/planwise.maxcaulfield.cn/AI_Service.php';
    $ai_service = new AI_Service();
    $test_result = $ai_service->testProvider($provider);
    
    if ($test_result['success']) {
        $message = "AI提供商 {$provider} 连接测试成功";
        $message_type = 'success';
    } else {
        $message = "AI提供商 {$provider} 连接测试失败: " . $test_result['error'];
        $message_type = 'error';
    }
}

function toggleAIProvider() {
    global $pdo, $message, $message_type;
    
    $provider = $_POST['provider'] ?? '';
    $is_enabled = isset($_POST['is_enabled']) ? 1 : 0;
    
    if (empty($provider)) {
        throw new Exception('请选择AI提供商');
    }
    
    $stmt = $pdo->prepare("
        UPDATE planwise_ai_configs 
        SET is_enabled = ?, updated_at = NOW()
        WHERE provider = ?
    ");
    
    $result = $stmt->execute([$is_enabled, $provider]);
    
    if ($result) {
        $status = $is_enabled ? '启用' : '禁用';
        $message = "AI提供商 {$provider} 已{$status}";
        $message_type = 'success';
        logAdminAction('toggle_ai_provider', 'ai_provider', $provider, ['is_enabled' => $is_enabled]);
    } else {
        throw new Exception('操作失败');
    }
}

function getSystemStats() {
    global $pdo;
    
    $stats = [];
    
    try {
        // 移除游客相关统计
        $stmt = $pdo->query("SELECT COUNT(*) FROM planwise_sessions WHERE user_id IS NOT NULL");
        $stats['total_reports'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM planwise_sessions WHERE DATE(created_at) = CURDATE() AND user_id IS NOT NULL");
        $stats['today_reports'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM planwise_sessions WHERE status = 'processing' AND user_id IS NOT NULL");
        $stats['processing_reports'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM planwise_user_quotas");
        $stats['total_users'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM planwise_ai_configs WHERE is_enabled = 1");
        $stats['enabled_ai_providers'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM planwise_announcements WHERE is_active = 1");
        $stats['active_announcements'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT SUM(cost) FROM planwise_ai_usage_logs WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())");
        $stats['monthly_cost'] = $stmt->fetchColumn() ?: 0;
        
    } catch (Exception $e) {
        error_log("获取统计数据失败: " . $e->getMessage());
    }
    
    return $stats;
}

function getAIConfigs() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("
            SELECT provider, provider_name, config_data, is_enabled, priority_order, updated_at 
            FROM planwise_ai_configs 
            ORDER BY priority_order ASC, provider ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("获取AI配置失败: " . $e->getMessage());
        return [];
    }
}

function getSystemConfigs() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT config_key, config_value, config_type, description FROM planwise_config ORDER BY config_key");
        $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $result = [];
        foreach ($configs as $config) {
            $result[$config['config_key']] = $config;
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("获取系统配置失败: " . $e->getMessage());
        return [];
    }
}

function getRecentSessions() {
    global $pdo;
    
    try {
        // 只显示注册用户的会话
        $stmt = $pdo->query("
            SELECT s.session_id, s.user_id, s.business_idea, s.industry, s.analysis_depth, 
                   s.status, s.created_at, s.completed_at, u.username
            FROM planwise_sessions s 
            LEFT JOIN users u ON s.user_id = u.id
            WHERE s.user_id IS NOT NULL
            ORDER BY s.created_at DESC 
            LIMIT 20
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("获取最近会话失败: " . $e->getMessage());
        return [];
    }
}

function getUsageStats() {
    global $pdo;
    
    try {
        // 只统计注册用户
        $stmt = $pdo->query("
            SELECT DATE(created_at) as date, 
                   COUNT(*) as total_requests,
                   COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_requests,
                   COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_requests,
                   COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_requests
            FROM planwise_sessions 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND user_id IS NOT NULL
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("获取使用统计失败: " . $e->getMessage());
        return [];
    }
}

function getUserQuotas() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("
            SELECT 
                u.id as user_id,
                u.username,
                u.email,
                u.registration_date,
                COALESCE(q.remaining_quota, 0) as remaining_quota,
                COALESCE(q.total_quota, 0) as total_quota,
                COALESCE(q.membership_type, 'free') as membership_type,
                q.last_reset,
                q.last_used,
                (SELECT COUNT(*) FROM planwise_sessions WHERE user_id = u.id) as total_sessions,
                (SELECT COUNT(*) FROM planwise_sessions WHERE user_id = u.id AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as sessions_30days
            FROM users u
            LEFT JOIN planwise_user_quotas q ON u.id = q.user_id
            WHERE u.id > 0
            ORDER BY q.membership_type DESC, u.registration_date DESC
            LIMIT 100
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("获取用户配额失败: " . $e->getMessage());
        return [];
    }
}

function logAdminAction($action, $target_type, $target_id, $details) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO planwise_admin_logs (admin_user_id, action, target_type, target_id, details, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION['admin_id'],
            $action,
            $target_type,
            $target_id,
            json_encode($details),
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (Exception $e) {
        error_log("记录管理员操作失败: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PlanWise AI 管理面板 - MaxCaulfield Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js"></script>
    <style>
        .admin-header { background-color: #2d3748; color: white; padding: 1.5rem 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .admin-nav a { color: #cbd5e1; margin-right: 1.5rem; text-decoration: none; transition: color 0.2s ease; }
        .admin-nav a.active, .admin-nav a:hover { color: white; font-weight: bold; }
        .stats-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stats-card.green { background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%); }
        .stats-card.yellow { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); }
        .stats-card.red { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
        .stats-card.purple { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }
        .stats-card.orange { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        .stats-card.teal { background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); }
        .toggle-switch { position: relative; display: inline-block; width: 60px; height: 34px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: 0.4s; border-radius: 34px; }
        .toggle-slider:before { position: absolute; content: ""; height: 26px; width: 26px; left: 4px; bottom: 4px; background-color: white; transition: 0.4s; border-radius: 50%; }
        input:checked + .toggle-slider { background-color: #4ade80; }
        input:checked + .toggle-slider:before { transform: translateX(26px); }
        .section-divider { border-top: 3px solid #e5e7eb; margin: 4rem 0; }
        .membership-badge { display: inline-block; padding: 0.5rem 1rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 600; text-transform: uppercase; }
        .membership-free { background-color: #f3f4f6; color: #374151; }
        .membership-basic { background-color: #dbeafe; color: #1e40af; }
        .membership-premium { background-color: #fef3c7; color: #92400e; }
        .membership-enterprise { background-color: #e0e7ff; color: #5b21b6; }
        .announcement-badge { display: inline-block; padding: 0.375rem 1rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 500; }
        .announcement-info { background-color: #dbeafe; color: #1e40af; }
        .announcement-warning { background-color: #fef3c7; color: #92400e; }
        .announcement-success { background-color: #dcfce7; color: #166534; }
        .announcement-error { background-color: #fee2e2; color: #dc2626; }
        
        /* 优化UI间距 */
        .section-card { margin-bottom: 2.5rem; }
        .form-section { margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        .input-spacing { margin-bottom: 1rem; }
        .button-spacing { margin-right: 1rem; margin-bottom: 1rem; }
        .table-spacing { margin-top: 1.5rem; }
        .modal-spacing { padding: 2rem; }
    </style>
</head>
<body class="min-h-screen bg-gray-50">
    <!-- 管理员导航头 -->
    <div class="admin-header">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-brain text-3xl mr-4"></i>
                <h1 class="text-2xl font-bold">PlanWise AI 管理面板</h1>
            </div>
            <div class="admin-nav">
                <a href="dashboard.php"><i class="fas fa-home"></i> 返回主面板</a>
                <a href="../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> 查看网站</a>
                <a href="https://planwise.maxcaulfield.cn" target="_blank"><i class="fas fa-brain"></i> PlanWise</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> 退出</a>
            </div>
        </div>
    </div>

    <main class="max-w-7xl mx-auto py-8 px-6">
        <!-- 消息提示 -->
        <?php if ($message): ?>
            <div class="mb-8 p-6 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
                <div class="flex items-center text-lg">
                    <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-3"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- 统计数据卡片 (移除游客相关统计) -->
        <div class="section-card">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6 mb-12">
                <div class="stats-card p-6 rounded-xl text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-90 mb-2">总报告数</p>
                            <p class="text-3xl font-bold"><?php echo number_format($stats['total_reports'] ?? 0); ?></p>
                        </div>
                        <i class="fas fa-file-alt text-3xl opacity-70"></i>
                    </div>
                </div>

                <div class="stats-card green p-6 rounded-xl text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-90 mb-2">今日报告</p>
                            <p class="text-3xl font-bold"><?php echo number_format($stats['today_reports'] ?? 0); ?></p>
                        </div>
                        <i class="fas fa-calendar-day text-3xl opacity-70"></i>
                    </div>
                </div>

                <div class="stats-card yellow p-6 rounded-xl text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-90 mb-2">处理中</p>
                            <p class="text-3xl font-bold"><?php echo number_format($stats['processing_reports'] ?? 0); ?></p>
                        </div>
                        <i class="fas fa-hourglass-half text-3xl opacity-70"></i>
                    </div>
                </div>

                <div class="stats-card purple p-6 rounded-xl text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-90 mb-2">注册用户</p>
                            <p class="text-3xl font-bold"><?php echo number_format($stats['total_users'] ?? 0); ?></p>
                        </div>
                        <i class="fas fa-users text-3xl opacity-70"></i>
                    </div>
                </div>

                <div class="stats-card orange p-6 rounded-xl text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-90 mb-2">AI服务</p>
                            <p class="text-3xl font-bold"><?php echo number_format($stats['enabled_ai_providers'] ?? 0); ?></p>
                        </div>
                        <i class="fas fa-robot text-3xl opacity-70"></i>
                    </div>
                </div>

                <div class="stats-card teal p-6 rounded-xl text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-90 mb-2">活跃公告</p>
                            <p class="text-3xl font-bold"><?php echo number_format($stats['active_announcements'] ?? 0); ?></p>
                        </div>
                        <i class="fas fa-bullhorn text-3xl opacity-70"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 公告管理系统 -->
        <div class="section-card">
            <div class="mb-10 flex items-center justify-between">
                <div>
                    <h2 class="text-4xl font-bold text-gray-900 mb-3">公告管理</h2>
                    <p class="text-lg text-gray-600">创建和管理系统公告，支持弹窗显示和目标用户群体</p>
                </div>
                <button onclick="openAnnouncementModal()" class="bg-blue-500 hover:bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold shadow-lg transition-colors">
                    <i class="fas fa-plus mr-3"></i>新建公告
                </button>
            </div>

            <!-- 公告列表 -->
            <div class="bg-white shadow-xl rounded-xl overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-xl font-semibold text-gray-800">公告列表</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-8 py-4 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">标题</th>
                                <th class="px-8 py-4 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">类型</th>
                                <th class="px-8 py-4 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">目标群体</th>
                                <th class="px-8 py-4 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">优先级</th>
                                <th class="px-8 py-4 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">状态</th>
                                <th class="px-8 py-4 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">查看次数</th>
                                <th class="px-8 py-4 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">操作</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($announcements as $announcement): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-8 py-6">
                                    <div>
                                        <div class="text-base font-semibold text-gray-900 mb-1">
                                            <?php echo htmlspecialchars($announcement['title']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            创建于 <?php echo date('Y-m-d H:i', strtotime($announcement['created_at'])); ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6 whitespace-nowrap">
                                    <span class="announcement-badge announcement-<?php echo $announcement['announcement_type']; ?>">
                                        <?php 
                                        echo match($announcement['announcement_type']) {
                                            'info' => '信息',
                                            'warning' => '警告',
                                            'success' => '成功',
                                            'error' => '错误',
                                            default => '未知'
                                        };
                                        ?>
                                    </span>
                                </td>
                                <td class="px-8 py-6 whitespace-nowrap text-sm text-gray-900">
                                    <?php 
                                    echo match($announcement['target_audience']) {
                                        'all' => '所有用户',
                                        'free' => '免费用户',
                                        'basic' => '基础会员',
                                        'premium' => '高级会员',
                                        'enterprise' => '企业会员',
                                        default => '未知'
                                    };
                                    ?>
                                </td>
                                <td class="px-8 py-6 whitespace-nowrap text-base font-semibold text-gray-900">
                                    <?php echo $announcement['priority']; ?>
                                </td>
                                <td class="px-8 py-6 whitespace-nowrap">
                                    <form method="post" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <input type="hidden" name="action" value="toggle_announcement">
                                        <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="is_active" <?php echo $announcement['is_active'] ? 'checked' : ''; ?> onchange="this.form.submit()">
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </form>
                                </td>
                                <td class="px-8 py-6 whitespace-nowrap text-base font-semibold text-gray-900">
                                    <?php echo number_format($announcement['view_count']); ?>
                                </td>
                                <td class="px-8 py-6 whitespace-nowrap text-base font-medium">
                                    <button onclick="editAnnouncement(<?php echo htmlspecialchars(json_encode($announcement)); ?>)" 
                                            class="text-blue-600 hover:text-blue-900 mr-4">
                                        <i class="fas fa-edit mr-1"></i> 编辑
                                    </button>
                                    <form method="post" class="inline" onsubmit="return confirm('确定要删除这个公告吗？')">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <input type="hidden" name="action" value="delete_announcement">
                                        <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash mr-1"></i> 删除
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="section-divider"></div>

        <!-- AI服务配置管理 -->
        <div class="section-card">
            <div class="mb-10">
                <h2 class="text-4xl font-bold text-gray-900 mb-3">AI服务配置</h2>
                <p class="text-lg text-gray-600">管理各种AI提供商的配置和状态</p>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                <?php foreach ($ai_configs as $config): ?>
                    <?php $config_data = json_decode($config['config_data'], true); ?>
                    <div class="bg-white shadow-xl rounded-xl overflow-hidden">
                        <div class="px-8 py-6 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                            <h3 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($config['provider_name']); ?></h3>
                            <div class="flex items-center space-x-4">
                                <form method="post" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="toggle_ai_provider">
                                    <input type="hidden" name="provider" value="<?php echo htmlspecialchars($config['provider']); ?>">
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="is_enabled" <?php echo $config['is_enabled'] ? 'checked' : ''; ?> onchange="this.form.submit()">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </form>
                                <span class="text-base text-gray-600"><?php echo $config['is_enabled'] ? '启用' : '禁用'; ?></span>
                            </div>
                        </div>
                        
                        <form method="post" class="p-8 space-y-6">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <input type="hidden" name="action" value="update_ai_config">
                            <input type="hidden" name="provider" value="<?php echo htmlspecialchars($config['provider']); ?>">
                            
                            <div class="form-group">
                                <label class="block text-base font-semibold text-gray-700 mb-3">API密钥</label>
                                <input type="password" name="api_key" value="<?php echo htmlspecialchars($config_data['api_key'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-3 focus:ring-blue-500 text-base"
                                       placeholder="输入API密钥">
                            </div>
                            
                            <div class="form-group">
                                <label class="block text-base font-semibold text-gray-700 mb-3">模型名称</label>
                                <input type="text" name="model" value="<?php echo htmlspecialchars($config_data['model'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-3 focus:ring-blue-500 text-base"
                                       placeholder="例如: gpt-4">
                            </div>
                            
                            <div class="grid grid-cols-2 gap-6">
                                <div class="form-group">
                                    <label class="block text-base font-semibold text-gray-700 mb-3">最大令牌</label>
                                    <input type="number" name="max_tokens" value="<?php echo $config_data['max_tokens'] ?? 4000; ?>" min="100" max="32000"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-3 focus:ring-blue-500 text-base">
                                </div>
                                
                                <div class="form-group">
                                    <label class="block text-base font-semibold text-gray-700 mb-3">温度值</label>
                                    <input type="number" name="temperature" value="<?php echo $config_data['temperature'] ?? 0.7; ?>" min="0" max="2" step="0.1"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-3 focus:ring-blue-500 text-base">
                                </div>
                            </div>
                            
                            <div class="flex space-x-4 pt-4">
                                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                                    <i class="fas fa-save mr-2"></i>保存配置
                                </button>
                                
                                <button type="button" onclick="testProvider('<?php echo htmlspecialchars($config['provider']); ?>')" 
                                        class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                                    <i class="fas fa-vial mr-2"></i>测试连接
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="section-divider"></div>

        <!-- 系统配置管理 -->
        <div class="section-card">
            <div class="mb-10">
                <h2 class="text-4xl font-bold text-gray-900 mb-3">系统配置</h2>
                <p class="text-lg text-gray-600">配置系统参数和各会员等级的配额限制</p>
            </div>
            
            <div class="bg-white shadow-xl rounded-xl overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-xl font-semibold text-gray-800">配额管理</h3>
                </div>
                
                <form method="post" class="p-8">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="update_system_config">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-8">
                        <div class="form-group">
                            <label class="block text-base font-semibold text-gray-700 mb-3">免费用户月配额</label>
                            <input type="number" name="free_user_monthly_quota" 
                                   value="<?php echo $system_configs['free_user_monthly_quota']['config_value'] ?? 10; ?>" 
                                   min="0" max="1000"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-3 focus:ring-blue-500 text-base">
                        </div>
                        
                        <div class="form-group">
                            <label class="block text-base font-semibold text-gray-700 mb-3">基础会员月配额</label>
                            <input type="number" name="basic_user_monthly_quota" 
                                   value="<?php echo $system_configs['basic_user_monthly_quota']['config_value'] ?? 50; ?>" 
                                   min="0" max="1000"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-3 focus:ring-blue-500 text-base">
                        </div>
                        
                        <div class="form-group">
                            <label class="block text-base font-semibold text-gray-700 mb-3">高级会员月配额</label>
                            <input type="number" name="premium_user_monthly_quota" 
                                   value="<?php echo $system_configs['premium_user_monthly_quota']['config_value'] ?? 200; ?>" 
                                   min="0" max="5000"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-3 focus:ring-blue-500 text-base">
                        </div>
                        
                        <div class="form-group">
                            <label class="block text-base font-semibold text-gray-700 mb-3">企业会员月配额</label>
                            <input type="number" name="enterprise_user_monthly_quota" 
                                   value="<?php echo $system_configs['enterprise_user_monthly_quota']['config_value'] ?? 1000; ?>" 
                                   min="0" max="10000"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-3 focus:ring-blue-500 text-base">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                        <div class="form-group">
                            <label class="block text-base font-semibold text-gray-700 mb-3">最大商业想法长度 (字符)</label>
                            <input type="number" name="max_business_idea_length" 
                                   value="<?php echo $system_configs['max_business_idea_length']['config_value'] ?? 2000; ?>" 
                                   min="100" max="10000"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-3 focus:ring-blue-500 text-base">
                        </div>
                        
                        <div class="form-group">
                            <label class="block text-base font-semibold text-gray-700 mb-3">分析超时时间 (秒)</label>
                            <input type="number" name="analysis_timeout_seconds" 
                                   value="<?php echo $system_configs['analysis_timeout_seconds']['config_value'] ?? 300; ?>" 
                                   min="60" max="1800"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-3 focus:ring-blue-500 text-base">
                        </div>
                    </div>
                    
                    <div>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold transition-colors">
                            <i class="fas fa-save mr-2"></i>保存系统配置
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="section-divider"></div>

        <!-- 用户管理 (显示会员分类组、总配额和剩余配额) -->
        <div class="section-card">
            <div class="mb-10 flex items-center justify-between">
                <div>
                    <h2 class="text-4xl font-bold text-gray-900 mb-3">用户管理</h2>
                    <p class="text-lg text-gray-600">管理用户配额，按会员分类组显示总配额和剩余配额</p>
                </div>
                <div class="flex space-x-4">
                    <select id="membershipFilter" onchange="filterUsers()" 
                            class="px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-3 focus:ring-blue-500 text-base">
                        <option value="all">所有会员</option>
                        <option value="free">免费用户</option>
                        <option value="basic">基础会员</option>
                        <option value="premium">高级会员</option>
                        <option value="enterprise">企业会员</option>
                    </select>
                </div>
            </div>
            
            <!-- 批量操作 -->
            <div class="bg-white shadow-lg rounded-xl p-8 mb-8">
                <h3 class="text-xl font-semibold text-gray-800 mb-6">批量操作</h3>
                <form method="post" class="flex items-end space-x-6">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="batch_update_quotas">
                    
                    <div class="form-group">
                        <label class="block text-base font-semibold text-gray-700 mb-3">会员类型</label>
                        <select name="batch_membership_type" required
                                class="px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-3 focus:ring-blue-500 text-base">
                            <option value="">请选择</option>
                            <option value="free">免费用户</option>
                            <option value="basic">基础会员</option>
                            <option value="premium">高级会员</option>
                            <option value="enterprise">企业会员</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="block text-base font-semibold text-gray-700 mb-3">操作类型</label>
                        <select name="batch_action" required
                                class="px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-3 focus:ring-blue-500 text-base">
                            <option value="">请选择</option>
                            <option value="reset_quota">重置配额</option>
                            <option value="double_quota">配额翻倍</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-3 rounded-lg font-semibold transition-colors"
                            onclick="return confirm('确定要执行批量操作吗？')">
                        <i class="fas fa-magic mr-2"></i>执行批量操作
                    </button>
                </form>
            </div>

            <!-- 用户列表 -->
            <div class="bg-white shadow-xl rounded-xl overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-xl font-semibold text-gray-800">用户配额详情</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-8 py-4 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">用户信息</th>
                                <th class="px-8 py-4 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">会员分类</th>
                                <th class="px-8 py-4 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">总配额</th>
                                <th class="px-8 py-4 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">剩余配额</th>
                                <th class="px-8 py-4 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">使用统计</th>
                                <th class="px-8 py-4 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">最后使用</th>
                                <th class="px-8 py-4 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">操作</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="usersTableBody">
                            <?php foreach ($user_quotas as $user): ?>
                            <tr class="user-row hover:bg-gray-50" data-membership="<?php echo $user['membership_type']; ?>">
                                <td class="px-8 py-6">
                                    <div>
                                        <div class="text-base font-semibold text-gray-900 mb-1">
                                            <?php echo htmlspecialchars($user['username']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500 mb-1">
                                            ID: <?php echo $user['user_id']; ?> | <?php echo htmlspecialchars($user['email']); ?>
                                        </div>
                                        <div class="text-xs text-gray-400">
                                            注册: <?php echo date('Y-m-d', strtotime($user['registration_date'])); ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6 whitespace-nowrap">
                                    <span class="membership-badge membership-<?php echo $user['membership_type']; ?>">
                                        <?php 
                                        echo match($user['membership_type']) {
                                            'free' => '免费用户',
                                            'basic' => '基础会员',
                                            'premium' => '高级会员',
                                            'enterprise' => '企业会员',
                                            default => '未知'
                                        };
                                        ?>
                                    </span>
                                </td>
                                <td class="px-8 py-6 whitespace-nowrap">
                                    <div class="text-base font-bold text-gray-900"><?php echo number_format($user['total_quota']); ?></div>
                                    <div class="text-sm text-gray-500">总配额</div>
                                </td>
                                <td class="px-8 py-6 whitespace-nowrap">
                                    <div class="text-base font-bold <?php echo $user['remaining_quota'] > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo number_format($user['remaining_quota']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500 mb-2">剩余配额</div>
                                    <?php if ($user['total_quota'] > 0): ?>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                                                 style="width: <?php echo min(100, ($user['remaining_quota'] / $user['total_quota']) * 100); ?>%"></div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-8 py-6 whitespace-nowrap">
                                    <div class="text-base text-gray-900">总会话: <?php echo number_format($user['total_sessions']); ?></div>
                                    <div class="text-sm text-gray-500">30天: <?php echo number_format($user['sessions_30days']); ?></div>
                                </td>
                                <td class="px-8 py-6 whitespace-nowrap text-base text-gray-500">
                                    <?php echo $user['last_used'] ? date('Y-m-d H:i', strtotime($user['last_used'])) : '从未使用'; ?>
                                </td>
                                <td class="px-8 py-6 whitespace-nowrap text-base font-medium">
                                    <button onclick="editUserQuota(<?php echo htmlspecialchars(json_encode($user)); ?>)" 
                                            class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-edit mr-1"></i> 编辑
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>

    <!-- 公告编辑模态框 -->
    <div id="announcementModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full">
                <form id="announcementForm" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" id="announcementAction" value="create_announcement">
                    <input type="hidden" name="announcement_id" id="announcementId">
                    
                    <div class="bg-white px-8 pt-8 pb-6">
                        <div class="mb-6">
                            <h3 class="text-2xl leading-6 font-bold text-gray-900" id="modalTitle">新建公告</h3>
                        </div>
                        
                        <div class="grid grid-cols-1 gap-8">
                            <div class="form-group">
                                <label class="block text-base font-semibold text-gray-700 mb-3">公告标题</label>
                                <input type="text" name="title" id="announcementTitle" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-3 focus:ring-blue-500 text-base">
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="form-group">
                                    <label class="block text-base font-semibold text-gray-700 mb-3">公告类型</label>
                                    <select name="announcement_type" id="announcementType" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-3 focus:ring-blue-500 text-base">
                                        <option value="info">信息</option>
                                        <option value="warning">警告</option>
                                        <option value="success">成功</option>
                                        <option value="error">错误</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="block text-base font-semibold text-gray-700 mb-3">目标群体</label>
                                    <select name="target_audience" id="targetAudience" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-3 focus:ring-blue-500 text-base">
                                        <option value="all">所有用户</option>
                                        <option value="free">免费用户</option>
                                        <option value="basic">基础会员</option>
                                        <option value="premium">高级会员</option>
                                        <option value="enterprise">企业会员</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="block text-base font-semibold text-gray-700 mb-3">优先级</label>
                                    <input type="number" name="priority" id="announcementPriority" value="0" min="0" max="999"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-3 focus:ring-blue-500 text-base">
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="flex items-center space-x-3">
                                    <input type="checkbox" name="show_popup" id="showPopup" checked
                                           class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="showPopup" class="block text-base font-semibold text-gray-700">弹窗显示</label>
                                </div>
                                
                                <div class="form-group">
                                    <label class="block text-base font-semibold text-gray-700 mb-3">结束时间（可选）</label>
                                    <input type="datetime-local" name="end_date" id="endDate" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-3 focus:ring-blue-500 text-base">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="block text-base font-semibold text-gray-700 mb-3">公告内容</label>
                                <textarea name="content" id="announcementContent" rows="12" required
                                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-3 focus:ring-blue-500 text-base"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 px-8 py-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-6 py-3 bg-blue-600 text-base font-semibold text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto">
                            <i class="fas fa-save mr-2"></i>保存
                        </button>
                        <button type="button" onclick="closeAnnouncementModal()" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-6 py-3 bg-white text-base font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto">
                            取消
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 用户配额编辑模态框 -->
    <div id="userQuotaModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="userQuotaForm" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="update_user_quota">
                    <input type="hidden" name="user_id" id="editUserId">
                    
                    <div class="bg-white px-8 pt-8 pb-6">
                        <div class="mb-6">
                            <h3 class="text-xl leading-6 font-bold text-gray-900" id="editUserTitle">编辑用户配额</h3>
                        </div>
                        
                        <div class="grid grid-cols-1 gap-6">
                            <div class="form-group">
                                <label class="block text-base font-semibold text-gray-700 mb-3">会员类型</label>
                                <select name="membership_type" id="editMembershipType" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-3 focus:ring-blue-500 text-base">
                                    <option value="free">免费用户</option>
                                    <option value="basic">基础会员</option>
                                    <option value="premium">高级会员</option>
                                    <option value="enterprise">企业会员</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="block text-base font-semibold text-gray-700 mb-3">总配额</label>
                                <input type="number" name="total_quota" id="editTotalQuota" min="0" max="10000" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-3 focus:ring-blue-500 text-base">
                            </div>
                            
                            <div class="form-group">
                                <label class="block text-base font-semibold text-gray-700 mb-3">剩余配额</label>
                                <input type="number" name="remaining_quota" id="editRemainingQuota" min="0" max="10000" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-3 focus:ring-blue-500 text-base">
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 px-8 py-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-6 py-3 bg-blue-600 text-base font-semibold text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto">
                            <i class="fas fa-save mr-2"></i>保存
                        </button>
                        <button type="button" onclick="closeUserQuotaModal()" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-6 py-3 bg-white text-base font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto">
                            取消
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // TinyMCE 初始化
        tinymce.init({
            selector: '#announcementContent',
            height: 350,
            language: 'zh_CN',
            plugins: 'lists link image table code',
            toolbar: 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image | table | code',
            menubar: false,
            branding: false,
            setup: function(editor) {
                editor.on('change', function() {
                    editor.save();
                });
            }
        });

        // 公告模态框管理
        function openAnnouncementModal() {
            document.getElementById('announcementModal').classList.remove('hidden');
            document.getElementById('modalTitle').textContent = '新建公告';
            document.getElementById('announcementAction').value = 'create_announcement';
            document.getElementById('announcementForm').reset();
            if (tinymce.get('announcementContent')) {
                tinymce.get('announcementContent').setContent('');
            }
        }

        function editAnnouncement(announcement) {
            document.getElementById('announcementModal').classList.remove('hidden');
            document.getElementById('modalTitle').textContent = '编辑公告';
            document.getElementById('announcementAction').value = 'update_announcement';
            document.getElementById('announcementId').value = announcement.id;
            document.getElementById('announcementTitle').value = announcement.title;
            document.getElementById('announcementType').value = announcement.announcement_type;
            document.getElementById('targetAudience').value = announcement.target_audience;
            document.getElementById('announcementPriority').value = announcement.priority;
            document.getElementById('showPopup').checked = announcement.show_popup == 1;
            
            if (announcement.end_date && announcement.end_date !== '0000-00-00 00:00:00') {
                const endDate = new Date(announcement.end_date);
                const year = endDate.getFullYear();
                const month = String(endDate.getMonth() + 1).padStart(2, '0');
                const day = String(endDate.getDate()).padStart(2, '0');
                const hours = String(endDate.getHours()).padStart(2, '0');
                const minutes = String(endDate.getMinutes()).padStart(2, '0');
                document.getElementById('endDate').value = `${year}-${month}-${day}T${hours}:${minutes}`;
            }
            
            if (tinymce.get('announcementContent')) {
                tinymce.get('announcementContent').setContent(announcement.content);
            } else {
                document.getElementById('announcementContent').value = announcement.content;
            }
        }

        function closeAnnouncementModal() {
            document.getElementById('announcementModal').classList.add('hidden');
        }

        // 用户配额编辑
        function editUserQuota(user) {
            document.getElementById('userQuotaModal').classList.remove('hidden');
            document.getElementById('editUserTitle').textContent = `编辑用户配额 - ${user.username}`;
            document.getElementById('editUserId').value = user.user_id;
            document.getElementById('editMembershipType').value = user.membership_type;
            document.getElementById('editTotalQuota').value = user.total_quota;
            document.getElementById('editRemainingQuota').value = user.remaining_quota;
        }

        function closeUserQuotaModal() {
            document.getElementById('userQuotaModal').classList.add('hidden');
        }

        // 用户筛选
        function filterUsers() {
            const filter = document.getElementById('membershipFilter').value;
            const rows = document.querySelectorAll('.user-row');
            
            rows.forEach(row => {
                if (filter === 'all' || row.dataset.membership === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // AI提供商测试
        function testProvider(provider) {
            // 显示加载状态
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>测试中...';
            button.disabled = true;
            
            // 创建FormData
            const formData = new FormData();
            formData.append('csrf_token', '<?php echo htmlspecialchars($csrf_token); ?>');
            formData.append('action', 'test_ai_provider');
            formData.append('provider', provider);
            
            // 发送AJAX请求
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                // 解析响应中的消息
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const messageDiv = doc.querySelector('.alert');
                
                if (messageDiv) {
                    // 显示消息
                    const existingAlert = document.querySelector('.alert');
                    if (existingAlert) {
                        existingAlert.remove();
                    }
                    
                    // 在页面顶部插入消息
                    const container = document.querySelector('.container') || document.body;
                    container.insertAdjacentHTML('afterbegin', messageDiv.outerHTML);
                    
                    // 自动滚动到消息位置
                    const newAlert = document.querySelector('.alert');
                    if (newAlert) {
                        newAlert.scrollIntoView({ behavior: 'smooth' });
                    }
                    
                    // 5秒后自动隐藏消息
                    setTimeout(() => {
                        const alert = document.querySelector('.alert');
                        if (alert) {
                            alert.style.opacity = '0';
                            setTimeout(() => alert.remove(), 300);
                        }
                    }, 5000);
                }
            })
            .catch(error => {
                console.error('测试请求失败:', error);
                alert('测试请求失败，请重试');
            })
            .finally(() => {
                // 恢复按钮状态
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }
    </script>
</body>
</html>
