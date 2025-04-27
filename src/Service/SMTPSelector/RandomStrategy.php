<?php

namespace Tourze\SMTPMailerBundle\Service\SMTPSelector;

use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;

/**
 * 随机策略实现
 */
#[AsTaggedItem(index: 'random')]
class RandomStrategy implements SMTPSelectorStrategyInterface
{
    /**
     * @inheritDoc
     */
    public function select(array $configs): ?SMTPConfig
    {
        if (empty($configs)) {
            return null;
        }

        $randomIndex = random_int(0, count($configs) - 1);
        return $configs[$randomIndex];
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'random';
    }
}
