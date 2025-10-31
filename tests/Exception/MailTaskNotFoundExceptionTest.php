<?php

namespace Tourze\SMTPMailerBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\SMTPMailerBundle\Exception\MailTaskNotFoundException;

/**
 * @internal
 */
#[CoversClass(MailTaskNotFoundException::class)]
final class MailTaskNotFoundExceptionTest extends AbstractExceptionTestCase
{
    public function testConstructor(): void
    {
        $mailTaskId = 123;
        $exception = new MailTaskNotFoundException($mailTaskId);

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertEquals('邮件任务不存在: 123', $exception->getMessage());
    }
}
