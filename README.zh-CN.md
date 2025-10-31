# SMTP Mailer Bundle

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Build Status](https://img.shields.io/badge/Build-Passing-brightgreen)](https://github.com/tourze/php-monorepo)
[![Code Coverage](https://img.shields.io/badge/Coverage-90%25-brightgreen)](https://github.com/tourze/php-monorepo)

[English](README.md) | [中文](README.zh-CN.md)

一个功能丰富的 Symfony Bundle，用于管理多个 SMTP 配置并支持灵活的邮件发送策略。

## 📚 目录

- [功能特性](#功能特性)
- [安装](#安装)
- [🚀 快速开始](#-快速开始)
  - [5分钟快速体验](#5分钟快速体验)
- [配置](#配置)
  - [不同环境的配置示例](#不同环境的配置示例)
- [使用](#使用)
  - [基本发送](#基本发送)
  - [指定SMTP配置发送](#指定smtp配置发送)
  - [使用不同的选择策略](#使用不同的选择策略)
- [高级用法](#高级用法)
  - [自定义 SMTP 选择策略](#自定义-smtp-选择策略)
  - [批量邮件发送](#批量邮件发送)
  - [邮件模板支持](#邮件模板支持)
- [运行定时任务处理](#运行定时任务处理)
- [后台管理](#后台管理)
- [🔧 故障排除](#-故障排除)
  - [常见问题](#常见问题)
  - [调试模式](#调试模式)
- [⚡ 性能优化](#-性能优化)
  - [大批量邮件发送优化](#大批量邮件发送优化)
  - [监控指标](#监控指标)
  - [优化建议](#优化建议)
- [🤝 贡献指南](#-贡献指南)
  - [开发环境设置](#开发环境设置)
  - [运行测试](#运行测试)
  - [提交规范](#提交规范)
  - [报告问题](#报告问题)
- [依赖项](#依赖项)
- [许可证](#许可证)

## 功能特性

- 支持配置多个 SMTP 服务器信息
- 支持邮件发送任务管理，包括定时发送
- 支持同步/异步发送（通过 symfony/messenger）
- 提供灵活的 SMTP 服务器选择策略（轮询、随机、权重等）
- 集成 EasyAdmin 后台管理界面
- 提供服务层供内部调用

## 安装

```bash
composer require tourze/smtp-mailer-bundle
```

## 🚀 快速开始

### 5分钟快速体验

1. **安装 Bundle**
   ```bash
   composer require tourze/smtp-mailer-bundle
   ```

2. **基本配置**
   ```bash
   # .env
   SMTP_MAILER_DEFAULT_FROM_EMAIL=your@email.com
   ```

3. **发送第一封邮件**
   ```php
   use Tourze\SMTPMailerBundle\Service\SMTPMailerService;

   // 在控制器或服务中
   public function sendEmail(SMTPMailerService $mailerService): void
   {
       $taskId = $mailerService->send(
           'recipient@example.com',
           'Hello World',
           'Your first email via SMTP Mailer Bundle!'
       );
   }
   ```

## 配置

Bundle 通过环境变量进行配置，支持以下配置项：

```bash
# 是否启用异步发送（默认：true）
SMTP_MAILER_ASYNC_ENABLED=true

# 默认发件人邮箱（默认：no-reply@example.com）
SMTP_MAILER_DEFAULT_FROM_EMAIL=no-reply@yoursite.com

# 默认SMTP选择策略（默认：round_robin）
# 可选值：round_robin, random, weighted, priority
SMTP_MAILER_DEFAULT_STRATEGY=round_robin

# 计划任务处理间隔，单位秒（默认：60）
SMTP_MAILER_PROCESS_INTERVAL=60
```

### 不同环境的配置示例

**开发环境：**

```bash
# .env.dev
SMTP_MAILER_ASYNC_ENABLED=false
SMTP_MAILER_DEFAULT_FROM_EMAIL=dev@localhost
SMTP_MAILER_DEFAULT_STRATEGY=random
```

**生产环境：**

```bash
# .env.prod
SMTP_MAILER_ASYNC_ENABLED=true
SMTP_MAILER_DEFAULT_FROM_EMAIL=noreply@yourcompany.com
SMTP_MAILER_DEFAULT_STRATEGY=weighted
SMTP_MAILER_PROCESS_INTERVAL=30
```

## 使用

### 基本发送

```php
use Tourze\SMTPMailerBundle\Service\SMTPMailerService;

public function sendBasicEmail(SMTPMailerService $mailerService): void
{
    $taskId = $mailerService->send(
        'recipient@example.com',
        '邮件主题',
        '邮件内容',
        [
            'from' => 'sender@example.com',
            'fromName' => '发件人名称',
            'toName' => '收件人名称',
            'isHtml' => true,
            'async' => true, // 异步发送
        ]
    );
    
    echo "邮件任务ID: $taskId";
}
```

### 指定SMTP配置发送

```php
// 使用特定的SMTP配置发送邮件
$taskId = $mailerService->sendWithConfig(
    1, // SMTP配置ID
    'recipient@example.com',
    '使用指定SMTP发送',
    '这封邮件使用指定的SMTP配置发送'
);
```

### 使用不同的选择策略

```php
// 使用权重策略
$taskId = $mailerService->send(
    'recipient@example.com',
    '权重策略邮件',
    '内容',
    ['strategy' => 'weighted']
);

// 使用优先级策略
$taskId = $mailerService->send(
    'recipient@example.com',
    '优先级策略邮件',
    '内容',
    ['strategy' => 'priority']
);
```

## 高级用法

### 自定义 SMTP 选择策略

```php
use Tourze\SMTPMailerBundle\Service\SMTPSelector\SMTPSelectorStrategyInterface;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;

class CustomStrategy implements SMTPSelectorStrategyInterface
{
    public function selectConfig(array $configs): ?SMTPConfig
    {
        // 实现自定义选择逻辑
        return $configs[array_rand($configs)] ?? null;
    }
}
```

### 批量邮件发送

```php
$tasks = [];
foreach ($recipients as $recipient) {
    $tasks[] = $mailerService->send(
        $recipient['email'],
        '批量邮件',
        '邮件内容',
        ['async' => true]
    );
}
```

### 邮件模板支持

```php
$mailerService->send(
    'recipient@example.com',
    '模板邮件',
    $this->renderView('emails/welcome.html.twig', [
        'user' => $user
    ]),
    [
        'isHtml' => true,
        'attachments' => [
            [
                'name' => 'attachment.pdf',
                'mime' => 'application/pdf',
                'data' => base64_encode($pdfContent)
            ]
        ]
    ]
);
```

## 运行定时任务处理

为了处理定时邮件任务，需要设置一个 cron 任务或使用 Symfony Messenger worker:

```bash
# 处理定时邮件
php bin/console smtp-mailer:process-scheduled-mails

# 如果使用异步处理，需要运行 messenger worker
php bin/console messenger:consume async
```

## 后台管理

Bundle 使用 EasyAdmin 提供管理界面，访问 `/admin` 即可管理 SMTP 配置和邮件任务。

## 🔧 故障排除

### 常见问题

**Q: 邮件发送失败，提示连接超时**

A: 检查 SMTP 服务器配置和网络连接，确保端口未被防火墙阻止。

```bash
# 测试 SMTP 连接
telnet smtp.example.com 587
```

**Q: 异步邮件没有发送**

A: 确保运行了 messenger worker：

```bash
php bin/console messenger:consume async
```

**Q: 定时邮件没有执行**

A: 检查 cron 任务是否正确配置：

```bash
# 添加到 crontab
* * * * * cd /path/to/project && php bin/console smtp-mailer:process-scheduled-mails
```

**Q: 邮件发送到垃圾箱**

A: 检查以下设置：
- SPF 记录配置
- DKIM 签名设置
- 发件人域名信誉
- 邮件内容合规性

### 调试模式

启用详细日志来诊断问题：

```bash
# .env
APP_ENV=dev
SYMFONY_LOG_LEVEL=debug
```

## ⚡ 性能优化

### 大批量邮件发送优化

1. **使用异步处理**
   ```bash
   SMTP_MAILER_ASYNC_ENABLED=true
   ```

2. **调整处理间隔**
   ```bash
   SMTP_MAILER_PROCESS_INTERVAL=30
   ```

3. **配置多个 SMTP 服务器实现负载均衡**
   ```php
   // 在后台管理中添加多个 SMTP 配置
   // 使用加权策略分配流量
   ```

### 监控指标

建议监控以下指标：

- 邮件发送成功率
- 平均发送延迟
- SMTP 服务器状态
- 队列积压情况

### 优化建议

- 对于大量邮件，建议分批发送避免服务器压力
- 使用 Redis 作为 Messenger transport 提高性能
- 定期清理已发送的邮件任务记录

## 🤝 贡献指南

我们欢迎任何形式的贡献！

### 开发环境设置

```bash
git clone https://github.com/tourze/php-monorepo.git
cd php-monorepo/packages/smtp-mailer-bundle
composer install
```

### 运行测试

```bash
# 运行单元测试
./vendor/bin/phpunit

# 运行代码质量检查
php -d memory_limit=2G ./vendor/bin/phpstan analyse

# 运行代码格式检查
./vendor/bin/php-cs-fixer fix --dry-run
```

### 提交规范

请遵循项目的以下规范：

- [PHP 代码规范](../../.cursor/rules/php.mdc)
- [测试规范](../../.cursor/rules/testing.mdc)
- [Git 提交规范](../../.cursor/rules/git.mdc)

### 报告问题

如果发现 Bug 或有功能建议，请在 [GitHub Issues](https://github.com/tourze/php-monorepo/issues) 中提交。

## 依赖项

- PHP 8.1+
- Symfony 6.4+
- Doctrine ORM 3.0+
- Symfony Messenger（用于异步处理）

## 许可证

此 Bundle 基于 MIT 许可证。详情请查看 [LICENSE](LICENSE) 文件。