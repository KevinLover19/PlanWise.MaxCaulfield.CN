<?php
// /www/wwwroot/planwise.maxcaulfield.cn/view_report.php
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

$report_id = $_GET['id'] ?? null;
if (!$report_id) {
    header('Location: /dashboard.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// 获取报告信息
try {
    $stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ? AND user_id = ?");
    $stmt->execute([$report_id, $user_id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$report) {
        header('Location: /dashboard.php?error=report_not_found');
        exit();
    }
} catch (PDOException $e) {
    error_log("View report error: " . $e->getMessage());
    header('Location: /dashboard.php?error=database_error');
    exit();
}

// SEO设置
$page_actual_title = htmlspecialchars($report['business_name']) . ' - 商业策略分析报告 - PlanWise AI';
$meta_description = '查看 ' . htmlspecialchars($report['business_name']) . ' 的详细商业策略分析报告，包含市场分析、竞争对手研究、用户画像、商业模式等8个维度的专业分析。';
$meta_keywords = '商业策略报告,市场分析,竞争分析,' . htmlspecialchars($report['industry']) . ',PlanWise';

require_once __DIR__ . '/includes/header.php';
?>

<main class="container mx-auto px-4 sm:px-6 lg:px-8 mt-8 mb-8">
    <!-- 返回按钮 -->
    <div class="mb-8 fade-in">
        <a href="/dashboard.php" class="inline-flex items-center text-[var(--text-secondary)] hover:text-[var(--text-accent)] transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>
            返回控制台
        </a>
    </div>
    
    <!-- 报告标题 -->
    <div class="text-center mb-12 stagger-fade">
        <h1 class="text-4xl md:text-5xl font-bold mb-6 fade-in">
            <span class="text-gradient"><?php echo htmlspecialchars($report['business_name']); ?></span>
        </h1>
        <div class="flex items-center justify-center space-x-6 text-sm text-[var(--text-secondary)] fade-in">
            <span><i class="fas fa-industry mr-2"></i><?php echo htmlspecialchars($report['industry']); ?></span>
            <span><i class="fas fa-calendar mr-2"></i><?php echo date('Y年m月d日', strtotime($report['created_at'])); ?></span>
            <span class="px-3 py-1 bg-[var(--text-accent)] text-white rounded-full text-xs">
                <i class="fas fa-check-circle mr-1"></i><?php echo ucfirst($report['status']); ?>
            </span>
        </div>
    </div>
    
    <!-- 报告内容 -->
    <div class="max-w-4xl mx-auto">
        <?php if ($report['status'] === 'completed' && !empty($report['analysis_result'])): ?>
            <!-- 解析并显示分析结果 -->
            <div class="space-y-8">
                <?php
                $analysis_data = json_decode($report['analysis_result'], true);
                if ($analysis_data && is_array($analysis_data)):
                ?>
                    <?php foreach ($analysis_data as $section_key => $section_data): ?>
                        <div class="glass-effect p-8 fade-in">
                            <div class="flex items-center mb-6">
                                <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center mr-4">
                                    <i class="fas fa-chart-line text-white text-xl"></i>
                                </div>
                                <h2 class="text-2xl md:text-3xl font-semibold text-[var(--text-primary)]">
                                    <?php echo htmlspecialchars($section_data['title'] ?? $section_key); ?>
                                </h2>
                            </div>
                            <div class="prose prose-lg max-w-none text-[var(--text-primary)]">
                                <?php if (isset($section_data['content'])): ?>
                                    <?php echo nl2br(htmlspecialchars($section_data['content'])); ?>
                                <?php else: ?>
                                    <?php echo nl2br(htmlspecialchars($section_data)); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- 如果无法解析JSON，显示原始内容 -->
                    <div class="glass-effect p-8 fade-in">
                        <div class="prose prose-lg max-w-none text-[var(--text-primary)]">
                            <pre class="whitespace-pre-wrap"><?php echo htmlspecialchars($report['analysis_result']); ?></pre>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- 报告生成中或出错 -->
            <div class="glass-effect p-12 text-center fade-in">
                <?php if ($report['status'] === 'processing'): ?>
                    <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-6 animate-pulse">
                        <i class="fas fa-cog fa-spin text-2xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-semibold text-[var(--text-primary)] mb-4">报告生成中...</h3>
                    <p class="text-[var(--text-secondary)] mb-8">
                        AI正在为您生成详细的商业策略分析报告，请稍候。
                    </p>
                    <div class="flex justify-center space-x-4">
                        <button onclick="location.reload()" class="btn-primary px-6 py-3 rounded-lg">
                            <i class="fas fa-sync-alt mr-2"></i>刷新页面
                        </button>
                        <a href="/dashboard.php" class="px-6 py-3 border border-[var(--border-color)] text-[var(--text-accent)] rounded-lg hover:bg-[var(--bg-glass)] transition-all">
                            返回控制台
                        </a>
                    </div>
                <?php elseif ($report['status'] === 'failed'): ?>
                    <div class="w-16 h-16 bg-red-500 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-exclamation-triangle text-2xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-semibold text-[var(--text-primary)] mb-4">报告生成失败</h3>
                    <p class="text-[var(--text-secondary)] mb-8">
                        很抱歉，报告生成过程中出现了问题。您可以尝试重新生成。
                    </p>
                    <div class="flex justify-center space-x-4">
                        <a href="/report.php" class="btn-primary px-6 py-3 rounded-lg">
                            <i class="fas fa-plus mr-2"></i>重新生成
                        </a>
                        <a href="/dashboard.php" class="px-6 py-3 border border-[var(--border-color)] text-[var(--text-accent)] rounded-lg hover:bg-[var(--bg-glass)] transition-all">
                            返回控制台
                        </a>
                    </div>
                <?php else: ?>
                    <div class="w-16 h-16 bg-gray-500 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-question text-2xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-semibold text-[var(--text-primary)] mb-4">报告状态未知</h3>
                    <p class="text-[var(--text-secondary)] mb-8">
                        报告状态异常，请联系技术支持或重新生成报告。
                    </p>
                    <div class="flex justify-center space-x-4">
                        <a href="/report.php" class="btn-primary px-6 py-3 rounded-lg">
                            <i class="fas fa-plus mr-2"></i>重新生成
                        </a>
                        <a href="/dashboard.php" class="px-6 py-3 border border-[var(--border-color)] text-[var(--text-accent)] rounded-lg hover:bg-[var(--bg-glass)] transition-all">
                            返回控制台
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- 报告操作 -->
        <?php if ($report['status'] === 'completed'): ?>
            <div class="mt-12 flex justify-center space-x-4 fade-in">
                <button onclick="printReport()" class="px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                    <i class="fas fa-print mr-2"></i>打印报告
                </button>
                <button onclick="downloadReport()" class="px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                    <i class="fas fa-download mr-2"></i>下载PDF
                </button>
                <button onclick="shareReport()" class="px-6 py-3 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition-colors">
                    <i class="fas fa-share mr-2"></i>分享报告
                </button>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
function printReport() {
    window.print();
}

function downloadReport() {
    // 实现PDF下载功能
    alert('PDF下载功能正在开发中...');
}

function shareReport() {
    if (navigator.share) {
        navigator.share({
            title: '<?php echo htmlspecialchars($report['business_name']); ?> - 商业策略分析报告',
            text: '查看这份由PlanWise AI生成的专业商业策略分析报告',
            url: window.location.href
        });
    } else {
        // 复制链接到剪贴板
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert('报告链接已复制到剪贴板！');
        }).catch(() => {
            alert('无法复制链接，请手动复制地址栏中的链接');
        });
    }
}

// 自动刷新处理中的报告
<?php if ($report['status'] === 'processing'): ?>
    setTimeout(() => {
        location.reload();
    }, 30000); // 30秒后自动刷新
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
