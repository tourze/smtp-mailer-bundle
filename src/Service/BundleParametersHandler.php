<?php

namespace Tourze\SMTPMailerBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;
use Tourze\SMTPMailerBundle\Repository\SMTPConfigRepository;

/**
 * Bundle参数处理器
 */
class BundleParametersHandler
{
    private bool $initialized = false;

    public function __construct(
        private readonly array $smtpConfigs,
        private readonly EntityManagerInterface $entityManager,
        private readonly SMTPConfigRepository $smtpConfigRepository,
    ) {
    }

    /**
     * 在内核启动时处理配置参数
     */
    public function onKernelBoot(KernelEvent $event): void
    {
        if ($this->initialized || empty($this->smtpConfigs)) {
            return;
        }

        $this->initialized = true;

        // 导入预配置的SMTP配置
        foreach ($this->smtpConfigs as $name => $config) {
            $this->importSmtpConfig($name, $config);
        }
    }

    /**
     * 导入SMTP配置
     */
    private function importSmtpConfig(string $name, array $config): void
    {
        // 检查是否已存在同名配置
        $existingConfig = $this->smtpConfigRepository->findOneBy(['name' => $name]);

        if ($existingConfig) {
            // 如果配置已存在，检查是否需要更新
            $updated = false;

            // 检查每个属性是否需要更新
            if ($existingConfig->getHost() !== $config['host']) {
                $existingConfig->setHost($config['host']);
                $updated = true;
            }

            if ($existingConfig->getPort() !== ($config['port'] ?? 587)) {
                $existingConfig->setPort($config['port'] ?? 587);
                $updated = true;
            }

            if ($existingConfig->getUsername() !== ($config['username'] ?? null)) {
                $existingConfig->setUsername($config['username'] ?? null);
                $updated = true;
            }

            if (isset($config['password']) && $existingConfig->getPassword() !== $config['password']) {
                $existingConfig->setPassword($config['password']);
                $updated = true;
            }

            if ($existingConfig->getEncryption() !== ($config['encryption'] ?? 'tls')) {
                $existingConfig->setEncryption($config['encryption'] ?? 'tls');
                $updated = true;
            }

            if ($existingConfig->getTimeout() !== ($config['timeout'] ?? 30)) {
                $existingConfig->setTimeout($config['timeout'] ?? 30);
                $updated = true;
            }

            if ($existingConfig->getAuthMode() !== ($config['auth_mode'] ?? null)) {
                $existingConfig->setAuthMode($config['auth_mode'] ?? null);
                $updated = true;
            }

            if ($existingConfig->getWeight() !== ($config['weight'] ?? 1)) {
                $existingConfig->setWeight($config['weight'] ?? 1);
                $updated = true;
            }

            if ($existingConfig->getPriority() !== ($config['priority'] ?? 0)) {
                $existingConfig->setPriority($config['priority'] ?? 0);
                $updated = true;
            }

            // 只有当有属性更新时才保存
            if ($updated) {
                $this->entityManager->flush();
            }
        } else {
            // 如果配置不存在，创建新配置
            $smtpConfig = new SMTPConfig();
            $smtpConfig->setName($name);
            $smtpConfig->setHost($config['host']);
            $smtpConfig->setPort($config['port'] ?? 587);

            if (isset($config['username'])) {
                $smtpConfig->setUsername($config['username']);
            }

            if (isset($config['password'])) {
                $smtpConfig->setPassword($config['password']);
            }

            $smtpConfig->setEncryption($config['encryption'] ?? 'tls');
            $smtpConfig->setTimeout($config['timeout'] ?? 30);

            if (isset($config['auth_mode'])) {
                $smtpConfig->setAuthMode($config['auth_mode']);
            }

            $smtpConfig->setWeight($config['weight'] ?? 1);
            $smtpConfig->setPriority($config['priority'] ?? 0);
            $smtpConfig->setEnabled(true);

            $this->entityManager->persist($smtpConfig);
            $this->entityManager->flush();
        }
    }
}
