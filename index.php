<?php
// /www/wwwroot/planwise.maxcaulfield.cn/index.php
// 同步主站blog.php的样式风格

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_connect.php';
$pdo = getPDO();

// SEO设置
$page_actual_title = 'PlanWise AI - 商业策略智能体 | 让AI助力您的商业决策';
$meta_description = '欢迎使用PlanWise AI商业策略智能体！我们运用先进的人工智能技术，帮您快速生成专业的商业分析报告，涵盖市场调研、竞品分析、风险评估、财务预测等多个维度，是您商业决策的得力助手。';
$meta_keywords = 'AI商业分析,商业策略,市场调研,竞品分析,商业计划书,创业助手,PlanWise,人工智能,商业决策';

require_once __DIR__ . '/includes/header.php';
?>

<main class="container mx-auto px-4 sm:px-6 lg:px-8 mt-8 mb-8">
    <!-- 英雄区域 -->
    <section class="text-center py-16 stagger-fade">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-4xl md:text-6xl font-bold mb-6 fade-in">
                <span class="text-gradient">PlanWise AI</span>
            </h1>
            <h2 class="text-xl md:text-2xl text-[var(--text-secondary)] mb-8 fade-in">
                商业策略智能体 - 让AI助力您的商业决策
            </h2>
            <p class="text-lg text-[var(--text-secondary)] mb-12 max-w-2xl mx-auto leading-relaxed fade-in">
                运用先进的人工智能技术，快速生成专业的商业分析报告。从市场调研到风险评估，从财务预测到营销策略，一站式解决您的商业决策难题。
            </p>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center fade-in">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/report.php" class="btn-primary px-8 py-4 text-lg font-semibold rounded-xl">
                        <i class="fas fa-rocket mr-3"></i>立即开始分析
                    </a>
                    <a href="/dashboard.php" class="px-8 py-4 text-lg font-semibold rounded-xl border border-[var(--border-color)] text-[var(--text-accent)] hover:bg-[var(--bg-glass)] transition-all">
                        <i class="fas fa-tachometer-alt mr-3"></i>查看我的报告
                    </a>
                <?php else: ?>
                    <a href="/register.php" class="btn-primary px-8 py-4 text-lg font-semibold rounded-xl">
                        <i class="fas fa-user-plus mr-3"></i>立即注册
                    </a>
                    <a href="/login.php" class="px-8 py-4 text-lg font-semibold rounded-xl border border-[var(--border-color)] text-[var(--text-accent)] hover:bg-[var(--bg-glass)] transition-all">
                        <i class="fas fa-sign-in-alt mr-3"></i>登录账户
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- 功能特性 -->
    <section class="py-16">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold mb-4 text-gradient">核心功能</h2>
            <p class="text-lg text-[var(--text-secondary)] max-w-2xl mx-auto">
                体验AI驱动的商业分析，让复杂的商业决策变得简单高效
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 stagger-fade">
            <div class="glass-effect p-8 text-center hover:scale-105 transition-all duration-300 fade-in">
                <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-chart-bar text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-semibold mb-4 text-[var(--text-primary)]">市场环境分析</h3>
                <p class="text-[var(--text-secondary)] leading-relaxed">
                    深度分析目标市场规模、发展趋势、用户需求等关键指标，为您的商业决策提供数据支撑。
                </p>
            </div>

            <div class="glass-effect p-8 text-center hover:scale-105 transition-all duration-300 fade-in">
                <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-teal-600 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-users text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-semibold mb-4 text-[var(--text-primary)]">竞争对手研究</h3>
                <p class="text-[var(--text-secondary)] leading-relaxed">
                    全面分析主要竞争对手的产品特色、市场策略、优劣势，助您制定差异化竞争策略。
                </p>
            </div>

            <div class="glass-effect p-8 text-center hover:scale-105 transition-all duration-300 fade-in">
                <div class="w-16 h-16 bg-gradient-to-r from-orange-500 to-red-600 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-user-tie text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-semibold mb-4 text-[var(--text-primary)]">目标用户画像</h3>
                <p class="text-[var(--text-secondary)] leading-relaxed">
                    精准构建目标用户画像，深入了解用户行为、偏好和需求，提高产品市场匹配度。
                </p>
            </div>

            <div class="glass-effect p-8 text-center hover:scale-105 transition-all duration-300 fade-in">
                <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-pink-600 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-cogs text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-semibold mb-4 text-[var(--text-primary)]">商业模式设计</h3>
                <p class="text-[var(--text-secondary)] leading-relaxed">
                    设计可持续的盈利模式，分析收入来源、成本结构，确保商业模式的可行性和盈利性。
                </p>
            </div>

            <div class="glass-effect p-8 text-center hover:scale-105 transition-all duration-300 fade-in">
                <div class="w-16 h-16 bg-gradient-to-r from-yellow-500 to-orange-600 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-exclamation-triangle text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-semibold mb-4 text-[var(--text-primary)]">风险评估</h3>
                <p class="text-[var(--text-secondary)] leading-relaxed">
                    识别潜在的市场风险、技术风险、财务风险等，提供风险规避和应对策略建议。
                </p>
            </div>

            <div class="glass-effect p-8 text-center hover:scale-105 transition-all duration-300 fade-in">
                <div class="w-16 h-16 bg-gradient-to-r from-indigo-500 to-blue-600 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-calculator text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-semibold mb-4 text-[var(--text-primary)]">财务预测</h3>
                <p class="text-[var(--text-secondary)] leading-relaxed">
                    基于市场数据和商业模式，进行科学的财务建模和预测，规划资金需求和回报预期。
                </p>
            </div>
        </div>
    </section>

    <!-- 使用流程 -->
    <section class="py-16">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold mb-4 text-gradient">简单三步，生成专业报告</h2>
            <p class="text-lg text-[var(--text-secondary)] max-w-2xl mx-auto">
                无需复杂操作，只需简单描述您的商业想法，AI即可为您生成全面的分析报告
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 stagger-fade">
            <div class="text-center fade-in">
                <div class="w-20 h-20 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-6 relative">
                    <span class="text-3xl font-bold text-white">1</span>
                    <div class="absolute -top-2 -right-2 w-8 h-8 bg-yellow-400 rounded-full flex items-center justify-center">
                        <i class="fas fa-star text-sm text-yellow-800"></i>
                    </div>
                </div>
                <h3 class="text-xl font-semibold mb-4 text-[var(--text-primary)]">描述商业想法</h3>
                <p class="text-[var(--text-secondary)] leading-relaxed">
                    简单描述您的产品或服务理念，目标市场和核心价值主张，让AI了解您的商业方向。
                </p>
            </div>

            <div class="text-center fade-in">
                <div class="w-20 h-20 bg-gradient-to-r from-green-500 to-teal-600 rounded-full flex items-center justify-center mx-auto mb-6 relative">
                    <span class="text-3xl font-bold text-white">2</span>
                    <div class="absolute -top-2 -right-2 w-8 h-8 bg-yellow-400 rounded-full flex items-center justify-center">
                        <i class="fas fa-cog fa-spin text-sm text-yellow-800"></i>
                    </div>
                </div>
                <h3 class="text-xl font-semibold mb-4 text-[var(--text-primary)]">AI智能分析</h3>
                <p class="text-[var(--text-secondary)] leading-relaxed">
                    AI开始工作，从多个维度深入分析您的商业想法，生成全面的商业策略建议。
                </p>
            </div>

            <div class="text-center fade-in">
                <div class="w-20 h-20 bg-gradient-to-r from-orange-500 to-red-600 rounded-full flex items-center justify-center mx-auto mb-6 relative">
                    <span class="text-3xl font-bold text-white">3</span>
                    <div class="absolute -top-2 -right-2 w-8 h-8 bg-yellow-400 rounded-full flex items-center justify-center">
                        <i class="fas fa-check text-sm text-yellow-800"></i>
                    </div>
                </div>
                <h3 class="text-xl font-semibold mb-4 text-[var(--text-primary)]">获取专业报告</h3>
                <p class="text-[var(--text-secondary)] leading-relaxed">
                    获得结构化的商业分析报告，包含市场分析、竞品研究、风险评估等专业内容。
                </p>
            </div>
        </div>
    </section>

    <!-- 优势特色 -->
    <section class="py-16">
        <div class="glass-effect p-12 text-center">
            <h2 class="text-3xl md:text-4xl font-bold mb-8 text-gradient">为什么选择PlanWise AI？</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 stagger-fade">
                <div class="fade-in">
                    <div class="text-4xl mb-4">⚡</div>
                    <h3 class="text-lg font-semibold mb-2 text-[var(--text-primary)]">快速高效</h3>
                    <p class="text-sm text-[var(--text-secondary)]">几分钟内生成专业报告，大幅节省您的时间</p>
                </div>
                <div class="fade-in">
                    <div class="text-4xl mb-4">🎯</div>
                    <h3 class="text-lg font-semibold mb-2 text-[var(--text-primary)]">专业精准</h3>
                    <p class="text-sm text-[var(--text-secondary)]">基于大量商业案例训练，分析结果专业可靠</p>
                </div>
                <div class="fade-in">
                    <div class="text-4xl mb-4">🔄</div>
                    <h3 class="text-lg font-semibold mb-2 text-[var(--text-primary)]">持续优化</h3>
                    <p class="text-sm text-[var(--text-secondary)]">AI模型持续学习更新，分析能力不断提升</p>
                </div>
                <div class="fade-in">
                    <div class="text-4xl mb-4">💡</div>
                    <h3 class="text-lg font-semibold mb-2 text-[var(--text-primary)]">创新洞察</h3>
                    <p class="text-sm text-[var(--text-secondary)]">发现人工分析可能忽略的商业机会和风险</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 立即开始 -->
    <section class="py-16 text-center">
        <div class="max-w-3xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold mb-6 text-gradient">准备好开始了吗？</h2>
            <p class="text-lg text-[var(--text-secondary)] mb-8">
                加入成千上万的创业者和企业决策者，体验AI驱动的商业分析魅力
            </p>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/report.php" class="btn-primary px-12 py-4 text-xl font-semibold rounded-xl inline-flex items-center">
                    <i class="fas fa-rocket mr-3"></i>立即生成商业报告
                </a>
            <?php else: ?>
                <a href="/register.php" class="btn-primary px-12 py-4 text-xl font-semibold rounded-xl inline-flex items-center">
                    <i class="fas fa-user-plus mr-3"></i>免费开始使用
                </a>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<style>
    /* 亮色主题首页特定优化 */
    .light-theme .hero-section {
        background: linear-gradient(135deg, 
            rgba(255, 255, 255, 0.9) 0%, 
            rgba(248, 250, 252, 0.8) 50%, 
            rgba(241, 245, 249, 0.7) 100%);
        backdrop-filter: blur(10px);
    }
    
    /* 亮色主题功能卡片渐变背景 */
    .light-theme .feature-card-1 .w-16 { background: linear-gradient(135deg, #0891b2, #0f766e) !important; }
    .light-theme .feature-card-2 .w-16 { background: linear-gradient(135deg, #059669, #047857) !important; }
    .light-theme .feature-card-3 .w-16 { background: linear-gradient(135deg, #dc2626, #b91c1c) !important; }
    .light-theme .feature-card-4 .w-16 { background: linear-gradient(135deg, #7c3aed, #6d28d9) !important; }
    .light-theme .feature-card-5 .w-16 { background: linear-gradient(135deg, #ea580c, #dc2626) !important; }
    .light-theme .feature-card-6 .w-16 { background: linear-gradient(135deg, #4338ca, #3730a3) !important; }
    
    /* 亮色主题步骤圆圈 */
    .light-theme .step-circle-1 { background: linear-gradient(135deg, #0891b2, #0f766e) !important; }
    .light-theme .step-circle-2 { background: linear-gradient(135deg, #059669, #047857) !important; }
    .light-theme .step-circle-3 { background: linear-gradient(135deg, #dc2626, #b91c1c) !important; }
    
    /* 亮色主题优势卡片背景 */
    .light-theme .advantage-section {
        background: linear-gradient(135deg, 
            rgba(255, 255, 255, 0.95) 0%, 
            rgba(248, 250, 252, 0.9) 100%);
        border: 1px solid rgba(203, 213, 225, 0.3);
    }
    
    /* 亮色主题文字阴影优化 */
    .light-theme h1, .light-theme h2, .light-theme h3 {
        text-shadow: 0 1px 2px rgba(148, 163, 184, 0.1);
    }
    
    /* 亮色主题按钮阴影增强 */
    .light-theme .btn-primary {
        box-shadow: 0 4px 15px rgba(15, 118, 110, 0.25), 
                    0 2px 8px rgba(15, 118, 110, 0.15),
                    0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .light-theme .btn-primary:hover {
        box-shadow: 0 8px 25px rgba(15, 118, 110, 0.35),
                    0 4px 15px rgba(15, 118, 110, 0.2),
                    0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    /* 亮色主题卡片边框优化 */
    .light-theme .glass-effect {
        border: 1px solid rgba(226, 232, 240, 0.6);
        box-shadow: 0 4px 20px rgba(148, 163, 184, 0.08), 
                    0 1px 3px rgba(148, 163, 184, 0.06);
    }
    
    /* 亮色主题悬停状态增强 */
    .light-theme .glass-effect:hover {
        border: 1px solid rgba(15, 118, 110, 0.15);
        box-shadow: 0 8px 30px rgba(148, 163, 184, 0.12), 
                    0 2px 6px rgba(148, 163, 184, 0.08);
        transform: translateY(-3px);
    }
    
    /* 亮色主题emoji增强 */
    .light-theme .emoji {
        filter: contrast(1.1) saturate(1.2);
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 为功能卡片添加类名以便样式定制
    const featureCards = document.querySelectorAll('.glass-effect');
    if (featureCards.length >= 6) {
        featureCards[1]?.classList.add('feature-card-1'); // 市场环境分析
        featureCards[2]?.classList.add('feature-card-2'); // 竞争对手研究
        featureCards[3]?.classList.add('feature-card-3'); // 目标用户画像
        featureCards[4]?.classList.add('feature-card-4'); // 商业模式设计
        featureCards[5]?.classList.add('feature-card-5'); // 风险评估
        featureCards[6]?.classList.add('feature-card-6'); // 财务预测
    }
    
    // 为步骤圆圈添加类名
    const stepCircles = document.querySelectorAll('.w-20.h-20.bg-gradient-to-r');
    stepCircles.forEach((circle, index) => {
        circle.classList.add(`step-circle-${index + 1}`);
    });
    
    // 为优势部分添加类名
    const advantageSection = document.querySelector('.glass-effect.p-12.text-center');
    if (advantageSection) {
        advantageSection.classList.add('advantage-section');
    }
    
    // 为emoji添加增强类
    const emojis = document.querySelectorAll('.text-4xl');
    emojis.forEach(emoji => {
        emoji.classList.add('emoji');
    });
});
</script>
