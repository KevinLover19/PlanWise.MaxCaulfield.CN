<?php
// /www/wwwroot/planwise.maxcaulfield.cn/login.php
// 同步主站blog.php的样式风格

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 如果已登录，重定向到控制台
if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard.php');
    exit();
}

require_once __DIR__ . '/db_connect.php';
$pdo = getPDO();

$error_message = '';
$success_message = '';

// 处理登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error_message = '请输入用户名和密码';
    } else {
        try {
            // 查找用户
            $stmt = $pdo->prepare("SELECT id, username, email, password_hash FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // 登录成功
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                
                // 更新最后登录时间
                $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                header('Location: /dashboard.php');
                exit();
            } else {
                $error_message = '用户名或密码错误';
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error_message = '登录失败，请稍后重试';
        }
    }
}

// SEO设置
$page_actual_title = '用户登录 - PlanWise AI';
$meta_description = '登录您的PlanWise AI账户，访问您的商业策略分析报告，管理您的AI商业分析结果。';
$meta_keywords = '登录,PlanWise,商业分析,AI分析,用户账户';

require_once __DIR__ . '/includes/header.php';
?>

<main class="container mx-auto px-4 sm:px-6 lg:px-8 mt-8 mb-8 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md">
        <!-- 登录卡片 -->
        <div class="glass-effect p-8 md:p-10 fade-in">
            <!-- Logo 和标题 -->
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-brain text-2xl text-white"></i>
                </div>
                <h1 class="text-3xl font-bold mb-2">
                    <span class="text-gradient">欢迎回来</span>
                </h1>
                <p class="text-[var(--text-secondary)]">
                    登录您的PlanWise AI账户
                </p>
            </div>
            
            <!-- 错误提示 -->
            <?php if ($error_message): ?>
                <div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                        <span class="text-red-500 text-sm"><?php echo htmlspecialchars($error_message); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- 成功提示 -->
            <?php if ($success_message): ?>
                <div class="mb-6 p-4 bg-green-500/10 border border-green-500/20 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                        <span class="text-green-500 text-sm"><?php echo htmlspecialchars($success_message); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- 登录表单 -->
            <form method="POST" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-[var(--text-primary)] mb-2">
                        <i class="fas fa-user mr-2 text-[var(--text-accent)]"></i>用户名或邮箱
                    </label>
                    <input type="text" id="username" name="username" required
                        class="w-full px-4 py-3 rounded-lg border border-[var(--border-color)] bg-[var(--bg-glass)] text-[var(--text-primary)] placeholder-[var(--text-secondary)] focus:ring-2 focus:ring-[var(--text-accent)] focus:border-transparent transition-all"
                        placeholder="请输入用户名或邮箱"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-[var(--text-primary)] mb-2">
                        <i class="fas fa-lock mr-2 text-[var(--text-accent)]"></i>密码
                    </label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required
                            class="w-full px-4 py-3 rounded-lg border border-[var(--border-color)] bg-[var(--bg-glass)] text-[var(--text-primary)] placeholder-[var(--text-secondary)] focus:ring-2 focus:ring-[var(--text-accent)] focus:border-transparent transition-all pr-12"
                            placeholder="请输入密码">
                        <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <i id="password-toggle-icon" class="fas fa-eye text-[var(--text-secondary)] hover:text-[var(--text-accent)] transition-colors"></i>
                        </button>
                    </div>
                </div>
                
                <!-- 记住我和忘记密码 -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember" name="remember" type="checkbox" 
                            class="h-4 w-4 text-[var(--text-accent)] focus:ring-[var(--text-accent)] border-[var(--border-color)] rounded">
                        <label for="remember" class="ml-2 block text-sm text-[var(--text-secondary)]">
                            记住我
                        </label>
                    </div>
                    
                    <div class="text-sm">
                        <a href="/forgot-password.php" class="text-[var(--text-accent)] hover:underline">
                            忘记密码？
                        </a>
                    </div>
                </div>
                
                <!-- 登录按钮 -->
                <button type="submit" 
                    class="w-full btn-primary py-4 text-lg font-semibold rounded-lg flex items-center justify-center">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    登录账户
                </button>
            </form>
            
            <!-- 分隔线 -->
            <div class="my-8">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-[var(--border-color)]"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-[var(--bg-glass)] text-[var(--text-secondary)]">还没有账户？</span>
                    </div>
                </div>
            </div>
            
            <!-- 注册链接 -->
            <div class="text-center">
                <a href="/register.php" 
                    class="inline-flex items-center px-6 py-3 border border-[var(--border-color)] text-[var(--text-accent)] rounded-lg hover:bg-[var(--bg-glass)] transition-all">
                    <i class="fas fa-user-plus mr-2"></i>
                    立即注册
                </a>
            </div>
            
            <!-- 返回链接 -->
            <div class="text-center mt-6">
                <a href="/index.php" class="text-sm text-[var(--text-secondary)] hover:text-[var(--text-accent)] transition-colors">
                    <i class="fas fa-arrow-left mr-1"></i>返回首页
                </a>
            </div>
        </div>
        
        <!-- 功能说明 -->
        <div class="mt-8 text-center">
            <div class="glass-effect p-6 fade-in">
                <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-4">为什么选择PlanWise AI？</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div class="flex items-center text-[var(--text-secondary)]">
                        <i class="fas fa-zap text-[var(--text-accent)] mr-2"></i>
                        快速分析
                    </div>
                    <div class="flex items-center text-[var(--text-secondary)]">
                        <i class="fas fa-shield-alt text-[var(--text-accent)] mr-2"></i>
                        安全可靠
                    </div>
                    <div class="flex items-center text-[var(--text-secondary)]">
                        <i class="fas fa-chart-line text-[var(--text-accent)] mr-2"></i>
                        专业报告
                    </div>
                    <div class="flex items-center text-[var(--text-secondary)]">
                        <i class="fas fa-clock text-[var(--text-accent)] mr-2"></i>
                        24/7服务
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('password-toggle-icon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

// 表单增强
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const submitButton = form.querySelector('button[type="submit"]');
    
    form.addEventListener('submit', function() {
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>登录中...';
        
        // 防止重复提交后重置按钮状态
        setTimeout(() => {
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="fas fa-sign-in-alt mr-2"></i>登录账户';
        }, 5000);
    });
    
    // 输入框焦点效果
    const inputs = form.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentNode.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.parentNode.classList.remove('focused');
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
