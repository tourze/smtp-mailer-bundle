<?php

namespace Tourze\SMTPMailerBundle\Exception;

/**
 * 邮件任务不存在异常
 */
class MailTaskNotFoundException extends \RuntimeException
{
    public function __construct(int $mailTaskId)
    {
        parent::__construct(sprintf('邮件任务不存在: %d', $mailTaskId));
    }
}
