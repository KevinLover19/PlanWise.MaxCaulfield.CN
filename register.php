<?php
// /www/wwwroot/planwise.maxcaulfield.cn/register.php
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

// 处理注册请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // 基本验证
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = '请填写所有必填字段';
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $error_message = '用户名长度必须在3-20个字符之间';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = '请输入有效的邮箱地址';
    } elseif (strlen($password) < 6) {
        $error_message = '密码长度至少6位';
    } elseif ($password !== $confirm_password) {
        $error_message = '两次输入的密码不一致';
    } else {
        try {
            // 检查用户名是否已存在
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $error_message = '该用户名已被使用';
            } else {
                // 检查邮箱是否已存在
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetchColumn() > 0) {
                    $error_message = '该邮箱已被注册';
                } else {
                    // 创建新用户
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, created_at) VALUES (?, ?, ?, NOW())");
                    
                    if ($stmt->execute([$username, $email, $password_hash])) {
                        $user_id = $pdo->lastInsertId();
                        
                        // 自动登录
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['username'] = $username;
                        $_SESSION['email'] = $email;
                        
                        header('Location: /dashboard.php?welcome=1');
                        exit();
                    } else {
                        $error_message = '注册失败，请稍后重试';
                    }
                }
            }
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $error_message = '注册失败，请稍后重试';
        }
    }
}

// SEO设置
$page_actual_title = '用户注册 - PlanWise AI';
$meta_description = '注册PlanWise AI账户，开始您的AI驱动商业策略分析之旅。免费使用先进的人工智能技术生成专业的商业分析报告。';
$meta_keywords = '注册,PlanWise,AI商业分析,免费注册,商业策略,人工智能';

require_once __DIR__ . '/includes/header.php';
?>

<main class="container mx-auto px-4 sm:px-6 lg:px-8 mt-8 mb-8 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md">
        <!-- 注册卡片 -->
        <div class="glass-effect p-8 md:p-10 fade-in">
            <!-- Logo 和标题 -->
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-user-plus text-2xl text-white"></i>
                </div>
                <h1 class="text-3xl font-bold mb-2">
                    <span class="text-gradient">加入PlanWise</span>
                </h1>
                <p class="text-[var(--text-secondary)]">
                    创建您的AI商业策略助手账户
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
            
            <!-- 注册表单 -->
            <form method="POST" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-[var(--text-primary)] mb-2">
                        <i class="fas fa-user mr-2 text-[var(--text-accent)]"></i>用户名 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="username" name="username" required
                        class="w-full px-4 py-3 rounded-lg border border-[var(--border-color)] bg-[var(--bg-glass)] text-[var(--text-primary)] placeholder-[var(--text-secondary)] focus:ring-2 focus:ring-[var(--text-accent)] focus:border-transparent transition-all"
                        placeholder="请输入用户名 (3-20个字符)"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-[var(--text-primary)] mb-2">
                        <i class="fas fa-envelope mr-2 text-[var(--text-accent)]"></i>邮箱地址 <span class="text-red-500">*</span>
                    </label>
                    <input type="email" id="email" name="email" required
                        class="w-full px-4 py-3 rounded-lg border border-[var(--border-color)] bg-[var(--bg-glass)] text-[var(--text-primary)] placeholder-[var(--text-secondary)] focus:ring-2 focus:ring-[var(--text-accent)] focus:border-transparent transition-all"
                        placeholder="请输入邮箱地址"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-[var(--text-primary)] mb-2">
                        <i class="fas fa-lock mr-2 text-[var(--text-accent)]"></i>密码 <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required
                            class="w-full px-4 py-3 rounded-lg border border-[var(--border-color)] bg-[var(--bg-glass)] text-[var(--text-primary)] placeholder-[var(--text-secondary)] focus:ring-2 focus:ring-[var(--text-accent)] focus:border-transparent transition-all pr-12"
                            placeholder="请输入密码 (至少6位)">
                        <button type="button" onclick="togglePassword('password')" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <i id="password-toggle-icon" class="fas fa-eye text-[var(--text-secondary)] hover:text-[var(--text-accent)] transition-colors"></i>
                        </button>
                    </div>
                    <!-- 密码强度指示器 -->
                    <div class="mt-2">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs text-[var(--text-secondary)]">密码强度</span>
                            <span id="strength-text" class="text-xs text-[var(--text-secondary)]">请输入密码</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-1">
                            <div id="strength-bar" class="h-1 rounded-full transition-all duration-300" style="width: 0%;"></div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-[var(--text-primary)] mb-2">
                        <i class="fas fa-lock mr-2 text-[var(--text-accent)]"></i>确认密码 <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="password" id="confirm_password" name="confirm_password" required
                            class="w-full px-4 py-3 rounded-lg border border-[var(--border-color)] bg-[var(--bg-glass)] text-[var(--text-primary)] placeholder-[var(--text-secondary)] focus:ring-2 focus:ring-[var(--text-accent)] focus:border-transparent transition-all pr-12"
                            placeholder="请再次输入密码">
                        <button type="button" onclick="togglePassword('confirm_password')" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <i id="confirm-password-toggle-icon" class="fas fa-eye text-[var(--text-secondary)] hover:text-[var(--text-accent)] transition-colors"></i>
                        </button>
                    </div>
                    <div id="password-match-message" class="text-xs mt-1 hidden"></div>
                </div>
                
                <!-- 服务条款 -->
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input id="agree_terms" name="agree_terms" type="checkbox" required
                            class="h-4 w-4 text-[var(--text-accent)] focus:ring-[var(--text-accent)] border-[var(--border-color)] rounded">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="agree_terms" class="text-[var(--text-secondary)]">
                            我已阅读并同意 
                            <a href="/terms.php" target="_blank" class="text-[var(--text-accent)] hover:underline">服务条款</a>
                            和
                            <a href="/privacy.php" target="_blank" class="text-[var(--text-accent)] hover:underline">隐私政策</a>
                        </label>
                    </div>
                </div>
                
                <!-- 注册按钮 -->
                <button type="submit" id="register-btn"
                    class="w-full btn-primary py-4 text-lg font-semibold rounded-lg flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-user-plus mr-2"></i>
                    <span>立即注册</span>
                </button>
            </form>
            
            <!-- 分隔线 -->
            <div class="my-8">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-[var(--border-color)]"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-[var(--bg-glass)] text-[var(--text-secondary)]">已有账户？</span>
                    </div>
                </div>
            </div>
            
            <!-- 登录链接 -->
            <div class="text-center">
                <a href="/login.php" 
                    class="inline-flex items-center px-6 py-3 border border-[var(--border-color)] text-[var(--text-accent)] rounded-lg hover:bg-[var(--bg-glass)] transition-all">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    立即登录
                </a>
            </div>
            
            <!-- 返回链接 -->
            <div class="text-center mt-6">
                <a href="/index.php" class="text-sm text-[var(--text-secondary)] hover:text-[var(--text-accent)] transition-colors">
                    <i class="fas fa-arrow-left mr-1"></i>返回首页
                </a>
            </div>
        </div>
        
        <!-- 功能特色 -->
        <div class="mt-8">
            <div class="glass-effect p-6 fade-in">
                <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-4 text-center">注册即可享受</h3>
                <div class="space-y-3">
                    <div class="flex items-center text-[var(--text-secondary)]">
                        <i class="fas fa-check-circle text-green-500 mr-3"></i>
                        免费生成商业策略分析报告
                    </div>
                    <div class="flex items-center text-[var(--text-secondary)]">
                        <i class="fas fa-check-circle text-green-500 mr-3"></i>
                        8个维度的专业商业分析
                    </div>
                    <div class="flex items-center text-[var(--text-secondary)]">
                        <i class="fas fa-check-circle text-green-500 mr-3"></i>
                        AI驱动的智能洞察建议
                    </div>
                    <div class="flex items-center text-[var(--text-secondary)]">
                        <i class="fas fa-check-circle text-green-500 mr-3"></i>
                        24/7全天候服务支持
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function togglePassword(fieldId) {
    const passwordInput = document.getElementById(fieldId);
    const toggleIcon = document.getElementById(fieldId + '-toggle-icon');
    
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

// 密码强度检测
function checkPasswordStrength(password) {
    let strength = 0;
    let text = '';
    let color = '';
    
    if (password.length >= 6) strength += 1;
    if (password.length >= 8) strength += 1;
    if (/[a-z]/.test(password)) strength += 1;
    if (/[A-Z]/.test(password)) strength += 1;
    if (/[0-9]/.test(password)) strength += 1;
    if (/[^A-Za-z0-9]/.test(password)) strength += 1;
    
    switch (strength) {
        case 0:
        case 1:
            text = '很弱';
            color = '#ef4444';
            break;
        case 2:
        case 3:
            text = '一般';
            color = '#f97316';
            break;
        case 4:
        case 5:
            text = '较强';
            color = '#eab308';
            break;
        case 6:
            text = '很强';
            color = '#22c55e';
            break;
        default:
            text = '请输入密码';
            color = '#6b7280';
    }
    
    return { strength, text, color };
}

// 表单增强
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const strengthBar = document.getElementById('strength-bar');
    const strengthText = document.getElementById('strength-text');
    const passwordMatchMessage = document.getElementById('password-match-message');
    const form = document.querySelector('form');
    const registerBtn = document.getElementById('register-btn');
    
    // 密码强度检测
    passwordInput.addEventListener('input', function() {
        const result = checkPasswordStrength(this.value);
        const widthPercent = (result.strength / 6) * 100;
        
        strengthBar.style.width = widthPercent + '%';
        strengthBar.style.backgroundColor = result.color;
        strengthText.textContent = result.text;
        strengthText.style.color = result.color;
        
        checkPasswordMatch();
    });
    
    // 密码匹配检测
    function checkPasswordMatch() {
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        
        if (confirmPassword && password !== confirmPassword) {
            passwordMatchMessage.textContent = '密码不匹配';
            passwordMatchMessage.className = 'text-xs mt-1 text-red-500';
            passwordMatchMessage.classList.remove('hidden');
        } else if (confirmPassword && password === confirmPassword) {
            passwordMatchMessage.textContent = '密码匹配';
            passwordMatchMessage.className = 'text-xs mt-1 text-green-500';
            passwordMatchMessage.classList.remove('hidden');
        } else {
            passwordMatchMessage.classList.add('hidden');
        }
    }
    
    confirmPasswordInput.addEventListener('input', checkPasswordMatch);
    
    // 表单提交处理
    form.addEventListener('submit', function(e) {
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('两次输入的密码不一致');
            return false;
        }
        
        if (password.length < 6) {
            e.preventDefault();
            alert('密码长度至少6位');
            return false;
        }
        
        // 显示加载状态
        registerBtn.disabled = true;
        registerBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i><span>注册中...</span>';
        
        // 防止重复提交后重置按钮状态
        setTimeout(() => {
            registerBtn.disabled = false;
            registerBtn.innerHTML = '<i class="fas fa-user-plus mr-2"></i><span>立即注册</span>';
        }, 8000);
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
