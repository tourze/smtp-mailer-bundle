# SMTP Mailer Bundle

一个功能丰富的 Symfony Bundle，用于管理多个 SMTP 配置并支持灵活的邮件发送策略。

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

你可以在 `.env` 文件中设置这些环境变量：

```ini
# .env
SMTP_MAILER_ASYNC_ENABLED=true
SMTP_MAILER_DEFAULT_FROM_EMAIL=support@mycompany.com
SMTP_MAILER_DEFAULT_STRATEGY=priority
SMTP_MAILER_PROCESS_INTERVAL=30
```

### 不同环境的配置示例

```bash
# 开发环境 - 快速处理，同步发送便于调试
export SMTP_MAILER_ASYNC_ENABLED=false
export SMTP_MAILER_DEFAULT_FROM_EMAIL=dev@localhost
export SMTP_MAILER_DEFAULT_STRATEGY=round_robin
export SMTP_MAILER_PROCESS_INTERVAL=10

# 生产环境 - 异步发送，优先级策略
export SMTP_MAILER_ASYNC_ENABLED=true
export SMTP_MAILER_DEFAULT_FROM_EMAIL=noreply@yourcompany.com
export SMTP_MAILER_DEFAULT_STRATEGY=priority
export SMTP_MAILER_PROCESS_INTERVAL=60

# 测试环境 - 同步发送，随机策略
export SMTP_MAILER_ASYNC_ENABLED=false
export SMTP_MAILER_DEFAULT_FROM_EMAIL=test@example.com
export SMTP_MAILER_DEFAULT_STRATEGY=random
export SMTP_MAILER_PROCESS_INTERVAL=30
```

## 使用

### 基本发送

```php
// 注入服务
use Tourze\SMTPMailerBundle\Service\SMTPMailerService;

class MyController extends AbstractController
{
    public function sendEmail(SMTPMailerService $mailerService)
    {
        $mailerService->send(
            'recipient@example.com',
            '邮件主题',
            '邮件内容',
            [
                'from' => 'sender@example.com',
                'isHtml' => true,
                'cc' => ['cc@example.com'],
            ]
        );
        
        // 或者安排稍后发送
        $mailerService->send(
            'recipient@example.com',
            '定时邮件',
            '这是一封定时发送的邮件',
            [
                'scheduledAt' => new \DateTime('+1 hour')
            ]
        );
    }
}
```

### 指定SMTP配置发送

```php
// 使用特定ID的SMTP配置
$mailerService->sendWithConfig(
    $smtpConfigId,
    'recipient@example.com',
    '邮件主题',
    '邮件内容'
);
```

### 使用不同的选择策略

```php
// 使用优先级策略选择SMTP
$mailerService->send(
    'recipient@example.com',
    '邮件主题',
    '邮件内容',
    [
        'strategy' => 'priority'
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

## 许可证

此 Bundle 基于 MIT 许可证。详情请查看 [LICENSE](LICENSE) 文件。
