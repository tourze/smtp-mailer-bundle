<?php

namespace Tourze\SMTPMailerBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;

/**
 * SMTP配置数据填充
 * 用于创建测试和演示用的SMTP服务器配置
 */
#[When(env: 'dev')]
#[When(env: 'test')]
class SMTPConfigFixtures extends Fixture
{
    // 定义引用常量，供其他Fixture使用
    public const GMAIL_SMTP_REFERENCE = 'gmail-smtp';
    public const OUTLOOK_SMTP_REFERENCE = 'outlook-smtp';
    public const QQ_SMTP_REFERENCE = 'qq-smtp';
    public const ALIYUN_SMTP_REFERENCE = 'aliyun-smtp';
    public const TENCENT_SMTP_REFERENCE = 'tencent-smtp';

    public function load(ObjectManager $manager): void
    {
        // 创建Gmail SMTP配置
        $gmailConfig = new SMTPConfig();
        $gmailConfig->setName('Gmail SMTP');
        $gmailConfig->setHost('smtp.gmail.com');
        $gmailConfig->setPort(587);
        $gmailConfig->setUsername('your-email@gmail.com');
        $gmailConfig->setPassword('your-app-password');
        $gmailConfig->setEncryption('tls');
        $gmailConfig->setTimeout(30);
        $gmailConfig->setAuthMode('login');
        $gmailConfig->setWeight(10);
        $gmailConfig->setPriority(100);
        $gmailConfig->setValid(true);

        $manager->persist($gmailConfig);
        $this->addReference(self::GMAIL_SMTP_REFERENCE, $gmailConfig);

        // 创建Outlook SMTP配置
        $outlookConfig = new SMTPConfig();
        $outlookConfig->setName('Outlook SMTP');
        $outlookConfig->setHost('smtp-mail.outlook.com');
        $outlookConfig->setPort(587);
        $outlookConfig->setUsername('your-email@outlook.com');
        $outlookConfig->setPassword('your-password');
        $outlookConfig->setEncryption('tls');
        $outlookConfig->setTimeout(30);
        $outlookConfig->setAuthMode('login');
        $outlookConfig->setWeight(8);
        $outlookConfig->setPriority(90);
        $outlookConfig->setValid(true);

        $manager->persist($outlookConfig);
        $this->addReference(self::OUTLOOK_SMTP_REFERENCE, $outlookConfig);

        // 创建QQ邮箱SMTP配置
        $qqConfig = new SMTPConfig();
        $qqConfig->setName('QQ邮箱 SMTP');
        $qqConfig->setHost('smtp.qq.com');
        $qqConfig->setPort(587);
        $qqConfig->setUsername('your-email@qq.com');
        $qqConfig->setPassword('your-authorization-code');
        $qqConfig->setEncryption('tls');
        $qqConfig->setTimeout(30);
        $qqConfig->setAuthMode('login');
        $qqConfig->setWeight(6);
        $qqConfig->setPriority(80);
        $qqConfig->setValid(true);

        $manager->persist($qqConfig);
        $this->addReference(self::QQ_SMTP_REFERENCE, $qqConfig);

        // 创建阿里云邮件推送SMTP配置
        $aliyunConfig = new SMTPConfig();
        $aliyunConfig->setName('阿里云邮件推送');
        $aliyunConfig->setHost('smtpdm.aliyun.com');
        $aliyunConfig->setPort(25);
        $aliyunConfig->setUsername('your-username@your-domain.com');
        $aliyunConfig->setPassword('your-smtp-password');
        $aliyunConfig->setEncryption('none');
        $aliyunConfig->setTimeout(30);
        $aliyunConfig->setAuthMode('login');
        $aliyunConfig->setWeight(15);
        $aliyunConfig->setPriority(110);
        $aliyunConfig->setValid(true);

        $manager->persist($aliyunConfig);
        $this->addReference(self::ALIYUN_SMTP_REFERENCE, $aliyunConfig);

        // 创建腾讯云邮件推送SMTP配置
        $tencentConfig = new SMTPConfig();
        $tencentConfig->setName('腾讯云邮件推送');
        $tencentConfig->setHost('smtp.qcloudmail.com');
        $tencentConfig->setPort(587);
        $tencentConfig->setUsername('your-username@your-domain.com');
        $tencentConfig->setPassword('your-smtp-password');
        $tencentConfig->setEncryption('tls');
        $tencentConfig->setTimeout(30);
        $tencentConfig->setAuthMode('login');
        $tencentConfig->setWeight(12);
        $tencentConfig->setPriority(105);
        $tencentConfig->setValid(true);

        $manager->persist($tencentConfig);
        $this->addReference(self::TENCENT_SMTP_REFERENCE, $tencentConfig);

        // 创建一个禁用的SMTP配置用于测试
        $disabledConfig = new SMTPConfig();
        $disabledConfig->setName('禁用的SMTP配置');
        $disabledConfig->setHost('smtp.disabled.com');
        $disabledConfig->setPort(587);
        $disabledConfig->setUsername('disabled@test.unsplash.com');
        $disabledConfig->setPassword('password');
        $disabledConfig->setEncryption('tls');
        $disabledConfig->setTimeout(30);
        $disabledConfig->setWeight(1);
        $disabledConfig->setPriority(10);
        $disabledConfig->setValid(false); // 禁用状态

        $manager->persist($disabledConfig);

        $manager->flush();
    }
}
