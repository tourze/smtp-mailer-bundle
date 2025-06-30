<?php

namespace Tourze\SMTPMailerBundle\Service;

use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;
use Tourze\SMTPMailerBundle\Repository\SMTPConfigRepository;
use Tourze\SMTPMailerBundle\Service\SMTPSelector\SMTPSelectorStrategyInterface;

/**
 * SMTP选择器服务
 */
class SMTPSelectorService
{
    /**
     * @var array<string, SMTPSelectorStrategyInterface>
     */
    private array $strategies = [];

    private string $defaultStrategy;

    public function __construct(
        private readonly SMTPConfigRepository $smtpConfigRepository,
        #[TaggedIterator(tag: 'smtp_mailer.selector_strategy', indexAttribute: 'key')]
        iterable $strategies,
    ) {
        $this->defaultStrategy = $_ENV['SMTP_MAILER_DEFAULT_STRATEGY'] ?? 'round_robin';

        foreach ($strategies as $key => $strategy) {
            $this->strategies[$key] = $strategy;
        }

        // 添加默认策略的别名
        if (!isset($this->strategies[$this->defaultStrategy]) && count($this->strategies) > 0) {
            // 如果默认策略不存在，使用第一个可用的策略
            $this->defaultStrategy = array_key_first($this->strategies);
        }
    }

    /**
     * 使用指定策略选择SMTP配置
     */
    public function selectConfig(?string $strategy = null): ?SMTPConfig
    {
        $strategy = $strategy ?? $this->defaultStrategy;

        // 获取所有启用的SMTP配置
        $configs = $this->smtpConfigRepository->findAllEnabled();

        if (empty($configs)) {
            return null;
        }

        // 如果指定了策略，使用该策略
        if (isset($this->strategies[$strategy])) {
            return $this->strategies[$strategy]->select($configs);
        }

        // 如果指定的策略不存在，使用默认策略
        if (isset($this->strategies[$this->defaultStrategy])) {
            return $this->strategies[$this->defaultStrategy]->select($configs);
        }

        // 如果连默认策略都不存在，就返回第一个配置
        return $configs[0];
    }

    /**
     * 获取可用的策略列表
     *
     * @return array<string, string> 策略名称映射
     */
    public function getAvailableStrategies(): array
    {
        $result = [];
        foreach ($this->strategies as $key => $strategy) {
            $result[$key] = $strategy->getName();
        }
        return $result;
    }

    /**
     * 获取默认策略
     */
    public function getDefaultStrategy(): string
    {
        return $this->defaultStrategy;
    }
}
