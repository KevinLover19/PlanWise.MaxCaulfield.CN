<?php
// /www/wwwroot/planwise.maxcaulfield.cn/introduction.php
// PlanWise AI 产品介绍页面 - 全面介绍产品功能、使用流程和价值体验

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_connect.php';
$pdo = getPDO();

// SEO设置
$page_actual_title = 'PlanWise AI 产品介绍 - AI驱动的商业策略分析平台 | 深度了解产品功能';
$meta_description = '深度了解PlanWise AI商业策略分析平台的核心功能、工作原理和价值优势。通过AI思考过程可视化、智能分析引擎、多维度商业洞察，帮助创业者和企业制定专业的商业策略。';
$meta_keywords = 'PlanWise AI,产品介绍,AI商业分析,商业策略,市场分析,竞品研究,用户画像,财务预测,风险评估,产品功能';

require_once __DIR__ . '/includes/header.php';

// 检查用户登录状态
$is_logged_in = isset($_SESSION['user_id']);
?>

<main class="container mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Hero Section - 产品核心价值介绍 -->
    <section class="text-center py-20 stagger-fade">
        <div class="max-w-5xl mx-auto">
            <div class="inline-flex items-center mb-8 px-6 py-3 bg-gradient-to-r from-blue-500/20 to-purple-600/20 rounded-full border border-blue-400/30 fade-in">
                <i class="fas fa-brain text-blue-400 mr-3 text-lg"></i>
                <span class="text-blue-300 font-semibold">AI驱动的商业策略分析平台</span>
            </div>
            
            <h1 class="text-4xl md:text-6xl font-bold mb-8 fade-in">
                <span class="text-gradient">让AI成为您的</span><br>
                <span class="text-[var(--text-primary)]">商业策略顾问</span>
            </h1>
            
            <p class="text-xl md:text-2xl text-[var(--text-secondary)] mb-8 max-w-4xl mx-auto leading-relaxed fade-in">
                PlanWise AI是一个革命性的商业策略分析平台，将复杂的商业分析过程简化为简单的对话，通过先进的AI技术为您提供专业级别的商业洞察和策略建议。
            </p>
            
            <div class="flex flex-col sm:flex-row gap-6 justify-center items-center mb-16 fade-in">
                <?php if ($is_logged_in): ?>
                    <a href="/report.php" class="btn-primary px-8 py-4 text-lg font-semibold rounded-xl">
                        <i class="fas fa-rocket mr-3"></i>立即创建分析报告
                    </a>
                    <a href="/dashboard.php" class="px-8 py-4 text-lg font-semibold rounded-xl border border-[var(--border-color)] text-[var(--text-accent)] hover:bg-[var(--bg-glass)] transition-all">
                        <i class="fas fa-chart-line mr-3"></i>查看我的报告
                    </a>
                <?php else: ?>
                    <a href="/register.php" class="btn-primary px-8 py-4 text-lg font-semibold rounded-xl">
                        <i class="fas fa-user-plus mr-3"></i>免费开始使用
                    </a>
                    <a href="#how-it-works" class="px-8 py-4 text-lg font-semibold rounded-xl border border-[var(--border-color)] text-[var(--text-accent)] hover:bg-[var(--bg-glass)] transition-all">
                        <i class="fas fa-play mr-3"></i>了解工作原理
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- 产品核心优势指标 -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 max-w-4xl mx-auto fade-in">
                <div class="text-center">
                    <div class="text-3xl font-bold text-gradient mb-2">8+</div>
                    <div class="text-[var(--text-secondary)]">分析维度</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-gradient mb-2">5分钟</div>
                    <div class="text-[var(--text-secondary)]">生成报告</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-gradient mb-2">100%</div>
                    <div class="text-[var(--text-secondary)]">透明思考</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-gradient mb-2">专业级</div>
                    <div class="text-[var(--text-secondary)]">分析质量</div>
                </div>
            </div>
        </div>
    </section>

    <!-- AI思考过程可视化 - 核心亮点 -->
    <section class="py-20 stagger-fade" id="ai-visualization">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16 fade-in">
                <h2 class="text-3xl md:text-4xl font-bold mb-6 text-[var(--text-primary)]">
                    <i class="fas fa-eye text-blue-500 mr-3"></i>
                    AI思考过程完全透明
                </h2>
                <p class="text-xl text-[var(--text-secondary)] max-w-3xl mx-auto">
                    与传统黑盒AI不同，PlanWise让您实时观察AI的分析思路，每一步推理都清晰可见，确保分析结果的可信度和专业性。
                </p>
            </div>
            
            <!-- 思考过程演示 -->
            <div class="bg-[var(--bg-card)] rounded-2xl p-8 border border-[var(--border-color)] fade-in">
                <div class="grid md:grid-cols-2 gap-8 items-center">
                    <div>
                        <h3 class="text-2xl font-bold mb-6 text-[var(--text-primary)]">实时思考链展示</h3>
                        <div class="space-y-4">
                            <div class="flex items-center space-x-4 p-4 bg-[var(--bg-glass)] rounded-lg">
                                <div class="w-3 h-3 bg-blue-500 rounded-full animate-pulse"></div>
                                <span class="text-[var(--text-secondary)]">正在分析目标市场规模...</span>
                            </div>
                            <div class="flex items-center space-x-4 p-4 bg-[var(--bg-glass)] rounded-lg">
                                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                <span class="text-[var(--text-secondary)]">市场分析完成，识别关键竞争对手...</span>
                            </div>
                            <div class="flex items-center space-x-4 p-4 bg-[var(--bg-glass)] rounded-lg">
                                <div class="w-3 h-3 bg-purple-500 rounded-full"></div>
                                <span class="text-[var(--text-secondary)]">构建用户画像模型...</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="bg-gradient-to-br from-blue-600/20 to-purple-600/20 rounded-2xl p-8 border border-blue-500/30">
                            <i class="fas fa-brain text-6xl text-blue-400 mb-4"></i>
                            <h4 class="text-xl font-bold text-[var(--text-primary)] mb-2">AI分析引擎</h4>
                            <p class="text-[var(--text-secondary)]">多层次推理 · 逻辑验证 · 结果输出</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 核心功能模块 -->
    <section class="py-20 stagger-fade" id="features">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16 fade-in">
                <h2 class="text-3xl md:text-4xl font-bold mb-6 text-[var(--text-primary)]">
                    八大分析维度，全方位商业洞察
                </h2>
                <p class="text-xl text-[var(--text-secondary)] max-w-4xl mx-auto">
                    从市场环境到财务预测，从竞争分析到风险评估，PlanWise为您提供完整的商业分析框架。
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- 功能卡片1: 市场环境分析 -->
                <div class="bg-[var(--bg-card)] rounded-xl p-6 border border-[var(--border-color)] hover:border-blue-500/50 transition-all duration-300 group fade-in">
                    <div class="w-14 h-14 bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-globe-americas text-white text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-[var(--text-primary)] mb-3">市场环境分析</h3>
                    <p class="text-[var(--text-secondary)] text-sm leading-relaxed">
                        深度分析目标市场规模、增长趋势、发展机遇和行业特征，为商业决策提供市场基础。
                    </p>
                </div>

                <!-- 功能卡片2: 竞争对手研究 -->
                <div class="bg-[var(--bg-card)] rounded-xl p-6 border border-[var(--border-color)] hover:border-purple-500/50 transition-all duration-300 group fade-in">
                    <div class="w-14 h-14 bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-chess text-white text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-[var(--text-primary)] mb-3">竞争对手研究</h3>
                    <p class="text-[var(--text-secondary)] text-sm leading-relaxed">
                        智能识别主要竞争对手，分析其产品特点、市场策略、优势劣势，制定差异化竞争策略。
                    </p>
                </div>

                <!-- 功能卡片3: 目标用户画像 -->
                <div class="bg-[var(--bg-card)] rounded-xl p-6 border border-[var(--border-color)] hover:border-green-500/50 transition-all duration-300 group fade-in">
                    <div class="w-14 h-14 bg-gradient-to-r from-green-500 to-green-600 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-users text-white text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-[var(--text-primary)] mb-3">目标用户画像</h3>
                    <p class="text-[var(--text-secondary)] text-sm leading-relaxed">
                        构建精准的用户画像模型，分析用户需求、行为特征、消费偏好，指导产品设计和营销策略。
                    </p>
                </div>

                <!-- 功能卡片4: 商业模式设计 -->
                <div class="bg-[var(--bg-card)] rounded-xl p-6 border border-[var(--border-color)] hover:border-orange-500/50 transition-all duration-300 group fade-in">
                    <div class="w-14 h-14 bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-cogs text-white text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-[var(--text-primary)] mb-3">商业模式设计</h3>
                    <p class="text-[var(--text-secondary)] text-sm leading-relaxed">
                        设计可持续的盈利模式，分析收入来源、成本结构、价值主张，构建完整的商业逻辑。
                    </p>
                </div>

                <!-- 功能卡片5: 风险评估 -->
                <div class="bg-[var(--bg-card)] rounded-xl p-6 border border-[var(--border-color)] hover:border-red-500/50 transition-all duration-300 group fade-in">
                    <div class="w-14 h-14 bg-gradient-to-r from-red-500 to-red-600 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-shield-alt text-white text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-[var(--text-primary)] mb-3">风险评估</h3>
                    <p class="text-[var(--text-secondary)] text-sm leading-relaxed">
                        全面识别市场风险、技术风险、财务风险等潜在威胁，提供风险缓解策略和应急预案。
                    </p>
                </div>

                <!-- 功能卡片6: 财务预测 -->
                <div class="bg-[var(--bg-card)] rounded-xl p-6 border border-[var(--border-color)] hover:border-cyan-500/50 transition-all duration-300 group fade-in">
                    <div class="w-14 h-14 bg-gradient-to-r from-cyan-500 to-cyan-600 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-chart-line text-white text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-[var(--text-primary)] mb-3">财务预测</h3>
                    <p class="text-[var(--text-secondary)] text-sm leading-relaxed">
                        建立详细的财务模型，预测收入增长、成本变化、现金流状况，评估投资回报率。
                    </p>
                </div>

                <!-- 功能卡片7: 营销策略 -->
                <div class="bg-[var(--bg-card)] rounded-xl p-6 border border-[var(--border-color)] hover:border-pink-500/50 transition-all duration-300 group fade-in">
                    <div class="w-14 h-14 bg-gradient-to-r from-pink-500 to-pink-600 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-bullhorn text-white text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-[var(--text-primary)] mb-3">营销策略</h3>
                    <p class="text-[var(--text-secondary)] text-sm leading-relaxed">
                        制定精准的营销策略，包括渠道选择、定价策略、推广方案，最大化市场影响力。
                    </p>
                </div>

                <!-- 功能卡片8: 实施计划 -->
                <div class="bg-[var(--bg-card)] rounded-xl p-6 border border-[var(--border-color)] hover:border-indigo-500/50 transition-all duration-300 group fade-in">
                    <div class="w-14 h-14 bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-route text-white text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-[var(--text-primary)] mb-3">实施计划</h3>
                    <p class="text-[var(--text-secondary)] text-sm leading-relaxed">
                        制定详细的分阶段实施路线图，包括时间节点、资源配置、关键里程碑和成功指标。
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- 使用流程 -->
    <section class="py-20 stagger-fade" id="how-it-works">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16 fade-in">
                <h2 class="text-3xl md:text-4xl font-bold mb-6 text-[var(--text-primary)]">
                    简单三步，获得专业分析
                </h2>
                <p class="text-xl text-[var(--text-secondary)] max-w-3xl mx-auto">
                    无需复杂的操作流程，只需简单描述您的想法，AI就能为您生成专业级别的商业策略报告。
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- 步骤1 -->
                <div class="text-center group fade-in">
                    <div class="relative mb-8">
                        <div class="w-20 h-20 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full flex items-center justify-center mx-auto group-hover:scale-110 transition-transform duration-300">
                            <span class="text-2xl font-bold text-white">1</span>
                        </div>
                        <div class="absolute -top-2 -right-2 w-6 h-6 bg-blue-400 rounded-full animate-pulse"></div>
                    </div>
                    <h3 class="text-xl font-bold text-[var(--text-primary)] mb-4">描述商业想法</h3>
                    <p class="text-[var(--text-secondary)] leading-relaxed mb-6">
                        用自然语言描述您的商业想法，包括产品概念、目标市场、预期目标等基本信息。不需要专业术语，说出您的想法即可。
                    </p>
                    <div class="bg-[var(--bg-glass)] rounded-lg p-4 text-sm text-[var(--text-secondary)]">
                        <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
                        例如：我想开一家专门为忙碌白领提供健康轻食的外卖店
                    </div>
                </div>

                <!-- 步骤2 -->
                <div class="text-center group fade-in">
                    <div class="relative mb-8">
                        <div class="w-20 h-20 bg-gradient-to-r from-purple-500 to-purple-600 rounded-full flex items-center justify-center mx-auto group-hover:scale-110 transition-transform duration-300">
                            <span class="text-2xl font-bold text-white">2</span>
                        </div>
                        <div class="absolute -top-2 -right-2 w-6 h-6 bg-purple-400 rounded-full animate-pulse"></div>
                    </div>
                    <h3 class="text-xl font-bold text-[var(--text-primary)] mb-4">AI深度分析</h3>
                    <p class="text-[var(--text-secondary)] leading-relaxed mb-6">
                        AI分析引擎将从8个维度对您的想法进行深度分析，包括市场调研、竞争分析、用户研究、财务预测等，整个过程透明可见。
                    </p>
                    <div class="bg-[var(--bg-glass)] rounded-lg p-4 text-sm text-[var(--text-secondary)]">
                        <i class="fas fa-cog text-purple-500 mr-2 animate-spin"></i>
                        分析过程：5-15分钟，实时显示思考步骤
                    </div>
                </div>

                <!-- 步骤3 -->
                <div class="text-center group fade-in">
                    <div class="relative mb-8">
                        <div class="w-20 h-20 bg-gradient-to-r from-green-500 to-green-600 rounded-full flex items-center justify-center mx-auto group-hover:scale-110 transition-transform duration-300">
                            <span class="text-2xl font-bold text-white">3</span>
                        </div>
                        <div class="absolute -top-2 -right-2 w-6 h-6 bg-green-400 rounded-full animate-pulse"></div>
                    </div>
                    <h3 class="text-xl font-bold text-[var(--text-primary)] mb-4">获得完整报告</h3>
                    <p class="text-[var(--text-secondary)] leading-relaxed mb-6">
                        收到包含策略建议、财务预测、实施计划的专业商业分析报告，可以下载、分享，也可以在线查看和管理。
                    </p>
                    <div class="bg-[var(--bg-glass)] rounded-lg p-4 text-sm text-[var(--text-secondary)]">
                        <i class="fas fa-file-alt text-green-500 mr-2"></i>
                        专业级报告：可导出PDF，终身保存
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 产品价值与差异化 -->
    <section class="py-20 stagger-fade" id="value-proposition">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16 fade-in">
                <h2 class="text-3xl md:text-4xl font-bold mb-6 text-[var(--text-primary)]">
                    为什么选择 PlanWise AI？
                </h2>
                <p class="text-xl text-[var(--text-secondary)] max-w-3xl mx-auto">
                    相比传统的商业咨询服务，PlanWise AI为您带来更高效、更经济、更可靠的商业分析体验。
                </p>
            </div>

            <div class="grid md:grid-cols-2 gap-8">
                <!-- 传统方式 vs PlanWise -->
                <div class="bg-[var(--bg-card)] rounded-xl p-8 border border-[var(--border-color)] fade-in">
                    <h3 class="text-2xl font-bold text-[var(--text-primary)] mb-6 text-center">
                        <i class="fas fa-times-circle text-red-500 mr-3"></i>
                        传统咨询服务
                    </h3>
                    <ul class="space-y-4 text-[var(--text-secondary)]">
                        <li class="flex items-start">
                            <i class="fas fa-clock text-red-500 mr-3 mt-1"></i>
                            <span>耗时长：通常需要数周甚至数月</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-dollar-sign text-red-500 mr-3 mt-1"></i>
                            <span>成本高：动辄数万元的咨询费用</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-question-circle text-red-500 mr-3 mt-1"></i>
                            <span>过程不透明：无法了解分析思路</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-user-tie text-red-500 mr-3 mt-1"></i>
                            <span>依赖人员：质量受咨询师水平影响</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-calendar-times text-red-500 mr-3 mt-1"></i>
                            <span>不易调整：修改需要额外时间成本</span>
                        </li>
                    </ul>
                </div>

                <div class="bg-gradient-to-br from-blue-600/10 to-purple-600/10 rounded-xl p-8 border border-blue-500/30 fade-in">
                    <h3 class="text-2xl font-bold text-[var(--text-primary)] mb-6 text-center">
                        <i class="fas fa-check-circle text-green-500 mr-3"></i>
                        PlanWise AI
                    </h3>
                    <ul class="space-y-4 text-[var(--text-secondary)]">
                        <li class="flex items-start">
                            <i class="fas fa-rocket text-green-500 mr-3 mt-1"></i>
                            <span>极速生成：5-15分钟完成分析</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-piggy-bank text-green-500 mr-3 mt-1"></i>
                            <span>经济实惠：极低成本享受专业服务</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-eye text-green-500 mr-3 mt-1"></i>
                            <span>全程透明：实时查看AI思考过程</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-brain text-green-500 mr-3 mt-1"></i>
                            <span>AI驱动：基于大数据的客观分析</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-sync-alt text-green-500 mr-3 mt-1"></i>
                            <span>随时迭代：可随时重新分析优化</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- 适用场景 -->
    <section class="py-20 stagger-fade" id="use-cases">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16 fade-in">
                <h2 class="text-3xl md:text-4xl font-bold mb-6 text-[var(--text-primary)]">
                    适合哪些场景使用？
                </h2>
                <p class="text-xl text-[var(--text-secondary)] max-w-3xl mx-auto">
                    无论您是创业新手还是经验丰富的企业家，PlanWise AI都能为您的不同需求提供专业支持。
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- 创业初期 -->
                <div class="bg-[var(--bg-card)] rounded-xl p-6 border border-[var(--border-color)] hover:border-blue-500/50 transition-all group fade-in">
                    <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-seedling text-white text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-[var(--text-primary)] mb-4">创业初期验证</h3>
                    <p class="text-[var(--text-secondary)] mb-4">
                        验证商业想法的可行性，了解市场需求和竞争情况，制定初期发展策略。
                    </p>
                    <div class="text-sm text-blue-600">
                        <i class="fas fa-check mr-2"></i>市场机会评估<br>
                        <i class="fas fa-check mr-2"></i>竞争格局分析<br>
                        <i class="fas fa-check mr-2"></i>商业模式设计
                    </div>
                </div>

                <!-- 业务扩张 -->
                <div class="bg-[var(--bg-card)] rounded-xl p-6 border border-[var(--border-color)] hover:border-green-500/50 transition-all group fade-in">
                    <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-green-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-chart-line text-white text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-[var(--text-primary)] mb-4">业务扩张规划</h3>
                    <p class="text-[var(--text-secondary)] mb-4">
                        为现有业务的扩张制定战略规划，评估新市场机会和潜在风险。
                    </p>
                    <div class="text-sm text-green-600">
                        <i class="fas fa-check mr-2"></i>扩张机会识别<br>
                        <i class="fas fa-check mr-2"></i>资源需求分析<br>
                        <i class="fas fa-check mr-2"></i>风险评估管控
                    </div>
                </div>

                <!-- 转型升级 -->
                <div class="bg-[var(--bg-card)] rounded-xl p-6 border border-[var(--border-color)] hover:border-purple-500/50 transition-all group fade-in">
                    <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-sync-alt text-white text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-[var(--text-primary)] mb-4">转型升级决策</h3>
                    <p class="text-[var(--text-secondary)] mb-4">
                        在业务转型或升级时提供决策支持，分析新方向的可行性和实施路径。
                    </p>
                    <div class="text-sm text-purple-600">
                        <i class="fas fa-check mr-2"></i>转型方向评估<br>
                        <i class="fas fa-check mr-2"></i>实施路径规划<br>
                        <i class="fas fa-check mr-2"></i>资源整合优化
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 text-center stagger-fade" id="get-started">
        <div class="max-w-4xl mx-auto">
            <div class="bg-gradient-to-br from-blue-600/10 to-purple-600/10 rounded-2xl p-12 border border-blue-500/20 fade-in">
                <h2 class="text-3xl md:text-4xl font-bold mb-6 text-[var(--text-primary)]">
                    准备好开始您的商业分析之旅吗？
                </h2>
                <p class="text-xl text-[var(--text-secondary)] mb-8 max-w-2xl mx-auto">
                    加入数千位创业者和企业家的行列，让AI成为您最专业的商业策略顾问。
                </p>
                
                <?php if ($is_logged_in): ?>
                    <div class="flex flex-col sm:flex-row gap-6 justify-center items-center">
                        <a href="/report.php" class="btn-primary px-10 py-4 text-xl font-semibold rounded-xl">
                            <i class="fas fa-rocket mr-3"></i>立即创建分析报告
                        </a>
                        <a href="/dashboard.php" class="px-10 py-4 text-xl font-semibold rounded-xl border border-[var(--border-color)] text-[var(--text-accent)] hover:bg-[var(--bg-glass)] transition-all">
                            <i class="fas fa-tachometer-alt mr-3"></i>查看我的报告
                        </a>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col sm:flex-row gap-6 justify-center items-center">
                        <a href="/register.php" class="btn-primary px-10 py-4 text-xl font-semibold rounded-xl">
                            <i class="fas fa-user-plus mr-3"></i>免费注册体验
                        </a>
                        <a href="/login.php" class="px-10 py-4 text-xl font-semibold rounded-xl border border-[var(--border-color)] text-[var(--text-accent)] hover:bg-[var(--bg-glass)] transition-all">
                            <i class="fas fa-sign-in-alt mr-3"></i>已有账号？立即登录
                        </a>
                    </div>
                <?php endif; ?>

                <!-- 信任指标 -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mt-12 pt-8 border-t border-[var(--border-color)]">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gradient mb-2">专业级</div>
                        <div class="text-[var(--text-secondary)] text-sm">分析质量</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gradient mb-2">5分钟</div>
                        <div class="text-[var(--text-secondary)] text-sm">快速生成</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gradient mb-2">8个维度</div>
                        <div class="text-[var(--text-secondary)] text-sm">全面分析</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gradient mb-2">100%透明</div>
                        <div class="text-[var(--text-secondary)] text-sm">思考过程</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
// 页面加载动画
document.addEventListener('DOMContentLoaded', function() {
    // 为所有 fade-in 元素添加观察器
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });

    // 初始化所有 fade-in 元素
    document.querySelectorAll('.fade-in').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'all 0.8s ease-out';
        observer.observe(el);
    });

    // 错开动画效果
    document.querySelectorAll('.stagger-fade').forEach(container => {
        const children = container.querySelectorAll('.fade-in');
        children.forEach((child, index) => {
            child.style.transitionDelay = `${index * 0.1}s`;
        });
    });
});

// 平滑滚动
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});
</script>
