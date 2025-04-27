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

在 `config/packages/smtp_mailer.yaml` 中添加配置：

```yaml
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

如果使用 Symfony Flex，bundle 会自动注册。否则，需要手动在 `config/bundles.php` 中添加：

```php
return [
    // ...
    Tourze\SMTPMailerBundle\SMTPMailerBundle::class => ['all' => true],
];
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
