# PlanWise - AI 商业策略智能体

PlanWise 基于 LNMP (Linux + Nginx + MySQL + PHP) 构建，为创业者和运营团队提供全自动的商业策略分析报告。系统将用户的商业想法拆分为 8 个分析维度，异步排队执行 AI 调用，并实时把阶段性洞察反馈到前端界面。

## 功能总览

- **异步分析流水线**：通过 `planwise_task_queue` 管理任务，PHP CLI Worker 批量处理，支持失败重试与状态回放。
- **多模型联动**：`AI_Service_Enhanced` 封装了多家模型供应商（Anthropic Claude / 通义千问等），自动降级到 mock 输出。
- **分步落库**：`planwise_report_steps` 记录每个章节的进度、原始响应与格式化结果，便于审计与回放。
- **前端实时可视化**：Tailwind 风格的 `report.php` 页面搭配 `assets/js/task_manager.js`，提供进度条、阶段性洞察卡片、AI 创意工具箱。
- **配额 / 安全**：`db_connect.php` 集成统一会话、CSRF 校验、数据库自动建表及配额字段升级。

## 关键目录与文件

### 后端服务层
- `AI_Service_Enhanced.php`：多模型调度、指数退避重试、成功模型记录（`getLastProvider`）。
- `config/ai_config.php`：模型供应商默认配置，可通过环境变量覆盖。
- `api/task/create.php`：创建分析任务、写入报告元数据、预生成步骤行并入队。
- `api/task/status.php`：查询任务状态、步骤进度和已完成章节。
- `db_connect.php`：PDO 连接、Session、CSRF 工具以及 `planwise_install_async_schema()`。
- `planwise_async_schema.sql`：离线部署时手动执行的 DDL 脚本（包含任务队列、报告步骤、配额扩展等）。

### 前端交互层
- `report.php`：Tailwind/Twig 风格的报告创建页面，引用 `assets/js/task_manager.js`。
- `assets/js/task_manager.js`：任务轮询、进度条更新、阶段性内容渲染、AIGC 模态窗逻辑。
- `assets/css/`：全局样式（与主站保持品牌一致）。

### Worker & 运维
- `worker/task_processor.php`：常驻 CLI Worker，消费队列、调用 AI、回写步骤状态，并生成执行摘要。
- `FINAL_REBUILD_STATUS.md` 等 *.md：重构说明及阶段性总结。

## 数据库结构

> 数据库文件统一放置在仓库根目录，主站及子站应使用独立 SQL 文件，PlanWise 的增量表结构位于 `planwise_async_schema.sql`。

核心表：

| 表名 | 作用 |
| ---- | ---- |
| `planwise_task_queue` | 异步任务队列，含 payload / result / retry_count |
| `planwise_reports_v2` | 报告主表，记录 business_idea、analysis_preferences 等 |
| `planwise_report_steps` | 每个章节的进度、模型、响应、耗时、错误信息 |
| `planwise_user_quotas` | 追加 tokens / category_group 等字段以支持配额控制 |

首次访问 `db_connect.php` 会自动执行建表逻辑；如需手动部署，可登录 MySQL 执行 `source planwise_async_schema.sql;`。

## 环境变量配置

在 Nginx FastCGI、宝塔面板或 `.user.ini` 中设置以下变量：

```bash
# 数据库
PLANWISE_DB_HOST=localhost
PLANWISE_DB_NAME=maxcaulfield_cn
PLANWISE_DB_USER=maxcaulfield_cn
PLANWISE_DB_PASS=数据库密码

# AI 模型（可选，默认 mock）
CLAUDE_API_KEY=xxxxxxxx
CLAUDE_MODEL=claude-3-sonnet-20240229
QWEN_API_KEY=xxxxxxxx
QWEN_MODEL=qwen-plus
ENCRYPTION_KEY=随机32字节字符串
```

若未设置 `PLANWISE_DB_PASS`，系统会自动回退到主站 `config.php` 中的密码（`d5iKNkpKd2eGxT8p`）。

## 部署流程（LNMP 服务器）

1. **代码部署**：将仓库拉取到 `/www/wwwroot/planwise.maxcaulfield.cn`，确保 `runtime` 目录可写（如有）。
2. **环境变量**：在站点配置或 `fastcgi_param` 中注入上述变量，确保 PHP-FPM 可读取。
3. **数据库**：
   - 使用宝塔 / phpMyAdmin 导入主库 `maxcaulfield.cn.sql`（如尚未导入）。
   - 执行 `planwise_async_schema.sql`，或直接访问站点触发自动建表。
4. **Nginx**：
   - 站点根目录指向仓库根路径。
   - 设置 `index.php` 为默认入口，开启 HTTPS 并配置证书。
5. **Worker 常驻进程**：
   - 创建 `/etc/supervisor/conf.d/planwise_worker.conf`：
     ```ini
     [program:planwise_worker]
     process_name=%(program_name)s_%(process_num)02d
     command=php /www/wwwroot/planwise.maxcaulfield.cn/worker/task_processor.php
     autostart=true
     autorestart=true
     numprocs=3
     user=www
     stdout_logfile=/www/wwwroot/planwise.maxcaulfield.cn/logs/worker.log
     stderr_logfile=/www/wwwroot/planwise.maxcaulfield.cn/logs/worker-error.log
     ```
   - 重载 Supervisor：`supervisorctl reread && supervisorctl update`。
6. **权限与安全**：
   - `logs/`、`cache/`（如存在）需设为 `www` 用户可写。
   - 确保 `config/ai_config.php` 不暴露真实密钥，可通过环境变量覆盖。

部署完成后，访问 `https://planwise.maxcaulfield.cn/report.php` 即可体验。表单提交后可在浏览器控制台查看 `/api/task/create.php`、`/api/task/status.php` 的交互。

## 日常运维建议

- **日志**：
  - Web 错误：`/www/wwwlogs/planwise.maxcaulfield.cn.error.log`
  - Worker：`/www/wwwroot/planwise.maxcaulfield.cn/logs/worker.log`
- **队列监控**：定期查询 `planwise_task_queue`，确保无大量 `pending/failed` 任务；可在后台管理页或命令行执行：
  ```sql
  SELECT status, COUNT(*) FROM planwise_task_queue GROUP BY status;
  ```
- **API 限频**：若需要进一步限制，可在 `middleware/RateLimiter.php` 或 Nginx 层开启限流。
- **模型成本控制**：通过修改 `config/ai_config.php` 或环境变量切换模型、温度、最大 token。

## 本地开发调试

1. 启动内置服务器：`php -S 0.0.0.0:8000`。
2. 配置 `.env` 或导出环境变量，使其指向本地 MySQL。
3. 运行 Worker：`php worker/task_processor.php`（终端输出可观察任务流）。
4. 若未配置真实密钥，系统会返回 Mock 报告，便于调试前端流程。

---

如需在主站后台管理 `planwise_management.php` 中扩展功能，请保持与上述表结构同步，避免直接在生产库中修改未跟踪的字段。
