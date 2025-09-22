class TaskManager {
    constructor(options = {}) {
        this.pollInterval = options.pollInterval || 2000;
        this.maxPollTime = options.maxPollTime || 10 * 60 * 1000;
        this.timeoutHandle = null;
        this.activeTaskId = null;
        this.taskStartedAt = null;
        this.csrfToken = this.resolveInitialToken();
        this.latestResult = null;
    }

    resolveInitialToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && meta.content) {
            return meta.content;
        }
        const hidden = document.querySelector('input[name="csrf_token"]');
        return hidden ? hidden.value : '';
    }

    setCsrfToken(token) {
        if (!token) return;
        this.csrfToken = token;
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) meta.setAttribute('content', token);
        const hidden = document.querySelector('input[name="csrf_token"]');
        if (hidden) hidden.value = token;
    }

    async createTask(formElement) {
        if (!(formElement instanceof HTMLFormElement)) {
            throw new Error('Invalid form element');
        }

        const formData = new FormData(formElement);
        const payload = {
            type: 'analyze_business_idea',
            csrf_token: this.csrfToken,
            data: Object.fromEntries(formData.entries()),
        };

        const response = await fetch('/api/task/create.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        });

        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.error || '任务创建失败');
        }

        this.setCsrfToken(data.next_csrf || '');
        this.prepareTaskUI(data.task_id, data.steps || []);
        this.startPolling(data.task_id);
        return data;
    }

    prepareTaskUI(taskId, steps) {
        this.activeTaskId = taskId;
        this.taskStartedAt = Date.now();
        this.latestResult = null;

        const progressCard = document.getElementById('analysis-progress');
        const progressBar = document.getElementById('task-progress-bar');
        const progressPercent = document.getElementById('task-progress-percent');
        const stepContainer = document.getElementById('task-step-list');
        const partialContainer = document.getElementById('partial-results');
        const finalContainer = document.getElementById('final-report');

        if (progressCard) {
            progressCard.classList.remove('hidden');
        }
        if (progressBar) {
            progressBar.style.width = '0%';
        }
        if (progressPercent) {
            progressPercent.textContent = '0%';
        }
        if (stepContainer) {
            stepContainer.innerHTML = '';
            steps.forEach(step => {
                const card = document.createElement('div');
                card.className = 'analysis-step flex items-center p-4 rounded-lg bg-[var(--bg-secondary)] border border-transparent';
                card.dataset.stepName = step.step_name;
                card.dataset.stepNumber = step.step_number;
                card.innerHTML = `
                    <div class="step-icon w-8 h-8 rounded-full bg-gray-400 flex items-center justify-center mr-4">
                        <i class="fas fa-circle-notch text-white text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <div class="step-title font-medium text-[var(--text-primary)]">${step.step_title}</div>
                        <div class="step-description text-sm text-[var(--text-secondary)]">等待中...</div>
                    </div>
                    <div class="step-status text-[var(--text-secondary)]">
                        <i class="fas fa-clock"></i>
                    </div>
                `;
                stepContainer.appendChild(card);
            });
        }
        if (partialContainer) {
            partialContainer.innerHTML = '';
        }
        if (finalContainer) {
            finalContainer.innerHTML = '';
        }
    }

    startPolling(taskId) {
        if (!taskId) return;
        if (this.timeoutHandle) {
            clearTimeout(this.timeoutHandle);
        }
        const poll = async () => {
            try {
                const status = await this.fetchStatus(taskId);
                this.updateProgress(taskId, status);

                if (status.task.status === 'completed') {
                    this.handleCompletion(taskId, status);
                    return;
                }
                if (status.task.status === 'failed') {
                    this.handleError(taskId, status);
                    return;
                }

                if (Date.now() - this.taskStartedAt > this.maxPollTime) {
                    this.handleTimeout(taskId);
                    return;
                }

                this.timeoutHandle = setTimeout(poll, this.pollInterval);
            } catch (error) {
                console.error('Polling error:', error);
                this.timeoutHandle = setTimeout(poll, Math.min(this.pollInterval * 1.5, 10000));
            }
        };

        poll();
    }

    async fetchStatus(taskId) {
        const response = await fetch(`/api/task/status.php?task_id=${encodeURIComponent(taskId)}`, {
            headers: { 'Accept': 'application/json' },
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.error || '获取任务状态失败');
        }
        return data;
    }

    updateProgress(taskId, data) {
        if (taskId !== this.activeTaskId) return;
        const task = data.task || {};
        const steps = data.steps || [];
        const partial = data.partial_result || [];

        const percent = task.total_steps ? Math.round((task.current_step / task.total_steps) * 100) : 0;
        const progressBar = document.getElementById('task-progress-bar');
        const progressPercent = document.getElementById('task-progress-percent');
        if (progressBar) {
            progressBar.style.width = `${Math.min(100, percent)}%`;
        }
        if (progressPercent) {
            progressPercent.textContent = `${Math.min(100, percent)}%`;
        }

        const stepContainer = document.getElementById('task-step-list');
        if (stepContainer) {
            steps.forEach(step => {
                const card = stepContainer.querySelector(`.analysis-step[data-step-name="${step.step_name}"]`);
                if (!card) return;
                const icon = card.querySelector('.step-icon');
                const desc = card.querySelector('.step-description');
                const status = card.querySelector('.step-status');

                card.classList.remove('border-green-400', 'border-[var(--border-color)]');
                switch (step.status) {
                    case 'completed':
                        card.classList.add('border-green-400');
                        if (icon) {
                            icon.classList.remove('bg-gray-400');
                            icon.classList.add('bg-emerald-500');
                            icon.innerHTML = '<i class="fas fa-check text-white text-sm"></i>';
                        }
                        if (status) {
                            status.innerHTML = '<i class="fas fa-check text-emerald-400"></i>';
                        }
                        if (desc) {
                            desc.textContent = '步骤完成';
                        }
                        break;
                    case 'processing':
                        card.classList.add('border-[var(--border-color)]');
                        if (icon) {
                            icon.classList.remove('bg-gray-400');
                            icon.classList.add('bg-[var(--text-accent)]');
                            icon.innerHTML = '<i class="fas fa-spinner fa-spin text-white text-sm"></i>';
                        }
                        if (status) {
                            status.innerHTML = '<i class="fas fa-spinner fa-spin text-[var(--text-accent)]"></i>';
                        }
                        if (desc) {
                            desc.textContent = task.current_message || '正在处理中...';
                        }
                        break;
                    default:
                        if (icon) {
                            icon.classList.add('bg-gray-400');
                            icon.innerHTML = '<i class="fas fa-circle-notch text-white text-sm"></i>';
                        }
                        if (status) {
                            status.innerHTML = '<i class="fas fa-clock text-[var(--text-secondary)]"></i>';
                        }
                        if (desc) {
                            desc.textContent = '等待中...';
                        }
                        break;
                }
            });
        }

        this.renderPartialResult(partial);
    }

    renderPartialResult(partial) {
        const container = document.getElementById('partial-results');
        if (!container) return;

        partial.sort((a, b) => a.step_number - b.step_number);
        partial.forEach(item => {
            const existing = container.querySelector(`[data-partial-step="${item.step_id}"]`);
            if (existing) {
                existing.querySelector('.partial-body').innerHTML = this.formatStepContent(item.content);
                return;
            }

            const card = document.createElement('div');
            card.className = 'partial-card bg-[var(--bg-secondary)] border border-[var(--border-color)] rounded-lg p-4 space-y-2 animate-fade-in';
            card.dataset.partialStep = item.step_id;
            card.innerHTML = `
                <div class="flex items-center justify-between">
                    <h4 class="font-semibold text-[var(--text-primary)]">${item.step_title}</h4>
                    <span class="text-xs text-[var(--text-secondary)]">${item.completed_at ? this.formatTime(item.completed_at) : ''}</span>
                </div>
                <div class="partial-body text-sm leading-relaxed text-[var(--text-secondary)]">${this.formatStepContent(item.content)}</div>
            `;
            container.appendChild(card);
        });
    }

    handleCompletion(taskId, data) {
        this.latestResult = data;
        const progressCard = document.getElementById('analysis-progress');
        if (progressCard) {
            progressCard.classList.add('completed');
        }
        this.renderReport(data.result || {});
    }

    handleError(taskId, data) {
        const message = (data.task && data.task.current_message) || '任务执行失败，请稍后重试。';
        this.showToast(message, 'error');
    }

    handleTimeout(taskId) {
        this.showToast('任务执行超时，请稍后重试。', 'warning');
    }

    formatStepContent(content) {
        if (!content) return '';
        return content
            .split(/\n+/)
            .map(line => `<p>${this.escapeHtml(line)}</p>`)
            .join('');
    }

    renderReport(result) {
        const container = document.getElementById('final-report');
        if (!container) return;

        container.innerHTML = '';
        container.classList.remove('hidden');
        const summary = result.executive_summary || result.summary || '';
        if (summary) {
            const summaryCard = document.createElement('div');
            summaryCard.className = 'bg-[var(--bg-secondary)] border border-[var(--border-color)] rounded-xl p-6 mb-6 shadow-sm';
            summaryCard.innerHTML = `
                <h3 class="text-xl font-semibold text-[var(--text-primary)] mb-3">执行摘要</h3>
                <div class="text-sm text-[var(--text-secondary)] leading-relaxed">${this.formatStepContent(summary)}</div>
            `;
            container.appendChild(summaryCard);
        }

        const sections = result.sections || result.content || {};
        Object.entries(sections).forEach(([key, value]) => {
            const card = document.createElement('div');
            card.className = 'bg-[var(--bg-secondary)] border border-[var(--border-color)] rounded-xl p-6 mb-6 shadow-sm';
            card.innerHTML = `
                <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-3">${this.translateSectionKey(key)}</h3>
                <div class="text-sm text-[var(--text-secondary)] leading-relaxed">${this.formatStepContent(value)}</div>
            `;
            container.appendChild(card);
        });
    }

    translateSectionKey(key) {
        const map = {
            market_analysis: '市场环境分析',
            competitor_research: '竞争对手研究',
            user_persona: '目标用户画像',
            business_model: '商业模式设计',
            risk_assessment: '风险评估分析',
            financial_forecast: '财务预测建模',
            marketing_strategy: '营销策略制定',
            implementation_plan: '实施计划规划',
            conclusion: '结论与建议',
        };
        return map[key] || key;
    }

    escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    formatTime(dateStr) {
        const date = new Date(dateStr);
        if (Number.isNaN(date.getTime())) return '';
        return date.toLocaleString();
    }

    showToast(message, type = 'info') {
        const container = document.getElementById('toast-container') || this.createToastContainer();
        const toast = document.createElement('div');
        const colors = {
            info: 'bg-blue-500',
            success: 'bg-emerald-500',
            warning: 'bg-amber-500',
            error: 'bg-rose-500',
        };
        toast.className = `text-white px-4 py-2 rounded-md shadow-lg flex items-center space-x-2 ${colors[type] || colors.info}`;
        toast.innerHTML = `<i class="fas fa-info-circle"></i><span>${this.escapeHtml(message)}</span>`;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    }

    createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'fixed top-6 right-6 space-y-3 z-50';
        document.body.appendChild(container);
        return container;
    }
}

const taskManager = new TaskManager();
window.PlanWiseTaskManager = taskManager;

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('report-form');
    const submitBtn = document.getElementById('submit-btn');
    const loadingIcon = document.getElementById('loading-icon');
    const btnText = document.getElementById('btn-text');

    if (!form) return;

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (submitBtn) {
            submitBtn.disabled = true;
        }
        if (btnText) {
            btnText.textContent = 'AI 正在分析...';
        }
        if (loadingIcon) {
            loadingIcon.classList.remove('hidden');
        }

        try {
            await taskManager.createTask(form);
        } catch (error) {
            console.error(error);
            taskManager.showToast(error.message || '任务创建失败', 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
            }
            if (btnText) {
                btnText.textContent = '开始AI分析';
            }
            if (loadingIcon) {
                loadingIcon.classList.add('hidden');
            }
        }
    });
});

// --- AIGC Modal helpers ---
let activeCreativeType = null;

function openAIGCModal(type) {
    activeCreativeType = type;
    const modal = document.getElementById('aigcModal');
    const title = document.getElementById('aigcTitle');
    if (!modal || !title) return;

    const mapping = {
        ad_copy: '✨ 生成广告文案',
        slogan: '🎯 创建品牌口号',
        social_media: '📱 社交媒体内容',
    };

    title.textContent = mapping[type] || 'AI 创意工具';
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    renderAIGCContent();
}

function closeAIGCModal() {
    const modal = document.getElementById('aigcModal');
    if (!modal) return;
    modal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

function updateCountDisplay(value) {
    const label = document.getElementById('countDisplay');
    if (label) {
        label.textContent = `${value}个方案`;
    }
}

function regenerateContent() {
    renderAIGCContent(true);
}

function insertToReport() {
    const results = document.querySelectorAll('#aigcResults .creative-item');
    if (!results.length) {
        taskManager.showToast('暂无可插入的内容', 'warning');
        return;
    }

    const container = document.getElementById('final-report');
    if (!container) return;

    const section = document.createElement('div');
    section.className = 'bg-[var(--bg-secondary)] border border-dashed border-[var(--border-color)] rounded-xl p-6 mb-6 shadow-sm';
    section.innerHTML = '<h3 class="text-lg font-semibold text-[var(--text-primary)] mb-3">AI 创意精选</h3>';

    results.forEach(item => {
        const clone = item.cloneNode(true);
        section.appendChild(clone);
    });

    container.prepend(section);
    taskManager.showToast('已将创意内容插入报告', 'success');
    closeAIGCModal();
}

function renderAIGCContent(force = false) {
    const container = document.getElementById('aigcResults');
    if (!container) return;
    container.innerHTML = '';

    const creativeStyle = document.getElementById('creativeStyle')?.value || 'professional';
    const count = parseInt(document.getElementById('generateCount')?.value || '3', 10);
    const context = window.PlanWiseTaskManager?.latestResult;

    if (!context || !context.result) {
        const info = document.createElement('div');
        info.className = 'text-sm text-[var(--text-secondary)]';
        info.textContent = '请先完成完整的商业分析报告，AI 创意工具将基于报告内容生成灵感。';
        container.appendChild(info);
        return;
    }

    const baseText = pickBaseContent(activeCreativeType, context);
    const ideas = craftCreativeIdeas(baseText, activeCreativeType, creativeStyle, count, force);

    ideas.forEach((idea, index) => {
        const item = document.createElement('div');
        item.className = 'creative-item border border-[var(--border-color)] rounded-lg p-4 bg-[var(--bg-secondary)] shadow-sm';
        item.innerHTML = `
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-semibold text-[var(--text-primary)]">创意方案 ${index + 1}</span>
                <span class="text-xs text-[var(--text-secondary)]">${creativeStyleLabel(creativeStyle)}</span>
            </div>
            <div class="text-sm text-[var(--text-secondary)] leading-relaxed">${taskManager.formatStepContent(idea)}</div>
        `;
        container.appendChild(item);
    });
}

function pickBaseContent(type, context) {
    const sections = context.result?.sections || context.result?.content || {};
    switch (type) {
        case 'ad_copy':
            return sections.marketing_strategy || sections.market_analysis || '';
        case 'slogan':
            return sections.user_persona || sections.business_model || '';
        case 'social_media':
            return sections.marketing_strategy || sections.implementation_plan || '';
        default:
            return Object.values(sections).join('\n');
    }
}

function craftCreativeIdeas(base, type, style, count, forceRandom) {
    if (!base) {
        return ['暂无足够的上下文可供生成，请稍后重试。'];
    }

    const sentences = base
        .split(/[\n。！？.!?]/)
        .map(s => s.trim())
        .filter(Boolean);

    const templates = {
        ad_copy: ['立即体验', '释放潜能', '智能驱动', '极速提升', '面向未来'],
        slogan: ['赋能', '点亮', '连接', '引领', '焕新'],
        social_media: ['#行业洞察', '#增长秘籍', '#创新实践', '#用户故事', '#实战分享'],
    };

    const ideaPool = [];
    for (let i = 0; i < count; i += 1) {
        const core = sentences[(i + (forceRandom ? Math.floor(Math.random() * sentences.length) : 0)) % sentences.length] || sentences[0];
        const prefix = (templates[type] || templates.ad_copy)[i % (templates[type] || templates.ad_copy).length];
        let styled = core;
        if (style === 'creative') {
            styled = `${prefix} · ${core}`;
        } else if (style === 'minimalist') {
            styled = `${core.slice(0, 20)}...`;
        } else {
            styled = `${prefix}，${core}`;
        }
        ideaPool.push(styled.trim());
    }

    return ideaPool;
}

function creativeStyleLabel(style) {
    switch (style) {
        case 'creative':
            return '创意风格';
        case 'minimalist':
            return '简约风格';
        default:
            return '专业风格';
    }
}

window.openAIGCModal = openAIGCModal;
window.closeAIGCModal = closeAIGCModal;
window.regenerateContent = regenerateContent;
window.insertToReport = insertToReport;
window.updateCountDisplay = updateCountDisplay;
