<?php

namespace Tourze\SMTPMailerBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;

/**
 * 生产环境数据填充
 * 仅包含生产环境必需的基础配置
 */
class ProdFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        // 创建默认的SMTP配置（需要管理员后续配置具体参数）
        $defaultConfig = new SMTPConfig();
        $defaultConfig->setName('默认SMTP配置');
        $defaultConfig->setHost('smtp.example.com');
        $defaultConfig->setPort(587);
        $defaultConfig->setUsername('your-email@example.com');
        $defaultConfig->setPassword('your-password');
        $defaultConfig->setEncryption('tls');
        $defaultConfig->setTimeout(30);
        $defaultConfig->setAuthMode('login');
        $defaultConfig->setWeight(10);
        $defaultConfig->setPriority(100);
        $defaultConfig->setValid(false); // 默认禁用，需要管理员配置后启用

        $manager->persist($defaultConfig);

        // 创建备用SMTP配置
        $backupConfig = new SMTPConfig();
        $backupConfig->setName('备用SMTP配置');
        $backupConfig->setHost('backup-smtp.example.com');
        $backupConfig->setPort(587);
        $backupConfig->setUsername('backup@example.com');
        $backupConfig->setPassword('backup-password');
        $backupConfig->setEncryption('tls');
        $backupConfig->setTimeout(30);
        $backupConfig->setAuthMode('login');
        $backupConfig->setWeight(5);
        $backupConfig->setPriority(50);
        $backupConfig->setValid(false); // 默认禁用

        $manager->persist($backupConfig);

        $manager->flush();
    }

    /**
     * 定义Fixture组
     */
    public static function getGroups(): array
    {
        return ['prod', 'production'];
    }
}
