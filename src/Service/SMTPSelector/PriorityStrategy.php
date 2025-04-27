<?php

namespace Tourze\SMTPMailerBundle\Service\SMTPSelector;

use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;

/**
 * 优先级策略实现
 */
#[AsTaggedItem(index: 'priority')]
class PriorityStrategy implements SMTPSelectorStrategyInterface
{
    /**
     * @inheritDoc
     */
    public function select(array $configs): ?SMTPConfig
    {
        if (empty($configs)) {
            return null;
        }

        // 按优先级排序（优先级越高，值越大）
        usort($configs, function (SMTPConfig $a, SMTPConfig $b) {
            return $b->getPriority() <=> $a->getPriority();
        });

        // 找到所有具有最高优先级的配置
        $highestPriority = $configs[0]->getPriority();
        $highestPriorityConfigs = [];

        foreach ($configs as $config) {
            if ($config->getPriority() === $highestPriority) {
                $highestPriorityConfigs[] = $config;
            } else {
                break;
            }
        }

        // 如果有多个配置具有相同的最高优先级，则随机选择其中一个
        if (count($highestPriorityConfigs) > 1) {
            return (new RandomStrategy())->select($highestPriorityConfigs);
        }

        return $highestPriorityConfigs[0];
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'priority';
    }
}
