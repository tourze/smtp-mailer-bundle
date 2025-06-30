<?php

namespace Tourze\SMTPMailerBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\SMTPMailerBundle\Exception\SMTPConfigNotFoundException;

class SMTPConfigNotFoundExceptionTest extends TestCase
{
    public function testConstructor(): void
    {
        $configId = 456;
        $exception = new SMTPConfigNotFoundException($configId);
        
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertEquals('SMTP配置不存在: 456', $exception->getMessage());
    }
}