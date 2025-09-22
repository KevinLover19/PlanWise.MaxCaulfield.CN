<?php
/**
 * PlanWise AI 报告生成页面
 * Author: Max Caulfield
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/quota_manager.php';

$pdo = getPDO();
$quota_manager = new QuotaManager($pdo);

// 获取用户ID（如果已登录）
$user_id = $_SESSION['user_id'] ?? null;

// 获取用户配额信息
$quota_info = $quota_manager->getUserQuota($user_id);
$membership_quotas = $quota_manager->getMembershipQuotas();

// SEO设置
$page_actual_title = '创建商业策略分析报告 - PlanWise AI';
$meta_description = '使用PlanWise AI创建详细的商业策略分析报告，涵盖市场分析、竞争对手研究、用户画像、商业模式、风险评估、财务预测、营销策略和实施计划等8个维度的专业分析。';
$meta_keywords = '商业策略报告,市场分析,竞争对手研究,AI分析,商业计划书,PlanWise';

require_once __DIR__ . '/includes/header.php';
?>

<main class="container mx-auto px-4 sm:px-6 lg:px-8 mt-8 mb-8">
    <!-- 用户配额状态卡片 -->
    <?php if ($user_id): ?>
    <div class="max-w-4xl mx-auto mb-8">
        <div class="glass-effect p-6 fade-in">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-user-crown text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-[var(--text-primary)]">
                            <?php echo htmlspecialchars($membership_quotas[$quota_info['membership_type']]['name']); ?>
                        </h3>
                        <p class="text-sm text-[var(--text-secondary)]">配额使用情况</p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-[var(--text-primary)]">
                        <?php echo $quota_info['remaining_quota']; ?> / <?php echo $quota_info['total_quota']; ?>
                    </div>
                    <p class="text-sm text-[var(--text-secondary)]">剩余次数</p>
                </div>
            </div>
            
            <!-- 配额进度条 -->
            <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                <?php 
                $usage_percent = $quota_info['total_quota'] > 0 
                    ? (($quota_info['total_quota'] - $quota_info['remaining_quota']) / $quota_info['total_quota']) * 100 
                    : 0;
                ?>
                <div class="h-2 rounded-full bg-gradient-to-r from-blue-500 to-purple-600" 
                     style="width: <?php echo min(100, $usage_percent); ?>%"></div>
            </div>
            
            <!-- 会员功能列表 -->
            <div class="flex flex-wrap gap-2">
                <?php foreach ($membership_quotas[$quota_info['membership_type']]['features'] as $feature): ?>
                    <span class="px-3 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                        <?php echo htmlspecialchars($feature); ?>
                    </span>
                <?php endforeach; ?>
            </div>
            
            <?php if ($quota_info['membership_type'] === 'free'): ?>
            <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-star text-yellow-500 mr-2"></i>
                    <p class="text-sm text-yellow-800">
                        <strong>升级会员</strong>享受更多分析次数和高级功能！
                        <a href="#upgrade" class="text-yellow-600 hover:text-yellow-700 underline ml-1">了解更多</a>
                    </p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
    <!-- 游客配额提示 -->
    <div class="max-w-4xl mx-auto mb-8">
        <div class="glass-effect p-6 fade-in border-l-4 border-orange-400">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-info-circle text-orange-500 text-xl mr-3"></i>
                    <div>
                        <h3 class="text-lg font-semibold text-[var(--text-primary)]">游客模式</h3>
                        <p class="text-sm text-[var(--text-secondary)]">
                            今日剩余分析次数: <strong><?php echo $quota_info['remaining_quota']; ?></strong> / <?php echo $quota_info['total_quota']; ?>
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <a href="/register.php" class="bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-2 rounded-full text-sm font-medium hover:opacity-90 transition">
                        免费注册获得更多次数
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

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
            <form id="report-form" class="space-y-8">
                <!-- 基本信息 -->
                <div class="space-y-6">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-lightbulb text-white text-xl"></i>
                        </div>
                        <h2 class="text-2xl md:text-3xl font-semibold text-[var(--text-primary)]">商业想法描述</h2>
                    </div>
                    
                    <div>
                        <label for="business_idea" class="block text-lg font-medium text-[var(--text-primary)] mb-3">
                            详细描述您的商业想法 <span class="text-red-500">*</span>
                        </label>
                        <textarea 
                            id="business_idea" 
                            name="business_idea" 
                            rows="6" 
                            required
                            maxlength="2000"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-[var(--text-primary)] bg-[var(--bg-secondary)]"
                            placeholder="例如：我想创建一个基于AI的智能健身指导平台，通过计算机视觉技术实时分析用户的运动姿态，提供个性化的健身建议和纠正指导。目标用户是希望在家进行高效健身的上班族..."></textarea>
                        <div class="flex justify-between mt-2">
                            <p class="text-sm text-[var(--text-secondary)]">
                                请详细描述您的商业模式、目标用户、核心价值等
                            </p>
                            <span id="char-count" class="text-sm text-[var(--text-secondary)]">0/2000</span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="industry" class="block text-lg font-medium text-[var(--text-primary)] mb-3">
                                所属行业
                            </label>
                            <select id="industry" name="industry" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-[var(--text-primary)] bg-[var(--bg-secondary)]">
                                <option value="">请选择行业</option>
                                <option value="科技互联网">科技互联网</option>
                                <option value="电子商务">电子商务</option>
                                <option value="金融科技">金融科技</option>
                                <option value="教育培训">教育培训</option>
                                <option value="医疗健康">医疗健康</option>
                                <option value="娱乐传媒">娱乐传媒</option>
                                <option value="餐饮服务">餐饮服务</option>
                                <option value="制造业">制造业</option>
                                <option value="房地产">房地产</option>
                                <option value="零售消费">零售消费</option>
                                <option value="旅游出行">旅游出行</option>
                                <option value="其他">其他</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="analysis_depth" class="block text-lg font-medium text-[var(--text-primary)] mb-3">
                                分析深度
                            </label>
                            <select id="analysis_depth" name="analysis_depth" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-[var(--text-primary)] bg-[var(--bg-secondary)]">
                                <option value="basic">基础分析（约5分钟，消耗1次配额）</option>
                                <?php if ($user_id && $quota_info['membership_type'] !== 'free'): ?>
                                <option value="standard">标准分析（约15分钟，消耗2次配额）</option>
                                <option value="deep">深度分析（约30分钟，消耗3次配额）</option>
                                <?php else: ?>
                                <option value="standard" disabled>标准分析（需要升级会员）</option>
                                <option value="deep" disabled>深度分析（需要升级会员）</option>
                                <?php endif; ?>
                            </select>
                            <p class="text-sm text-[var(--text-secondary)] mt-2">
                                <?php if (!$user_id || $quota_info['membership_type'] === 'free'): ?>
                                    <i class="fas fa-info-circle mr-1"></i>升级会员解锁标准和深度分析
                                <?php else: ?>
                                    <i class="fas fa-check-circle mr-1 text-green-500"></i>您可以使用所有分析深度
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- 重点关注领域 -->
                <div class="space-y-6">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-teal-600 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-bullseye text-white text-xl"></i>
                        </div>
                        <h2 class="text-2xl md:text-3xl font-semibold text-[var(--text-primary)]">重点关注领域</h2>
                    </div>
                    
                    <p class="text-[var(--text-secondary)] mb-4">选择您最希望深入分析的方面（可多选）：</p>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" name="focus_areas" value="市场规模" class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                            <span class="text-[var(--text-primary)]">市场规模</span>
                        </label>
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" name="focus_areas" value="竞争分析" class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                            <span class="text-[var(--text-primary)]">竞争分析</span>
                        </label>
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" name="focus_areas" value="用户获取" class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                            <span class="text-[var(--text-primary)]">用户获取</span>
                        </label>
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" name="focus_areas" value="盈利模式" class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                            <span class="text-[var(--text-primary)]">盈利模式</span>
                        </label>
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" name="focus_areas" value="技术实现" class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                            <span class="text-[var(--text-primary)]">技术实现</span>
                        </label>
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" name="focus_areas" value="资金需求" class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                            <span class="text-[var(--text-primary)]">资金需求</span>
                        </label>
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" name="focus_areas" value="风险控制" class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                            <span class="text-[var(--text-primary)]">风险控制</span>
                        </label>
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" name="focus_areas" value="扩张策略" class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                            <span class="text-[var(--text-primary)]">扩张策略</span>
                        </label>
                    </div>
                </div>
                
                <!-- 提交按钮 -->
                <div class="text-center pt-8">
                    <button 
                        type="submit" 
                        id="submit-btn"
                        class="bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-bold py-4 px-12 rounded-full text-lg transition duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
                        <?php echo $quota_info['remaining_quota'] <= 0 ? 'disabled' : ''; ?>
                    >
                        <i class="fas fa-magic mr-2"></i>
                        开始AI分析
                    </button>
                    
                    <?php if ($quota_info['remaining_quota'] <= 0): ?>
                    <p class="text-red-500 mt-4">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <?php echo $user_id ? '配额已用完，请等待重置或升级会员' : '今日游客分析次数已达上限，请明天再来或注册账户'; ?>
                    </p>
                    <?php endif; ?>
                    
                    <p class="text-sm text-[var(--text-secondary)] mt-4">
                        点击开始分析即表示您同意我们的服务条款和隐私政策
                    </p>
                </div>
            </form>
        </div>
    </div>
    
    <!-- 分析中的进度显示 -->
    <div id="analysis-progress" class="max-w-4xl mx-auto mt-8 hidden">
        <div class="glass-effect p-8">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full mb-4">
                    <i class="fas fa-brain text-white text-2xl animate-pulse"></i>
                </div>
                <h2 class="text-2xl font-bold text-[var(--text-primary)] mb-2">AI正在分析您的商业想法</h2>
                <p class="text-[var(--text-secondary)]">请稍候，预计需要 <span id="estimated-time">5</span> 分钟</p>
            </div>
            
            <!-- 进度条 -->
            <div class="w-full bg-gray-200 rounded-full h-3 mb-8">
                <div id="progress-bar" class="h-3 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full transition-all duration-500" style="width: 0%"></div>
            </div>
            
            <!-- 当前步骤显示 -->
            <div id="current-step" class="text-center">
                <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-2">正在进行：市场环境分析</h3>
                <p class="text-[var(--text-secondary)]">正在分析目标市场环境和规模...</p>
            </div>
            
            <!-- 分析步骤列表 -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
                <div class="step-item text-center p-4 rounded-lg bg-[var(--bg-secondary)]" data-step="1">
                    <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center mx-auto mb-2">
                        <i class="fas fa-chart-line text-gray-600"></i>
                    </div>
                    <p class="text-sm text-[var(--text-secondary)]">市场分析</p>
                </div>
                <div class="step-item text-center p-4 rounded-lg bg-[var(--bg-secondary)]" data-step="2">
                    <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center mx-auto mb-2">
                        <i class="fas fa-users text-gray-600"></i>
                    </div>
                    <p class="text-sm text-[var(--text-secondary)]">竞争研究</p>
                </div>
                <div class="step-item text-center p-4 rounded-lg bg-[var(--bg-secondary)]" data-step="3">
                    <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center mx-auto mb-2">
                        <i class="fas fa-user-friends text-gray-600"></i>
                    </div>
                    <p class="text-sm text-[var(--text-secondary)]">用户画像</p>
                </div>
                <div class="step-item text-center p-4 rounded-lg bg-[var(--bg-secondary)]" data-step="4">
                    <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center mx-auto mb-2">
                        <i class="fas fa-cogs text-gray-600"></i>
                    </div>
                    <p class="text-sm text-[var(--text-secondary)]">商业模式</p>
                </div>
                <div class="step-item text-center p-4 rounded-lg bg-[var(--bg-secondary)]" data-step="5">
                    <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center mx-auto mb-2">
                        <i class="fas fa-exclamation-triangle text-gray-600"></i>
                    </div>
                    <p class="text-sm text-[var(--text-secondary)]">风险评估</p>
                </div>
                <div class="step-item text-center p-4 rounded-lg bg-[var(--bg-secondary)]" data-step="6">
                    <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center mx-auto mb-2">
                        <i class="fas fa-calculator text-gray-600"></i>
                    </div>
                    <p class="text-sm text-[var(--text-secondary)]">财务预测</p>
                </div>
                <div class="step-item text-center p-4 rounded-lg bg-[var(--bg-secondary)]" data-step="7">
                    <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center mx-auto mb-2">
                        <i class="fas fa-bullhorn text-gray-600"></i>
                    </div>
                    <p class="text-sm text-[var(--text-secondary)]">营销策略</p>
                </div>
                <div class="step-item text-center p-4 rounded-lg bg-[var(--bg-secondary)]" data-step="8">
                    <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center mx-auto mb-2">
                        <i class="fas fa-roadmap text-gray-600"></i>
                    </div>
                    <p class="text-sm text-[var(--text-secondary)]">实施计划</p>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// 字符计数
document.getElementById('business_idea').addEventListener('input', function() {
    const count = this.value.length;
    document.getElementById('char-count').textContent = count + '/2000';
    
    if (count > 1800) {
        document.getElementById('char-count').classList.add('text-red-500');
    } else {
        document.getElementById('char-count').classList.remove('text-red-500');
    }
});

// 表单提交处理
document.getElementById('report-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const business_idea = formData.get('business_idea');
    const analysis_depth = formData.get('analysis_depth');
    
    if (!business_idea || business_idea.trim().length < 10) {
        alert('请详细描述您的商业想法（至少10个字符）');
        return;
    }
    
    // 获取选中的关注领域
    const focus_areas = [];
    document.querySelectorAll('input[name="focus_areas"]:checked').forEach(checkbox => {
        focus_areas.push(checkbox.value);
    });
    
    // 开始分析
    startAnalysis({
        business_idea: business_idea,
        industry: formData.get('industry'),
        analysis_depth: analysis_depth,
        focus_areas: focus_areas
    });
});

// 开始分析函数
async function startAnalysis(data) {
    try {
        // 获取CSRF token
        const tokenResponse = await fetch('/api.php?action=get_csrf_token');
        const tokenData = await tokenResponse.json();
        
        // 显示进度界面
        document.getElementById('report-form').parentElement.style.display = 'none';
        document.getElementById('analysis-progress').classList.remove('hidden');
        
        // 设置预估时间
        const times = { basic: 5, standard: 15, deep: 30 };
        document.getElementById('estimated-time').textContent = times[data.analysis_depth] || 5;
        
        // 启动分析
        const startResponse = await fetch('/api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'start_analysis',
                csrf_token: tokenData.csrf_token,
                business_idea: data.business_idea,
                industry: data.industry,
                analysis_depth: data.analysis_depth,
                focus_areas: JSON.stringify(data.focus_areas)
            })
        });
        
        const startData = await startResponse.json();
        
        if (startData.success) {
            // 开始轮询进度
            pollProgress(startData.session_id);
        } else {
            alert('启动分析失败：' + (startData.error || '未知错误'));
            showForm();
        }
    } catch (error) {
        console.error('Error starting analysis:', error);
        alert('启动分析时发生错误，请重试');
        showForm();
    }
}

// 轮询进度
async function pollProgress(sessionId) {
    try {
        const response = await fetch(`/api.php?action=get_progress&session_id=${sessionId}`);
        const data = await response.json();
        
        if (data.success) {
            updateProgress(data.progress);
            
            if (data.progress.status === 'completed') {
                // 分析完成，跳转到报告页面
                window.location.href = `/view_report.php?session_id=${sessionId}`;
            } else if (data.progress.status === 'failed') {
                alert('分析失败：' + (data.progress.error_message || '未知错误'));
                showForm();
            } else {
                // 继续轮询
                setTimeout(() => pollProgress(sessionId), 3000);
            }
        } else {
            throw new Error(data.error || '获取进度失败');
        }
    } catch (error) {
        console.error('Error polling progress:', error);
        setTimeout(() => pollProgress(sessionId), 5000); // 出错时延长轮询间隔
    }
}

// 更新进度显示
function updateProgress(progress) {
    // 更新进度条
    document.getElementById('progress-bar').style.width = progress.progress_percentage + '%';
    
    // 更新当前步骤
    if (progress.current_step_description) {
        document.getElementById('current-step').innerHTML = `
            <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-2">正在进行：${getStepName(progress.current_step)}</h3>
            <p class="text-[var(--text-secondary)]">${progress.current_step_description}</p>
        `;
    }
    
    // 更新步骤状态
    updateStepStatus(progress.current_step, progress.status);
}

// 获取步骤名称
function getStepName(stepNumber) {
    const names = {
        1: '市场环境分析',
        2: '竞争对手研究',
        3: '目标用户画像',
        4: '商业模式设计',
        5: '风险评估分析',
        6: '财务预测建模',
        7: '营销策略制定',
        8: '实施计划规划'
    };
    return names[stepNumber] || '分析中';
}

// 更新步骤状态
function updateStepStatus(currentStep, status) {
    document.querySelectorAll('.step-item').forEach((item, index) => {
        const stepNum = index + 1;
        const icon = item.querySelector('i');
        const circle = item.querySelector('.w-10');
        
        if (stepNum < currentStep || (stepNum === currentStep && status === 'completed')) {
            // 已完成的步骤
            circle.classList.remove('bg-gray-300');
            circle.classList.add('bg-green-500');
            icon.classList.remove('text-gray-600');
            icon.classList.add('text-white');
        } else if (stepNum === currentStep) {
            // 当前进行的步骤
            circle.classList.remove('bg-gray-300');
            circle.classList.add('bg-blue-500');
            icon.classList.remove('text-gray-600');
            icon.classList.add('text-white');
        }
    });
}

// 显示表单
function showForm() {
    document.getElementById('analysis-progress').classList.add('hidden');
    document.getElementById('report-form').parentElement.style.display = 'block';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
