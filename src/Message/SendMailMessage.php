<?php

namespace Tourze\SMTPMailerBundle\Message;

/**
 * 异步邮件发送消息
 */
class SendMailMessage
{
    public function __construct(
        private readonly int $mailTaskId,
    ) {
    }

    /**
     * 获取邮件任务ID
     */
    public function getMailTaskId(): int
    {
        return $this->mailTaskId;
    }
}
