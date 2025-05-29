<?php

namespace Tourze\SMTPMailerBundle\Service\SMTPSelector;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;

/**
 * SMTP选择策略接口
 */
#[AutoconfigureTag('smtp_mailer.selector_strategy')]
interface SMTPSelectorStrategyInterface
{
    /**
     * 根据策略从配置列表中选择一个SMTP配置
     *
     * @param array<SMTPConfig> $configs 可用的SMTP配置列表
     * @return SMTPConfig|null 选中的SMTP配置，如果没有可用配置则返回null
     */
    public function select(array $configs): ?SMTPConfig;

    /**
     * 获取策略名称
     *
     * @return string 策略名称
     */
    public function getName(): string;
}
