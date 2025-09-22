# PlanWise - AI商业策略智能体

一个基于 LNMP + PHP 的交互式 AI 商业分析网站。输入商业想法，系统自动分解为多步分析，逐步执行并生成结构化商业策略报告，前端实时可视化展示分析进度与中间结果。

## 目录结构

- index.php                # 前端页面（Bootstrap 5 + jQuery）
- api.php                  # 后端API：新建任务、查询状态、执行步骤（tick）、获取报告
- db_connect.php           # 数据库连接、统一会话、CSRF & XSS 工具、表结构自动安装
- AI_Service.php           # AI服务封装：OpenAI 集成 + 无密钥 Mock 回退
- assets/
  - css/style.css          # 主题样式（品牌色 #0A4DCC）
  - js/main.js             # 前端逻辑（AJAX + 轮询 + CSRF轮换）
- database.sql             # 数据库DDL（可手动导入）

## 快速开始

1) 环境变量（推荐）

在 Web 站点运行用户环境中设置以下变量（宝塔面板/站点设置/运行环境/环境变量 或 Nginx fastcgi_param 注入）：

- PLANWISE_DB_HOST=localhost
- PLANWISE_DB_NAME=maxcaulfield_cn
- PLANWISE_DB_USER=maxcaulfield_cn
- PLANWISE_DB_PASS={{DB_PASSWORD}}
- PLANWISE_AI_PROVIDER=mock            # 可选：mock | openai
- PLANWISE_OPENAI_API_KEY={{OPENAI_KEY}}  # 仅当 provider=openai 时需要
- PLANWISE_OPENAI_MODEL=gpt-4o-mini    # 可选

注意：请用实际值替换 {{DB_PASSWORD}} 与 {{OPENAI_KEY}}，不要以明文写入代码库。

2) 数据库表

- 自动安装：首次访问 API 会自动创建 planwise_* 表（幂等）
- 手动导入：可以在 MySQL 中执行 database.sql

3) 访问

- 打开 https://planwise.maxcaulfield.cn/
- 输入商业想法并提交，右侧实时显示进度与中间结果

## 安全与合规

- 所有 DB 查询均使用 PDO 预处理（防 SQL注入）
- 所有表单 POST 含 CSRF 校验（一次性令牌，服务器返回 next_csrf 供下次POST）
- 输出统一使用 htmlspecialchars 转义（防 XSS）
- 会话 Cookie 统一域名 .maxcaulfield.cn，便于与主站共享登录（如需）
- SameSite=Lax, HttpOnly, 按 HTTPS 自动设置 Secure

## API 说明

- POST /api.php?action=create_task
  - 参数：business_idea, industry(可选), analysis_depth(basic|standard|deep), csrf_token
  - 返回：{ ok, task_id, next_csrf }

- GET /api.php?action=get_status&task_id=ID
  - 返回：{ ok, status, current_step, progress, steps:[] }

- POST /api.php?action=tick
  - 参数：task_id, csrf_token（每次由上次响应返回的 next_csrf 刷新）
  - 返回：{ ok, step_key, step_title, content, next_csrf } 或 { ok, completed, next_csrf }

- GET /api.php?action=get_report&task_id=ID
  - 返回：{ ok, report: { title, executive_summary, full_content } }

## 与主站集成

- 站点 Cookie domain 设为 .maxcaulfield.cn，可与主站共享会话（session_name=MAXCAULFIELD_SESSION）
- 若未设置 PLANWISE_DB_PASS，会自动回落加载主站 /www/wwwroot/maxcaulfield.cn/config.php 的 DB 配置

## 品牌与SEO

- 主题色：#0A4DCC（专业蓝）
- 语义化 HTML，含 title / meta description / canonical
- 图片请务必包含 alt 属性（本MVP未包含图片）

## 限制与后续路线

- 当前为最小可用版本（MVP），AI 模式默认 mock；配置 OpenAI Key 后可切换为真实 LLM
- 建议后续：
  - 增加报告导出 PDF
  - 增加历史列表与分享链接
  - 更细粒度的错误重试与超时处理
  - 引入进阶可视化（图表）
