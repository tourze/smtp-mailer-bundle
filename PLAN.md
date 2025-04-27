# SMTP Mailer Bundle 开发计划

## 1. 项目概述

SMTP Mailer Bundle 是一个 Symfony Bundle，主要功能包括：
- 支持配置多个 SMTP 服务器信息
- 支持邮件发送任务管理，包括定时发送
- 支持同步/异步发送（通过 symfony/messenger）
- 提供灵活的 SMTP 服务器选择策略（轮询、随机、权重等）
- 提供 EasyAdmin 后台管理界面
- 封装服务层供内部调用

## 2. 技术要求

- PHP 8.1+
- Symfony 6.4+
- Doctrine ORM
- Symfony Messenger
- EasyAdmin Bundle

## 3. 系统架构

### 3.1 核心组件

1. **实体层**
   - `SMTPConfig`: SMTP服务器配置实体
   - `MailTask`: 邮件发送任务实体

2. **仓库层**
   - `SMTPConfigRepository`: 管理SMTP配置
   - `MailTaskRepository`: 管理邮件任务

3. **服务层**
   - `SMTPMailerService`: 核心服务，提供邮件发送功能
   - `SMTPSelectorService`: SMTP选择策略服务
   - `MailTaskSchedulerService`: 任务调度服务

4. **消息/事件**
   - `SendMailMessage`: 异步邮件发送消息
   - `MailMessageHandler`: 消息处理器

5. **命令**
   - `ProcessScheduledMailsCommand`: 处理定时邮件任务

6. **管理界面**
   - EasyAdmin CRUD 控制器

### 3.2 SMTP选择策略

- 轮询策略（RoundRobinStrategy）
- 随机策略（RandomStrategy）
- 权重策略（WeightedStrategy）
- 优先级策略（PriorityStrategy）
- 自定义策略接口（StrategyInterface）

## 4. 数据模型

### 4.1 SMTPConfig

```
- id: int (主键)
- name: string (配置名称)
- host: string (SMTP服务器地址)
- port: int (端口)
- username: string (用户名)
- password: string (密码)
- encryption: string (加密方式：none, ssl, tls)
- timeout: int (超时时间，单位秒)
- auth_mode: string (认证模式)
- weight: int (权重，用于权重策略)
- priority: int (优先级，用于优先级策略)
- enabled: bool (是否启用)
- created_at: datetime
- updated_at: datetime
```

### 4.2 MailTask

```
- id: int (主键)
- from_email: string (发件人邮箱)
- from_name: string (发件人名称)
- to_email: string (收件人邮箱)
- to_name: string (收件人名称)
- cc: json (抄送)
- bcc: json (密送)
- subject: string (邮件主题)
- body: text (邮件内容)
- is_html: bool (是否HTML内容)
- attachments: json (附件信息)
- scheduled_at: datetime (计划发送时间)
- status: string (状态：pending, processing, sent, failed)
- status_message: text (状态信息，主要用于记录错误)
- smtp_config_id: int (外键，指定SMTP配置，可为空)
- selector_strategy: string (选择器策略，如为空则使用默认策略)
- created_at: datetime
- updated_at: datetime
- sent_at: datetime (实际发送时间)
```

## 5. 配置结构

```yaml
# config/packages/smtp_mailer.yaml
smtp_mailer:
  default_strategy: 'round_robin'  # 默认SMTP选择策略
  async_enabled: true              # 是否启用异步发送
  async_transport: 'async'         # 异步消息传输名称
  process_scheduled_interval: 60   # 处理定时任务的间隔（秒）
  smtp_configs:                    # 预定义SMTP配置（可选）
    main:
      host: '%env(MAILER_HOST)%'
      port: '%env(int:MAILER_PORT)%'
      username: '%env(MAILER_USERNAME)%'
      password: '%env(MAILER_PASSWORD)%'
      encryption: '%env(MAILER_ENCRYPTION)%'
```

## 6. 开发阶段

### 6.1 基础框架搭建
- 设置 Bundle 基础结构
- 完善 DependencyInjection 配置
- 创建实体类

### 6.2 核心功能开发
- 实现 SMTP 选择策略接口及各策略类
- 开发邮件发送服务
- 开发任务调度服务

### 6.3 异步处理
- 设置 Messenger 集成
- 实现消息处理程序

### 6.4 管理界面
- 集成 EasyAdmin
- 创建 CRUD 控制器

### 6.5 测试与文档
- 单元测试
- 功能测试
- 更新 README.md

## 7. 使用示例

```php
// 使用服务示例
$mailerService->send($to, $subject, $body, [
    'from' => 'sender@example.com',
    'cc' => ['cc@example.com'],
    'isHtml' => true,
    'strategy' => 'priority',  // 使用优先级策略
    'scheduledAt' => new \DateTime('+1 hour')  // 一小时后发送
]);

// 指定使用特定SMTP配置
$mailerService->sendWithConfig($configId, $to, $subject, $body, $options);
```

## 8. 开发排期

1. 基础框架搭建 - 1天
2. 核心功能开发 - 2天
3. 异步处理机制 - 1天
4. EasyAdmin集成 - 1天
5. 测试与文档 - 1天

总计：约6个工作日
