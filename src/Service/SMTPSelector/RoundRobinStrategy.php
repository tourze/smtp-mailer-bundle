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

    /**
     * @inheritDoc
     */
    public function select(array $configs): ?SMTPConfig
    {
        if (empty($configs)) {
            return null;
        }

        $count = count($configs);
        $this->lastIndex = ($this->lastIndex + 1) % $count;

        return $configs[$this->lastIndex];
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'round_robin';
    }
}
