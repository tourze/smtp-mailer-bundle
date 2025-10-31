<?php

namespace Tourze\SMTPMailerBundle\Exception;

/**
 * 邮件任务ID为空异常
 */
class MailTaskIdNullException extends \RuntimeException
{
    public function __construct(string $context = '邮件任务')
    {
        parent::__construct(sprintf('%sID不能为空', $context));
    }
}
