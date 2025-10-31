<?php

namespace Tourze\SMTPMailerBundle\Service\SMTPSelector;

use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;

/**
 * 权重策略实现
 */
#[AsTaggedItem(index: 'weighted')]
class WeightedStrategy implements SMTPSelectorStrategyInterface
{
    public function select(array $configs): ?SMTPConfig
    {
        if (0 === count($configs)) {
            return null;
        }

        // 计算总权重
        $totalWeight = 0;
        foreach ($configs as $config) {
            $totalWeight += $config->getWeight();
        }

        if ($totalWeight <= 0) {
            // 如果总权重为0，则随机选择
            return (new RandomStrategy())->select($configs);
        }

        // 随机选择一个权重值
        $randomWeight = random_int(1, $totalWeight);

        // 根据权重选择配置
        $currentWeight = 0;
        foreach ($configs as $config) {
            $currentWeight += $config->getWeight();
            if ($randomWeight <= $currentWeight) {
                return $config;
            }
        }

        // 默认返回第一个（理论上不会到达这里）
        return $configs[0];
    }

    public function getName(): string
    {
        return 'weighted';
    }
}
