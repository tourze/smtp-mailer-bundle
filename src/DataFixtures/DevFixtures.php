<?php

namespace Tourze\SMTPMailerBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\SMTPMailerBundle\Entity\MailTask;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;
use Tourze\SMTPMailerBundle\Enum\MailTaskStatus;

/**
 * 开发环境数据填充
 * 用于开发和测试环境的额外测试数据
 */
#[When(env: 'dev')]
#[When(env: 'test')]
class DevFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        // 获取SMTP配置引用
        $gmailConfig = $this->getReference(SMTPConfigFixtures::GMAIL_SMTP_REFERENCE, SMTPConfig::class);
        $outlookConfig = $this->getReference(SMTPConfigFixtures::OUTLOOK_SMTP_REFERENCE, SMTPConfig::class);

        // 创建大量测试邮件任务用于性能测试
        $this->createBulkTestTasks($manager, $gmailConfig, $outlookConfig);

        // 创建各种边界情况的测试数据
        $this->createEdgeCaseTestTasks($manager, $gmailConfig);

        // 创建本地测试SMTP配置
        $this->createLocalTestSmtpConfigs($manager);

        $manager->flush();
    }

    /**
     * 创建批量测试任务
     */
    private function createBulkTestTasks(ObjectManager $manager, SMTPConfig $gmailConfig, SMTPConfig $outlookConfig): void
    {
        $statuses = [MailTaskStatus::PENDING, MailTaskStatus::SENT, MailTaskStatus::FAILED, MailTaskStatus::PROCESSING];
        $strategies = ['round_robin', 'random', 'weighted', 'priority'];
        $configs = [$gmailConfig, $outlookConfig, null]; // null表示使用策略选择

        for ($i = 1; $i <= 50; ++$i) {
            $task = new MailTask();
            $task->setFromEmail("test{$i}@test.unsplash.com");
            $task->setFromName("测试发件人 {$i}");
            $task->setToEmail("recipient{$i}@test.unsplash.com");
            $task->setToName("测试收件人 {$i}");
            $task->setSubject("批量测试邮件 #{$i}");
            $task->setBody("这是第 {$i} 封测试邮件的内容。用于测试系统的批量处理能力。");
            $task->setIsHtml(false);

            // 随机分配状态
            $task->setStatus($statuses[array_rand($statuses)]);

            // 随机分配策略
            $task->setSelectorStrategy($strategies[array_rand($strategies)]);

            // 随机分配SMTP配置
            $config = $configs[array_rand($configs)];
            if (null !== $config) {
                $task->setSmtpConfig($config);
            }

            // 部分任务设置为计划发送
            if (0 === $i % 10) {
                $task->setScheduledTime(new \DateTimeImmutable('+' . rand(1, 24) . ' hours'));
            }

            // 部分任务添加抄送
            if (0 === $i % 7) {
                $task->setCc(["cc{$i}@test.unsplash.com", "manager{$i}@test.unsplash.com"]);
            }

            // 部分任务添加密送
            if (0 === $i % 11) {
                $task->setBcc(["bcc{$i}@test.unsplash.com"]);
            }

            $manager->persist($task);
        }
    }

    /**
     * 创建边界情况测试任务
     */
    private function createEdgeCaseTestTasks(ObjectManager $manager, SMTPConfig $gmailConfig): void
    {
        // 超长主题的邮件
        $longSubjectTask = new MailTask();
        $longSubjectTask->setFromEmail('test@test.unsplash.com');
        $longSubjectTask->setToEmail('recipient@test.unsplash.com');
        $longSubjectTask->setSubject(str_repeat('这是一个非常长的邮件主题，用于测试系统对超长主题的处理能力。', 10));
        $longSubjectTask->setBody('测试超长主题的邮件内容');
        $longSubjectTask->setSmtpConfig($gmailConfig);
        $longSubjectTask->setStatus(MailTaskStatus::PENDING);

        $manager->persist($longSubjectTask);

        // 超长内容的邮件
        $longBodyTask = new MailTask();
        $longBodyTask->setFromEmail('test@test.unsplash.com');
        $longBodyTask->setToEmail('recipient@test.unsplash.com');
        $longBodyTask->setSubject('超长内容测试邮件');
        $longBodyTask->setBody(str_repeat('这是一段很长的邮件内容，用于测试系统对大容量邮件的处理能力。', 1000));
        $longBodyTask->setIsHtml(false);
        $longBodyTask->setSmtpConfig($gmailConfig);
        $longBodyTask->setStatus(MailTaskStatus::PENDING);

        $manager->persist($longBodyTask);

        // 包含特殊字符的邮件
        $specialCharsTask = new MailTask();
        $specialCharsTask->setFromEmail('test@test.unsplash.com');
        $specialCharsTask->setFromName('测试用户 🚀');
        $specialCharsTask->setToEmail('recipient@test.unsplash.com');
        $specialCharsTask->setToName('收件人 ✨');
        $specialCharsTask->setSubject('特殊字符测试 📧 ♥ ★ ☆ ♠ ♣ ♦ ♥');
        $specialCharsTask->setBody('
            测试各种特殊字符：
            中文：你好世界
            日文：こんにちは世界
            韩文：안녕하세요 세계
            阿拉伯文：مرحبا بالعالم
            俄文：Привет мир
            表情符号：😀 😃 😄 😁 😆 😅 😂 🤣
            数学符号：∑ ∏ ∫ ∂ ∇ ∞ ± × ÷
            货币符号：$ € £ ¥ ₹ ₽
        ');
        $specialCharsTask->setIsHtml(false);
        $specialCharsTask->setSmtpConfig($gmailConfig);
        $specialCharsTask->setStatus(MailTaskStatus::PENDING);

        $manager->persist($specialCharsTask);

        // 大量抄送和密送的邮件
        $massEmailTask = new MailTask();
        $massEmailTask->setFromEmail('newsletter@test.unsplash.com');
        $massEmailTask->setToEmail('primary@test.unsplash.com');
        $massEmailTask->setSubject('大量收件人测试邮件');
        $massEmailTask->setBody('这是一封测试大量收件人的邮件');

        // 生成大量抄送地址
        $ccList = [];
        for ($i = 1; $i <= 20; ++$i) {
            $ccList[] = "cc{$i}@test.unsplash.com";
        }
        $massEmailTask->setCc($ccList);

        // 生成大量密送地址
        $bccList = [];
        for ($i = 1; $i <= 30; ++$i) {
            $bccList[] = "bcc{$i}@test.unsplash.com";
        }
        $massEmailTask->setBcc($bccList);

        $massEmailTask->setSmtpConfig($gmailConfig);
        $massEmailTask->setStatus(MailTaskStatus::PENDING);

        $manager->persist($massEmailTask);

        // 复杂HTML邮件
        $complexHtmlTask = new MailTask();
        $complexHtmlTask->setFromEmail('design@test.unsplash.com');
        $complexHtmlTask->setFromName('设计团队');
        $complexHtmlTask->setToEmail('client@test.unsplash.com');
        $complexHtmlTask->setToName('客户');
        $complexHtmlTask->setSubject('复杂HTML邮件模板测试');
        $complexHtmlTask->setBody($this->getComplexHtmlTemplate());
        $complexHtmlTask->setIsHtml(true);
        $complexHtmlTask->setSmtpConfig($gmailConfig);
        $complexHtmlTask->setStatus(MailTaskStatus::PENDING);

        $manager->persist($complexHtmlTask);
    }

    /**
     * 创建本地测试SMTP配置
     */
    private function createLocalTestSmtpConfigs(ObjectManager $manager): void
    {
        // MailHog 本地测试配置
        $mailhogConfig = new SMTPConfig();
        $mailhogConfig->setName('MailHog 本地测试');
        $mailhogConfig->setHost('localhost');
        $mailhogConfig->setPort(1025);
        $mailhogConfig->setEncryption('none');
        $mailhogConfig->setTimeout(10);
        $mailhogConfig->setWeight(1);
        $mailhogConfig->setPriority(1);
        $mailhogConfig->setValid(true);

        $manager->persist($mailhogConfig);

        // Mailtrap 测试配置
        $mailtrapConfig = new SMTPConfig();
        $mailtrapConfig->setName('Mailtrap 测试');
        $mailtrapConfig->setHost('smtp.mailtrap.io');
        $mailtrapConfig->setPort(2525);
        $mailtrapConfig->setUsername('your-mailtrap-username');
        $mailtrapConfig->setPassword('your-mailtrap-password');
        $mailtrapConfig->setEncryption('tls');
        $mailtrapConfig->setTimeout(30);
        $mailtrapConfig->setAuthMode('login');
        $mailtrapConfig->setWeight(5);
        $mailtrapConfig->setPriority(50);
        $mailtrapConfig->setValid(true);

        $manager->persist($mailtrapConfig);

        // 模拟的高延迟SMTP配置
        $slowSmtpConfig = new SMTPConfig();
        $slowSmtpConfig->setName('高延迟SMTP测试');
        $slowSmtpConfig->setHost('slow-smtp.test.unsplash.com');
        $slowSmtpConfig->setPort(587);
        $slowSmtpConfig->setUsername('slow@test.unsplash.com');
        $slowSmtpConfig->setPassword('password');
        $slowSmtpConfig->setEncryption('tls');
        $slowSmtpConfig->setTimeout(120); // 2分钟超时
        $slowSmtpConfig->setAuthMode('login');
        $slowSmtpConfig->setWeight(1);
        $slowSmtpConfig->setPriority(10);
        $slowSmtpConfig->setValid(false); // 默认禁用

        $manager->persist($slowSmtpConfig);
    }

    /**
     * 获取复杂HTML模板
     */
    private function getComplexHtmlTemplate(): string
    {
        return '
        <!DOCTYPE html>
        <html lang="zh-CN">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>复杂HTML邮件模板</title>
            <style>
                body { font-family: "Helvetica Neue", Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .button { display: inline-block; padding: 12px 30px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
                .footer { background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #6c757d; }
                .grid { display: table; width: 100%; }
                .grid-item { display: table-cell; width: 50%; padding: 10px; vertical-align: top; }
                .highlight { background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 15px 0; }
                @media only screen and (max-width: 600px) {
                    .grid-item { display: block; width: 100%; }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🎨 复杂HTML邮件模板测试</h1>
                    <p>测试各种HTML元素和CSS样式</p>
                </div>
                
                <div class="content">
                    <h2>功能特性</h2>
                    <div class="grid">
                        <div class="grid-item">
                            <h3>📱 响应式设计</h3>
                            <p>完美适配各种设备屏幕</p>
                        </div>
                        <div class="grid-item">
                            <h3>🎯 精准投递</h3>
                            <p>确保邮件准确送达</p>
                        </div>
                    </div>
                    
                    <div class="highlight">
                        <h3>⚠️ 重要提醒</h3>
                        <p>这是一个高亮显示的重要信息框，用于测试CSS样式的兼容性。</p>
                    </div>
                    
                    <h3>📊 数据统计</h3>
                    <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                        <thead>
                            <tr style="background-color: #f8f9fa;">
                                <th style="border: 1px solid #dee2e6; padding: 12px; text-align: left;">指标</th>
                                <th style="border: 1px solid #dee2e6; padding: 12px; text-align: right;">数值</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="border: 1px solid #dee2e6; padding: 12px;">发送成功率</td>
                                <td style="border: 1px solid #dee2e6; padding: 12px; text-align: right;">99.9%</td>
                            </tr>
                            <tr style="background-color: #f8f9fa;">
                                <td style="border: 1px solid #dee2e6; padding: 12px;">平均响应时间</td>
                                <td style="border: 1px solid #dee2e6; padding: 12px; text-align: right;">0.5秒</td>
                            </tr>
                            <tr>
                                <td style="border: 1px solid #dee2e6; padding: 12px;">用户满意度</td>
                                <td style="border: 1px solid #dee2e6; padding: 12px; text-align: right;">4.8/5.0</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <p style="text-align: center;">
                        <a href="https://unsplash.com/action" class="button">立即体验</a>
                        <a href="https://unsplash.com/learn-more" class="button" style="background-color: #28a745;">了解更多</a>
                    </p>
                    
                    <h3>🌟 用户评价</h3>
                    <blockquote style="border-left: 4px solid #007bff; padding-left: 15px; margin: 20px 0; font-style: italic; color: #6c757d;">
                        "这个邮件系统真的很棒！界面美观，功能强大，使用起来非常方便。"
                        <br><strong>- 张先生，企业用户</strong>
                    </blockquote>
                </div>
                
                <div class="footer">
                    <p>© 2024 SMTP Mailer Bundle. 保留所有权利。</p>
                    <p>
                        <a href="https://unsplash.com/unsubscribe" style="color: #6c757d;">退订</a> |
                        <a href="https://unsplash.com/privacy" style="color: #6c757d;">隐私政策</a> |
                        <a href="https://unsplash.com/contact" style="color: #6c757d;">联系我们</a>
                    </p>
                </div>
            </div>
        </body>
        </html>';
    }

    /**
     * 获取依赖的Fixture类
     */
    public function getDependencies(): array
    {
        return [
            SMTPConfigFixtures::class,
            MailTaskFixtures::class,
        ];
    }

    /**
     * 定义Fixture组
     */
    public static function getGroups(): array
    {
        return ['dev', 'test'];
    }
}
