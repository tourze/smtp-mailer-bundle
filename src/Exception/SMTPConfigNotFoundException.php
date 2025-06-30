<?php

namespace Tourze\SMTPMailerBundle\Exception;

/**
 * SMTP配置不存在异常
 */
class SMTPConfigNotFoundException extends \RuntimeException
{
    public function __construct(int $configId)
    {
        parent::__construct(sprintf('SMTP配置不存在: %d', $configId));
    }
}
