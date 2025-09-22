<?php
// /www/wwwroot/planwise.maxcaulfield.cn/db_connect.php
// Secure DB connection, unified session management, CSRF & XSS helpers

// 1) Unified session across *.maxcaulfield.cn
if (session_status() === PHP_SESSION_NONE) {
    $is_https = (
        (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
        (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
    );
    
    // Align with main site session name to enable SSO-like behavior if needed
    session_name('MAXCAULFIELD_SESSION');

    // Determine cookie domain dynamically
    $cookie_domain = '';
    if (!empty($_SERVER['HTTP_HOST'])) {
        $host = $_SERVER['HTTP_HOST'];
        if (stripos($host, 'maxcaulfield.cn') !== false) {
            $cookie_domain = '.maxcaulfield.cn';
        } else {
            $cookie_domain = $host;
        }
    }

    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'domain' => $cookie_domain,
        'secure' => $is_https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    @ini_set('session.use_strict_mode', '1');
    @ini_set('session.gc_maxlifetime', '86400');
    @ini_set('session.gc_probability', '1');
    @ini_set('session.gc_divisor', '100');
    session_start();
}

// 2) Security helpers
function h($str) { return htmlspecialchars((string)$str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_validate($token) {
    if (!isset($_SESSION['csrf_token']) || !is_string($token)) return false;
    $ok = hash_equals($_SESSION['csrf_token'], $token);
    if ($ok) unset($_SESSION['csrf_token']); // one-time token
    return $ok;
}

if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        return csrf_token();
    }
}

// 3) Authentication functions
function is_logged_in() {
    return isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

function get_current_user_id() {
    return is_logged_in() ? (int)$_SESSION['user_id'] : null;
}

function get_current_user_info() {
    if (!is_logged_in()) return null;
    
    try {
        $pdo = planwise_pdo();
        // Try to get user from main site users table first
        $stmt = $pdo->prepare("SELECT id, username, email, display_name as nickname FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // If not found in main users table, try planwise_users
            $stmt = $pdo->prepare("SELECT id, username, email, nickname FROM planwise_users WHERE id = ? AND status = 'active' LIMIT 1");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
        }
        
        return $user ?: null;
    } catch (Exception $e) {
        error_log('[PlanWise] Get user info error: ' . $e->getMessage());
        return null;
    }
}

function login_user($user_id, $remember = false) {
    $_SESSION['user_id'] = (int)$user_id;
    $_SESSION['login_time'] = time();
    
    if ($remember) {
        // Set remember token (optional implementation)
        $_SESSION['remember_login'] = true;
    }
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    return true;
}

function logout_user() {
    $_SESSION = [];
    
    // Destroy session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

function require_login($redirect_to = '/login.php') {
    if (!is_logged_in()) {
        $current_url = $_SERVER['REQUEST_URI'] ?? '';
        $redirect_url = $redirect_to;
        
        if ($current_url && $redirect_to === '/login.php') {
            $redirect_url .= '?redirect=' . urlencode($current_url);
        }
        
        header('Location: ' . $redirect_url);
        exit();
    }
}

// 4) Database connection
function planwise_pdo() {
    static $pdo = null; 
    if ($pdo) return $pdo;

    $db_host = getenv('PLANWISE_DB_HOST') ?: 'localhost';
    $db_name = getenv('PLANWISE_DB_NAME') ?: 'maxcaulfield_cn';
    $db_user = getenv('PLANWISE_DB_USER') ?: 'maxcaulfield_cn';
    $db_pass = getenv('PLANWISE_DB_PASS');

    // Use hardcoded database credentials as fallback to avoid open_basedir restrictions
    if ($db_pass === false || $db_pass === '') {
        $db_pass = 'd5iKNkpKd2eGxT8p'; // MySQL password from rules
    }

    $charset = 'utf8mb4';
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset={$charset}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ];
    
    try {
        $pdo = new PDO($dsn, $db_user, $db_pass, $options);
        $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        return $pdo;
    } catch (Throwable $e) {
        error_log('[PlanWise] DB connect error: ' . $e->getMessage());
        http_response_code(500);
        die('数据库连接失败');
    }
}

// 5) Alias function for compatibility
function getPDO() {
    return planwise_pdo();
}

// 6) Reports table (if not exists)  
function ensure_reports_table() {
    try {
        $pdo = planwise_pdo();
        $pdo->exec("CREATE TABLE IF NOT EXISTS reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            business_name VARCHAR(200) NOT NULL,
            industry VARCHAR(100) DEFAULT NULL,
            business_description TEXT NOT NULL,
            analysis_depth ENUM('basic','standard','deep') NOT NULL DEFAULT 'standard',
            focus_area VARCHAR(50) DEFAULT 'balanced',
            status ENUM('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
            analysis_result LONGTEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            completed_at TIMESTAMP NULL,
            INDEX(user_id), INDEX(status), INDEX(industry)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Also ensure users table exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            display_name VARCHAR(100) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL,
            INDEX(username), INDEX(email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
    } catch (Exception $e) {
        error_log('[PlanWise] Table creation error: ' . $e->getMessage());
    }
}

// 7) One-time DDL installer (idempotent)
function planwise_install_schema(PDO $pdo) {
    // PlanWise users table (for standalone users not from main site)
    $pdo->exec("CREATE TABLE IF NOT EXISTS planwise_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        nickname VARCHAR(50) DEFAULT NULL,
        password_hash VARCHAR(255) NOT NULL,
        status ENUM('active','inactive','banned') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login_at TIMESTAMP NULL,
        INDEX(username), INDEX(email), INDEX(status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS planwise_tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        business_idea TEXT NOT NULL,
        industry VARCHAR(100) DEFAULT NULL,
        analysis_depth ENUM('basic','standard','deep') NOT NULL DEFAULT 'standard',
        status ENUM('pending','running','completed','error') NOT NULL DEFAULT 'pending',
        current_step VARCHAR(50) DEFAULT NULL,
        total_steps INT NOT NULL DEFAULT 8,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        INDEX(status), INDEX(current_step)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS planwise_steps (
        id INT AUTO_INCREMENT PRIMARY KEY,
        task_id INT NOT NULL,
        step_key VARCHAR(50) NOT NULL,
        step_title VARCHAR(120) NOT NULL,
        prompt_used TEXT NULL,
        ai_response LONGTEXT NULL,
        status ENUM('pending','running','completed','error') NOT NULL DEFAULT 'pending',
        started_at TIMESTAMP NULL,
        completed_at TIMESTAMP NULL,
        FOREIGN KEY (task_id) REFERENCES planwise_tasks(id) ON DELETE CASCADE,
        INDEX(task_id), INDEX(step_key), INDEX(status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS planwise_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        task_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        executive_summary TEXT NULL,
        full_content LONGTEXT NULL,
        export_count INT NOT NULL DEFAULT 0,
        is_public TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (task_id) REFERENCES planwise_tasks(id) ON DELETE CASCADE,
        INDEX(task_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function planwise_table_exists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([$table]);
    return (bool) $stmt->fetchColumn();
}

function planwise_ensure_column(PDO $pdo, string $table, string $column, string $definition): void {
    if (!planwise_table_exists($pdo, $table)) {
        return;
    }

    $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
    $stmt->execute([$column]);
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN {$definition}");
    }
}

function planwise_ensure_index(PDO $pdo, string $table, string $indexName, string $definition): void {
    if (!planwise_table_exists($pdo, $table)) {
        return;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?");
    $stmt->execute([$table, $indexName]);
    if ((int) $stmt->fetchColumn() === 0) {
        $pdo->exec("CREATE INDEX `{$indexName}` ON `{$table}` {$definition}");
    }
}

function planwise_get_reports_table(PDO $pdo): string {
    static $table = null;
    if ($table !== null) {
        return $table;
    }

    $table = planwise_table_exists($pdo, 'planwise_reports_v2') ? 'planwise_reports_v2' : 'planwise_reports';
    return $table;
}

function planwise_install_async_schema(PDO $pdo): void {
    // Task queue for asynchronous processing
    $pdo->exec("CREATE TABLE IF NOT EXISTS planwise_task_queue (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // New report table (string identifier based) to avoid conflicts with legacy schema
    $pdo->exec("CREATE TABLE IF NOT EXISTS planwise_reports_v2 (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Detailed step table for multi-stage report assembly
    $pdo->exec("CREATE TABLE IF NOT EXISTS planwise_report_steps (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Extend user quota table with advanced quota tracking if table exists
    if (planwise_table_exists($pdo, 'planwise_user_quotas')) {
        planwise_ensure_column($pdo, 'planwise_user_quotas', 'custom_id', "VARCHAR(50) DEFAULT NULL");
        planwise_ensure_column($pdo, 'planwise_user_quotas', 'category_group', "VARCHAR(50) DEFAULT 'standard'");
        planwise_ensure_column($pdo, 'planwise_user_quotas', 'total_tokens', "INT DEFAULT 1000000");
        planwise_ensure_column($pdo, 'planwise_user_quotas', 'used_tokens', "INT DEFAULT 0");
        planwise_ensure_column($pdo, 'planwise_user_quotas', 'reset_date', "DATE DEFAULT NULL");

        planwise_ensure_index($pdo, 'planwise_user_quotas', 'idx_custom_id', '(custom_id)');
        planwise_ensure_index($pdo, 'planwise_user_quotas', 'idx_category_group', '(category_group)');
    }
}

// Initialize tables on first load
$__planwiseBootstrapPdo = planwise_pdo();
ensure_reports_table();
planwise_install_schema($__planwiseBootstrapPdo);
planwise_install_async_schema($__planwiseBootstrapPdo);
