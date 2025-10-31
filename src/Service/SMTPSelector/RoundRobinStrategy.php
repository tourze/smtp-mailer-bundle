<?php

namespace Tourze\SMTPMailerBundle\Service\SMTPSelector;

use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;

/**
 * 轮询策略实现
 */
#[AsTaggedItem(index: 'round_robin')]
class RoundRobinStrategy implements SMTPSelectorStrategyInterface
{
    private int $lastIndex = -1;

    public function select(array $configs): ?SMTPConfig
    {
        if (0 === count($configs)) {
            return null;
        }

        $count = count($configs);
        $this->lastIndex = ($this->lastIndex + 1) % $count;

        return $configs[$this->lastIndex];
    }

    public function getName(): string
    {
        return 'round_robin';
    }
}
