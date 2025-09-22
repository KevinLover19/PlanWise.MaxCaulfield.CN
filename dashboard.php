<?php
// /www/wwwroot/planwise.maxcaulfield.cn/dashboard.php
// 同步主站blog.php的样式风格

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_connect.php';
$pdo = getPDO();

// 检查用户是否已登录
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// 获取用户信息
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        session_destroy();
        header('Location: /login.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Dashboard user query failed: " . $e->getMessage());
    $user = ['username' => '未知用户', 'email' => '', 'created_at' => date('Y-m-d H:i:s')];
}

// 获取用户的报告列表
try {
    $stmt = $pdo->prepare("SELECT * FROM reports WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$user_id]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Dashboard reports query failed: " . $e->getMessage());
    $reports = [];
}

// 统计数据
$total_reports = count($reports);
$recent_reports = array_slice($reports, 0, 5);

// 获取活跃公告
try {
    // 获取用户会员类型
    $stmt = $pdo->prepare("SELECT COALESCE(q.membership_type, 'free') as membership_type FROM users u LEFT JOIN planwise_user_quotas q ON u.id = q.user_id WHERE u.id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $user_membership = $user_data ? $user_data['membership_type'] : 'free';
    
    // 获取活跃公告
    $stmt = $pdo->prepare("SELECT id, title, content, announcement_type, priority, created_at FROM planwise_announcements WHERE is_active = 1 AND (end_date IS NULL OR end_date > NOW()) AND (target_audience = 'all' OR target_audience = ?) ORDER BY priority DESC, created_at DESC LIMIT 5");
    $stmt->execute([$user_membership]);
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Dashboard announcements query failed: " . $e->getMessage());
    $announcements = [];
}

// SEO设置
$page_actual_title = '我的控制台 - PlanWise AI';
$meta_description = 'PlanWise用户控制台，管理您的商业策略分析报告，查看生成历史和账户信息。快速访问和管理您的AI商业分析结果。';
$meta_keywords = '用户控制台,报告管理,PlanWise,商业分析报告,AI分析';

require_once __DIR__ . '/includes/header.php';
?>

<main class="container mx-auto px-4 sm:px-6 lg:px-8 mt-8 mb-8">
    <!-- 页面标题 -->
    <div class="text-center mb-12 stagger-fade">
        <h1 class="text-4xl md:text-5xl font-bold mb-6 fade-in">
            <span class="text-gradient">我的控制台</span>
        </h1>
        <p class="text-lg md:text-xl text-[var(--text-secondary)] max-w-2xl mx-auto fade-in">
            管理您的商业策略分析报告，查看生成历史和账户信息
        </p>
    </div>

    <!-- 公告区域 -->
    <?php if (!empty($announcements)): ?>
    <div class="mb-12 stagger-fade">
        <div class="glass-effect p-6 rounded-2xl">
            <div class="flex items-center mb-6">
                <div class="p-3 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg mr-4">
                    <i class="fas fa-bullhorn text-white text-xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-[var(--text-primary)]">系统公告</h2>
            </div>
            
            <div class="space-y-4">
                <?php foreach ($announcements as $announcement): ?>
                    <?php 
                    $typeColors = [
                        'info' => 'from-blue-500 to-blue-600',
                        'success' => 'from-green-500 to-green-600', 
                        'warning' => 'from-yellow-500 to-orange-500',
                        'error' => 'from-red-500 to-red-600'
                    ];
                    $typeIcons = [
                        'info' => 'fa-info-circle',
                        'success' => 'fa-check-circle',
                        'warning' => 'fa-exclamation-triangle', 
                        'error' => 'fa-times-circle'
                    ];
                    $bgColor = $typeColors[$announcement['announcement_type']] ?? $typeColors['info'];
                    $iconClass = $typeIcons[$announcement['announcement_type']] ?? $typeIcons['info'];
                    ?>
                    <div class="announcement-item p-5 rounded-xl border border-[var(--border-color)] bg-[var(--bg-secondary)]">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0 p-2 bg-gradient-to-r <?php echo $bgColor; ?> rounded-lg">
                                <i class="fas <?php echo $iconClass; ?> text-white"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-2">
                                    <?php echo htmlspecialchars($announcement['title']); ?>
                                </h3>
                                <p class="text-[var(--text-primary)] mb-3 leading-relaxed announcement-content">
                                    <?php echo strip_tags($announcement['content'], '<p><strong><b><ul><li><br><em><i>'); ?>
                                </p>
                                <div class="text-sm text-[var(--text-tertiary)]">
                                    📅 发布于 <?php echo date('Y年m月d日 H:i', strtotime($announcement['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- 主要内容区域 -->
        <div class="lg:col-span-2 space-y-8">
            <!-- 快速操作 -->
            <div class="glass-effect p-8 fade-in">
                <div class="flex items-center mb-6">
                    <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-bolt text-white text-xl"></i>
                    </div>
                    <h2 class="text-2xl md:text-3xl font-semibold text-[var(--text-primary)]">快速操作</h2>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <a href="/report.php" class="group flex items-center p-6 rounded-xl border border-[var(--border-color)] hover:border-[var(--text-accent)] transition-all duration-300 hover:scale-105">
                        <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-teal-600 rounded-full flex items-center justify-center mr-4 group-hover:scale-110 transition-transform">
                            <i class="fas fa-plus text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-[var(--text-primary)] group-hover:text-[var(--text-accent)] transition-colors">创建新报告</h3>
                            <p class="text-sm text-[var(--text-secondary)]">生成新的商业策略分析</p>
                        </div>
                    </a>
                    
                    <a href="/introduction.php" class="group flex items-center p-6 rounded-xl border border-[var(--border-color)] hover:border-[var(--text-accent)] transition-all duration-300 hover:scale-105">
                        <div class="w-12 h-12 bg-gradient-to-r from-orange-500 to-red-600 rounded-full flex items-center justify-center mr-4 group-hover:scale-110 transition-transform">
                            <i class="fas fa-info-circle text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-[var(--text-primary)] group-hover:text-[var(--text-accent)] transition-colors">了解功能</h3>
                            <p class="text-sm text-[var(--text-secondary)]">查看详细功能介绍</p>
                        </div>
                    </a>
                </div>
            </div>
            
            <!-- 我的报告 -->
            <div class="glass-effect p-8 fade-in">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-pink-600 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-chart-line text-white text-xl"></i>
                        </div>
                        <h2 class="text-2xl md:text-3xl font-semibold text-[var(--text-primary)]">我的报告</h2>
                    </div>
                    <div class="text-sm text-[var(--text-secondary)]">
                        共 <?php echo $total_reports; ?> 份报告
                    </div>
                </div>
                
                <?php if (empty($reports)): ?>
                    <div class="text-center py-12">
                        <div class="w-20 h-20 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-file-alt text-3xl text-[var(--text-secondary)]"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-[var(--text-primary)] mb-2">还没有报告</h3>
                        <p class="text-[var(--text-secondary)] mb-6">创建您的第一份商业策略分析报告</p>
                        <a href="/report.php" class="btn-primary px-8 py-3 text-lg font-semibold rounded-xl inline-flex items-center">
                            <i class="fas fa-plus mr-2"></i>创建报告
                        </a>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($recent_reports as $report): ?>
                            <div class="flex items-center justify-between p-6 rounded-xl border border-[var(--border-color)] hover:border-[var(--text-accent)] transition-all duration-300 hover:bg-[var(--bg-secondary)]">
                                <div class="flex items-center flex-1">
                                    <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-teal-500 rounded-full flex items-center justify-center mr-4">
                                        <i class="fas fa-file-chart-line text-white"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-[var(--text-primary)] mb-1">
                                            <?php echo htmlspecialchars($report['business_name'] ?? '商业策略报告'); ?>
                                        </h3>
                                        <div class="flex items-center text-sm text-[var(--text-secondary)] space-x-4">
                                            <span><i class="fas fa-calendar mr-1"></i><?php echo date('Y-m-d H:i', strtotime($report['created_at'])); ?></span>
                                            <span><i class="fas fa-industry mr-1"></i><?php echo htmlspecialchars($report['industry'] ?? '未知行业'); ?></span>
                                            <span class="px-2 py-1 bg-[var(--text-accent)] text-white rounded text-xs">
                                                <?php echo ucfirst($report['status'] ?? 'completed'); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <a href="/view_report.php?id=<?php echo $report['id']; ?>" 
                                       class="px-4 py-2 bg-[var(--text-accent)] text-white rounded-lg hover:opacity-90 transition-opacity">
                                        <i class="fas fa-eye mr-1"></i>查看
                                    </a>
                                    <button onclick="deleteReport(<?php echo $report['id']; ?>)" 
                                            class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                                        <i class="fas fa-trash mr-1"></i>删除
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($total_reports > 5): ?>
                        <div class="text-center mt-6">
                            <button onclick="loadMoreReports()" class="px-6 py-3 border border-[var(--border-color)] text-[var(--text-accent)] rounded-lg hover:bg-[var(--bg-glass)] transition-all">
                                <i class="fas fa-chevron-down mr-2"></i>查看更多报告
                            </button>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 侧边栏 -->
        <div class="lg:col-span-1 space-y-8">
            <!-- 用户信息 -->
            <div class="glass-effect p-8 fade-in">
                <div class="flex items-center mb-6">
                    <div class="w-12 h-12 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-user text-white text-xl"></i>
                    </div>
                    <h2 class="text-xl font-semibold text-[var(--text-primary)]">账户信息</h2>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-[var(--text-secondary)]">用户名</label>
                        <div class="text-[var(--text-primary)] font-medium"><?php echo htmlspecialchars($user['username'] ?? ''); ?></div>
                    </div>
                    
                    <div>
                        <label class="text-sm font-medium text-[var(--text-secondary)]">邮箱</label>
                        <div class="text-[var(--text-primary)] font-medium"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
                    </div>
                    
                    <div>
                        <label class="text-sm font-medium text-[var(--text-secondary)]">注册时间</label>
                        <div class="text-[var(--text-primary)] font-medium"><?php echo date('Y年m月d日', strtotime($user['created_at'])); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- 统计信息 -->
            <div class="glass-effect p-8 fade-in">
                <div class="flex items-center mb-6">
                    <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-blue-500 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-chart-bar text-white text-xl"></i>
                    </div>
                    <h2 class="text-xl font-semibold text-[var(--text-primary)]">使用统计</h2>
                </div>
                
                <div class="grid grid-cols-1 gap-4">
                    <div class="text-center p-4 rounded-lg bg-[var(--bg-secondary)]">
                        <div class="text-2xl font-bold text-[var(--text-accent)] mb-1"><?php echo $total_reports; ?></div>
                        <div class="text-sm text-[var(--text-secondary)]">生成报告</div>
                    </div>
                    
                    <div class="text-center p-4 rounded-lg bg-[var(--bg-secondary)]">
                        <div class="text-2xl font-bold text-[var(--text-accent)] mb-1">
                            <?php echo date_diff(date_create($user['created_at']), date_create())->days; ?>
                        </div>
                        <div class="text-sm text-[var(--text-secondary)]">使用天数</div>
                    </div>
                </div>
            </div>
            
            <!-- 快速链接 -->
            <div class="glass-effect p-8 fade-in">
                <div class="flex items-center mb-6">
                    <div class="w-12 h-12 bg-gradient-to-r from-yellow-500 to-orange-500 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-link text-white text-xl"></i>
                    </div>
                    <h2 class="text-xl font-semibold text-[var(--text-primary)]">快速链接</h2>
                </div>
                
                <div class="space-y-3">
                    <a href="/introduction.php" class="flex items-center text-[var(--text-secondary)] hover:text-[var(--text-accent)] transition-colors">
                        <i class="fas fa-info-circle mr-3"></i>功能介绍
                    </a>
                    <a href="https://maxcaulfield.cn/blog.php" class="flex items-center text-[var(--text-secondary)] hover:text-[var(--text-accent)] transition-colors">
                        <i class="fas fa-external-link-alt mr-3"></i>返回主站
                    </a>
                    <a href="/logout.php" class="flex items-center text-[var(--text-secondary)] hover:text-red-500 transition-colors">
                        <i class="fas fa-sign-out-alt mr-3"></i>退出登录
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 删除报告功能
    window.deleteReport = function(reportId) {
        if (confirm('确定要删除这份报告吗？此操作不可撤销。')) {
            fetch('/api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'delete_report',
                    report_id: reportId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('删除失败：' + (data.message || '未知错误'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('删除失败，请稍后重试');
            });
        }
    };
    
    // 加载更多报告功能
    window.loadMoreReports = function() {
        // 实现分页加载逻辑
        window.location.href = '/reports.php'; // 跳转到完整报告列表页面
    };
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
