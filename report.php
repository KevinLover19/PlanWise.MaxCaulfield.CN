<?php
// /www/wwwroot/planwise.maxcaulfield.cn/report.php
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

// SEO设置
$page_actual_title = '创建商业策略分析报告 - PlanWise AI';
$meta_description = '使用PlanWise AI创建详细的商业策略分析报告，涵盖市场分析、竞争对手研究、用户画像、商业模式、风险评估、财务预测、营销策略和实施计划等8个维度的专业分析。';
$meta_keywords = '商业策略报告,市场分析,竞争对手研究,AI分析,商业计划书,PlanWise';

require_once __DIR__ . '/includes/header.php';
$csrf_token = csrf_token();
?>

<main class="container mx-auto px-4 sm:px-6 lg:px-8 mt-8 mb-8">
    <!-- 页面标题 -->
    <div class="text-center mb-12 stagger-fade">
        <h1 class="text-4xl md:text-5xl font-bold mb-6 fade-in">
            <span class="text-gradient">创建商业策略分析报告</span>
        </h1>
        <p class="text-lg md:text-xl text-[var(--text-secondary)] max-w-3xl mx-auto fade-in">
            告诉我们您的商业想法，AI将为您生成包含8个维度的专业分析报告
        </p>
    </div>
    
    <!-- 报告生成表单 -->
    <div class="max-w-4xl mx-auto">
        <div class="glass-effect p-8 md:p-12 fade-in">
            <form id="report-form" method="post" class="space-y-8">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                
                <!-- 基本信息 -->
                <div class="space-y-6">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-lightbulb text-white text-xl"></i>
                        </div>
                        <h2 class="text-2xl md:text-3xl font-semibold text-[var(--text-primary)]">商业想法描述</h2>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="business_name" class="block text-sm font-medium text-[var(--text-primary)] mb-2">
                                <i class="fas fa-tag mr-2 text-[var(--text-accent)]"></i>项目/产品名称 <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="business_name" name="business_name" required 
                                class="w-full px-4 py-3 rounded-lg border border-[var(--border-color)] bg-[var(--bg-glass)] text-[var(--text-primary)] placeholder-[var(--text-secondary)] focus:ring-2 focus:ring-[var(--text-accent)] focus:border-transparent transition-all"
                                placeholder="请输入您的项目或产品名称">
                        </div>
                        
                        <div>
                            <label for="industry" class="block text-sm font-medium text-[var(--text-primary)] mb-2">
                                <i class="fas fa-industry mr-2 text-[var(--text-accent)]"></i>所属行业 <span class="text-red-500">*</span>
                            </label>
                            <select id="industry" name="industry" required 
                                class="w-full px-4 py-3 rounded-lg border border-[var(--border-color)] bg-[var(--bg-glass)] text-[var(--text-primary)] focus:ring-2 focus:ring-[var(--text-accent)] focus:border-transparent transition-all">
                                <option value="">请选择行业</option>
                                <option value="科技互联网">科技互联网</option>
                                <option value="电子商务">电子商务</option>
                                <option value="金融服务">金融服务</option>
                                <option value="教育培训">教育培训</option>
                                <option value="医疗健康">医疗健康</option>
                                <option value="零售消费">零售消费</option>
                                <option value="房地产">房地产</option>
                                <option value="制造业">制造业</option>
                                <option value="餐饮服务">餐饮服务</option>
                                <option value="文化娱乐">文化娱乐</option>
                                <option value="其他">其他</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label for="business_description" class="block text-sm font-medium text-[var(--text-primary)] mb-2">
                            <i class="fas fa-edit mr-2 text-[var(--text-accent)]"></i>商业想法详细描述 <span class="text-red-500">*</span>
                        </label>
                        <textarea id="business_description" name="business_description" rows="6" required
                            class="w-full px-4 py-3 rounded-lg border border-[var(--border-color)] bg-[var(--bg-glass)] text-[var(--text-primary)] placeholder-[var(--text-secondary)] focus:ring-2 focus:ring-[var(--text-accent)] focus:border-transparent transition-all resize-none"
                            placeholder="请详细描述您的商业想法，包括：&#10;• 产品或服务的核心功能&#10;• 解决的问题或满足的需求&#10;• 目标用户群体&#10;• 预期的商业模式&#10;• 任何其他重要信息..."></textarea>
                        <div class="text-xs text-[var(--text-secondary)] mt-1">建议至少300字，描述越详细，分析结果越准确</div>
                    </div>
                </div>
                
                <!-- 分析偏好设置 -->
                <div class="space-y-6 border-t border-[var(--border-color)] pt-8">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-teal-600 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-sliders-h text-white text-xl"></i>
                        </div>
                        <h2 class="text-2xl md:text-3xl font-semibold text-[var(--text-primary)]">分析偏好设置</h2>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="analysis_depth" class="block text-sm font-medium text-[var(--text-primary)] mb-2">
                                <i class="fas fa-layer-group mr-2 text-[var(--text-accent)]"></i>分析深度
                            </label>
                            <select id="analysis_depth" name="analysis_depth" 
                                class="w-full px-4 py-3 rounded-lg border border-[var(--border-color)] bg-[var(--bg-glass)] text-[var(--text-primary)] focus:ring-2 focus:ring-[var(--text-accent)] focus:border-transparent transition-all">
                                <option value="standard">标准版 (推荐)</option>
                                <option value="basic">基础版</option>
                                <option value="deep">深度版</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="focus_area" class="block text-sm font-medium text-[var(--text-primary)] mb-2">
                                <i class="fas fa-bullseye mr-2 text-[var(--text-accent)]"></i>重点关注领域
                            </label>
                            <select id="focus_area" name="focus_area" 
                                class="w-full px-4 py-3 rounded-lg border border-[var(--border-color)] bg-[var(--bg-glass)] text-[var(--text-primary)] focus:ring-2 focus:ring-[var(--text-accent)] focus:border-transparent transition-all">
                                <option value="balanced">平衡分析</option>
                                <option value="market">市场分析</option>
                                <option value="competition">竞争分析</option>
                                <option value="financial">财务分析</option>
                                <option value="risk">风险评估</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- 提交按钮 -->
                <div class="text-center pt-8">
                    <button type="submit" id="submit-btn" 
                        class="btn-primary px-12 py-4 text-lg font-semibold rounded-xl inline-flex items-center disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-rocket mr-3"></i>
                        <span id="btn-text">开始AI分析</span>
                        <i class="fas fa-spinner fa-spin ml-3 hidden" id="loading-icon"></i>
                    </button>
                    <p class="text-sm text-[var(--text-secondary)] mt-4">
                        预计分析时间：3-5分钟，请耐心等待
                    </p>
                </div>
            </form>
        </div>
        
        <!-- AI分析进度显示 -->
        <div id="analysis-progress" class="glass-effect p-8 mt-8 hidden">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-pink-600 rounded-full flex items-center justify-center mx-auto mb-4 animate-pulse">
                    <i class="fas fa-brain text-2xl text-white"></i>
                </div>
                <h3 class="text-2xl font-semibold text-[var(--text-primary)] mb-2">AI 正在分析您的商业想法</h3>
                <p class="text-[var(--text-secondary)]">请稍候，我们将分阶段展示分析进度与洞察结果。</p>
            </div>

            <div class="space-y-6">
                <div>
                    <div class="flex items-center justify-between mb-3 text-sm text-[var(--text-secondary)]">
                        <span>分析进度</span>
                        <span id="task-progress-percent">0%</span>
                    </div>
                    <div class="w-full h-2 rounded-full bg-gray-200 overflow-hidden">
                        <div id="task-progress-bar" class="h-2 rounded-full bg-gradient-to-r from-[var(--text-accent)] to-[var(--glow-color)] transition-all duration-500" style="width:0%"></div>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-3">分析步骤</h3>
                    <div id="task-step-list" class="space-y-3"></div>
                </div>

                <div class="border-t border-dashed border-[var(--border-color)] pt-6">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-lg font-semibold text-[var(--text-primary)]">阶段性洞察</h3>
                        <span class="text-xs text-[var(--text-secondary)]">结果将实时刷新</span>
                    </div>
                    <div id="partial-results" class="space-y-4"></div>
                </div>
            </div>
        </div>

        <div id="final-report" class="glass-effect p-8 mt-8 hidden">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-2xl font-semibold text-[var(--text-primary)]">AI 分析报告</h2>
                    <p class="text-sm text-[var(--text-secondary)] mt-1">报告生成后将展示完整章节，可直接复制或导出。</p>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" onclick="openAIGCModal('ad_copy')" class="px-4 py-2 rounded-lg bg-gradient-to-r from-purple-500 to-blue-500 text-white shadow hover:shadow-lg transition-all">
                        <i class="fas fa-magic mr-2"></i>AI 创意工具箱
                    </button>
                </div>
            </div>
        </div>

        <!-- AIGC 交互模态框 -->
        <div id="aigcModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/40 backdrop-blur">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="bg-white dark:bg-[var(--bg-secondary)] rounded-lg shadow-xl max-w-2xl w-full">
                    <div class="p-6 space-y-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold" id="aigcTitle"></h3>
                            <button type="button" onclick="closeAIGCModal()" class="text-[var(--text-secondary)] hover:text-[var(--text-primary)]">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-[var(--text-primary)] mb-2">创意风格</label>
                                <select id="creativeStyle" class="w-full px-3 py-2 border border-[var(--border-color)] rounded-lg bg-[var(--bg-secondary)] text-[var(--text-primary)]">
                                    <option value="professional">专业正式</option>
                                    <option value="creative">创意活泼</option>
                                    <option value="minimalist">简约现代</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-[var(--text-primary)] mb-2">生成数量</label>
                                <input type="range" id="generateCount" min="1" max="5" value="3" class="w-full" oninput="updateCountDisplay(this.value)">
                                <span id="countDisplay" class="text-xs text-[var(--text-secondary)]">3个方案</span>
                            </div>
                        </div>

                        <div id="aigcResults" class="space-y-3 max-h-96 overflow-y-auto pr-1"></div>

                        <div class="flex justify-end gap-3">
                            <button type="button" onclick="regenerateContent()" class="px-4 py-2 rounded-lg border border-[var(--border-color)] text-[var(--text-primary)] hover:bg-[var(--bg-secondary)]">
                                重新生成
                            </button>
                            <button type="button" onclick="insertToReport()" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                                插入报告
                            </button>
                            <button type="button" onclick="closeAIGCModal()" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200">
                                关闭
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="/assets/js/task_manager.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
