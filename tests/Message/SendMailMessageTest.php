<?php

namespace Tourze\SMTPMailerBundle\Tests\Message;

use PHPUnit\Framework\TestCase;
use Tourze\SMTPMailerBundle\Message\SendMailMessage;

class SendMailMessageTest extends TestCase
{
    public function testConstruction(): void
    {
        $taskId = 123;
        $message = new SendMailMessage($taskId);
        
        $this->assertSame($taskId, $message->getMailTaskId());
    }
} 