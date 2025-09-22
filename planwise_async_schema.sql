-- PlanWise AI 异步任务与多步骤报告扩展表结构
USE maxcaulfield_cn;

-- 任务队列表
CREATE TABLE IF NOT EXISTS planwise_task_queue (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id VARCHAR(64) NOT NULL UNIQUE,
  user_id INT NULL,
  report_id VARCHAR(64) DEFAULT NULL,
  task_type VARCHAR(50) NOT NULL,
  status ENUM('pending','processing','completed','failed') DEFAULT 'pending',
  priority INT DEFAULT 5,
  payload JSON,
  result JSON,
  error_message TEXT,
  retry_count INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  started_at TIMESTAMP NULL,
  completed_at TIMESTAMP NULL,
  KEY idx_status_priority (status, priority),
  KEY idx_user_id (user_id),
  KEY idx_task_id (task_id),
  KEY idx_report_id (report_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 报告元数据表（与旧 planwise_reports 并行）
CREATE TABLE IF NOT EXISTS planwise_reports_v2 (
  id INT AUTO_INCREMENT PRIMARY KEY,
  report_id VARCHAR(64) NOT NULL UNIQUE,
  user_id INT NULL,
  custom_id VARCHAR(50) DEFAULT NULL,
  title VARCHAR(255) NOT NULL,
  business_idea TEXT NOT NULL,
  industry VARCHAR(100) DEFAULT NULL,
  target_market VARCHAR(100) DEFAULT NULL,
  analysis_depth ENUM('basic','standard','deep') DEFAULT 'standard',
  status ENUM('draft','analyzing','completed','failed') DEFAULT 'draft',
  visibility ENUM('private','public','shared') DEFAULT 'private',
  share_token VARCHAR(64) DEFAULT NULL,
  total_words INT DEFAULT 0,
  ai_tokens_used INT DEFAULT 0,
  analysis_preferences JSON DEFAULT NULL,
  last_error TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  completed_at TIMESTAMP NULL,
  INDEX idx_user_id (user_id),
  INDEX idx_custom_id (custom_id),
  INDEX idx_status (status),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 报告步骤明细
CREATE TABLE IF NOT EXISTS planwise_report_steps (
  id INT AUTO_INCREMENT PRIMARY KEY,
  step_id VARCHAR(64) NOT NULL UNIQUE,
  report_id VARCHAR(64) NOT NULL,
  step_number INT NOT NULL,
  step_name VARCHAR(50) NOT NULL,
  step_title VARCHAR(100) NOT NULL,
  task_id VARCHAR(64) DEFAULT NULL,
  status ENUM('pending','processing','completed','failed','skipped') DEFAULT 'pending',
  ai_model VARCHAR(50) DEFAULT NULL,
  prompt_template TEXT,
  ai_response JSON,
  formatted_content MEDIUMTEXT,
  word_count INT DEFAULT 0,
  tokens_used INT DEFAULT 0,
  processing_time INT DEFAULT 0,
  retry_count INT DEFAULT 0,
  error_message TEXT DEFAULT NULL,
  error_code VARCHAR(50) DEFAULT NULL,
  metadata JSON DEFAULT NULL,
  started_at TIMESTAMP NULL,
  completed_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_report_step (report_id, step_number),
  KEY idx_report_id (report_id),
  KEY idx_task_id (task_id),
  KEY idx_status (status),
  KEY idx_step_name (step_name),
  CONSTRAINT fk_report_steps_report_v2 FOREIGN KEY (report_id) REFERENCES planwise_reports_v2 (report_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 用户配额扩展字段
ALTER TABLE planwise_user_quotas ADD COLUMN IF NOT EXISTS custom_id VARCHAR(50) DEFAULT NULL;
ALTER TABLE planwise_user_quotas ADD COLUMN IF NOT EXISTS category_group VARCHAR(50) DEFAULT 'standard';
ALTER TABLE planwise_user_quotas ADD COLUMN IF NOT EXISTS total_tokens INT DEFAULT 1000000;
ALTER TABLE planwise_user_quotas ADD COLUMN IF NOT EXISTS used_tokens INT DEFAULT 0;
ALTER TABLE planwise_user_quotas ADD COLUMN IF NOT EXISTS reset_date DATE DEFAULT NULL;
CREATE INDEX IF NOT EXISTS idx_custom_id ON planwise_user_quotas (custom_id);
CREATE INDEX IF NOT EXISTS idx_category_group ON planwise_user_quotas (category_group);
