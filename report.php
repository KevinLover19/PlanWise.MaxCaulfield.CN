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
            <form id="report-form" method="post" action="/api.php" class="space-y-8">
                <input type="hidden" name="action" value="create_report">
                
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
                <h3 class="text-2xl font-semibold text-[var(--text-primary)] mb-2">AI正在分析您的商业想法</h3>
                <p class="text-[var(--text-secondary)]">请稍候，我们正在从多个维度为您生成专业的分析报告...</p>
            </div>
            
            <!-- 分析步骤 -->
            <div class="space-y-4">
                <div class="analysis-step flex items-center p-4 rounded-lg bg-[var(--bg-secondary)]" data-step="1">
                    <div class="step-icon w-8 h-8 rounded-full bg-[var(--text-accent)] flex items-center justify-center mr-4">
                        <i class="fas fa-search text-white text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <div class="step-title font-medium text-[var(--text-primary)]">市场环境分析</div>
                        <div class="step-description text-sm text-[var(--text-secondary)]">正在分析目标市场...</div>
                    </div>
                    <div class="step-status">
                        <i class="fas fa-clock text-[var(--text-secondary)]"></i>
                    </div>
                </div>
                
                <div class="analysis-step flex items-center p-4 rounded-lg bg-[var(--bg-secondary)]" data-step="2">
                    <div class="step-icon w-8 h-8 rounded-full bg-gray-400 flex items-center justify-center mr-4">
                        <i class="fas fa-users text-white text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <div class="step-title font-medium text-[var(--text-primary)]">竞争对手研究</div>
                        <div class="step-description text-sm text-[var(--text-secondary)]">等待中...</div>
                    </div>
                    <div class="step-status">
                        <i class="fas fa-clock text-[var(--text-secondary)]"></i>
                    </div>
                </div>
                
                <div class="analysis-step flex items-center p-4 rounded-lg bg-[var(--bg-secondary)]" data-step="3">
                    <div class="step-icon w-8 h-8 rounded-full bg-gray-400 flex items-center justify-center mr-4">
                        <i class="fas fa-user-tie text-white text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <div class="step-title font-medium text-[var(--text-primary)]">目标用户画像</div>
                        <div class="step-description text-sm text-[var(--text-secondary)]">等待中...</div>
                    </div>
                    <div class="step-status">
                        <i class="fas fa-clock text-[var(--text-secondary)]"></i>
                    </div>
                </div>
                
                <div class="analysis-step flex items-center p-4 rounded-lg bg-[var(--bg-secondary)]" data-step="4">
                    <div class="step-icon w-8 h-8 rounded-full bg-gray-400 flex items-center justify-center mr-4">
                        <i class="fas fa-cogs text-white text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <div class="step-title font-medium text-[var(--text-primary)]">商业模式设计</div>
                        <div class="step-description text-sm text-[var(--text-secondary)]">等待中...</div>
                    </div>
                    <div class="step-status">
                        <i class="fas fa-clock text-[var(--text-secondary)]"></i>
                    </div>
                </div>
                
                <div class="analysis-step flex items-center p-4 rounded-lg bg-[var(--bg-secondary)]" data-step="5">
                    <div class="step-icon w-8 h-8 rounded-full bg-gray-400 flex items-center justify-center mr-4">
                        <i class="fas fa-exclamation-triangle text-white text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <div class="step-title font-medium text-[var(--text-primary)]">风险评估</div>
                        <div class="step-description text-sm text-[var(--text-secondary)]">等待中...</div>
                    </div>
                    <div class="step-status">
                        <i class="fas fa-clock text-[var(--text-secondary)]"></i>
                    </div>
                </div>
                
                <div class="analysis-step flex items-center p-4 rounded-lg bg-[var(--bg-secondary)]" data-step="6">
                    <div class="step-icon w-8 h-8 rounded-full bg-gray-400 flex items-center justify-center mr-4">
                        <i class="fas fa-calculator text-white text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <div class="step-title font-medium text-[var(--text-primary)]">财务预测</div>
                        <div class="step-description text-sm text-[var(--text-secondary)]">等待中...</div>
                    </div>
                    <div class="step-status">
                        <i class="fas fa-clock text-[var(--text-secondary)]"></i>
                    </div>
                </div>
                
                <div class="analysis-step flex items-center p-4 rounded-lg bg-[var(--bg-secondary)]" data-step="7">
                    <div class="step-icon w-8 h-8 rounded-full bg-gray-400 flex items-center justify-center mr-4">
                        <i class="fas fa-bullhorn text-white text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <div class="step-title font-medium text-[var(--text-primary)]">营销策略</div>
                        <div class="step-description text-sm text-[var(--text-secondary)]">等待中...</div>
                    </div>
                    <div class="step-status">
                        <i class="fas fa-clock text-[var(--text-secondary)]"></i>
                    </div>
                </div>
                
                <div class="analysis-step flex items-center p-4 rounded-lg bg-[var(--bg-secondary)]" data-step="8">
                    <div class="step-icon w-8 h-8 rounded-full bg-gray-400 flex items-center justify-center mr-4">
                        <i class="fas fa-road text-white text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <div class="step-title font-medium text-[var(--text-primary)]">实施计划</div>
                        <div class="step-description text-sm text-[var(--text-secondary)]">等待中...</div>
                    </div>
                    <div class="step-status">
                        <i class="fas fa-clock text-[var(--text-secondary)]"></i>
                    </div>
                </div>
            </div>
            
            <!-- 进度条 -->
            <div class="mt-8">
                <div class="flex justify-between text-sm text-[var(--text-secondary)] mb-2">
                    <span>分析进度</span>
                    <span><span id="progress-percent">0</span>%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div id="progress-bar" class="bg-gradient-to-r from-[var(--text-accent)] to-[var(--glow-color)] h-2 rounded-full transition-all duration-500" style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('report-form');
    const submitBtn = document.getElementById('submit-btn');
    const btnText = document.getElementById('btn-text');
    const loadingIcon = document.getElementById('loading-icon');
    const progressSection = document.getElementById('analysis-progress');
    const progressBar = document.getElementById('progress-bar');
    const progressPercent = document.getElementById('progress-percent');

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // 表单验证
        if (!validateForm()) {
            return;
        }
        
        // 显示加载状态
        submitBtn.disabled = true;
        btnText.textContent = '正在提交...';
        loadingIcon.classList.remove('hidden');
        
        // 显示进度区域
        progressSection.classList.remove('hidden');
        progressSection.scrollIntoView({ behavior: 'smooth' });
        
        // 开始模拟分析进度
        startAnalysisSimulation();
        
        try {
            const formData = new FormData(form);
            const response = await fetch('/api.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // 分析完成，跳转到报告页面
                window.location.href = `/view_report.php?id=${result.report_id}`;
            } else {
                throw new Error(result.message || '生成报告失败');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('生成报告时出现错误：' + error.message);
            
            // 重置按钮状态
            submitBtn.disabled = false;
            btnText.textContent = '开始AI分析';
            loadingIcon.classList.add('hidden');
            progressSection.classList.add('hidden');
        }
    });
    
    function validateForm() {
        const businessName = document.getElementById('business_name').value.trim();
        const industry = document.getElementById('industry').value;
        const description = document.getElementById('business_description').value.trim();
        
        if (!businessName) {
            alert('请输入项目/产品名称');
            return false;
        }
        
        if (!industry) {
            alert('请选择所属行业');
            return false;
        }
        
        if (!description || description.length < 50) {
            alert('请详细描述您的商业想法（至少50个字符）');
            return false;
        }
        
        return true;
    }
    
    function startAnalysisSimulation() {
        let currentStep = 0;
        const steps = document.querySelectorAll('.analysis-step');
        const stepDescriptions = [
            '正在分析目标市场规模和发展趋势...',
            '正在识别主要竞争对手和市场格局...',
            '正在构建目标用户画像...',
            '正在设计可持续的盈利模式...',
            '正在识别潜在风险和挑战...',
            '正在进行财务建模和预测...',
            '正在制定营销推广策略...',
            '正在规划实施路线图...'
        ];
        
        function updateStep() {
            if (currentStep < steps.length) {
                const step = steps[currentStep];
                const icon = step.querySelector('.step-icon');
                const description = step.querySelector('.step-description');
                const status = step.querySelector('.step-status');
                
                // 更新当前步骤
                icon.classList.remove('bg-gray-400');
                icon.classList.add('bg-[var(--text-accent)]');
                description.textContent = stepDescriptions[currentStep];
                status.innerHTML = '<i class="fas fa-spinner fa-spin text-[var(--text-accent)]"></i>';
                
                // 更新进度条
                const progress = ((currentStep + 1) / steps.length) * 100;
                progressBar.style.width = progress + '%';
                progressPercent.textContent = Math.round(progress);
                
                setTimeout(() => {
                    // 标记为完成
                    status.innerHTML = '<i class="fas fa-check text-green-500"></i>';
                    currentStep++;
                    
                    if (currentStep < steps.length) {
                        setTimeout(updateStep, 500);
                    }
                }, Math.random() * 2000 + 2000); // 2-4秒随机间隔
            }
        }
        
        updateStep();
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
