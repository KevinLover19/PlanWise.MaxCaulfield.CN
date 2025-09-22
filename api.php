<?php
/**
 * PlanWise AI 商业策略智能体 API
 * Author: Max Caulfield
 * Created: 2024-09-21
 */

// 安全检查和通用设置
error_reporting(E_ALL);
ini_set('display_errors', 0);
session_start();

// 引入数据库连接
require_once 'db_connect.php';
require_once 'AI_Service.php';

// CSRF保护
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die(json_encode(['error' => 'CSRF token validation failed']));
    }
}

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

class PlanWiseAPI {
    private $db;
    private $ai_service;
    private $user_id;
    private $is_guest;
    
    // 分析步骤定义
    private $analysis_steps = [
        1 => ['name' => '市场环境分析', 'description' => '正在分析目标市场环境和规模...', 'weight' => 15],
        2 => ['name' => '竞争对手研究', 'description' => '正在识别和分析主要竞争对手...', 'weight' => 12],
        3 => ['name' => '目标用户画像', 'description' => '正在构建详细用户画像...', 'weight' => 13],
        4 => ['name' => '商业模式设计', 'description' => '正在设计最优盈利模式...', 'weight' => 18],
        5 => ['name' => '风险评估分析', 'description' => '正在识别潜在风险因素...', 'weight' => 10],
        6 => ['name' => '财务预测建模', 'description' => '正在进行财务建模和预测...', 'weight' => 15],
        7 => ['name' => '营销策略制定', 'description' => '正在制定营销推广方案...', 'weight' => 10],
        8 => ['name' => '实施计划规划', 'description' => '正在制定详细实施路线图...', 'weight' => 7]
    ];
    
    public function __construct() {
        global $pdo;
        $this->db = $pdo;
        $this->ai_service = new AI_Service();
        
        // 用户认证
        $this->authenticateUser();
    }
    
    private function authenticateUser() {
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            $this->user_id = $_SESSION['user_id'];
            $this->is_guest = false;
        } else {
            // 游客模式
            $this->user_id = null;
            $this->is_guest = true;
        }
    }
    
    public function handleRequest() {
        $action = $_GET['action'] ?? $_POST['action'] ?? '';
        
        try {
            switch ($action) {
                case 'start_analysis':
                    return $this->startAnalysis();
                case 'get_progress':
                    return $this->getProgress();
                case 'get_report':
                    return $this->getReport();
                case 'save_report':
                    return $this->saveReport();
                case 'get_user_reports':
                    return $this->getUserReports();
                case 'get_quota_status':
                    return $this->getQuotaStatus();
                case 'get_csrf_token':
                    return $this->getCsrfToken();
                default:
                    throw new Exception('Invalid action');
            }
        } catch (Exception $e) {
            error_log("PlanWise API Error: " . $e->getMessage());
            http_response_code(400);
            return ['error' => $e->getMessage()];
        }
    }
    
    private function getCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return ['csrf_token' => $_SESSION['csrf_token']];
    }
    
    private function startAnalysis() {
        // 检查用户权限和配额
        $quota_check = $this->checkUserQuota();
        if (!$quota_check['allowed']) {
            throw new Exception($quota_check['message']);
        }
        
        $business_idea = $_POST['business_idea'] ?? '';
        $industry = $_POST['industry'] ?? '';
        $analysis_depth = $_POST['analysis_depth'] ?? 'basic';
        $focus_areas = $_POST['focus_areas'] ?? [];
        
        if (empty($business_idea) || strlen($business_idea) < 10) {
            throw new Exception('商业想法描述太简短，请提供更详细的描述');
        }
        
        // 创建分析会话
        $session_id = $this->createAnalysisSession($business_idea, $industry, $analysis_depth, $focus_areas);
        
        // 扣除用户配额
        $this->deductUserQuota($analysis_depth);
        
        // 启动异步分析（模拟）
        $this->initializeAnalysisProgress($session_id);
        
        return [
            'success' => true,
            'session_id' => $session_id,
            'estimated_time' => $this->getEstimatedTime($analysis_depth),
            'steps' => $this->analysis_steps
        ];
    }
    
    private function createAnalysisSession($business_idea, $industry, $depth, $focus_areas) {
        $session_id = uniqid('planwise_', true);
        
        $stmt = $this->db->prepare("
            INSERT INTO planwise_sessions (
                session_id, user_id, business_idea, industry, 
                analysis_depth, focus_areas, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'processing', NOW())
        ");
        
        $stmt->execute([
            $session_id,
            $this->user_id,
            htmlspecialchars($business_idea, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($industry, ENT_QUOTES, 'UTF-8'),
            $depth,
            json_encode($focus_areas)
        ]);
        
        return $session_id;
    }
    
    private function initializeAnalysisProgress($session_id) {
        // 创建进度追踪记录
        $stmt = $this->db->prepare("
            INSERT INTO planwise_analysis_queue (session_id, current_step, status, progress_percentage, created_at) 
            VALUES (?, 1, 'processing', 0, NOW())
        ");
        $stmt->execute([$session_id]);
        
        // 这里实际应该启动后台任务或队列处理
        // 为演示目的，我们创建一个模拟的进度更新机制
    }
    
    private function getProgress() {
        $session_id = $_GET['session_id'] ?? '';
        if (empty($session_id)) {
            throw new Exception('Session ID required');
        }
        
        // 验证用户权限
        if (!$this->validateSessionAccess($session_id)) {
            throw new Exception('Access denied');
        }
        
        // 模拟进度更新逻辑
        $this->simulateProgressUpdate($session_id);
        
        $stmt = $this->db->prepare("
            SELECT current_step, status, progress_percentage, current_step_description,
                   error_message, updated_at
            FROM planwise_analysis_queue 
            WHERE session_id = ?
        ");
        $stmt->execute([$session_id]);
        $progress = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$progress) {
            throw new Exception('Session not found');
        }
        
        return [
            'success' => true,
            'progress' => $progress,
            'steps' => $this->analysis_steps,
            'estimated_remaining' => $this->calculateRemainingTime($progress['current_step'], $progress['status'])
        ];
    }
    
    private function simulateProgressUpdate($session_id) {
        // 获取当前进度
        $stmt = $this->db->prepare("
            SELECT current_step, status, progress_percentage, updated_at 
            FROM planwise_analysis_queue 
            WHERE session_id = ?
        ");
        $stmt->execute([$session_id]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$current) return;
        
        $now = time();
        $last_update = strtotime($current['updated_at']);
        
        // 如果最后更新超过30秒且未完成，则推进进度
        if ($now - $last_update > 30 && $current['status'] !== 'completed') {
            $new_step = min($current['current_step'] + 1, count($this->analysis_steps));
            $new_progress = ($new_step / count($this->analysis_steps)) * 100;
            $status = $new_step >= count($this->analysis_steps) ? 'completed' : 'processing';
            $description = $this->analysis_steps[$new_step]['description'] ?? 'Analysis completed';
            
            $update_stmt = $this->db->prepare("
                UPDATE planwise_analysis_queue 
                SET current_step = ?, status = ?, progress_percentage = ?, 
                    current_step_description = ?, updated_at = NOW()
                WHERE session_id = ?
            ");
            
            $update_stmt->execute([$new_step, $status, $new_progress, $description, $session_id]);
            
            // 如果完成，更新会话状态并生成报告
            if ($status === 'completed') {
                $this->completeAnalysis($session_id);
            }
        }
    }
    
    private function completeAnalysis($session_id) {
        // 获取会话详情
        $session = $this->getSessionDetails($session_id);
        if (!$session) return;
        
        // 生成模拟报告
        $report_content = $this->ai_service->generateReport(
            $session['business_idea'],
            $session['industry'],
            $session['analysis_depth'],
            json_decode($session['focus_areas'] ?? '[]', true)
        );
        
        // 保存报告
        $stmt = $this->db->prepare("
            INSERT INTO planwise_reports (session_id, report_content, executive_summary, generated_at)
            VALUES (?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $session_id,
            json_encode($report_content['content']),
            json_encode($report_content['executive_summary'])
        ]);
        
        // 更新会话状态
        $this->db->prepare("UPDATE planwise_sessions SET status = 'completed', completed_at = NOW() WHERE session_id = ?")
                 ->execute([$session_id]);
    }
    
    private function getReport() {
        $session_id = $_GET['session_id'] ?? '';
        if (empty($session_id)) {
            throw new Exception('Session ID required');
        }
        
        if (!$this->validateSessionAccess($session_id)) {
            throw new Exception('Access denied');
        }
        
        $stmt = $this->db->prepare("
            SELECT s.*, r.report_content, r.executive_summary, r.generated_at
            FROM planwise_sessions s
            LEFT JOIN planwise_reports r ON s.session_id = r.session_id
            WHERE s.session_id = ? AND s.status = 'completed'
        ");
        $stmt->execute([$session_id]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$report || !$report['report_content']) {
            throw new Exception('Report not found or not completed');
        }
        
        return [
            'success' => true,
            'report' => [
                'session_id' => $report['session_id'],
                'business_idea' => $report['business_idea'],
                'industry' => $report['industry'],
                'analysis_depth' => $report['analysis_depth'],
                'executive_summary' => json_decode($report['executive_summary'], true),
                'content' => json_decode($report['report_content'], true),
                'generated_at' => $report['generated_at'],
                'created_at' => $report['created_at']
            ]
        ];
    }
    
    private function getUserReports() {
        if ($this->is_guest) {
            throw new Exception('Login required');
        }
        
        $stmt = $this->db->prepare("
            SELECT s.session_id, s.business_idea, s.industry, s.analysis_depth, 
                   s.status, s.created_at, s.completed_at,
                   CASE WHEN r.session_id IS NOT NULL THEN 1 ELSE 0 END as has_report
            FROM planwise_sessions s
            LEFT JOIN planwise_reports r ON s.session_id = r.session_id
            WHERE s.user_id = ?
            ORDER BY s.created_at DESC
            LIMIT 50
        ");
        
        $stmt->execute([$this->user_id]);
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'reports' => $reports
        ];
    }
    
    private function getQuotaStatus() {
        if ($this->is_guest) {
            $guest_limit = $this->getConfig('guest_daily_limit', 3);
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as used_today 
                FROM planwise_sessions 
                WHERE user_id IS NULL AND DATE(created_at) = CURDATE()
            ");
            $stmt->execute();
            $result = $stmt->fetch();
            
            return [
                'success' => true,
                'quota' => [
                    'type' => 'guest',
                    'total' => $guest_limit,
                    'remaining' => max(0, $guest_limit - $result['used_today']),
                    'reset_time' => 'Daily at midnight'
                ]
            ];
        } else {
            $quota = $this->getUserQuota();
            return [
                'success' => true,
                'quota' => $quota
            ];
        }
    }
    
    private function checkUserQuota() {
        if ($this->is_guest) {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as daily_count 
                FROM planwise_sessions 
                WHERE user_id IS NULL AND DATE(created_at) = CURDATE()
            ");
            $stmt->execute();
            $result = $stmt->fetch();
            
            $guest_daily_limit = $this->getConfig('guest_daily_limit', 3);
            if ($result['daily_count'] >= $guest_daily_limit) {
                return [
                    'allowed' => false, 
                    'message' => '游客每日分析次数已达上限，请注册后继续使用'
                ];
            }
        } else {
            $quota = $this->getUserQuota();
            if ($quota['remaining_quota'] <= 0) {
                return [
                    'allowed' => false, 
                    'message' => '您的分析次数已用完，请升级会员或等待配额重置'
                ];
            }
        }
        
        return ['allowed' => true, 'message' => 'OK'];
    }
    
    private function getUserQuota() {
        if ($this->is_guest) return ['remaining_quota' => 0, 'total_quota' => 0];
        
        $stmt = $this->db->prepare("
            SELECT remaining_quota, total_quota, last_reset, membership_type
            FROM planwise_user_quotas 
            WHERE user_id = ?
        ");
        $stmt->execute([$this->user_id]);
        $quota = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$quota) {
            $this->createDefaultQuota();
            return $this->getUserQuota();
        }
        
        $this->checkQuotaReset($quota);
        return $quota;
    }
    
    private function deductUserQuota($analysis_depth) {
        if ($this->is_guest) return;
        
        $cost = $this->getAnalysisCost($analysis_depth);
        
        $stmt = $this->db->prepare("
            UPDATE planwise_user_quotas 
            SET remaining_quota = remaining_quota - ?,
                last_used = NOW()
            WHERE user_id = ? AND remaining_quota >= ?
        ");
        
        $stmt->execute([$cost, $this->user_id, $cost]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('配额不足，无法完成分析');
        }
    }
    
    private function getAnalysisCost($depth) {
        return match($depth) {
            'basic' => 1,
            'standard' => 2,
            'deep' => 3,
            default => 1
        };
    }
    
    private function getConfig($key, $default = null) {
        try {
            $stmt = $this->db->prepare("SELECT config_value FROM planwise_config WHERE config_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch();
            return $result ? $result['config_value'] : $default;
        } catch (Exception $e) {
            return $default;
        }
    }
    
    private function getEstimatedTime($depth) {
        return match($depth) {
            'basic' => 300,    // 5 minutes
            'standard' => 900, // 15 minutes
            'deep' => 1800,    // 30 minutes
            default => 300
        };
    }
    
    private function validateSessionAccess($session_id) {
        $stmt = $this->db->prepare("SELECT user_id FROM planwise_sessions WHERE session_id = ?");
        $stmt->execute([$session_id]);
        $session = $stmt->fetch();
        
        if (!$session) return false;
        
        if (is_null($session['user_id'])) return true;
        return $session['user_id'] === $this->user_id;
    }
    
    private function getSessionDetails($session_id) {
        $stmt = $this->db->prepare("SELECT * FROM planwise_sessions WHERE session_id = ?");
        $stmt->execute([$session_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function calculateRemainingTime($current_step, $status) {
        $remaining_steps = count($this->analysis_steps) - $current_step;
        if ($status === 'completed') $remaining_steps = 0;
        return max(0, $remaining_steps * 45);
    }
    
    private function createDefaultQuota() {
        $stmt = $this->db->prepare("
            INSERT INTO planwise_user_quotas (
                user_id, remaining_quota, total_quota, membership_type, last_reset
            ) VALUES (?, 10, 10, 'free', NOW())
        ");
        $stmt->execute([$this->user_id]);
    }
    
    private function checkQuotaReset($quota) {
        $last_reset = strtotime($quota['last_reset']);
        $month_start = strtotime('first day of this month 00:00:00');
        
        if ($last_reset < $month_start) {
            $stmt = $this->db->prepare("
                UPDATE planwise_user_quotas 
                SET remaining_quota = total_quota, last_reset = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([$this->user_id]);
        }
    }
}

// 主处理逻辑
try {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    $api = new PlanWiseAPI();
    $response = $api->handleRequest();
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("PlanWise API Fatal Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
