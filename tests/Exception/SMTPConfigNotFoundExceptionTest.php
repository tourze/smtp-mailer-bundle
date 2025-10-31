<?php

namespace Tourze\SMTPMailerBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\SMTPMailerBundle\Exception\SMTPConfigNotFoundException;

/**
 * @internal
 */
#[CoversClass(SMTPConfigNotFoundException::class)]
final class SMTPConfigNotFoundExceptionTest extends AbstractExceptionTestCase
{
    public function testConstructor(): void
    {
        $configId = 456;
        $exception = new SMTPConfigNotFoundException($configId);

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertEquals('SMTP配置不存在: 456', $exception->getMessage());
    }
}
