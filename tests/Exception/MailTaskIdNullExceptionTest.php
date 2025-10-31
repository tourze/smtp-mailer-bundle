<?php

namespace Tourze\SMTPMailerBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\SMTPMailerBundle\Exception\MailTaskIdNullException;

/**
 * @internal
 */
#[CoversClass(MailTaskIdNullException::class)]
final class MailTaskIdNullExceptionTest extends AbstractExceptionTestCase
{
    public function testConstructor(): void
    {
        $exception = new MailTaskIdNullException();

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertEquals('邮件任务ID不能为空', $exception->getMessage());
    }

    public function testConstructorWithCustomContext(): void
    {
        $context = '计划任务';
        $exception = new MailTaskIdNullException($context);

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertEquals('计划任务ID不能为空', $exception->getMessage());
    }
}
