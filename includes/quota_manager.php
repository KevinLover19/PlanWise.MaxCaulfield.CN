<?php
/**
 * PlanWise 用户配额管理系统
 * Author: Max Caulfield
 */

class QuotaManager {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * 获取用户配额信息
     */
    public function getUserQuota($user_id) {
        if (!$user_id) {
            return $this->getGuestQuota();
        }
        
        $stmt = $this->db->prepare("
            SELECT remaining_quota, total_quota, membership_type, last_reset, last_used 
            FROM planwise_user_quotas 
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $quota = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$quota) {
            // 创建默认配额
            $this->createDefaultQuota($user_id);
            return $this->getUserQuota($user_id);
        }
        
        // 检查是否需要重置配额
        $this->checkQuotaReset($user_id, $quota);
        
        return $quota;
    }
    
    /**
     * 获取游客配额信息
     */
    public function getGuestQuota() {
        $guest_limit = $this->getConfig('guest_daily_limit', 3);
        
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as used_today 
            FROM planwise_sessions 
            WHERE user_id IS NULL AND DATE(created_at) = CURDATE()
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        
        return [
            'remaining_quota' => max(0, $guest_limit - $result['used_today']),
            'total_quota' => $guest_limit,
            'membership_type' => 'guest',
            'last_reset' => date('Y-m-d'),
            'reset_type' => 'daily'
        ];
    }
    
    /**
     * 检查用户是否可以创建报告
     */
    public function canCreateReport($user_id, $analysis_depth = 'basic') {
        $quota = $this->getUserQuota($user_id);
        $cost = $this->getAnalysisCost($analysis_depth);
        
        if ($quota['remaining_quota'] >= $cost) {
            return ['allowed' => true, 'quota' => $quota];
        }
        
        return [
            'allowed' => false, 
            'quota' => $quota,
            'message' => $user_id ? '您的分析次数已用完，请升级会员或等待配额重置' : '游客每日分析次数已达上限，请注册后继续使用'
        ];
    }
    
    /**
     * 扣除用户配额
     */
    public function deductQuota($user_id, $analysis_depth = 'basic') {
        if (!$user_id) {
            return; // 游客配额通过计数检查
        }
        
        $cost = $this->getAnalysisCost($analysis_depth);
        
        $stmt = $this->db->prepare("
            UPDATE planwise_user_quotas 
            SET remaining_quota = remaining_quota - ?, last_used = NOW()
            WHERE user_id = ? AND remaining_quota >= ?
        ");
        
        $result = $stmt->execute([$cost, $user_id, $cost]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('配额不足，无法完成分析');
        }
    }
    
    /**
     * 获取分析成本
     */
    public function getAnalysisCost($depth) {
        $costs = [
            'basic' => 1,
            'standard' => 2,
            'deep' => 3
        ];
        
        return $costs[$depth] ?? 1;
    }
    
    /**
     * 获取会员类型配额配置
     */
    public function getMembershipQuotas() {
        return [
            'free' => [
                'monthly_quota' => (int)$this->getConfig('free_user_monthly_quota', 10),
                'name' => '免费用户',
                'features' => ['基础分析', '标准分析限制', '报告保存30天']
            ],
            'basic' => [
                'monthly_quota' => (int)$this->getConfig('basic_user_monthly_quota', 50),
                'name' => '基础会员',
                'features' => ['所有分析深度', '无限报告保存', '优先处理']
            ],
            'premium' => [
                'monthly_quota' => (int)$this->getConfig('premium_user_monthly_quota', 200),
                'name' => '高级会员',
                'features' => ['所有功能', '专家点评', 'API访问', '自定义模板']
            ],
            'enterprise' => [
                'monthly_quota' => (int)$this->getConfig('enterprise_user_monthly_quota', 1000),
                'name' => '企业会员',
                'features' => ['企业级支持', '团队协作', '高级定制', '专属客服']
            ]
        ];
    }
    
    /**
     * 升级用户会员
     */
    public function upgradeMembership($user_id, $new_membership) {
        $quotas = $this->getMembershipQuotas();
        
        if (!isset($quotas[$new_membership])) {
            throw new Exception('无效的会员类型');
        }
        
        $new_quota = $quotas[$new_membership]['monthly_quota'];
        
        $stmt = $this->db->prepare("
            INSERT INTO planwise_user_quotas (user_id, remaining_quota, total_quota, membership_type, last_reset)
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                total_quota = ?,
                membership_type = ?,
                remaining_quota = GREATEST(remaining_quota, ?),
                updated_at = NOW()
        ");
        
        $stmt->execute([
            $user_id, $new_quota, $new_quota, $new_membership,
            $new_quota, $new_membership, $new_quota
        ]);
        
        return true;
    }
    
    /**
     * 创建默认配额
     */
    private function createDefaultQuota($user_id) {
        $default_quota = (int)$this->getConfig('free_user_monthly_quota', 10);
        
        $stmt = $this->db->prepare("
            INSERT INTO planwise_user_quotas (
                user_id, remaining_quota, total_quota, membership_type, last_reset
            ) VALUES (?, ?, ?, 'free', NOW())
        ");
        
        $stmt->execute([$user_id, $default_quota, $default_quota]);
    }
    
    /**
     * 检查配额重置
     */
    private function checkQuotaReset($user_id, $quota) {
        $last_reset = strtotime($quota['last_reset']);
        $now = time();
        $month_start = strtotime('first day of this month 00:00:00');
        
        if ($last_reset < $month_start) {
            // 重置配额
            $stmt = $this->db->prepare("
                UPDATE planwise_user_quotas 
                SET remaining_quota = total_quota, last_reset = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
        }
    }
    
    /**
     * 获取配置值
     */
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
    
    /**
     * 获取用户配额使用统计
     */
    public function getUserUsageStats($user_id, $days = 30) {
        if (!$user_id) {
            return [];
        }
        
        $stmt = $this->db->prepare("
            SELECT 
                DATE(created_at) as date,
                analysis_depth,
                COUNT(*) as count
            FROM planwise_sessions 
            WHERE user_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY DATE(created_at), analysis_depth
            ORDER BY date DESC
        ");
        
        $stmt->execute([$user_id, $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 获取用户报告列表
     */
    public function getUserReports($user_id, $limit = 20, $offset = 0) {
        if (!$user_id) {
            return [];
        }
        
        $stmt = $this->db->prepare("
            SELECT 
                s.session_id,
                s.business_idea,
                s.industry,
                s.analysis_depth,
                s.status,
                s.created_at,
                s.completed_at,
                CASE WHEN r.session_id IS NOT NULL THEN 1 ELSE 0 END as has_report
            FROM planwise_sessions s
            LEFT JOIN planwise_reports r ON s.session_id = r.session_id
            WHERE s.user_id = ?
            ORDER BY s.created_at DESC
            LIMIT ? OFFSET ?
        ");
        
        $stmt->execute([$user_id, $limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
