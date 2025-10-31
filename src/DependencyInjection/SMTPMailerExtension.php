<?php

namespace Tourze\SMTPMailerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

#[Autoconfigure(public: true)]
class SMTPMailerExtension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }
}
