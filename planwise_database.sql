-- PlanWise AI 商业策略智能体数据库结构
-- 为主站 maxcaulfield.cn 数据库添加 PlanWise 相关表

USE maxcaulfield_cn;

-- 1. PlanWise 用户会话表
CREATE TABLE IF NOT EXISTS planwise_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) UNIQUE NOT NULL,
    user_id INT NULL, -- NULL表示游客用户
    business_idea TEXT NOT NULL,
    industry VARCHAR(100) DEFAULT '',
    analysis_depth ENUM('basic', 'standard', 'deep') DEFAULT 'basic',
    focus_areas JSON DEFAULT NULL,
    status ENUM('processing', 'completed', 'failed') DEFAULT 'processing',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    INDEX idx_session_id (session_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. PlanWise 分析进度队列表
CREATE TABLE IF NOT EXISTS planwise_analysis_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL,
    current_step TINYINT DEFAULT 1,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    progress_percentage DECIMAL(5,2) DEFAULT 0.00,
    current_step_description TEXT DEFAULT NULL,
    error_message TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_session (session_id),
    INDEX idx_status (status),
    INDEX idx_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. PlanWise 分步结果表
CREATE TABLE IF NOT EXISTS planwise_step_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL,
    step_number TINYINT NOT NULL,
    step_name VARCHAR(100) NOT NULL,
    result_data JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_session_step (session_id, step_number),
    INDEX idx_session_id (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. PlanWise 最终报告表
CREATE TABLE IF NOT EXISTS planwise_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) UNIQUE NOT NULL,
    report_content JSON NOT NULL,
    executive_summary JSON NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session_id (session_id),
    INDEX idx_generated_at (generated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. PlanWise 用户配额管理表
CREATE TABLE IF NOT EXISTS planwise_user_quotas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    remaining_quota INT DEFAULT 10,
    total_quota INT DEFAULT 10,
    membership_type ENUM('free', 'basic', 'premium', 'enterprise') DEFAULT 'free',
    last_reset TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_membership_type (membership_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. PlanWise AI 服务配置表
CREATE TABLE IF NOT EXISTS planwise_ai_configs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider VARCHAR(50) UNIQUE NOT NULL,
    provider_name VARCHAR(100) NOT NULL,
    config_data JSON NOT NULL, -- API密钥、模型配置等
    is_enabled BOOLEAN DEFAULT FALSE,
    priority_order TINYINT DEFAULT 100, -- 优先级排序，数值越小优先级越高
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_provider (provider),
    INDEX idx_enabled (is_enabled),
    INDEX idx_priority (priority_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. PlanWise AI 使用日志表
CREATE TABLE IF NOT EXISTS planwise_ai_usage_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL,
    provider VARCHAR(50) NOT NULL,
    tokens_used INT DEFAULT 0,
    cost DECIMAL(10,4) DEFAULT 0.0000,
    response_time INT DEFAULT 0, -- 响应时间（毫秒）
    status ENUM('success', 'failed') DEFAULT 'success',
    error_message TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session_id (session_id),
    INDEX idx_provider (provider),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. PlanWise 系统配置表
CREATE TABLE IF NOT EXISTS planwise_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT NOT NULL,
    config_type ENUM('string', 'int', 'bool', 'json') DEFAULT 'string',
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_config_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. PlanWise 管理员操作日志表
CREATE TABLE IF NOT EXISTS planwise_admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(50) DEFAULT NULL, -- user, config, ai_provider 等
    target_id VARCHAR(100) DEFAULT NULL,
    details JSON DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_user_id (admin_user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入默认系统配置
INSERT INTO planwise_config (config_key, config_value, config_type, description) VALUES
('guest_daily_limit', '3', 'int', '游客每日分析次数限制'),
('max_business_idea_length', '2000', 'int', '商业想法最大字符长度'),
('analysis_timeout_seconds', '300', 'int', '分析超时时间（秒）'),
('enable_guest_access', '1', 'bool', '是否允许游客访问'),
('default_analysis_depth', 'basic', 'string', '默认分析深度'),
('report_retention_days', '365', 'int', '报告保留天数'),
('free_user_monthly_quota', '10', 'int', '免费用户月度配额'),
('basic_user_monthly_quota', '50', 'int', '基础会员月度配额'),
('premium_user_monthly_quota', '200', 'int', '高级会员月度配额'),
('enterprise_user_monthly_quota', '1000', 'int', '企业会员月度配额')
ON DUPLICATE KEY UPDATE 
    config_value = VALUES(config_value),
    updated_at = CURRENT_TIMESTAMP;

-- 插入默认AI服务配置模板（需要管理员后续配置具体参数）
INSERT INTO planwise_ai_configs (provider, provider_name, config_data, is_enabled, priority_order) VALUES
('openai', 'OpenAI GPT', '{"api_key":"","model":"gpt-3.5-turbo","max_tokens":4000,"temperature":0.7}', FALSE, 10),
('claude', 'Claude', '{"api_key":"","model":"claude-3-sonnet","max_tokens":4000}', FALSE, 20),
('qianwen', '通义千问', '{"api_key":"","model":"qwen-plus","max_tokens":4000}', FALSE, 30),
('gemini', 'Google Gemini', '{"api_key":"","model":"gemini-pro","max_tokens":4000}', FALSE, 40),
('baichuan', '百川AI', '{"api_key":"","model":"baichuan2-turbo","max_tokens":4000}', FALSE, 50),
('moonshot', 'Moonshot AI', '{"api_key":"","model":"moonshot-v1-8k","max_tokens":4000}', FALSE, 60)
ON DUPLICATE KEY UPDATE 
    provider_name = VALUES(provider_name),
    priority_order = VALUES(priority_order);

-- 创建外键约束（如果存在用户表的话）
-- 注意：这里假设主站有users表，如果表名不同需要调整
-- ALTER TABLE planwise_sessions ADD CONSTRAINT fk_planwise_sessions_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;
-- ALTER TABLE planwise_user_quotas ADD CONSTRAINT fk_planwise_quotas_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
-- ALTER TABLE planwise_admin_logs ADD CONSTRAINT fk_planwise_admin_logs_user_id FOREIGN KEY (admin_user_id) REFERENCES users(id) ON DELETE CASCADE;

-- 创建视图：用户报告统计
CREATE OR REPLACE VIEW planwise_user_report_stats AS
SELECT 
    ps.user_id,
    COUNT(*) as total_reports,
    COUNT(CASE WHEN ps.status = 'completed' THEN 1 END) as completed_reports,
    COUNT(CASE WHEN ps.status = 'failed' THEN 1 END) as failed_reports,
    COUNT(CASE WHEN ps.status = 'processing' THEN 1 END) as processing_reports,
    MAX(ps.created_at) as last_report_date,
    AVG(CASE 
        WHEN ps.status = 'completed' AND ps.completed_at IS NOT NULL 
        THEN TIMESTAMPDIFF(SECOND, ps.created_at, ps.completed_at) 
        ELSE NULL 
    END) as avg_completion_time_seconds
FROM planwise_sessions ps
WHERE ps.user_id IS NOT NULL
GROUP BY ps.user_id;

-- 创建视图：AI服务使用统计
CREATE OR REPLACE VIEW planwise_ai_usage_stats AS
SELECT 
    DATE(created_at) as usage_date,
    provider,
    COUNT(*) as total_requests,
    SUM(tokens_used) as total_tokens,
    SUM(cost) as total_cost,
    AVG(response_time) as avg_response_time,
    COUNT(CASE WHEN status = 'success' THEN 1 END) as success_count,
    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_count
FROM planwise_ai_usage_logs
GROUP BY DATE(created_at), provider
ORDER BY usage_date DESC, provider;

-- 创建索引优化查询性能
CREATE INDEX idx_planwise_sessions_user_status ON planwise_sessions(user_id, status);
CREATE INDEX idx_planwise_sessions_created_status ON planwise_sessions(created_at, status);
CREATE INDEX idx_planwise_usage_date_provider ON planwise_ai_usage_logs(created_at, provider);

COMMIT;
