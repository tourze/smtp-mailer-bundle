<?php

namespace Tourze\SMTPMailerBundle\Tests\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\SMTPMailerBundle\Message\SendMailMessage;

/**
 * @internal
 */
#[CoversClass(SendMailMessage::class)]
final class SendMailMessageTest extends TestCase
{
    public function testConstruction(): void
    {
        $taskId = 123;
        $message = new SendMailMessage($taskId);

        $this->assertSame($taskId, $message->getMailTaskId());
    }
}
