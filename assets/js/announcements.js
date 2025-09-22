/**
 * PlanWise 公告系统前端脚本
 * 处理公告的获取、显示和交互
 */

class AnnouncementManager {
    constructor() {
        this.apiUrl = '/api/announcements.php';
        this.announcements = [];
        this.currentPopupIndex = 0;
        this.init();
    }

    async init() {
        try {
            await this.loadAnnouncements();
            this.displayBannerAnnouncements();
            this.displayPopupAnnouncements();
        } catch (error) {
            console.error('公告系统初始化失败:', error);
        }
    }

    async loadAnnouncements() {
        try {
            const response = await fetch(`${this.apiUrl}?action=get_active_announcements`);
            const data = await response.json();
            
            if (data.success) {
                this.announcements = data.announcements || [];
                this.userLoggedIn = data.user_logged_in || false;
            } else {
                throw new Error(data.error || '获取公告失败');
            }
        } catch (error) {
            console.error('加载公告失败:', error);
            this.announcements = [];
        }
    }

    displayBannerAnnouncements() {
        const bannerAnnouncements = this.announcements.filter(a => !a.show_popup);
        
        if (bannerAnnouncements.length === 0) return;

        // 创建公告条容器
        const bannerContainer = this.createBannerContainer();
        
        bannerAnnouncements.forEach((announcement, index) => {
            if (index < 3) { // 最多显示3个横幅公告
                const bannerElement = this.createBannerElement(announcement);
                bannerContainer.appendChild(bannerElement);
            }
        });

        // 插入到页面顶部
        document.body.insertBefore(bannerContainer, document.body.firstChild);
    }

    displayPopupAnnouncements() {
        const popupAnnouncements = this.announcements.filter(a => 
            a.show_popup && !a.is_viewed
        );

        if (popupAnnouncements.length === 0) return;

        // 延迟显示第一个弹窗公告
        setTimeout(() => {
            this.showPopupAnnouncement(popupAnnouncements[0]);
        }, 1500);

        // 如果有多个弹窗公告，设置队列显示
        if (popupAnnouncements.length > 1) {
            this.popupQueue = popupAnnouncements.slice(1);
        }
    }

    createBannerContainer() {
        const container = document.createElement('div');
        container.id = 'announcement-banners';
        container.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 9999;
            pointer-events: none;
        `;
        return container;
    }

    createBannerElement(announcement) {
        const banner = document.createElement('div');
        banner.className = `announcement-banner announcement-${announcement.announcement_type}`;
        banner.setAttribute('data-announcement-id', announcement.id);
        
        const typeColors = {
            info: 'background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); color: white;',
            warning: 'background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;',
            success: 'background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;',
            error: 'background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white;'
        };

        banner.style.cssText = `
            ${typeColors[announcement.announcement_type] || typeColors.info}
            padding: 12px 20px;
            text-align: center;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            pointer-events: auto;
            position: relative;
            animation: slideDown 0.5s ease-out;
        `;

        const typeIcons = {
            info: '📢',
            warning: '⚠️',
            success: '✅',
            error: '❌'
        };

        banner.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                <span style="font-size: 16px;">${typeIcons[announcement.announcement_type] || '📢'}</span>
                <span>${this.escapeHtml(announcement.title)}</span>
                <button onclick="this.parentElement.parentElement.style.display='none'" 
                        style="background: rgba(255,255,255,0.2); border: none; color: inherit; 
                               padding: 2px 8px; border-radius: 4px; cursor: pointer; margin-left: 10px;">
                    ✕
                </button>
            </div>
        `;

        // 添加CSS动画
        if (!document.getElementById('announcement-styles')) {
            const style = document.createElement('style');
            style.id = 'announcement-styles';
            style.textContent = `
                @keyframes slideDown {
                    from { transform: translateY(-100%); }
                    to { transform: translateY(0); }
                }
                @keyframes fadeInScale {
                    from { opacity: 0; transform: scale(0.9); }
                    to { opacity: 1; transform: scale(1); }
                }
                .announcement-modal {
                    animation: fadeInScale 0.3s ease-out;
                }
            `;
            document.head.appendChild(style);
        }

        return banner;
    }

    showPopupAnnouncement(announcement) {
        if (!announcement) return;

        const modal = this.createPopupModal(announcement);
        document.body.appendChild(modal);

        // 标记为已查看
        if (this.userLoggedIn) {
            this.markAsViewed(announcement.id);
        }
    }

    createPopupModal(announcement) {
        const overlay = document.createElement('div');
        overlay.className = 'announcement-modal-overlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.6);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        `;

        const typeColors = {
            info: 'border-left: 5px solid #3b82f6;',
            warning: 'border-left: 5px solid #f59e0b;',
            success: 'border-left: 5px solid #10b981;',
            error: 'border-left: 5px solid #ef4444;'
        };

        const typeIcons = {
            info: '📢',
            warning: '⚠️',
            success: '✅',
            error: '❌'
        };

        const modal = document.createElement('div');
        modal.className = 'announcement-modal';
        modal.style.cssText = `
            background: white;
            border-radius: 12px;
            max-width: 600px;
            width: 100%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            ${typeColors[announcement.announcement_type] || typeColors.info}
        `;

        modal.innerHTML = `
            <div style="padding: 30px;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="font-size: 24px;">${typeIcons[announcement.announcement_type] || '📢'}</span>
                        <h3 style="margin: 0; font-size: 20px; font-weight: 600; color: #1f2937;">
                            ${this.escapeHtml(announcement.title)}
                        </h3>
                    </div>
                    <button class="close-announcement" style="
                        background: #f3f4f6; 
                        border: none; 
                        width: 32px; 
                        height: 32px; 
                        border-radius: 50%; 
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        color: #6b7280;
                        font-size: 18px;
                        transition: background-color 0.2s;
                    " onmouseover="this.style.backgroundColor='#e5e7eb'" 
                       onmouseout="this.style.backgroundColor='#f3f4f6'">
                        ✕
                    </button>
                </div>
                
                <div style="color: #374151; line-height: 1.6; font-size: 16px;">
                    ${this.formatContent(announcement.content)}
                </div>
                
                <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #e5e7eb; 
                            display: flex; justify-content: space-between; align-items: center;">
                    <small style="color: #6b7280; font-size: 14px;">
                        发布时间: ${new Date(announcement.created_at).toLocaleString('zh-CN')}
                    </small>
                    <button class="close-announcement" style="
                        background: #3b82f6; 
                        color: white; 
                        border: none; 
                        padding: 8px 20px; 
                        border-radius: 6px; 
                        cursor: pointer;
                        font-size: 14px;
                        font-weight: 500;
                        transition: background-color 0.2s;
                    " onmouseover="this.style.backgroundColor='#2563eb'" 
                       onmouseout="this.style.backgroundColor='#3b82f6'">
                        我知道了
                    </button>
                </div>
            </div>
        `;

        // 关闭事件
        const closeButtons = modal.querySelectorAll('.close-announcement');
        closeButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                document.body.removeChild(overlay);
                this.showNextPopupAnnouncement();
            });
        });

        // 点击背景关闭
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                document.body.removeChild(overlay);
                this.showNextPopupAnnouncement();
            }
        });

        overlay.appendChild(modal);
        return overlay;
    }

    showNextPopupAnnouncement() {
        if (this.popupQueue && this.popupQueue.length > 0) {
            const nextAnnouncement = this.popupQueue.shift();
            setTimeout(() => {
                this.showPopupAnnouncement(nextAnnouncement);
            }, 1000);
        }
    }

    async markAsViewed(announcementId) {
        try {
            const formData = new FormData();
            formData.append('action', 'mark_as_viewed');
            formData.append('announcement_id', announcementId);

            await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
        } catch (error) {
            console.error('标记公告已查看失败:', error);
        }
    }

    formatContent(content) {
        // 简单的内容格式化，支持基本的HTML标签
        return content
            .replace(/\n/g, '<br>')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>');
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// 页面加载完成后初始化公告系统
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new AnnouncementManager();
    });
} else {
    new AnnouncementManager();
}
