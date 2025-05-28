# SMTP Mailer Bundle - 数据填充（DataFixtures）

本目录包含了 SMTP Mailer Bundle 的数据填充类，用于在不同环境中快速创建测试和演示数据。

## 📁 文件结构

```ascii
src/DataFixtures/
├── README.md                 # 本说明文档
├── SMTPConfigFixtures.php    # SMTP配置数据填充
├── MailTaskFixtures.php      # 邮件任务数据填充
├── DevFixtures.php          # 开发环境专用数据填充
└── ProdFixtures.php         # 生产环境最小化数据填充
```

## 🚀 快速开始

### 安装依赖

确保已安装 DoctrineFixturesBundle：

```bash
composer require --dev doctrine/doctrine-fixtures-bundle
```

### 基本使用

```bash
# 加载所有 Fixtures
php bin/console doctrine:fixtures:load

# 加载特定组的 Fixtures
php bin/console doctrine:fixtures:load --group=dev
php bin/console doctrine:fixtures:load --group=prod

# 追加数据（不清空现有数据）
php bin/console doctrine:fixtures:load --append
```

## 📋 Fixture 类说明

### 1. SMTPConfigFixtures

**用途**：创建基础的 SMTP 服务器配置

**包含数据**：

- Gmail SMTP 配置
- Outlook SMTP 配置
- QQ邮箱 SMTP 配置
- 阿里云邮件推送配置
- 腾讯云邮件推送配置
- 一个禁用的测试配置

**引用常量**：

```php
SMTPConfigFixtures::GMAIL_SMTP_REFERENCE
SMTPConfigFixtures::OUTLOOK_SMTP_REFERENCE
SMTPConfigFixtures::QQ_SMTP_REFERENCE
SMTPConfigFixtures::ALIYUN_SMTP_REFERENCE
SMTPConfigFixtures::TENCENT_SMTP_REFERENCE
```

### 2. MailTaskFixtures

**用途**：创建各种类型的邮件任务示例

**依赖**：`SMTPConfigFixtures`

**包含数据**：

- 已发送的欢迎邮件
- 待发送的产品发布通知
- 计划发送的新闻通讯
- 发送失败的技术支持邮件
- 带附件的HR邮件
- 处理中的系统通知
- 群发促销邮件

**特色**：

- 包含丰富的HTML邮件模板
- 演示各种邮件状态
- 展示不同的发送策略
- 包含抄送、密送示例

### 3. DevFixtures

**用途**：开发和测试环境的扩展数据

**依赖**：`SMTPConfigFixtures`, `MailTaskFixtures`

**Fixture组**：`dev`, `test`

**包含数据**：

- 50个批量测试邮件任务
- 边界情况测试数据（超长主题、超长内容、特殊字符等）
- 本地测试SMTP配置（MailHog、Mailtrap等）
- 复杂HTML邮件模板测试

**使用场景**：

- 性能测试
- 功能测试
- 边界条件测试
- 本地开发调试

### 4. ProdFixtures

**用途**：生产环境的最小化配置

**Fixture组**：`prod`, `production`

**包含数据**：

- 默认SMTP配置模板（需要管理员配置）
- 备用SMTP配置模板

**特点**：

- 避免重复创建（检查现有配置）
- 默认禁用状态，需要手动启用
- 最小化数据，适合生产环境

## 🎯 使用场景

### 开发环境

```bash
# 加载完整的开发数据
php bin/console doctrine:fixtures:load --group=dev

# 或者加载基础数据
php bin/console doctrine:fixtures:load
```

### 测试环境

```bash
# 加载测试数据
php bin/console doctrine:fixtures:load --group=test

# 或者加载所有数据进行全面测试
php bin/console doctrine:fixtures:load
```

### 生产环境

```bash
# 仅加载生产环境必需的基础配置
php bin/console doctrine:fixtures:load --group=prod
```

### 演示环境

```bash
# 加载完整数据用于演示
php bin/console doctrine:fixtures:load
```

## ⚙️ 自定义配置

### 修改SMTP配置

编辑 `SMTPConfigFixtures.php` 中的配置信息：

```php
$gmailConfig->setHost('your-smtp-host.com');
$gmailConfig->setUsername('your-email@domain.com');
$gmailConfig->setPassword('your-password');
```

### 添加新的邮件模板

在 `MailTaskFixtures.php` 中添加新的邮件任务：

```php
$customTask = new MailTask();
$customTask->setFromEmail('custom@example.com');
$customTask->setToEmail('recipient@example.com');
$customTask->setSubject('自定义邮件主题');
$customTask->setBody('自定义邮件内容');
// ... 其他配置

$manager->persist($customTask);
```

### 创建自定义Fixture组

```php
class CustomFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['custom', 'my-group'];
    }

    // ... 实现 load 方法
}
```

## 🔧 最佳实践

### 1. 依赖管理

- 使用 `DependentFixtureInterface` 管理Fixture间的依赖关系
- 通过引用（References）在Fixture间共享对象
- 确保依赖顺序正确

### 2. 数据安全

- 生产环境Fixture不包含敏感信息
- 使用占位符代替真实的邮箱和密码
- 默认禁用需要配置的SMTP服务器

### 3. 性能优化

- 批量操作时使用 `flush()` 而不是每次 `persist()` 后都 `flush()`
- 大量数据时考虑分批处理
- 避免在Fixture中进行复杂的业务逻辑

### 4. 测试友好

- 提供各种状态的测试数据
- 包含边界条件和异常情况
- 使用有意义的测试数据便于调试

## 🐛 故障排除

### 常见问题

1. **依赖错误**：确保依赖的Fixture类存在且正确实现
2. **引用错误**：检查引用名称是否正确，确保提供了类型参数
3. **重复数据**：使用 `--append` 参数或在Fixture中检查现有数据
4. **权限问题**：确保数据库用户有足够的权限

### 调试技巧

```bash
# 查看可用的Fixture类
php bin/console doctrine:fixtures:load --help

# 使用详细输出查看执行过程
php bin/console doctrine:fixtures:load -v

# 仅执行特定的Fixture类
php bin/console doctrine:fixtures:load --fixtures=SMTPConfigFixtures
```

## 📚 参考资料

- [DoctrineFixturesBundle 官方文档](https://symfony.com/bundles/DoctrineFixturesBundle/current/index.html)
- [Doctrine ORM 文档](https://www.doctrine-project.org/projects/orm.html)
- [Symfony 最佳实践](https://symfony.com/doc/current/best_practices.html)

## 🤝 贡献

如需添加新的Fixture或改进现有的数据填充，请：

1. 遵循现有的代码风格和命名规范
2. 添加适当的中文注释
3. 确保数据的真实性和有用性
4. 更新相关文档
