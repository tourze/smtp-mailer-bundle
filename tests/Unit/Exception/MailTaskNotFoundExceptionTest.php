<?php

namespace Tourze\SMTPMailerBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\SMTPMailerBundle\Exception\MailTaskNotFoundException;

class MailTaskNotFoundExceptionTest extends TestCase
{
    public function testConstructor(): void
    {
        $mailTaskId = 123;
        $exception = new MailTaskNotFoundException($mailTaskId);
        
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertEquals('邮件任务不存在: 123', $exception->getMessage());
    }
}